<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}

$path = getenv('PENATUS_TENANT_CONFIG') ?: '/var/lib/penatausahaan-config/tenant.json';
$existing = is_file($path) ? json_decode((string) file_get_contents($path), true) : array();
$existing = is_array($existing) ? $existing : array();
$mapping = array(
	'pemda' => 'PENATUS_TENANT_NAME',
	'alamat' => 'PENATUS_TENANT_ADDRESS',
	'kontak' => 'PENATUS_TENANT_CONTACT',
	'website' => 'PENATUS_TENANT_WEBSITE',
	'kota' => 'PENATUS_TENANT_CITY',
	'logo' => 'PENATUS_TENANT_LOGO',
	'perihal_npd' => 'PENATUS_TENANT_NPD_SUBJECT',
);

$tenant = array('format_version' => 1);
foreach ($mapping as $key => $environment) {
	$value = getenv($environment);
	$tenant[$key] = $value !== false ? trim($value) : (string) ($existing[$key] ?? '');
}
foreach (array('ppk_nama', 'ppk_nip', 'bendahara_nama', 'bendahara_nip') as $key) {
	$tenant[$key] = (string) ($existing[$key] ?? '');
}
if ($tenant['pemda'] === '') {
	throw new RuntimeException('Set PENATUS_TENANT_NAME for first-time tenant configuration.');
}
if ($tenant['perihal_npd'] === '') $tenant['perihal_npd'] = 'Permintaan Pembayaran Kegiatan Non Tunai';
if ($tenant['logo'] !== '' && (strpos($tenant['logo'], '..') !== false || preg_match('~^assets/img/[A-Za-z0-9_./-]+\.(?:png|jpe?g|svg)$~D', $tenant['logo']) !== 1)) {
	throw new RuntimeException('Tenant logo must be a safe relative path under assets/img.');
}

$directory = dirname($path);
if (!is_dir($directory) || is_link($directory)) throw new RuntimeException('Private configuration directory is unavailable.');
$temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
file_put_contents($temporary, json_encode($tenant, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL, LOCK_EX);
chmod($temporary, 0640);
chown($temporary, 'root');
chgrp($temporary, 'www');
if (!rename($temporary, $path)) {
	@unlink($temporary);
	throw new RuntimeException('Tenant configuration cannot be installed.');
}
fwrite(STDOUT, "Tenant configuration installed outside webroot.\n");
