<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Health extends CI_Controller
{
	public function index()
	{
		if ( ! in_array($this->input->method(TRUE), array('GET', 'HEAD'), TRUE))
		{
			return $this->respond(405, array('status' => 'error', 'code' => 'method_not_allowed'));
		}

		$database_ok = FALSE;
		try
		{
			$database_ok = (bool) $this->db->query('SELECT 1 AS healthy')->row_array();
		}
		catch (Throwable $exception)
		{
			log_message('error', 'Health database check failed: '.$exception->getMessage());
		}

		$product = (array) $this->config->item('product');
		return $this->respond($database_ok ? 200 : 503, array(
			'status' => $database_ok ? 'ok' : 'down',
			'product' => isset($product['code']) ? $product['code'] : 'NAMUA_PENATAUSAHAAN',
			'version' => isset($product['version']) ? $product['version'] : 'unknown',
			'environment' => isset($product['environment']) ? $product['environment'] : 'unknown',
			'time' => date(DATE_ATOM),
		));
	}

	private function respond($status, array $payload)
	{
		$this->output
			->set_status_header((int) $status)
			->set_content_type('application/json', 'utf-8')
			->set_header('Cache-Control: no-store')
			->set_header('X-Content-Type-Options: nosniff')
			->set_output(json_encode($payload, JSON_UNESCAPED_SLASHES));
	}
}
