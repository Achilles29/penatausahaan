-- =====================================================================
-- IMPORT DATA: literasi -> penatus
-- Copy nomenklatur/DPA/arus kas + transform pemetaan OPD + seed pajak.
-- Idempotent: hapus dulu isi tabel target, lalu isi ulang.
-- User (butuh password_hash) di-seed via controller Setup (PHP), bukan di sini.
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Bersihkan target (aman dijalankan ulang)
DELETE FROM penatus.master_skema_pajak_detail;
DELETE FROM penatus.master_skema_pajak;
DELETE FROM penatus.master_penerima;
DELETE FROM penatus.anggaran_kas_bulanan;
DELETE FROM penatus.anggaran_kas;
DELETE FROM penatus.dpa_detail;
DELETE FROM penatus.dpa;
DELETE FROM penatus.pegawai_rekening;
DELETE FROM penatus.pegawai_jabatan;
DELETE FROM penatus.pegawai;
DELETE FROM penatus.ref_jabatan;
DELETE FROM penatus.ref_bank;
DELETE FROM penatus.opd_unit_bidang_urusan;
DELETE FROM penatus.opd_bidang_urusan;
DELETE FROM penatus.master_opd_unit;
DELETE FROM penatus.master_opd;
DELETE FROM penatus.master_sumber_dana;
DELETE FROM penatus.master_rekening;
DELETE FROM penatus.master_subkegiatan;
DELETE FROM penatus.master_kegiatan;
DELETE FROM penatus.master_program;
DELETE FROM penatus.master_bidang;
DELETE FROM penatus.master_urusan;

-- ---------- Nomenklatur (copy langsung, kolom identik) ----------
INSERT INTO penatus.master_urusan (id, kode_urusan, nama_urusan, created_at, updated_at)
  SELECT id, kode_urusan, nama_urusan, created_at, updated_at FROM literasi.master_urusan;

INSERT INTO penatus.master_bidang (id, urusan_id, kode_bidang, nama_bidang, created_at, updated_at)
  SELECT id, urusan_id, kode_bidang, nama_bidang, created_at, updated_at FROM literasi.master_bidang;

INSERT INTO penatus.master_program (id, bidang_id, kode_program, nama_program, created_at, updated_at)
  SELECT id, bidang_id, kode_program, nama_program, created_at, updated_at FROM literasi.master_program;

INSERT INTO penatus.master_kegiatan (id, program_id, kode_kegiatan, nama_kegiatan, created_at, updated_at)
  SELECT id, program_id, kode_kegiatan, nama_kegiatan, created_at, updated_at FROM literasi.master_kegiatan;

INSERT INTO penatus.master_subkegiatan (id, kegiatan_id, kode_subkegiatan, nama_subkegiatan, created_at, updated_at)
  SELECT id, kegiatan_id, kode_subkegiatan, nama_subkegiatan, created_at, updated_at FROM literasi.master_subkegiatan;

INSERT INTO penatus.master_rekening (id, kode_rekening, uraian, jenis_belanja, created_at, updated_at)
  SELECT id, kode_rekening, uraian, jenis_belanja, created_at, updated_at FROM literasi.master_rekening;

INSERT INTO penatus.master_sumber_dana (id, kode, nama, keterangan, is_active, created_at, updated_at)
  SELECT id, kode, nama, keterangan, is_active, created_at, updated_at FROM literasi.master_sumber_dana;

-- ---------- Organisasi ----------
INSERT INTO penatus.master_opd (id, kode_opd, nama_opd, singkatan, dominant_bidang_id, is_active, kepala_opd, nip_kepala, created_at, updated_at)
  SELECT id, kode_opd, nama_opd, singkatan, dominant_bidang_id, is_active, kepala_opd, nip_kepala, created_at, updated_at FROM literasi.master_opd;

-- master_opd_bidang -> master_opd_unit (id dipertahankan; jenis_unit diturunkan dari nama)
INSERT INTO penatus.master_opd_unit (id, opd_id, kode_unit, nama_unit, jenis_unit, kepala, nip_kepala, is_active, created_at, updated_at)
  SELECT id, opd_id, kode_bidang_opd, nama_bidang_opd,
         CASE
           WHEN UPPER(nama_bidang_opd) LIKE '%SEKRETARIAT%' THEN 'sekretariat'
           WHEN UPPER(nama_bidang_opd) LIKE '%UPTD%' THEN 'uptd'
           ELSE 'bidang'
         END,
         kepala_bidang, nip_kepala, 1, created_at, updated_at
  FROM literasi.master_opd_bidang;

