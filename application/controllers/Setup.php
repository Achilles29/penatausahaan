<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller Setup: bangun ulang database penatus dari nol (skema + import
 * dari literasi + seed user & pajak). Berguna saat pindah device.
 *
 * KEAMANAN: hanya dapat dijalankan dari CLI. Pemeriksaan IP localhost tidak
 * aman di belakang reverse proxy/Cloudflare Tunnel karena koneksi publik
 * dapat terlihat berasal dari 127.0.0.1.
 */
class Setup extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		if ( ! $this->input->is_cli_request())
		{
			show_404();
		}
	}

	public function index()
	{
		$counts = $this->counts();
		echo "Setup Penatausahaan\nDatabase: penatus\n\n";
		foreach ($counts as $t => $c) { echo str_pad($t, 28) . ' : ' . $c . "\n"; }
		echo "\nPerintah:\n";
		echo "  php index.php setup/rebuild     # destruktif: rebuild penuh\n";
		echo "  php index.php setup/seed_users  # destruktif: reset user\n";
	}

	/** Rebuild penuh: skema -> import -> seed user. */
	public function rebuild()
	{
		// Fail before the destructive schema/import steps when seed secrets are absent.
		$this->seed_passwords();

		$log = array();
		$log[] = $this->run_sql_file(FCPATH . 'docs/master/penatus_schema.sql', 'Skema');
		$log[] = $this->run_sql_file(FCPATH . 'docs/master/penatus_import.sql', 'Import dari literasi');
		$log[] = $this->do_seed_users();

		echo "Rebuild selesai\n" . implode("\n", $log) . "\n\n";
		foreach ($this->counts() as $t => $c) { echo str_pad($t, 28) . ' : ' . $c . "\n"; }
	}

	/** Seed ulang hanya tabel users (idempotent). */
	public function seed_users()
	{
		echo $this->do_seed_users() . "\n";
	}

	// ------------------------------------------------------------------

	private function do_seed_users()
	{
		$passwords = $this->seed_passwords();

		$this->db->query('SET FOREIGN_KEY_CHECKS = 0');
		$this->db->truncate('user_akses');
		$this->db->truncate('users');
		$this->db->query('SET FOREIGN_KEY_CHECKS = 1');

		$now = date('Y-m-d H:i:s');
		$users = array(
			array(
				'nip' => NULL, 'username' => 'superadmin',
				'password' => password_hash($passwords['superadmin'], PASSWORD_DEFAULT),
				'nama' => 'Super Administrator', 'pegawai_id' => NULL,
				'role' => 'superadmin', 'opd_id' => NULL, 'opd_unit_id' => NULL,
				'is_active' => 1, 'created_at' => $now,
			),
			array(
				'nip' => '197001011990031001', 'username' => NULL,
				'password' => password_hash($passwords['admin_opd'], PASSWORD_DEFAULT),
				'nama' => 'Administrator OPD Demo', 'pegawai_id' => NULL,
				'role' => 'admin_opd', 'opd_id' => 16, 'opd_unit_id' => NULL,
				'is_active' => 1, 'created_at' => $now,
			),
			array(
				'nip' => '198901292012061001', 'username' => NULL,
				'password' => password_hash($passwords['user_opd'], PASSWORD_DEFAULT),
				'nama' => 'Pengguna OPD Demo', 'pegawai_id' => 1,
				'role' => 'user_opd', 'opd_id' => 16, 'opd_unit_id' => 3,
				'is_active' => 1, 'created_at' => $now,
			),
		);
		$this->db->insert_batch('users', $users);

		return 'Seed users: ' . count($users) . ' akun. Password dibaca dari environment CLI.';
	}

	private function seed_passwords()
	{
		$vars = array(
			'superadmin' => 'PENATUS_SEED_SUPERADMIN_PASSWORD',
			'admin_opd'  => 'PENATUS_SEED_ADMIN_OPD_PASSWORD',
			'user_opd'   => 'PENATUS_SEED_USER_OPD_PASSWORD',
		);
		$passwords = array();
		foreach ($vars as $key => $name)
		{
			$value = getenv($name);
			if ($value === FALSE || strlen($value) < 12)
			{
				throw new RuntimeException('Set ' . $name . ' minimal 12 karakter sebelum seed/rebuild.');
			}
			$passwords[$key] = $value;
		}

		return $passwords;
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
