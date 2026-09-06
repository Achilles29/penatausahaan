<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}

$root = dirname(__DIR__);
$runtimeDirectory = '/var/lib/penatausahaan-config';
$runtimePath = $runtimeDirectory.'/runtime.json';

$existing = array();
if (is_file($runtimePath)) {
	$decoded = json_decode((string) file_get_contents($runtimePath), true);
	$existing = is_array($decoded) ? $decoded : array();
}

$databaseName = (string) (getenv('PENATUS_DB_DATABASE') ?: ($existing['database']['database'] ?? 'penatus'));
if (preg_match('/^[A-Za-z0-9_]+$/D', $databaseName) !== 1) {
	throw new RuntimeException('Database name is invalid.');
}

$base64url = static function (int $bytes): string {
	return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
};

$applicationUser = 'namua_penatus_app';
$applicationPassword = (string) ($existing['database']['password'] ?? $base64url(36));
$applicationKey = (string) ($existing['application']['encryption_key'] ?? $base64url(48));
$backupKey = (string) ($existing['application']['backup_encryption_key'] ?? $base64url(32));
$adminHost = (string) (getenv('PENATUS_DB_ADMIN_HOST') ?: 'localhost');
$adminUser = getenv('PENATUS_DB_ADMIN_USERNAME');
$adminPassword = getenv('PENATUS_DB_ADMIN_PASSWORD');
$skipDatabaseProvisioning = FALSE;

foreach (array($root.'/application/cache/sessions', $root.'/application/cache/login_attempts', $root.'/application/logs') as $writablePath) {
	if (!is_dir($writablePath) && !mkdir($writablePath, 0700, true) && !is_dir($writablePath)) {
		throw new RuntimeException('Unable to create writable runtime directory: '.$writablePath);
	}
	chmod($writablePath, 0700);
	chown($writablePath, 'www');
	chgrp($writablePath, 'www');
}

if (($adminUser === FALSE || $adminPassword === FALSE) && isset($existing['database']['password'])) {
	$verify = new PDO(
		'mysql:host='.(string) $existing['database']['hostname'].';dbname='.$databaseName.';charset=utf8mb4',
		(string) $existing['database']['username'],
		(string) $existing['database']['password'],
		array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
	);
	$verify->query('SELECT 1')->fetchColumn();
	$skipDatabaseProvisioning = TRUE;
}
if (!$skipDatabaseProvisioning && ($adminUser === FALSE || $adminPassword === FALSE || $adminUser === '' || $adminPassword === '')) {
	throw new RuntimeException('Set PENATUS_DB_ADMIN_USERNAME and PENATUS_DB_ADMIN_PASSWORD for first-time provisioning.');
}

if (!$skipDatabaseProvisioning) {
	$admin = new PDO(
		'mysql:host='.$adminHost.';dbname=mysql;charset=utf8mb4',
		(string) $adminUser,
		(string) $adminPassword,
		array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false)
	);

	$quotedPassword = $admin->quote($applicationPassword);
	$admin->exec("CREATE USER IF NOT EXISTS '{$applicationUser}'@'localhost' IDENTIFIED BY {$quotedPassword}");
	$admin->exec("ALTER USER '{$applicationUser}'@'localhost' IDENTIFIED BY {$quotedPassword}");
	$admin->exec("REVOKE ALL PRIVILEGES, GRANT OPTION FROM '{$applicationUser}'@'localhost'");
	$admin->exec("GRANT SELECT, INSERT, UPDATE, DELETE ON `{$databaseName}`.* TO '{$applicationUser}'@'localhost'");
	$admin->exec('FLUSH PRIVILEGES');
}

$runtime = array(
	'format_version' => 1,
	'application' => array(
		'url' => (string) (getenv('APP_URL') ?: ($existing['application']['url'] ?? 'https://efin.namuaprojects.com')),
		'environment' => (string) (getenv('PENATUS_APP_ENVIRONMENT') ?: ($existing['application']['environment'] ?? 'STAGING')),
		'encryption_key' => $applicationKey,
		'backup_encryption_key' => $backupKey,
	),
	'database' => array(
		'hostname' => (string) ($existing['database']['hostname'] ?? 'localhost'),
		'username' => (string) ($existing['database']['username'] ?? $applicationUser),
		'password' => $applicationPassword,
		'database' => $databaseName,
	),
);

if ( ! is_dir($runtimeDirectory) && ! mkdir($runtimeDirectory, 0750, true) && ! is_dir($runtimeDirectory)) {
	throw new RuntimeException('Unable to create runtime configuration directory.');
}
if (is_link($runtimeDirectory)) {
	throw new RuntimeException('Runtime configuration directory must not be a symlink.');
}

$temporary = $runtimePath.'.tmp.'.bin2hex(random_bytes(6));
$encoded = json_encode($runtime, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
if (file_put_contents($temporary, $encoded, LOCK_EX) === false || ! rename($temporary, $runtimePath)) {
	@unlink($temporary);
	throw new RuntimeException('Unable to install runtime configuration.');
}

chmod($runtimeDirectory, 0750);
chown($runtimeDirectory, 'root');
chgrp($runtimeDirectory, 'www');
chmod($runtimePath, 0640);
chown($runtimePath, 'root');
chgrp($runtimePath, 'www');

$verify = new PDO(
	'mysql:host='.$runtime['database']['hostname'].';dbname='.$databaseName.';charset=utf8mb4',
	$runtime['database']['username'],
	$applicationPassword,
	array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);
$verify->query('SELECT 1')->fetchColumn();

fwrite(STDOUT, "Runtime configuration installed outside webroot.\n");
fwrite(STDOUT, "Least-privilege database connection verified.\n");
