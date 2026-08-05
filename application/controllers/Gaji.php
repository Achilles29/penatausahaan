<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Modul Gaji — simulasi slip gaji ASN + rekap per OPD.
 * Mendukung PNS dan PPPK. Komponen sesuai peraturan berlaku (PP 5/2024, UU HPP 2021).
 *
 * BPJS dipisah: gaji (1% pegawai + 4% pemerintah dari GP+T.Keluarga)
 *               dan TPP (4% pemerintah dari TPP pegawai).
 * PPh 21 ditanggung pemerintah (DTP) untuk PNS/PPPK pemda.
 */
class Gaji extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Master_model', 'mm');
	}

	// =================== SIMULASI SLIP ===================

	public function simulasi()
	{
		$ke_rows = $this->db->select('no, nama, bulan_basis')
			->from('ref_gaji_ke')->where('is_active', 1)
			->order_by('no')->get()->result_array();
		$this->render('gaji/simulasi', array(
			'peg_url'    => site_url('gaji/pegawai_search'),
			'hitung_url' => site_url('gaji/hitung'),
			'ke_rows'    => $ke_rows,
		), 'Simulasi Slip Gaji');
	}

	/** AJAX: search pegawai untuk simulasi. */
	public function pegawai_search()
	{
		$q = trim($this->input->get('q', TRUE));
		if (strlen($q) < 2) { $this->_json(array()); return; }

		$rows = $this->db
			->select('m.id, m.nama_lengkap, m.nip, m.jenis_kepegawaian, m.golongan, m.pangkat,
			          m.masa_kerja_golongan, m.status_pernikahan, m.terima_tunjangan_keluarga, m.jumlah_anak,
			          m.tgl_lahir, m.tmt_kenaikan_pangkat, m.jabatan_struktural_id, m.jabatan_fungsional_id,
			          o.nama_opd, o.kode_opd,
			          rjs.nama_jabatan AS jab_struktural, rjs.eselon,
			          rjf.nama_jabatan AS jab_fungsional', FALSE)
			->from('pegawai m')
			->join('master_opd o',    'o.id = m.opd_id', 'left')
			->join('ref_jabatan rjs', 'rjs.id = m.jabatan_struktural_id', 'left')
			->join('ref_jabatan rjf', 'rjf.id = m.jabatan_fungsional_id', 'left')
			->group_start()
				->like('m.nama_lengkap', $q)
				->or_like('m.nip', $q)
			->group_end()
			->order_by('m.nama_lengkap')->limit(15);

		if ( ! is_super()) $this->db->where('m.opd_id', (int) scope_opd_id());
		$this->_json(array_values($this->db->get()->result_array()));
	}

	/** AJAX: POST pegawai_id, bulan, tahun, is_ke → kalkulasi slip gaji. */
	public function hitung()
	{
		$pegawai_id = (int) $this->input->post('pegawai_id');
		if ( ! $pegawai_id) { $this->_json(array('ok' => 0, 'msg' => 'Pegawai tidak dipilih')); return; }

		$bulan = (int) $this->input->post('bulan');
		$tahun = (int) $this->input->post('tahun');
		$is_ke = (int) $this->input->post('is_ke');

		if ($bulan < 1 || $bulan > 12) $bulan = (int) date('n');
		if ($tahun < 2020 || $tahun > 2099) $tahun = (int) date('Y');

		$ke_nama = '';
		if ($is_ke > 0) {
			$ke_row = $this->db->select('no,nama,bulan_basis')->from('ref_gaji_ke')
				->where('no', $is_ke)->where('is_active', 1)->limit(1)->get()->row_array();
			if ($ke_row) {
				$bulan   = (int) $ke_row['bulan_basis'];
				$ke_nama = $ke_row['nama'];
			} else {
				$is_ke = 0;
			}
		}

		$this->_json($this->_hitung_gaji($pegawai_id, $bulan, $tahun, $is_ke, $ke_nama));
	}

	// =================== REKAP GAJI PER OPD ===================

	public function rekap()
	{
		$opd_options = $this->db->select('id, CONCAT(kode_opd," — ",nama_opd) AS label', FALSE)
			->from('master_opd')->where('is_active', 1)->order_by('kode_opd')->get()->result_array();
		$opd_list = array();
		foreach ($opd_options as $o) $opd_list[$o['id']] = $o['label'];

		$ke_rows = $this->db->select('no, nama, bulan_basis')
			->from('ref_gaji_ke')->where('is_active', 1)
			->order_by('no')->get()->result_array();
		$this->render('gaji/rekap', array(
			'opd_list'       => $opd_list,
			'hitung_url'     => site_url('gaji/hitung_rekap'),
			'is_super'       => is_super(),
			'default_opd'    => is_super() ? 0 : (int) scope_opd_id(),
			'ke_rows'        => $ke_rows,
		), 'Rekap Gaji ASN');
	}

	/** AJAX: POST bulan, tahun, opd_id, is_ke → kalkulasi semua pegawai. */
	public function hitung_rekap()
	{
		$bulan  = (int) $this->input->post('bulan');
		$tahun  = (int) $this->input->post('tahun');
		$opd_id = (int) $this->input->post('opd_id');
		$is_ke  = (int) $this->input->post('is_ke');

		if ($bulan < 1 || $bulan > 12 || $tahun < 2000) {
			$this->_json(array('ok' => 0, 'msg' => 'Periode tidak valid'));
			return;
		}

		$ke_nama = '';
		if ($is_ke > 0) {
			$ke_row = $this->db->select('no,nama,bulan_basis')->from('ref_gaji_ke')
				->where('no', $is_ke)->where('is_active', 1)->limit(1)->get()->row_array();
			if ($ke_row) {
				$bulan   = (int) $ke_row['bulan_basis'];
				$ke_nama = $ke_row['nama'];
			} else {
				$is_ke = 0;
			}
		}

		$q = $this->db->select('m.id, m.jenis_kepegawaian')->from('pegawai m')
			->where('m.is_active', 1)
			->where_in('m.jenis_kepegawaian', array('PNS','PPPK'));
		if ($opd_id) $q->where('m.opd_id', $opd_id);
		elseif ( ! is_super()) $q->where('m.opd_id', (int) scope_opd_id());
		$pegawais = $this->db->get()->result_array();

		$rows = array();
		$t0 = array(
			'gaji_pokok' => 0, 't_keluarga' => 0,
			't_jabatan_str' => 0, 't_jabatan_fung' => 0, 't_jabatan_umum' => 0, 't_khusus' => 0,
			't_pangan' => 0, 't_pembulatan' => 0,
			'tpp' => 0, 'bruto' => 0,
			'pot_bpjs_kes' => 0, 'pot_bpjs_tpp_peg' => 0,
			'pot_pensiun' => 0, 'pot_pensiun_peg' => 0, 'pot_jht_taspen' => 0,
			'pot_jht' => 0, 'pot_jp' => 0,
			'pot_total' => 0, 'bersih' => 0,
			'bel_bpjs_gaji' => 0, 'bel_bpjs_tpp' => 0,
			'bel_pph21' => 0, 'bel_pph21_tpp' => 0,
			'bel_jkk' => 0, 'bel_jkm' => 0,
			'pensiun_count' => 0,
		);
		$totals = array('all' => $t0, 'pns' => $t0, 'pppk' => $t0);

		foreach ($pegawais as $p) {
			$h = $this->_hitung_gaji($p['id'], $bulan, $tahun, $is_ke, $ke_nama);
			if ( ! $h['ok']) continue;

			$jk = strtolower($p['jenis_kepegawaian']); // 'pns' or 'pppk'

			// Cek pensiun di bulan/tahun target
			if ($h['pegawai']['pensiun_di_target']) {
				foreach (array('all', $jk) as $tk) $totals[$tk]['pensiun_count']++;
				continue;
			}

			$rows[] = $h;

			foreach (array('all', $jk) as $tk) {
				$totals[$tk]['gaji_pokok']     += $h['komponen']['gaji_pokok'];
				$totals[$tk]['t_keluarga']     += $h['komponen']['t_istri'] + $h['komponen']['t_anak'];
				$totals[$tk]['t_jabatan_str']  += ($h['komponen']['t_jabatan_str'] ?? 0);
				$totals[$tk]['t_jabatan_fung'] += ($h['komponen']['t_jabatan_fung'] ?? 0);
				$totals[$tk]['t_jabatan_umum'] += ($h['komponen']['t_jabatan_umum'] ?? 0);
				$totals[$tk]['t_khusus']       += ($h['komponen']['t_khusus'] ?? 0);
				$totals[$tk]['t_pangan']       += $h['komponen']['t_pangan'];
				$totals[$tk]['t_pembulatan']  += ($h['komponen']['t_pembulatan'] ?? 0);
				$totals[$tk]['tpp']           += $h['komponen']['tpp'];
				$totals[$tk]['bruto']         += $h['bruto'];
				$totals[$tk]['pot_bpjs_kes']     += $h['iuran']['bpjs_kes_pegawai'];
				$totals[$tk]['pot_bpjs_tpp_peg'] += ($h['iuran']['bpjs_tpp_pegawai'] ?? 0);
				$totals[$tk]['pot_pensiun']      += $h['iuran']['pensiun_pegawai'] + ($h['iuran']['jht_taspen'] ?? 0);
				$totals[$tk]['pot_pensiun_peg']  += $h['iuran']['pensiun_pegawai'];
				$totals[$tk]['pot_jht_taspen']   += ($h['iuran']['jht_taspen'] ?? 0);
				$totals[$tk]['pot_jht']          += $h['iuran']['jht'];
				$totals[$tk]['pot_jp']           += $h['iuran']['jp'];
				$totals[$tk]['pot_total']        += $h['total_potong'];
				$totals[$tk]['bersih']           += $h['bersih'];
				$totals[$tk]['bel_bpjs_gaji']    += $h['belanja']['bpjs_kes_employer'];
				$totals[$tk]['bel_bpjs_tpp']     += $h['belanja']['bpjs_tpp'];
				$totals[$tk]['bel_pph21']        += $h['belanja']['pph21'];
				$totals[$tk]['bel_pph21_tpp']    += ($h['belanja']['pph21_tpp'] ?? 0);
				$totals[$tk]['bel_jkk']          += $h['belanja']['jkk'];
				$totals[$tk]['bel_jkm']          += $h['belanja']['jkm'];
			}
		}

		$this->_json(array(
			'ok'         => 1,
			'bulan'      => $bulan,
			'tahun'      => $tahun,
			'is_ke'      => $is_ke,
			'ke_nama'    => $ke_nama,
			'rows'       => $rows,
			'total'      => $totals['all'],
			'total_pns'  => $totals['pns'],
			'total_pppk' => $totals['pppk'],
			'jumlah'     => count($rows),
		));
	}

	// =================== ENGINE PERHITUNGAN GAJI ===================

	/**
	 * Hitung komponen gaji satu pegawai untuk bulan/tahun tertentu.
	 * Jika bulan/tahun NULL → pakai bulan saat ini (simulasi realtime).
	 * Mendukung proyeksi KGB (Kenaikan Gaji Berkala) otomatis dari tgl_pns.
	 */
	protected function _hitung_gaji($pegawai_id, $target_bulan = NULL, $target_tahun = NULL, $is_ke = 0, $ke_nama = '')
	{
		$now = new DateTime();
		if ($target_bulan === NULL) $target_bulan = (int) $now->format('n');
		if ($target_tahun === NULL) $target_tahun = (int) $now->format('Y');
		$target_date = new DateTime("{$target_tahun}-{$target_bulan}-01");

		$peg = $this->db
			->select('m.*, o.nama_opd, o.kode_opd, ou.nama_unit,
			          rjs.nama_jabatan AS jab_struktural, rjs.eselon,
			          rjf.nama_jabatan AS jab_fungsional,
			          rjp.nama_jabatan AS jab_penatausahaan', FALSE)
			->from('pegawai m')
			->join('master_opd o',       'o.id = m.opd_id', 'left')
			->join('master_opd_unit ou', 'ou.id = m.opd_unit_id', 'left')
			->join('ref_jabatan rjs', 'rjs.id = m.jabatan_struktural_id',    'left')
			->join('ref_jabatan rjf', 'rjf.id = m.jabatan_fungsional_id',    'left')
			->join('ref_jabatan rjp', 'rjp.id = m.jabatan_penatausahaan_id', 'left')
			->where('m.id', $pegawai_id)
			->get()->row_array();

		if ( ! $peg) return array('ok' => 0, 'msg' => 'Pegawai tidak ditemukan');

		$jenis         = $peg['jenis_kepegawaian'];
		$golongan      = $peg['golongan'] ?? '';
		$status_nik    = $peg['status_pernikahan'] ?? 'BELUM_KAWIN';
		$jml_anak      = (int) ($peg['jumlah_anak'] ?? 0);
		$terima_tk     = (int) ($peg['terima_tunjangan_keluarga'] ?? 1); // 0 = pasangan ASN lebih tinggi
		$eselon        = $peg['eselon'] ?? '';
		$ref_tpp_id    = (int) ($peg['ref_tpp_id'] ?? 0);
		$jenis_kelamin = $peg['jenis_kelamin'] ?? 'L';

		// ── MKG proyeksi KGB (Kenaikan Gaji Berkala tiap 2 tahun dari tgl_pns) ──────
		$mkg_base     = (int) ($peg['masa_kerja_golongan'] ?? 0);
		$mkg_projected = $mkg_base;
		$kgb_info      = '';
		if ( ! empty($peg['tgl_pns']))
		{
			$pns_date    = new DateTime($peg['tgl_pns']);
			$months_now    = max(0, (int) $pns_date->diff($now)->y * 12 + (int) $pns_date->diff($now)->m);
			$months_target = max(0, (int) $pns_date->diff($target_date)->y * 12 + (int) $pns_date->diff($target_date)->m);
			$kgb_now    = (int) floor($months_now / 24);
			$kgb_target = (int) floor($months_target / 24);
			$delta_kgb  = max(0, $kgb_target - $kgb_now);
			$mkg_projected = $mkg_base + ($delta_kgb * 2);
			if ($delta_kgb > 0) $kgb_info = '+'.($delta_kgb * 2).' (KGB proyeksi)';
		}
		elseif ( ! empty($peg['tmt_kgb']))
		{
			// Fallback: jika tgl_pns kosong, gunakan tmt_kgb yang disimpan manual
			$kgb_date = new DateTime($peg['tmt_kgb']);
			if ($target_date >= $kgb_date)
			{
				$mkg_projected = $mkg_base + 2;
				$kgb_info = '+2 (KGB dari TMT KGB)';
			}
		}

		// ── Cek pensiun di target ────────────────────────────────────────────────────
		$bup           = $this->_bup($eselon, $peg['kd_jabatan_fungsional'] ?? NULL);
		$pensiun_target = FALSE;
		$usia_target    = NULL;
		if ( ! empty($peg['tgl_lahir']))
		{
			$lahir_date   = new DateTime($peg['tgl_lahir']);
			$usia_target  = (int) $lahir_date->diff($target_date)->y;
			// Cek usia di akhir bulan agar pegawai lahir di pertengahan/akhir bulan
			// terdeteksi pensiun pada bulan ulang tahun BUP-nya, bukan bulan berikutnya.
			$bulan_akhir  = (clone $target_date)->modify('last day of this month');
			$pensiun_target = ((int) $lahir_date->diff($bulan_akhir)->y >= $bup);
		}

		// ── KPP — Kenaikan Pangkat Pengabdian (PP 11/2017 Ps.166) ───────────────────
		// PNS yang mencapai BUP di bulan target → gaji terakhir dihitung 1 pangkat di atas.
		// MKG di golongan baru = 0 (baru masuk pangkat).
		$kpp_aktif    = FALSE;
		$golongan_asli = $golongan;
		if ($pensiun_target && $jenis === 'PNS' && $golongan)
		{
			$pns_gol = array(
				'I/a','I/b','I/c','I/d',
				'II/a','II/b','II/c','II/d',
				'III/a','III/b','III/c','III/d',
				'IV/a','IV/b','IV/c','IV/d','IV/e',
			);
			$kpp_idx = array_search($golongan, $pns_gol);
			if ($kpp_idx !== FALSE && $kpp_idx < count($pns_gol) - 1)
			{
				$golongan  = $pns_gol[$kpp_idx + 1];
				$kpp_aktif = TRUE;
				// MKG tetap (tidak di-reset ke 0): KPP naik pangkat, bukan mengulang masa kerja.
			}
		}

		// ── 1. GAJI POKOK ────────────────────────────────────────────────────────────
		$gapok_row = NULL;
		if ($golongan && in_array($jenis, array('PNS','PPPK'), TRUE))
		{
			$gapok_row = $this->db
				->select('gaji_pokok, masa_kerja, pp_nomor, berlaku_mulai')
				->from('ref_gaji_pokok')
				->where('jenis', $jenis)
				->where('golongan', $golongan)
				->where('masa_kerja <=', $mkg_projected)
				->where('is_active', 1)
				->order_by('masa_kerja DESC, berlaku_mulai DESC')
				->limit(1)->get()->row_array();
		}
		$gaji_pokok  = $gapok_row ? (int) $gapok_row['gaji_pokok'] : 0;
		$persen_gaji = (int) ($peg['persen_gaji'] ?? 100);
		if ($persen_gaji !== 100 && $persen_gaji > 0) {
			$gaji_pokok = (int) round($gaji_pokok * $persen_gaji / 100);
		}

		// ── 2. TUNJANGAN KELUARGA ────────────────────────────────────────────────────
		// $terima_tk=0 → pasangan ASN dengan gapok lebih tinggi; tunjangan keluarga (istri + anak) ikut pasangan
		$t_istri = ($status_nik === 'KAWIN' && $terima_tk) ? (int) round($gaji_pokok * 0.10) : 0;
		// Anak kena T.Anak: max 2; jika KAWIN dan terima_tk=0 maka anak terdaftar di pasangan → 0
		$anak_kena = ($status_nik === 'KAWIN' && !$terima_tk) ? 0 : min($jml_anak, 2);
		$t_anak    = (int) round($gaji_pokok * 0.02 * $anak_kena);

		// ── 3. TUNJANGAN JABATAN ─────────────────────────────────────────────────────
		$t_jabatan = 0; $t_jabatan_nama = ''; $t_jabatan_type = '';
		if ($eselon && $peg['jabatan_struktural_id'])
		{
			$eselon_kode = 'ES_' . strtoupper(str_replace(array('-','.'),'',preg_replace('/\s+/','',$eselon)));
			$tj = $this->db->select('nominal, nama')->from('ref_tunjangan_jabatan')
				->where('jenis', 'STRUKTURAL')->where('kode', $eselon_kode)->where('is_active', 1)
				->order_by('berlaku_mulai DESC')->limit(1)->get()->row_array();
			if ($tj) { $t_jabatan = (int) $tj['nominal']; $t_jabatan_nama = $tj['nama']; $t_jabatan_type = 'Struktural'; }
		}
		if ( ! $t_jabatan && ! empty($peg['kd_jabatan_fungsional']))
		{
			$tj = $this->db->select('nominal, nama_jabatan')->from('ref_tunjangan_fungsional')
				->where('kdjabatan', $peg['kd_jabatan_fungsional'])->where('is_active', 1)
				->limit(1)->get()->row_array();
			if ($tj) { $t_jabatan = (int) $tj['nominal']; $t_jabatan_nama = $tj['nama_jabatan']; $t_jabatan_type = 'Fungsional'; }
		}
		if ( ! $t_jabatan && $peg['jabatan_fungsional_id'] && $peg['jab_fungsional'])
		{
			$tj = $this->db->select('nominal, nama')->from('ref_tunjangan_jabatan')
				->where('jenis', 'FUNGSIONAL')->like('nama', $peg['jab_fungsional'])->where('is_active', 1)
				->order_by('berlaku_mulai DESC')->limit(1)->get()->row_array();
			if ($tj) { $t_jabatan = (int) $tj['nominal']; $t_jabatan_nama = $tj['nama']; $t_jabatan_type = 'Fungsional'; }
		}
		if ( ! $t_jabatan && $jenis !== 'NON_ASN' && $golongan)
		{
			$gol_group = strtoupper(substr($golongan, 0, strpos($golongan, '/')));
			if ($gol_group) {
				$tj = $this->db->select('nominal, nama')->from('ref_tunjangan_jabatan')
					->where('jenis', 'UMUM')->where('kode', 'UMUM_'.$gol_group)->where('is_active', 1)
					->order_by('berlaku_mulai DESC')->limit(1)->get()->row_array();
				if ($tj) { $t_jabatan = (int) $tj['nominal']; $t_jabatan_nama = $tj['nama']; $t_jabatan_type = 'Umum'; }
			}
		}

		// ── 3b. TUNJANGAN KHUSUS ─────────────────────────────────────────────────────
		$t_khusus = 0; $t_khusus_nama = '';
		if ( ! empty($peg['kd_tunjangan_khusus']))
		{
			$tk = $this->db->select('nominal, nama_jabatan')->from('ref_tunjangan_khusus')
				->where('kdjabatan', $peg['kd_tunjangan_khusus'])->where('is_active', 1)
				->limit(1)->get()->row_array();
			if ($tk) { $t_khusus = (int) $tk['nominal']; $t_khusus_nama = $tk['nama_jabatan']; }
		}

		// ── 4. TUNJANGAN PANGAN ──────────────────────────────────────────────────────
		// 10 kg per jiwa per bulan × harga_per_kg
		// Jiwa: diri sendiri (1) + pasangan jika terima_tk=1 (1) + anak_kena
		// $anak_kena sudah = 0 jika terima_tk=0 (KAWIN, tunjangan di pasangan), sehingga jiwa_pangan = 1
		$jiwa_pangan = 1 + ($status_nik === 'KAWIN' && $terima_tk ? 1 : 0) + $anak_kena;
		$beras_row   = $this->db->select('harga_per_kg')->from('ref_harga_beras')
			->order_by('berlaku_mulai DESC')->limit(1)->get()->row_array();
		$harga_per_kg = $beras_row ? (int) $beras_row['harga_per_kg'] : 0;
		$t_pangan     = $jiwa_pangan * 10 * $harga_per_kg;

		// ── 5. TPP dari ref_tpp (direct lookup via ref_tpp_id) ──────────────────────
		$tpp = 0; $tpp_perbup = ''; $tpp_uraian = ''; $kelas_jab = 0; $kelas_nama = '';
		if ($ref_tpp_id > 0)
		{
			$tpp_row = $this->db
				->select('rt.nominal, rt.perbup, rt.uraian, rkj.kelas, rkj.nama AS kelas_nama_str', FALSE)
				->from('ref_tpp rt')
				->join('ref_kelas_jabatan rkj', 'rkj.id = rt.kelas_jabatan_id', 'left')
				->where('rt.id', $ref_tpp_id)->where('rt.is_active', 1)
				->limit(1)->get()->row_array();
			if ($tpp_row) {
				$tpp        = (int) $tpp_row['nominal'];
				$tpp_perbup = $tpp_row['perbup'];
				$tpp_uraian = $tpp_row['uraian'];
				$kelas_jab  = (int) ($tpp_row['kelas'] ?? 0);
				$kelas_nama = $tpp_row['kelas_nama_str'] ?? '';
			}
		}

		// ── 7. BRUTO PENGHASILAN (pembulatan dihitung di 9b setelah potongan diketahui) ──
		// Sementara tanpa pembulatan; diupdate di bawah setelah t_pembulatan dihitung
		$bruto_tanpa_bulat = $gaji_pokok + $t_istri + $t_anak + $t_jabatan + $t_khusus + $t_pangan + $tpp;

		// ── 8. IURAN / POTONGAN PEGAWAI ─────────────────────────────────────────────
		// Dasar IWP pensiun/Taspen: gapok + t_keluarga saja (PP 25/1981)
		$dasar_iuran = $gaji_pokok + $t_istri + $t_anak;
		// Dasar BPJS Kes: gapok + t_keluarga + t_jabatan (Perpres 82/2018 & perubahannya)
		$dasar_bpjs  = $gaji_pokok + $t_istri + $t_anak + $t_jabatan;

		// BPJS Kes: 1% pegawai dari dasar BPJS (semua ASN)
		$bpjs_kes_pegawai = ($jenis !== 'NON_ASN') ? (int) round($dasar_bpjs * 0.01) : 0;

		// Taspen total PNS: 8% (4.75% pensiun + 3.25% JHT) — dihitung sekali agar tidak ada selisih rounding
		$taspen_total    = ($jenis === 'PNS') ? (int) round($dasar_iuran * 0.08)   : 0;
		$pensiun_pegawai = ($jenis === 'PNS') ? (int) round($dasar_iuran * 0.0475) : 0;
		$jht_taspen      = ($jenis === 'PNS') ? $taspen_total - $pensiun_pegawai    : 0;

		// BPJS TK (JHT + JP) untuk PPPK
		$jht = ($jenis === 'PPPK') ? (int) round($gaji_pokok * 0.02) : 0;
		$jp  = ($jenis === 'PPPK') ? (int) round($gaji_pokok * 0.01) : 0;

		$total_potong_pegawai = $bpjs_kes_pegawai + $pensiun_pegawai + $jht_taspen + $jht + $jp;

		// ── 9. BELANJA PEMERINTAH (tidak dipotong dari gaji) ─────────────────────────
		// BPJS Kes employer: 4% dari dasar BPJS (terpisah dari bagian pegawai)
		$bpjs_kes_employer = ($jenis !== 'NON_ASN') ? (int) round($dasar_bpjs * 0.04) : 0;

		// BPJS Kes dari TPP: 4% dari TPP (terpisah, rekening berbeda)
		$bpjs_tpp = ($tpp > 0 && $jenis !== 'NON_ASN') ? (int) round($tpp * 0.04) : 0;

		// JKK 0.24% dari gapok saja (Taspen/BPJS TK, PP 70/2015)
		$jkk = ($jenis !== 'NON_ASN') ? (int) round($gaji_pokok * 0.0024) : 0;
		// JKM: PNS = 0.72% Taspen (PP 25/1981), PPPK = 0.30% BPJS TK — base gapok saja
		$jkm = ($jenis === 'PNS')
			? (int) round($gaji_pokok * 0.0072)
			: (($jenis === 'PPPK') ? (int) round($gaji_pokok * 0.0030) : 0);

		// PPh 21 gaji — DTP, dihitung dengan metode TER (PMK 168/2023), tanpa TPP
		$pph21 = $this->_hitung_pph21($gaji_pokok, $t_istri, $t_anak, $t_jabatan, $t_pangan, $t_khusus, $status_nik, $jml_anak, $jenis_kelamin);

		// PPh 21 TPP — DTP, tarif flat: 5% Gol I-III, 15% Gol IV (PP 80/2010)
		$pph21_tpp = 0;
		if ($tpp > 0 && $jenis !== 'NON_ASN') {
			$rate_tpp  = (strncmp($golongan, 'IV', 2) === 0) ? 0.15 : 0.05;
			$pph21_tpp = (int) round($tpp * $rate_tpp);
		}

		// ── 9b. TUNJANGAN PEMBULATAN ─────────────────────────────────────────────────
		// Bersih gaji kasar (tanpa pembulatan, tanpa TPP, tanpa item belanja pemerintah)
		$raw_bersih_gaji = ($gaji_pokok + $t_istri + $t_anak + $t_jabatan + $t_khusus + $t_pangan) - $total_potong_pegawai;
		// Bulatkan ke ATAS ke kelipatan 100 (pembulatan tidak pernah memotong gaji)
		$rem_gaji      = (int)$raw_bersih_gaji % 100;
		$t_pembulatan  = $rem_gaji > 0 ? (100 - $rem_gaji) : 0;

		// BPJS Kes TPP: 1% pegawai dipotong dari TPP, 4% (bpjs_tpp) sudah dihitung di atas
		$bpjs_tpp_pegawai = ($tpp > 0 && $jenis !== 'NON_ASN') ? (int) round($tpp * 0.01) : 0;
		$total_potong_pegawai += $bpjs_tpp_pegawai;
		$bruto = $bruto_tanpa_bulat + $t_pembulatan;

		// ── GAJI KE-13/14: per PP 14/2024 — komponen Gapok+TKel+TJab+TPangan saja ──────
		// Tidak ada pembulatan; tidak ada potongan BPJS/pensiun dari gaji;
		// PPh21 metode marginal: TER(bruto_reguler + bruto_ke13) × total − PPh21_reguler.
		// TPP ke-13/14 sama seperti reguler (1% BPJS peg, 4% employer + pajak DTP).
		if ($is_ke > 0) {
			$t_pembulatan        = 0;
			$bpjs_kes_pegawai    = 0;
			$pensiun_pegawai     = 0;
			$jht_taspen          = 0;
			$jht                 = 0;
			$jp                  = 0;
			$bpjs_kes_employer   = 0;
			$jkk                 = 0;
			$jkm                 = 0;

			// Metode marginal PPh21 ke-13: selisih TER gabungan dengan PPh21 reguler
			$pph21_reguler  = $pph21;
			$bruto_regular  = $gaji_pokok + $t_istri + $t_anak + $t_jabatan + $t_khusus + $t_pangan;
			$bruto_ke13     = $gaji_pokok + $t_istri + $t_anak + $t_jabatan + $t_pangan;
			$bruto_gabungan = $bruto_regular + $bruto_ke13;
			if ($status_nik === 'KAWIN' && $jenis_kelamin === 'L') {
				if ($jml_anak >= 3)     $ter_kat = 'C';
				elseif ($jml_anak >= 1) $ter_kat = 'B';
				else                    $ter_kat = 'A';
			} else {
				$ter_kat = ($jml_anak >= 2) ? 'B' : 'A';
			}
			$pph21 = max(0, (int) round($bruto_gabungan * $this->_ter_rate($ter_kat, $bruto_gabungan)) - $pph21_reguler);

			$total_potong_pegawai = $bpjs_tpp_pegawai;
			$bruto = $bruto_ke13 + $tpp;
		}

		// ── 10. MARKER KARIR ─────────────────────────────────────────────────────────
		$usia_sekarang = NULL; $sisa_bup = NULL; $hari_kp = NULL;
		if ( ! empty($peg['tgl_lahir']))
		{
			$lahir = new DateTime($peg['tgl_lahir']);
			$usia_sekarang = (int) $lahir->diff($now)->y;
			$sisa_bup      = max(0, $bup - $usia_sekarang);
		}
		if ( ! empty($peg['tmt_kenaikan_pangkat']))
		{
			$kp_date = new DateTime($peg['tmt_kenaikan_pangkat']);
			$hari_kp = (int) $now->diff($kp_date)->days * ($kp_date > $now ? 1 : -1);
		}
		// KGB berikutnya
		$kgb_berikutnya = NULL;
		if ( ! empty($peg['tgl_pns']))
		{
			$pns_d = new DateTime($peg['tgl_pns']);
			$bulan_sejak_pns = (int) $pns_d->diff($now)->y * 12 + (int) $pns_d->diff($now)->m;
			$siklus_selanjutnya = (floor($bulan_sejak_pns / 24) + 1) * 24;
			$kgb_berikutnya = (clone $pns_d)->modify("+{$siklus_selanjutnya} months")->format('Y-m-d');
		}
		elseif ( ! empty($peg['tmt_kgb']))
		{
			$kgb_berikutnya = $peg['tmt_kgb'];
		}

		$rek_sfx = ($jenis === 'PNS') ? '.00001' : '.00002';

		return array(
			'ok'      => 1,
			'pegawai' => array(
				'id'              => $peg['id'],
				'nama'            => $peg['nama_lengkap'],
				'nip'             => $peg['nip'],
				'jenis'           => $jenis,
				'golongan'        => $golongan,
				'pangkat'         => $peg['pangkat'],
				'mkg'             => $mkg_projected,
				'mkg_base'        => $mkg_base,
				'kgb_info'        => $kgb_info,
				'kgb_berikutnya'  => $kgb_berikutnya,
				'gapok_mkg'       => $gapok_row ? (int) $gapok_row['masa_kerja'] : 0,
				'gapok_pp'        => $gapok_row ? $gapok_row['pp_nomor'] : '-',
				'opd'             => $peg['nama_opd'],
				'kode_opd'        => $peg['kode_opd'],
				'unit'            => $peg['nama_unit'],
				'jab_struktural'  => $peg['jab_struktural'],
				'eselon'          => $eselon,
				'jab_fungsional'  => $peg['jab_fungsional'],
				'jab_penatausahaan' => $peg['jab_penatausahaan'],
				'status_pernikahan'      => $status_nik,
				'terima_tk'              => $terima_tk,
				'jumlah_anak'            => $jml_anak,
				'anak_kena'              => $anak_kena,
				'jiwa_pangan'            => $jiwa_pangan,
				'kelas_jabatan'   => $kelas_jab,
				'kelas_nama'      => $kelas_nama,
				'ref_tpp_id'      => $ref_tpp_id,
				'tpp_uraian'      => $tpp_uraian,
				'persen_gaji'     => $persen_gaji,
				'tgl_lahir'       => $peg['tgl_lahir'],
				'usia'            => $usia_sekarang,
				'usia_target'     => $usia_target,
				'bup'             => $bup,
				'sisa_bup'        => $sisa_bup,
				'pensiun_di_target' => $pensiun_target,
				'kpp_aktif'       => $kpp_aktif,
				'golongan_asli'   => $golongan_asli,
				'tmt_kp'          => $peg['tmt_kenaikan_pangkat'],
				'hari_kp'         => $hari_kp,
				'target_bulan'    => $target_bulan,
				'target_tahun'    => $target_tahun,
				'is_ke'           => $is_ke,
				'ke_nama'         => $ke_nama,
			),
			'komponen' => array(
				'gaji_pokok'       => $gaji_pokok,
				't_istri'          => $t_istri,
				't_anak'           => $t_anak,
				't_jabatan'        => $t_jabatan,
				't_jabatan_type'   => $t_jabatan_type,
				't_jabatan_str'    => ($t_jabatan_type === 'Struktural') ? $t_jabatan : 0,
				't_jabatan_fung'   => ($t_jabatan_type === 'Fungsional') ? $t_jabatan : 0,
				't_jabatan_umum'   => ($t_jabatan_type === 'Umum')       ? $t_jabatan : 0,
				't_khusus'         => $t_khusus,
				't_pangan'         => $t_pangan,
				't_pembulatan'     => $t_pembulatan,
				'tpp'              => $tpp,
			),
			'penghasilan' => array_values(array_filter(array(
				array('rekening' => '5.1.01.01.001'.$rek_sfx,
					'label' => 'Gaji Pokok (Gol.'.$golongan.($kpp_aktif?' [KPP dari '.$golongan_asli.']':'').', MKG'.($gapok_row?$gapok_row['masa_kerja']:0).')'.($persen_gaji!==100?' ['.$persen_gaji.'%]':''),
					'nominal' => $gaji_pokok,
					'catatan' => ($gapok_row ? $gapok_row['pp_nomor'] : 'Data gaji pokok belum tersedia')
						.($kpp_aktif ? ' · KPP: pangkat naik '.$golongan_asli.' → '.$golongan.' (PP 11/2017 Ps.166)' : '')
						.($kgb_info?' · '.$kgb_info:'')
						.($persen_gaji!==100?' · '.$persen_gaji.'% (CPNS/Hudis)':'')),
				array('rekening' => '5.1.01.01.002'.$rek_sfx, 'label' => 'Tunjangan Suami/Istri (10%)',
					'nominal' => $t_istri,
					'catatan' => $status_nik !== 'KAWIN' ? '—' : ($terima_tk ? '' : 'Tidak diklaim — pasangan ASN dengan gapok lebih tinggi')),
				array('rekening' => '5.1.01.01.002'.$rek_sfx, 'label' => 'Tunjangan Anak ('.$anak_kena.' anak × 2%)',
					'nominal' => $t_anak, 'catatan' => $anak_kena > 0 ? '' : '—'),
				array('rekening' => ($t_jabatan_type === 'Fungsional' ? '5.1.01.01.004' : ($t_jabatan_type === 'Umum' ? '5.1.01.01.005' : '5.1.01.01.003')).$rek_sfx,
					'label' => ($t_jabatan_nama ?: 'Tunjangan Jabatan').($t_jabatan_type?' ('.$t_jabatan_type.')':''),
					'nominal' => $t_jabatan, 'catatan' => $t_jabatan ? '' : 'Tidak ada data tunjangan jabatan'),
				$t_khusus ? array('rekening' => '5.1.01.01.007'.$rek_sfx, 'label' => 'Tunjangan Khusus'.($t_khusus_nama?' — '.$t_khusus_nama:''),
					'nominal' => $t_khusus, 'catatan' => '') : NULL,
				array('rekening' => '5.1.01.01.006'.$rek_sfx, 'label' => 'Tunjangan Pangan ('.$jiwa_pangan.' jiwa × 10 kg × '.number_format($harga_per_kg,0,',','.').')',
					'nominal' => $t_pangan, 'catatan' => $harga_per_kg ? 'Rp '.number_format($harga_per_kg,0,',','.').' / kg' : 'Harga belum diisi'),
				$t_pembulatan > 0 ? array('rekening' => '5.1.01.01.008'.$rek_sfx, 'label' => 'Tunjangan Pembulatan',
					'nominal' => $t_pembulatan, 'catatan' => 'Selisih pembulatan bersih ke kelipatan Rp 100') : NULL,
				array('rekening' => '5.1.01.02.001'.$rek_sfx, 'label' => 'Tambahan Penghasilan Pegawai (TPP)'.($tpp_uraian?' — '.$tpp_uraian:''),
					'nominal' => $tpp, 'catatan' => $tpp_perbup ? 'Dasar: '.$tpp_perbup : ($ref_tpp_id ? 'Data TPP tidak ditemukan' : 'Kategori TPP belum di-set')),
			))),
			'bruto'   => $bruto,
			'iuran'   => array(
				'bpjs_kes_pegawai'  => $bpjs_kes_pegawai,
				'pensiun_pegawai'   => $pensiun_pegawai,
				'jht_taspen'        => $jht_taspen,
				'jht'               => $jht,
				'jp'                => $jp,
				'bpjs_tpp_pegawai'  => $bpjs_tpp_pegawai,
			),
			'potongan' => array_values(array_filter(array(
				$bpjs_kes_pegawai  ? array('rekening' => '5.1.01.01.009'.$rek_sfx, 'label' => 'BPJS Kesehatan Gaji (1%)',         'nominal' => $bpjs_kes_pegawai) : NULL,
				$pensiun_pegawai   ? array('rekening' => '5.1.01.01.013'.$rek_sfx, 'label' => 'Taspen — Iuran Pensiun (4,75%)',   'nominal' => $pensiun_pegawai)  : NULL,
				$jht_taspen        ? array('rekening' => '5.1.01.01.013'.$rek_sfx, 'label' => 'Taspen — JHT (3,25%)',             'nominal' => $jht_taspen)       : NULL,
				$jht               ? array('rekening' => '5.1.01.01.013'.$rek_sfx, 'label' => 'BPJS TK — JHT (2%)',              'nominal' => $jht)              : NULL,
				$jp                ? array('rekening' => '5.1.01.01.013'.$rek_sfx, 'label' => 'BPJS TK — JP (1%)',               'nominal' => $jp)               : NULL,
				$bpjs_tpp_pegawai  ? array('rekening' => '5.1.01.01.009'.$rek_sfx, 'label' => 'BPJS Kesehatan TPP (1%)',         'nominal' => $bpjs_tpp_pegawai) : NULL,
			))),
			'total_potong' => $total_potong_pegawai,
			'bersih'       => $bruto - $total_potong_pegawai,
			'belanja'      => array(
				'bpjs_kes_employer' => $bpjs_kes_employer,
				'bpjs_tpp'          => $bpjs_tpp,
				'jkk'               => $jkk,
				'jkm'               => $jkm,
				'pph21'             => $pph21,
				'pph21_tpp'         => $pph21_tpp,
			),
			'harga_per_kg' => $harga_per_kg,
		);
	}

	/**
	 * PPh 21 bulanan — metode Tarif Efektif Rata-Rata (TER) sesuai PMK 168/2023.
	 * DTP (Ditanggung Pemerintah). Tidak memasukkan TPP (dihitung terpisah).
	 */
	protected function _hitung_pph21($gapok, $t_istri, $t_anak, $t_jabatan, $t_pangan, $t_khusus, $status_nik, $jml_anak, $jenis_kelamin)
	{
		if ($gapok <= 0) return 0;

		$bruto = $gapok + $t_istri + $t_anak + $t_jabatan + $t_khusus + $t_pangan;
		if ($bruto <= 0) return 0;

		// Penentuan kategori TER (PMK 168/2023 / PP 58/2023)
		// Perempuan KAWIN: K/0 (suami klaim tanggungan) → Kategori A
		// Laki-laki KAWIN K/1-K/2 → Kategori B; K/3 → Kategori C
		// TK/2-TK/3 (janda/duda/belum kawin ≥2 anak) → Kategori B
		if ($status_nik === 'KAWIN' && $jenis_kelamin === 'L') {
			if ($jml_anak >= 3)     $kategori = 'C';
			elseif ($jml_anak >= 1) $kategori = 'B';
			else                    $kategori = 'A';
		} else {
			// Perempuan KAWIN, BELUM_KAWIN, JANDA, DUDA
			$kategori = ($jml_anak >= 2) ? 'B' : 'A';
		}

		return (int) round($bruto * $this->_ter_rate($kategori, $bruto));
	}

	/** Tarif Efektif Rata-Rata (TER) dari PP 58/2023 / PMK 168/2023. */
	protected function _ter_rate($kategori, $bruto)
	{
		if ($kategori === 'A') {
			if ($bruto <=  5400000) return 0.0000;
			if ($bruto <=  5650000) return 0.0025;
			if ($bruto <=  5950000) return 0.0050;
			if ($bruto <=  6300000) return 0.0075;
			if ($bruto <=  6750000) return 0.0100;
			if ($bruto <=  7500000) return 0.0125;
			if ($bruto <=  8550000) return 0.0150;
			if ($bruto <=  9650000) return 0.0175;
			if ($bruto <= 10050000) return 0.0200;
			if ($bruto <= 10350000) return 0.0225;
			if ($bruto <= 10700000) return 0.0250;
			if ($bruto <= 11050000) return 0.0300;
			if ($bruto <= 11600000) return 0.0350;
			if ($bruto <= 12500000) return 0.0400;
			if ($bruto <= 13750000) return 0.0500;
			if ($bruto <= 15100000) return 0.0600;
			if ($bruto <= 16950000) return 0.0700;
			if ($bruto <= 19750000) return 0.0800;
			if ($bruto <= 24150000) return 0.0900;
			if ($bruto <= 26450000) return 0.1000;
			if ($bruto <= 28000000) return 0.1100;
			if ($bruto <= 30050000) return 0.1200;
			return 0.1300;
		}
		if ($kategori === 'B') {
			if ($bruto <=  6200000) return 0.0000;
			if ($bruto <=  6500000) return 0.0025;
			if ($bruto <=  6850000) return 0.0050;
			if ($bruto <=  7300000) return 0.0075;
			if ($bruto <=  9200000) return 0.0100;
			if ($bruto <= 10750000) return 0.0150;
			if ($bruto <= 11250000) return 0.0200;
			if ($bruto <= 11600000) return 0.0250;
			if ($bruto <= 12600000) return 0.0300;
			if ($bruto <= 13600000) return 0.0400;
			if ($bruto <= 14950000) return 0.0500;
			if ($bruto <= 16400000) return 0.0600;
			if ($bruto <= 18450000) return 0.0700;
			if ($bruto <= 21850000) return 0.0800;
			if ($bruto <= 26000000) return 0.0900;
			if ($bruto <= 27700000) return 0.1000;
			if ($bruto <= 29350000) return 0.1100;
			return 0.1200;
		}
		// Kategori C (K/3)
		if ($bruto <=  6600000) return 0.0000;
		if ($bruto <=  6950000) return 0.0025;
		if ($bruto <=  7350000) return 0.0050;
		if ($bruto <=  7800000) return 0.0075;
		if ($bruto <=  8850000) return 0.0100;
		if ($bruto <=  9800000) return 0.0125;
		if ($bruto <= 10950000) return 0.0175;
		if ($bruto <= 11200000) return 0.0200;
		if ($bruto <= 12050000) return 0.0200;
		if ($bruto <= 12950000) return 0.0300;
		if ($bruto <= 14150000) return 0.0400;
		if ($bruto <= 15550000) return 0.0500;
		if ($bruto <= 17050000) return 0.0600;
		if ($bruto <= 19500000) return 0.0700;
		if ($bruto <= 22700000) return 0.0800;
		if ($bruto <= 26600000) return 0.0900;
		if ($bruto <= 28100000) return 0.1000;
		if ($bruto <= 30100000) return 0.1100;
		return 0.1200;
	}

	/** Batas Usia Pensiun berdasarkan eselon / kode jabatan fungsional. */
	protected function _bup($eselon, $kd_jabatan_fungsional = NULL)
	{
		$es = strtoupper(str_replace(array(' ','-','.'),'', $eselon));
		if (in_array($es, array('1A','1B','IA','IB'), TRUE))   return 65;
		if (in_array($es, array('2A','2B','IIA','IIB'), TRUE)) return 60;
		if ($kd_jabatan_fungsional)
		{
			$r = $this->db->select('bup_usia')->from('ref_tunjangan_fungsional')
				->where('kdjabatan', $kd_jabatan_fungsional)->where('is_active', 1)
				->limit(1)->get()->row_array();
			if ($r) return (int) $r['bup_usia'];
		}
		return 58;
	}

	protected function _json($data)
	{
		$this->output->set_content_type('application/json')->set_output(json_encode($data));
	}
}
