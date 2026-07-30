<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Otentikasi: login via NIP (admin_opd/user_opd) atau username (superadmin).
 * Satu kolom identitas menerima keduanya.
 */
class Auth extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('form_validation');
	}

	public function index()
	{
		redirect('auth/login');
	}

	public function login()
	{
		if (is_login())
		{
			redirect('dashboard');
		}

		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('identitas', 'NIP / Username', 'required|trim');
			$this->form_validation->set_rules('password', 'Kata sandi', 'required');

			if ($this->form_validation->run())
			{
				$id  = $this->input->post('identitas', TRUE);
				$pwd = $this->input->post('password');

				$user = $this->db->select('u.*, o.nama_opd AS opd_nama, ou.nama_unit AS unit_nama')
					->from('users u')
					->join('master_opd o', 'o.id = u.opd_id', 'left')
					->join('master_opd_unit ou', 'ou.id = u.opd_unit_id', 'left')
					->group_start()->where('u.nip', $id)->or_where('u.username', $id)->group_end()
					->where('u.is_active', 1)
					->limit(1)->get()->row();

				if ($user && password_verify($pwd, $user->password))
				{
					$this->db->where('id', $user->id)->update('users', array('last_login' => date('Y-m-d H:i:s')));

					$this->session->set_userdata('user', array(
						'id'          => (int) $user->id,
						'nip'         => $user->nip,
						'username'    => $user->username,
						'nama'        => $user->nama,
						'role'        => $user->role,
						'opd_id'      => $user->opd_id !== NULL ? (int) $user->opd_id : NULL,
						'opd_unit_id' => $user->opd_unit_id !== NULL ? (int) $user->opd_unit_id : NULL,
						'pegawai_id'  => $user->pegawai_id !== NULL ? (int) $user->pegawai_id : NULL,
						'opd_nama'    => $user->opd_nama,
						'unit_nama'   => $user->unit_nama,
					));

					$dest = $this->session->userdata('redirect_after_login');
					$this->session->unset_userdata('redirect_after_login');
					redirect($dest ? $dest : 'dashboard');
				}
				else
				{
					$this->session->set_flashdata('login_error', 'NIP/Username atau kata sandi salah.');
					redirect('auth/login');
				}
			}
		}

		$this->load->view('auth/login');
	}

	public function logout()
	{
		$this->session->unset_userdata('user');
		$this->session->sess_destroy();
		redirect('auth/login');
	}
}
