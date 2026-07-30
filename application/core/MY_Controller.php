<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller untuk halaman yang butuh login.
 * Controller aplikasi (Dashboard, Master, dll) meng-extend kelas ini.
 * Controller publik (Auth) meng-extend CI_Controller langsung.
 */
class MY_Controller extends CI_Controller {

	/** @var array data user yang login */
	protected $user;

	public function __construct()
	{
		parent::__construct();

		if ( ! is_login())
		{
			// Simpan tujuan agar bisa kembali setelah login
			if ($this->input->method() === 'get')
			{
				$this->session->set_userdata('redirect_after_login', current_url());
			}
			redirect('auth/login');
		}

		$this->user = current_user();
	}

	/**
	 * Batasi akses hanya untuk role tertentu. Jika tidak cocok -> 403.
	 * @param string|array $roles
	 */
	protected function require_role($roles)
	{
		if ( ! has_role($roles))
		{
			show_error('Anda tidak memiliki hak akses untuk halaman ini.', 403, 'Akses Ditolak');
		}
	}

	/**
	 * Render halaman dengan template layout Materio.
	 * @param string $view       path view konten (relatif ke views/)
	 * @param array  $data       data untuk view
	 * @param string $page_title judul halaman
	 */
	protected function render($view, $data = array(), $page_title = 'Penatausahaan')
	{
		$data['page_title']   = $page_title;
		$data['current_user'] = $this->user;
		$data['content']      = $this->load->view($view, $data, TRUE);
		$this->load->view('templates/layout', $data);
	}
}
