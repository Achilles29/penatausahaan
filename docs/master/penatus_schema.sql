-- =====================================================================
-- SKEMA DATABASE: penatus (Aplikasi Penatausahaan)
-- Versi normalisasi & rapi. Menggantikan 4 tabel pemetaan OPD literasi
-- dengan 2 junction bersih (opd_bidang_urusan, opd_unit_bidang_urusan).
-- Charset: utf8mb4. Idempotent (aman dijalankan ulang).
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Urutan drop kebalikan dependensi
DROP TABLE IF EXISTS user_akses;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS npd_pinbuk_pajak;
DROP TABLE IF EXISTS npd_pinbuk_rincian;
DROP TABLE IF EXISTS npd_pinbuk;
DROP TABLE IF EXISTS npd_detail;
DROP TABLE IF EXISTS npd;
DROP TABLE IF EXISTS anggaran_kas_bulanan;
DROP TABLE IF EXISTS anggaran_kas;
DROP TABLE IF EXISTS dpa_detail;
DROP TABLE IF EXISTS dpa;
DROP TABLE IF EXISTS master_skema_pajak_detail;
DROP TABLE IF EXISTS master_skema_pajak;
DROP TABLE IF EXISTS master_penerima;
DROP TABLE IF EXISTS pegawai_rekening;
DROP TABLE IF EXISTS pegawai_jabatan;
DROP TABLE IF EXISTS pegawai;
DROP TABLE IF EXISTS ref_jabatan;
DROP TABLE IF EXISTS ref_bank;
DROP TABLE IF EXISTS opd_unit_bidang_urusan;
DROP TABLE IF EXISTS opd_bidang_urusan;
DROP TABLE IF EXISTS master_opd_unit;
DROP TABLE IF EXISTS master_opd;
DROP TABLE IF EXISTS master_sumber_dana;
DROP TABLE IF EXISTS master_rekening;
DROP TABLE IF EXISTS master_subkegiatan;
DROP TABLE IF EXISTS master_kegiatan;
DROP TABLE IF EXISTS master_program;
DROP TABLE IF EXISTS master_bidang;
DROP TABLE IF EXISTS master_urusan;

