<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}
require_once __DIR__.'/lib/Control_receipt.php';

$action = strtolower((string) ($argv[1] ?? 'retry'));
if ($action !== 'retry') {
	fwrite(STDERR, "Usage: php tools/deployment_receipt.php retry\n");
	exit(1);
}
$controlPath = getenv('PENATUS_CONTROL_CONFIG') ?: '/var/lib/penatausahaan-config/control-center-heartbeat.json';
$receiptDirectory = getenv('PENATUS_DEPLOYMENT_RECEIPT_DIR') ?: '/var/lib/penatausahaan-deployments/receipts';
if (!is_file($controlPath) || !is_readable($controlPath) || is_link($controlPath)) throw new RuntimeException('Control configuration is unavailable.');
$control = json_decode((string) file_get_contents($controlPath), true, 32, JSON_THROW_ON_ERROR);
$results = Control_receipt::retryPending($control, $receiptDirectory);
fwrite(STDOUT, 'Pending receipt retry complete; delivered='.count($results).PHP_EOL);
