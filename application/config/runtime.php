<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('penatus_runtime_config'))
{
	function penatus_runtime_config()
	{
		static $runtime = NULL;
		if ($runtime !== NULL)
		{
			return $runtime;
		}

		$path = getenv('PENATUS_RUNTIME_CONFIG');
		if ($path === FALSE || trim($path) === '')
		{
			$path = '/var/lib/penatausahaan-config/runtime.json';
		}
		if ( ! is_file($path) || ! is_readable($path))
		{
			return $runtime = array();
		}

		$decoded = json_decode((string) file_get_contents($path), TRUE);
		return $runtime = is_array($decoded) ? $decoded : array();
	}
}

if ( ! function_exists('penatus_env'))
{
	function penatus_env($name, $fallback = NULL)
	{
		$value = getenv($name);
		return $value !== FALSE && $value !== '' ? $value : $fallback;
	}
}
