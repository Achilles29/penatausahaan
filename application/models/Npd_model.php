<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model NPD — inti sinkronisasi dengan sisa anggaran DPA.
 * Sisa anggaran dihitung per (opd, subkegiatan, rekening):
 *   pagu      = SUM(dpa_detail.total_harga) untuk kombinasi tsb
 *   realisasi = SUM(npd_detail.jumlah) dari NPD lain pada kombinasi tsb
 *   sisa      = pagu - realisasi
 */
class Npd_model extends CI_Model {

	/** Subkegiatan yang memiliki DPA untuk OPD tertentu (opsional dibatasi scope). */
	public function subkegiatan_with_dpa($opd_id, $scope_ids = NULL)
	{
		$this->db->distinct()
			->select('sk.id, sk.kode_subkegiatan, sk.nama_subkegiatan,
			          k.id AS kegiatan_id, k.kode_kegiatan, k.nama_kegiatan,
			          p.id AS program_id, p.kode_program, p.nama_program,
			          b.id AS bidang_id, b.urusan_id', FALSE)
			->from('dpa d')
			->join('dpa_detail dd', 'dd.dpa_id = d.id')
			->join('master_subkegiatan sk', 'sk.id = dd.subkegiatan_id')
			->join('master_kegiatan k', 'k.id = sk.kegiatan_id')
			->join('master_program p', 'p.id = k.program_id')
			->join('master_bidang b', 'b.id = p.bidang_id')
			->where('d.opd_id', (int) $opd_id);

		if ($scope_ids !== NULL)
		{
			if (empty($scope_ids)) $this->db->where('1 = 0', NULL, FALSE);
			else $this->db->where_in('sk.id', $scope_ids);
		}
		return $this->db->order_by('sk.kode_subkegiatan')->get()->result();
	}

	/** Konteks nomenklatur satu subkegiatan (utk isi header NPD). */
	public function subkegiatan_context($opd_id, $subkegiatan_id)
	{
		return $this->db
			->select('sk.id AS subkegiatan_id, sk.kode_subkegiatan, sk.nama_subkegiatan,
			          k.id AS kegiatan_id, p.id AS program_id, b.id AS bidang_id, b.urusan_id', FALSE)
			->from('master_subkegiatan sk')
			->join('master_kegiatan k', 'k.id = sk.kegiatan_id')
			->join('master_program p', 'p.id = k.program_id')
			->join('master_bidang b', 'b.id = p.bidang_id')
			->where('sk.id', (int) $subkegiatan_id)
			->get()->row();
	}

	/**
	 * Daftar rekening pada subkegiatan (dari DPA) beserta pagu, realisasi, sisa.
	 * @param int      $exclude_npd_id NPD yang sedang diedit (dikecualikan dari realisasi)
	 */
	public function rekening_sisa($opd_id, $subkegiatan_id, $exclude_npd_id = NULL)
	{
		// pagu per rekening
		$pagu = $this->db
			->select('dd.rekening_id, r.kode_rekening, r.uraian, r.kategori_pajak, SUM(dd.total_harga) AS pagu', FALSE)
			->from('dpa d')
			->join('dpa_detail dd', 'dd.dpa_id = d.id')
			->join('master_rekening r', 'r.id = dd.rekening_id')
			->where('d.opd_id', (int) $opd_id)
			->where('dd.subkegiatan_id', (int) $subkegiatan_id)
			->group_by('dd.rekening_id, r.kode_rekening, r.uraian, r.kategori_pajak')
			->order_by('r.kode_rekening')
			->get()->result();

		$real = $this->realisasi_map($opd_id, $subkegiatan_id, $exclude_npd_id);

		$out = array();
		foreach ($pagu as $p)
		{
			$used = isset($real[$p->rekening_id]) ? $real[$p->rekening_id] : 0;
			$out[] = array(
				'rekening_id'    => (int) $p->rekening_id,
				'kode'           => $p->kode_rekening,
				'uraian'         => $p->uraian,
				'kategori_pajak' => $p->kategori_pajak,
				'pagu'           => (float) $p->pagu,
				'realisasi'      => (float) $used,
				'sisa'           => (float) $p->pagu - (float) $used,
			);
		}
		return $out;
	}

	/** Map [rekening_id => realisasi] untuk (opd, subkegiatan). */
	public function realisasi_map($opd_id, $subkegiatan_id, $exclude_npd_id = NULL)
	{
		$this->db->select('nd.rekening_id, SUM(nd.jumlah) AS realisasi', FALSE)
			->from('npd_detail nd')
			->join('npd n', 'n.id = nd.npd_id')
			->where('n.opd_id', (int) $opd_id)
			->where('n.subkegiatan_id', (int) $subkegiatan_id);
		if ($exclude_npd_id) $this->db->where('n.id !=', (int) $exclude_npd_id);
		$rows = $this->db->group_by('nd.rekening_id')->get()->result();

		$map = array();
		foreach ($rows as $r) $map[(int) $r->rekening_id] = (float) $r->realisasi;
		return $map;
	}

	/** Pagu total per rekening pada (opd, subkegiatan) => untuk validasi server. */
	public function pagu_map($opd_id, $subkegiatan_id)
	{
		$rows = $this->db
			->select('dd.rekening_id, SUM(dd.total_harga) AS pagu', FALSE)
			->from('dpa d')->join('dpa_detail dd', 'dd.dpa_id = d.id')
			->where('d.opd_id', (int) $opd_id)
			->where('dd.subkegiatan_id', (int) $subkegiatan_id)
			->group_by('dd.rekening_id')->get()->result();
		$map = array();
		foreach ($rows as $r) $map[(int) $r->rekening_id] = (float) $r->pagu;
		return $map;
	}

	/** Nomor NPD berikutnya (auto, dapat diedit user). */
	public function next_nomor($opd_id, $tahun)
	{
		$n = $this->db->where('opd_id', (int) $opd_id)
			->where('YEAR(tanggal)', (int) $tahun)
			->count_all_results('npd') + 1;
		$opd = $this->db->select('singkatan, kode_opd')->get_where('master_opd', array('id' => (int) $opd_id))->row();
		$sing = $opd ? ($opd->singkatan ?: $opd->kode_opd) : 'OPD';
		$romawi = array('', 'I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII');
		$bln = $romawi[(int) date('n')];
		return '900.1.11/' . str_pad($n, 3, '0', STR_PAD_LEFT) . '/NPD/' . $sing . '/' . $bln . '/' . $tahun;
	}

	/** Ambil NPD + detailnya. */
	public function get_full($id)
	{
		$npd = $this->db->get_where('npd', array('id' => (int) $id))->row();
		if ( ! $npd) return NULL;
		$npd->details = $this->db
			->select('nd.*, r.kode_rekening, r.uraian, r.kategori_pajak', FALSE)
			->from('npd_detail nd')->join('master_rekening r', 'r.id = nd.rekening_id')
			->where('nd.npd_id', (int) $id)->order_by('r.kode_rekening')->get()->result();
		return $npd;
	}
}
