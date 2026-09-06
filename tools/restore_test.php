<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}

require_once __DIR__.'/lib/Encrypted_backup.php';

$runtimePath = getenv('PENATUS_RUNTIME_CONFIG') ?: '/var/lib/penatausahaan-config/runtime.json';
$backupDirectory = getenv('PENATUS_BACKUP_DIR') ?: '/var/lib/penatausahaan-backups';
$runtime = is_file($runtimePath) ? json_decode((string) file_get_contents($runtimePath), true) : array();
if (!is_array($runtime) || empty($runtime['database'])) throw new RuntimeException('Runtime configuration is unavailable.');

$adminHost = (string) (getenv('PENATUS_DB_RESTORE_HOST') ?: 'localhost');
$adminUser = getenv('PENATUS_DB_RESTORE_USERNAME');
$adminPassword = getenv('PENATUS_DB_RESTORE_PASSWORD');
if ($adminUser === false || $adminPassword === false || $adminUser === '' || $adminPassword === '') {
	throw new RuntimeException('Set PENATUS_DB_RESTORE_USERNAME and PENATUS_DB_RESTORE_PASSWORD for the disposable restore test.');
}

$path = $argv[1] ?? '';
if ($path === '') {
	$backups = glob($backupDirectory.'/namua-penatausahaan-*.nsb') ?: array();
	rsort($backups, SORT_STRING);
	$path = $backups[0] ?? '';
}
$real = $path !== '' ? realpath($path) : false;
$backupRoot = realpath($backupDirectory);
if ($real === false || $backupRoot === false || strpos($real, $backupRoot.DIRECTORY_SEPARATOR) !== 0) {
	throw new RuntimeException('Backup must be inside the configured backup directory.');
}

$databaseName = 'penatus_restore_test_'.gmdate('YmdHis').'_'.bin2hex(random_bytes(3));
if (preg_match('/^penatus_restore_test_[0-9]{14}_[a-f0-9]{6}$/D', $databaseName) !== 1) {
	throw new RuntimeException('Disposable database name validation failed.');
}
$quotedDatabase = '`'.str_replace('`', '``', $databaseName).'`';
$admin = new PDO(
	'mysql:host='.$adminHost.';dbname=mysql;charset=utf8mb4',
	(string) $adminUser,
	(string) $adminPassword,
	array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false)
);
$created = false;
$process = null;
$pipes = array();

try {
	$admin->exec('CREATE DATABASE '.$quotedDatabase.' CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
	$created = true;
	$environment = getenv();
	if (!is_array($environment)) $environment = array();
	$environment['MYSQL_PWD'] = (string) $adminPassword;
	$command = array(
		'/usr/bin/mysql',
		'--host='.$adminHost,
		'--user='.(string) $adminUser,
		'--default-character-set=utf8mb4',
		'--database='.$databaseName,
	);
	$process = proc_open($command, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes, null, $environment, array('bypass_shell' => true));
	if (!is_resource($process)) throw new RuntimeException('Unable to start disposable database restore.');

	$result = Encrypted_backup::verify($real, (string) ($runtime['application']['backup_encryption_key'] ?? ''), $pipes[0]);
	fclose($pipes[0]);
	$stdout = stream_get_contents($pipes[1]);
	$stderr = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	$exitCode = proc_close($process);
	$process = null;
	if ($exitCode !== 0) throw new RuntimeException('Disposable restore failed: '.trim(substr((string) $stderr, 0, 500)));

	$source = $runtime['database'];
	$sourcePdo = new PDO(
		'mysql:host='.(string) $source['hostname'].';dbname='.(string) $source['database'].';charset=utf8mb4',
		(string) $source['username'],
		(string) $source['password'],
		array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
	);
	$restored = new PDO(
		'mysql:host='.$adminHost.';dbname='.$databaseName.';charset=utf8mb4',
		(string) $adminUser,
		(string) $adminPassword,
		array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
	);
	$tables = $sourcePdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_COLUMN);
	$mismatches = array();
	foreach ($tables as $table) {
		if (preg_match('/^[A-Za-z0-9_]+$/D', (string) $table) !== 1) throw new RuntimeException('Unexpected source table name.');
		$sourceCount = (int) $sourcePdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn();
		$restoredCount = (int) $restored->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn();
		if ($sourceCount !== $restoredCount) $mismatches[] = $table;
	}
	$restoredTables = (int) $restored->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = "BASE TABLE"')->fetchColumn();
	if ($restoredTables !== count($tables) || $mismatches !== array()) {
		throw new RuntimeException('Disposable restore row-count verification failed.');
	}

	fwrite(STDOUT, 'Disposable restore passed: authenticated decrypt, SQL import, '.count($tables).' tables, and all row counts verified.'.PHP_EOL);
	fwrite(STDOUT, 'SQL bytes streamed without a plaintext dump file: '.(int) $result['plaintext_size_bytes'].PHP_EOL);
} finally {
	if (isset($pipes[0]) && is_resource($pipes[0])) fclose($pipes[0]);
	if (isset($pipes[1]) && is_resource($pipes[1])) fclose($pipes[1]);
	if (isset($pipes[2]) && is_resource($pipes[2])) fclose($pipes[2]);
	if (is_resource($process)) proc_terminate($process);
	if ($created) {
		$admin->exec('DROP DATABASE '.$quotedDatabase);
		fwrite(STDOUT, 'Disposable restore database removed.'.PHP_EOL);
	}
}
