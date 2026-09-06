<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}

$adminHost = (string) (getenv('PENATUS_DB_RESTORE_HOST') ?: 'localhost');
$adminUser = getenv('PENATUS_DB_RESTORE_USERNAME');
$adminPassword = getenv('PENATUS_DB_RESTORE_PASSWORD');
if ($adminUser === false || $adminPassword === false || $adminUser === '' || $adminPassword === '') {
	throw new RuntimeException('Set PENATUS_DB_RESTORE_USERNAME and PENATUS_DB_RESTORE_PASSWORD for the disposable schema test.');
}
$schemaPath = dirname(__DIR__).'/database/schema/20260906_baseline.sql';
if (!is_file($schemaPath) || !is_readable($schemaPath)) throw new RuntimeException('Baseline schema is unavailable.');

$databaseName = 'penatus_schema_test_'.gmdate('YmdHis').'_'.bin2hex(random_bytes(3));
if (preg_match('/^penatus_schema_test_[0-9]{14}_[a-f0-9]{6}$/D', $databaseName) !== 1) throw new RuntimeException('Disposable database name validation failed.');
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
	$process = proc_open(array(
		'/usr/bin/mysql', '--host='.$adminHost, '--user='.(string) $adminUser,
		'--default-character-set=utf8mb4', '--database='.$databaseName,
	), array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes, null, $environment, array('bypass_shell' => true));
	if (!is_resource($process)) throw new RuntimeException('Unable to start disposable schema import.');
	$schema = fopen($schemaPath, 'rb');
	if ($schema === false) throw new RuntimeException('Unable to read baseline schema.');
	stream_copy_to_stream($schema, $pipes[0]);
	fclose($schema);
	fclose($pipes[0]);
	$stdout = stream_get_contents($pipes[1]);
	$stderr = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	$exitCode = proc_close($process);
	$process = null;
	if ($exitCode !== 0) throw new RuntimeException('Disposable schema import failed: '.trim(substr((string) $stderr, 0, 500)));

	$test = new PDO(
		'mysql:host='.$adminHost.';dbname='.$databaseName.';charset=utf8mb4',
		(string) $adminUser,
		(string) $adminPassword,
		array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
	);
	$tables = (int) $test->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = "BASE TABLE"')->fetchColumn();
	$users = (int) $test->query('SELECT COUNT(*) FROM users')->fetchColumn();
	if ($tables !== 41 || $users !== 0) throw new RuntimeException('Baseline schema verification failed.');
	fwrite(STDOUT, 'Clean schema install passed: 41 tables and no bundled user/customer records.'.PHP_EOL);
} finally {
	if (isset($pipes[0]) && is_resource($pipes[0])) fclose($pipes[0]);
	if (isset($pipes[1]) && is_resource($pipes[1])) fclose($pipes[1]);
	if (isset($pipes[2]) && is_resource($pipes[2])) fclose($pipes[2]);
	if (is_resource($process)) proc_terminate($process);
	if ($created) {
		$admin->exec('DROP DATABASE '.$quotedDatabase);
		fwrite(STDOUT, 'Disposable schema database removed.'.PHP_EOL);
	}
}
