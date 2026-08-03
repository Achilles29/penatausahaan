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
		$c = array();
		if (is_super())
			$c[] = array('name' => 'm.opd_id', 'label' => 'OPD', 'source' => 'opd', 'opturl' => site_url('master/options/opd'));
		$c[] = array('name' => 'm.program_id', 'label' => 'Program', 'source' => 'program', 'opturl' => site_url('master/options/program'));
		$c[] = array('name' => 'm.kegiatan_id', 'label' => 'Kegiatan', 'source' => 'kegiatan', 'opturl' => site_url('master/options/kegiatan'));
		$c[] = array('name' => 'm.subkegiatan_id', 'label' => 'Sub Kegiatan', 'source' => 'subkegiatan', 'opturl' => site_url('master/options/subkegiatan'));
		return $c;
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

	/** JSON: subkegiatan ber-DPA utk OPD (scoped). */
	public function subkegiatan_options()
	{
		$opd_id = $this->eff_opd();
		if ( ! $opd_id) { $this->json(array()); return; }
		$scope = is_super() ? NULL : scope_subkegiatan_ids();
		$rows = $this->npd->subkegiatan_with_dpa($opd_id, $scope);
		$out = array();
		foreach ($rows as $r)
			$out[] = array('id' => $r->id, 'label' => $r->kode_subkegiatan . ' — ' . $r->nama_subkegiatan);
		$this->json($out);
	}

	/** JSON: rekening + sisa anggaran utk subkegiatan. */
	public function rekening_sisa()
	{
		$opd_id = $this->eff_opd();
		$sub    = (int) $this->input->get('subkegiatan_id');
		$exclude = $this->input->get('npd_id') ? (int) $this->input->get('npd_id') : NULL;
		if ( ! $opd_id || ! $sub) { $this->json(array()); return; }
		if ( ! is_super() && ! $this->subkeg_allowed($sub)) { $this->json(array()); return; }
		$this->json($this->npd->rekening_sisa($opd_id, $sub, $exclude));
	}

	/** JSON: nomor NPD berikutnya. */
	public function next_nomor()
	{
		$opd_id = $this->eff_opd();
		$tahun  = (int) ($this->input->get('tahun') ?: date('Y'));
		if ( ! $opd_id) { $this->json(array('nomor' => '')); return; }
		$this->json(array('nomor' => $this->npd->next_nomor($opd_id, $tahun)));
	}

	// ---------------- SAVE ----------------
	public function save()
	{
		$id     = (int) $this->input->post('id');
		$opd_id = $this->eff_opd();
		$sub    = (int) $this->input->post('subkegiatan_id');
		$errors = array();

		if ( ! $opd_id) $errors[] = 'OPD wajib dipilih.';
		if ( ! $sub)    $errors[] = 'Sub kegiatan wajib dipilih.';

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

		// validasi sisa
		if ($sub && $opd_id && $lines)
		{
			$pagu = $this->npd->pagu_map($opd_id, $sub);
			$real = $this->npd->realisasi_map($opd_id, $sub, $id ?: NULL);
			foreach ($lines as $rid => $jml)
			{
				if ( ! isset($pagu[$rid])) { $errors[] = "Rekening #$rid tidak ada di DPA subkegiatan ini."; continue; }
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
		if ($nomor === '') $nomor = $this->npd->next_nomor($opd_id, (int) date('Y', strtotime($tanggal)));

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
	public function cetak($id)       { $this->cetak_doc((int) $id, 'npd/cetak_npd', 'Cetak NPD'); }
	public function pindah_buku($id) { $this->cetak_doc((int) $id, 'npd/cetak_pindahbuku', 'Pindah Buku'); }
	public function c5($id)          { $this->cetak_doc((int) $id, 'npd/cetak_c5', 'C5'); }

	private function cetak_doc($id, $view, $title)
	{
		$d = $this->load_taxed($id);
		$d['judul'] = $title;
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
		          p.nama_program, sd.nama AS sumber_dana', FALSE)
			->from('npd n')
			->join('master_opd o', 'o.id = n.opd_id', 'left')
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
			COALESCE(pg.npwp, mp.npwp) AS npwp_live,
			CASE WHEN np.pegawai_id IS NOT NULL THEN "pegawai"
			     WHEN np.penerima_id IS NOT NULL THEN "penerima" ELSE "manual" END AS sumber', FALSE)
			->from('npd_penerima np')
			->join('npd_detail nd', 'nd.id = np.npd_detail_id')
			->join('pegawai pg', 'pg.id = np.pegawai_id', 'left')
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

		return array('row' => $row, 'info' => $info, 'penmap' => $penmap);
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

		$data = array(
			'npd_detail_id' => $detail_id,
			'pegawai_id'    => $this->input->post('pegawai_id') ? (int) $this->input->post('pegawai_id') : NULL,
			'penerima_id'   => $this->input->post('penerima_id') ? (int) $this->input->post('penerima_id') : NULL,
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