-- Junction OPD <-> bidang-urusan (gabung 2 sumber, dedup via unique key)
INSERT INTO penatus.opd_bidang_urusan (opd_id, bidang_urusan_id, is_dominant)
  SELECT opd_id, bidang_urusan_id, is_dominant FROM literasi.map_opd_bidang_urusan;
INSERT IGNORE INTO penatus.opd_bidang_urusan (opd_id, bidang_urusan_id, is_dominant)
  SELECT opd_id, bidang_urusan_id, 0 FROM literasi.master_opd_urusan_bidang;

-- Junction unit OPD <-> bidang-urusan (opd_bidang_id = opd_unit_id; gabung 2 sumber)
INSERT IGNORE INTO penatus.opd_unit_bidang_urusan (opd_unit_id, bidang_urusan_id)
  SELECT opd_bidang_id, bidang_urusan_id FROM literasi.map_opd_bidang_to_bidang_urusan;
INSERT IGNORE INTO penatus.opd_unit_bidang_urusan (opd_unit_id, bidang_urusan_id)
  SELECT opd_bidang_id, bidang_urusan_id FROM literasi.master_opd_bidang_urusan;

-- ---------- Referensi & pegawai ----------
INSERT INTO penatus.ref_bank (id, kode_bank, nama_bank, is_active, created_at, updated_at)
  SELECT id, kode_bank, nama_bank, is_active, created_at, updated_at FROM literasi.ref_bank;

INSERT INTO penatus.ref_jabatan (id, kode_jabatan, nama_jabatan, singkatan_jabatan, jenis_jabatan, is_active, created_at, updated_at)
  SELECT id, kode_jabatan, nama_jabatan, singkatan_jabatan, jenis_jabatan, is_active, created_at, updated_at FROM literasi.ref_jabatan;

INSERT INTO penatus.pegawai (id, nama_lengkap, status_kepegawaian, jenis_kepegawaian, nip, npwp, opd_id, opd_unit_id, is_active, created_at, updated_at)
  SELECT id, nama_lengkap, status_kepegawaian, jenis_kepegawaian, nip, npwp, opd_id, opd_bidang_id, is_active, created_at, updated_at FROM literasi.pegawai;

-- ---------- Anggaran: DPA + arus kas ----------
INSERT INTO penatus.dpa (id, tahun, opd_id, unit_opd_kode, unit_opd_nama, nomor_dokumen, tanggal_dokumen, sumber_file, created_by, created_at, updated_at)
  SELECT id, tahun, opd_id, unit_opd_kode, unit_opd_nama, nomor_dokumen, tanggal_dokumen, sumber_file, created_by, created_at, updated_at FROM literasi.trx_dpa;

INSERT INTO penatus.dpa_detail (id, dpa_id, no_urut, urusan_id, bidang_id, program_id, kegiatan_id, subkegiatan_id, rekening_id, kode_skpd, nama_skpd, paket_belanja, keterangan_belanja, sumber_dana_id, sumber_dana_text, nama_penerima_bantuan, kode_standar_harga, nama_standar_harga, spesifikasi, koefisien_murni, harga_satuan_murni, total_harga_murni, koefisien, harga_satuan, total_harga, created_at, updated_at)
  SELECT id, dpa_id, no_urut, urusan_id, bidang_id, program_id, kegiatan_id, subkegiatan_id, rekening_id, kode_skpd, nama_skpd, paket_belanja, keterangan_belanja, sumber_dana_id, sumber_dana_text, nama_penerima_bantuan, kode_standar_harga, nama_standar_harga, spesifikasi, koefisien_murni, harga_satuan_murni, total_harga_murni, koefisien, harga_satuan, total_harga, created_at, updated_at FROM literasi.trx_dpa_detail;

