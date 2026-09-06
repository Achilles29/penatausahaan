<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}

$root = dirname(__DIR__);
$logDirectory = realpath($root.'/application/logs');
$expected = $root.'/application/logs';
$retentionDays = max(7, min(365, (int) (getenv('PENATUS_LOG_RETENTION_DAYS') ?: 30)));
if ($logDirectory === false || $logDirectory !== $expected || is_link($logDirectory)) {
	throw new RuntimeException('Application log directory is unavailable or unsafe.');
}
$cutoff = time() - ($retentionDays * 86400);
$deleted = 0;
foreach (new DirectoryIterator($logDirectory) as $entry) {
	if (!$entry->isFile() || $entry->isLink()) continue;
	$name = $entry->getFilename();
	if (preg_match('/^log-\d{4}-\d{2}-\d{2}\.(?:jsonl|php)$/D', $name) !== 1) continue;
	if ($entry->getMTime() >= $cutoff) continue;
	$path = $entry->getPathname();
	if (dirname($path) !== $logDirectory || !unlink($path)) throw new RuntimeException('Unable to remove an expired application log.');
	$deleted++;
}
fwrite(STDOUT, 'Log retention complete; retention_days='.$retentionDays.'; deleted='.$deleted.PHP_EOL);
