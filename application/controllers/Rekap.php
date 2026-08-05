<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'controllers/Gaji.php';

/**
 * Rekap Gaji — perhitungan range bulan, pisah PNS/PPPK, pisah Gaji/TPP.
 *
 * Extends Gaji untuk mewarisi _hitung_gaji(), _hitung_pph21(), _bup().
 *
 * Pajak TPP:
 *   PNS Gol IV (IV/a–IV/d) → 15%;  semua lain (termasuk PPPK) → 5%.
 * BPJS & PPh dari rekening gaji (bukan rekening TPP).
 * Gaji ke-13: bulan_basis dari ref_gaji_ke; komponen sama dengan gaji reguler.
 * Gaji ke-14: bulan_basis dari ref_gaji_ke; komponen sama dengan gaji reguler.
 */
class Rekap extends Gaji {

	private static $BULAN = ['','Januari','Februari','Maret','April','Mei','Juni',
		'Juli','Agustus','September','Oktober','November','Desember'];

	// ─────────────────────────────────────────────────────── HALAMAN UTAMA ──

	public function index()
	{
		$data = $this->_base_data();

		if ($this->input->method() === 'post')
		{
			$params = $this->_params_from_post();
			if ($params['ok'])
			{
				$data['params'] = $params;
				$data['result'] = $this->_rekap_range(
					$params['opd_id'], $params['tahun'],
					$params['bm'],     $params['ba'],
					$params['jenis_filter'], $params['include_ke']
				);
			}
			else
			{
				$data['error'] = $params['msg'];
			}
		}

		$this->render('rekap/index', $data, 'Rekap Gaji Tahunan');
	}

	// ──────────────────────────────────────────────── DETAIL PER PEGAWAI ──

	public function detail($peg_id, $tahun, $bm = 1, $ba = 12)
	{
		$peg_id = (int) $peg_id;
		$tahun  = (int) $tahun;
		$bm     = max(1,  min(12, (int) $bm));
		$ba     = max($bm, min(12, (int) $ba));

		// Dapatkan referensi gaji ke
		$ke_rows = $this->db->select('no,nama,bulan_basis')
			->from('ref_gaji_ke')->where('is_active', 1)->get()->result_array();
		$ke_map = []; // bulan_basis => [{no, nama}]
		foreach ($ke_rows as $ke) {
			$ke_map[(int)$ke['bulan_basis']][] = $ke;
		}

		$months = [];
		for ($b = $bm; $b <= $ba; $b++) {
			$months[] = ['bulan' => $b, 'tahun' => $tahun, 'is_ke' => 0, 'ke_nama' => ''];
			if (isset($ke_map[$b])) {
				foreach ($ke_map[$b] as $ke) {
					$months[] = ['bulan' => $b, 'tahun' => $tahun, 'is_ke' => (int)$ke['no'], 'ke_nama' => $ke['nama']];
				}
			}
		}

		$detail_months = [];
		$peg_info = null;
		foreach ($months as $m) {
			$h = $this->_hitung_gaji($peg_id, $m['bulan'], $m['tahun'], $m['is_ke']);
			if (!$h['ok']) continue;
			if ($peg_info === null) $peg_info = $h['pegawai'];
			if ($h['pegawai']['pensiun_di_target']) {
				$detail_months[] = array_merge($m, ['pensiun' => TRUE]);
				continue;
			}
			$pajak_tpp    = $h['belanja']['pph21_tpp'] ?? 0;
			$bpjs_tpp_peg = $h['iuran']['bpjs_tpp_pegawai'] ?? 0;
			$detail_months[] = array_merge($m, [
				'pensiun'     => FALSE,
				'hitung'      => $h,
				'pajak_tpp'   => $pajak_tpp,   // DTP — untuk referensi
				'bpjs_tpp_peg'=> $bpjs_tpp_peg,
				'tpp_bersih'  => $h['komponen']['tpp'] - $bpjs_tpp_peg,
				'penghasilan' => $h['penghasilan'],
				'potongan'    => $h['potongan'],
				'belanja'     => $h['belanja'],
				'bersih_gaji' => $h['bersih'] - $h['komponen']['tpp'] + $bpjs_tpp_peg,
				'bersih_total'=> $h['bersih'],
			]);
		}

		if (!$peg_info) show_404();

		$data = array_merge($this->_base_data(), [
			'peg_info'      => $peg_info,
			'detail_months' => $detail_months,
			'tahun'         => $tahun,
			'bm'            => $bm,
			'ba'            => $ba,
		]);
		$this->render('rekap/detail', $data, 'Detail Gaji — '.$peg_info['nama']);
	}

