<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Setup: bangun ulang database penatus dari nol (skema + import
 * dari literasi + seed user & pajak). Berguna saat pindah device.
 *
 * KEAMANAN: hanya untuk lingkungan pengembangan lokal. Aksi merusak
 * (rebuild) butuh ?confirm=yes. Sebaiknya dinonaktifkan di produksi.
 */
class Setup extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		// Batasi ke akses lokal saja
		$ip = $this->input->ip_address();
		if ( ! $this->input->is_cli_request() && ! in_array($ip, array('127.0.0.1', '::1'), TRUE))
		{
			show_error('Setup hanya dapat diakses dari localhost.', 403);
		}
	}

	public function index()
	{
		$counts = $this->counts();
		echo '<h2>Setup Penatausahaan</h2>';
		echo '<p>Database: <b>penatus</b></p>';
		echo '<pre>';
		foreach ($counts as $t => $c) { echo str_pad($t, 28) . ' : ' . $c . "\n"; }
		echo '</pre>';
		echo '<ul>';
		echo '<li><a href="' . site_url('setup/rebuild?confirm=yes') . '">Rebuild penuh (skema + import + seed)</a> — <b>menghapus & mengisi ulang</b></li>';
		echo '<li><a href="' . site_url('setup/seed_users') . '">Seed ulang user saja</a></li>';
		echo '</ul>';
		echo '<p><a href="' . site_url('/') . '">&larr; ke aplikasi</a></p>';
	}

	/** Rebuild penuh: skema -> import -> seed user. */
	public function rebuild()
	{
		if ($this->input->get('confirm') !== 'yes' && ! $this->input->is_cli_request())
		{
			show_error('Tambahkan ?confirm=yes untuk menjalankan rebuild (menghapus data).', 400);
		}

		$log = array();
		$log[] = $this->run_sql_file(FCPATH . 'docs/master/penatus_schema.sql', 'Skema');
		$log[] = $this->run_sql_file(FCPATH . 'docs/master/penatus_import.sql', 'Import dari literasi');
		$log[] = $this->do_seed_users();

		echo '<h2>Rebuild selesai</h2><pre>' . implode("\n", $log) . '</pre>';
		echo '<pre>';
		foreach ($this->counts() as $t => $c) { echo str_pad($t, 28) . ' : ' . $c . "\n"; }
		echo '</pre>';
		echo '<p><a href="' . site_url('/') . '">&larr; ke aplikasi</a></p>';
	}

	/** Seed ulang hanya tabel users (idempotent). */
	public function seed_users()
	{
		echo '<pre>' . $this->do_seed_users() . '</pre>';
		echo '<p><a href="' . site_url('setup') . '">&larr; kembali</a></p>';
	}

	// ------------------------------------------------------------------

	private function do_seed_users()
	{
		$this->db->query('SET FOREIGN_KEY_CHECKS = 0');
		$this->db->truncate('user_akses');
		$this->db->truncate('users');
		$this->db->query('SET FOREIGN_KEY_CHECKS = 1');

		$now = date('Y-m-d H:i:s');
		$users = array(
			array(
				'nip' => NULL, 'username' => 'superadmin',
				'password' => password_hash('admin123', PASSWORD_DEFAULT),
				'nama' => 'Super Administrator', 'pegawai_id' => NULL,
				'role' => 'superadmin', 'opd_id' => NULL, 'opd_unit_id' => NULL,
				'is_active' => 1, 'created_at' => $now,
			),
			array(
				'nip' => '197001011990031001', 'username' => NULL,
				'password' => password_hash('opd123', PASSWORD_DEFAULT),
				'nama' => 'Kepala Dinas Kearsipan dan Perpustakaan', 'pegawai_id' => NULL,
				'role' => 'admin_opd', 'opd_id' => 16, 'opd_unit_id' => NULL,
				'is_active' => 1, 'created_at' => $now,
			),
			array(
				'nip' => '198901292012061001', 'username' => NULL,
				'password' => password_hash('user123', PASSWORD_DEFAULT),
				'nama' => 'MUKHAMMAD ANWAR FUADI', 'pegawai_id' => 1,
				'role' => 'user_opd', 'opd_id' => 16, 'opd_unit_id' => 3,
				'is_active' => 1, 'created_at' => $now,
			),
		);
		$this->db->insert_batch('users', $users);

		return 'Seed users: ' . count($users) . ' akun (superadmin/admin123, '
			. '197001011990031001/opd123, 198901292012061001/user123)';
	}

	private function run_sql_file($path, $label)
	{
		if ( ! is_file($path)) return "[$label] GAGAL: file tidak ada ($path)";
		$sql  = file_get_contents($path);
		$conn = $this->db->conn_id;

		mysqli_multi_query($conn, $sql);
		do {
			if ($res = mysqli_store_result($conn)) { mysqli_free_result($res); }
		} while (mysqli_more_results($conn) && mysqli_next_result($conn));

		$err = mysqli_error($conn);
		return $err === '' ? "[$label] OK" : "[$label] ERROR: $err";
	}

	private function counts()
	{
		$tables = array('master_urusan','master_bidang','master_program','master_kegiatan',
			'master_subkegiatan','master_rekening','master_sumber_dana','master_opd',
			'master_opd_unit','opd_bidang_urusan','opd_unit_bidang_urusan','dpa','dpa_detail',
			'anggaran_kas','anggaran_kas_bulanan','pegawai','master_penerima',
			'master_skema_pajak','master_skema_pajak_detail','users');
		$out = array();
		foreach ($tables as $t)
		{
			$out[$t] = $this->db->count_all_results($t, TRUE);
		}
		return $out;
	}
}
