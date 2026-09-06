<?php

declare(strict_types=1);

final class Control_receipt
{
	private const ENDPOINT_PATH = '/api/v1/deployment-receipts';

	public static function issue(array $payload, array $configuration, string $receiptDirectory): array
	{
		self::validateConfiguration($configuration);
		if (preg_match('/^[a-f0-9-]{36}$/D', (string) ($payload['receipt_id'] ?? '')) !== 1) throw new RuntimeException('Deployment receipt ID is invalid.');
		self::prepareDirectory($receiptDirectory);
		$path = $receiptDirectory.'/'.$payload['receipt_id'].'.json';
		if (file_exists($path)) throw new RuntimeException('Deployment receipt ID already exists locally.');
		$document = array('schema' => 1, 'payload' => $payload, 'delivery' => array('status' => 'PENDING', 'attempts' => array()));
		self::writeDocument($path, $document);
		return self::deliver($path, $document, $configuration);
	}

	public static function retryPending(array $configuration, string $receiptDirectory): array
	{
		self::validateConfiguration($configuration);
		self::prepareDirectory($receiptDirectory);
		$results = array();
		foreach (glob(rtrim($receiptDirectory, '/').'/*.json') ?: array() as $path) {
			$document = json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
			if (($document['delivery']['status'] ?? '') === 'ACCEPTED') continue;
			$results[] = self::deliver($path, $document, $configuration);
		}
		return $results;
	}

	private static function deliver(string $path, array $document, array $configuration): array
	{
		$payload = (array) ($document['payload'] ?? array());
		$body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
		$timestamp = gmdate(DATE_ATOM);
		$nonce = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
		$idempotency = 'dr:'.(string) ($payload['receipt_id'] ?? '');
		$endpoint = self::endpoint($configuration);
		$canonical = implode("\n", array(
			'POST', self::ENDPOINT_PATH, $configuration['instance_id'], $configuration['key_id'],
			$timestamp, $nonce, $idempotency, hash('sha256', $body),
		));
		$signature = hash_hmac('sha256', $canonical, $configuration['secret']);

		$handle = curl_init($endpoint);
		curl_setopt_array($handle, array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $body,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 15,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
				'User-Agent: Namua-Penatausahaan-Deployment-Receipt/1.0',
				'X-Namua-Instance-ID: '.$configuration['instance_id'],
				'X-Namua-Key-ID: '.$configuration['key_id'],
				'X-Namua-Timestamp: '.$timestamp,
				'X-Namua-Nonce: '.$nonce,
				'X-Namua-Signature: '.$signature,
				'Idempotency-Key: '.$idempotency,
			),
		));
		$response = curl_exec($handle);
		$httpStatus = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
		$error = curl_error($handle);
		curl_close($handle);
		$decoded = is_string($response) ? json_decode($response, true) : null;
		$accepted = $error === '' && in_array($httpStatus, array(200, 202), true)
			&& is_array($decoded) && ($decoded['status'] ?? '') === 'accepted';
		$attempt = array(
			'attempted_at' => $timestamp,
			'key_id' => $configuration['key_id'],
			'nonce' => $nonce,
			'idempotency_key' => $idempotency,
			'payload_sha256' => hash('sha256', $body),
			'signature_hmac_sha256' => $signature,
			'http_status' => $httpStatus,
			'result' => $accepted ? 'ACCEPTED' : 'FAILED',
			'response_code' => is_array($decoded) ? (string) ($decoded['code'] ?? $decoded['status'] ?? '') : 'transport_failed',
		);
		$document['delivery']['attempts'][] = $attempt;
		$document['delivery']['attempts'] = array_slice($document['delivery']['attempts'], -20);
		$document['delivery']['status'] = $accepted ? 'ACCEPTED' : 'PENDING';
		$document['delivery']['last_http_status'] = $httpStatus;
		$document['delivery']['received_at'] = $accepted ? ($decoded['received_at'] ?? null) : null;
		self::writeDocument($path, $document);
		if (!$accepted) throw new RuntimeException('Control rejected or could not receive the deployment receipt (HTTP '.$httpStatus.').');
		return array('receipt_id' => $payload['receipt_id'], 'http_status' => $httpStatus, 'duplicate' => (bool) ($decoded['duplicate'] ?? false));
	}

	private static function endpoint(array $configuration): string
	{
		if (isset($configuration['receipt_endpoint']) && is_string($configuration['receipt_endpoint']) && $configuration['receipt_endpoint'] !== '') {
			$endpoint = $configuration['receipt_endpoint'];
		} else {
			$url = parse_url((string) $configuration['endpoint']);
			if (!is_array($url) || ($url['scheme'] ?? '') !== 'https' || empty($url['host'])) throw new RuntimeException('Control endpoint is invalid.');
			$endpoint = 'https://'.$url['host'].(isset($url['port']) ? ':'.$url['port'] : '').self::ENDPOINT_PATH;
		}
		$url = parse_url($endpoint);
		if (!is_array($url) || ($url['scheme'] ?? '') !== 'https' || ($url['path'] ?? '') !== self::ENDPOINT_PATH || isset($url['query']) || isset($url['fragment'])) {
			throw new RuntimeException('Control deployment receipt endpoint is invalid.');
		}
		return $endpoint;
	}

	private static function validateConfiguration(array $configuration): void
	{
		foreach (array('endpoint','instance_id','key_id','secret','environment') as $key) {
			if (!isset($configuration[$key]) || !is_string($configuration[$key]) || $configuration[$key] === '') throw new RuntimeException('Control configuration is incomplete.');
		}
	}

	private static function prepareDirectory(string $directory): void
	{
		if (is_link($directory)) throw new RuntimeException('Deployment receipt directory is unsafe.');
		if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new RuntimeException('Unable to create deployment receipt directory.');
		chmod($directory, 0700);
		@chown($directory, 'root');
		@chgrp($directory, 'root');
	}

	private static function writeDocument(string $path, array $document): void
	{
		$temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
		if (file_put_contents($temporary, json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL, LOCK_EX) === false) {
			throw new RuntimeException('Unable to write deployment receipt.');
		}
		chmod($temporary, 0600);
		@chown($temporary, 'root');
		@chgrp($temporary, 'root');
		if (!rename($temporary, $path)) {
			@unlink($temporary);
			throw new RuntimeException('Unable to install deployment receipt.');
		}
	}
}
