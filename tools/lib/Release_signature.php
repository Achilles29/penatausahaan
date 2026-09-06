<?php

declare(strict_types=1);

final class Release_signature
{
	public static function verify(string $manifestPath, ?string $signaturePath = null, ?string $trustPath = null): array
	{
		$manifestReal = realpath($manifestPath);
		$signaturePath = $signaturePath ?: substr($manifestPath, 0, -13).'.release.sig.json';
		$signatureReal = realpath($signaturePath);
		$trustPath = $trustPath ?: '/var/lib/namua-control/release-signing/trusted/NAMUA_PENATAUSAHAAN.json';
		if ($manifestReal === false || $signatureReal === false || !is_file($trustPath) || !is_readable($trustPath)
			|| is_link($manifestReal) || is_link($signatureReal) || is_link($trustPath)) {
			throw new RuntimeException('Release manifest, signature, or trust key is unavailable or unsafe.');
		}
		if ((fileperms($trustPath) & 0777) !== 0600 || (function_exists('posix_geteuid') && fileowner($trustPath) !== 0)) {
			throw new RuntimeException('Release trust key must be root-owned with mode 0600.');
		}

		$manifestBytes = (string) file_get_contents($manifestReal);
		$manifest = json_decode($manifestBytes, true, 64, JSON_THROW_ON_ERROR);
		$signatureDocument = json_decode((string) file_get_contents($signatureReal), true, 32, JSON_THROW_ON_ERROR);
		$trust = json_decode((string) file_get_contents($trustPath), true, 32, JSON_THROW_ON_ERROR);
		$publicKey = base64_decode((string) ($trust['public_key_base64'] ?? ''), true);
		$signature = base64_decode((string) ($signatureDocument['signature_base64'] ?? ''), true);
		$manifestHash = hash('sha256', $manifestBytes);

		if (($manifest['manifest_version'] ?? null) !== 2
			|| ($manifest['product_code'] ?? '') !== 'NAMUA_PENATAUSAHAAN'
			|| ($trust['schema'] ?? null) !== 1 || ($trust['status'] ?? '') !== 'ACTIVE'
			|| ($trust['product_code'] ?? '') !== $manifest['product_code'] || ($trust['algorithm'] ?? '') !== 'Ed25519'
			|| ($signatureDocument['schema'] ?? null) !== 1 || ($signatureDocument['product_code'] ?? '') !== $manifest['product_code']
			|| ($signatureDocument['algorithm'] ?? '') !== 'Ed25519' || ($signatureDocument['context'] ?? '') !== 'NAMUA_RELEASE_MANIFEST_V1'
			|| ($signatureDocument['signed_file'] ?? '') !== basename($manifestReal)
			|| !hash_equals((string) ($trust['key_id'] ?? ''), (string) ($signatureDocument['key_id'] ?? ''))
			|| !hash_equals((string) ($signatureDocument['manifest_sha256'] ?? ''), $manifestHash)
			|| $publicKey === false || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
			|| !hash_equals((string) ($trust['public_key_sha256'] ?? ''), hash('sha256', $publicKey))
			|| !hash_equals((string) ($signatureDocument['public_key_sha256'] ?? ''), hash('sha256', $publicKey))
			|| $signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
			|| !sodium_crypto_sign_verify_detached($signature, "NAMUA_RELEASE_MANIFEST_V1\n".$manifestHash, $publicKey)) {
			throw new RuntimeException('Release signature is invalid or untrusted.');
		}

		$artifactReal = realpath(dirname($manifestReal).DIRECTORY_SEPARATOR.(string) ($manifest['artifact'] ?? ''));
		if ($artifactReal === false || !is_file($artifactReal) || is_link($artifactReal)
			|| !hash_equals((string) ($manifest['sha256'] ?? ''), (string) hash_file('sha256', $artifactReal))
			|| (int) ($manifest['size_bytes'] ?? -1) !== (int) filesize($artifactReal)) {
			throw new RuntimeException('Signed release artifact integrity validation failed.');
		}

		return array(
			'manifest' => $manifest,
			'manifest_path' => $manifestReal,
			'manifest_sha256' => $manifestHash,
			'signature_path' => $signatureReal,
			'signature_sha256' => hash_file('sha256', $signatureReal),
			'artifact_path' => $artifactReal,
			'artifact_sha256' => hash_file('sha256', $artifactReal),
			'key_id' => (string) $trust['key_id'],
		);
	}
}
