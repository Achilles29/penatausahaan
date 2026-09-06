<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Log extends CI_Log
{
	protected function _format_line($level, $date, $message)
	{
		return self::format_record($level, $date, $message);
	}

	public static function format_record($level, $date, $message)
	{
		$uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
		$path = parse_url($uri, PHP_URL_PATH);
		if ( ! is_string($path)) $path = '';
		$record = array(
			'timestamp' => (string) $date,
			'level' => strtoupper((string) $level),
			'request_id' => defined('NAMUA_REQUEST_ID') ? NAMUA_REQUEST_ID : 'unavailable',
			'environment' => defined('ENVIRONMENT') ? ENVIRONMENT : 'unknown',
			'sapi' => PHP_SAPI,
			'method' => PHP_SAPI === 'cli' ? 'CLI' : strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN')),
			'path' => substr($path, 0, 500),
			'message' => substr((string) $message, 0, 4000),
		);
		$encoded = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
		return ($encoded !== FALSE ? $encoded : '{"level":"ERROR","message":"log_encoding_failed"}').PHP_EOL;
	}
}
