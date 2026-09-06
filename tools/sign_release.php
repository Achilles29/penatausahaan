<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}
if ($argc !== 2 || !is_file($argv[1]) || substr($argv[1], -13) !== '.release.json') {
	fwrite(STDERR, "Usage: php tools/sign_release.php /path/to/*.release.json\n");
	exit(1);
}

$manifestPath = realpath($argv[1]);
$keyPath = getenv('PENATUS_RELEASE_SIGNING_KEY') ?: '/var/lib/namua-control/release-signing/private/NAMUA_PENATAUSAHAAN.json';
if ($manifestPath === false || !is_file($keyPath) || !is_readable($keyPath) || is_link($keyPath)) {
	throw new RuntimeException('Release manifest or signing key is unavailable.');
}
if ((fileperms($keyPath) & 0777) !== 0600 || (function_exists('posix_geteuid') && fileowner($keyPath) !== 0)) {
	throw new RuntimeException('Release signing key must be owned by root with mode 0600.');
}

$manifestBytes = (string) file_get_contents($manifestPath);
$manifest = json_decode($manifestBytes, true, 32, JSON_THROW_ON_ERROR);
$key = json_decode((string) file_get_contents($keyPath), true, 32, JSON_THROW_ON_ERROR);
if (($manifest['product_code'] ?? '') !== 'NAMUA_PENATAUSAHAAN'
	|| ($key['product_code'] ?? '') !== $manifest['product_code']
	|| ($key['algorithm'] ?? '') !== 'Ed25519'
	|| preg_match('/^[a-f0-9-]{36}$/D', (string) ($key['key_id'] ?? '')) !== 1) {
	throw new RuntimeException('Release signing metadata is invalid.');
}
$secretKey = base64_decode((string) ($key['secret_key_base64'] ?? ''), true);
$publicKey = base64_decode((string) ($key['public_key_base64'] ?? ''), true);
if ($secretKey === false || strlen($secretKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES
	|| $publicKey === false || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
	|| !hash_equals((string) ($key['public_key_sha256'] ?? ''), hash('sha256', $publicKey))) {
	throw new RuntimeException('Release signing key material is invalid.');
}

$manifestHash = hash('sha256', $manifestBytes);
$context = 'NAMUA_RELEASE_MANIFEST_V1';
$signature = sodium_crypto_sign_detached($context."\n".$manifestHash, $secretKey);
$signaturePath = substr($manifestPath, 0, -13).'.release.sig.json';
$payload = array(
	'schema' => 1,
	'product_code' => $manifest['product_code'],
	'key_id' => $key['key_id'],
	'algorithm' => 'Ed25519',
	'context' => $context,
	'signed_file' => basename($manifestPath),
	'manifest_sha256' => $manifestHash,
	'public_key_sha256' => hash('sha256', $publicKey),
	'signature_base64' => base64_encode($signature),
	'signed_at' => gmdate(DATE_ATOM),
);
$temporary = $signaturePath.'.tmp.'.bin2hex(random_bytes(6));
if (file_put_contents($temporary, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL, LOCK_EX) === false) {
	throw new RuntimeException('Unable to write release signature.');
}
chmod($temporary, 0640);
if (!rename($temporary, $signaturePath)) {
	@unlink($temporary);
	throw new RuntimeException('Unable to install release signature.');
}
sodium_memzero($secretKey);

fwrite(STDOUT, 'Signature: '.$signaturePath.PHP_EOL);
fwrite(STDOUT, 'Key ID: '.$key['key_id'].'; manifest SHA256: '.$manifestHash.PHP_EOL);