INSERT INTO penatus.anggaran_kas (id, tahun, opd_id, urusan_id, bidang_id, program_id, kegiatan_id, subkegiatan_id, rekening_id, pagu_tahunan, created_by, created_at, updated_at)
  SELECT id, tahun, opd_id, urusan_id, bidang_id, program_id, kegiatan_id, subkegiatan_id, rekening_id, pagu_tahunan, created_by, created_at, updated_at FROM literasi.anggaran_kas;

INSERT INTO penatus.anggaran_kas_bulanan (id, anggaran_kas_id, bulan, nilai_maksimal, created_at, updated_at)
  SELECT id, anggaran_kas_id, bulan, nilai_maksimal, created_at, updated_at FROM literasi.anggaran_kas_bulanan;

-- ---------- Seed skema pajak (template awal, dapat diedit via CRUD) ----------
INSERT INTO penatus.master_skema_pajak (id, kode_skema, nama_skema, kategori, keterangan, is_active) VALUES
  (1,'PPH21_HONOR','PPh 21 Honorarium','honorarium','Pemotongan PPh 21 atas honorarium (final utk PNS per golongan)',1),
  (2,'PPH22_BARANG','PPh 22 Belanja Barang','barang','PPh 22 atas belanja barang di atas batas',1),
  (3,'PPH23_JASA','PPh 23 Jasa','jasa','PPh 23 atas imbalan jasa',1),
  (4,'PPN','PPN','ppn','PPN 11% atas penyerahan BKP/JKP oleh PKP',1),
  (5,'NONPAJAK','Tanpa Pajak','nonpajak','Transaksi tanpa pemotongan pajak',1);

INSERT INTO penatus.master_skema_pajak_detail (skema_id, jenis_pajak, batas_min, batas_max, punya_npwp, tarif, basis_penghitungan, rumus, keterangan, kelompok, golongan_honor) VALUES
  (1,'PPH21',0,NULL,NULL,0.00,'langsung','bruto*0%','Golongan I: 0% (final)','exclusive','I'),
  (1,'PPH21',0,NULL,NULL,0.00,'langsung','bruto*0%','Golongan II: 0% (final)','exclusive','II'),
  (1,'PPH21',0,NULL,NULL,5.00,'langsung','bruto*5%','Golongan III: 5% (final)','exclusive','III'),
  (1,'PPH21',0,NULL,NULL,15.00,'langsung','bruto*15%','Golongan IV: 15% (final)','exclusive','IV'),
  (1,'PPH21',0,NULL,1,5.00,'langsung','bruto*5%','Non-PNS ber-NPWP: 5%','exclusive','NON_PNS'),
  (1,'PPH21',0,NULL,0,6.00,'langsung','bruto*6%','Non-PNS tanpa NPWP: 5% +20%','exclusive','NON_PNS'),
  (2,'PPH22',2000000,NULL,1,1.50,'setelah_ppn','dpp*1.5%','Ber-NPWP 1,5% dari DPP (di atas 2 juta)','opsional',NULL),
  (2,'PPH22',2000000,NULL,0,3.00,'setelah_ppn','dpp*3%','Tanpa NPWP 3% dari DPP','opsional',NULL),
  (3,'PPH23',0,NULL,1,2.00,'setelah_ppn','dpp*2%','Ber-NPWP 2% dari DPP','opsional',NULL),
  (3,'PPH23',0,NULL,0,4.00,'setelah_ppn','dpp*4%','Tanpa NPWP 4% dari DPP','opsional',NULL),
  (4,'PPN',0,NULL,NULL,11.00,'ppn_included','bruto*100/111*11%','PPN 11% (harga termasuk PPN)','opsional',NULL);

-- ---------- Seed contoh penerima ----------
INSERT INTO penatus.master_penerima (nama_penerima, jenis_penerima, punya_npwp, npwp, golongan, nama_bank, no_rekening, nama_rekening, is_active) VALUES
  ('MUKHAMMAD ANWAR FUADI','orang',0,NULL,'III','BPD JATENG','3-123-456789','MUKHAMMAD ANWAR FUADI',1),
  ('CV MITRA SEJAHTERA','badan',1,'01.234.567.8-901.000','NON_PNS','BCA','1234567890','CV MITRA SEJAHTERA',1);

-- Selaraskan AUTO_INCREMENT tabel yang di-seed manual
ALTER TABLE penatus.master_skema_pajak AUTO_INCREMENT = 6;

SET FOREIGN_KEY_CHECKS = 1;
