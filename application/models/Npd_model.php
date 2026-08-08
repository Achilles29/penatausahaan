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

	/** Program yang ADA di DPA OPD (distinct), opsional dibatasi scope subkegiatan. */
	public function dpa_programs($opd_id, $scope_ids = NULL)
	{
		$this->db->distinct()->select('p.id, p.kode_program, p.nama_program', FALSE)
			->from('dpa d')->join('dpa_detail dd', 'dd.dpa_id = d.id')
			->join('master_program p', 'p.id = dd.program_id')
			->where('d.opd_id', (int) $opd_id);
		$this->apply_scope($scope_ids);
		return $this->db->order_by('p.kode_program')->get()->result();
	}

	/** Kegiatan di DPA OPD di bawah sebuah program. */
	public function dpa_kegiatan($opd_id, $program_id, $scope_ids = NULL)
	{
		$this->db->distinct()->select('k.id, k.kode_kegiatan, k.nama_kegiatan', FALSE)
			->from('dpa d')->join('dpa_detail dd', 'dd.dpa_id = d.id')
			->join('master_kegiatan k', 'k.id = dd.kegiatan_id')
			->where('d.opd_id', (int) $opd_id)->where('dd.program_id', (int) $program_id);
		$this->apply_scope($scope_ids);
		return $this->db->order_by('k.kode_kegiatan')->get()->result();
	}

	/** Sub kegiatan di DPA OPD di bawah sebuah kegiatan. */
	public function dpa_subkegiatan($opd_id, $kegiatan_id, $scope_ids = NULL)
	{
		$this->db->distinct()->select('sk.id, sk.kode_subkegiatan, sk.nama_subkegiatan', FALSE)
			->from('dpa d')->join('dpa_detail dd', 'dd.dpa_id = d.id')
			->join('master_subkegiatan sk', 'sk.id = dd.subkegiatan_id')
			->where('d.opd_id', (int) $opd_id)->where('dd.kegiatan_id', (int) $kegiatan_id);
		$this->apply_scope($scope_ids);
		return $this->db->order_by('sk.kode_subkegiatan')->get()->result();
	}

	private function apply_scope($scope_ids)
	{
		if ($scope_ids === NULL) return;
		if (empty($scope_ids)) $this->db->where('1 = 0', NULL, FALSE);
		else $this->db->where_in('dd.subkegiatan_id', $scope_ids);
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

	/** Daftar PEKERJAAN (paket_belanja) pada subkegiatan (dari DPA). */
	public function dpa_pekerjaan($opd_id, $subkegiatan_id)
	{
		$rows = $this->db->distinct()->select('dd.paket_belanja', FALSE)
			->from('dpa d')->join('dpa_detail dd', 'dd.dpa_id = d.id')
			->where('d.opd_id', (int) $opd_id)
			->where('dd.subkegiatan_id', (int) $subkegiatan_id)
			->where('dd.paket_belanja IS NOT NULL', NULL, FALSE)
			->where("dd.paket_belanja != ''", NULL, FALSE)
			->order_by('dd.paket_belanja')->get()->result();
		$out = array();
		foreach ($rows as $r) $out[] = $r->paket_belanja;
		return $out;
	}

	/** Sumber dana yang ada pada (subkegiatan, pekerjaan) di DPA. */
	public function dpa_sumber_dana($opd_id, $subkegiatan_id, $paket)
	{
		$rows = $this->db->distinct()
			->select('dd.sumber_dana_id, COALESCE(sd.nama, dd.sumber_dana_text, "(Tanpa Sumber Dana)") AS nama', FALSE)
			->from('dpa d')->join('dpa_detail dd', 'dd.dpa_id = d.id')
			->join('master_sumber_dana sd', 'sd.id = dd.sumber_dana_id', 'left')
			->where('d.opd_id', (int) $opd_id)
			->where('dd.subkegiatan_id', (int) $subkegiatan_id)
			->where('dd.paket_belanja', $paket)
			->order_by('nama')->get()->result();
		return $rows; // [{sumber_dana_id, nama}]
	}

	private function _sd($sd) { return $sd ? (int) $sd : NULL; }

	/**
	 * Rekening pada (subkegiatan, pekerjaan, sumber dana) dari DPA + pagu/realisasi/sisa.
	 */
	public function rekening_sisa($opd_id, $subkegiatan_id, $paket, $sumber_dana_id, $exclude_npd_id = NULL)
	{
		$sd = $this->_sd($sumber_dana_id);
		$this->db
			->select('dd.rekening_id, r.kode_rekening, r.uraian, r.kategori_pajak, SUM(dd.total_harga) AS pagu', FALSE)
			->from('dpa d')->join('dpa_detail dd', 'dd.dpa_id = d.id')
			->join('master_rekening r', 'r.id = dd.rekening_id')
			->where('d.opd_id', (int) $opd_id)
			->where('dd.subkegiatan_id', (int) $subkegiatan_id)
			->where('dd.paket_belanja', $paket)
			->where('dd.sumber_dana_id', $sd)
			->group_by('dd.rekening_id, r.kode_rekening, r.uraian, r.kategori_pajak')
			->order_by('r.kode_rekening');
		$pagu = $this->db->get()->result();

		$real = $this->realisasi_map($opd_id, $subkegiatan_id, $paket, $sumber_dana_id, $exclude_npd_id);

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

	/** Realisasi [rekening_id => jumlah] utk (opd, subkeg, pekerjaan, sumber dana). */
	public function realisasi_map($opd_id, $subkegiatan_id, $paket, $sumber_dana_id, $exclude_npd_id = NULL)
	{
		$sd = $this->_sd($sumber_dana_id);
		$this->db->select('nd.rekening_id, SUM(nd.jumlah) AS realisasi', FALSE)
			->from('npd_detail nd')->join('npd n', 'n.id = nd.npd_id')
			->where('n.opd_id', (int) $opd_id)
			->where('n.subkegiatan_id', (int) $subkegiatan_id)
			->where('n.perihal', $paket)
			->where('n.sumber_dana_id', $sd);
		if ($exclude_npd_id) $this->db->where('n.id !=', (int) $exclude_npd_id);
		$rows = $this->db->group_by('nd.rekening_id')->get()->result();

		$map = array();
		foreach ($rows as $r) $map[(int) $r->rekening_id] = (float) $r->realisasi;
		return $map;
	}

	/** Pagu [rekening_id => total] utk (opd, subkeg, pekerjaan, sumber dana) => validasi server. */
	public function pagu_map($opd_id, $subkegiatan_id, $paket, $sumber_dana_id)
	{
		$sd = $this->_sd($sumber_dana_id);
		$rows = $this->db
			->select('dd.rekening_id, SUM(dd.total_harga) AS pagu', FALSE)
			->from('dpa d')->join('dpa_detail dd', 'dd.dpa_id = d.id')
			->where('d.opd_id', (int) $opd_id)
			->where('dd.subkegiatan_id', (int) $subkegiatan_id)
			->where('dd.paket_belanja', $paket)
			->where('dd.sumber_dana_id', $sd)
			->group_by('dd.rekening_id')->get()->result();
		$map = array();
		foreach ($rows as $r) $map[(int) $r->rekening_id] = (float) $r->pagu;
		return $map;
	}

	/** Nomor NPD berikutnya (auto, dapat diedit user). */
	/**
	 * Nomor NPD berikutnya. Format: 900 / 0001 / 06 / 2026
	 * (900=kode, 0001=urut per OPD/tahun, 06=bulan, 2026=tahun). Dapat diedit user.
	 */
	public function next_nomor($opd_id, $tahun, $bulan = NULL)
	{
		$n = $this->db->where('opd_id', (int) $opd_id)
			->where('YEAR(tanggal)', (int) $tahun)
			->count_all_results('npd') + 1;
		$bln = $bulan ? (int) $bulan : (int) date('n');
		return '900 / ' . str_pad($n, 4, '0', STR_PAD_LEFT)
			. ' / ' . str_pad($bln, 2, '0', STR_PAD_LEFT)
			. ' / ' . $tahun;
	}

	/** Ambil NPD + detailnya. */
	public function get_full($id)
	{
		$npd = $this->db->get_where('npd', array('id' => (int) $id))->row();
		if ( ! $npd) return NULL;
		$npd->details = $this->db
			->select('nd.*, r.kode_rekening, r.uraian, r.kategori_pajak, r.jenis_belanja', FALSE)
			->from('npd_detail nd')->join('master_rekening r', 'r.id = nd.rekening_id')
			->where('nd.npd_id', (int) $id)->order_by('r.kode_rekening')->get()->result();
		return $npd;
	}
}