-- =====================================================================
-- 1. NOMENKLATUR (impor langsung dari literasi)
-- =====================================================================
CREATE TABLE master_urusan (
  id INT NOT NULL AUTO_INCREMENT,
  kode_urusan VARCHAR(10) NOT NULL,
  nama_urusan VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT current_timestamp(),
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY uk_kode_urusan (kode_urusan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE master_bidang (
  id INT NOT NULL AUTO_INCREMENT,
  urusan_id INT NOT NULL,
  kode_bidang VARCHAR(10) NOT NULL,
  nama_bidang VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT current_timestamp(),
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY uk_bidang (urusan_id, kode_bidang),
  CONSTRAINT fk_bidang_urusan FOREIGN KEY (urusan_id) REFERENCES master_urusan (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE master_program (
  id INT NOT NULL AUTO_INCREMENT,
  bidang_id INT NOT NULL,
  kode_program VARCHAR(15) NOT NULL,
  nama_program VARCHAR(500) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT current_timestamp(),
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY uk_program (bidang_id, kode_program),
  CONSTRAINT fk_program_bidang FOREIGN KEY (bidang_id) REFERENCES master_bidang (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE master_kegiatan (
  id INT NOT NULL AUTO_INCREMENT,
  program_id INT NOT NULL,
  kode_kegiatan VARCHAR(20) NOT NULL,
  nama_kegiatan VARCHAR(500) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT current_timestamp(),
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY uk_kegiatan (program_id, kode_kegiatan),
  CONSTRAINT fk_kegiatan_program FOREIGN KEY (program_id) REFERENCES master_program (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE master_subkegiatan (
  id INT NOT NULL AUTO_INCREMENT,
  kegiatan_id INT NOT NULL,
  kode_subkegiatan VARCHAR(25) NOT NULL,
  nama_subkegiatan VARCHAR(1000) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT current_timestamp(),
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY uk_subkegiatan (kegiatan_id, kode_subkegiatan),
  CONSTRAINT fk_subkegiatan_kegiatan FOREIGN KEY (kegiatan_id) REFERENCES master_kegiatan (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE master_rekening (
  id INT NOT NULL AUTO_INCREMENT,
  kode_rekening VARCHAR(50) NOT NULL,
  uraian TEXT NOT NULL,
  jenis_belanja VARCHAR(50) DEFAULT NULL,
  kategori_pajak VARCHAR(30) DEFAULT NULL COMMENT 'Klasifikasi jenis belanja untuk penentuan pajak; tertaut ke master_skema_pajak.kategori',
  created_at TIMESTAMP NULL DEFAULT current_timestamp(),
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY uk_kode_rekening (kode_rekening),
  KEY idx_rekening_kategori_pajak (kategori_pajak)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE master_sumber_dana (
  id INT NOT NULL AUTO_INCREMENT,
  kode VARCHAR(30) NOT NULL,
  nama VARCHAR(500) NOT NULL,
  keterangan TEXT DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_sumber_dana_kode (kode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 2. ORGANISASI (OPD + unit internal)
-- =====================================================================
CREATE TABLE master_opd (
  id INT NOT NULL AUTO_INCREMENT,
  kode_opd VARCHAR(30) NOT NULL,
  nama_opd VARCHAR(500) NOT NULL,
  singkatan VARCHAR(50) DEFAULT NULL,
  dominant_bidang_id INT DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  kepala_opd VARCHAR(255) DEFAULT NULL,
  nip_kepala VARCHAR(30) DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT current_timestamp(),
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY uk_kode_opd (kode_opd),
  KEY fk_opd_dominant_bidang (dominant_bidang_id),
  CONSTRAINT fk_opd_dominant_bidang FOREIGN KEY (dominant_bidang_id) REFERENCES master_bidang (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Unit internal OPD = sekretariat + bidang-bidang (rename dari master_opd_bidang)
CREATE TABLE master_opd_unit (
  id INT NOT NULL AUTO_INCREMENT,
  opd_id INT NOT NULL,
  kode_unit VARCHAR(20) DEFAULT NULL,
  nama_unit VARCHAR(255) NOT NULL,
  jenis_unit ENUM('sekretariat','bidang','uptd','lainnya') NOT NULL DEFAULT 'bidang',
  kepala VARCHAR(255) DEFAULT NULL,
  nip_kepala VARCHAR(30) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT current_timestamp(),
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY uk_opd_unit (opd_id, nama_unit),
  CONSTRAINT fk_opd_unit_opd FOREIGN KEY (opd_id) REFERENCES master_opd (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Junction 1: OPD menjalankan bidang-urusan apa (1 OPD bisa banyak)
CREATE TABLE opd_bidang_urusan (
  id INT NOT NULL AUTO_INCREMENT,
  opd_id INT NOT NULL,
  bidang_urusan_id INT NOT NULL,
  is_dominant TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT current_timestamp(),
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY uk_opd_bidang_urusan (opd_id, bidang_urusan_id),
  KEY idx_obu_bidang (bidang_urusan_id),
  CONSTRAINT fk_obu_opd FOREIGN KEY (opd_id) REFERENCES master_opd (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_obu_bidang FOREIGN KEY (bidang_urusan_id) REFERENCES master_bidang (id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Junction 2: unit internal OPD menangani bidang-urusan apa
CREATE TABLE opd_unit_bidang_urusan (
  id INT NOT NULL AUTO_INCREMENT,
  opd_unit_id INT NOT NULL,
  bidang_urusan_id INT NOT NULL,
  created_at TIMESTAMP NULL DEFAULT current_timestamp(),
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY uk_opd_unit_bidang_urusan (opd_unit_id, bidang_urusan_id),
  KEY idx_oubu_bidang (bidang_urusan_id),
  CONSTRAINT fk_oubu_unit FOREIGN KEY (opd_unit_id) REFERENCES master_opd_unit (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_oubu_bidang FOREIGN KEY (bidang_urusan_id) REFERENCES master_bidang (id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 3. REFERENSI & PEGAWAI & PENERIMA
-- =====================================================================
CREATE TABLE ref_bank (
  id BIGINT(20) NOT NULL AUTO_INCREMENT,
  kode_bank VARCHAR(20) DEFAULT NULL,
  nama_bank VARCHAR(120) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT current_timestamp(),
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_ref_bank_nama (nama_bank)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE ref_jabatan (
  id BIGINT(20) NOT NULL AUTO_INCREMENT,
  kode_jabatan VARCHAR(50) DEFAULT NULL,
  nama_jabatan VARCHAR(255) NOT NULL,
  singkatan_jabatan VARCHAR(100) DEFAULT NULL,
  jenis_jabatan ENUM('STRUKTURAL','PENATAUSAHAAN','FUNGSIONAL','LAINNYA') NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT current_timestamp(),
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_ref_jabatan_nama (nama_jabatan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE pegawai (
  id BIGINT(20) NOT NULL AUTO_INCREMENT,
  nama_lengkap VARCHAR(255) NOT NULL,
  status_kepegawaian ENUM('ASN','NON_ASN') NOT NULL,
  jenis_kepegawaian ENUM('PNS','PPPK','NON_ASN') NOT NULL,
  nip VARCHAR(30) DEFAULT NULL,
  npwp VARCHAR(32) DEFAULT NULL,
  opd_id INT NOT NULL,
  opd_unit_id INT DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT current_timestamp(),
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_pegawai_nip (nip),
  CONSTRAINT fk_pegawai_opd FOREIGN KEY (opd_id) REFERENCES master_opd (id) ON UPDATE CASCADE,
  CONSTRAINT fk_pegawai_opd_unit FOREIGN KEY (opd_unit_id) REFERENCES master_opd_unit (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE pegawai_jabatan (
  id BIGINT(20) NOT NULL AUTO_INCREMENT,
  pegawai_id BIGINT(20) NOT NULL,
  jabatan_id BIGINT(20) NOT NULL,
  opd_id INT NOT NULL,
  opd_unit_id INT DEFAULT NULL,
  tmt_mulai DATE DEFAULT NULL,
  tmt_selesai DATE DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT current_timestamp(),
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_pj_lookup (opd_id, opd_unit_id, jabatan_id),
  CONSTRAINT fk_pj_pegawai FOREIGN KEY (pegawai_id) REFERENCES pegawai (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_pj_jabatan FOREIGN KEY (jabatan_id) REFERENCES ref_jabatan (id) ON UPDATE CASCADE,
  CONSTRAINT fk_pj_opd FOREIGN KEY (opd_id) REFERENCES master_opd (id) ON UPDATE CASCADE,
  CONSTRAINT fk_pj_opd_unit FOREIGN KEY (opd_unit_id) REFERENCES master_opd_unit (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE pegawai_rekening (
  id BIGINT(20) NOT NULL AUTO_INCREMENT,
  pegawai_id BIGINT(20) NOT NULL,
  bank_id BIGINT(20) NOT NULL,
  no_rekening VARCHAR(50) NOT NULL,
  nama_pemilik_rekening VARCHAR(255) NOT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT current_timestamp(),
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_pegawai_rekening (pegawai_id, bank_id, no_rekening),
  CONSTRAINT fk_pegrek_pegawai FOREIGN KEY (pegawai_id) REFERENCES pegawai (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_pegrek_bank FOREIGN KEY (bank_id) REFERENCES ref_bank (id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE master_penerima (
  id INT NOT NULL AUTO_INCREMENT,
  nama_penerima VARCHAR(150) NOT NULL,
  -- asn = PNS/PPPK (pajak beda), non_asn = perorangan non-ASN, badan = vendor/badan hukum
  jenis_penerima ENUM('asn','non_asn','badan') NOT NULL,
  punya_npwp TINYINT(1) NOT NULL DEFAULT 0,
  npwp VARCHAR(30) DEFAULT NULL,
  -- Golongan hanya berlaku untuk ASN (menentukan tarif PPh 21)
  golongan ENUM('I','II','III','IV') DEFAULT NULL,
  nama_bank VARCHAR(50) DEFAULT NULL,
  no_rekening VARCHAR(50) DEFAULT NULL,
  nama_rekening VARCHAR(150) DEFAULT NULL,
  alamat TEXT DEFAULT NULL,
  keterangan TEXT DEFAULT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT current_timestamp(),
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 4. SKEMA PAJAK
-- =====================================================================
CREATE TABLE master_skema_pajak (
  id INT NOT NULL AUTO_INCREMENT,
  kode_skema VARCHAR(30) DEFAULT NULL,
  nama_skema VARCHAR(150) DEFAULT NULL,
  kategori VARCHAR(50) NOT NULL,
  keterangan TEXT DEFAULT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY uk_skema (kode_skema)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE master_skema_pajak_detail (
  id INT NOT NULL AUTO_INCREMENT,
  skema_id INT DEFAULT NULL,
  jenis_pajak ENUM('PPH21','PPH22','PPH23','PPH4_2','PPN','PDRD') DEFAULT NULL,
  batas_min DECIMAL(15,2) DEFAULT 0.00,
  batas_max DECIMAL(15,2) DEFAULT NULL,
  punya_npwp TINYINT(1) DEFAULT NULL,
  tarif DECIMAL(5,2) DEFAULT NULL,
  basis_penghitungan ENUM('langsung','ppn_included','setelah_ppn') NOT NULL DEFAULT 'langsung',
  rumus VARCHAR(255) NOT NULL,
  keterangan VARCHAR(255) DEFAULT NULL,
  kelompok ENUM('opsional','exclusive') DEFAULT 'opsional',
  golongan_honor VARCHAR(10) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_skema_id (skema_id),
  CONSTRAINT fk_skema_detail FOREIGN KEY (skema_id) REFERENCES master_skema_pajak (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 5. ANGGARAN: DPA (raw SIPD) + ARUS KAS
-- =====================================================================
CREATE TABLE dpa (
  id BIGINT(20) NOT NULL AUTO_INCREMENT,
  tahun SMALLINT(6) NOT NULL,
  opd_id INT NOT NULL,
  unit_opd_kode VARCHAR(50) DEFAULT NULL,
  unit_opd_nama VARCHAR(255) DEFAULT NULL,
  nomor_dokumen VARCHAR(120) DEFAULT NULL,
  tanggal_dokumen DATE DEFAULT NULL,
  sumber_file VARCHAR(255) DEFAULT NULL,
  created_by BIGINT(20) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT current_timestamp(),
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_dpa_tahun_opd (tahun, opd_id),
  CONSTRAINT fk_dpa_opd FOREIGN KEY (opd_id) REFERENCES master_opd (id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- dpa_detail: FK hanya ke dpa; id nomenklatur diindeks (bukan FK) agar
-- toleran terhadap data raw SIPD (mengikuti pola trx_dpa_detail literasi).
CREATE TABLE dpa_detail (
  id BIGINT(20) NOT NULL AUTO_INCREMENT,
  dpa_id BIGINT(20) NOT NULL,
  no_urut INT DEFAULT NULL,
  urusan_id INT DEFAULT NULL,
  bidang_id INT DEFAULT NULL,
  program_id INT DEFAULT NULL,
  kegiatan_id INT DEFAULT NULL,
  subkegiatan_id INT DEFAULT NULL,
  rekening_id INT DEFAULT NULL,
  kode_skpd VARCHAR(50) DEFAULT NULL,
  nama_skpd VARCHAR(255) DEFAULT NULL,
  paket_belanja VARCHAR(255) DEFAULT NULL,
  keterangan_belanja TEXT DEFAULT NULL,
  sumber_dana_id BIGINT(20) DEFAULT NULL,
  sumber_dana_text VARCHAR(255) DEFAULT NULL,
  nama_penerima_bantuan VARCHAR(255) DEFAULT NULL,
  kode_standar_harga VARCHAR(100) DEFAULT NULL,
  nama_standar_harga VARCHAR(255) DEFAULT NULL,
  spesifikasi TEXT DEFAULT NULL,
  koefisien_murni VARCHAR(100) DEFAULT NULL,
  harga_satuan_murni DECIMAL(20,2) NOT NULL DEFAULT 0.00,
  total_harga_murni DECIMAL(20,2) NOT NULL DEFAULT 0.00,
  koefisien VARCHAR(100) DEFAULT NULL,
  harga_satuan DECIMAL(20,2) NOT NULL DEFAULT 0.00,
  total_harga DECIMAL(20,2) NOT NULL DEFAULT 0.00,
  created_at DATETIME NOT NULL DEFAULT current_timestamp(),
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_dpa_detail_dpa (dpa_id),
  KEY idx_dpa_detail_subkeg (subkegiatan_id),
  KEY idx_dpa_detail_rekening (rekening_id),
  CONSTRAINT fk_dpa_detail_dpa FOREIGN KEY (dpa_id) REFERENCES dpa (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE anggaran_kas (
  id BIGINT(20) NOT NULL AUTO_INCREMENT,
  tahun SMALLINT(6) NOT NULL,
  opd_id INT NOT NULL,
  urusan_id INT DEFAULT NULL,
  bidang_id INT DEFAULT NULL,
  program_id INT NOT NULL,
  kegiatan_id INT NOT NULL,
  subkegiatan_id INT NOT NULL,
  rekening_id INT NOT NULL,
  pagu_tahunan DECIMAL(20,2) NOT NULL DEFAULT 0.00,
  created_by BIGINT(20) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT current_timestamp(),
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_anggaran_kas (tahun, opd_id, program_id, kegiatan_id, subkegiatan_id, rekening_id),
  KEY idx_ak_lookup (opd_id, program_id, kegiatan_id, subkegiatan_id),
  CONSTRAINT fk_ak_opd FOREIGN KEY (opd_id) REFERENCES master_opd (id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE anggaran_kas_bulanan (
  id BIGINT(20) NOT NULL AUTO_INCREMENT,
  anggaran_kas_id BIGINT(20) NOT NULL,
  bulan TINYINT(4) NOT NULL,
  nilai_maksimal DECIMAL(20,2) NOT NULL DEFAULT 0.00,
  created_at DATETIME NOT NULL DEFAULT current_timestamp(),
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_ak_bulanan (anggaran_kas_id, bulan),
  KEY idx_ak_bulanan_bulan (bulan),
  CONSTRAINT fk_akb_parent FOREIGN KEY (anggaran_kas_id) REFERENCES anggaran_kas (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 6. TRANSAKSI: NPD -> PINDAH BUKU -> PAJAK  (skema disiapkan utk Tahap 2+)
-- =====================================================================
CREATE TABLE npd (
  id INT NOT NULL AUTO_INCREMENT,
  nomor_npd VARCHAR(100) NOT NULL,
  tanggal DATE NOT NULL,
  perihal VARCHAR(255) NOT NULL,
  opd_id INT DEFAULT NULL,
  opd_unit_id INT DEFAULT NULL,
  urusan_id INT DEFAULT NULL,
  bidang_id INT DEFAULT NULL,
  program_id INT DEFAULT NULL,
  kegiatan_id INT DEFAULT NULL,
  subkegiatan_id INT DEFAULT NULL,
  pekerjaan TEXT NOT NULL,
  sumber_dana_id INT DEFAULT NULL,
  status ENUM('draft','final','dibayar') NOT NULL DEFAULT 'draft',
  keterangan TEXT DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_at DATETIME DEFAULT current_timestamp(),
  updated_at DATETIME DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  KEY idx_npd_subkeg (subkegiatan_id),
  KEY idx_npd_opd (opd_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE npd_detail (
  id INT NOT NULL AUTO_INCREMENT,
  npd_id INT NOT NULL,
  dpa_detail_id BIGINT(20) DEFAULT NULL,
  rekening_id INT NOT NULL,
  jumlah DECIMAL(20,2) NOT NULL DEFAULT 0.00,
  created_at DATETIME DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY idx_npd_detail_npd (npd_id),
  KEY idx_npd_detail_dpa (dpa_detail_id),
  CONSTRAINT fk_npd_detail_npd FOREIGN KEY (npd_id) REFERENCES npd (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE npd_pinbuk (
  id INT NOT NULL AUTO_INCREMENT,
  nomor VARCHAR(50) NOT NULL,
  npd_id INT NOT NULL,
  npd_detail_id INT NOT NULL,
  penerima_id INT DEFAULT NULL,
  jenis_transaksi VARCHAR(50) NOT NULL,
  kategori_pajak VARCHAR(50) DEFAULT NULL,
  nilai_bruto DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  total_pajak DECIMAL(15,2) DEFAULT 0.00,
  nilai_netto DECIMAL(15,2) DEFAULT 0.00,
  status ENUM('draft','final','dibayar') DEFAULT 'draft',
  tanggal_pinbuk DATE DEFAULT NULL,
  tanggal_persediaan DATE DEFAULT NULL,
  keterangan TEXT DEFAULT NULL,
  created_at DATETIME DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_pinbuk_npd (npd_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE npd_pinbuk_rincian (
  id INT NOT NULL AUTO_INCREMENT,
  pinbuk_id INT NOT NULL,
  penerima_id INT NOT NULL,
  volume INT NOT NULL DEFAULT 1,
  harga_satuan DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  jumlah DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  keterangan VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT current_timestamp(),
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_pinbuk_rincian_pinbuk (pinbuk_id),
  KEY idx_pinbuk_rincian_penerima (penerima_id),
  CONSTRAINT fk_pinbuk_rincian_pinbuk FOREIGN KEY (pinbuk_id) REFERENCES npd_pinbuk (id) ON DELETE CASCADE,
  CONSTRAINT fk_pinbuk_rincian_penerima FOREIGN KEY (penerima_id) REFERENCES master_penerima (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE npd_pinbuk_pajak (
  id INT NOT NULL AUTO_INCREMENT,
  pinbuk_id INT NOT NULL,
  nama_pajak VARCHAR(20) DEFAULT NULL,
  tarif DECIMAL(5,2) DEFAULT NULL,
  dasar_pengenaan DECIMAL(15,2) DEFAULT NULL,
  nilai_pajak DECIMAL(15,2) DEFAULT NULL,
  created_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_pinbuk_pajak_pinbuk (pinbuk_id),
  CONSTRAINT fk_pinbuk_pajak_pinbuk FOREIGN KEY (pinbuk_id) REFERENCES npd_pinbuk (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 7. USER & HAK AKSES
-- =====================================================================
CREATE TABLE users (
  id INT NOT NULL AUTO_INCREMENT,
  nip VARCHAR(30) DEFAULT NULL,
  username VARCHAR(50) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  nama VARCHAR(150) NOT NULL,
  pegawai_id BIGINT(20) DEFAULT NULL,
  role ENUM('superadmin','admin_opd','user_opd') NOT NULL DEFAULT 'user_opd',
  opd_id INT DEFAULT NULL,
  opd_unit_id INT DEFAULT NULL,
  is_active TINYINT(1) DEFAULT 1,
  last_login DATETIME DEFAULT NULL,
  created_at DATETIME DEFAULT current_timestamp(),
  updated_at DATETIME DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY uk_users_nip (nip),
  UNIQUE KEY uk_users_username (username),
  KEY idx_users_pegawai (pegawai_id),
  CONSTRAINT fk_users_opd FOREIGN KEY (opd_id) REFERENCES master_opd (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_users_opd_unit FOREIGN KEY (opd_unit_id) REFERENCES master_opd_unit (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_users_pegawai FOREIGN KEY (pegawai_id) REFERENCES pegawai (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tag scope opsional untuk kontrol akses granular (mis. user_opd diberi
-- akses tambahan ke unit / bidang-urusan tertentu di luar scope default).
CREATE TABLE user_akses (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  opd_unit_id INT DEFAULT NULL,
  bidang_urusan_id INT DEFAULT NULL,
  created_at DATETIME DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY idx_user_akses_user (user_id),
  CONSTRAINT fk_user_akses_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_user_akses_unit FOREIGN KEY (opd_unit_id) REFERENCES master_opd_unit (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_user_akses_bidang FOREIGN KEY (bidang_urusan_id) REFERENCES master_bidang (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