	// ──────────────────────────────────────────── KALKULASI RANGE UTAMA ──

	private function _rekap_range($opd_id, $tahun, $bm, $ba, $jenis_filter, $include_ke)
	{
		// 1. Referensi gaji ke
		$ke_rows = $this->db->select('no,nama,bulan_basis')
			->from('ref_gaji_ke')->where('is_active', 1)->get()->result_array();
		$ke_map = [];
		foreach ($ke_rows as $ke) {
			$ke_map[(int)$ke['bulan_basis']][] = $ke;
		}

		// 2. Bangun daftar periode (termasuk gaji ke-13/14)
		$periods = [];
		for ($b = $bm; $b <= $ba; $b++) {
			$periods[] = ['bulan' => $b, 'tahun' => $tahun, 'is_ke' => 0, 'ke_nama' => ''];
			if ($include_ke && isset($ke_map[$b])) {
				foreach ($ke_map[$b] as $ke) {
					$periods[] = ['bulan' => $b, 'tahun' => $tahun, 'is_ke' => (int)$ke['no'], 'ke_nama' => $ke['nama']];
				}
			}
		}

		// 3. Ambil pegawai
		$q = $this->db->select('id, jenis_kepegawaian, golongan, nama_lengkap, nip')
			->from('pegawai')
			->where('is_active', 1)
			->where_in('jenis_kepegawaian', ['PNS','PPPK']);
		if ($opd_id) $q->where('opd_id', $opd_id);
		elseif (!is_super()) $q->where('opd_id', (int)scope_opd_id());
		if ($jenis_filter && $jenis_filter !== 'SEMUA') $q->where('jenis_kepegawaian', $jenis_filter);
		$pegawais = $this->db->get()->result_array();

		// 4. Loop periode × pegawai
		$months_data = [];
		$peg_agg     = []; // aggregate per pegawai across all months

		foreach ($periods as $p) {
			$row = [
				'bulan'    => $p['bulan'],
				'tahun'    => $p['tahun'],
				'label'    => self::$BULAN[$p['bulan']].' '.$p['tahun'].($p['is_ke'] ? ' — '.$p['ke_nama'] : ''),
				'is_ke'    => $p['is_ke'],
				'ke_nama'  => $p['ke_nama'],
				'pns'      => $this->_zero_totals(),
				'pppk'     => $this->_zero_totals(),
				'combined' => $this->_zero_totals(),
			];

			foreach ($pegawais as $peg) {
				$h = $this->_hitung_gaji($peg['id'], $p['bulan'], $p['tahun'], $p['is_ke']);
				if (!$h['ok'] || $h['pegawai']['pensiun_di_target']) continue;

				$jenis_key = strtolower($peg['jenis_kepegawaian']);

				$t = $this->_totals_from($h);
				$this->_add_totals($row[$jenis_key], $t);
				$this->_add_totals($row['combined'], $t);

				// Per-pegawai aggregate
				if (!isset($peg_agg[$peg['id']])) {
					$peg_agg[$peg['id']] = [
						'id'       => $peg['id'],
						'nama'     => $h['pegawai']['nama'],
						'nip'      => $peg['nip'],
						'jenis'    => $peg['jenis_kepegawaian'],
						'golongan' => $peg['golongan'],
						'eselon'   => $h['pegawai']['eselon'] ?? '',
						'kode_opd' => $h['pegawai']['kode_opd'] ?? '',
						'tgl_lahir'=> $h['pegawai']['tgl_lahir'] ?? '',
						'totals'   => $this->_zero_totals(),
					];
				}
				$this->_add_totals($peg_agg[$peg['id']]['totals'], $t);
			}

			$months_data[] = $row;
		}

		// 5. Grand totals
		$grand = ['pns' => $this->_zero_totals(), 'pppk' => $this->_zero_totals(), 'combined' => $this->_zero_totals()];
		foreach ($months_data as $md) {
			$this->_add_totals($grand['pns'],      $md['pns']);
			$this->_add_totals($grand['pppk'],     $md['pppk']);
			$this->_add_totals($grand['combined'], $md['combined']);
		}

		$peg_rows = array_values($peg_agg);
		$eselon_rank = ['2A'=>1,'2B'=>2,'3A'=>3,'3B'=>4,'4A'=>5,'4B'=>6];
		$gol_order   = array_flip(['I/a','I/b','I/c','I/d','II/a','II/b','II/c','II/d',
			'III/a','III/b','III/c','III/d','IV/a','IV/b','IV/c','IV/d','IV/e',
			'I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII','XIII','XIV','XV','XVI','XVII']);
		$jenis_rank = ['PNS'=>1,'PPPK'=>2,'NON_ASN'=>3];
		usort($peg_rows, function($a, $b) use ($eselon_rank, $gol_order, $jenis_rank) {
			// 1. kode_opd
			$c = strcmp($a['kode_opd'] ?? '', $b['kode_opd'] ?? '');
			if ($c) return $c;
			// 2. jenis kepegawaian: PNS → PPPK → NON_ASN
			$ja = $jenis_rank[strtoupper($a['jenis'] ?? '')] ?? 99;
			$jb = $jenis_rank[strtoupper($b['jenis'] ?? '')] ?? 99;
			if ($ja !== $jb) return $ja - $jb;
			// 3. eselon rank ASC (highest eselon = smallest rank number first)
			$ea = $eselon_rank[$a['eselon']] ?? 99;
			$eb = $eselon_rank[$b['eselon']] ?? 99;
			if ($ea !== $eb) return $ea - $eb;
			// 3. golongan rank DESC (highest golongan first)
			$ga = $gol_order[$a['golongan']] ?? -1;
			$gb = $gol_order[$b['golongan']] ?? -1;
			if ($ga !== $gb) return $gb - $ga;
			// 4. tgl_lahir ASC (oldest first = highest age)
			return strcmp($a['tgl_lahir'] ?? '9999', $b['tgl_lahir'] ?? '9999');
		});

		return [
			'months'   => $months_data,
			'grand'    => $grand,
			'peg_rows' => $peg_rows,
		];
	}

