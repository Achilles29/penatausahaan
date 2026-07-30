<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller {

	public function index()
	{
		$opd_id = scope_opd_id();          // NULL = superadmin (semua)
		$sk_ids = scope_subkegiatan_ids(); // NULL = semua

		// Total pagu DPA sesuai scope
		$this->db->select('COALESCE(SUM(dd.total_harga),0) AS total', FALSE)
			->from('dpa_detail dd')->join('dpa d', 'd.id = dd.dpa_id');
		if ($opd_id !== NULL) $this->db->where('d.opd_id', $opd_id);
		$total_pagu = (float) $this->db->get()->row()->total;

		// Jumlah baris DPA sesuai scope
		$this->db->from('dpa_detail dd')->join('dpa d', 'd.id = dd.dpa_id');
		if ($opd_id !== NULL) $this->db->where('d.opd_id', $opd_id);
		$jml_dpa = $this->db->count_all_results();

		// Jumlah subkegiatan dalam kewenangan
		if ($sk_ids === NULL) {
			$jml_subkeg = $this->db->count_all_results('master_subkegiatan');
		} else {
			$jml_subkeg = count($sk_ids);
		}

		$data = array(
			'jml_opd'    => $this->db->count_all_results('master_opd'),
			'jml_subkeg' => $jml_subkeg,
			'total_pagu' => $total_pagu,
			'jml_dpa'    => $jml_dpa,
			'jml_penerima' => $this->db->count_all_results('master_penerima'),
		);

		$this->render('dashboard/index', $data, 'Dashboard');
	}
}
