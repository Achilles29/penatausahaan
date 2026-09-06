<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}

require_once __DIR__.'/lib/Encrypted_backup.php';
require_once __DIR__.'/lib/Migration_runner.php';

$root = dirname(__DIR__);
$runtimePath = getenv('PENATUS_RUNTIME_CONFIG') ?: '/var/lib/penatausahaan-config/runtime.json';
$backupDirectory = getenv('PENATUS_BACKUP_DIR') ?: '/var/lib/penatausahaan-backups';
$statusPath = $backupDirectory.'/status.json';
$retention = max(1, min(90, (int) (getenv('PENATUS_BACKUP_RETENTION') ?: 14)));

if (!is_file($runtimePath) || !is_readable($runtimePath)) {
	throw new RuntimeException('Runtime configuration is unavailable.');
}
$runtime = json_decode((string) file_get_contents($runtimePath), true, 32, JSON_THROW_ON_ERROR);
$manifest = json_decode((string) file_get_contents($root.'/app-manifest.json'), true, 32, JSON_THROW_ON_ERROR);
$runtimeDatabase = (array) ($runtime['database'] ?? array());
$database = new PDO(
	'mysql:host='.(string) ($runtimeDatabase['hostname'] ?? '').';dbname='.(string) ($runtimeDatabase['database'] ?? '').';charset=utf8mb4',
	(string) ($runtimeDatabase['username'] ?? ''),
	(string) ($runtimeDatabase['password'] ?? ''),
	array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false)
);
$migrationRunner = new Migration_runner($database, $root.'/database/migrations', $root.'/database/schema/20260906_baseline.sql');
$currentSchema = $migrationRunner->currentSchema();
$sourceTableCount = (int) $database->query(
	"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'"
)->fetchColumn();
$databaseFingerprint = hash(
	'sha256',
	strtolower((string) ($runtimeDatabase['hostname'] ?? ''))."\0".(string) ($runtimeDatabase['database'] ?? '')
);

if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0750, true) && !is_dir($backupDirectory)) {
	throw new RuntimeException('Unable to create backup directory.');
}
if (is_link($backupDirectory)) throw new RuntimeException('Backup directory must not be a symlink.');
chmod($backupDirectory, 0750);
@chown($backupDirectory, 'root');
@chgrp($backupDirectory, 'www');

$stamp = gmdate('Ymd\THis\Z');
$filename = 'namua-penatausahaan-'.$stamp.'.nsb';
$temporary = $backupDirectory.'/.'.$filename.'.tmp';
$destination = $backupDirectory.'/'.$filename;

try {
	$result = Encrypted_backup::create($runtime, $temporary, array(
		'product_code' => (string) ($manifest['product_code'] ?? 'NAMUA_PENATAUSAHAAN'),
		'app_version' => (string) ($manifest['version'] ?? 'unknown'),
		'schema_version' => $currentSchema,
		'database_fingerprint' => $databaseFingerprint,
		'source_table_count' => $sourceTableCount,
		'restore_scope' => 'selected_database_only',
	));
	if (!rename($temporary, $destination)) throw new RuntimeException('Unable to finalize backup file.');
	chmod($destination, 0600);

	$verified = Encrypted_backup::verify($destination, (string) $runtime['application']['backup_encryption_key']);
	if (!hash_equals((string) $result['plaintext_sha256'], (string) $verified['plaintext_sha256'])) {
		throw new RuntimeException('Backup verification checksum mismatch.');
	}

	$status = array(
		'format_version' => 1,
		'state' => 'SUCCESS',
		'last_attempt_at' => gmdate(DATE_ATOM),
		'last_success_at' => gmdate(DATE_ATOM),
		'artifact' => $filename,
		'encrypted_size_bytes' => (int) $result['encrypted_size_bytes'],
		'encrypted_sha256' => (string) $result['encrypted_sha256'],
		'plaintext_sha256' => (string) $result['plaintext_sha256'],
		'app_version' => (string) ($manifest['version'] ?? 'unknown'),
		'schema_version' => $currentSchema,
		'database_fingerprint' => $databaseFingerprint,
		'source_table_count' => $sourceTableCount,
	);
	writeStatus($statusPath, $status);

	$backups = glob($backupDirectory.'/namua-penatausahaan-*.nsb') ?: array();
	rsort($backups, SORT_STRING);
	foreach (array_slice($backups, $retention) as $expired) {
		if (is_file($expired) && dirname($expired) === $backupDirectory) unlink($expired);
	}

	fwrite(STDOUT, 'Encrypted backup created and cryptographically verified: '.$filename.PHP_EOL);
	fwrite(STDOUT, 'Encrypted bytes: '.(int) $result['encrypted_size_bytes'].'; retention: '.$retention.PHP_EOL);
} catch (Throwable $exception) {
	@unlink($temporary);
	$previous = is_file($statusPath) ? json_decode((string) file_get_contents($statusPath), true) : array();
	if (!is_array($previous)) $previous = array();
	$previous['format_version'] = 1;
	$previous['state'] = 'FAILED';
	$previous['last_attempt_at'] = gmdate(DATE_ATOM);
	$previous['last_error'] = 'backup_failed';
	writeStatus($statusPath, $previous);
	fwrite(STDERR, 'Backup failed: '.$exception->getMessage().PHP_EOL);
	exit(1);
}

function writeStatus(string $path, array $status): void
{
	$temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
	$data = json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
	if (file_put_contents($temporary, $data, LOCK_EX) === false || !rename($temporary, $path)) {
		@unlink($temporary);
		throw new RuntimeException('Unable to write backup status.');
	}
	chmod($path, 0640);
	@chown($path, 'root');
	@chgrp($path, 'www');
}
