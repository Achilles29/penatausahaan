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
		$this->output
			->set_header('Cache-Control: no-store, private')
			->set_header('Pragma: no-cache')
			->set_header('X-Content-Type-Options: nosniff');

		if ( ! is_login())
		{
			// Simpan tujuan agar bisa kembali setelah login
			if ($this->input->method() === 'get')
			{
				$this->session->set_userdata('redirect_after_login', current_url());
			}
			redirect('auth/login');
		}

		$session_user = current_user();
		$fresh_user = $this->db->select('u.id, u.nip, u.username, u.nama, u.password, u.role, u.opd_id, u.opd_unit_id, u.pegawai_id, u.akses_semua_bidang, u.is_active, o.nama_opd AS opd_nama, ou.nama_unit AS unit_nama')
			->from('users u')
			->join('master_opd o', 'o.id = u.opd_id', 'left')
			->join('master_opd_unit ou', 'ou.id = u.opd_unit_id', 'left')
			->where('u.id', (int) ($session_user['id'] ?? 0))
			->limit(1)->get()->row_array();
		$expected_fingerprint = $fresh_user ? auth_session_fingerprint($fresh_user['password']) : '';
		if ( ! $fresh_user || ! (int) $fresh_user['is_active'] || empty($session_user['auth_hash'])
			|| ! hash_equals($expected_fingerprint, (string) $session_user['auth_hash']))
		{
			$this->session->unset_userdata('user');
			$this->session->sess_destroy();
			redirect('auth/login');
		}

		$this->user = array(
			'id' => (int) $fresh_user['id'],
			'nip' => $fresh_user['nip'],
			'username' => $fresh_user['username'],
			'nama' => $fresh_user['nama'],
			'role' => $fresh_user['role'],
			'opd_id' => $fresh_user['opd_id'] !== NULL ? (int) $fresh_user['opd_id'] : NULL,
			'opd_unit_id' => $fresh_user['opd_unit_id'] !== NULL ? (int) $fresh_user['opd_unit_id'] : NULL,
			'pegawai_id' => $fresh_user['pegawai_id'] !== NULL ? (int) $fresh_user['pegawai_id'] : NULL,
			'akses_semua_bidang' => (int) $fresh_user['akses_semua_bidang'],
			'opd_nama' => $fresh_user['opd_nama'],
			'unit_nama' => $fresh_user['unit_nama'],
			'password_change_required' => ! empty($session_user['password_change_required']),
			'auth_hash' => $expected_fingerprint,
		);
		$this->session->set_userdata('user', $this->user);
		if ( ! empty($this->user['password_change_required'])
			&& trim($this->uri->uri_string(), '/') !== 'account/password')
		{
			$this->session->set_flashdata('error', 'Kata sandi lama tidak memenuhi kebijakan keamanan. Perbarui kata sandi untuk melanjutkan.');
			redirect('account/password');
		}

		// Enforcement hak akses menu (role matrix). superadmin selalu lolos;
		// endpoint utility/tak-terpetakan -> NULL -> diizinkan.
		$mkey = current_menu_key();
		if ($mkey !== NULL && ! menu_allowed($mkey))
		{
			show_error('Menu ini tidak tersedia untuk akun Anda. Hubungi admin.', 403, 'Akses Ditolak');
		}
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

	/** Tolak perubahan state melalui GET atau metode selain POST. */
	protected function require_post()
	{
		if ($this->input->method(TRUE) !== 'POST')
		{
			show_error('Metode tidak diizinkan.', 405, 'Method Not Allowed');
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
