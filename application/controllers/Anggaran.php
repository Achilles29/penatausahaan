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

	// ======================= DPA =======================
	private function dpa_cfg()
	{
		return array(
			'from'  => 'dpa_detail dd', 'alias' => 'dd',
			'select' => 'dd.id, o.singkatan AS opd_singkat, d.tahun, sk.kode_subkegiatan, sk.nama_subkegiatan,'
				. ' r.kode_rekening, dd.paket_belanja, dd.koefisien, dd.harga_satuan, dd.total_harga',
			'joins' => array(
				array('dpa d', 'd.id = dd.dpa_id'),
				array('master_opd o', 'o.id = d.opd_id'),
				array('master_subkegiatan sk', 'sk.id = dd.subkegiatan_id'),
				array('master_rekening r', 'r.id = dd.rekening_id'),
			),
			'searchable' => array('dd.paket_belanja', 'sk.nama_subkegiatan', 'r.kode_rekening', 'dd.keterangan_belanja'),
			'order_by' => 'dd.no_urut',
			'columns' => array(
				array('field' => 'kode_subkegiatan', 'label' => 'Sub Kegiatan', 'order' => 'sk.kode_subkegiatan', 'width' => '150px'),
				array('field' => 'paket_belanja', 'label' => 'Paket / Uraian Belanja'),
				array('field' => 'kode_rekening', 'label' => 'Rekening', 'order' => 'r.kode_rekening', 'width' => '180px'),
				array('field' => 'koefisien', 'label' => 'Koefisien', 'width' => '150px'),
				array('field' => 'harga_satuan', 'label' => 'Harga Satuan', 'render' => 'money', 'width' => '150px'),
				array('field' => 'total_harga', 'label' => 'Total', 'render' => 'money', 'width' => '160px'),
			),
		);
	}

	public function dpa()
	{
		$this->render('anggaran/viewer', array(
			'cfg'      => $this->dpa_cfg(),
			'filters'  => $this->chain('dd'),
			'data_url' => site_url('anggaran/dpa_data'),
			'judul'    => 'DPA — Dokumen Pelaksanaan Anggaran',
			'ikon'     => 'fa-file-invoice-dollar',
			'ket'      => 'Data mentah DPA dari SIPD. Sumber sisa anggaran untuk penerbitan NPD.',
		), 'DPA — Dokumen Pelaksanaan Anggaran');
	}

	public function dpa_data()
	{
		$res = $this->run_datatable($this->dpa_cfg(), $this->chain('dd'), 'd.opd_id');
		$this->json($res);
	}

	public function dpa_rekening_options()
	{
		$this->json_out($this->rekening_options('dd'));
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
