<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * NPD — Nota Pencairan Dana (inti).
 * Sinkron dengan DPA: user pilih subkegiatan lalu isi jumlah per rekening,
 * dibatasi sisa anggaran (pagu - realisasi). Nomor auto (bisa diedit).
 */
class Npd extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Npd_model', 'npd');
		$this->load->model('Master_model', 'mm');
	}

	// ---------------- OPD efektif utk user ----------------
	private function eff_opd($from_request = TRUE)
	{
		if (is_super())
		{
			return $from_request ? (int) $this->input->post('opd_id') ?: (int) $this->input->get('opd_id') : NULL;
		}
		return (int) scope_opd_id();
	}

	// ---------------- INDEX ----------------
	private function cfg()
	{
		return array(
			'from' => 'npd m', 'alias' => 'm',
			'select' => 'm.id, m.nomor_npd, m.tanggal, m.perihal, m.status,'
				. ' o.singkatan AS opd_singkat, sk.kode_subkegiatan, sk.nama_subkegiatan,'
				. ' (SELECT COALESCE(SUM(nd.jumlah),0) FROM npd_detail nd WHERE nd.npd_id = m.id) AS total',
			'joins' => array(
				array('master_opd o', 'o.id = m.opd_id'),
				array('master_subkegiatan sk', 'sk.id = m.subkegiatan_id'),
			),
			'searchable' => array('m.nomor_npd', 'm.perihal', 'sk.nama_subkegiatan', 'sk.kode_subkegiatan'),
			'order_by' => 'm.tanggal DESC, m.id DESC',
			'columns' => array(
				array('field' => 'nomor_npd', 'label' => 'Nomor', 'order' => 'm.nomor_npd', 'width' => '190px'),
				array('field' => 'tanggal', 'label' => 'Tanggal', 'order' => 'm.tanggal', 'render' => 'date', 'width' => '110px'),
				array('field' => 'kode_subkegiatan', 'label' => 'Sub Kegiatan', 'order' => 'sk.kode_subkegiatan'),
				array('field' => 'perihal', 'label' => 'Perihal'),
				array('field' => 'total', 'label' => 'Jumlah', 'render' => 'money', 'width' => '150px'),
				array('field' => 'status', 'label' => 'Status', 'render' => 'status', 'width' => '90px'),
			),
		);
	}

	private function chain()
	{
		// Opsi filter bersumber DPA OPD (bukan master global) — konsisten dgn tagging DPA.
		$c = array();
		if (is_super())
			$c[] = array('name' => 'm.opd_id', 'label' => 'OPD', 'source' => 'opd', 'opturl' => site_url('master/options/opd'));
		$c[] = array('name' => 'm.program_id', 'label' => 'Program', 'source' => 'program', 'opturl' => site_url('npd/flt_program'));
		$c[] = array('name' => 'm.kegiatan_id', 'label' => 'Kegiatan', 'source' => 'kegiatan', 'opturl' => site_url('npd/flt_kegiatan'));
		$c[] = array('name' => 'm.subkegiatan_id', 'label' => 'Sub Kegiatan', 'source' => 'subkegiatan', 'opturl' => site_url('npd/flt_subkegiatan'));
		return $c;
	}

	// ---------- Opsi filter index (DPA-scoped) ----------
	private function flt_opd()
	{
		return is_super() ? (int) $this->input->get('opd') : (int) scope_opd_id();
	}

	public function flt_program()
	{
		$opd = $this->flt_opd();
		$out = array();
		if ($opd)
		{
			$scope = is_super() ? NULL : scope_subkegiatan_ids();
			foreach ($this->npd->dpa_programs($opd, $scope) as $r)
				$out[$r->id] = $r->kode_program . ' — ' . $r->nama_program;
		}
		$this->json($out);
	}

	public function flt_kegiatan()
	{
		$opd = $this->flt_opd();
		$program = (int) $this->input->get('program');
		$out = array();
		if ($opd && $program)
		{
			$scope = is_super() ? NULL : scope_subkegiatan_ids();
			foreach ($this->npd->dpa_kegiatan($opd, $program, $scope) as $r)
				$out[$r->id] = $r->kode_kegiatan . ' — ' . $r->nama_kegiatan;
		}
		$this->json($out);
	}

	public function flt_subkegiatan()
	{
		$opd = $this->flt_opd();
		$kegiatan = (int) $this->input->get('kegiatan');
		$out = array();
		if ($opd && $kegiatan)
		{
			$scope = is_super() ? NULL : scope_subkegiatan_ids();
			foreach ($this->npd->dpa_subkegiatan($opd, $kegiatan, $scope) as $r)
				$out[$r->id] = $r->kode_subkegiatan . ' — ' . $r->nama_subkegiatan;
		}
		$this->json($out);
	}

	public function index()
	{
		$this->render('npd/index', array(
			'cfg'      => $this->cfg(),
			'filters'  => $this->chain(),
			'data_url' => site_url('npd/data'),
		), 'NPD — Nota Pencairan Dana');
	}

	public function data()
	{
		$cfg = $this->cfg();
		$dt = array(
			'draw' => (int) $this->input->get('draw'), 'start' => (int) $this->input->get('start'),
			'length' => (int) $this->input->get('length'), 'search' => $this->input->get('search'),
			'order' => $this->input->get('order'),
		);
		$filters = array();
		foreach ($this->chain() as $f)
		{
			$v = $this->input->get('f_' . md5($f['name']));
			if ($v !== NULL && $v !== '') $filters[$f['name']] = $v;
		}
		$st = $this->input->get('f_status');
		if ($st !== NULL && $st !== '') $filters['m.status'] = $st;

		$scope = $this->index_scope();
		$res = $this->mm->datatables($cfg, $dt, $filters, $scope);
		$this->output->set_content_type('application/json')
			->set_output(json_encode(array('draw' => $dt['draw']) + $res));
	}

	private function index_scope()
	{
		if (is_super()) return NULL;
		if (current_role() === 'admin_opd')
			return array('column' => 'm.opd_id', 'ids' => array((int) scope_opd_id()));
		// user_opd: batasi ke subkegiatan kewenangannya
		return array('column' => 'm.subkegiatan_id', 'ids' => scope_subkegiatan_ids());
	}

	// ---------------- FORM (create/edit) ----------------
	public function form($id = NULL)
	{
		$id  = $id ? (int) $id : NULL;
		$row = NULL;
		if ($id)
		{
			$row = $this->npd->get_full($id);
			if ( ! $row) show_404();
			if ( ! $this->can_edit($row)) show_error('Akses ditolak', 403);
		}

		$opd_opts = is_super()
			? $this->mm->options('master_opd', 'id', "CONCAT(kode_opd,' - ',nama_opd)", array(), 'kode_opd')
			: array();

		$this->render('npd/form', array(
			'row'       => $row,
			'is_super'  => is_super(),
			'opd_opts'  => $opd_opts,
			'my_opd_id' => is_super() ? '' : scope_opd_id(),
			'sumber_opts' => $this->mm->options('master_sumber_dana', 'id', "CONCAT(kode,' - ',nama)", array('is_active' => 1), 'kode'),
		), $id ? 'Edit NPD' : 'Buat NPD');
	}

	/** JSON: program yang ada di DPA OPD (cascade level 1). */
	public function program_options()
	{
		$opd_id = $this->eff_opd();
		if ( ! $opd_id) { $this->json(array()); return; }
		$scope = is_super() ? NULL : scope_subkegiatan_ids();
		$out = array();
		foreach ($this->npd->dpa_programs($opd_id, $scope) as $r)
			$out[] = array('id' => $r->id, 'label' => $r->kode_program . ' — ' . $r->nama_program);
		$this->json($out);
	}

	/** JSON: kegiatan di DPA OPD di bawah program (cascade level 2). */
	public function kegiatan_options()
	{
		$opd_id  = $this->eff_opd();
		$program = (int) $this->input->get('program_id');
		if ( ! $opd_id || ! $program) { $this->json(array()); return; }
		$scope = is_super() ? NULL : scope_subkegiatan_ids();
		$out = array();
		foreach ($this->npd->dpa_kegiatan($opd_id, $program, $scope) as $r)
			$out[] = array('id' => $r->id, 'label' => $r->kode_kegiatan . ' — ' . $r->nama_kegiatan);
		$this->json($out);
	}

	/** JSON: subkegiatan di DPA OPD di bawah kegiatan (cascade level 3). */
	public function subkegiatan_options()
	{
		$opd_id   = $this->eff_opd();
		$kegiatan = (int) $this->input->get('kegiatan_id');
		if ( ! $opd_id) { $this->json(array()); return; }
		$scope = is_super() ? NULL : scope_subkegiatan_ids();

		// Bila kegiatan_id diberikan -> subkeg di bawah kegiatan tsb (cascade).
		// Bila tidak -> semua subkeg ber-DPA OPD (kompatibilitas).
		$out = array();
		if ($kegiatan)
		{
			foreach ($this->npd->dpa_subkegiatan($opd_id, $kegiatan, $scope) as $r)
				$out[] = array('id' => $r->id, 'label' => $r->kode_subkegiatan . ' — ' . $r->nama_subkegiatan);
		}
		else
		{
			foreach ($this->npd->subkegiatan_with_dpa($opd_id, $scope) as $r)
				$out[] = array('id' => $r->id, 'label' => $r->kode_subkegiatan . ' — ' . $r->nama_subkegiatan);
		}
		$this->json($out);
	}

	/** JSON: pekerjaan (paket) pada subkegiatan (dari DPA). */
	public function pekerjaan_options()
	{
		$opd_id = $this->eff_opd();
		$sub    = (int) $this->input->get('subkegiatan_id');
		if ( ! $opd_id || ! $sub) { $this->json(array()); return; }
		if ( ! is_super() && ! $this->subkeg_allowed($sub)) { $this->json(array()); return; }
		$out = array();
		foreach ($this->npd->dpa_pekerjaan($opd_id, $sub) as $paket)
			$out[] = array('id' => $paket, 'label' => $paket);
		$this->json($out);
	}

	/** JSON: sumber dana pada (subkegiatan, pekerjaan) dari DPA. */
	public function sumber_dana_options()
	{
		$opd_id = $this->eff_opd();
		$sub    = (int) $this->input->get('subkegiatan_id');
		$paket  = (string) $this->input->get('pekerjaan');
		if ( ! $opd_id || ! $sub || $paket === '') { $this->json(array()); return; }
		$out = array();
		foreach ($this->npd->dpa_sumber_dana($opd_id, $sub, $paket) as $r)
			$out[] = array('id' => (int) $r->sumber_dana_id, 'label' => $r->nama);
		$this->json($out);
	}

	/** JSON: rekening + sisa anggaran utk (subkegiatan, pekerjaan, sumber dana). */
	public function rekening_sisa()
	{
		$opd_id  = $this->eff_opd();
		$sub     = (int) $this->input->get('subkegiatan_id');
		$paket   = (string) $this->input->get('pekerjaan');
		$sd      = (int) $this->input->get('sumber_dana_id');
		$exclude = $this->input->get('npd_id') ? (int) $this->input->get('npd_id') : NULL;
		if ( ! $opd_id || ! $sub || $paket === '') { $this->json(array()); return; }
		if ( ! is_super() && ! $this->subkeg_allowed($sub)) { $this->json(array()); return; }
		$this->json($this->npd->rekening_sisa($opd_id, $sub, $paket, $sd, $exclude));
	}

	/** JSON: nomor NPD berikutnya. */
	public function next_nomor()
	{
		$opd_id = $this->eff_opd();
		$tahun  = (int) ($this->input->get('tahun') ?: date('Y'));
		$bulan  = (int) $this->input->get('bulan');
		if ( ! $opd_id) { $this->json(array('nomor' => '')); return; }
		$this->json(array('nomor' => $this->npd->next_nomor($opd_id, $tahun, $bulan ?: NULL)));
	}

	// ---------------- SAVE ----------------
	public function save()
	{
		$id     = (int) $this->input->post('id');
		$opd_id = $this->eff_opd();
		$sub    = (int) $this->input->post('subkegiatan_id');
		$errors = array();

		$paket = (string) $this->input->post('perihal', TRUE);   // = pekerjaan (paket DPA)
		$sd    = (int) $this->input->post('sumber_dana_id');

		if ( ! $opd_id) $errors[] = 'OPD wajib dipilih.';
		if ( ! $sub)    $errors[] = 'Sub kegiatan wajib dipilih.';
		if ($paket === '') $errors[] = 'Pekerjaan wajib dipilih.';

		// otorisasi
		if ($id)
		{
			$existing = $this->npd->get_full($id);
			if ( ! $existing || ! $this->can_edit($existing)) show_error('Akses ditolak', 403);
		}
		if ($sub && ! is_super() && ! $this->subkeg_allowed($sub))
			$errors[] = 'Sub kegiatan di luar kewenangan Anda.';

		// baris detail
		$rek_ids = $this->input->post('rekening_id');
		$jmls    = $this->input->post('jumlah');
		$lines   = array();
		if (is_array($rek_ids))
		{
			foreach ($rek_ids as $i => $rid)
			{
				$rid = (int) $rid;
				$jml = (float) str_replace(array('.', ' '), '', (string) (isset($jmls[$i]) ? $jmls[$i] : 0));
				if ($rid && $jml > 0) $lines[$rid] = ($lines[$rid] ?? 0) + $jml;
			}
		}
		if (empty($lines)) $errors[] = 'Minimal satu rekening dengan jumlah > 0.';

		// validasi sisa (grain: subkeg + pekerjaan + sumber dana + rekening)
		if ($sub && $opd_id && $paket !== '' && $lines)
		{
			$pagu = $this->npd->pagu_map($opd_id, $sub, $paket, $sd);
			$real = $this->npd->realisasi_map($opd_id, $sub, $paket, $sd, $id ?: NULL);
			foreach ($lines as $rid => $jml)
			{
				if ( ! isset($pagu[$rid])) { $errors[] = "Rekening #$rid tidak ada di DPA pekerjaan/sumber dana ini."; continue; }
				$sisa = $pagu[$rid] - (isset($real[$rid]) ? $real[$rid] : 0);
				if ($jml > $sisa + 0.001)
					$errors[] = 'Jumlah rekening ' . $rid . ' (' . rupiah($jml) . ') melebihi sisa ' . rupiah($sisa) . '.';
			}
		}

		if ($errors)
		{
			$this->session->set_flashdata('error', implode(' ', $errors));
			redirect($id ? 'npd/form/' . $id : 'npd/form');
		}

		$ctx = $this->npd->subkegiatan_context($opd_id, $sub);
		$tanggal = $this->input->post('tanggal', TRUE) ?: date('Y-m-d');
		$nomor   = trim((string) $this->input->post('nomor_npd', TRUE));
		if ($nomor === '') $nomor = $this->npd->next_nomor($opd_id, (int) date('Y', strtotime($tanggal)), (int) date('n', strtotime($tanggal)));

		$header = array(
			'nomor_npd'      => $nomor,
			'tanggal'        => $tanggal,
			'perihal'        => (string) $this->input->post('perihal', TRUE),
			'pekerjaan'      => (string) $this->input->post('pekerjaan', TRUE),
			'opd_id'         => $opd_id,
			'opd_unit_id'    => (current_role() === 'user_opd' && ! empty($this->user['opd_unit_id'])) ? (int) $this->user['opd_unit_id'] : NULL,
			'urusan_id'      => $ctx ? $ctx->urusan_id : NULL,
			'bidang_id'      => $ctx ? $ctx->bidang_id : NULL,
			'program_id'     => $ctx ? $ctx->program_id : NULL,
			'kegiatan_id'    => $ctx ? $ctx->kegiatan_id : NULL,
			'subkegiatan_id' => $sub,
			'sumber_dana_id' => $this->input->post('sumber_dana_id') ? (int) $this->input->post('sumber_dana_id') : NULL,
			'status'         => in_array($this->input->post('status'), array('draft','final','dibayar'), TRUE) ? $this->input->post('status') : 'draft',
			'keterangan'     => $this->input->post('keterangan', TRUE),
		);

		$this->db->trans_start();
		if ($id)
		{
			$this->db->where('id', $id)->update('npd', $header);
			$this->db->where('npd_id', $id)->delete('npd_detail');
			$npd_id = $id;
		}
		else
		{
			$header['created_by'] = (int) $this->user['id'];
			$this->db->insert('npd', $header);
			$npd_id = $this->db->insert_id();
		}
		$batch = array();
		foreach ($lines as $rid => $jml)
			$batch[] = array('npd_id' => $npd_id, 'rekening_id' => $rid, 'jumlah' => $jml);
		if ($batch) $this->db->insert_batch('npd_detail', $batch);
		$this->db->trans_complete();

		$this->session->set_flashdata('success', 'NPD ' . $nomor . ' berhasil disimpan.');
		redirect('npd/view/' . $npd_id);
	}

	// ---------------- VIEW ----------------
	public function view($id)
	{
		$d = $this->load_taxed((int) $id);
		$this->render('npd/view', array(
			'row' => $d['row'], 'info' => $d['info'], 'penmap' => $d['penmap'], 'can_edit' => $this->can_edit($d['row']),
		), 'NPD ' . $d['row']->nomor_npd);
	}

	// ---------------- CETAK / DOKUMEN (2d) ----------------
	public function cetak($id)       { $this->cetak_doc((int) $id, 'npd/cetak_npd', 'NPD'); }
	public function pindah_buku($id) { $this->cetak_doc((int) $id, 'npd/cetak_pindahbuku', 'Pindah Buku'); }
	public function c5($id)          { $this->cetak_doc((int) $id, 'npd/cetak_c5', 'C5'); }

	private function cetak_doc($id, $view, $title)
	{
		$d = $this->load_taxed($id);
		$this->config->load('instansi', TRUE);
		$d['instansi'] = $this->config->item('instansi', 'instansi');
		$d['judul']    = $title;

		$fmt = strtolower((string) $this->input->get('format'));
		$d['format'] = in_array($fmt, array('excel', 'word', 'pdf'), TRUE) ? $fmt : 'html';

		if ($d['format'] === 'excel' || $d['format'] === 'word')
		{
			$slug = preg_replace('/[^A-Za-z0-9]+/', '_', $title . ' ' . $d['row']->nomor_npd);
			$slug = trim($slug, '_');
			$ext  = $d['format'] === 'excel' ? 'xls' : 'doc';
			$mime = $d['format'] === 'excel' ? 'application/vnd.ms-excel' : 'application/msword';
			$this->output
				->set_content_type($mime, 'utf-8')
				->set_header('Content-Disposition: attachment; filename="' . $slug . '.' . $ext . '"')
				->set_header('Cache-Control: max-age=0');
		}
		$this->load->view($view, $d); // layout cetak sendiri (tanpa sidebar)
	}

	/** Muat NPD lengkap + penerima (nama/NPWP live) + pajak otomatis. */
	private function load_taxed($id)
	{
		$row = $this->npd->get_full($id);
		if ( ! $row) show_404();
		if ( ! $this->can_view($row)) show_error('Akses ditolak', 403);

		$info = $this->db->select('o.nama_opd, o.singkatan, o.kepala_opd, o.nip_kepala, o.kode_opd,
		          sk.kode_subkegiatan, sk.nama_subkegiatan, k.nama_kegiatan, k.kode_kegiatan,
		          p.nama_program, sd.nama AS sumber_dana,
		          ou.nama_unit AS unit_nama, ou.jenis_unit AS unit_jenis,
		          ou.kepala AS pptk_nama, ou.nip_kepala AS pptk_nip', FALSE)
			->from('npd n')
			->join('master_opd o', 'o.id = n.opd_id', 'left')
			->join('master_opd_unit ou', 'ou.id = n.opd_unit_id', 'left')
			->join('master_subkegiatan sk', 'sk.id = n.subkegiatan_id', 'left')
			->join('master_kegiatan k', 'k.id = n.kegiatan_id', 'left')
			->join('master_program p', 'p.id = n.program_id', 'left')
			->join('master_sumber_dana sd', 'sd.id = n.sumber_dana_id', 'left')
			->where('n.id', (int) $id)->get()->row();

		$rek_of_detail = array();
		foreach ($row->details as $dd) $rek_of_detail[$dd->id] = $dd->rekening_id;

		$pen = $this->db->select('np.*,
			COALESCE(pg.nama_lengkap, mp.nama_penerima, np.nama_penerima) AS nama_live,
			pg.nip AS peg_nip, pg.golongan AS peg_gol, pg.jenis_kepegawaian AS peg_jenis,
			mp.golongan AS pen_gol, mp.nama_bank, mp.no_rekening,
			COALESCE(pgr.no_rekening, mp.no_rekening) AS norek_live,
			COALESCE(rb.nama_bank, mp.nama_bank) AS bank_live,
			COALESCE(pg.npwp, mp.npwp) AS npwp_live,
			COALESCE(rjf.nama_jabatan, rjs.nama_jabatan) AS jabatan_live,
			CASE WHEN np.pegawai_id IS NOT NULL THEN "pegawai"
			     WHEN np.penerima_id IS NOT NULL THEN "penerima" ELSE "manual" END AS sumber', FALSE)
			->from('npd_penerima np')
			->join('npd_detail nd', 'nd.id = np.npd_detail_id')
			->join('pegawai pg', 'pg.id = np.pegawai_id', 'left')
			->join('ref_jabatan rjf', 'rjf.id = pg.jabatan_fungsional_id', 'left')
			->join('ref_jabatan rjs', 'rjs.id = pg.jabatan_struktural_id', 'left')
			->join('pegawai_rekening pgr', 'pgr.pegawai_id = np.pegawai_id AND pgr.is_primary = 1', 'left')
			->join('ref_bank rb', 'rb.id = pgr.bank_id', 'left')
			->join('master_penerima mp', 'mp.id = np.penerima_id', 'left')
			->where('nd.npd_id', (int) $id)->order_by('np.id')->get()->result();

		$penmap = array();
		foreach ($pen as $p)
		{
			if ($p->sumber === 'pegawai') { $gol = $p->peg_gol; $is_pns = in_array($p->peg_jenis, array('PNS','CPNS'), TRUE); }
			elseif ($p->sumber === 'penerima') { $gol = $p->pen_gol; $is_pns = (golongan_roman($p->pen_gol) !== ''); }
			else { $gol = NULL; $is_pns = FALSE; }

			$rek = isset($rek_of_detail[$p->npd_detail_id]) ? $rek_of_detail[$p->npd_detail_id] : NULL;
			$p->pajak = $rek
				? hitung_pajak_rekening($rek, $p->jumlah, array('punya_npwp' => $p->npwp_live ? 1 : 0, 'golongan' => $gol, 'is_pns' => $is_pns))
				: array('lines' => array(), 'total_pajak' => 0, 'netto' => (float) $p->jumlah);
			$penmap[$p->npd_detail_id][] = $p;
		}

		return array(
			'row' => $row, 'info' => $info, 'penmap' => $penmap,
			'pejabat' => $this->pejabat_of($row->opd_id, $row->opd_unit_id),
		);
	}

	/**
	 * Pejabat penatausahaan OPD dari data pegawai (jabatan_penatausahaan_id -> ref_jabatan).
	 * Dicocokkan berdasar NAMA jabatan (bukan ID) agar tahan perubahan seed.
	 *   PPTK  = "PEJABAT PELAKSANA TEKNIS KEGIATAN" (utamakan unit sesuai NPD)
	 *   PPK   = "PEJABAT PENATAUSAHAAN KEUANGAN SKPD"
	 *   Bend. = "BENDAHARA PENGELUARAN" (persis, bukan pembantu)
	 *   PA    = "PENGGUNA ANGGARAN"
	 */
	private function pejabat_of($opd_id, $opd_unit_id)
	{
		$by = function ($like, $unit = NULL, $exact = NULL) use ($opd_id) {
			$this->db->select('pg.nama_lengkap, pg.nip', FALSE)->from('pegawai pg')
				->join('ref_jabatan rj', 'rj.id = pg.jabatan_penatausahaan_id')
				->where('pg.opd_id', (int) $opd_id)->where('pg.is_active', 1);
			if ($exact) $this->db->where('rj.nama_jabatan', $exact);
			else        $this->db->like('rj.nama_jabatan', $like);
			if ($unit)  $this->db->where('pg.opd_unit_id', (int) $unit);
			return $this->db->order_by('pg.id')->limit(1)->get()->row();
		};
		$pptk = $opd_unit_id ? $by('PELAKSANA TEKNIS', (int) $opd_unit_id) : NULL;
		if ( ! $pptk) $pptk = $by('PELAKSANA TEKNIS');
		return array(
			'pptk'      => $pptk,
			'ppk'       => $by('PENATAUSAHAAN KEUANGAN'),
			'bendahara' => $by(NULL, NULL, 'BENDAHARA PENGELUARAN'),
			'pa'        => $by('PENGGUNA ANGGARAN'),
		);
	}

	// ---------------- PENERIMA (2b) ----------------
	/** JSON: cari calon penerima dari PEGAWAI (data live) + master_penerima. */
	public function penerima_search()
	{
		$q = trim((string) $this->input->get('q', TRUE));
		if (strlen($q) < 2) { $this->json(array()); return; }
		$out = array();

		// Pegawai (diutamakan; bila dipilih, data ikut perubahan pegawai)
		$peg = $this->db->select('id, nama_lengkap, nip, npwp, golongan, jenis_kepegawaian', FALSE)
			->from('pegawai')
			->group_start()->like('nama_lengkap', $q)->or_like('nip', $q)->group_end()
			->where('is_active', 1)->order_by('nama_lengkap')->limit(10)->get()->result();
		foreach ($peg as $p)
			$out[] = array('source' => 'pegawai', 'id' => (int) $p->id, 'nama' => $p->nama_lengkap,
				'sub' => 'NIP ' . $p->nip . ($p->golongan ? ' · Gol ' . $p->golongan : ''),
				'npwp' => $p->npwp, 'punya_npwp' => $p->npwp ? 1 : 0);

		// Master penerima (non-pegawai / badan)
		$pen = $this->db->select('id, nama_penerima, npwp, punya_npwp, nama_bank, no_rekening', FALSE)
			->from('master_penerima')
			->group_start()->like('nama_penerima', $q)->or_like('npwp', $q)->group_end()
			->where('is_active', 1)->order_by('nama_penerima')->limit(10)->get()->result();
		foreach ($pen as $p)
			$out[] = array('source' => 'penerima', 'id' => (int) $p->id, 'nama' => $p->nama_penerima,
				'sub' => trim(($p->nama_bank ? $p->nama_bank . ' ' . $p->no_rekening : '') . ($p->npwp ? ' · NPWP ' . $p->npwp : '')),
				'npwp' => $p->npwp, 'punya_npwp' => (int) $p->punya_npwp);

		$this->json($out);
	}

	/** JSON: satu baris penerima (utk modal edit). */
	public function penerima_get($id)
	{
		$row = $this->db->get_where('npd_penerima', array('id' => (int) $id))->row();
		if ( ! $row) show_404();
		$npd = $this->npd_of_detail($row->npd_detail_id);
		if ( ! $npd || ! $this->can_edit($npd)) show_error('Akses ditolak', 403);
		$this->json($row);
	}

	public function penerima_save()
	{
		$id        = (int) $this->input->post('id');
		$detail_id = (int) $this->input->post('npd_detail_id');
		$detail = $this->db->get_where('npd_detail', array('id' => $detail_id))->row();
		if ( ! $detail) show_error('Baris NPD tidak ditemukan', 404);
		$npd = $this->npd_of_detail($detail_id);
		if ( ! $npd || ! $this->can_edit($npd)) show_error('Akses ditolak', 403);

		$nama   = trim((string) $this->input->post('nama_penerima', TRUE));
		$volume = (float) str_replace('.', '', (string) $this->input->post('volume')); if ($volume <= 0) $volume = 1;
		$harga  = (float) str_replace('.', '', (string) $this->input->post('harga_satuan'));
		$jumlah = round($volume * $harga, 2);

		$errors = array();
		if ($nama === '') $errors[] = 'Nama penerima wajib diisi.';
		if ($jumlah <= 0) $errors[] = 'Nominal (volume × harga satuan) harus lebih dari 0.';

		$sum_other = (float) $this->db->select('COALESCE(SUM(jumlah),0) AS s', FALSE)
			->from('npd_penerima')->where('npd_detail_id', $detail_id)
			->where('id !=', $id ?: 0)->get()->row()->s;
		if ($jumlah + $sum_other > (float) $detail->jumlah + 0.001)
			$errors[] = 'Total penerima (' . rupiah($jumlah + $sum_other) . ') melebihi jumlah baris rekening (' . rupiah($detail->jumlah) . ').';

		if ($errors)
		{
			$this->session->set_flashdata('error', implode(' ', $errors));
			redirect('npd/view/' . $npd->id);
		}

		$pegid = $this->input->post('pegawai_id') ? (int) $this->input->post('pegawai_id') : NULL;
		$penid = $this->ensure_penerima($pegid, $this->input->post('penerima_id') ? (int) $this->input->post('penerima_id') : NULL, $nama);
		$data = array(
			'npd_detail_id' => $detail_id,
			'pegawai_id'    => $pegid,
			'penerima_id'   => $penid,
			'nama_penerima' => $nama,
			'uraian'        => $this->input->post('uraian', TRUE),
			'volume'        => $volume,
			'harga_satuan'  => $harga,
			'jumlah'        => $jumlah,
			'keterangan'    => $this->input->post('keterangan', TRUE),
		);
		if ($id) $this->db->where('id', $id)->update('npd_penerima', $data);
		else $this->db->insert('npd_penerima', $data);
		$this->session->set_flashdata('success', 'Penerima berhasil disimpan.');
		redirect('npd/view/' . $npd->id);
	}

	/** Tambah BANYAK penerima sekaligus (dari modal multi-baris). */
	public function penerima_batch()
	{
		$detail_id = (int) $this->input->post('npd_detail_id');
		$detail = $this->db->get_where('npd_detail', array('id' => $detail_id))->row();
		if ( ! $detail) show_error('Baris NPD tidak ditemukan', 404);
		$npd = $this->npd_of_detail($detail_id);
		if ( ! $npd || ! $this->can_edit($npd)) show_error('Akses ditolak', 403);

		$nama_a = (array) $this->input->post('nama_penerima');
		$peg_a  = (array) $this->input->post('pegawai_id');
		$pen_a  = (array) $this->input->post('penerima_id');
		$ur_a   = (array) $this->input->post('uraian');
		$vol_a  = (array) $this->input->post('volume');
		$hrg_a  = (array) $this->input->post('harga_satuan');
		$ket_a  = (array) $this->input->post('keterangan');

		$rows = array(); $sum_new = 0; $errors = array();
		foreach ($nama_a as $i => $nm)
		{
			$nm  = trim((string) $nm);
			$vol = (float) str_replace('.', '', (string) ($vol_a[$i] ?? 1)); if ($vol <= 0) $vol = 1;
			$hrg = (float) str_replace('.', '', (string) ($hrg_a[$i] ?? 0));
			$jml = round($vol * $hrg, 2);
			if ($nm === '' && $jml <= 0) continue; // baris kosong -> lewati
			if ($nm === '') { $errors[] = 'Ada baris tanpa nama penerima.'; continue; }
			if ($jml <= 0) { $errors[] = 'Nominal "' . $nm . '" harus > 0.'; continue; }
			$pegid = ! empty($peg_a[$i]) ? (int) $peg_a[$i] : NULL;
			$penid = $this->ensure_penerima($pegid, ! empty($pen_a[$i]) ? (int) $pen_a[$i] : NULL, $nm);
			$rows[] = array(
				'npd_detail_id' => $detail_id,
				'pegawai_id'    => $pegid,
				'penerima_id'   => $penid,
				'nama_penerima' => $nm,
				'uraian'        => trim((string) ($ur_a[$i] ?? '')),
				'volume'        => $vol,
				'harga_satuan'  => $hrg,
				'jumlah'        => $jml,
				'keterangan'    => trim((string) ($ket_a[$i] ?? '')),
			);
			$sum_new += $jml;
		}
		if (empty($rows) && empty($errors)) $errors[] = 'Tidak ada penerima untuk disimpan.';

		$sum_exist = (float) $this->db->select('COALESCE(SUM(jumlah),0) AS s', FALSE)
			->from('npd_penerima')->where('npd_detail_id', $detail_id)->get()->row()->s;
		if ($sum_new + $sum_exist > (float) $detail->jumlah + 0.001)
			$errors[] = 'Total penerima (' . rupiah($sum_new + $sum_exist) . ') melebihi jumlah baris rekening (' . rupiah($detail->jumlah) . ').';

		if ($errors)
		{
			$this->session->set_flashdata('error', implode(' ', $errors));
			redirect('npd/view/' . $npd->id);
		}

		$this->db->insert_batch('npd_penerima', $rows);
		$this->session->set_flashdata('success', count($rows) . ' penerima ditambahkan.');
		redirect('npd/view/' . $npd->id);
	}

	/**
	 * Pastikan penerima tercatat di master_penerima; kembalikan penerima_id.
	 * - penerima_id sudah ada  -> pakai (sudah dari master).
	 * - dari pegawai           -> cari/buat master_penerima ber-pegawai_id (dedup by pegawai_id).
	 * - manual (nama saja)     -> cari/buat by nama, pegawai_id NULL (dedup by nama).
	 * Mencegah penerima yang sama masuk lebih dari sekali.
	 */
	private function ensure_penerima($pegawai_id, $penerima_id, $nama)
	{
		if ($penerima_id) return (int) $penerima_id;

		if ($pegawai_id)
		{
			$ex = $this->db->select('id')->get_where('master_penerima', array('pegawai_id' => (int) $pegawai_id))->row();
			if ($ex) return (int) $ex->id;

			$pg = $this->db->select('nama_lengkap, npwp, golongan, status_kepegawaian')
				->get_where('pegawai', array('id' => (int) $pegawai_id))->row();
			if ($pg)
			{
				$bank = $this->db->select('rb.nama_bank, pr.no_rekening, pr.nama_pemilik_rekening', FALSE)
					->from('pegawai_rekening pr')->join('ref_bank rb', 'rb.id = pr.bank_id', 'left')
					->where('pr.pegawai_id', (int) $pegawai_id)->where('pr.is_primary', 1)
					->limit(1)->get()->row();
				$this->db->insert('master_penerima', array(
					'pegawai_id'     => (int) $pegawai_id,
					'nama_penerima'  => $pg->nama_lengkap,
					'jenis_penerima' => $pg->status_kepegawaian === 'ASN' ? 'asn' : 'non_asn',
					'punya_npwp'     => $pg->npwp ? 1 : 0,
					'npwp'           => $pg->npwp,
					'golongan'       => $this->pen_golongan($pg->golongan),
					'nama_bank'      => $bank ? $bank->nama_bank : NULL,
					'no_rekening'    => $bank ? $bank->no_rekening : NULL,
					'nama_rekening'  => ($bank && $bank->nama_pemilik_rekening) ? $bank->nama_pemilik_rekening : $pg->nama_lengkap,
					'is_active'      => 1,
				));
				return (int) $this->db->insert_id();
			}
		}

		$nm = trim((string) $nama);
		if ($nm === '') return NULL;
		$ex = $this->db->select('id')->from('master_penerima')
			->where('pegawai_id IS NULL', NULL, FALSE)
			->where('LOWER(nama_penerima)', strtolower($nm))
			->limit(1)->get()->row();
		if ($ex) return (int) $ex->id;
		$this->db->insert('master_penerima', array(
			'nama_penerima'  => $nm,
			'jenis_penerima' => 'non_asn',
			'punya_npwp'     => 0,
			'is_active'      => 1,
		));
		return (int) $this->db->insert_id();
	}

	/** Peta golongan pegawai (III/a, IX, dst.) -> enum master_penerima (I..IV) atau NULL. */
	private function pen_golongan($g)
	{
		$g = strtoupper(trim((string) $g));
		if ($g === '') return NULL;
		$base = (strpos($g, '/') !== FALSE) ? substr($g, 0, strpos($g, '/')) : $g;
		return in_array($base, array('I', 'II', 'III', 'IV'), TRUE) ? $base : NULL;
	}

	public function penerima_delete()
	{
		$id  = (int) $this->input->post('id');
		$row = $this->db->get_where('npd_penerima', array('id' => $id))->row();
		if ($row)
		{
			$npd = $this->npd_of_detail($row->npd_detail_id);
			if ($npd && $this->can_edit($npd))
			{
				$this->db->where('id', $id)->delete('npd_penerima');
				$this->session->set_flashdata('success', 'Penerima dihapus.');
				redirect('npd/view/' . $npd->id);
			}
		}
		show_error('Akses ditolak', 403);
	}

	private function npd_of_detail($detail_id)
	{
		return $this->db->select('n.*')->from('npd_detail d')
			->join('npd n', 'n.id = d.npd_id')->where('d.id', (int) $detail_id)->get()->row();
	}

	// ---------------- DELETE ----------------
	public function delete()
	{
		$id  = (int) $this->input->post('id');
		$row = $this->npd->get_full($id);
		if ( ! $row || ! $this->can_edit($row)) show_error('Akses ditolak', 403);
		$this->db->where('id', $id)->delete('npd'); // detail ikut terhapus (FK cascade)
		$this->session->set_flashdata('success', 'NPD dihapus.');
		redirect('npd');
	}

	// ---------------- otorisasi ----------------
	private function can_view($row)
	{
		if (is_super()) return TRUE;
		if (current_role() === 'admin_opd') return (int) $row->opd_id === (int) scope_opd_id();
		$ids = scope_subkegiatan_ids();
		return is_array($ids) && in_array((int) $row->subkegiatan_id, $ids, TRUE);
	}
	private function can_edit($row) { return $this->can_view($row); }

	private function subkeg_allowed($sub)
	{
		$ids = scope_subkegiatan_ids();
		if ($ids === NULL) return TRUE; // super
		return in_array((int) $sub, $ids, TRUE);
	}

	private function json($arr) { $this->output->set_content_type('application/json')->set_output(json_encode($arr)); }
}
