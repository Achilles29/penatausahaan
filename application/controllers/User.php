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
			'select' => 'u.id, u.nama, u.nip, u.username, u.role, u.is_active, o.kode_opd, o.nama_opd AS opd_nama, ou.nama_unit AS unit_nama, p.nama_lengkap AS pegawai_nama',
			'joins' => array(
				array('master_opd o', 'o.id = u.opd_id'),
				array('master_opd_unit ou', 'ou.id = u.opd_unit_id'),
				array('pegawai p', 'p.id = u.pegawai_id'),
			),
			'searchable' => array('u.nama', 'u.nip', 'u.username'),
			'order_by' => 'u.id',
			'columns' => array(
				array('field' => 'nama',      'label' => 'Nama',          'order' => 'u.nama'),
				array('field' => 'identitas', 'label' => 'NIP / Username', 'width' => '190px'),
				array('field' => 'role',      'label' => 'Role',          'render' => 'badge',  'width' => '120px'),
				array('field' => 'opd_nama',  'label' => 'OPD',           'order' => 'o.kode_opd'),
				array('field' => 'is_active', 'label' => 'Status',        'render' => 'active', 'width' => '100px'),
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
			? $this->mm->options('master_opd', 'id', "CONCAT(kode_opd,' - ',nama_opd)", array(), 'kode_opd')
			: array();

		$this->render('user/index', array(
			'roles'    => $roles,
			'opd_opts' => $opd_opts,
			'is_super' => is_super(),
			'my_opd'   => scope_opd_id(),
			'data_url' => site_url('user/data'),
		), 'Manajemen Pengguna');
	}

	public function data()
	{
		$cfg = $this->cfg();
		$dt = array(
			'draw'   => (int) $this->input->get('draw'),
			'start'  => (int) $this->input->get('start'),
			'length' => (int) $this->input->get('length'),
			'search' => $this->input->get('search'),
			'order'  => $this->input->get('order'),
		);

		$filters = array();
		$v = $this->input->get('f_role');
		if ($v !== NULL && $v !== '') $filters['u.role'] = $v;
		$v = $this->input->get('f_opd_id');
		if ($v !== NULL && $v !== '') $filters['u.opd_id'] = (int) $v;
		$v = $this->input->get('f_status');
		if ($v !== NULL && $v !== '') $filters['u.is_active'] = (int) $v;

		$scope = is_super() ? NULL : array('column' => 'u.opd_id', 'ids' => array((int) scope_opd_id()));
		$res = $this->mm->datatables($cfg, $dt, $filters, $scope);
		foreach ($res['data'] as &$r) { $r['identitas'] = $r['nip'] ? $r['nip'] : $r['username']; }
		$this->output->set_content_type('application/json')
			->set_output(json_encode(array('draw' => $dt['draw']) + $res));
	}

	public function get($id)
	{
		$row = $this->db->select('u.*, p.nama_lengkap AS pegawai_nama, p.jenis_kepegawaian AS pegawai_jenis')
			->from('users u')
			->join('pegawai p', 'p.id = u.pegawai_id', 'left')
			->where('u.id', (int) $id)
			->get()->row_array();
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
			$pegawai_id = (int) $this->input->post('pegawai_id');
			if ( ! $pegawai_id)
			{
				$errors[] = 'Pegawai wajib dipilih.';
			}
			else
			{
				$peg_q = $this->db->select('id, nip, opd_id, opd_unit_id')
					->from('pegawai')->where('id', $pegawai_id);
				if ( ! $is_super) $peg_q->where('opd_id', (int) scope_opd_id());
				$peg = $peg_q->get()->row_array();
				if ( ! $peg)
				{
					$errors[] = 'Pegawai tidak valid atau di luar kewenangan.';
				}
				else
				{
					$data['pegawai_id'] = (int) $peg['id'];
					$data['nip']        = $peg['nip'];
					$opd_id = $is_super ? (int) $this->input->post('opd_id') : (int) scope_opd_id();
					if ( ! $opd_id) $opd_id = (int) $peg['opd_id'];
					$data['opd_id'] = $opd_id ?: NULL;
					if ( ! $data['opd_id']) $errors[] = 'OPD tidak teridentifikasi.';
					if ( ! empty($data['nip']) && ! $this->mm->is_unique_value('users', 'nip', $data['nip'], $id ?: NULL))
						$errors[] = 'NIP sudah digunakan oleh pengguna lain.';
				}
			}
			$data['username']    = NULL;
			$data['opd_unit_id'] = $this->input->post('opd_unit_id') ? (int) $this->input->post('opd_unit_id') : NULL;
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

	/** Cari pegawai by nama/NIP — dipakai select2-like widget di form user. */
	public function pegawai_search()
	{
		$q = trim($this->input->get('q', TRUE));
		if (strlen($q) < 2)
		{
			$this->output->set_content_type('application/json')->set_output('[]');
			return;
		}
		$this->db->select('m.id, m.nama_lengkap, m.nip, m.jenis_kepegawaian, m.opd_id, m.opd_unit_id, o.nama_opd')
			->from('pegawai m')
			->join('master_opd o', 'o.id = m.opd_id', 'left')
			->group_start()
				->like('m.nama_lengkap', $q)
				->or_like('m.nip', $q)
			->group_end()
			->order_by('m.nama_lengkap')
			->limit(15);
		if ( ! is_super()) $this->db->where('m.opd_id', (int) scope_opd_id());
		$rows = $this->db->get()->result_array();
		$this->output->set_content_type('application/json')->set_output(json_encode(array_values($rows)));
	}

	/** Opsi unit OPD untuk cascading (dipakai form). */
	public function unit_options()
	{
		$opd = (int) $this->input->get('parent');
		$opts = $this->mm->options('master_opd_unit', 'id', 'nama_unit', array('opd_id' => $opd), 'nama_unit');
		$this->output->set_content_type('application/json')->set_output(json_encode($opts));
	}

	/** Bidang urusan yang dipetakan ke OPD tertentu (untuk modal akses). */
	public function bidang_for_opd()
	{
		$opd_id = is_super() ? (int) $this->input->get('opd_id') : (int) scope_opd_id();
		if ( ! $opd_id) { $this->output->set_content_type('application/json')->set_output('[]'); return; }
		$rows = $this->db
			->select('b.id, b.kode_bidang, b.nama_bidang', FALSE)
			->from('master_bidang b')
			->join('opd_bidang_urusan obu', 'obu.bidang_urusan_id = b.id')
			->where('obu.opd_id', $opd_id)
			->order_by('b.kode_bidang')
			->get()->result_array();
		$this->output->set_content_type('application/json')->set_output(json_encode(array_values($rows)));
	}

	/** Daftar bidang_urusan_id yang sudah di-assign ke user (user_akses). */
	public function get_akses($user_id)
	{
		$user_id = (int) $user_id;
		$row = $this->mm->get_row('users', $user_id);
		if ( ! $row || ! $this->can_touch($row)) show_error('Akses ditolak', 403);
		$existing = $this->db->select('bidang_urusan_id')
			->where('user_id', $user_id)
			->where('bidang_urusan_id IS NOT NULL', NULL, FALSE)
			->get('user_akses')->result_array();
		$ids = array_map('intval', array_column($existing, 'bidang_urusan_id'));
		$this->output->set_content_type('application/json')
			->set_output(json_encode(array('user' => $row, 'akses' => $ids)));
	}

	/** Simpan akses bidang untuk user_opd — mengganti seluruh user_akses user ini. */
	public function save_akses()
	{
		$user_id    = (int) $this->input->post('user_id');
		$bidang_raw = $this->input->post('bidang_ids');

		$row = $this->mm->get_row('users', $user_id);
		if ( ! $row || ! $this->can_touch($row)) show_error('Akses ditolak', 403);
		if ($row['role'] !== 'user_opd')
		{
			$this->output->set_content_type('application/json')
				->set_output(json_encode(array('ok' => 0, 'msg' => 'Hanya untuk user_opd')));
			return;
		}

		$this->db->delete('user_akses', array('user_id' => $user_id));

		if ($bidang_raw)
		{
			$ids = array_filter(array_map('intval', explode(',', $bidang_raw)));
			foreach ($ids as $bid)
			{
				$this->db->insert('user_akses', array('user_id' => $user_id, 'bidang_urusan_id' => $bid));
			}
		}

		$this->output->set_content_type('application/json')->set_output(json_encode(array('ok' => 1)));
	}

	private function can_touch($row)
	{
		if (is_super()) return TRUE;
		// admin_opd: hanya user non-super di OPD-nya
		return $row['role'] !== 'superadmin' && (int) $row['opd_id'] === (int) scope_opd_id();
	}
}
