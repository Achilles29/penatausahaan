<?php

declare(strict_types=1);

require_once __DIR__.'/Encrypted_backup.php';

final class Deployment_guard
{
	public static function assertFreshVerifiedBackup(
		array $runtime,
		string $statusPath,
		string $currentSchema,
		int $maximumAgeSeconds = 1800
	): array {
		if ($maximumAgeSeconds < 300 || $maximumAgeSeconds > 7200) {
			throw new InvalidArgumentException('Backup guard age must be between 5 and 120 minutes.');
		}
		if (!is_file($statusPath) || !is_readable($statusPath) || is_link($statusPath)) {
			throw new RuntimeException('Backup status is unavailable or unsafe.');
		}
		$status = json_decode((string) file_get_contents($statusPath), true, 32, JSON_THROW_ON_ERROR);
		$successAt = strtotime((string) ($status['last_success_at'] ?? ''));
		if (($status['state'] ?? '') !== 'SUCCESS' || $successAt === false || $successAt > time() + 300 || time() - $successAt > $maximumAgeSeconds) {
			throw new RuntimeException('A successful encrypted backup no older than '.(int) ceil($maximumAgeSeconds / 60).' minutes is required.');
		}

		$database = (array) ($runtime['database'] ?? array());
		$fingerprint = hash('sha256', strtolower((string) ($database['hostname'] ?? ''))."\0".(string) ($database['database'] ?? ''));
		if (!isset($status['database_fingerprint']) || !hash_equals($fingerprint, (string) $status['database_fingerprint'])) {
			throw new RuntimeException('Backup does not belong to the selected database.');
		}
		if (!isset($status['schema_version']) || !hash_equals($currentSchema, (string) $status['schema_version'])) {
			throw new RuntimeException('Backup schema version does not match the database being changed.');
		}

		$artifactName = basename((string) ($status['artifact'] ?? ''));
		$artifactPath = dirname($statusPath).DIRECTORY_SEPARATOR.$artifactName;
		if ($artifactName === '' || !is_file($artifactPath) || is_link($artifactPath) || (fileperms($artifactPath) & 0777) !== 0600) {
			throw new RuntimeException('Backup artifact is missing or has unsafe permissions.');
		}
		$encryptedHash = hash_file('sha256', $artifactPath);
		if (!is_string($encryptedHash) || !hash_equals((string) ($status['encrypted_sha256'] ?? ''), $encryptedHash)) {
			throw new RuntimeException('Backup artifact checksum does not match its status record.');
		}

		$verification = Encrypted_backup::verify(
			$artifactPath,
			(string) ($runtime['application']['backup_encryption_key'] ?? '')
		);
		if (!hash_equals((string) ($status['plaintext_sha256'] ?? ''), (string) $verification['plaintext_sha256'])) {
			throw new RuntimeException('Backup plaintext checksum verification failed.');
		}

		return array(
			'artifact' => $artifactName,
			'last_success_at' => gmdate(DATE_ATOM, $successAt),
			'schema_version' => $currentSchema,
		);
	}
}
