<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Anggaran (read-only Tahap 1): viewer DPA (raw SIPD) & Arus Kas.
 * Memakai ulang engine DataTables server-side dari Master_model.
 */
class Anggaran extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Master_model', 'mm');
	}

	// ---------- DPA ----------
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
		$opd_opts = is_super()
			? $this->mm->options('master_opd', 'id', "CONCAT(COALESCE(singkatan,''),' - ',nama_opd)", array(), 'nama_opd')
			: array();

		$this->render('anggaran/viewer', array(
			'cfg'      => $this->dpa_cfg(),
			'opd_opts' => $opd_opts,
			'is_super' => is_super(),
			'data_url' => site_url('anggaran/dpa_data'),
			'judul'    => 'DPA — Dokumen Pelaksanaan Anggaran',
			'ikon'     => 'fa-file-invoice-dollar',
			'ket'      => 'Data mentah DPA dari SIPD. Sumber sisa anggaran untuk penerbitan NPD.',
		), 'DPA — Dokumen Pelaksanaan Anggaran');
	}

	public function dpa_data()
	{
		$cfg = $this->dpa_cfg();
		$dt = array(
			'draw' => (int) $this->input->get('draw'), 'start' => (int) $this->input->get('start'),
			'length' => (int) $this->input->get('length'), 'search' => $this->input->get('search'),
			'order' => $this->input->get('order'),
		);
		$filters = array();
		$fopd = $this->input->get('f_opd');
		if (is_super() && $fopd !== NULL && $fopd !== '') $filters['d.opd_id'] = $fopd;

		$scope = $this->dpa_scope();
		$res = $this->mm->datatables($cfg, $dt, $filters, $scope);
		$this->json(array('draw' => $dt['draw']) + $res + array(
			'recordsTotal' => $res['recordsTotal'], 'recordsFiltered' => $res['recordsFiltered'],
		));
	}

	private function dpa_scope()
	{
		if (is_super()) return NULL;
		return array('column' => 'd.opd_id', 'ids' => array((int) scope_opd_id()));
	}

	// ---------- ARUS KAS ----------
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
		$opd_opts = is_super()
			? $this->mm->options('master_opd', 'id', "CONCAT(COALESCE(singkatan,''),' - ',nama_opd)", array(), 'nama_opd')
			: array();

		$this->render('anggaran/viewer', array(
			'cfg'      => $this->ak_cfg(),
			'opd_opts' => $opd_opts,
			'is_super' => is_super(),
			'data_url' => site_url('anggaran/arus_kas_data'),
			'judul'    => 'Arus Kas / Anggaran Kas',
			'ikon'     => 'fa-money-bill-trend-up',
			'ket'      => 'Rencana anggaran kas (pagu tahunan) per sub kegiatan & rekening.',
		), 'Arus Kas / Anggaran Kas');
	}

	public function arus_kas_data()
	{
		$cfg = $this->ak_cfg();
		$dt = array(
			'draw' => (int) $this->input->get('draw'), 'start' => (int) $this->input->get('start'),
			'length' => (int) $this->input->get('length'), 'search' => $this->input->get('search'),
			'order' => $this->input->get('order'),
		);
		$filters = array();
		$fopd = $this->input->get('f_opd');
		if (is_super() && $fopd !== NULL && $fopd !== '') $filters['ak.opd_id'] = $fopd;

		$scope = is_super() ? NULL : array('column' => 'ak.opd_id', 'ids' => array((int) scope_opd_id()));
		$res = $this->mm->datatables($cfg, $dt, $filters, $scope);
		$this->json(array('draw' => $dt['draw']) + $res);
	}

	// ---------- util ----------
	private function json($arr)
	{
		$this->output->set_content_type('application/json')->set_output(json_encode($arr));
	}
}
