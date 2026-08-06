<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Anggaran (read-only Tahap 1): viewer DPA (raw SIPD) & Arus Kas.
 * Memakai ulang engine DataTables server-side dari Master_model + filter
 * bertingkat OPD -> Urusan -> Bidang -> Program -> Kegiatan -> Sub Kegiatan -> Rekening.
 */
class Anggaran extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Master_model', 'mm');
	}

	// ======================= DPA — Tree View =======================

	public function dpa()
	{
		$opd_opts = is_super()
			? $this->mm->options('master_opd', 'id', "CONCAT(kode_opd,' - ',nama_opd)", array(), 'kode_opd')
			: array();
		$this->render('anggaran/dpa', array(
			'opd_opts'  => $opd_opts,
			'is_super'  => is_super(),
			'my_opd_id' => is_super() ? '' : scope_opd_id(),
			'tree_url'  => site_url('anggaran/dpa_tree'),
		), 'DPA — Dokumen Pelaksanaan Anggaran');
	}

	public function dpa_tree()
	{
		$opd_id = is_super() ? $this->input->get('opd_id') : scope_opd_id();
		if ( ! $opd_id)
		{
			$this->json(array('opd' => NULL, 'programs' => array(), 'total' => 0));
			return;
		}

		$rows = $this->db
			->select('o.id AS opd_id, o.kode_opd, o.nama_opd, COALESCE(o.singkatan, o.nama_opd) AS opd_singkat,
			          p.id AS prog_id, p.kode_program, p.nama_program,
			          k.id AS keg_id, k.kode_kegiatan, k.nama_kegiatan,
			          sk.id AS subkeg_id, sk.kode_subkegiatan, sk.nama_subkegiatan,
			          r.id AS rek_id, r.kode_rekening, r.uraian AS rek_uraian,
			          dd.sumber_dana_id,
			          COALESCE(msd.nama, dd.sumber_dana_text) AS sumber_dana_text,
			          dd.paket_belanja, dd.koefisien, dd.harga_satuan, dd.total_harga', FALSE)
			->from('dpa d')
			->join('dpa_detail dd',         'dd.dpa_id = d.id')
			->join('master_opd o',           'o.id = d.opd_id')
			->join('master_program p',       'p.id = dd.program_id')
			->join('master_kegiatan k',      'k.id = dd.kegiatan_id')
			->join('master_subkegiatan sk',  'sk.id = dd.subkegiatan_id')
			->join('master_rekening r',      'r.id = dd.rekening_id')
			->join('master_sumber_dana msd', 'msd.id = dd.sumber_dana_id', 'left')
			->where('d.opd_id', (int) $opd_id)
			->order_by('p.kode_program, k.kode_kegiatan, sk.kode_subkegiatan, r.kode_rekening, dd.no_urut')
			->get()->result_array();

		if (empty($rows))
		{
			$this->json(array('opd' => NULL, 'programs' => array(), 'total' => 0));
			return;
		}

		$programs   = array();
		$opd_total  = 0;

		foreach ($rows as $row)
		{
			$pid  = $row['prog_id'];  $kid  = $row['keg_id'];
			$skid = $row['subkeg_id']; $rid  = $row['rek_id'];
			$amt  = (float) $row['total_harga'];

			if ( ! isset($programs[$pid]))
				$programs[$pid] = array('id'=>$pid,'kode'=>$row['kode_program'],'nama'=>$row['nama_program'],'total'=>0,'kegiatans'=>array());
			if ( ! isset($programs[$pid]['kegiatans'][$kid]))
				$programs[$pid]['kegiatans'][$kid] = array('id'=>$kid,'kode'=>$row['kode_kegiatan'],'nama'=>$row['nama_kegiatan'],'total'=>0,'subkegiatans'=>array());
			if ( ! isset($programs[$pid]['kegiatans'][$kid]['subkegiatans'][$skid]))
				$programs[$pid]['kegiatans'][$kid]['subkegiatans'][$skid] = array('id'=>$skid,'kode'=>$row['kode_subkegiatan'],'nama'=>$row['nama_subkegiatan'],'total'=>0,'rekenings'=>array());
			if ( ! isset($programs[$pid]['kegiatans'][$kid]['subkegiatans'][$skid]['rekenings'][$rid]))
				$programs[$pid]['kegiatans'][$kid]['subkegiatans'][$skid]['rekenings'][$rid] = array('id'=>$rid,'kode'=>$row['kode_rekening'],'nama'=>$row['rek_uraian'],'total'=>0,'items'=>array());

			$programs[$pid]['kegiatans'][$kid]['subkegiatans'][$skid]['rekenings'][$rid]['items'][] = array(
				'paket'  => $row['paket_belanja'],
				'koef'   => $row['koefisien'],
				'harga'  => (float) $row['harga_satuan'],
				'total'  => $amt,
				'sd_id'  => $row['sumber_dana_id'],
				'sd'     => $row['sumber_dana_text'],
			);
			$programs[$pid]['kegiatans'][$kid]['subkegiatans'][$skid]['rekenings'][$rid]['total'] += $amt;
			$programs[$pid]['kegiatans'][$kid]['subkegiatans'][$skid]['total']                    += $amt;
			$programs[$pid]['kegiatans'][$kid]['total']                                            += $amt;
			$programs[$pid]['total']                                                               += $amt;
			$opd_total                                                                             += $amt;
		}

		// Re-index arrays (preserve order)
		$out = array();
		foreach ($programs as $p)
		{
			$out_k = array();
			foreach ($p['kegiatans'] as $k)
			{
				$out_sk = array();
				foreach ($k['subkegiatans'] as $sk)
				{
					$sk['rekenings'] = array_values($sk['rekenings']);
					$out_sk[] = $sk;
				}
				$k['subkegiatans'] = $out_sk;
				$out_k[] = $k;
			}
			$p['kegiatans'] = $out_k;
			$out[] = $p;
		}

		$r0 = $rows[0];
		$this->json(array(
			'opd' => array(
				'id'     => $r0['opd_id'],
				'kode'   => $r0['kode_opd'],
				'nama'   => $r0['nama_opd'],
				'singkat'=> $r0['opd_singkat'],
				'total'  => $opd_total,
			),
			'programs' => $out,
			'total'    => $opd_total,
		));
	}

	public function dpa_rekening_options()
	{
		$this->json_out($this->rekening_options('dd'));
	}

	// ======================= REALISASI ANGGARAN (LRA) =======================

	public function realisasi()
	{
		$opd_opts = is_super()
			? $this->mm->options('master_opd', 'id', "CONCAT(kode_opd,' - ',nama_opd)", array(), 'kode_opd')
			: array();
		$this->render('anggaran/realisasi', array(
			'opd_opts'  => $opd_opts,
			'is_super'  => is_super(),
			'my_opd_id' => is_super() ? '' : scope_opd_id(),
			'tree_url'  => site_url('anggaran/realisasi_tree'),
		), 'Realisasi Anggaran (LRA)');
	}

	/** Pohon Pagu (DPA) + Realisasi (NPD final/dibayar) + Sisa, per Program→Kegiatan→SubKeg→Rekening. */
	public function realisasi_tree()
	{
		$opd_id = is_super() ? $this->input->get('opd_id') : scope_opd_id();
		if ( ! $opd_id) { $this->json(array('opd' => NULL, 'leaves' => array())); return; }

		$rows = $this->db
			->select('o.id AS opd_id, o.kode_opd, o.nama_opd, COALESCE(o.singkatan,o.nama_opd) AS opd_singkat,
			          p.id AS p_id, p.kode_program AS p_kode, p.nama_program AS p_nama,
			          k.id AS k_id, k.kode_kegiatan AS k_kode, k.nama_kegiatan AS k_nama,
			          sk.id AS s_id, sk.kode_subkegiatan AS s_kode, sk.nama_subkegiatan AS s_nama,
			          dd.paket_belanja AS paket, dd.sumber_dana_id AS sd_id,
			          COALESCE(msd.nama, dd.sumber_dana_text, "(Tanpa Sumber Dana)") AS sd_nama,
			          r.id AS r_id, r.kode_rekening AS r_kode, r.uraian AS r_nama, dd.total_harga', FALSE)
			->from('dpa d')
			->join('dpa_detail dd',         'dd.dpa_id = d.id')
			->join('master_opd o',           'o.id = d.opd_id')
			->join('master_program p',       'p.id = dd.program_id')
			->join('master_kegiatan k',      'k.id = dd.kegiatan_id')
			->join('master_subkegiatan sk',  'sk.id = dd.subkegiatan_id')
			->join('master_rekening r',      'r.id = dd.rekening_id')
			->join('master_sumber_dana msd', 'msd.id = dd.sumber_dana_id', 'left')
			->where('d.opd_id', (int) $opd_id)
			->order_by('p.kode_program, k.kode_kegiatan, sk.kode_subkegiatan, dd.paket_belanja, r.kode_rekening')
			->get()->result_array();
		if (empty($rows)) { $this->json(array('opd' => NULL, 'leaves' => array())); return; }

		// Realisasi (NPD final/dibayar) per (subkegiatan, pekerjaan, sumber dana, rekening).
		$real = array();
		$rr = $this->db->select('n.subkegiatan_id AS sk, n.perihal AS paket, COALESCE(n.sumber_dana_id,0) AS sd, nd.rekening_id AS rek, SUM(nd.jumlah) AS rlz', FALSE)
			->from('npd n')->join('npd_detail nd', 'nd.npd_id = n.id')
			->where('n.opd_id', (int) $opd_id)
			->where_in('n.status', array('final', 'dibayar'))
			->group_by('n.subkegiatan_id, n.perihal, n.sumber_dana_id, nd.rekening_id')->get()->result();
		foreach ($rr as $x) $real[$x->sk . '|' . $x->paket . '|' . (int) $x->sd . '|' . $x->rek] = (float) $x->rlz;

		// Agregasi jadi "leaves" unik per (program,kegiatan,subkeg,pekerjaan,sumber dana,rekening).
		$lmap = array(); $o_pagu = 0;
		foreach ($rows as $row)
		{
			$key = $row['s_id'] . '|' . $row['paket'] . '|' . ((int) $row['sd_id']) . '|' . $row['r_id'];
			if ( ! isset($lmap[$key]))
			{
				$lmap[$key] = array(
					'p'     => array('id'=>$row['p_id'],'kode'=>$row['p_kode'],'nama'=>$row['p_nama']),
					'k'     => array('id'=>$row['k_id'],'kode'=>$row['k_kode'],'nama'=>$row['k_nama']),
					's'     => array('id'=>$row['s_id'],'kode'=>$row['s_kode'],'nama'=>$row['s_nama']),
					'paket' => ($row['paket'] !== NULL && $row['paket'] !== '') ? $row['paket'] : '(Tanpa Pekerjaan)',
					'sd'    => array('id'=>(int)$row['sd_id'],'nama'=>$row['sd_nama']),
					'r'     => array('id'=>$row['r_id'],'kode'=>$row['r_kode'],'nama'=>$row['r_nama']),
					'pagu'  => 0,
					'real'  => isset($real[$key]) ? $real[$key] : 0,
				);
			}
			$lmap[$key]['pagu'] += (float) $row['total_harga'];
			$o_pagu += (float) $row['total_harga'];
		}
		$leaves = array_values($lmap);
		$o_real = 0; foreach ($leaves as $lf) $o_real += $lf['real'];

		$r0 = $rows[0];
		$this->json(array(
			'opd' => array('id'=>$r0['opd_id'],'kode'=>$r0['kode_opd'],'nama'=>$r0['nama_opd'],'singkat'=>$r0['opd_singkat'],
				'pagu'=>$o_pagu,'real'=>$o_real,'sisa'=>$o_pagu-$o_real),
			'leaves' => $leaves,
		));
	}

	// ======================= ARUS KAS =======================
	private function ak_cfg()
	{
		return array(
			'from' => 'anggaran_kas ak', 'alias' => 'ak',
			'select' => 'ak.id, o.singkatan AS opd_singkat, ak.tahun, sk.kode_subkegiatan, sk.nama_subkegiatan,'
				. ' r.kode_rekening, ak.pagu_tahunan',
			'joins' => array(
				array('master_opd o', 'o.id = ak.opd_id'),
				array('master_subkegiatan sk', 'sk.id = ak.subkegiatan_id'),
				array('master_rekening r', 'r.id = ak.rekening_id'),
			),
			'searchable' => array('sk.nama_subkegiatan', 'r.kode_rekening'),
			'order_by' => 'sk.kode_subkegiatan',
			'columns' => array(
				array('field' => 'kode_subkegiatan', 'label' => 'Sub Kegiatan', 'order' => 'sk.kode_subkegiatan', 'width' => '150px'),
				array('field' => 'nama_subkegiatan', 'label' => 'Nama Sub Kegiatan'),
				array('field' => 'kode_rekening', 'label' => 'Rekening', 'order' => 'r.kode_rekening', 'width' => '180px'),
				array('field' => 'pagu_tahunan', 'label' => 'Pagu Tahunan', 'render' => 'money', 'width' => '170px'),
			),
		);
	}

	public function arus_kas()
	{
		$this->render('anggaran/viewer', array(
			'cfg'      => $this->ak_cfg(),
			'filters'  => $this->chain('ak'),
			'data_url' => site_url('anggaran/arus_kas_data'),
			'judul'    => 'Arus Kas / Anggaran Kas',
			'ikon'     => 'fa-money-bill-trend-up',
			'ket'      => 'Rencana anggaran kas (pagu tahunan) per sub kegiatan & rekening.',
		), 'Arus Kas / Anggaran Kas');
	}

	public function arus_kas_data()
	{
		$res = $this->run_datatable($this->ak_cfg(), $this->chain('ak'), 'ak.opd_id');
		$this->json($res);
	}

	public function ak_rekening_options()
	{
		$this->json_out($this->rekening_options('ak'));
	}

	// ======================= HELPERS =======================

	/** Rantai filter bertingkat. $p = alias tabel ('dd' DPA / 'ak' arus kas). */
	private function chain($p)
	{
		$opd_col = ($p === 'dd') ? 'd.opd_id' : 'ak.opd_id';
		$rek_url = ($p === 'dd') ? 'anggaran/dpa_rekening_options' : 'anggaran/ak_rekening_options';
		$c = array();
		if (is_super())
		{
			$c[] = array('name' => $opd_col, 'label' => 'OPD', 'source' => 'opd', 'opturl' => site_url('master/options/opd'));
		}
		$c[] = array('name' => $p.'.urusan_id', 'label' => 'Urusan', 'source' => 'urusan', 'opturl' => site_url('master/options/urusan'));
		$c[] = array('name' => $p.'.bidang_id', 'label' => 'Bidang', 'source' => 'bidang', 'opturl' => site_url('master/options/bidang'));
		$c[] = array('name' => $p.'.program_id', 'label' => 'Program', 'source' => 'program', 'opturl' => site_url('master/options/program'));
		$c[] = array('name' => $p.'.kegiatan_id', 'label' => 'Kegiatan', 'source' => 'kegiatan', 'opturl' => site_url('master/options/kegiatan'));
		$c[] = array('name' => $p.'.subkegiatan_id', 'label' => 'Sub Kegiatan', 'source' => 'subkegiatan', 'opturl' => site_url('master/options/subkegiatan'));
		$c[] = array('name' => $p.'.rekening_id', 'label' => 'Rekening', 'source' => 'rekening', 'opturl' => site_url($rek_url));
		return $c;
	}

	private function run_datatable($cfg, $chain, $opd_col)
	{
		$dt = array(
			'draw' => (int) $this->input->get('draw'), 'start' => (int) $this->input->get('start'),
			'length' => (int) $this->input->get('length'), 'search' => $this->input->get('search'),
			'order' => $this->input->get('order'),
		);
		$filters = array();
		foreach ($chain as $f)
		{
			$v = $this->input->get('f_' . md5($f['name']));
			if ($v !== NULL && $v !== '') $filters[$f['name']] = $v;
		}
		$scope = is_super() ? NULL : array('column' => $opd_col, 'ids' => array((int) scope_opd_id()));
		$res = $this->mm->datatables($cfg, $dt, $filters, $scope);
		return array('draw' => $dt['draw']) + $res;
	}

	/** Opsi rekening data-driven: distinct rekening yang ADA di data (scoped + dipersempit ancestor). */
	private function rekening_options($p)
	{
		if ($p === 'dd')
		{
			$this->db->from('dpa_detail dd')->join('dpa d', 'd.id = dd.dpa_id');
			$opd_col = 'd.opd_id';
		}
		else
		{
			$this->db->from('anggaran_kas ak');
			$opd_col = 'ak.opd_id';
		}
		$this->db->join('master_rekening r', 'r.id = ' . $p . '.rekening_id')
			->select('r.id AS k, CONCAT(r.kode_rekening, " - ", LEFT(r.uraian,50)) AS v', FALSE)
			->distinct();

		if ( ! is_super()) $this->db->where($opd_col, (int) scope_opd_id());
		$map = array('opd' => $opd_col, 'urusan' => $p.'.urusan_id', 'bidang' => $p.'.bidang_id',
			'program' => $p.'.program_id', 'kegiatan' => $p.'.kegiatan_id', 'subkegiatan' => $p.'.subkegiatan_id');
		foreach ($map as $lv => $col)
		{
			$v = $this->input->get($lv);
			if ($v !== NULL && $v !== '') $this->db->where($col, $v);
		}
		$rows = $this->db->order_by('r.kode_rekening')->get()->result();
		$out = array();
		foreach ($rows as $r) $out[$r->k] = $r->v;
		return $out;
	}

	private function json($arr)     { $this->output->set_content_type('application/json')->set_output(json_encode($arr)); }
	private function json_out($arr) { $this->json($arr); }
}