	// ──────────────────────────────────────────── HELPER INTERNAL ──

	private function _pajak_tpp($golongan, $tpp)
	{
		if ($tpp <= 0) return 0;
		$rate = (strpos($golongan, 'IV') === 0) ? 0.15 : 0.05;
		return (int) round($tpp * $rate);
	}

	private function _zero_totals()
	{
		return [
			'jml'           => 0,
			'gaji_pokok'    => 0,
			't_keluarga'    => 0,
			't_jabatan_str' => 0,
			't_jabatan_fung'=> 0,
			't_jabatan_umum'=> 0,
			't_khusus'      => 0,
			't_pangan'      => 0,
			't_pembulatan'  => 0,
			'bruto_gaji'    => 0,
			'pot_bpjs'      => 0,
			'pot_pensiun'   => 0,
			'pph21'         => 0,
			'bersih_gaji'   => 0,
			'tpp_bruto'     => 0,
			'pajak_tpp'     => 0,
			'bpjs_tpp_peg'  => 0,
			'tpp_bersih'    => 0,
			'total_bersih'  => 0,
			'bel_bpjs_gaji' => 0,
			'bel_jkk'       => 0,
			'bel_jkm'       => 0,
			'bel_tpp_bpjs'  => 0,
		];
	}

	private function _totals_from($h)
	{
		$k   = $h['komponen'];
		$iu  = $h['iuran'];
		$bel = $h['belanja'];
		$tpp          = $k['tpp'];
		$t_pembulatan = $k['t_pembulatan'] ?? 0;
		$bpjs_tpp_peg = $iu['bpjs_tpp_pegawai'] ?? 0;
		$pot_bpjs     = $iu['bpjs_kes_pegawai'];
		$pajak_tpp    = $bel['pph21_tpp'] ?? 0;
		$pot_pensiun  = $iu['pensiun_pegawai'] + ($iu['jht_taspen'] ?? 0) + $iu['jht'] + $iu['jp'];
		$bruto_gaji   = $k['gaji_pokok'] + $k['t_istri'] + $k['t_anak'] + $k['t_jabatan'] + $k['t_khusus'] + $k['t_pangan'] + $t_pembulatan;
		$pph21        = $bel['pph21'];
		$bersih_gaji  = $bruto_gaji - $pot_bpjs - $pot_pensiun;
		$tpp_bersih   = $tpp - $bpjs_tpp_peg;

		return [
			'jml'           => 1,
			'gaji_pokok'    => $k['gaji_pokok'],
			't_keluarga'    => $k['t_istri'] + $k['t_anak'],
			't_jabatan_str' => $k['t_jabatan_str']  ?? 0,
			't_jabatan_fung'=> $k['t_jabatan_fung'] ?? 0,
			't_jabatan_umum'=> $k['t_jabatan_umum'] ?? 0,
			't_khusus'      => $k['t_khusus']       ?? 0,
			't_pangan'      => $k['t_pangan'],
			't_pembulatan'  => $t_pembulatan,
			'bruto_gaji'    => $bruto_gaji,
			'pot_bpjs'      => $pot_bpjs,
			'pot_pensiun'   => $pot_pensiun,
			'pph21'         => $pph21,
			'bersih_gaji'   => $bersih_gaji,
			'tpp_bruto'     => $tpp,
			'pajak_tpp'     => $pajak_tpp,
			'bpjs_tpp_peg'  => $bpjs_tpp_peg,
			'tpp_bersih'    => $tpp_bersih,
			'total_bersih'  => $bersih_gaji + $tpp_bersih,
			'bel_bpjs_gaji' => $bel['bpjs_kes_employer'],
			'bel_jkk'       => $bel['jkk'],
			'bel_jkm'       => $bel['jkm'],
			'bel_tpp_bpjs'  => $bel['bpjs_tpp'],
		];
	}

