<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}

$root = dirname(__DIR__);
$baseUrl = rtrim(getenv('APP_URL') ?: 'https://efin.namuaprojects.com', '/');
$failures = 0;
$checks = 0;

$check = static function (bool $condition, string $message) use (&$failures, &$checks): void {
	$checks++;
	fwrite($condition ? STDOUT : STDERR, ($condition ? '[PASS] ' : '[FAIL] ').$message.PHP_EOL);
	if (!$condition) $failures++;
};

$runtimePath = getenv('PENATUS_RUNTIME_CONFIG') ?: '/var/lib/penatausahaan-config/runtime.json';
$controlPath = getenv('PENATUS_CONTROL_CONFIG') ?: '/var/lib/penatausahaan-config/control-center-heartbeat.json';
$tenantPath = getenv('PENATUS_TENANT_CONFIG') ?: '/var/lib/penatausahaan-config/tenant.json';
$migrationConfigPath = getenv('PENATUS_MIGRATION_CONFIG') ?: '/var/lib/penatausahaan-config/migration.json';
$backupStatusPath = getenv('PENATUS_BACKUP_STATUS') ?: '/var/lib/penatausahaan-backups/status.json';
$runtime = is_file($runtimePath) ? json_decode((string) file_get_contents($runtimePath), true) : null;
$control = is_file($controlPath) ? json_decode((string) file_get_contents($controlPath), true) : null;
$tenant = is_file($tenantPath) ? json_decode((string) file_get_contents($tenantPath), true) : null;
$migrationConfig = is_file($migrationConfigPath) && is_readable($migrationConfigPath) ? json_decode((string) file_get_contents($migrationConfigPath), true) : null;
$backup = is_file($backupStatusPath) ? json_decode((string) file_get_contents($backupStatusPath), true) : null;

$check(is_array($runtime), 'Runtime configuration exists outside webroot');
$check(is_array($control), 'Control heartbeat configuration exists outside webroot');
$check(is_array($tenant), 'Tenant configuration exists outside webroot');
$check(is_array($migrationConfig), 'Migration configuration exists outside webroot');
$check(strpos((string) realpath($runtimePath), $root.DIRECTORY_SEPARATOR) !== 0, 'Runtime configuration is not inside repository');
$check(strpos((string) realpath($controlPath), $root.DIRECTORY_SEPARATOR) !== 0, 'Control secret is not inside repository');
$check(strpos((string) realpath($tenantPath), $root.DIRECTORY_SEPARATOR) !== 0, 'Tenant configuration is not inside repository');
$check(strpos((string) realpath($migrationConfigPath), $root.DIRECTORY_SEPARATOR) !== 0, 'Migration secret is not inside repository');
$check((fileperms($runtimePath) & 0777) === 0640, 'Runtime configuration mode is 0640');
$check((fileperms($controlPath) & 0777) === 0640, 'Control secret mode is 0640');
$check((fileperms($migrationConfigPath) & 0777) === 0600, 'Migration secret mode is root-only 0600');
$check(!function_exists('posix_geteuid') || fileowner($migrationConfigPath) === 0, 'Migration secret is owned by root');
$check(($runtime['database']['username'] ?? '') !== 'root', 'Application database user is not root');
$check(($migrationConfig['username'] ?? '') !== 'root' && ($migrationConfig['username'] ?? '') !== ($runtime['database']['username'] ?? ''), 'Migration account is dedicated and separate from runtime');
$check(strlen((string) ($runtime['application']['encryption_key'] ?? '')) >= 32, 'Application encryption key is external and sufficiently long');
$check(strlen((string) ($runtime['application']['backup_encryption_key'] ?? '')) >= 32, 'Backup encryption key is external and sufficiently long');
$backupArtifact = is_array($backup) ? basename((string) ($backup['artifact'] ?? '')) : '';
$check(is_array($backup) && ($backup['state'] ?? '') === 'SUCCESS' && is_file(dirname($backupStatusPath).'/'.$backupArtifact), 'Encrypted backup status and artifact are available');
$check(!file_exists($root.'/penatus.sql') && !file_exists($root.'/db_backup'), 'Legacy database dumps are absent from webroot');
$check(is_file($root.'/tools/create_admin.php') && strpos((string) file_get_contents($root.'/tools/create_admin.php'), 'active superadmin already exists') !== false, 'Initial admin provisioning has a duplicate-admin guard');
$check(is_file($root.'/tools/migrate.php') && strpos((string) file_get_contents($root.'/tools/migrate.php'), 'runMandatoryBackup') !== false, 'Database migration enforces a pre-change backup');

$databaseConfig = (string) file_get_contents($root.'/application/config/database.php');
$appConfig = (string) file_get_contents($root.'/application/config/config.php');
$setupController = (string) file_get_contents($root.'/application/controllers/Setup.php');
$check(strpos($databaseConfig, "'username' => 'root'") === false, 'Source contains no root database configuration');
$check(preg_match("/'password'\\s*=>\\s*'[^']+'/", $databaseConfig) !== 1, 'Source contains no literal database password');
$check(strpos($appConfig, "\$config['csrf_protection'] = TRUE;") !== false, 'CSRF protection is enabled');
$check(strpos($appConfig, "\$config['cookie_secure']\t= TRUE;") !== false, 'Secure cookies are enabled');
$check(strpos($appConfig, "\$config['log_file_extension'] = 'jsonl';") !== false && strpos($appConfig, "\$config['log_file_permissions'] = 0600;") !== false, 'Structured application logs use private JSONL files');
$check(is_file($root.'/application/core/MY_Log.php') && strpos((string) file_get_contents($root.'/application/core/MY_Log.php'), "'request_id'") !== false, 'Structured logs include the correlation request ID');
$check(strpos($setupController, "is_cli_request()") !== false, 'Destructive Setup controller is CLI-only');
$check((string) ($runtime['database']['password'] ?? '') !== '' && strpos($databaseConfig, (string) $runtime['database']['password']) === false, 'Runtime database password is absent from source');

