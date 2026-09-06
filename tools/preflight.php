<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}

require_once __DIR__.'/lib/Migration_runner.php';

$root = dirname(__DIR__);
$runtimePath = getenv('PENATUS_RUNTIME_CONFIG') ?: '/var/lib/penatausahaan-config/runtime.json';
$controlPath = getenv('PENATUS_CONTROL_CONFIG') ?: '/var/lib/penatausahaan-config/control-center-heartbeat.json';
$tenantPath = getenv('PENATUS_TENANT_CONFIG') ?: '/var/lib/penatausahaan-config/tenant.json';
$migrationConfigPath = getenv('PENATUS_MIGRATION_CONFIG') ?: '/var/lib/penatausahaan-config/migration.json';
$backupStatusPath = getenv('PENATUS_BACKUP_STATUS') ?: '/var/lib/penatausahaan-backups/status.json';
$failures = 0;
$warnings = 0;
$checks = 0;

$check = static function (bool $condition, string $message, bool $warning = false) use (&$failures, &$warnings, &$checks): void {
	$checks++;
	if ($condition) {
		fwrite(STDOUT, '[PASS] '.$message.PHP_EOL);
		return;
	}
	if ($warning) {
		$warnings++;
		fwrite(STDOUT, '[WARN] '.$message.PHP_EOL);
		return;
	}
	$failures++;
	fwrite(STDERR, '[FAIL] '.$message.PHP_EOL);
};
$json = static function (string $path): array {
	if (!is_file($path) || !is_readable($path)) return array();
	$decoded = json_decode((string) file_get_contents($path), true);
	return is_array($decoded) ? $decoded : array();
};
$outsideRoot = static function (string $path) use ($root): bool {
	$real = realpath($path);
	return $real !== false && strpos($real, $root.DIRECTORY_SEPARATOR) !== 0;
};

$check(version_compare(PHP_VERSION, '8.1.0', '>=') && version_compare(PHP_VERSION, '8.5.0', '<'), 'PHP version is within the supported range (8.1–8.4)');
foreach (array('curl', 'intl', 'json', 'mbstring', 'mysqli', 'pdo_mysql', 'sodium', 'zlib') as $extension) {
	$check(extension_loaded($extension), 'Required PHP extension is loaded: '.$extension);
}
$check(is_executable('/usr/bin/mysqldump'), 'Database backup executable is available');

$runtime = $json($runtimePath);
$control = $json($controlPath);
$tenant = $json($tenantPath);
$migrationConfig = $json($migrationConfigPath);
$backup = $json($backupStatusPath);
$check($runtime !== array() && $outsideRoot($runtimePath), 'Runtime configuration exists outside webroot');
$check($control !== array() && $outsideRoot($controlPath), 'Control configuration exists outside webroot');
$check($tenant !== array() && $outsideRoot($tenantPath), 'Tenant configuration exists outside webroot');
$check($migrationConfig !== array() && $outsideRoot($migrationConfigPath), 'Migration configuration exists outside webroot');
$check(is_file($runtimePath) && (fileperms($runtimePath) & 0777) === 0640, 'Runtime configuration mode is 0640');
$check(is_file($controlPath) && (fileperms($controlPath) & 0777) === 0640, 'Control configuration mode is 0640');
$check(is_file($tenantPath) && (fileperms($tenantPath) & 0777) === 0640, 'Tenant configuration mode is 0640');
$check(is_file($migrationConfigPath) && (fileperms($migrationConfigPath) & 0777) === 0600, 'Migration configuration mode is root-only 0600');
$check(is_file($migrationConfigPath) && (!function_exists('posix_geteuid') || fileowner($migrationConfigPath) === 0), 'Migration configuration is owned by root');
$check(filter_var($runtime['application']['url'] ?? '', FILTER_VALIDATE_URL) !== false && strncmp((string) ($runtime['application']['url'] ?? ''), 'https://', 8) === 0, 'Application URL is valid HTTPS');
$check(strlen((string) ($runtime['application']['encryption_key'] ?? '')) >= 32, 'Application encryption key is configured');
$check(strlen((string) ($runtime['application']['backup_encryption_key'] ?? '')) >= 32, 'Backup encryption key is configured');
$check(trim((string) ($tenant['pemda'] ?? '')) !== '', 'Tenant identity is configured');