	private function _add_totals(&$target, $src)
	{
		foreach ($src as $k => $v) {
			$target[$k] = ($target[$k] ?? 0) + $v;
		}
	}

	private function _base_data()
	{
		$is_super = is_super();
		$opd_list = [];
		if ($is_super) {
			$opds = $this->db->select('id, kode_opd, nama_opd')->from('master_opd')
				->where('is_active', 1)->order_by('kode_opd')->get()->result_array();
			foreach ($opds as $o) $opd_list[$o['id']] = $o['kode_opd'].' — '.$o['nama_opd'];
		}
		return [
			'is_super'    => $is_super,
			'opd_list'    => $opd_list,
			'default_opd' => is_super() ? 0 : (int)scope_opd_id(),
			'params'      => null,
			'result'      => null,
			'error'       => null,
		];
	}

	private function _params_from_post()
	{
		$tahun = (int) $this->input->post('tahun');
		$bm    = (int) $this->input->post('bulan_mulai');
		$ba    = (int) $this->input->post('bulan_akhir');
		if ($tahun < 2020 || $tahun > 2099) return ['ok' => 0, 'msg' => 'Tahun tidak valid'];
		if ($bm < 1 || $bm > 12 || $ba < 1 || $ba > 12 || $ba < $bm)
			return ['ok' => 0, 'msg' => 'Range bulan tidak valid'];

		$opd_id = (int) $this->input->post('opd_id');
		if (!is_super()) $opd_id = (int)scope_opd_id();
		$jenis_filter = $this->input->post('jenis_filter', TRUE);
		if (!in_array($jenis_filter, ['SEMUA','PNS','PPPK'], TRUE)) $jenis_filter = 'SEMUA';
		$include_ke = (bool) $this->input->post('include_ke');

		$opd_nama = '';
		if ($opd_id) {
			$o = $this->db->select('nama_opd, kode_opd')->from('master_opd')->where('id', $opd_id)->get()->row_array();
			if ($o) $opd_nama = $o['kode_opd'].' — '.$o['nama_opd'];
		}

		return [
			'ok' => 1, 'tahun' => $tahun, 'bm' => $bm, 'ba' => $ba,
			'opd_id' => $opd_id, 'opd_nama' => $opd_nama,
			'jenis_filter' => $jenis_filter, 'include_ke' => $include_ke,
		];
	}
}
