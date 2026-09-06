<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}

defined('BASEPATH') OR define('BASEPATH', dirname(__DIR__).'/system/');
defined('ENVIRONMENT') OR define('ENVIRONMENT', 'testing');
defined('NAMUA_REQUEST_ID') OR define('NAMUA_REQUEST_ID', '123e4567-e89b-42d3-a456-426614174000');
require_once BASEPATH.'core/Log.php';
require_once dirname(__DIR__).'/application/core/MY_Log.php';

$_SERVER['REQUEST_URI'] = '/health?secret=must-not-appear';
$_SERVER['REQUEST_METHOD'] = 'GET';
$line = MY_Log::format_record('error', '2026-09-06T19:00:00.000000+07:00', "test\nmessage");
$record = json_decode(trim($line), true, 16, JSON_THROW_ON_ERROR);
if (($record['request_id'] ?? '') !== NAMUA_REQUEST_ID
	|| ($record['path'] ?? '') !== '/health'
	|| strpos($line, 'must-not-appear') !== false
	|| ($record['level'] ?? '') !== 'ERROR') {
	throw new RuntimeException('Structured log contract validation failed.');
}
fwrite(STDOUT, 'Structured log contract passed: JSONL, request ID, path without query string, and no query secret.'.PHP_EOL);
