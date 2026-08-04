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
				'modal_size' => 'lg',
				'select' => 'm.id, m.nama_lengkap, m.jenis_kelamin, m.jenis_kepegawaian, m.nip,'
					. ' m.golongan, m.pangkat, m.status_pernikahan, m.jumlah_anak,'
					. ' m.masa_kerja_golongan, m.tgl_lahir, m.tgl_cpns, m.tgl_pns,'
					. ' m.tmt_kenaikan_pangkat, m.tmt_kgb, m.ref_tpp_id,'
					. ' m.jabatan_struktural_id, m.jabatan_penatausahaan_id, m.jabatan_fungsional_id,'
					. ' o.nama_opd AS opd_nama, o.kode_opd,'
					. ' rjs.nama_jabatan AS jabatan_struktural_nama, rjs.eselon,'
					. ' rjp.nama_jabatan AS jabatan_penatausahaan_nama,'
					. ' rjf.nama_jabatan AS jabatan_fungsional_nama',
				'joins' => array(
					array('master_opd o',   'o.id = m.opd_id'),
					array('ref_jabatan rjs', 'rjs.id = m.jabatan_struktural_id',    'left'),
					array('ref_jabatan rjp', 'rjp.id = m.jabatan_penatausahaan_id', 'left'),
					array('ref_jabatan rjf', 'rjf.id = m.jabatan_fungsional_id',    'left'),
				),
				'searchable' => array('m.nama_lengkap', 'm.nip', 'm.pangkat'),
				'order_by' => "o.kode_opd ASC, CASE m.jenis_kepegawaian WHEN 'PNS' THEN 1 WHEN 'PPPK' THEN 2 ELSE 3 END ASC, CASE COALESCE(rjs.eselon,'') WHEN '2A' THEN 1 WHEN '2B' THEN 2 WHEN '3A' THEN 3 WHEN '3B' THEN 4 WHEN '4A' THEN 5 WHEN '4B' THEN 6 ELSE 99 END ASC, FIELD(m.golongan,'I/a','I/b','I/c','I/d','II/a','II/b','II/c','II/d','III/a','III/b','III/c','III/d','IV/a','IV/b','IV/c','IV/d','IV/e','I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII','XIII','XIV','XV','XVI','XVII') DESC, m.tgl_lahir ASC",
				'order_by_raw' => TRUE,
				'columns' => array(
					array('field' => 'nama_lengkap',              'label' => 'Nama',              'order' => 'm.nama_lengkap'),
					array('field' => 'nip',                       'label' => 'NIP',               'width' => '185px'),
					array('field' => 'golongan',                  'label' => 'Gol.',              'width' => '60px'),
					array('field' => 'masa_kerja_golongan',       'label' => 'MKG',              'width' => '50px'),
					array('field' => 'tmt_kgb',                   'label' => 'TMT KGB Yad',      'width' => '110px', 'order' => 'm.tmt_kgb'),
					array('field' => 'eselon',                    'label' => 'Eselon',            'width' => '70px'),
					array('field' => 'jabatan_struktural_nama',   'label' => 'Jabatan', 'render' => 'jabatan_multi'),
					array('field' => 'opd_nama',                  'label' => 'OPD',              'order' => 'o.kode_opd'),
				),
				'filters' => array(
					array('name' => 'm.jenis_kepegawaian', 'label' => 'Jenis', 'options' => array('PNS'=>'PNS','PPPK'=>'PPPK','NON_ASN'=>'Non ASN')),
					array('name' => 'm.opd_id', 'label' => 'OPD', 'source' => 'opd'),
				),
				'fields' => array(
					// -- Tab: Identitas --
					array('name' => 'nama_lengkap',   'tab' => 'Identitas', 'label' => 'Nama Lengkap', 'type' => 'text', 'required' => TRUE),
					array('name' => 'jenis_kelamin',  'tab' => 'Identitas', 'label' => 'Jenis Kelamin', 'type' => 'enum',
						'options' => array('L'=>'Laki-laki','P'=>'Perempuan'), 'default' => 'L'),
					array('name' => 'jenis_kepegawaian', 'tab' => 'Identitas', 'label' => 'Jenis Kepegawaian', 'type' => 'enum',
						'options' => array('PNS'=>'PNS','PPPK'=>'PPPK','NON_ASN'=>'Non ASN'), 'required' => TRUE),
					array('name' => 'nip',  'tab' => 'Identitas', 'label' => 'NIP / NI PPPK', 'type' => 'text', 'placeholder' => '18 digit'),
					array('name' => 'npwp', 'tab' => 'Identitas', 'label' => 'NPWP', 'type' => 'text'),
					// -- Tab: Kepangkatan --
					array('name' => 'golongan', 'tab' => 'Kepangkatan', 'label' => 'Golongan / Ruang', 'type' => 'enum',
						'options' => array(
							'PNS' => array(
								'I/a'=>'I/a — Juru Muda','I/b'=>'I/b — Juru Muda Tk.I','I/c'=>'I/c — Juru','I/d'=>'I/d — Juru Tk.I',
								'II/a'=>'II/a — Pengatur Muda','II/b'=>'II/b — Pengatur Muda Tk.I','II/c'=>'II/c — Pengatur','II/d'=>'II/d — Pengatur Tk.I',
								'III/a'=>'III/a — Penata Muda','III/b'=>'III/b — Penata Muda Tk.I','III/c'=>'III/c — Penata','III/d'=>'III/d — Penata Tk.I',
								'IV/a'=>'IV/a — Pembina','IV/b'=>'IV/b — Pembina Tk.I','IV/c'=>'IV/c — Pembina Utama Muda',
								'IV/d'=>'IV/d — Pembina Utama Madya','IV/e'=>'IV/e — Pembina Utama',
							),
							'PPPK' => array(
								'I'=>'Gol. I','II'=>'Gol. II','III'=>'Gol. III','IV'=>'Gol. IV',
								'V'=>'Gol. V','VI'=>'Gol. VI','VII'=>'Gol. VII','VIII'=>'Gol. VIII','IX'=>'Gol. IX',
								'X'=>'Gol. X','XI'=>'Gol. XI','XII'=>'Gol. XII','XIII'=>'Gol. XIII',
								'XIV'=>'Gol. XIV','XV'=>'Gol. XV','XVI'=>'Gol. XVI','XVII'=>'Gol. XVII',
							),
						)),
					array('name' => 'pangkat', 'tab' => 'Kepangkatan', 'label' => 'Pangkat', 'type' => 'text'),
					array('name' => 'masa_kerja_golongan', 'tab' => 'Kepangkatan', 'label' => 'Masa Kerja Golongan (MKG, tahun)', 'type' => 'number', 'min' => 0, 'max' => 40, 'placeholder' => '0'),
					array('name' => 'tgl_cpns',  'tab' => 'Kepangkatan', 'label' => 'TMT CPNS',       'type' => 'date'),
					array('name' => 'tgl_pns',   'tab' => 'Kepangkatan', 'label' => 'TMT PNS / PPPK', 'type' => 'date'),
					array('name' => 'tmt_kenaikan_pangkat', 'tab' => 'Kepangkatan', 'label' => 'TMT Kenaikan Pangkat YAD', 'type' => 'date'),
				array('name' => 'tmt_kgb', 'tab' => 'Kepangkatan', 'label' => 'TMT KGB Berikutnya (fallback jika TMT PNS kosong)', 'type' => 'date'),
					array('name' => 'persen_gaji', 'tab' => 'Kepangkatan', 'label' => 'Persentase Gaji (%)', 'type' => 'enum',
						'options' => array('100' => '100% — Normal', '80' => '80% — CPNS', '50' => '50% — Hukuman Disiplin'), 'default' => '100'),
					// -- Tab: Jabatan --
					array('name' => 'jabatan_struktural_id',    'tab' => 'Jabatan', 'label' => 'Jabatan Struktural',         'type' => 'select', 'source' => 'jabatan_struktural'),
					array('name' => 'jabatan_fungsional_id',    'tab' => 'Jabatan', 'label' => 'Jabatan Fungsional',         'type' => 'select', 'source' => 'jabatan_fungsional'),
					array('name' => 'jabatan_penatausahaan_id', 'tab' => 'Jabatan', 'label' => 'Jabatan Penatausahaan Keu.', 'type' => 'select', 'source' => 'jabatan_penatausahaan'),
					array('name' => 'ref_tpp_id', 'tab' => 'Jabatan', 'label' => 'Kategori TPP (Perbup)', 'type' => 'select', 'source' => 'ref_tpp'),
					array('name' => 'kd_jabatan_fungsional', 'tab' => 'Jabatan', 'label' => 'Tunjangan Fungsional', 'type' => 'select', 'source' => 'tunjangan_fungsional'),
					array('name' => 'kd_tunjangan_khusus', 'tab' => 'Jabatan', 'label' => 'Tunjangan Khusus (opsional)', 'type' => 'select', 'source' => 'tunjangan_khusus'),
					// -- Tab: Keluarga --
					array('name' => 'tgl_lahir', 'tab' => 'Keluarga', 'label' => 'Tanggal Lahir', 'type' => 'date'),
					array('name' => 'status_pernikahan', 'tab' => 'Keluarga', 'label' => 'Status Pernikahan', 'type' => 'enum',
						'options' => array('BELUM_KAWIN'=>'Belum Kawin','KAWIN'=>'Kawin','JANDA'=>'Janda','DUDA'=>'Duda'), 'default' => 'BELUM_KAWIN'),
					array('name' => 'terima_tunjangan_keluarga', 'tab' => 'Keluarga', 'label' => 'Terima Tunjangan Istri/Suami', 'type' => 'checkbox', 'default' => 1,
						'hint' => 'Centang jika pegawai ini yang menerima tunjangan keluarga. Hapus centang jika pasangan adalah ASN dengan gapok lebih tinggi (tunjangan mengikuti pasangan).'),
					array('name' => 'jumlah_anak', 'tab' => 'Keluarga', 'label' => 'Jumlah Anak (tanggungan)', 'type' => 'number', 'min' => 0, 'max' => 10, 'placeholder' => '0'),
					// -- Tab: Penempatan --
					array('name' => 'opd_id',      'tab' => 'Penempatan', 'label' => 'OPD',      'type' => 'select', 'source' => 'opd',      'required' => TRUE),
					array('name' => 'opd_unit_id', 'tab' => 'Penempatan', 'label' => 'Unit OPD', 'type' => 'select', 'source' => 'opd_unit', 'depends' => 'opd_id'),
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
						'options' => array('1A'=>'Eselon 1A','1B'=>'Eselon 1B','2A'=>'Eselon 2A','2B'=>'Eselon 2B',
							'3A'=>'Eselon 3A','3B'=>'Eselon 3B','4A'=>'Eselon 4A','4B'=>'Eselon 4B',
							'5A'=>'Eselon 5A','5B'=>'Eselon 5B','NON'=>'Non Eselon')),
					array('name' => 'kelas_jabatan', 'label' => 'Kelas Jabatan (1–17)', 'type' => 'number', 'min' => 1, 'max' => 17),
					array('name' => 'is_active', 'label' => 'Status Aktif', 'type' => 'checkbox', 'default' => 1),
				),
				'manage' => array('superadmin', 'admin_opd'),
			),

			'penerima' => array(
				'title' => 'Penerima Pembayaran', 'table' => 'master_penerima', 'from' => 'master_penerima m', 'alias' => 'm',
				'select' => 'm.id, m.pegawai_id, COALESCE(pg.nama_lengkap, m.nama_penerima) AS nama_penerima, m.jenis_penerima,'
					. ' COALESCE(pg.golongan, m.golongan) AS golongan, m.punya_npwp, COALESCE(pg.npwp, m.npwp) AS npwp,'
					. ' m.nama_bank, m.no_rekening, m.is_active,'
					. ' CASE WHEN m.pegawai_id IS NOT NULL THEN 1 ELSE 0 END AS is_pegawai',
				'joins' => array(array('pegawai pg', 'pg.id = m.pegawai_id', 'left')),
				'searchable' => array('m.nama_penerima', 'pg.nama_lengkap', 'm.npwp', 'm.no_rekening'),
				'order_by' => 'm.jenis_penerima, nama_penerima',
				'columns' => array(
					array('field' => 'nama_penerima', 'label' => 'Nama Penerima', 'order' => 'm.nama_penerima'),
					array('field' => 'is_pegawai', 'label' => 'Sumber', 'render' => 'pegawai_badge', 'width' => '90px'),
					array('field' => 'jenis_penerima', 'label' => 'Jenis', 'render' => 'badge', 'width' => '100px'),
					array('field' => 'golongan', 'label' => 'Gol.', 'width' => '60px'),
					array('field' => 'npwp', 'label' => 'NPWP', 'width' => '160px'),
					array('field' => 'nama_bank', 'label' => 'Bank'),
					array('field' => 'no_rekening', 'label' => 'No. Rekening', 'width' => '140px'),
					array('field' => 'is_active', 'label' => 'Status', 'render' => 'active', 'width' => '80px'),
				),
				'filters' => array(
					array('name' => 'm.jenis_penerima', 'label' => 'Jenis', 'options' => array('asn'=>'ASN','non_asn'=>'Non ASN','badan'=>'Badan/Vendor')),
					array('name' => 'm.is_active', 'label' => 'Status', 'options' => array('1'=>'Aktif','0'=>'Nonaktif')),
				),
				'fields' => array(
					array('name' => 'pegawai_id', 'type' => 'hidden'),
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

			'unit_pemetaan' => array(
				'title' => 'Pemetaan Unit OPD – Bidang Urusan', 'table' => 'opd_unit_bidang_urusan', 'from' => 'opd_unit_bidang_urusan m', 'alias' => 'm',
				'select' => 'm.id, o.kode_opd, ou.nama_unit, b.kode_bidang, b.nama_bidang AS bidang_nama',
				'joins' => array(
					array('master_opd_unit ou', 'ou.id = m.opd_unit_id'),
					array('master_opd o', 'o.id = ou.opd_id'),
					array('master_bidang b', 'b.id = m.bidang_urusan_id'),
				),
				'searchable' => array('ou.nama_unit', 'b.nama_bidang', 'o.nama_opd'),
				'order_by' => 'o.kode_opd, ou.nama_unit, b.kode_bidang',
				'columns' => array(
					array('field' => 'kode_opd',    'label' => 'OPD',           'order' => 'o.kode_opd',  'width' => '90px'),
					array('field' => 'nama_unit',   'label' => 'Unit OPD',      'order' => 'ou.nama_unit'),
					array('field' => 'kode_bidang', 'label' => 'Kode Bidang',   'order' => 'b.kode_bidang','width' => '130px'),
					array('field' => 'bidang_nama', 'label' => 'Bidang Urusan'),
				),
				'filters' => array(
					array('name' => 'ou.opd_id',         'label' => 'OPD',    'source' => 'opd'),
					array('name' => 'm.bidang_urusan_id','label' => 'Bidang', 'source' => 'bidang'),
				),
				'fields' => array(
					array('name' => 'opd_unit_id',     'label' => 'Unit OPD',      'type' => 'select', 'source' => 'opd_unit', 'required' => TRUE),
					array('name' => 'bidang_urusan_id','label' => 'Bidang Urusan', 'type' => 'select', 'source' => 'bidang',   'required' => TRUE),
				),
				'manage' => array('superadmin'),
			),

			// =================== MODUL GAJI ===================

			'ref_gaji_pokok' => array(
				'title' => 'Tabel Gaji Pokok', 'table' => 'ref_gaji_pokok', 'from' => 'ref_gaji_pokok m', 'alias' => 'm',
				'select' => 'm.id, m.jenis, m.golongan, m.masa_kerja, m.gaji_pokok, m.pp_nomor, m.berlaku_mulai, m.is_active',
				'searchable' => array('m.golongan', 'm.pp_nomor'),
				'order_by' => 'm.jenis, m.golongan, m.masa_kerja, m.berlaku_mulai DESC',
				'columns' => array(
					array('field' => 'jenis',        'label' => 'Jenis ASN',  'render' => 'badge', 'width' => '80px'),
					array('field' => 'golongan',     'label' => 'Golongan',   'width' => '80px'),
					array('field' => 'masa_kerja',   'label' => 'MKG',        'width' => '60px'),
					array('field' => 'gaji_pokok',   'label' => 'Gaji Pokok (Rp)', 'render' => 'money'),
					array('field' => 'pp_nomor',     'label' => 'Dasar Hukum','width' => '140px'),
					array('field' => 'berlaku_mulai','label' => 'Berlaku',    'width' => '110px'),
					array('field' => 'is_active',    'label' => 'Aktif',      'render' => 'active', 'width' => '70px'),
				),
				'filters' => array(
					array('name' => 'm.jenis', 'label' => 'Jenis', 'options' => array('PNS'=>'PNS','PPPK'=>'PPPK')),
				),
				'fields' => array(
					array('name' => 'jenis',        'label' => 'Jenis ASN', 'type' => 'enum', 'options' => array('PNS'=>'PNS','PPPK'=>'PPPK'), 'required' => TRUE),
					array('name' => 'golongan',     'label' => 'Golongan', 'type' => 'text', 'required' => TRUE, 'placeholder' => 'PNS: I/a–IV/e  |  PPPK: I–XVII'),
					array('name' => 'masa_kerja',   'label' => 'Masa Kerja Golongan (tahun)', 'type' => 'number', 'min' => 0, 'max' => 40, 'required' => TRUE),
					array('name' => 'gaji_pokok',   'label' => 'Gaji Pokok (Rp)', 'type' => 'number', 'min' => 0, 'required' => TRUE),
					array('name' => 'pp_nomor',     'label' => 'Dasar PP', 'type' => 'text', 'placeholder' => 'mis. PP 5/2024'),
					array('name' => 'berlaku_mulai','label' => 'Berlaku Mulai', 'type' => 'date', 'required' => TRUE),
					array('name' => 'is_active',    'label' => 'Aktif', 'type' => 'checkbox', 'default' => 1),
				),
				'manage' => array('superadmin'),
			),

			'ref_tunjangan_jabatan' => array(
				'title' => 'Tunjangan Jabatan', 'table' => 'ref_tunjangan_jabatan', 'from' => 'ref_tunjangan_jabatan m', 'alias' => 'm',
				'select' => 'm.id, m.jenis, m.nama, m.kode, m.nominal, m.pp_nomor, m.berlaku_mulai, m.is_active',
				'searchable' => array('m.nama', 'm.kode'),
				'order_by' => 'm.jenis, m.nominal DESC',
				'columns' => array(
					array('field' => 'jenis',        'label' => 'Jenis',        'render' => 'badge', 'width' => '110px'),
					array('field' => 'nama',         'label' => 'Nama Tunjangan'),
					array('field' => 'kode',         'label' => 'Kode',         'width' => '100px'),
					array('field' => 'nominal',      'label' => 'Nominal (Rp)', 'render' => 'money'),
					array('field' => 'pp_nomor',     'label' => 'Dasar Hukum',  'width' => '140px'),
					array('field' => 'is_active',    'label' => 'Aktif',        'render' => 'active', 'width' => '70px'),
				),
				'filters' => array(
					array('name' => 'm.jenis', 'label' => 'Jenis', 'options' => array('STRUKTURAL'=>'Struktural','FUNGSIONAL'=>'Fungsional','UMUM'=>'Umum')),
					array('name' => 'm.is_active', 'label' => 'Status', 'options' => array('1'=>'Aktif','0'=>'Nonaktif')),
				),
				'fields' => array(
					array('name' => 'jenis',        'label' => 'Jenis', 'type' => 'enum', 'options' => array('STRUKTURAL'=>'Struktural','FUNGSIONAL'=>'Fungsional','UMUM'=>'Umum'), 'required' => TRUE),
					array('name' => 'nama',         'label' => 'Nama Tunjangan', 'type' => 'text', 'required' => TRUE),
					array('name' => 'kode',         'label' => 'Kode', 'type' => 'text', 'placeholder' => 'mis. ES_4A'),
					array('name' => 'nominal',      'label' => 'Nominal (Rp)', 'type' => 'number', 'min' => 0, 'required' => TRUE),
					array('name' => 'pp_nomor',     'label' => 'Dasar Hukum', 'type' => 'text'),
					array('name' => 'berlaku_mulai','label' => 'Berlaku Mulai', 'type' => 'date', 'required' => TRUE),
					array('name' => 'is_active',    'label' => 'Aktif', 'type' => 'checkbox', 'default' => 1),
				),
				'manage' => array('superadmin'),
			),

			'ref_kelas_jabatan' => array(
				'title' => 'Kelas Jabatan', 'table' => 'ref_kelas_jabatan', 'from' => 'ref_kelas_jabatan m', 'alias' => 'm',
				'select' => 'm.id, m.kelas, m.nama, m.berlaku_mulai, m.is_active',
				'searchable' => array('m.nama'),
				'order_by' => 'm.kelas',
				'columns' => array(
					array('field' => 'kelas',        'label' => 'Kelas', 'width' => '60px'),
					array('field' => 'nama',         'label' => 'Nama Kelas'),
					array('field' => 'berlaku_mulai','label' => 'Berlaku',  'width' => '110px'),
					array('field' => 'is_active',    'label' => 'Aktif',   'render' => 'active', 'width' => '70px'),
				),
				'fields' => array(
					array('name' => 'kelas',        'label' => 'Kelas Jabatan (1-17)', 'type' => 'number', 'min' => 1, 'max' => 17, 'required' => TRUE),
					array('name' => 'nama',         'label' => 'Keterangan', 'type' => 'text'),
					array('name' => 'berlaku_mulai','label' => 'Berlaku Mulai', 'type' => 'date', 'required' => TRUE),
					array('name' => 'is_active',    'label' => 'Aktif', 'type' => 'checkbox', 'default' => 1),
				),
				'manage' => array('superadmin'),
			),

			'ref_harga_beras' => array(
				'title' => 'Harga Beras (Tunjangan Pangan)', 'table' => 'ref_harga_beras', 'from' => 'ref_harga_beras m', 'alias' => 'm',
				'select' => 'm.id, m.harga_per_kg, m.berlaku_mulai',
				'searchable' => array(),
				'order_by' => 'm.berlaku_mulai DESC',
				'columns' => array(
					array('field' => 'harga_per_kg', 'label' => 'Harga per Kg (Rp)', 'render' => 'money'),
					array('field' => 'berlaku_mulai','label' => 'Berlaku Mulai'),
				),
				'fields' => array(
					array('name' => 'harga_per_kg', 'label' => 'Harga Beras per Kg (Rp)', 'type' => 'number', 'min' => 0, 'required' => TRUE, 'placeholder' => 'mis. 7242'),
					array('name' => 'berlaku_mulai','label' => 'Berlaku Mulai', 'type' => 'date', 'required' => TRUE),
				),
				'manage' => array('superadmin'),
			),

			'ref_tpp' => array(
				'title' => 'TPP Perbup Rembang', 'table' => 'ref_tpp', 'from' => 'ref_tpp m', 'alias' => 'm',
				'select' => 'm.id, rkj.kelas, m.uraian, m.nominal, m.perbup, m.berlaku_mulai, m.is_active',
				'joins' => array(
					array('ref_kelas_jabatan rkj', 'rkj.id = m.kelas_jabatan_id', 'left'),
				),
				'searchable' => array('m.uraian', 'm.perbup'),
				'order_by' => 'rkj.kelas DESC, m.uraian',
				'columns' => array(
					array('field' => 'kelas',        'label' => 'Kelas', 'order' => 'rkj.kelas', 'width' => '65px'),
					array('field' => 'uraian',       'label' => 'Uraian Jabatan',  'order' => 'm.uraian'),
					array('field' => 'nominal',      'label' => 'Nominal TPP (Rp)', 'render' => 'money'),
					array('field' => 'perbup',       'label' => 'Perbup',          'width' => '160px'),
					array('field' => 'berlaku_mulai','label' => 'Berlaku',          'width' => '110px'),
					array('field' => 'is_active',    'label' => 'Aktif',            'render' => 'active', 'width' => '70px'),
				),
				'filters' => array(
					array('name' => 'm.is_active', 'label' => 'Status', 'options' => array('1'=>'Aktif','0'=>'Nonaktif')),
				),
				'fields' => array(
					array('name' => 'kelas_jabatan_id', 'label' => 'Kelas Jabatan', 'type' => 'select', 'source' => 'kelas_jabatan', 'required' => FALSE),
					array('name' => 'uraian',           'label' => 'Uraian Jabatan (sesuai Perbup)', 'type' => 'text', 'required' => TRUE, 'placeholder' => 'mis. JF Ahli Muda - Dinas/Badan'),
					array('name' => 'nominal',          'label' => 'Nominal TPP (Rp)', 'type' => 'number', 'min' => 0, 'required' => TRUE),
					array('name' => 'perbup',           'label' => 'Dasar Hukum (Perbup)', 'type' => 'text', 'placeholder' => 'mis. Perbup Rembang 45/2024'),
					array('name' => 'berlaku_mulai',    'label' => 'Berlaku Mulai', 'type' => 'date', 'required' => TRUE),
					array('name' => 'is_active',        'label' => 'Aktif', 'type' => 'checkbox', 'default' => 1),
				),
				'manage' => array('superadmin'),
			),

			'ref_iuran_gaji' => array(
				'title' => 'Iuran & Potongan Gaji', 'table' => 'ref_iuran_gaji', 'from' => 'ref_iuran_gaji m', 'alias' => 'm',
				'select' => 'm.id, m.kode, m.nama, m.jenis_asn, m.persen_pegawai, m.persen_employer, m.keterangan, m.berlaku_mulai, m.is_active',
				'searchable' => array('m.kode', 'm.nama'),
				'order_by' => 'm.jenis_asn, m.kode',
				'columns' => array(
					array('field' => 'kode',            'label' => 'Kode',         'width' => '110px'),
					array('field' => 'nama',            'label' => 'Nama Iuran'),
					array('field' => 'jenis_asn',       'label' => 'Berlaku',      'render' => 'badge', 'width' => '80px'),
					array('field' => 'persen_pegawai',  'label' => '% Pegawai',    'width' => '100px'),
					array('field' => 'persen_employer', 'label' => '% Pemberi Kerja', 'width' => '120px'),
					array('field' => 'is_active',       'label' => 'Aktif',        'render' => 'active', 'width' => '70px'),
				),
				'filters' => array(
					array('name' => 'm.jenis_asn', 'label' => 'Jenis', 'options' => array('PNS'=>'PNS','PPPK'=>'PPPK','SEMUA'=>'Semua')),
				),
				'fields' => array(
					array('name' => 'kode',            'label' => 'Kode',            'type' => 'text', 'required' => TRUE, 'unique' => FALSE),
					array('name' => 'nama',            'label' => 'Nama Iuran',      'type' => 'text', 'required' => TRUE),
					array('name' => 'jenis_asn',       'label' => 'Berlaku Untuk',   'type' => 'enum', 'options' => array('PNS'=>'PNS','PPPK'=>'PPPK','SEMUA'=>'Semua ASN'), 'default' => 'SEMUA'),
					array('name' => 'persen_pegawai',  'label' => '% Potongan Pegawai', 'type' => 'text', 'placeholder' => 'mis. 1.000'),
					array('name' => 'persen_employer', 'label' => '% Tanggungan Pemberi Kerja', 'type' => 'text', 'placeholder' => 'mis. 4.000'),
					array('name' => 'keterangan',      'label' => 'Keterangan / Dasar Pengenaan', 'type' => 'text'),
					array('name' => 'berlaku_mulai',   'label' => 'Berlaku Mulai', 'type' => 'date', 'required' => TRUE),
					array('name' => 'is_active',       'label' => 'Aktif', 'type' => 'checkbox', 'default' => 1),
				),
				'manage' => array('superadmin'),
			),

			'ref_tunjangan_fungsional' => array(
				'title' => 'Tunjangan Jabatan Fungsional', 'table' => 'ref_tunjangan_fungsional', 'from' => 'ref_tunjangan_fungsional m', 'alias' => 'm',
				'select' => 'm.id, m.kdjabatan, m.nama_jabatan, m.nominal, m.bup_usia, m.kategori, m.is_active',
				'searchable' => array('m.kdjabatan', 'm.nama_jabatan'),
				'order_by' => 'm.nama_jabatan',
				'columns' => array(
					array('field' => 'kdjabatan',    'label' => 'Kode',           'width' => '80px'),
					array('field' => 'nama_jabatan', 'label' => 'Nama Jabatan Fungsional'),
					array('field' => 'nominal',      'label' => 'Tunjangan (Rp)', 'render' => 'money'),
					array('field' => 'bup_usia',     'label' => 'BUP (thn)',      'width' => '90px'),
					array('field' => 'is_active',    'label' => 'Aktif',          'render' => 'active', 'width' => '70px'),
				),
				'filters' => array(
					array('name' => 'm.is_active', 'label' => 'Status', 'options' => array('1'=>'Aktif','0'=>'Nonaktif')),
				),
				'fields' => array(
					array('name' => 'kdjabatan',    'label' => 'Kode Jabatan (5 digit)', 'type' => 'text', 'required' => TRUE, 'unique' => TRUE),
					array('name' => 'nama_jabatan', 'label' => 'Nama Jabatan Fungsional', 'type' => 'text', 'required' => TRUE),
					array('name' => 'nominal',      'label' => 'Tunjangan (Rp)', 'type' => 'number', 'min' => 0, 'required' => TRUE),
					array('name' => 'bup_usia',     'label' => 'BUP Usia (tahun)', 'type' => 'number', 'min' => 50, 'max' => 70),
					array('name' => 'kategori',     'label' => 'Kategori (0=umum)', 'type' => 'number', 'min' => 0),
					array('name' => 'is_active',    'label' => 'Aktif', 'type' => 'checkbox', 'default' => 1),
				),
				'manage' => array('superadmin'),
			),

			'ref_tunjangan_khusus' => array(
				'title' => 'Tunjangan Khusus', 'table' => 'ref_tunjangan_khusus', 'from' => 'ref_tunjangan_khusus m', 'alias' => 'm',
				'select' => 'm.id, m.kdjabatan, m.nama_jabatan, m.nominal, m.bup_usia, m.is_active',
				'searchable' => array('m.kdjabatan', 'm.nama_jabatan'),
				'order_by' => 'm.nama_jabatan',
				'columns' => array(
					array('field' => 'kdjabatan',    'label' => 'Kode',         'width' => '80px'),
					array('field' => 'nama_jabatan', 'label' => 'Nama Tunjangan Khusus'),
					array('field' => 'nominal',      'label' => 'Nominal (Rp)', 'render' => 'money'),
					array('field' => 'bup_usia',     'label' => 'BUP (thn)',    'width' => '90px'),
					array('field' => 'is_active',    'label' => 'Aktif',        'render' => 'active', 'width' => '70px'),
				),
				'filters' => array(
					array('name' => 'm.is_active', 'label' => 'Status', 'options' => array('1'=>'Aktif','0'=>'Nonaktif')),
				),
				'fields' => array(
					array('name' => 'kdjabatan',    'label' => 'Kode Jabatan (5 digit)', 'type' => 'text', 'required' => TRUE, 'unique' => TRUE),
					array('name' => 'nama_jabatan', 'label' => 'Nama Tunjangan Khusus', 'type' => 'text', 'required' => TRUE),
					array('name' => 'nominal',      'label' => 'Nominal (Rp)', 'type' => 'number', 'min' => 0, 'required' => TRUE),
					array('name' => 'bup_usia',     'label' => 'BUP Usia (tahun)', 'type' => 'number', 'min' => 50, 'max' => 70),
					array('name' => 'is_active',    'label' => 'Aktif', 'type' => 'checkbox', 'default' => 1),
				),
				'manage' => array('superadmin'),
			),

			'ref_gaji_ke' => array(
				'title' => 'Konfigurasi Gaji Ke-13/14', 'table' => 'ref_gaji_ke', 'from' => 'ref_gaji_ke m', 'alias' => 'm',
				'select' => 'm.id, m.no, m.nama, m.bulan_basis, m.keterangan, m.is_active',
				'searchable' => array('m.nama'),
				'order_by' => 'm.no',
				'columns' => array(
					array('field' => 'no',          'label' => 'No',          'width' => '60px'),
					array('field' => 'nama',        'label' => 'Nama'),
					array('field' => 'bulan_basis', 'label' => 'Bulan Basis', 'width' => '120px'),
					array('field' => 'keterangan',  'label' => 'Keterangan'),
					array('field' => 'is_active',   'label' => 'Aktif',       'render' => 'active', 'width' => '70px'),
				),
				'fields' => array(
					array('name' => 'no',          'label' => 'Nomor Gaji (13 atau 14)', 'type' => 'number', 'min' => 13, 'max' => 14, 'required' => TRUE),
					array('name' => 'nama',        'label' => 'Nama', 'type' => 'text', 'required' => TRUE),
					array('name' => 'bulan_basis', 'label' => 'Bulan Basis (1-12)', 'type' => 'number', 'min' => 1, 'max' => 12, 'required' => TRUE),
					array('name' => 'keterangan',  'label' => 'Keterangan', 'type' => 'text'),
					array('name' => 'is_active',   'label' => 'Aktif', 'type' => 'checkbox', 'default' => 1),
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

		$key = $this->pkey($entity);
		$data = array(
			'entity'         => $entity,
			'cfg'            => $cfg,
			'can_create'     => can_create($key),
			'can_edit'       => can_edit($key),
			'can_delete'     => can_delete($key),
			'can_manage'     => (can_create($key) || can_edit($key) || can_delete($key)),
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
		if ( ! can_edit($this->pkey($entity)) || ! $this->scope_ok($cfg, $row)) show_error('Akses ditolak', 403);
		$this->output->set_content_type('application/json')->set_output(json_encode($row));
	}

	// ================= SIMPAN =================
	public function save($entity)
	{
		$cfg = $this->registry($entity);
		$id   = (int) $this->input->post('id');
		$key  = $this->pkey($entity);
		if ( ! can($id > 0 ? 'edit' : 'create', $key)) show_error('Akses ditolak', 403);

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

		// Pegawai: nama_lengkap selalu UPPERCASE; status_kepegawaian dari jenis_kepegawaian
		if ($entity === 'pegawai')
		{
			if (isset($data['nama_lengkap'])) $data['nama_lengkap'] = strtoupper($data['nama_lengkap']);
			if (isset($data['jenis_kepegawaian']))
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
		if ( ! can_delete($this->pkey($entity))) show_error('Akses ditolak', 403);
		$id = (int) $this->input->post('id');

		$row = $this->mm->get_row($cfg['table'], $id);
		if ($row && ! $this->scope_ok($cfg, $row)) show_error('Akses ditolak', 403);

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
		$this->db->select('m.id, m.nama_lengkap, m.nip, m.jenis_kepegawaian, m.golongan, m.pangkat, m.npwp,'
				. ' o.nama_opd, rjs.nama_jabatan AS jabatan_struktural, rjp.nama_jabatan AS jabatan_penatausahaan', FALSE)
			->from('pegawai m')
			->join('master_opd o',    'o.id = m.opd_id', 'left')
			->join('ref_jabatan rjs', 'rjs.id = m.jabatan_struktural_id', 'left')
			->join('ref_jabatan rjp', 'rjp.id = m.jabatan_penatausahaan_id', 'left')
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

	/** Konversi result rows (k,v) -> map [k=>v]. */
	private function kv($rows)
	{
		$o = array();
		foreach ($rows as $r) $o[$r->k] = $r->v;
		return $o;
	}

	/** Opsi [id=>label] untuk sebuah source entitas, opsional difilter parent. */
	private function source_options($source, $parent = NULL)
	{
		switch ($source)
		{
			case 'urusan':
				return $this->mm->options('master_urusan', 'id', "CONCAT(kode_urusan,' - ',nama_urusan)", array(), 'kode_urusan');
			case 'bidang':
				// non-super: hanya bidang-urusan kewenangan OPD-nya (hitung scope DULU)
				$bu = is_super() ? NULL : scope_bidang_urusan_ids();
				if ($bu !== NULL && empty($bu)) return array();
				$this->db->select("id AS k, CONCAT(kode_bidang,' - ',nama_bidang) AS v", FALSE)->from('master_bidang');
				if ($parent !== NULL && $parent !== '') $this->db->where('urusan_id', $parent);
				if ($bu !== NULL) $this->db->where_in('id', $bu);
				return $this->kv($this->db->order_by('kode_bidang')->get()->result());
			case 'program':
				$bu = is_super() ? NULL : scope_bidang_urusan_ids();
				if ($bu !== NULL && empty($bu)) return array();
				$this->db->select("id AS k, CONCAT(kode_program,' - ',nama_program) AS v", FALSE)->from('master_program');
				if ($parent !== NULL && $parent !== '') $this->db->where('bidang_id', $parent);
				if ($bu !== NULL) $this->db->where_in('bidang_id', $bu);
				return $this->kv($this->db->order_by('kode_program')->get()->result());
			case 'kegiatan':
				$bu = is_super() ? NULL : scope_bidang_urusan_ids();
				if ($bu !== NULL && empty($bu)) return array();
				$this->db->select("k.id AS k, CONCAT(k.kode_kegiatan,' - ',k.nama_kegiatan) AS v", FALSE)->from('master_kegiatan k');
				if ($parent !== NULL && $parent !== '') $this->db->where('k.program_id', $parent);
				if ($bu !== NULL) $this->db->join('master_program p', 'p.id = k.program_id')->where_in('p.bidang_id', $bu);
				return $this->kv($this->db->order_by('k.kode_kegiatan')->get()->result());
			case 'subkegiatan':
				if ($parent === NULL || $parent === '') return array(); // butuh induk (kegiatan)
				$sk = is_super() ? NULL : scope_subkegiatan_ids();
				if ($sk !== NULL && empty($sk)) return array();
				$this->db->select("id AS k, CONCAT(kode_subkegiatan,' - ',LEFT(nama_subkegiatan,60)) AS v", FALSE)
					->from('master_subkegiatan')->where('kegiatan_id', $parent);
				if ($sk !== NULL) $this->db->where_in('id', $sk);
				return $this->kv($this->db->order_by('kode_subkegiatan')->get()->result());
			case 'opd':
				return $this->mm->options('master_opd', 'id', "CONCAT(kode_opd,' - ',nama_opd)", array(), 'kode_opd');
			case 'opd_unit':
				$w = ($parent !== NULL && $parent !== '') ? array('opd_id' => $parent) : array();
				return $this->mm->options('master_opd_unit', 'id', 'nama_unit', $w, 'nama_unit');
			case 'jabatan_struktural':
				$opts = array();
				$jrows = $this->db
					->select("id, eselon, nama_jabatan", FALSE)
					->from('ref_jabatan')
					->where('jenis_jabatan', 'STRUKTURAL')->where('is_active', 1)
					->order_by('eselon, nama_jabatan')->get()->result_array();
				foreach ($jrows as $jr) {
					$opts[$jr['id']] = array(
						'label'  => ($jr['eselon'] ? 'Eselon '.$jr['eselon'].' \xe2\x80\x94 ' : '').$jr['nama_jabatan'],
						'eselon' => $jr['eselon'] ?? '',
					);
				}
				return $opts;
			case 'jabatan_penatausahaan':
				return $this->mm->options('ref_jabatan', 'id', 'nama_jabatan', array('jenis_jabatan' => 'PENATAUSAHAAN', 'is_active' => 1), 'nama_jabatan');
			case 'jabatan_fungsional':
				return $this->mm->options('ref_jabatan', 'id', 'nama_jabatan', array('jenis_jabatan' => 'FUNGSIONAL', 'is_active' => 1), 'nama_jabatan');
			case 'jabatan_semua':
				return $this->mm->options('ref_jabatan', 'id', "CONCAT('[',jenis_jabatan,'] ',nama_jabatan)", array('is_active' => 1), 'jenis_jabatan, nama_jabatan');
			case 'kelas_jabatan':
				$opts = array('' => '— (tidak ada) —');
				$opts += $this->mm->options('ref_kelas_jabatan', 'id', "CONCAT('Kelas ',kelas,IF(nama IS NOT NULL AND nama!='',CONCAT(' \xe2\x80\x94 ',nama),''))", array('is_active' => 1), 'kelas');
				return $opts;
			case 'ref_tpp':
				$opts = array('' => '— (tidak ada) —');
				$rows = $this->db
					->select('rt.id, rkj.kelas, rt.uraian, rt.nominal', FALSE)
					->from('ref_tpp rt')
					->join('ref_kelas_jabatan rkj', 'rkj.id = rt.kelas_jabatan_id', 'left')
					->where('rt.is_active', 1)
					->order_by('rkj.kelas DESC, rt.uraian')
					->get()->result_array();
				foreach ($rows as $r) {
					$kelas = $r['kelas'] !== NULL ? 'Kelas '.$r['kelas'].' \xe2\x80\x94 ' : '';
					$opts[$r['id']] = $kelas.$r['uraian'].' (Rp '.number_format((int)$r['nominal'],0,',','.').')';
				}
				return $opts;
			case 'tunjangan_fungsional':
				$opts = array('' => '— (tidak ada) —');
				$opts += $this->mm->options('ref_tunjangan_fungsional', 'kdjabatan', "CONCAT(kdjabatan,' \xe2\x80\x94 ',nama_jabatan,' (Rp ',FORMAT(nominal,0),')')", array('is_active' => 1), 'nama_jabatan');
				return $opts;
			case 'tunjangan_khusus':
				$opts = array('' => '— (tidak ada) —');
				$opts += $this->mm->options('ref_tunjangan_khusus', 'kdjabatan', "CONCAT(kdjabatan,' \xe2\x80\x94 ',nama_jabatan,' (Rp ',FORMAT(nominal,0),')')", array('is_active' => 1), 'nama_jabatan');
				return $opts;
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
	/** Page key untuk hak akses. gaji ref -> 'gaji.ref', lainnya 'master.<entity>'. */
	private function pkey($entity)
	{
		$gaji_refs = array('ref_tpp','ref_gaji_pokok','ref_tunjangan_jabatan','ref_kelas_jabatan',
			'ref_harga_beras','ref_iuran_gaji','ref_tunjangan_fungsional','ref_tunjangan_khusus','ref_gaji_ke');
		if (in_array($entity, $gaji_refs, TRUE)) return 'gaji.ref';
		return 'master.' . $entity;
	}

	/** Batasan kepemilikan baris per-OPD (admin_opd hanya baris OPD-nya). */
	private function scope_ok($cfg, $row = NULL)
	{
		if ($row === NULL || is_super() || empty($cfg['save_scope_col'])) return TRUE;
		if (current_role() === 'admin_opd')
			return (int) $row[$cfg['save_scope_col']] === (int) scope_opd_id();
		return TRUE;
	}
}
