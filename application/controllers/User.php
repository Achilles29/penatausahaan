<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Manajemen Pengguna. superadmin: semua user & semua role.
 * admin_opd: hanya user di OPD-nya, role admin_opd/user_opd.
 */
class User extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->require_role(array('superadmin', 'admin_opd'));
		$this->load->model('Master_model', 'mm');
	}

	private function cfg()
	{
		return array(
			'from' => 'users u', 'alias' => 'u',
			'select' => 'u.id, u.nama, u.nip, u.username, u.role, u.is_active, o.nama_opd AS opd_nama, ou.nama_unit AS unit_nama',
			'joins' => array(
				array('master_opd o', 'o.id = u.opd_id'),
				array('master_opd_unit ou', 'ou.id = u.opd_unit_id'),
			),
			'searchable' => array('u.nama', 'u.nip', 'u.username'),
			'order_by' => 'u.nama',
			'columns' => array(
				array('field' => 'nama', 'label' => 'Nama', 'order' => 'u.nama'),
				array('field' => 'identitas', 'label' => 'NIP / Username', 'width' => '190px'),
				array('field' => 'role', 'label' => 'Role', 'render' => 'badge', 'width' => '120px'),
				array('field' => 'opd_nama', 'label' => 'OPD'),
				array('field' => 'is_active', 'label' => 'Status', 'render' => 'active', 'width' => '100px'),
			),
		);
	}

	public function index()
	{
		$role = current_role();
		$roles = ($role === 'superadmin')
			? array('superadmin' => 'Super Admin', 'admin_opd' => 'Admin OPD', 'user_opd' => 'User OPD')
			: array('admin_opd' => 'Admin OPD', 'user_opd' => 'User OPD');

		$opd_opts = ($role === 'superadmin')
			? $this->mm->options('master_opd', 'id', "CONCAT(COALESCE(singkatan,''),' - ',nama_opd)", array(), 'nama_opd')
			: array();

		$this->render('user/index', array(
			'roles'     => $roles,
			'opd_opts'  => $opd_opts,
			'is_super'  => is_super(),
			'my_opd'    => scope_opd_id(),
			'data_url'  => site_url('user/data'),
		), 'Manajemen Pengguna');
	}

	public function data()
	{
		$cfg = $this->cfg();
		$dt = array(
			'draw' => (int) $this->input->get('draw'), 'start' => (int) $this->input->get('start'),
			'length' => (int) $this->input->get('length'), 'search' => $this->input->get('search'),
			'order' => $this->input->get('order'),
		);
		$scope = is_super() ? NULL : array('column' => 'u.opd_id', 'ids' => array((int) scope_opd_id()));
		$res = $this->mm->datatables($cfg, $dt, array(), $scope);
		// bentuk kolom "identitas"
		foreach ($res['data'] as &$r) { $r['identitas'] = $r['nip'] ? $r['nip'] : $r['username']; }
		$this->output->set_content_type('application/json')
			->set_output(json_encode(array('draw' => $dt['draw']) + $res));
	}

	public function get($id)
	{
		$row = $this->mm->get_row('users', (int) $id);
		if ( ! $row) show_404();
		if ( ! $this->can_touch($row)) show_error('Akses ditolak', 403);
		unset($row['password']);
		$this->output->set_content_type('application/json')->set_output(json_encode($row));
	}

	public function save()
	{
		$id       = (int) $this->input->post('id');
		$role     = $this->input->post('role', TRUE);
		$nama     = $this->input->post('nama', TRUE);
		$is_super = is_super();
		$errors   = array();

		// Batasi role yang boleh dibuat
		$allowed_roles = $is_super ? array('superadmin','admin_opd','user_opd') : array('admin_opd','user_opd');
		if ( ! in_array($role, $allowed_roles, TRUE)) show_error('Role tidak valid', 403);

		$data = array('nama' => $nama, 'role' => $role, 'is_active' => $this->input->post('is_active') ? 1 : 0);

		if ($role === 'superadmin')
		{
			$username = $this->input->post('username', TRUE);
			if ( ! $username) $errors[] = 'Username wajib untuk superadmin.';
			$data['username'] = $username; $data['nip'] = NULL;
			$data['opd_id'] = NULL; $data['opd_unit_id'] = NULL;
			if ($username && ! $this->mm->is_unique_value('users', 'username', $username, $id ?: NULL))
				$errors[] = 'Username sudah digunakan.';
		}
		else
		{
			$nip = $this->input->post('nip', TRUE);
			if ( ! $nip) $errors[] = 'NIP wajib diisi.';
			$data['nip'] = $nip; $data['username'] = NULL;
			// admin_opd dipaksa ke OPD sendiri
			$opd_id = $is_super ? (int) $this->input->post('opd_id') : (int) scope_opd_id();
			if ( ! $opd_id) $errors[] = 'OPD wajib dipilih.';
			$data['opd_id'] = $opd_id ?: NULL;
			$data['opd_unit_id'] = $this->input->post('opd_unit_id') ? (int) $this->input->post('opd_unit_id') : NULL;
			if ($nip && ! $this->mm->is_unique_value('users', 'nip', $nip, $id ?: NULL))
				$errors[] = 'NIP sudah digunakan.';
		}

		if ( ! $nama) $errors[] = 'Nama wajib diisi.';

		$pwd = $this->input->post('password');
		if ($id === 0 && ! $pwd) $errors[] = 'Kata sandi wajib diisi untuk pengguna baru.';
		if ($pwd) $data['password'] = password_hash($pwd, PASSWORD_DEFAULT);

		// admin_opd edit: pastikan target di OPD-nya
		if ($id > 0)
		{
			$existing = $this->mm->get_row('users', $id);
			if ( ! $existing || ! $this->can_touch($existing)) show_error('Akses ditolak', 403);
		}

		if ($errors)
		{
			$this->session->set_flashdata('error', implode(' ', $errors));
			redirect('user');
		}

		if ($id > 0) { $this->mm->update('users', $id, $data); $msg = 'Pengguna diperbarui.'; }
		else { $this->mm->insert('users', $data); $msg = 'Pengguna ditambahkan.'; }
		$this->session->set_flashdata('success', $msg);
		redirect('user');
	}

	public function delete()
	{
		$id = (int) $this->input->post('id');
		if ($id === (int) $this->user['id'])
		{
			$this->session->set_flashdata('error', 'Tidak dapat menghapus akun sendiri.');
			redirect('user');
		}
		$row = $this->mm->get_row('users', $id);
		if ($row && $this->can_touch($row))
		{
			$this->mm->delete('users', $id);
			$this->session->set_flashdata('success', 'Pengguna dihapus.');
		}
		else { $this->session->set_flashdata('error', 'Akses ditolak.'); }
		redirect('user');
	}

	/** Opsi unit OPD untuk cascading (dipakai form). */
	public function unit_options()
	{
		$opd = (int) $this->input->get('parent');
		$opts = $this->mm->options('master_opd_unit', 'id', 'nama_unit', array('opd_id' => $opd), 'nama_unit');
		$this->output->set_content_type('application/json')->set_output(json_encode($opts));
	}

	private function can_touch($row)
	{
		if (is_super()) return TRUE;
		// admin_opd: hanya user non-super di OPD-nya
		return $row['role'] !== 'superadmin' && (int) $row['opd_id'] === (int) scope_opd_id();
	}
}
