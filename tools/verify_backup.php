<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}

require_once __DIR__.'/lib/Encrypted_backup.php';

$runtimePath = getenv('PENATUS_RUNTIME_CONFIG') ?: '/var/lib/penatausahaan-config/runtime.json';
$backupDirectory = getenv('PENATUS_BACKUP_DIR') ?: '/var/lib/penatausahaan-backups';
if (!is_file($runtimePath) || !is_readable($runtimePath)) throw new RuntimeException('Runtime configuration is unavailable.');
$runtime = json_decode((string) file_get_contents($runtimePath), true, 32, JSON_THROW_ON_ERROR);

$path = $argv[1] ?? '';
if ($path === '') {
	$backups = glob($backupDirectory.'/namua-penatausahaan-*.nsb') ?: array();
	rsort($backups, SORT_STRING);
	$path = $backups[0] ?? '';
}
if ($path === '' || !is_file($path)) throw new RuntimeException('No backup is available to verify.');
$real = realpath($path);
$backupRoot = realpath($backupDirectory);
if ($real === false || $backupRoot === false || strpos($real, $backupRoot.DIRECTORY_SEPARATOR) !== 0) {
	throw new RuntimeException('Backup must be inside the configured backup directory.');
}

$result = Encrypted_backup::verify($real, (string) ($runtime['application']['backup_encryption_key'] ?? ''));
fwrite(STDOUT, 'Backup authentication, decompression, and SQL structure verification passed.'.PHP_EOL);
fwrite(STDOUT, 'Artifact: '.basename($real).'; encrypted bytes: '.(int) $result['encrypted_size_bytes'].'; SQL bytes: '.(int) $result['plaintext_size_bytes'].PHP_EOL);
