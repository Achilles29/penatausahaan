<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Skema Pajak: kelola skema (header) + ATURAN detailnya.
 * Menampilkan besaran pajak per aturan — tarif bisa berbeda tergantung
 * batas nilai pembayaran, status NPWP penerima, dan golongan.
 */
class Skema_pajak extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Master_model', 'mm');
	}

	private function can_manage() { return is_super(); }

	public function index()
	{
		$schemes = $this->db->order_by('kategori')->order_by('id')->get('master_skema_pajak')->result();
		foreach ($schemes as $s)
		{
			$s->details = $this->db->order_by('id')->get_where('master_skema_pajak_detail', array('skema_id' => $s->id))->result();
		}

		$this->render('skema_pajak/index', array(
			'schemes'    => $schemes,
			'can_manage' => $this->can_manage(),
			'kategori_opts' => $this->kategori_opts(),
		), 'Skema Pajak');
	}

	// ---------- Header skema ----------
	public function save_skema()
	{
		if ( ! $this->can_manage()) show_error('Akses ditolak', 403);
		$id = (int) $this->input->post('id');
		$kode = $this->input->post('kode_skema', TRUE);
		$data = array(
			'kode_skema' => $kode,
			'nama_skema' => $this->input->post('nama_skema', TRUE),
			'kategori'   => $this->input->post('kategori', TRUE),
			'keterangan' => $this->input->post('keterangan', TRUE),
			'is_active'  => $this->input->post('is_active') ? 1 : 0,
		);
		$err = array();
		if ( ! $data['kode_skema']) $err[] = 'Kode skema wajib.';
		if ( ! $data['nama_skema']) $err[] = 'Nama skema wajib.';
		if ( ! $data['kategori']) $err[] = 'Kategori wajib.';
		if ($data['kode_skema'] && ! $this->mm->is_unique_value('master_skema_pajak', 'kode_skema', $data['kode_skema'], $id ?: NULL))
			$err[] = 'Kode skema sudah dipakai.';

		if ($err) { $this->session->set_flashdata('error', implode(' ', $err)); redirect('skema_pajak'); }

		if ($id > 0) { $this->mm->update('master_skema_pajak', $id, $data); $m = 'Skema diperbarui.'; }
		else { $this->mm->insert('master_skema_pajak', $data); $m = 'Skema ditambahkan.'; }
		$this->session->set_flashdata('success', $m);
		redirect('skema_pajak');
	}

	public function delete_skema()
	{
		if ( ! $this->can_manage()) show_error('Akses ditolak', 403);
		$id = (int) $this->input->post('id');
		$this->mm->delete('master_skema_pajak', $id); // detail terhapus via ON DELETE CASCADE
		$this->session->set_flashdata('success', 'Skema (beserta aturannya) dihapus.');
		redirect('skema_pajak');
	}

	public function get_skema($id)
	{
		if ( ! $this->can_manage()) show_error('Akses ditolak', 403);
		$row = $this->mm->get_row('master_skema_pajak', (int) $id);
		if ( ! $row) show_404();
		$this->output->set_content_type('application/json')->set_output(json_encode($row));
	}

	// ---------- Aturan (detail) ----------
	public function save_detail()
	{
		if ( ! $this->can_manage()) show_error('Akses ditolak', 403);
		$id = (int) $this->input->post('id');
		$skema_id = (int) $this->input->post('skema_id');
		if ( ! $skema_id) show_error('Skema tidak valid', 400);

		$npwp = $this->input->post('punya_npwp');
		$data = array(
			'skema_id'           => $skema_id,
			'jenis_pajak'        => $this->input->post('jenis_pajak', TRUE),
			'tarif'              => (float) $this->input->post('tarif'),
			'batas_min'          => $this->input->post('batas_min') !== '' ? (float) $this->input->post('batas_min') : 0,
			'batas_max'          => $this->input->post('batas_max') !== '' ? (float) $this->input->post('batas_max') : NULL,
			'punya_npwp'         => ($npwp === '' || $npwp === NULL) ? NULL : (int) $npwp,
			'golongan_honor'     => $this->input->post('golongan_honor', TRUE) ?: NULL,
			'basis_penghitungan' => $this->input->post('basis_penghitungan', TRUE) ?: 'langsung',
			'kelompok'           => $this->input->post('kelompok', TRUE) ?: 'opsional',
			'rumus'              => $this->input->post('rumus', TRUE) ?: '',
			'keterangan'         => $this->input->post('keterangan', TRUE),
		);

		if ($id > 0) { $this->mm->update('master_skema_pajak_detail', $id, $data); $m = 'Aturan pajak diperbarui.'; }
		else { $this->mm->insert('master_skema_pajak_detail', $data); $m = 'Aturan pajak ditambahkan.'; }
		$this->session->set_flashdata('success', $m);
		redirect('skema_pajak');
	}

	public function delete_detail()
	{
		if ( ! $this->can_manage()) show_error('Akses ditolak', 403);
		$this->mm->delete('master_skema_pajak_detail', (int) $this->input->post('id'));
		$this->session->set_flashdata('success', 'Aturan pajak dihapus.');
		redirect('skema_pajak');
	}

	public function get_detail($id)
	{
		if ( ! $this->can_manage()) show_error('Akses ditolak', 403);
		$row = $this->mm->get_row('master_skema_pajak_detail', (int) $id);
		if ( ! $row) show_404();
		$this->output->set_content_type('application/json')->set_output(json_encode($row));
	}

	private function kategori_opts()
	{
		return array(
			'honorarium' => 'Honorarium', 'barang' => 'Barang', 'jasa' => 'Jasa',
			'jasa_boga' => 'Jasa Boga/Katering', 'makan_minum' => 'Makan & Minum',
			'sewa' => 'Sewa', 'konstruksi' => 'Konstruksi', 'modal' => 'Modal',
			'perjalanan_dinas' => 'Perjalanan Dinas', 'pegawai' => 'Pegawai/Gaji',
			'non_pajak' => 'Non Pajak', 'lainnya' => 'Lainnya',
		);
	}
}
