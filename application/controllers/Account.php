<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Account extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('form_validation');
	}

	public function password()
	{
		if ($this->input->method(TRUE) === 'POST')
		{
			$this->form_validation->set_rules('current_password', 'Kata sandi saat ini', 'required');
			$this->form_validation->set_rules('new_password', 'Kata sandi baru', 'required|min_length[12]|max_length[200]');
			$this->form_validation->set_rules('confirm_password', 'Konfirmasi kata sandi', 'required|matches[new_password]');

			if ($this->form_validation->run())
			{
				$row = $this->db->select('id, password')->where('id', (int) $this->user['id'])->get('users')->row_array();
				$current = (string) $this->input->post('current_password');
				$new = (string) $this->input->post('new_password');

				if ( ! $row || ! password_verify($current, $row['password']))
				{
					$this->session->set_flashdata('error', 'Kata sandi saat ini tidak sesuai.');
				}
				elseif (password_verify($new, $row['password']))
				{
					$this->session->set_flashdata('error', 'Kata sandi baru harus berbeda dari kata sandi saat ini.');
				}
				else
				{
					$new_hash = password_hash($new, PASSWORD_DEFAULT);
					$this->db->where('id', (int) $row['id'])->update('users', array(
						'password' => $new_hash,
					));
					$user_session = (array) $this->session->userdata('user');
					$user_session['password_change_required'] = FALSE;
					$user_session['auth_hash'] = auth_session_fingerprint($new_hash);
					$this->session->set_userdata('user', $user_session);
					$this->session->sess_regenerate(TRUE);
					log_message('info', 'Password diubah oleh user_id='.(int) $row['id']);
					$this->session->set_flashdata('success', 'Kata sandi berhasil diperbarui.');
					redirect('dashboard');
				}
				redirect('account/password');
			}
		}

		$this->render('account/password', array(), 'Ubah Kata Sandi');
	}
}