$secretEmbedded = false;
$needles = array_filter(array(
	(string) ($runtime['database']['password'] ?? ''),
	(string) ($runtime['application']['encryption_key'] ?? ''),
	(string) ($runtime['application']['backup_encryption_key'] ?? ''),
	(string) ($control['secret'] ?? ''),
	(string) ($migrationConfig['password'] ?? ''),
), static function ($value) { return strlen($value) >= 16; });
$scanRoots = array($root.'/application', $root.'/assets', $root.'/database', $root.'/tools', $root.'/deploy');
$scanFiles = array($root.'/index.php', $root.'/.htaccess', $root.'/README.md', $root.'/INSTALL.md', $root.'/app-manifest.json', $root.'/composer.json');
foreach ($scanRoots as $scanRoot) {
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($scanRoot, FilesystemIterator::SKIP_DOTS));
	foreach ($iterator as $file) {
		if (!$file->isFile() || $file->getSize() > 5 * 1024 * 1024) continue;
		$scanFiles[] = $file->getPathname();
	}
}
foreach (array_unique($scanFiles) as $file) {
	$content = is_file($file) ? file_get_contents($file) : false;
	if ($content === false) continue;
	foreach ($needles as $needle) {
		if (strpos($content, $needle) !== false) {
			$secretEmbedded = true;
			break 2;
		}
	}
}
$check(!$secretEmbedded, 'Runtime, migration, and Control secrets are absent from packageable source');
$manifest = json_decode((string) file_get_contents($root.'/app-manifest.json'), true);
$productConfig = (string) file_get_contents($root.'/application/config/product.php');
$check(is_array($manifest) && strpos($productConfig, "'version' => '".($manifest['version'] ?? '')."'") !== false, 'Product version is consistent across manifest and application config');
$legacyIdentity = false;
foreach (array_unique($scanFiles) as $file) {
	if (realpath($file) === realpath(__FILE__) || !is_file($file) || filesize($file) >= 5 * 1024 * 1024) continue;
	$content = file_get_contents($file);
	if ($content !== false && preg_match('/rembang|mukhammad\s+anwar/i', $content) === 1) {
		$legacyIdentity = true;
		break;
	}
}
$check(!$legacyIdentity, 'Packageable source contains no previous-customer identity');

$connection = @new mysqli(
	(string) ($runtime['database']['hostname'] ?? ''),
	(string) ($runtime['database']['username'] ?? ''),
	(string) ($runtime['database']['password'] ?? ''),
	(string) ($runtime['database']['database'] ?? '')
);
$check(!$connection->connect_errno, 'Least-privilege database connection succeeds');
if (!$connection->connect_errno) {
	$grants = array();
	$result = $connection->query('SHOW GRANTS FOR CURRENT_USER()');
	while ($result && $row = $result->fetch_row()) $grants[] = $row[0];
	$grantText = implode("\n", $grants);
	$check(stripos($grantText, 'GRANT ALL') === false && preg_match('/\\b(CREATE|ALTER|DROP|GRANT OPTION|SUPER|FILE)\\b/i', $grantText) !== 1, 'Runtime database account has no DDL/admin grants');
	$connection->close();
}

$http = static function (string $path) use ($baseUrl): array {
	$handle = curl_init($baseUrl.$path);
	$headers = array();
	curl_setopt_array($handle, array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_TIMEOUT => 15,
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_SSL_VERIFYHOST => 2,
		CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$headers): int {
			$length = strlen($line);
			if (strpos($line, ':') !== false) {
				list($name, $value) = explode(':', $line, 2);
				$headers[strtolower(trim($name))][] = trim($value);
			}
			return $length;
		},
	));
	$body = (string) curl_exec($handle);
	$status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
	curl_close($handle);
	return array($status, $body, $headers);
};

list($healthStatus, $healthBody) = $http('/health');
$health = json_decode($healthBody, true);
$check($healthStatus === 200 && ($health['status'] ?? '') === 'ok', 'Public health endpoint is healthy and minimal');
list($notFoundStatus, , $notFoundHeaders) = $http('/__namua_application_404_probe');
$notFoundRequestId = implode(', ', $notFoundHeaders['x-request-id'] ?? array());
$check(
	$notFoundStatus === 404
	&& preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D', $notFoundRequestId) === 1,
	'Application errors preserve the public correlation request ID'
);
foreach (array('/penatus.sql', '/db_backup/penatus.sql', '/application/config/database.php', '/database/README.md', '/deploy/nginx-ci3.conf', '/docs/ROADMAP.md', '/tools/security_scan.php', '/tools/activate_release.php', '/app-manifest.json', '/composer.json', '/INSTALL.md', '/setup', '/index.php/setup') as $path) {
	list($status) = $http($path);
	$check($status === 404, 'Protected path returns 404: '.$path);
}

fwrite(STDOUT, "Checks: {$checks}; failures: {$failures}.\n");
exit($failures === 0 ? 0 : 1);
