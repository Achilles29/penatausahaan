<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Master data — engine CRUD generik berbasis registry entitas.
 * URL:
 *   master/<entity>            -> index (halaman)
 *   master/data/<entity>       -> JSON DataTables server-side
 *   master/get/<entity>/<id>   -> JSON satu baris (untuk modal edit)
 *   master/save/<entity>       -> simpan (POST)
 *   master/delete/<entity>     -> hapus (POST id)
 *   master/options/<entity>    -> JSON opsi cascading (?parent=..)
 */
class Master extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Master_model', 'mm');
	}

	// ================= REGISTRY ENTITAS =================
	private function registry($key = NULL)
	{
		$reg = array(

			'urusan' => array(
				'title' => 'Urusan', 'table' => 'master_urusan', 'from' => 'master_urusan m', 'alias' => 'm',
				'select' => 'm.id, m.kode_urusan, m.nama_urusan',
				'searchable' => array('m.kode_urusan', 'm.nama_urusan'),
				'order_by' => 'm.kode_urusan',
				'columns' => array(
					array('field' => 'kode_urusan', 'label' => 'Kode', 'order' => 'm.kode_urusan', 'width' => '120px'),
					array('field' => 'nama_urusan', 'label' => 'Nama Urusan', 'order' => 'm.nama_urusan'),
				),
				'fields' => array(
					array('name' => 'kode_urusan', 'label' => 'Kode Urusan', 'type' => 'text', 'required' => TRUE, 'unique' => TRUE),
					array('name' => 'nama_urusan', 'label' => 'Nama Urusan', 'type' => 'text', 'required' => TRUE),
				),
				'manage' => array('superadmin'),
			),

			'bidang' => array(
				'title' => 'Bidang Urusan', 'table' => 'master_bidang', 'from' => 'master_bidang m', 'alias' => 'm',
				'select' => 'm.id, m.kode_bidang, m.nama_bidang, u.nama_urusan AS urusan_nama',
				'joins' => array(array('master_urusan u', 'u.id = m.urusan_id')),
				'searchable' => array('m.kode_bidang', 'm.nama_bidang', 'u.nama_urusan'),
				'order_by' => 'm.kode_bidang',
				'columns' => array(
					array('field' => 'kode_bidang', 'label' => 'Kode', 'order' => 'm.kode_bidang', 'width' => '100px'),
					array('field' => 'nama_bidang', 'label' => 'Nama Bidang'),
					array('field' => 'urusan_nama', 'label' => 'Urusan', 'order' => 'u.nama_urusan'),
				),
				'filters' => array(
					array('name' => 'm.urusan_id', 'label' => 'Urusan', 'source' => 'urusan'),
				),
				'fields' => array(
					array('name' => 'urusan_id', 'label' => 'Urusan', 'type' => 'select', 'source' => 'urusan', 'required' => TRUE),
					array('name' => 'kode_bidang', 'label' => 'Kode Bidang', 'type' => 'text', 'required' => TRUE),
					array('name' => 'nama_bidang', 'label' => 'Nama Bidang', 'type' => 'text', 'required' => TRUE),
				),
				'manage' => array('superadmin'),
			),

			'program' => array(
				'title' => 'Program', 'table' => 'master_program', 'from' => 'master_program m', 'alias' => 'm',
				'select' => 'm.id, m.kode_program, m.nama_program, b.nama_bidang AS bidang_nama',
				'joins' => array(array('master_bidang b', 'b.id = m.bidang_id')),
				'searchable' => array('m.kode_program', 'm.nama_program'),
				'order_by' => 'm.kode_program',
				'columns' => array(
					array('field' => 'kode_program', 'label' => 'Kode', 'order' => 'm.kode_program', 'width' => '110px'),
					array('field' => 'nama_program', 'label' => 'Nama Program'),
					array('field' => 'bidang_nama', 'label' => 'Bidang', 'order' => 'b.nama_bidang'),
				),
				'filters' => array(
					array('name' => 'b.urusan_id', 'label' => 'Urusan', 'source' => 'urusan'),
					array('name' => 'm.bidang_id', 'label' => 'Bidang', 'source' => 'bidang'),
				),
				'fields' => array(
					array('name' => 'bidang_id', 'label' => 'Bidang Urusan', 'type' => 'select', 'source' => 'bidang', 'required' => TRUE),
					array('name' => 'kode_program', 'label' => 'Kode Program', 'type' => 'text', 'required' => TRUE),
					array('name' => 'nama_program', 'label' => 'Nama Program', 'type' => 'text', 'required' => TRUE),
				),
				'manage' => array('superadmin'),
			),

			'kegiatan' => array(
				'title' => 'Kegiatan', 'table' => 'master_kegiatan', 'from' => 'master_kegiatan m', 'alias' => 'm',
				'select' => 'm.id, m.kode_kegiatan, m.nama_kegiatan, p.nama_program AS program_nama',
				'joins' => array(
					array('master_program p', 'p.id = m.program_id'),
					array('master_bidang b', 'b.id = p.bidang_id'),
				),
				'searchable' => array('m.kode_kegiatan', 'm.nama_kegiatan'),
				'order_by' => 'm.kode_kegiatan',
				'columns' => array(
					array('field' => 'kode_kegiatan', 'label' => 'Kode', 'order' => 'm.kode_kegiatan', 'width' => '130px'),
					array('field' => 'nama_kegiatan', 'label' => 'Nama Kegiatan'),
					array('field' => 'program_nama', 'label' => 'Program', 'order' => 'p.nama_program'),
				),
				'filters' => array(
					array('name' => 'b.urusan_id', 'label' => 'Urusan', 'source' => 'urusan'),
					array('name' => 'p.bidang_id', 'label' => 'Bidang', 'source' => 'bidang'),
					array('name' => 'm.program_id', 'label' => 'Program', 'source' => 'program'),
				),
				'fields' => array(
					array('name' => 'program_id', 'label' => 'Program', 'type' => 'select', 'source' => 'program', 'required' => TRUE),
					array('name' => 'kode_kegiatan', 'label' => 'Kode Kegiatan', 'type' => 'text', 'required' => TRUE),
					array('name' => 'nama_kegiatan', 'label' => 'Nama Kegiatan', 'type' => 'text', 'required' => TRUE),
				),
				'manage' => array('superadmin'),
			),

			'subkegiatan' => array(
				'title' => 'Sub Kegiatan', 'table' => 'master_subkegiatan', 'from' => 'master_subkegiatan m', 'alias' => 'm',
				'select' => 'm.id, m.kode_subkegiatan, m.nama_subkegiatan, k.nama_kegiatan AS kegiatan_nama',
				'joins' => array(
					array('master_kegiatan k', 'k.id = m.kegiatan_id'),
					array('master_program p', 'p.id = k.program_id'),
					array('master_bidang b', 'b.id = p.bidang_id'),
				),
				'searchable' => array('m.kode_subkegiatan', 'm.nama_subkegiatan'),
				'order_by' => 'm.kode_subkegiatan',
				'columns' => array(
					array('field' => 'kode_subkegiatan', 'label' => 'Kode', 'order' => 'm.kode_subkegiatan', 'width' => '150px'),
					array('field' => 'nama_subkegiatan', 'label' => 'Nama Sub Kegiatan'),
					array('field' => 'kegiatan_nama', 'label' => 'Kegiatan', 'order' => 'k.nama_kegiatan'),
				),
				'filters' => array(
					array('name' => 'b.urusan_id', 'label' => 'Urusan', 'source' => 'urusan'),
					array('name' => 'p.bidang_id', 'label' => 'Bidang', 'source' => 'bidang'),
					array('name' => 'k.program_id', 'label' => 'Program', 'source' => 'program'),
					array('name' => 'm.kegiatan_id', 'label' => 'Kegiatan', 'source' => 'kegiatan'),
				),
				'fields' => array(
					array('name' => 'kegiatan_id', 'label' => 'Kegiatan', 'type' => 'select', 'source' => 'kegiatan', 'required' => TRUE),
					array('name' => 'kode_subkegiatan', 'label' => 'Kode Sub Kegiatan', 'type' => 'text', 'required' => TRUE),
					array('name' => 'nama_subkegiatan', 'label' => 'Nama Sub Kegiatan', 'type' => 'textarea', 'required' => TRUE),
				),
				'manage' => array('superadmin'),
			),

			'rekening' => array(
				'title' => 'Rekening (Kode Rekening)', 'table' => 'master_rekening', 'from' => 'master_rekening m', 'alias' => 'm',
				'select' => 'm.id, m.kode_rekening, m.uraian, m.kategori_pajak',
				'searchable' => array('m.kode_rekening', 'm.uraian'),
				'order_by' => 'm.kode_rekening',
				'columns' => array(
					array('field' => 'kode_rekening', 'label' => 'Kode Rekening', 'order' => 'm.kode_rekening', 'width' => '200px'),
					array('field' => 'uraian', 'label' => 'Uraian'),
					array('field' => 'kategori_pajak', 'label' => 'Kategori Pajak', 'render' => 'badge', 'order' => 'm.kategori_pajak', 'width' => '150px'),
				),
				'filters' => array(
					array('name' => 'm.kategori_pajak', 'label' => 'Kategori Pajak', 'options' => $this->kategori_pajak_options()),
				),
				'fields' => array(
					array('name' => 'kode_rekening', 'label' => 'Kode Rekening', 'type' => 'text', 'required' => TRUE, 'unique' => TRUE),
					array('name' => 'uraian', 'label' => 'Uraian', 'type' => 'textarea', 'required' => TRUE),
					array('name' => 'kategori_pajak', 'label' => 'Kategori Pajak', 'type' => 'enum', 'options' => $this->kategori_pajak_options()),
					array('name' => 'jenis_belanja', 'label' => 'Jenis Belanja (opsional)', 'type' => 'text'),
				),
				'manage' => array('superadmin'),
			),

			'sumber_dana' => array(
				'title' => 'Sumber Dana', 'table' => 'master_sumber_dana', 'from' => 'master_sumber_dana m', 'alias' => 'm',
				'select' => 'm.id, m.kode, m.nama, m.is_active',
				'searchable' => array('m.kode', 'm.nama'),
				'order_by' => 'm.kode',
				'columns' => array(
					array('field' => 'kode', 'label' => 'Kode', 'order' => 'm.kode', 'width' => '150px'),
					array('field' => 'nama', 'label' => 'Nama Sumber Dana'),
					array('field' => 'is_active', 'label' => 'Status', 'render' => 'active', 'width' => '110px'),
				),
				'fields' => array(
					array('name' => 'kode', 'label' => 'Kode', 'type' => 'text', 'required' => TRUE, 'unique' => TRUE),
					array('name' => 'nama', 'label' => 'Nama', 'type' => 'text', 'required' => TRUE),
					array('name' => 'keterangan', 'label' => 'Keterangan', 'type' => 'textarea'),
					array('name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox', 'default' => 1),
				),
				'manage' => array('superadmin'),
			),

			'opd' => array(
				'title' => 'OPD', 'table' => 'master_opd', 'from' => 'master_opd m', 'alias' => 'm',
				'select' => 'm.id, m.kode_opd, m.nama_opd, m.singkatan, m.kepala_opd, m.is_active',
				'searchable' => array('m.kode_opd', 'm.nama_opd', 'm.singkatan'),
				'order_by' => 'm.kode_opd',
				'columns' => array(
					array('field' => 'kode_opd', 'label' => 'Kode', 'order' => 'm.kode_opd', 'width' => '190px'),
					array('field' => 'nama_opd', 'label' => 'Nama OPD'),
					array('field' => 'singkatan', 'label' => 'Singkatan', 'width' => '110px'),
					array('field' => 'is_active', 'label' => 'Status', 'render' => 'active', 'width' => '100px'),
				),
				'fields' => array(
					array('name' => 'kode_opd', 'label' => 'Kode OPD', 'type' => 'text', 'required' => TRUE, 'unique' => TRUE),
					array('name' => 'nama_opd', 'label' => 'Nama OPD', 'type' => 'text', 'required' => TRUE),
					array('name' => 'singkatan', 'label' => 'Singkatan', 'type' => 'text'),
					array('name' => 'kepala_opd', 'label' => 'Kepala OPD', 'type' => 'text'),
					array('name' => 'nip_kepala', 'label' => 'NIP Kepala', 'type' => 'text'),
					array('name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox', 'default' => 1),
				),
				'manage' => array('superadmin'),
			),

			'opd_unit' => array(
				'title' => 'Unit OPD', 'table' => 'master_opd_unit', 'from' => 'master_opd_unit m', 'alias' => 'm',
				'select' => 'm.id, o.nama_opd AS opd_nama, m.kode_unit, m.nama_unit, m.jenis_unit, m.kepala',
				'joins' => array(array('master_opd o', 'o.id = m.opd_id')),
				'searchable' => array('m.nama_unit', 'm.kode_unit', 'o.nama_opd'),
				'order_by' => 'o.kode_opd, m.nama_unit',
				'columns' => array(
					array('field' => 'opd_nama', 'label' => 'OPD', 'order' => 'o.kode_opd'),
					array('field' => 'nama_unit', 'label' => 'Unit', 'order' => 'm.nama_unit'),
					array('field' => 'jenis_unit', 'label' => 'Jenis', 'render' => 'badge', 'width' => '130px'),
					array('field' => 'kepala', 'label' => 'Kepala'),
				),
				'filters' => array(
					array('name' => 'm.opd_id', 'label' => 'OPD', 'source' => 'opd'),
				),
				'fields' => array(
					array('name' => 'opd_id', 'label' => 'OPD', 'type' => 'select', 'source' => 'opd', 'required' => TRUE),
					array('name' => 'kode_unit', 'label' => 'Kode Unit', 'type' => 'text'),
					array('name' => 'nama_unit', 'label' => 'Nama Unit', 'type' => 'text', 'required' => TRUE),
					array('name' => 'jenis_unit', 'label' => 'Jenis Unit', 'type' => 'enum', 'options' => array('sekretariat'=>'Sekretariat','bidang'=>'Bidang','uptd'=>'UPTD','lainnya'=>'Lainnya'), 'default' => 'bidang', 'required' => TRUE),
					array('name' => 'kepala', 'label' => 'Kepala Unit', 'type' => 'text'),
					array('name' => 'nip_kepala', 'label' => 'NIP Kepala', 'type' => 'text'),
				),
				'manage' => array('superadmin', 'admin_opd'),
				'scope_col' => 'm.opd_id',
				'save_scope_col' => 'opd_id',
			),

			'pegawai' => array(
				'title' => 'Pegawai', 'table' => 'pegawai', 'from' => 'pegawai m', 'alias' => 'm',
				'select' => 'm.id, m.nama_lengkap, m.jenis_kepegawaian, m.nip, m.golongan, m.pangkat,'
					. ' o.nama_opd AS opd_nama, o.kode_opd,'
					. ' (SELECT rj.nama_jabatan FROM pegawai_jabatan pj'
					. '  JOIN ref_jabatan rj ON rj.id = pj.jabatan_id'
					. '  WHERE pj.pegawai_id = m.id AND pj.is_active = 1'
					. '  ORDER BY pj.id DESC LIMIT 1) AS jabatan_nama',
				'joins' => array(array('master_opd o', 'o.id = m.opd_id')),
				'searchable' => array('m.nama_lengkap', 'm.nip', 'm.pangkat'),
				'order_by' => 'o.kode_opd, m.nama_lengkap',
				'columns' => array(
					array('field' => 'nama_lengkap', 'label' => 'Nama', 'order' => 'm.nama_lengkap'),
					array('field' => 'nip', 'label' => 'NIP', 'width' => '190px'),
					array('field' => 'golongan', 'label' => 'Gol.', 'width' => '60px'),
					array('field' => 'jenis_kepegawaian', 'label' => 'Jenis', 'render' => 'badge', 'width' => '80px'),
					array('field' => 'jabatan_nama', 'label' => 'Jabatan'),
					array('field' => 'opd_nama', 'label' => 'OPD', 'order' => 'o.kode_opd'),
				),
				'filters' => array(
					array('name' => 'm.jenis_kepegawaian', 'label' => 'Jenis', 'options' => array('PNS'=>'PNS','PPPK'=>'PPPK','NON_ASN'=>'Non ASN')),
					array('name' => 'm.opd_id', 'label' => 'OPD', 'source' => 'opd'),
				),
				'fields' => array(
					array('name' => 'nama_lengkap', 'label' => 'Nama Lengkap', 'type' => 'text', 'required' => TRUE),
					array('name' => 'jenis_kepegawaian', 'label' => 'Jenis Kepegawaian', 'type' => 'enum',
						'options' => array('PNS'=>'PNS (Pegawai Negeri Sipil)','PPPK'=>'PPPK (Pegawai Pemerintah Dengan Perjanjian Kerja)','NON_ASN'=>'Non ASN'), 'required' => TRUE),
					array('name' => 'nip', 'label' => 'NIP / NI PPPK', 'type' => 'text'),
					array('name' => 'golongan', 'label' => 'Golongan', 'type' => 'select',
						'options' => array(
							'I/a'=>'I/a','I/b'=>'I/b','I/c'=>'I/c','I/d'=>'I/d',
							'II/a'=>'II/a','II/b'=>'II/b','II/c'=>'II/c','II/d'=>'II/d',
							'III/a'=>'III/a','III/b'=>'III/b','III/c'=>'III/c','III/d'=>'III/d',
							'IV/a'=>'IV/a','IV/b'=>'IV/b','IV/c'=>'IV/c','IV/d'=>'IV/d','IV/e'=>'IV/e',
						)),
					array('name' => 'pangkat', 'label' => 'Pangkat', 'type' => 'text'),
					array('name' => 'npwp', 'label' => 'NPWP', 'type' => 'text'),
					array('name' => 'opd_id', 'label' => 'OPD', 'type' => 'select', 'source' => 'opd', 'required' => TRUE),
					array('name' => 'opd_unit_id', 'label' => 'Unit OPD', 'type' => 'select', 'source' => 'opd_unit', 'depends' => 'opd_id'),
				),
				'manage' => array('superadmin', 'admin_opd'),
				'scope_col' => 'm.opd_id',
				'save_scope_col' => 'opd_id',
			),

			'ref_jabatan' => array(
				'title' => 'Master Jabatan', 'table' => 'ref_jabatan', 'from' => 'ref_jabatan m', 'alias' => 'm',
				'select' => 'm.id, m.kode_jabatan, m.nama_jabatan, m.singkatan_jabatan, m.jenis_jabatan, m.eselon, m.is_active',
				'searchable' => array('m.nama_jabatan', 'm.kode_jabatan', 'm.singkatan_jabatan'),
				'order_by' => 'm.jenis_jabatan, m.nama_jabatan',
				'columns' => array(
					array('field' => 'kode_jabatan', 'label' => 'Kode', 'width' => '100px'),
					array('field' => 'nama_jabatan', 'label' => 'Nama Jabatan', 'order' => 'm.nama_jabatan'),
					array('field' => 'singkatan_jabatan', 'label' => 'Singkatan', 'width' => '120px'),
					array('field' => 'jenis_jabatan', 'label' => 'Jenis', 'render' => 'badge', 'width' => '120px'),
					array('field' => 'eselon', 'label' => 'Eselon', 'width' => '80px'),
					array('field' => 'is_active', 'label' => 'Aktif', 'render' => 'active', 'width' => '70px'),
				),
				'filters' => array(
					array('name' => 'm.jenis_jabatan', 'label' => 'Jenis', 'options' => array(
						'STRUKTURAL'=>'Struktural','FUNGSIONAL'=>'Fungsional','PENATAUSAHAAN'=>'Penatausahaan','LAINNYA'=>'Lainnya')),
				),
				'fields' => array(
					array('name' => 'kode_jabatan', 'label' => 'Kode Jabatan', 'type' => 'text'),
					array('name' => 'nama_jabatan', 'label' => 'Nama Jabatan', 'type' => 'text', 'required' => TRUE, 'unique' => TRUE),
					array('name' => 'singkatan_jabatan', 'label' => 'Singkatan', 'type' => 'text'),
					array('name' => 'jenis_jabatan', 'label' => 'Jenis Jabatan', 'type' => 'enum',
						'options' => array('STRUKTURAL'=>'Struktural','FUNGSIONAL'=>'Fungsional','PENATAUSAHAAN'=>'Penatausahaan','LAINNYA'=>'Lainnya'), 'required' => TRUE),
					array('name' => 'eselon', 'label' => 'Eselon', 'type' => 'select',
						'options' => array('I'=>'Eselon I','IIa'=>'Eselon IIa','IIb'=>'Eselon IIb',
							'IIIa'=>'Eselon IIIa','IIIb'=>'Eselon IIIb','IVa'=>'Eselon IVa','IVb'=>'Eselon IVb',
							'Va'=>'Eselon Va','Vb'=>'Eselon Vb','NON'=>'Non Eselon')),
					array('name' => 'is_active', 'label' => 'Status Aktif', 'type' => 'checkbox', 'default' => 1),
				),
				'manage' => array('superadmin', 'admin_opd'),
			),

			'penerima' => array(
				'title' => 'Penerima Pembayaran', 'table' => 'master_penerima', 'from' => 'master_penerima m', 'alias' => 'm',
				'select' => 'm.id, m.nama_penerima, m.jenis_penerima, m.golongan, m.punya_npwp, m.npwp, m.nama_bank, m.no_rekening, m.is_active',
				'searchable' => array('m.nama_penerima', 'm.npwp', 'm.no_rekening'),
				'order_by' => 'm.jenis_penerima, m.nama_penerima',
				'columns' => array(
					array('field' => 'nama_penerima', 'label' => 'Nama Penerima', 'order' => 'm.nama_penerima'),
					array('field' => 'jenis_penerima', 'label' => 'Jenis', 'render' => 'badge', 'width' => '100px'),
					array('field' => 'golongan', 'label' => 'Gol.', 'width' => '60px'),
					array('field' => 'npwp', 'label' => 'NPWP', 'width' => '180px'),
					array('field' => 'nama_bank', 'label' => 'Bank'),
					array('field' => 'no_rekening', 'label' => 'No. Rekening', 'width' => '150px'),
					array('field' => 'is_active', 'label' => 'Status', 'render' => 'active', 'width' => '80px'),
				),
				'filters' => array(
					array('name' => 'm.jenis_penerima', 'label' => 'Jenis', 'options' => array('asn'=>'ASN','non_asn'=>'Non ASN','badan'=>'Badan/Vendor')),
					array('name' => 'm.is_active', 'label' => 'Status', 'options' => array('1'=>'Aktif','0'=>'Nonaktif')),
				),
				'fields' => array(
					array('name' => 'nama_penerima', 'label' => 'Nama Penerima', 'type' => 'text', 'required' => TRUE),
					array('name' => 'jenis_penerima', 'label' => 'Jenis Penerima', 'type' => 'enum',
						'options' => array('asn'=>'ASN (PNS/PPPK)','non_asn'=>'Non ASN (perorangan)','badan'=>'Badan / Vendor'), 'required' => TRUE),
					array('name' => 'golongan', 'label' => 'Golongan (ASN)', 'type' => 'enum',
						'options' => array(''=>'— tidak berlaku —','I'=>'I','II'=>'II','III'=>'III','IV'=>'IV')),
					array('name' => 'punya_npwp', 'label' => 'Punya NPWP', 'type' => 'checkbox', 'default' => 0),
					array('name' => 'npwp', 'label' => 'NPWP', 'type' => 'text'),
					array('name' => 'nama_bank', 'label' => 'Nama Bank', 'type' => 'text'),
					array('name' => 'no_rekening', 'label' => 'No. Rekening', 'type' => 'text'),
					array('name' => 'nama_rekening', 'label' => 'Nama di Rekening', 'type' => 'text'),
					array('name' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea'),
					array('name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox', 'default' => 1),
				),
				'manage' => array('superadmin', 'admin_opd', 'user_opd'),
			),

			'skema_pajak' => array(
				'title' => 'Skema Pajak', 'table' => 'master_skema_pajak', 'from' => 'master_skema_pajak m', 'alias' => 'm',
				'select' => 'm.id, m.kode_skema, m.nama_skema, m.kategori, m.is_active',
				'searchable' => array('m.kode_skema', 'm.nama_skema', 'm.kategori'),
				'order_by' => 'm.kode_skema',
				'columns' => array(
					array('field' => 'kode_skema', 'label' => 'Kode', 'order' => 'm.kode_skema', 'width' => '150px'),
					array('field' => 'nama_skema', 'label' => 'Nama Skema'),
					array('field' => 'kategori', 'label' => 'Kategori', 'render' => 'badge', 'width' => '140px'),
					array('field' => 'is_active', 'label' => 'Status', 'render' => 'active', 'width' => '100px'),
				),
				'fields' => array(
					array('name' => 'kode_skema', 'label' => 'Kode Skema', 'type' => 'text', 'required' => TRUE, 'unique' => TRUE),
					array('name' => 'nama_skema', 'label' => 'Nama Skema', 'type' => 'text', 'required' => TRUE),
					array('name' => 'kategori', 'label' => 'Kategori', 'type' => 'text', 'required' => TRUE),
					array('name' => 'keterangan', 'label' => 'Keterangan', 'type' => 'textarea'),
					array('name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox', 'default' => 1),
				),
				'manage' => array('superadmin'),
			),

			'pemetaan' => array(
				'title' => 'Pemetaan OPD – Bidang Urusan', 'table' => 'opd_bidang_urusan', 'from' => 'opd_bidang_urusan m', 'alias' => 'm',
				'select' => 'm.id, o.kode_opd, o.nama_opd AS opd_nama, b.kode_bidang, b.nama_bidang AS bidang_nama, m.is_dominant',
				'joins' => array(
					array('master_opd o', 'o.id = m.opd_id'),
					array('master_bidang b', 'b.id = m.bidang_urusan_id'),
				),
				'searchable' => array('o.nama_opd', 'b.nama_bidang'),
				'order_by' => 'o.kode_opd, b.kode_bidang',
				'columns' => array(
					array('field' => 'opd_nama',   'label' => 'OPD',          'order' => 'o.kode_opd'),
					array('field' => 'kode_bidang', 'label' => 'Kode Bidang', 'order' => 'b.kode_bidang', 'width' => '130px'),
					array('field' => 'bidang_nama', 'label' => 'Bidang Urusan'),
					array('field' => 'is_dominant', 'label' => 'Dominan',     'render' => 'active', 'width' => '100px'),
				),
				'filters' => array(
					array('name' => 'm.opd_id',          'label' => 'OPD',    'source' => 'opd'),
					array('name' => 'm.bidang_urusan_id','label' => 'Bidang', 'source' => 'bidang'),
				),
				'fields' => array(
					array('name' => 'opd_id',          'label' => 'OPD',           'type' => 'select', 'source' => 'opd',    'required' => TRUE),
					array('name' => 'bidang_urusan_id','label' => 'Bidang Urusan', 'type' => 'select', 'source' => 'bidang', 'required' => TRUE),
					array('name' => 'is_dominant',     'label' => 'Dominan',       'type' => 'checkbox', 'default' => 0),
				),
				'manage' => array('superadmin'),
			),
		);

		if ($key === NULL) return $reg;
		if ( ! isset($reg[$key])) show_404();
		return $reg[$key];
	}

	// ================= HALAMAN INDEX =================
	public function index($entity = 'urusan')
	{
		$cfg = $this->registry($entity);

		// Siapkan opsi filter
		$filter_options = array();
		if ( ! empty($cfg['filters']))
		{
			foreach ($cfg['filters'] as $f)
			{
				// Filter statis (punya 'options') dirender server; filter cascade
				// (punya 'source') opsinya dimuat via JS bertingkat.
				if (isset($f['options'])) $filter_options[$f['name']] = $f['options'];
			}
		}
		// Siapkan opsi select untuk form
		$field_options = array();
		foreach ($cfg['fields'] as $fld)
		{
			if ($fld['type'] === 'select' && empty($fld['depends']))
			{
				if (isset($fld['options']))
					$field_options[$fld['name']] = $fld['options'];
				elseif (isset($fld['source']))
					$field_options[$fld['name']] = $this->source_options($fld['source']);
			}
		}

		$data = array(
			'entity'         => $entity,
			'cfg'            => $cfg,
			'can_manage'     => $this->can_manage($cfg),
			'filter_options' => $filter_options,
			'field_options'  => $field_options,
		);
		$this->render('master/index', $data, $cfg['title']);
	}

	// ================= JSON DATATABLES =================
	public function data($entity)
	{
		$cfg = $this->registry($entity);
		$dt = array(
			'draw'   => (int) $this->input->get('draw'),
			'start'  => (int) $this->input->get('start'),
			'length' => (int) $this->input->get('length'),
			'search' => $this->input->get('search'),
			'order'  => $this->input->get('order'),
		);

		// Filter dari request
		$filters = array();
		if ( ! empty($cfg['filters']))
		{
			foreach ($cfg['filters'] as $f)
			{
				$val = $this->input->get('f_' . md5($f['name']));
				if ($val !== NULL && $val !== '') $filters[$f['name']] = $val;
			}
		}

		$scope = $this->scope_for($cfg);
		$res = $this->mm->datatables($cfg, $dt, $filters, $scope);

		$out = array(
			'draw'            => $dt['draw'],
			'recordsTotal'    => $res['recordsTotal'],
			'recordsFiltered' => $res['recordsFiltered'],
			'data'            => $res['data'],
		);
		$this->output->set_content_type('application/json')->set_output(json_encode($out));
	}

	// ================= JSON GET ROW =================
	public function get($entity, $id)
	{
		$cfg = $this->registry($entity);
		$row = $this->mm->get_row($cfg['table'], (int) $id);
		if ( ! $row) show_404();
		if ( ! $this->can_manage($cfg, $row)) show_error('Akses ditolak', 403);
		$this->output->set_content_type('application/json')->set_output(json_encode($row));
	}

	// ================= SIMPAN =================
	public function save($entity)
	{
		$cfg = $this->registry($entity);
		if ( ! $this->can_manage($cfg)) show_error('Akses ditolak', 403);

		$id   = (int) $this->input->post('id');
		$data = array();
		$errors = array();

		foreach ($cfg['fields'] as $fld)
		{
			$name = $fld['name'];
			if ($fld['type'] === 'checkbox')
			{
				$val = $this->input->post($name) ? 1 : 0;
			}
			else
			{
				$val = $this->input->post($name, TRUE);
				if ($val === '') $val = NULL;
			}

			if ( ! empty($fld['required']) && ($val === NULL || $val === ''))
			{
				$errors[] = $fld['label'] . ' wajib diisi.';
			}
			if ( ! empty($fld['unique']) && $val !== NULL)
			{
				if ( ! $this->mm->is_unique_value($cfg['table'], $name, $val, $id ?: NULL))
				{
					$errors[] = $fld['label'] . ' "' . $val . '" sudah digunakan.';
				}
			}
			$data[$name] = $val;
		}

		// Batasan scope OPD untuk admin_opd
		if ( ! empty($cfg['save_scope_col']) && current_role() === 'admin_opd')
		{
			$data[$cfg['save_scope_col']] = scope_opd_id();
		}

		// Pegawai: status_kepegawaian NOT NULL, diturunkan dari jenis_kepegawaian
		if ($entity === 'pegawai' && isset($data['jenis_kepegawaian']))
		{
			$data['status_kepegawaian'] = ($data['jenis_kepegawaian'] === 'NON_ASN') ? 'NON_ASN' : 'ASN';
		}

		if ($errors)
		{
			$this->session->set_flashdata('error', implode(' ', $errors));
			redirect('master/' . $entity);
		}

		if ($id > 0)
		{
			$this->mm->update($cfg['table'], $id, $data);
			$this->session->set_flashdata('success', $cfg['title'] . ' berhasil diperbarui.');
		}
		else
		{
			$this->mm->insert($cfg['table'], $data);
			$this->session->set_flashdata('success', $cfg['title'] . ' berhasil ditambahkan.');
		}
		redirect('master/' . $entity);
	}

	// ================= HAPUS =================
	public function delete($entity)
	{
		$cfg = $this->registry($entity);
		if ( ! $this->can_manage($cfg)) show_error('Akses ditolak', 403);
		$id = (int) $this->input->post('id');

		$row = $this->mm->get_row($cfg['table'], $id);
		if ($row && ! $this->can_manage($cfg, $row)) show_error('Akses ditolak', 403);

		$this->db->db_debug = FALSE;
		$ok = $this->mm->delete($cfg['table'], $id);
		if ($ok && $this->db->affected_rows() >= 0 && ! $this->db->error()['code'])
		{
			$this->session->set_flashdata('success', $cfg['title'] . ' berhasil dihapus.');
		}
		else
		{
			$this->session->set_flashdata('error', 'Data tidak dapat dihapus karena masih digunakan oleh data lain.');
		}
		redirect('master/' . $entity);
	}

	// ================= PEGAWAI SEARCH (untuk penerima picker) =================
	public function pegawai_search()
	{
		$q = trim($this->input->get('q', TRUE));
		if (strlen($q) < 2)
		{
			$this->output->set_content_type('application/json')->set_output('[]');
			return;
		}
		$this->db->select('m.id, m.nama_lengkap, m.nip, m.jenis_kepegawaian, m.golongan, m.pangkat, m.npwp, o.nama_opd')
			->from('pegawai m')
			->join('master_opd o', 'o.id = m.opd_id', 'left')
			->group_start()
				->like('m.nama_lengkap', $q)
				->or_like('m.nip', $q)
			->group_end()
			->order_by('m.nama_lengkap')
			->limit(15);
		$rows = $this->db->get()->result_array();
		$this->output->set_content_type('application/json')->set_output(json_encode(array_values($rows)));
	}

	// ================= OPSI CASCADING =================
	public function options($entity)
	{
		$parent = $this->input->get('parent');
		$opts = $this->source_options($entity, $parent);
		$this->output->set_content_type('application/json')->set_output(json_encode($opts));
	}

	// ================= HELPERS =================

	/** Daftar kategori pajak rekening (dasar penentuan pajak). */
	private function kategori_pajak_options()
	{
		return array(
			'honorarium'       => 'Honorarium',
			'barang'           => 'Barang',
			'jasa'             => 'Jasa',
			'jasa_boga'        => 'Jasa Boga/Katering',
			'makan_minum'      => 'Makan & Minum',
			'sewa'             => 'Sewa',
			'konstruksi'       => 'Konstruksi',
			'modal'            => 'Modal',
			'perjalanan_dinas' => 'Perjalanan Dinas',
			'pegawai'          => 'Pegawai/Gaji',
			'non_pajak'        => 'Non Pajak',
			'lainnya'          => 'Lainnya',
		);
	}

	/** Opsi [id=>label] untuk sebuah source entitas, opsional difilter parent. */
	private function source_options($source, $parent = NULL)
	{
		switch ($source)
		{
			case 'urusan':
				return $this->mm->options('master_urusan', 'id', "CONCAT(kode_urusan,' - ',nama_urusan)", array(), 'kode_urusan');
			case 'bidang':
				$w = ($parent !== NULL && $parent !== '') ? array('urusan_id' => $parent) : array();
				return $this->mm->options('master_bidang', 'id', "CONCAT(kode_bidang,' - ',nama_bidang)", $w, 'kode_bidang');
			case 'program':
				$w = ($parent !== NULL && $parent !== '') ? array('bidang_id' => $parent) : array();
				return $this->mm->options('master_program', 'id', "CONCAT(kode_program,' - ',nama_program)", $w, 'kode_program');
			case 'kegiatan':
				$w = ($parent !== NULL && $parent !== '') ? array('program_id' => $parent) : array();
				return $this->mm->options('master_kegiatan', 'id', "CONCAT(kode_kegiatan,' - ',nama_kegiatan)", $w, 'kode_kegiatan');
			case 'subkegiatan':
				if ($parent === NULL || $parent === '') return array(); // butuh induk (kegiatan)
				return $this->mm->options('master_subkegiatan', 'id', "CONCAT(kode_subkegiatan,' - ',LEFT(nama_subkegiatan,60))", array('kegiatan_id' => $parent), 'kode_subkegiatan');
			case 'opd':
				return $this->mm->options('master_opd', 'id', "CONCAT(kode_opd,' - ',COALESCE(singkatan,nama_opd))", array(), 'kode_opd');
			case 'opd_unit':
				$w = ($parent !== NULL && $parent !== '') ? array('opd_id' => $parent) : array();
				return $this->mm->options('master_opd_unit', 'id', 'nama_unit', $w, 'nama_unit');
		}
		return array();
	}

	/** Batasan scope untuk index query. */
	private function scope_for($cfg)
	{
		if (empty($cfg['scope_col'])) return NULL;
		if (is_super()) return NULL;
		if (current_role() === 'admin_opd')
		{
			return array('column' => $cfg['scope_col'], 'ids' => array((int) scope_opd_id()));
		}
		// user_opd tidak mengelola entitas ber-scope OPD di Tahap 1
		return array('column' => $cfg['scope_col'], 'ids' => array((int) scope_opd_id()));
	}

	/** Apakah user boleh mengelola entitas (opsional cek baris utk scope OPD). */
	private function can_manage($cfg, $row = NULL)
	{
		if ( ! has_role($cfg['manage'])) return FALSE;
		if ($row !== NULL && ! empty($cfg['save_scope_col']) && current_role() === 'admin_opd')
		{
			return (int) $row[$cfg['save_scope_col']] === (int) scope_opd_id();
		}
		return TRUE;
	}
}
