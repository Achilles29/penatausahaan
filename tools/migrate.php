<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}

require_once __DIR__.'/lib/Migration_runner.php';
require_once __DIR__.'/lib/Deployment_guard.php';

$root = dirname(__DIR__);
$action = strtolower((string) ($argv[1] ?? 'status'));
if (!in_array($action, array('status', 'up', 'down'), true)) {
	fwrite(STDERR, "Usage: php tools/migrate.php [status|up|down] [rollback-steps]\n");
	exit(1);
}

$runtimePath = getenv('PENATUS_RUNTIME_CONFIG') ?: '/var/lib/penatausahaan-config/runtime.json';
$migrationConfigPath = getenv('PENATUS_MIGRATION_CONFIG') ?: '/var/lib/penatausahaan-config/migration.json';
$backupStatusPath = getenv('PENATUS_BACKUP_STATUS') ?: '/var/lib/penatausahaan-backups/status.json';
$runtime = readJsonFile($runtimePath, 'Runtime configuration');
$runtimeDatabase = (array) ($runtime['database'] ?? array());

if ($action === 'status') {
	$connection = $runtimeDatabase;
} else {
	$connection = migrationConnection($migrationConfigPath);
	foreach (array('hostname', 'database') as $field) {
		if (!isset($runtimeDatabase[$field], $connection[$field])
			|| !hash_equals((string) $runtimeDatabase[$field], (string) $connection[$field])) {
			throw new RuntimeException('Migration target does not match the application runtime database.');
		}
	}
}

$database = new PDO(
	'mysql:host='.(string) ($connection['hostname'] ?? '').';dbname='.(string) ($connection['database'] ?? '').';charset=utf8mb4',
	(string) ($connection['username'] ?? ''),
	(string) ($connection['password'] ?? ''),
	array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false)
);
$runner = new Migration_runner($database, $root.'/database/migrations', $root.'/database/schema/20260906_baseline.sql');
$before = $runner->status();

if ($action === 'status') {
	printStatus($before);
	exit(0);
}

$steps = $action === 'down' ? (int) ($argv[2] ?? 1) : 0;
if (($action === 'up' && $before['pending'] === array()) || ($action === 'down' && $before['applied'] === array())) {
	fwrite(STDOUT, "No database change is required.\n");
	printStatus($before);
	exit(0);
}

runMandatoryBackup();
$maximumAge = (int) (getenv('PENATUS_MIGRATION_BACKUP_MAX_AGE') ?: 1800);
$guard = Deployment_guard::assertFreshVerifiedBackup(
	$runtime,
	$backupStatusPath,
	(string) $before['current_schema'],
	$maximumAge
);
fwrite(STDOUT, 'Backup guard passed: '.$guard['artifact'].'; schema='.$guard['schema_version'].PHP_EOL);

$after = $action === 'up' ? $runner->migrateUp() : $runner->migrateDown($steps);
fwrite(STDOUT, 'Migration '.$action.' completed and verified.'.PHP_EOL);
printStatus($after);

function readJsonFile(string $path, string $label): array
{
	if (!is_file($path) || !is_readable($path) || is_link($path)) throw new RuntimeException($label.' is unavailable or unsafe.');
	$decoded = json_decode((string) file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
	if (!is_array($decoded)) throw new RuntimeException($label.' is invalid.');
	return $decoded;
}

function migrationConnection(string $path): array
{
	$environment = array(
		'hostname' => getenv('PENATUS_DB_MIGRATION_HOST'),
		'username' => getenv('PENATUS_DB_MIGRATION_USERNAME'),
		'password' => getenv('PENATUS_DB_MIGRATION_PASSWORD'),
		'database' => getenv('PENATUS_DB_MIGRATION_DATABASE'),
	);
	$complete = true;
	foreach ($environment as $value) $complete = $complete && is_string($value) && $value !== '';
	if ($complete) return $environment;

	$config = readJsonFile($path, 'Migration configuration');
	if ((fileperms($path) & 0777) !== 0600) throw new RuntimeException('Migration configuration mode must be 0600.');
	if (function_exists('posix_geteuid') && fileowner($path) !== 0) throw new RuntimeException('Migration configuration must be owned by root.');
	foreach (array('hostname', 'username', 'password', 'database') as $field) {
		if (!isset($config[$field]) || !is_string($config[$field]) || $config[$field] === '') {
			throw new RuntimeException('Migration configuration is incomplete.');
		}
	}
	return $config;
}

function runMandatoryBackup(): void
{
	$command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/backup.php');
	passthru($command, $exitCode);
	if ($exitCode !== 0) throw new RuntimeException('Migration stopped because the mandatory pre-change backup failed.');
}

function printStatus(array $status): void
{
	fwrite(STDOUT, 'Current schema: '.$status['current_schema'].PHP_EOL);
	fwrite(STDOUT, 'Target schema: '.$status['target_schema'].PHP_EOL);
	fwrite(STDOUT, 'Applied migrations: '.($status['applied'] === array() ? 'none' : implode(', ', $status['applied'])).PHP_EOL);
	fwrite(STDOUT, 'Pending migrations: '.($status['pending'] === array() ? 'none' : implode(', ', $status['pending'])).PHP_EOL);
}
