<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Control_heartbeat
{
	private $CI;
	private $product;
	private $configuration;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->product = (array) $this->CI->config->item('product');
		$this->configuration = $this->load_configuration();
		$this->CI->load->library('schema_state');
	}

	public function snapshot()
	{
		$components = array(
			'web' => 'OK',
			'database' => 'DOWN',
			'migration' => 'UNKNOWN',
			'session' => is_dir(APPPATH.'cache/sessions') && is_writable(APPPATH.'cache/sessions') ? 'OK' : 'DEGRADED',
			'runtime_config' => ! empty($this->configuration) ? 'OK' : 'DEGRADED',
			'backup' => $this->backup_status(),
		);

		try
		{
			$components['database'] = $this->CI->db->query('SELECT 1 AS healthy')->row_array() ? 'OK' : 'DOWN';
			$this->CI->schema_state->current();
			$components['migration'] = 'OK';
		}
		catch (Throwable $exception)
		{
			log_message('error', 'Heartbeat database check failed: '.$exception->getMessage());
		}

		$health = 'OK';
		if (in_array('DOWN', $components, TRUE)) $health = 'DOWN';
		elseif (in_array('DEGRADED', $components, TRUE)) $health = 'DEGRADED';

		$total = @disk_total_space(FCPATH);
		$free = @disk_free_space(FCPATH);
		$disk = $total && $free !== FALSE ? round((($total - $free) / $total) * 100, 2) : NULL;

		return array(
			'health' => $health,
			'components' => $components,
			'metrics' => array(
				'queue_pending' => 0,
				'queue_failed' => 0,
				'disk_used_percent' => $disk,
			),
		);
	}

	public function send()
	{
		$this->assert_configuration();
		$snapshot = $this->snapshot();
		try
		{
			$schema_version = $this->CI->schema_state->current();
		}
		catch (Throwable $exception)
		{
			$schema_version = 'unknown';
		}
		$timestamp = date(DATE_ATOM);
		$nonce = $this->base64url(random_bytes(24));
		$idempotency = 'hb:'.gmdate('YmdHis').':'.bin2hex(random_bytes(8));
		$payload = array(
			'instance_id' => $this->configuration['instance_id'],
			'sent_at' => $timestamp,
			'app_version' => $this->product['version'],
			'schema_version' => $schema_version,
			'environment' => $this->configuration['environment'],
			'health' => $snapshot['health'],
			'components' => $snapshot['components'],
			'metrics' => $snapshot['metrics'],
		);
		$body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

		$url = parse_url($this->configuration['endpoint']);
		if ( ! is_array($url) || ($url['scheme'] ?? '') !== 'https' || ($url['path'] ?? '') !== '/api/v1/heartbeats')
		{
			throw new RuntimeException('Control endpoint is invalid.');
		}
		$canonical = implode("\n", array(
			'POST',
			'/api/v1/heartbeats',
			$this->configuration['instance_id'],
			$this->configuration['key_id'],
			$timestamp,
			$nonce,
			$idempotency,
			hash('sha256', $body),
		));
		$signature = hash_hmac('sha256', $canonical, $this->configuration['secret']);

		$handle = curl_init($this->configuration['endpoint']);
		curl_setopt_array($handle, array(
			CURLOPT_POST => TRUE,
			CURLOPT_POSTFIELDS => $body,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 15,
			CURLOPT_SSL_VERIFYPEER => TRUE,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
				'User-Agent: Namua-Penatausahaan/'.$this->product['version'],
				'X-Namua-Instance-ID: '.$this->configuration['instance_id'],
				'X-Namua-Key-ID: '.$this->configuration['key_id'],
				'X-Namua-Timestamp: '.$timestamp,
				'X-Namua-Nonce: '.$nonce,
				'X-Namua-Signature: '.$signature,
				'Idempotency-Key: '.$idempotency,
			),
		));
		$response = curl_exec($handle);
		$status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
		$error = curl_error($handle);
		curl_close($handle);

		if ($response === FALSE || $error !== '')
		{
			throw new RuntimeException('Control heartbeat transport failed.');
		}
		$decoded = json_decode((string) $response, TRUE);
		if ( ! in_array($status, array(200, 202), TRUE) || ! is_array($decoded) || ($decoded['status'] ?? '') !== 'accepted')
		{
			$code = is_array($decoded) && isset($decoded['code']) ? $decoded['code'] : 'unexpected_response';
			throw new RuntimeException('Control heartbeat rejected: '.$code.' (HTTP '.$status.').');
		}

		return array(
			'ok' => TRUE,
			'http_status' => $status,
			'health' => $snapshot['health'],
			'received_at' => isset($decoded['received_at']) ? $decoded['received_at'] : NULL,
			'next_heartbeat_seconds' => isset($decoded['next_heartbeat_seconds']) ? (int) $decoded['next_heartbeat_seconds'] : 300,
		);
	}

	private function load_configuration()
	{
		$path = isset($this->product['control_config_path']) ? $this->product['control_config_path'] : '';
		if ($path === '' || ! is_file($path) || ! is_readable($path)) return array();
		$decoded = json_decode((string) file_get_contents($path), TRUE);
		return is_array($decoded) ? $decoded : array();
	}

	private function backup_status()
	{
		$path = isset($this->product['backup_status_path']) ? (string) $this->product['backup_status_path'] : '';
		if ($path === '' || ! is_file($path) || ! is_readable($path)) return 'DEGRADED';
		$status = json_decode((string) file_get_contents($path), TRUE);
		if ( ! is_array($status) || ($status['state'] ?? '') !== 'SUCCESS' || empty($status['last_success_at']) || empty($status['artifact']))
		{
			return 'DEGRADED';
		}
		$successAt = strtotime((string) $status['last_success_at']);
		$artifact = basename((string) $status['artifact']);
		if ($successAt === FALSE || $successAt > time() + 300 || time() - $successAt > 26 * 3600)
		{
			return 'DEGRADED';
		}
		return is_file(dirname($path).DIRECTORY_SEPARATOR.$artifact) ? 'OK' : 'DEGRADED';
	}

	private function assert_configuration()
	{
		foreach (array('endpoint', 'instance_id', 'key_id', 'secret', 'environment') as $key)
		{
			if (empty($this->configuration[$key])) throw new RuntimeException('Control heartbeat configuration is incomplete.');
		}
		if ( ! in_array($this->configuration['environment'], array('DEMO', 'STAGING', 'PRODUCTION'), TRUE))
		{
			throw new RuntimeException('Control heartbeat environment is invalid.');
		}
	}

	private function base64url($bytes)
	{
		return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
	}
}
