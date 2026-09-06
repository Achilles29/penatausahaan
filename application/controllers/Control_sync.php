<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Control_sync extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		if ( ! $this->input->is_cli_request()) show_404();
		$this->load->library('control_heartbeat');
	}

	public function index()
	{
		return $this->status();
	}

	public function status()
	{
		echo json_encode($this->control_heartbeat->snapshot(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
	}

	public function send()
	{
		try
		{
			$result = $this->control_heartbeat->send();
			log_message('info', 'Control heartbeat accepted with health='.$result['health']);
			echo 'Heartbeat accepted; health='.$result['health'].'; http='.$result['http_status'].PHP_EOL;
		}
		catch (Throwable $exception)
		{
			log_message('error', 'Control heartbeat failed: '.$exception->getMessage());
			fwrite(STDERR, 'Heartbeat failed: '.$exception->getMessage().PHP_EOL);
			exit(1);
		}
	}
}
