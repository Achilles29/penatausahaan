<?php

declare(strict_types=1);

final class Encrypted_backup
{
	private const MAGIC = "NAMUA-PENATUS-BACKUP\n";
	private const MAX_METADATA_BYTES = 16384;
	private const MAX_FRAME_BYTES = 2097152;

	public static function decodeKey(string $encoded): string
	{
		$encoded = strtr(trim($encoded), '-_', '+/');
		$padding = strlen($encoded) % 4;
		if ($padding !== 0) {
			$encoded .= str_repeat('=', 4 - $padding);
		}
		$key = base64_decode($encoded, true);
		if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES) {
			throw new RuntimeException('Backup encryption key is invalid.');
		}
		return $key;
	}

	public static function create(array $runtime, string $target, array $metadata): array
	{
		if (!extension_loaded('sodium') || !extension_loaded('zlib')) {
			throw new RuntimeException('PHP sodium and zlib extensions are required.');
		}
		$db = isset($runtime['database']) && is_array($runtime['database']) ? $runtime['database'] : array();
		$key = self::decodeKey((string) ($runtime['application']['backup_encryption_key'] ?? ''));
		foreach (array('hostname', 'username', 'password', 'database') as $field) {
			if (!isset($db[$field]) || (string) $db[$field] === '') {
				throw new RuntimeException('Runtime database configuration is incomplete.');
			}
		}

		$metadata += array(
			'format_version' => 1,
			'cipher' => 'secretstream_xchacha20poly1305',
			'compression' => 'gzip',
			'created_at' => gmdate(DATE_ATOM),
		);
		$metadataJson = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
		if (strlen($metadataJson) > self::MAX_METADATA_BYTES) {
			throw new RuntimeException('Backup metadata is too large.');
		}

		$output = fopen($target, 'xb');
		if ($output === false) {
			throw new RuntimeException('Unable to create backup output.');
		}
		chmod($target, 0600);

		try {
			[$state, $streamHeader] = sodium_crypto_secretstream_xchacha20poly1305_init_push($key);
			self::writeAll($output, self::MAGIC);
			self::writeAll($output, pack('N', strlen($metadataJson)));
			self::writeAll($output, $metadataJson);
			self::writeAll($output, $streamHeader);

			$command = array(
				'/usr/bin/mysqldump',
				'--host='.(string) $db['hostname'],
				'--user='.(string) $db['username'],
				'--default-character-set=utf8mb4',
				'--single-transaction',
				'--quick',
				'--skip-lock-tables',
				'--skip-add-locks',
				'--skip-comments',
				'--hex-blob',
				'--no-tablespaces',
				(string) $db['database'],
			);
			$environment = getenv();
			if (!is_array($environment)) $environment = array();
			$environment['MYSQL_PWD'] = (string) $db['password'];
			$descriptors = array(
				0 => array('pipe', 'r'),
				1 => array('pipe', 'w'),
				2 => array('pipe', 'w'),
			);
			$process = proc_open($command, $descriptors, $pipes, null, $environment, array('bypass_shell' => true));
			if (!is_resource($process)) {
				throw new RuntimeException('Unable to start database dump.');
			}
			fclose($pipes[0]);
			stream_set_blocking($pipes[1], false);
			stream_set_blocking($pipes[2], false);

			$deflate = deflate_init(ZLIB_ENCODING_GZIP, array('level' => 6));
			if ($deflate === false) throw new RuntimeException('Unable to initialize backup compression.');
			$plainHash = hash_init('sha256');
			$plainBytes = 0;
			$stderr = '';
			while (!feof($pipes[1]) || !feof($pipes[2])) {
				$read = array();
				if (!feof($pipes[1])) $read[] = $pipes[1];
				if (!feof($pipes[2])) $read[] = $pipes[2];
				if ($read === array()) break;
				$write = null;
				$except = null;
				if (stream_select($read, $write, $except, 10) === false) {
					throw new RuntimeException('Unable to read database dump.');
				}
				foreach ($read as $stream) {
					$chunk = fread($stream, 65536);
					if ($chunk === false || $chunk === '') continue;
					if ($stream === $pipes[2]) {
						if (strlen($stderr) < 8192) $stderr .= $chunk;
						continue;
					}
					$plainBytes += strlen($chunk);
					hash_update($plainHash, $chunk);
					$compressed = deflate_add($deflate, $chunk, ZLIB_NO_FLUSH);
					if ($compressed === false) throw new RuntimeException('Backup compression failed.');
					if ($compressed !== '') self::writeFrame($output, $state, $compressed, $metadataJson, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE);
				}
			}
			fclose($pipes[1]);
			fclose($pipes[2]);
			$exitCode = proc_close($process);
			if ($exitCode !== 0) {
				throw new RuntimeException('Database dump failed: '.trim(substr($stderr, 0, 500)));
			}
			if ($plainBytes === 0) throw new RuntimeException('Database dump produced no data.');

			$final = deflate_add($deflate, '', ZLIB_FINISH);
			if ($final === false) throw new RuntimeException('Backup compression finalization failed.');
			self::writeFrame($output, $state, $final, $metadataJson, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL);
			fflush($output);
			fclose($output);

			return array(
				'metadata' => $metadata,
				'plaintext_size_bytes' => $plainBytes,
				'plaintext_sha256' => hash_final($plainHash),
				'encrypted_size_bytes' => filesize($target),
				'encrypted_sha256' => hash_file('sha256', $target),
			);
		} catch (Throwable $exception) {
			if (is_resource($output)) fclose($output);
			@unlink($target);
			throw $exception;
		} finally {
			sodium_memzero($key);
		}
	}

	public static function verify(string $path, string $encodedKey, $plainOutput = null): array
	{
		if ($plainOutput !== null && !is_resource($plainOutput)) throw new RuntimeException('Plaintext output stream is invalid.');
		$key = self::decodeKey($encodedKey);
		$input = fopen($path, 'rb');
		if ($input === false) throw new RuntimeException('Unable to open backup.');
		try {
			if (self::readExact($input, strlen(self::MAGIC)) !== self::MAGIC) {
				throw new RuntimeException('Backup magic is invalid.');
			}
			$length = unpack('Nlength', self::readExact($input, 4))['length'];
			if ($length < 2 || $length > self::MAX_METADATA_BYTES) throw new RuntimeException('Backup metadata length is invalid.');
			$metadataJson = self::readExact($input, $length);
			$metadata = json_decode($metadataJson, true, 32, JSON_THROW_ON_ERROR);
			if (($metadata['format_version'] ?? null) !== 1) throw new RuntimeException('Backup format version is unsupported.');
			if ($plainOutput !== null && ($metadata['restore_scope'] ?? '') !== 'selected_database_only') {
				throw new RuntimeException('Backup is not approved for isolated restore.');
			}
			$streamHeader = self::readExact($input, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
			$state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($streamHeader, $key);
			$inflate = inflate_init(ZLIB_ENCODING_GZIP);
			if ($inflate === false) throw new RuntimeException('Unable to initialize backup decompression.');
			$plainHash = hash_init('sha256');
			$plainBytes = 0;
			$foundSchema = false;
			$tail = '';
			$finalSeen = false;

			while (!feof($input)) {
				$prefix = fread($input, 4);
				if ($prefix === false) throw new RuntimeException('Unable to read backup frame.');
				if ($prefix === '') break;
				if (strlen($prefix) !== 4) throw new RuntimeException('Backup frame prefix is truncated.');
				$frameLength = unpack('Nlength', $prefix)['length'];
				if ($frameLength < SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES || $frameLength > self::MAX_FRAME_BYTES) {
					throw new RuntimeException('Backup frame length is invalid.');
				}
				$pulled = sodium_crypto_secretstream_xchacha20poly1305_pull($state, self::readExact($input, $frameLength), $metadataJson);
				if ($pulled === false) throw new RuntimeException('Backup authentication failed.');
				[$compressed, $tag] = $pulled;
				$plain = inflate_add($inflate, $compressed, $tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL ? ZLIB_FINISH : ZLIB_SYNC_FLUSH);
				if ($plain === false) throw new RuntimeException('Backup decompression failed.');
				$plainBytes += strlen($plain);
				hash_update($plainHash, $plain);
				if ($plainOutput !== null && $plain !== '') self::writeAll($plainOutput, $plain);
				$scan = $tail.$plain;
				if (stripos($scan, 'CREATE TABLE') !== false) $foundSchema = true;
				$tail = substr($scan, -64);
				if ($tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
					$finalSeen = true;
					if (fread($input, 1) !== '') throw new RuntimeException('Backup contains data after final frame.');
					break;
				}
			}
			if (!$finalSeen || !$foundSchema || $plainBytes === 0) throw new RuntimeException('Backup content verification failed.');
			return array(
				'metadata' => $metadata,
				'plaintext_size_bytes' => $plainBytes,
				'plaintext_sha256' => hash_final($plainHash),
				'encrypted_size_bytes' => filesize($path),
				'encrypted_sha256' => hash_file('sha256', $path),
			);
		} finally {
			fclose($input);
			sodium_memzero($key);
		}
	}

	private static function writeFrame($output, string &$state, string $data, string $additionalData, int $tag): void
	{
		$ciphertext = sodium_crypto_secretstream_xchacha20poly1305_push($state, $data, $additionalData, $tag);
		self::writeAll($output, pack('N', strlen($ciphertext)).$ciphertext);
	}

	private static function writeAll($stream, string $data): void
	{
		$offset = 0;
		while ($offset < strlen($data)) {
			$written = fwrite($stream, substr($data, $offset));
			if ($written === false || $written === 0) throw new RuntimeException('Unable to write backup data.');
			$offset += $written;
		}
	}

	private static function readExact($stream, int $length): string
	{
		$data = '';
		while (strlen($data) < $length && !feof($stream)) {
			$chunk = fread($stream, $length - strlen($data));
			if ($chunk === false) throw new RuntimeException('Unable to read backup data.');
			$data .= $chunk;
		}
		if (strlen($data) !== $length) throw new RuntimeException('Backup is truncated.');
		return $data;
	}
}
