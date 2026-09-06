<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}
if ($argc < 2 || $argc > 3 || !is_file($argv[1])) {
	fwrite(STDERR, "Usage: php tools/activate_release.php /path/to/*.release.json [activate|rollback]\n");
	exit(1);
}

require_once __DIR__.'/lib/Release_signature.php';
require_once __DIR__.'/lib/Migration_runner.php';
require_once __DIR__.'/lib/Control_receipt.php';

$root = dirname(__DIR__);
$operation = strtolower((string) ($argv[2] ?? 'activate'));
if (!in_array($operation, array('activate', 'rollback'), true)) throw new RuntimeException('Release operation must be activate or rollback.');
$verified = Release_signature::verify((string) $argv[1]);
$manifest = $verified['manifest'];
$applicationManifest = json_decode((string) file_get_contents($root.'/app-manifest.json'), true, 32, JSON_THROW_ON_ERROR);
if (($applicationManifest['product_code'] ?? '') !== $manifest['product_code']
	|| ($applicationManifest['version'] ?? '') !== $manifest['version']
	|| ($applicationManifest['schema_version'] ?? '') !== $manifest['schema_version']) {
	throw new RuntimeException('Deployed source does not match the signed release manifest.');
}

$runtimePath = getenv('PENATUS_RUNTIME_CONFIG') ?: '/var/lib/penatausahaan-config/runtime.json';
$controlPath = getenv('PENATUS_CONTROL_CONFIG') ?: '/var/lib/penatausahaan-config/control-center-heartbeat.json';
$identityPath = getenv('PENATUS_DEPLOYED_RELEASE') ?: '/var/lib/penatausahaan-config/deployed-release.json';
$receiptDirectory = getenv('PENATUS_DEPLOYMENT_RECEIPT_DIR') ?: '/var/lib/penatausahaan-deployments/receipts';
$runtime = readJson($runtimePath, 'Runtime configuration');
$control = readJson($controlPath, 'Control configuration');
$databaseConfig = (array) ($runtime['database'] ?? array());
$database = new PDO(
	'mysql:host='.(string) ($databaseConfig['hostname'] ?? '').';dbname='.(string) ($databaseConfig['database'] ?? '').';charset=utf8mb4',
	(string) ($databaseConfig['username'] ?? ''),
	(string) ($databaseConfig['password'] ?? ''),
	array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false)
);
$runner = new Migration_runner($database, $root.'/database/migrations', $root.'/database/schema/20260906_baseline.sql');
$schemaStatus = $runner->status();
if (!hash_equals((string) $manifest['schema_version'], (string) $schemaStatus['current_schema']) || $schemaStatus['pending'] !== array()) {
	throw new RuntimeException('Database schema does not match the signed release target.');
}

$previous = is_file($identityPath) ? readJson($identityPath, 'Previous deployed release identity') : array();
if ($operation === 'rollback' && ($previous === array() || version_compare((string) $manifest['version'], (string) ($previous['version'] ?? ''), '>='))) {
	throw new RuntimeException('Rollback target must be older than the currently activated release.');
}
$identity = array(
	'schema' => 1,
	'product_code' => $manifest['product_code'],
	'version' => $manifest['version'],
	'schema_version' => $manifest['schema_version'],
	'manifest_sha256' => $verified['manifest_sha256'],
	'artifact_sha256' => $verified['artifact_sha256'],
	'signature_sha256' => $verified['signature_sha256'],
	'signing_key_id' => $verified['key_id'],
	'activated_at' => gmdate(DATE_ATOM),
);
writeIdentity($identityPath, $identity);

$backupStatusPath = getenv('PENATUS_BACKUP_STATUS') ?: '/var/lib/penatausahaan-backups/status.json';
$backup = is_file($backupStatusPath) ? json_decode((string) file_get_contents($backupStatusPath), true) : array();
$receiptId = uuid();
$payload = array(
	'receipt_id' => $receiptId,
	'instance_id' => (string) ($control['instance_id'] ?? ''),
	'occurred_at' => gmdate(DATE_ATOM),
	'receipt_type' => $operation === 'rollback' ? 'RELEASE_ROLLBACK' : 'RELEASE_ACTIVATION',
	'status' => 'SUCCEEDED',
	'environment' => (string) ($control['environment'] ?? ''),
	'app_version' => (string) $manifest['version'],
	'from_schema' => (string) ($previous['schema_version'] ?? $schemaStatus['current_schema']),
	'to_schema' => (string) $schemaStatus['current_schema'],
	'migration_versions' => array_values($schemaStatus['applied']),
	'release_manifest_sha256' => (string) $verified['manifest_sha256'],
	'artifact_sha256' => (string) $verified['artifact_sha256'],
	'backup_sha256' => isset($backup['encrypted_sha256']) && preg_match('/^[a-f0-9]{64}$/D', (string) $backup['encrypted_sha256']) === 1
		? (string) $backup['encrypted_sha256'] : NULL,
);
$result = Control_receipt::issue($payload, $control, $receiptDirectory);

fwrite(STDOUT, 'Signed release identity activated: '.$identity['version'].' / '.$identity['schema_version'].PHP_EOL);
fwrite(STDOUT, 'Control deployment receipt accepted: '.$result['receipt_id'].'; HTTP '.$result['http_status'].PHP_EOL);

function readJson(string $path, string $label): array
{
	if (!is_file($path) || !is_readable($path) || is_link($path)) throw new RuntimeException($label.' is unavailable or unsafe.');
	$decoded = json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
	if (!is_array($decoded)) throw new RuntimeException($label.' is invalid.');
	return $decoded;
}

function writeIdentity(string $path, array $identity): void
{
	$temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
	if (file_put_contents($temporary, json_encode($identity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL, LOCK_EX) === false) throw new RuntimeException('Unable to write deployed release identity.');
	chmod($temporary, 0640);
	@chown($temporary, 'root');
	@chgrp($temporary, 'www');
	if (!rename($temporary, $path)) {
		@unlink($temporary);
		throw new RuntimeException('Unable to install deployed release identity.');
	}
}

function uuid(): string
{
	$bytes = random_bytes(16);
	$bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
	$bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
	return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}
