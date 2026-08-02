<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller {

	public function index()
	{
		$opd_id = scope_opd_id();
		$sk_ids = scope_subkegiatan_ids();
		$role   = current_role();

		$this->db->select('COALESCE(SUM(dd.total_harga),0) AS total', FALSE)
			->from('dpa_detail dd')->join('dpa d', 'd.id = dd.dpa_id');
		if ($opd_id !== NULL) $this->db->where('d.opd_id', $opd_id);
		$total_pagu = (float) $this->db->get()->row()->total;

		$this->db->from('dpa_detail dd')->join('dpa d', 'd.id = dd.dpa_id');
		if ($opd_id !== NULL) $this->db->where('d.opd_id', $opd_id);
		$jml_dpa = $this->db->count_all_results();

		if ($sk_ids === NULL) {
			$jml_subkeg = $this->db->count_all_results('master_subkegiatan');
		} else {
			$jml_subkeg = count($sk_ids);
		}

		$data = array(
			'jml_opd'      => $this->db->count_all_results('master_opd'),
			'jml_subkeg'   => $jml_subkeg,
			'total_pagu'   => $total_pagu,
			'jml_dpa'      => $jml_dpa,
			'jml_penerima' => $this->db->count_all_results('master_penerima'),
			'breakdown'    => $this->_breakdown($opd_id, $sk_ids, $role),
			'role'         => $role,
		);

		$this->render('dashboard/index', $data, 'Dashboard');
	}

	private function _breakdown($opd_id, $sk_ids, $role)
	{
		if ($role === 'superadmin')
		{
			$rows = $this->db
				->select('o.kode_opd, COALESCE(o.singkatan, o.nama_opd) AS nama,
				          COUNT(DISTINCT dd.subkegiatan_id) AS jml_subkeg,
				          COALESCE(SUM(dd.total_harga), 0) AS total_pagu', FALSE)
				->from('master_opd o')
				->join('dpa d', 'd.opd_id = o.id', 'left')
				->join('dpa_detail dd', 'dd.dpa_id = d.id', 'left')
				->group_by('o.id, o.kode_opd, o.singkatan, o.nama_opd')
				->order_by('o.kode_opd')
				->get()->result_array();
			return array('type' => 'opd', 'rows' => $rows);
		}

		if ($role === 'admin_opd' && $opd_id)
		{
			$rows = $this->db
				->select('p.kode_program, p.nama_program,
				          COUNT(DISTINCT dd.subkegiatan_id) AS jml_subkeg,
				          COALESCE(SUM(dd.total_harga), 0) AS total_pagu', FALSE)
				->from('dpa d')
				->join('dpa_detail dd', 'dd.dpa_id = d.id')
				->join('master_program p', 'p.id = dd.program_id')
				->where('d.opd_id', (int) $opd_id)
				->group_by('p.id, p.kode_program, p.nama_program')
				->order_by('p.kode_program')
				->get()->result_array();
			return array('type' => 'program', 'rows' => $rows);
		}

		if ($role === 'user_opd' && ! empty($sk_ids) && $opd_id)
		{
			$rows = $this->db
				->select('sk.kode_subkegiatan, sk.nama_subkegiatan,
				          COALESCE(SUM(dd.total_harga), 0) AS total_pagu', FALSE)
				->from('dpa d')
				->join('dpa_detail dd', 'dd.dpa_id = d.id')
				->join('master_subkegiatan sk', 'sk.id = dd.subkegiatan_id')
				->where('d.opd_id', (int) $opd_id)
				->where_in('dd.subkegiatan_id', $sk_ids)
				->group_by('sk.id, sk.kode_subkegiatan, sk.nama_subkegiatan')
				->order_by('sk.kode_subkegiatan')
				->get()->result_array();
			return array('type' => 'subkegiatan', 'rows' => $rows);
		}

		return array('type' => 'none', 'rows' => array());
	}
}
