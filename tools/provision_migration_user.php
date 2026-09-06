<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}
if (function_exists('posix_geteuid') && posix_geteuid() !== 0) {
	throw new RuntimeException('Run this command as root so the migration secret remains root-only.');
}

$root = dirname(__DIR__);
$runtimePath = getenv('PENATUS_RUNTIME_CONFIG') ?: '/var/lib/penatausahaan-config/runtime.json';
$configPath = getenv('PENATUS_MIGRATION_CONFIG') ?: '/var/lib/penatausahaan-config/migration.json';
if (!is_file($runtimePath) || !is_readable($runtimePath) || is_link($runtimePath)) throw new RuntimeException('Runtime configuration is unavailable.');
$runtime = json_decode((string) file_get_contents($runtimePath), true, 32, JSON_THROW_ON_ERROR);
$target = (array) ($runtime['database'] ?? array());

$adminHost = (string) (getenv('PENATUS_DB_ADMIN_HOST') ?: ($target['hostname'] ?? 'localhost'));
$adminUser = getenv('PENATUS_DB_ADMIN_USERNAME');
$adminPassword = getenv('PENATUS_DB_ADMIN_PASSWORD');
if (!is_string($adminUser) || $adminUser === '' || !is_string($adminPassword) || $adminPassword === '') {
	throw new RuntimeException('Set PENATUS_DB_ADMIN_USERNAME and PENATUS_DB_ADMIN_PASSWORD.');
}
$databaseName = (string) ($target['database'] ?? '');
$migrationUser = (string) (getenv('PENATUS_DB_MIGRATION_USERNAME') ?: 'namua_penatus_migrator');
if (preg_match('/^[A-Za-z0-9_]{1,32}$/D', $databaseName) !== 1 || preg_match('/^[A-Za-z0-9_]{1,32}$/D', $migrationUser) !== 1) {
	throw new RuntimeException('Database or migration username is invalid.');
}

$admin = new PDO(
	'mysql:host='.$adminHost.';dbname=mysql;charset=utf8mb4',
	$adminUser,
	$adminPassword,
	array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false)
);
$password = rtrim(strtr(base64_encode(random_bytes(36)), '+/', '-_'), '=');
$account = "'".$migrationUser."'@'localhost'";
$admin->exec('CREATE USER IF NOT EXISTS '.$account.' IDENTIFIED BY '.$admin->quote($password));
$admin->exec('ALTER USER '.$account.' IDENTIFIED BY '.$admin->quote($password));
$admin->exec(
	'GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES ON `'.$databaseName.'`.* TO '.$account
);

$configuration = array(
	'hostname' => (string) ($target['hostname'] ?? 'localhost'),
	'username' => $migrationUser,
	'password' => $password,
	'database' => $databaseName,
);
$directory = dirname($configPath);
if (strpos((string) realpath($directory), $root.DIRECTORY_SEPARATOR) === 0 || is_link($directory)) {
	throw new RuntimeException('Migration configuration directory must be private and outside webroot.');
}
if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
	throw new RuntimeException('Unable to create migration configuration directory.');
}
$temporary = $configPath.'.tmp.'.bin2hex(random_bytes(6));
$json = json_encode($configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
if (file_put_contents($temporary, $json, LOCK_EX) === false) throw new RuntimeException('Unable to write migration configuration.');
chmod($temporary, 0600);
chown($temporary, 'root');
chgrp($temporary, 'root');
if (!rename($temporary, $configPath)) {
	@unlink($temporary);
	throw new RuntimeException('Unable to install migration configuration.');
}

fwrite(STDOUT, 'Dedicated migration user provisioned for one database; secret stored root-only outside webroot.'.PHP_EOL);