$databaseOk = false;
$databaseVersion = '';
$grantText = '';
$schemaStatus = array();
if ($runtime !== array()) {
	$connection = @new mysqli(
		(string) ($runtime['database']['hostname'] ?? ''),
		(string) ($runtime['database']['username'] ?? ''),
		(string) ($runtime['database']['password'] ?? ''),
		(string) ($runtime['database']['database'] ?? '')
	);
	$databaseOk = !$connection->connect_errno;
	if ($databaseOk) {
		$databaseVersion = (string) ($connection->query('SELECT VERSION()')->fetch_row()[0] ?? '');
		$result = $connection->query('SHOW GRANTS FOR CURRENT_USER()');
		$grants = array();
		while ($result && $row = $result->fetch_row()) $grants[] = $row[0];
		$grantText = implode("\n", $grants);
		$pdo = new PDO(
			'mysql:host='.(string) ($runtime['database']['hostname'] ?? '').';dbname='.(string) ($runtime['database']['database'] ?? '').';charset=utf8mb4',
			(string) ($runtime['database']['username'] ?? ''),
			(string) ($runtime['database']['password'] ?? ''),
			array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false)
		);
		$runner = new Migration_runner($pdo, $root.'/database/migrations', $root.'/database/schema/20260906_baseline.sql');
		$schemaStatus = $runner->status();
		$connection->close();
	}
}
$check($databaseOk, 'Application database connection succeeds');
$check($databaseOk && ($runtime['database']['username'] ?? '') !== 'root', 'Application database account is not root');
$check($databaseOk && stripos($grantText, 'GRANT ALL') === false && preg_match('/\b(CREATE|ALTER|DROP|GRANT OPTION|SUPER|FILE)\b/i', $grantText) !== 1, 'Database account has no DDL/admin grants');
$check($databaseVersion !== '', 'Database server version is readable');
$manifest = $json($root.'/app-manifest.json');
$check($schemaStatus !== array() && ($schemaStatus['pending'] ?? array()) === array(), 'All packaged database migrations are applied');
$check($schemaStatus !== array() && hash_equals((string) ($manifest['schema_version'] ?? ''), (string) ($schemaStatus['current_schema'] ?? '')), 'Database schema matches the package target');
$check(($migrationConfig['username'] ?? '') !== ($runtime['database']['username'] ?? ''), 'Runtime and migration database accounts are separated');
$migrationConnection = @new mysqli(
	(string) ($migrationConfig['hostname'] ?? ''),
	(string) ($migrationConfig['username'] ?? ''),
	(string) ($migrationConfig['password'] ?? ''),
	(string) ($migrationConfig['database'] ?? '')
);
$migrationDatabaseOk = !$migrationConnection->connect_errno;
$migrationGrantText = '';
if ($migrationDatabaseOk) {
	$result = $migrationConnection->query('SHOW GRANTS FOR CURRENT_USER()');
	$grants = array();
	while ($result && $row = $result->fetch_row()) $grants[] = $row[0];
	$migrationGrantText = implode("\n", $grants);
	$migrationConnection->close();
}
$check($migrationDatabaseOk, 'Dedicated migration database connection succeeds');
$check(
	$migrationGrantText !== ''
	&& preg_match('/\bCREATE\b/i', $migrationGrantText) === 1
	&& preg_match('/\bALTER\b/i', $migrationGrantText) === 1
	&& preg_match('/\bDROP\b/i', $migrationGrantText) === 1
	&& stripos($migrationGrantText, 'ALL PRIVILEGES') === false
	&& stripos($migrationGrantText, 'GRANT OPTION') === false,
	'Migration account has scoped DDL without global/admin privileges'
);

$www = function_exists('posix_getpwnam') ? posix_getpwnam('www') : false;
foreach (array('application/cache/sessions', 'application/cache/login_attempts', 'application/logs') as $relative) {
	$path = $root.'/'.$relative;
	$ownerOk = $www === false || (is_dir($path) && fileowner($path) === (int) $www['uid']);
	$check(is_dir($path) && is_writable($path) && $ownerOk && (fileperms($path) & 0777) === 0700, 'Private writable directory is ready: '.$relative);
}

$backupAt = strtotime((string) ($backup['last_success_at'] ?? ''));
$artifact = basename((string) ($backup['artifact'] ?? ''));
$artifactPath = dirname($backupStatusPath).DIRECTORY_SEPARATOR.$artifact;
$check(($backup['state'] ?? '') === 'SUCCESS' && $backupAt !== false && time() - $backupAt <= 26 * 3600, 'Encrypted backup is successful and fresh');
$check($artifact !== '' && is_file($artifactPath) && (fileperms($artifactPath) & 0777) === 0600, 'Encrypted backup artifact is private');
$databaseFingerprint = hash('sha256', strtolower((string) ($runtime['database']['hostname'] ?? ''))."\0".(string) ($runtime['database']['database'] ?? ''));
$check(isset($backup['database_fingerprint']) && hash_equals($databaseFingerprint, (string) $backup['database_fingerprint']), 'Backup is bound to the selected database');
$check(isset($backup['schema_version'], $schemaStatus['current_schema']) && hash_equals((string) $backup['schema_version'], (string) $schemaStatus['current_schema']), 'Backup schema matches the active database schema');

$baseUrl = rtrim((string) ($runtime['application']['url'] ?? ''), '/');
$healthOk = false;
if ($baseUrl !== '') {
	$handle = curl_init($baseUrl.'/health');
	curl_setopt_array($handle, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2));
	$body = (string) curl_exec($handle);
	$status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
	$error = curl_error($handle);
	curl_close($handle);
	$health = json_decode($body, true);
	$healthOk = $error === '' && $status === 200 && ($health['product'] ?? '') === 'NAMUA_PENATAUSAHAAN';
}
$check($healthOk, 'Public HTTPS health endpoint is reachable');

$schema = (string) @file_get_contents($root.'/database/schema/20260906_baseline.sql');
$check(substr_count($schema, 'CREATE TABLE') === 41, 'Baseline schema contains the expected 41 tables');
$check(is_file('/etc/cron.d/namua-penatausahaan-heartbeat'), 'Heartbeat schedule is installed', true);
$check(is_file('/etc/cron.d/namua-penatausahaan-backup'), 'Backup schedule is installed', true);
$check(is_file('/etc/cron.d/namua-penatausahaan-log-retention'), 'Structured log retention schedule is installed', true);

fwrite(STDOUT, "Checks: {$checks}; warnings: {$warnings}; failures: {$failures}.\n");
exit($failures === 0 ? 0 : 1);
