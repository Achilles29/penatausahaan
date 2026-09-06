<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login_throttle
{
	private $directory;
	private $window = 900;
	private $maximum = 5;
	private $block_seconds = 900;

	public function __construct()
	{
		$this->directory = APPPATH.'cache/login_attempts';
		if (is_dir($this->directory) && random_int(1, 100) === 1)
		{
			foreach (glob($this->directory.'/*.json') ?: array() as $path)
			{
				if (is_file($path) && filemtime($path) < time() - 172800) @unlink($path);
			}
		}
	}

	public function status($identity)
	{
		$state = $this->mutate($identity, function ($state, $now) {
			$state['failures'] = array_values(array_filter($state['failures'], function ($at) use ($now) {
				return is_int($at) && $at >= ($now - $this->window);
			}));
			if ((int) $state['blocked_until'] <= $now)
			{
				$state['blocked_until'] = 0;
			}
			return $state;
		});

		$retry = max(0, (int) $state['blocked_until'] - time());
		return array('allowed' => $retry === 0, 'retry_after' => $retry);
	}

	public function failure($identity)
	{
		return $this->mutate($identity, function ($state, $now) {
			$state['failures'] = array_values(array_filter($state['failures'], function ($at) use ($now) {
				return is_int($at) && $at >= ($now - $this->window);
			}));
			$state['failures'][] = $now;
			if (count($state['failures']) >= $this->maximum)
			{
				$state['blocked_until'] = $now + $this->block_seconds;
			}
			return $state;
		});
	}

	public function clear($identity)
	{
		$path = $this->path($identity);
		if (is_file($path))
		{
			@unlink($path);
		}
	}

	private function mutate($identity, callable $callback)
	{
		if ( ! is_dir($this->directory) || ! is_writable($this->directory))
		{
			throw new RuntimeException('Login throttle storage is unavailable.');
		}

		$path = $this->path($identity);
		$handle = fopen($path, 'c+');
		if ($handle === FALSE || ! flock($handle, LOCK_EX))
		{
			if (is_resource($handle)) fclose($handle);
			throw new RuntimeException('Unable to lock login throttle state.');
		}

		$raw = stream_get_contents($handle);
		$state = json_decode((string) $raw, TRUE);
		if ( ! is_array($state))
		{
			$state = array('failures' => array(), 'blocked_until' => 0);
		}
		$state['failures'] = isset($state['failures']) && is_array($state['failures']) ? $state['failures'] : array();
		$state['blocked_until'] = isset($state['blocked_until']) ? (int) $state['blocked_until'] : 0;
		$state = $callback($state, time());

		ftruncate($handle, 0);
		rewind($handle);
		fwrite($handle, json_encode($state));
		fflush($handle);
		flock($handle, LOCK_UN);
		fclose($handle);
		@chmod($path, 0600);

		return $state;
	}

	private function path($identity)
	{
		$CI =& get_instance();
		$remote = (string) $CI->input->server('REMOTE_ADDR');
		$forwarded = (string) $CI->input->server('HTTP_CF_CONNECTING_IP');
		$ip = $remote;
		if (in_array($remote, array('127.0.0.1', '::1'), TRUE) && filter_var($forwarded, FILTER_VALIDATE_IP))
		{
			$ip = $forwarded;
		}
		$material = strtolower(trim((string) $identity)).'|'.$ip;
		$key = hash_hmac('sha256', $material, (string) $CI->config->item('encryption_key'));
		return $this->directory.'/'.$key.'.json';
	}
}
