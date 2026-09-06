/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `anggaran_kas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `anggaran_kas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tahun` smallint(6) NOT NULL,
  `opd_id` int(11) NOT NULL,
  `urusan_id` int(11) DEFAULT NULL,
  `bidang_id` int(11) DEFAULT NULL,
  `program_id` int(11) NOT NULL,
  `kegiatan_id` int(11) NOT NULL,
  `subkegiatan_id` int(11) NOT NULL,
  `rekening_id` int(11) NOT NULL,
  `pagu_tahunan` decimal(20,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_anggaran_kas` (`tahun`,`opd_id`,`program_id`,`kegiatan_id`,`subkegiatan_id`,`rekening_id`) USING BTREE,
  KEY `idx_ak_lookup` (`opd_id`,`program_id`,`kegiatan_id`,`subkegiatan_id`) USING BTREE,
  CONSTRAINT `fk_ak_opd` FOREIGN KEY (`opd_id`) REFERENCES `master_opd` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `anggaran_kas_bulanan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `anggaran_kas_bulanan` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `anggaran_kas_id` bigint(20) NOT NULL,
  `bulan` tinyint(4) NOT NULL,
  `nilai_maksimal` decimal(20,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_ak_bulanan` (`anggaran_kas_id`,`bulan`) USING BTREE,
  KEY `idx_ak_bulanan_bulan` (`bulan`) USING BTREE,
  CONSTRAINT `fk_akb_parent` FOREIGN KEY (`anggaran_kas_id`) REFERENCES `anggaran_kas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dpa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dpa` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tahun` smallint(6) NOT NULL,
  `opd_id` int(11) NOT NULL,
  `unit_opd_kode` varchar(50) DEFAULT NULL,
  `unit_opd_nama` varchar(255) DEFAULT NULL,
  `nomor_dokumen` varchar(120) DEFAULT NULL,
  `tanggal_dokumen` date DEFAULT NULL,
  `sumber_file` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_dpa_tahun_opd` (`tahun`,`opd_id`) USING BTREE,
  KEY `fk_dpa_opd` (`opd_id`) USING BTREE,
  CONSTRAINT `fk_dpa_opd` FOREIGN KEY (`opd_id`) REFERENCES `master_opd` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dpa_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dpa_detail` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `dpa_id` bigint(20) NOT NULL,
  `no_urut` int(11) DEFAULT NULL,
  `urusan_id` int(11) DEFAULT NULL,
  `bidang_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `kegiatan_id` int(11) DEFAULT NULL,
  `subkegiatan_id` int(11) DEFAULT NULL,
  `rekening_id` int(11) DEFAULT NULL,
  `kode_skpd` varchar(50) DEFAULT NULL,
  `nama_skpd` varchar(255) DEFAULT NULL,
  `paket_belanja` varchar(255) DEFAULT NULL,
  `keterangan_belanja` text DEFAULT NULL,
  `sumber_dana_id` bigint(20) DEFAULT NULL,
  `sumber_dana_text` varchar(255) DEFAULT NULL,
  `nama_penerima_bantuan` varchar(255) DEFAULT NULL,
  `kode_standar_harga` varchar(100) DEFAULT NULL,
  `nama_standar_harga` varchar(255) DEFAULT NULL,
  `spesifikasi` text DEFAULT NULL,
  `koefisien_murni` varchar(100) DEFAULT NULL,
  `harga_satuan_murni` decimal(20,2) NOT NULL DEFAULT 0.00,
  `total_harga_murni` decimal(20,2) NOT NULL DEFAULT 0.00,
  `koefisien` varchar(100) DEFAULT NULL,
  `harga_satuan` decimal(20,2) NOT NULL DEFAULT 0.00,
  `total_harga` decimal(20,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_dpa_detail_dpa` (`dpa_id`) USING BTREE,
  KEY `idx_dpa_detail_subkeg` (`subkegiatan_id`) USING BTREE,
  KEY `idx_dpa_detail_rekening` (`rekening_id`) USING BTREE,
  CONSTRAINT `fk_dpa_detail_dpa` FOREIGN KEY (`dpa_id`) REFERENCES `dpa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_bidang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_bidang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `urusan_id` int(11) NOT NULL,
  `kode_bidang` varchar(10) NOT NULL,
  `nama_bidang` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_bidang` (`urusan_id`,`kode_bidang`) USING BTREE,
  CONSTRAINT `fk_bidang_urusan` FOREIGN KEY (`urusan_id`) REFERENCES `master_urusan` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_kegiatan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_kegiatan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `kode_kegiatan` varchar(20) NOT NULL,
  `nama_kegiatan` varchar(500) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_kegiatan` (`program_id`,`kode_kegiatan`) USING BTREE,
  CONSTRAINT `fk_kegiatan_program` FOREIGN KEY (`program_id`) REFERENCES `master_program` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_opd`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_opd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_opd` varchar(30) NOT NULL,
  `nama_opd` varchar(500) NOT NULL,
  `singkatan` varchar(50) DEFAULT NULL,
  `dominant_bidang_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `kepala_opd` varchar(255) DEFAULT NULL,
  `nip_kepala` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_kode_opd` (`kode_opd`) USING BTREE,
  KEY `fk_opd_dominant_bidang` (`dominant_bidang_id`) USING BTREE,
  CONSTRAINT `fk_opd_dominant_bidang` FOREIGN KEY (`dominant_bidang_id`) REFERENCES `master_bidang` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_opd_unit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_opd_unit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `opd_id` int(11) NOT NULL,
  `kode_unit` varchar(20) DEFAULT NULL,
  `nama_unit` varchar(255) NOT NULL,
  `jenis_unit` enum('sekretariat','bidang','uptd','lainnya') NOT NULL DEFAULT 'bidang',
  `kepala` varchar(255) DEFAULT NULL,
  `nip_kepala` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_opd_unit` (`opd_id`,`nama_unit`) USING BTREE,
  CONSTRAINT `fk_opd_unit_opd` FOREIGN KEY (`opd_id`) REFERENCES `master_opd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_penerima`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_penerima` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pegawai_id` bigint(20) DEFAULT NULL,
  `nama_penerima` varchar(150) NOT NULL,
  `jenis_penerima` enum('asn','non_asn','badan') NOT NULL,
  `punya_npwp` tinyint(1) NOT NULL DEFAULT 0,
  `npwp` varchar(30) DEFAULT NULL,
  `golongan` enum('I','II','III','IV') DEFAULT NULL,
  `nama_bank` varchar(50) DEFAULT NULL,
  `no_rekening` varchar(50) DEFAULT NULL,
  `nama_rekening` varchar(150) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_mp_pegawai` (`pegawai_id`) USING BTREE,
  CONSTRAINT `fk_mp_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_program`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_program` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bidang_id` int(11) NOT NULL,
  `kode_program` varchar(15) NOT NULL,
  `nama_program` varchar(500) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_program` (`bidang_id`,`kode_program`) USING BTREE,
  CONSTRAINT `fk_program_bidang` FOREIGN KEY (`bidang_id`) REFERENCES `master_bidang` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_rekening`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_rekening` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_rekening` varchar(50) NOT NULL,
  `uraian` text NOT NULL,
  `jenis_belanja` varchar(50) DEFAULT NULL,
  `kategori_pajak` varchar(30) DEFAULT NULL COMMENT 'Klasifikasi jenis belanja untuk penentuan pajak; tertaut ke master_skema_pajak.kategori',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_kode_rekening` (`kode_rekening`) USING BTREE,
  KEY `idx_rekening_kategori_pajak` (`kategori_pajak`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_skema_pajak`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_skema_pajak` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_skema` varchar(30) DEFAULT NULL,
  `nama_skema` varchar(150) DEFAULT NULL,
  `kategori` varchar(50) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_skema` (`kode_skema`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_skema_pajak_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_skema_pajak_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skema_id` int(11) DEFAULT NULL,
  `jenis_pajak` enum('PPH21','PPH22','PPH23','PPH4_2','PPN','PDRD') DEFAULT NULL,
  `batas_min` decimal(15,2) DEFAULT 0.00,
  `batas_max` decimal(15,2) DEFAULT NULL,
  `punya_npwp` tinyint(1) DEFAULT NULL,
  `tarif` decimal(5,2) DEFAULT NULL,
  `basis_penghitungan` enum('langsung','ppn_included','setelah_ppn') NOT NULL DEFAULT 'langsung',
  `rumus` varchar(255) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `kelompok` enum('opsional','exclusive') DEFAULT 'opsional',
  `golongan_honor` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_skema_id` (`skema_id`) USING BTREE,
  CONSTRAINT `fk_skema_detail` FOREIGN KEY (`skema_id`) REFERENCES `master_skema_pajak` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_subkegiatan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_subkegiatan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kegiatan_id` int(11) NOT NULL,
  `kode_subkegiatan` varchar(25) NOT NULL,
  `nama_subkegiatan` varchar(1000) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_subkegiatan` (`kegiatan_id`,`kode_subkegiatan`) USING BTREE,
  CONSTRAINT `fk_subkegiatan_kegiatan` FOREIGN KEY (`kegiatan_id`) REFERENCES `master_kegiatan` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_sumber_dana`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_sumber_dana` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(30) NOT NULL,
  `nama` varchar(500) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_sumber_dana_kode` (`kode`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_urusan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_urusan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_urusan` varchar(10) NOT NULL,
  `nama_urusan` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_kode_urusan` (`kode_urusan`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `npd`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `npd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nomor_npd` varchar(100) NOT NULL,
  `tanggal` date NOT NULL,
  `perihal` varchar(255) NOT NULL,
  `opd_id` int(11) DEFAULT NULL,
  `opd_unit_id` int(11) DEFAULT NULL,
  `urusan_id` int(11) DEFAULT NULL,
  `bidang_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `kegiatan_id` int(11) DEFAULT NULL,
  `subkegiatan_id` int(11) DEFAULT NULL,
  `pekerjaan` text NOT NULL,
  `sumber_dana_id` int(11) DEFAULT NULL,
  `status` enum('draft','final','dibayar') NOT NULL DEFAULT 'draft',
  `keterangan` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_npd_subkeg` (`subkegiatan_id`) USING BTREE,
  KEY `idx_npd_opd` (`opd_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `npd_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `npd_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `npd_id` int(11) NOT NULL,
  `dpa_detail_id` bigint(20) DEFAULT NULL,
  `rekening_id` int(11) NOT NULL,
  `jumlah` decimal(20,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_npd_detail_npd` (`npd_id`) USING BTREE,
  KEY `idx_npd_detail_dpa` (`dpa_detail_id`) USING BTREE,
  CONSTRAINT `fk_npd_detail_npd` FOREIGN KEY (`npd_id`) REFERENCES `npd` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `npd_penerima`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `npd_penerima` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `npd_detail_id` int(11) NOT NULL,
  `pegawai_id` bigint(20) DEFAULT NULL,
  `penerima_id` int(11) DEFAULT NULL,
  `nama_penerima` varchar(150) NOT NULL,
  `no_rekening` varchar(50) DEFAULT NULL,
  `npwp` varchar(30) DEFAULT NULL,
  `skema_pajak_id` int(11) DEFAULT NULL,
  `uraian` varchar(255) DEFAULT NULL,
  `komponen_pd` varchar(20) DEFAULT NULL,
  `volume` decimal(15,2) NOT NULL DEFAULT 1.00,
  `harga_satuan` decimal(20,2) NOT NULL DEFAULT 0.00,
  `jumlah` decimal(20,2) NOT NULL DEFAULT 0.00,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_np_detail` (`npd_detail_id`) USING BTREE,
  KEY `idx_np_penerima` (`penerima_id`) USING BTREE,
  KEY `idx_np_pegawai` (`pegawai_id`) USING BTREE,
  CONSTRAINT `fk_np_detail` FOREIGN KEY (`npd_detail_id`) REFERENCES `npd_detail` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_np_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_np_penerima` FOREIGN KEY (`penerima_id`) REFERENCES `master_penerima` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `npd_pinbuk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `npd_pinbuk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nomor` varchar(50) NOT NULL,
  `npd_id` int(11) NOT NULL,
  `npd_detail_id` int(11) NOT NULL,
  `penerima_id` int(11) DEFAULT NULL,
  `jenis_transaksi` varchar(50) NOT NULL,
  `kategori_pajak` varchar(50) DEFAULT NULL,
  `nilai_bruto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_pajak` decimal(15,2) DEFAULT 0.00,
  `nilai_netto` decimal(15,2) DEFAULT 0.00,
  `status` enum('draft','final','dibayar') DEFAULT 'draft',
  `tanggal_pinbuk` date DEFAULT NULL,
  `tanggal_persediaan` date DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_pinbuk_npd` (`npd_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `npd_pinbuk_pajak`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `npd_pinbuk_pajak` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pinbuk_id` int(11) NOT NULL,
  `nama_pajak` varchar(20) DEFAULT NULL,
  `tarif` decimal(5,2) DEFAULT NULL,
  `dasar_pengenaan` decimal(15,2) DEFAULT NULL,
  `nilai_pajak` decimal(15,2) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_pinbuk_pajak_pinbuk` (`pinbuk_id`) USING BTREE,
  CONSTRAINT `fk_pinbuk_pajak_pinbuk` FOREIGN KEY (`pinbuk_id`) REFERENCES `npd_pinbuk` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `npd_pinbuk_rincian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `npd_pinbuk_rincian` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pinbuk_id` int(11) NOT NULL,
  `penerima_id` int(11) NOT NULL,
  `volume` int(11) NOT NULL DEFAULT 1,
  `harga_satuan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `jumlah` decimal(15,2) NOT NULL DEFAULT 0.00,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_pinbuk_rincian_pinbuk` (`pinbuk_id`) USING BTREE,
  KEY `idx_pinbuk_rincian_penerima` (`penerima_id`) USING BTREE,
  CONSTRAINT `fk_pinbuk_rincian_penerima` FOREIGN KEY (`penerima_id`) REFERENCES `master_penerima` (`id`),
  CONSTRAINT `fk_pinbuk_rincian_pinbuk` FOREIGN KEY (`pinbuk_id`) REFERENCES `npd_pinbuk` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `opd_bidang_urusan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `opd_bidang_urusan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `opd_id` int(11) NOT NULL,
  `bidang_urusan_id` int(11) NOT NULL,
  `is_dominant` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_opd_bidang_urusan` (`opd_id`,`bidang_urusan_id`) USING BTREE,
  KEY `idx_obu_bidang` (`bidang_urusan_id`) USING BTREE,
  CONSTRAINT `fk_obu_bidang` FOREIGN KEY (`bidang_urusan_id`) REFERENCES `master_bidang` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_obu_opd` FOREIGN KEY (`opd_id`) REFERENCES `master_opd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `opd_unit_bidang_urusan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `opd_unit_bidang_urusan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `opd_unit_id` int(11) NOT NULL,
  `bidang_urusan_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_opd_unit_bidang_urusan` (`opd_unit_id`,`bidang_urusan_id`) USING BTREE,
  KEY `idx_oubu_bidang` (`bidang_urusan_id`) USING BTREE,
  CONSTRAINT `fk_oubu_bidang` FOREIGN KEY (`bidang_urusan_id`) REFERENCES `master_bidang` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_oubu_unit` FOREIGN KEY (`opd_unit_id`) REFERENCES `master_opd_unit` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pegawai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pegawai` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(255) NOT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `tgl_lahir` date DEFAULT NULL,
  `tgl_cpns` date DEFAULT NULL,
  `tgl_pns` date DEFAULT NULL,
  `status_kepegawaian` enum('ASN','NON_ASN') NOT NULL,
  `jenis_kepegawaian` enum('PNS','CPNS','PPPK','NON_ASN') NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `golongan` varchar(10) DEFAULT NULL,
  `pangkat` varchar(100) DEFAULT NULL,
  `status_pernikahan` enum('BELUM_KAWIN','KAWIN','JANDA','DUDA') DEFAULT 'BELUM_KAWIN',
  `jumlah_anak` tinyint(3) unsigned DEFAULT 0,
  `terima_tunjangan_keluarga` tinyint(1) NOT NULL DEFAULT 1,
  `masa_kerja_golongan` tinyint(3) unsigned DEFAULT 0,
  `tmt_kenaikan_pangkat` date DEFAULT NULL,
  `tmt_kgb` date DEFAULT NULL COMMENT 'TMT KGB Berikutnya (fallback jika tgl_pns tidak diisi)',
  `jabatan_struktural_id` bigint(20) DEFAULT NULL,
  `jabatan_penatausahaan_id` bigint(20) DEFAULT NULL,
  `jabatan_fungsional_id` bigint(20) DEFAULT NULL,
  `ref_tpp_id` int(11) DEFAULT NULL,
  `kd_jabatan_fungsional` varchar(6) DEFAULT NULL,
  `kd_tunjangan_khusus` varchar(6) DEFAULT NULL,
  `persen_gaji` tinyint(3) unsigned NOT NULL DEFAULT 100 COMMENT '100=normal, 80=CPNS, 50=hudis',
  `npwp` varchar(32) DEFAULT NULL,
  `opd_id` int(11) NOT NULL,
  `opd_unit_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_pegawai_nip` (`nip`) USING BTREE,
  KEY `fk_pegawai_opd` (`opd_id`) USING BTREE,
  KEY `fk_pegawai_opd_unit` (`opd_unit_id`) USING BTREE,
  CONSTRAINT `fk_pegawai_opd` FOREIGN KEY (`opd_id`) REFERENCES `master_opd` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pegawai_opd_unit` FOREIGN KEY (`opd_unit_id`) REFERENCES `master_opd_unit` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pegawai_jabatan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pegawai_jabatan` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pegawai_id` bigint(20) NOT NULL,
  `jabatan_id` bigint(20) NOT NULL,
  `opd_id` int(11) NOT NULL,
  `opd_unit_id` int(11) DEFAULT NULL,
  `tmt_mulai` date DEFAULT NULL,
  `tmt_selesai` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_pj_lookup` (`opd_id`,`opd_unit_id`,`jabatan_id`) USING BTREE,
  KEY `fk_pj_pegawai` (`pegawai_id`) USING BTREE,
  KEY `fk_pj_jabatan` (`jabatan_id`) USING BTREE,
  KEY `fk_pj_opd_unit` (`opd_unit_id`) USING BTREE,
  CONSTRAINT `fk_pj_jabatan` FOREIGN KEY (`jabatan_id`) REFERENCES `ref_jabatan` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pj_opd` FOREIGN KEY (`opd_id`) REFERENCES `master_opd` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pj_opd_unit` FOREIGN KEY (`opd_unit_id`) REFERENCES `master_opd_unit` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pj_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pegawai_rekening`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pegawai_rekening` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pegawai_id` bigint(20) NOT NULL,
  `bank_id` bigint(20) NOT NULL,
  `no_rekening` varchar(50) NOT NULL,
  `nama_pemilik_rekening` varchar(255) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_pegawai_rekening` (`pegawai_id`,`bank_id`,`no_rekening`) USING BTREE,
  KEY `fk_pegrek_bank` (`bank_id`) USING BTREE,
  CONSTRAINT `fk_pegrek_bank` FOREIGN KEY (`bank_id`) REFERENCES `ref_bank` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pegrek_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_bank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_bank` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `kode_bank` varchar(20) DEFAULT NULL,
  `nama_bank` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_ref_bank_nama` (`nama_bank`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_gaji_ke`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_gaji_ke` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no` tinyint(4) NOT NULL COMMENT '13 atau 14',
  `nama` varchar(60) NOT NULL,
  `bulan_basis` tinyint(4) NOT NULL DEFAULT 6 COMMENT 'bulan referensi komponen gaji (1=Jan)',
  `keterangan` varchar(200) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_gaji_pokok`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_gaji_pokok` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jenis` enum('PNS','PPPK') NOT NULL,
  `golongan` varchar(6) NOT NULL,
  `masa_kerja` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `gaji_pokok` int(11) NOT NULL,
  `pp_nomor` varchar(40) DEFAULT NULL,
  `berlaku_mulai` date NOT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_gapok` (`jenis`,`golongan`,`masa_kerja`,`berlaku_mulai`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_harga_beras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_harga_beras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `harga_per_kg` int(11) NOT NULL DEFAULT 0,
  `berlaku_mulai` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_iuran_gaji`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_iuran_gaji` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(30) NOT NULL,
  `nama` varchar(120) NOT NULL,
  `jenis_asn` enum('PNS','PPPK','SEMUA') DEFAULT 'SEMUA',
  `persen_pegawai` decimal(5,3) NOT NULL DEFAULT 0.000,
  `persen_employer` decimal(5,3) NOT NULL DEFAULT 0.000,
  `keterangan` varchar(200) DEFAULT NULL,
  `berlaku_mulai` date NOT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_iuran` (`kode`,`berlaku_mulai`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_jabatan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_jabatan` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `kode_jabatan` varchar(50) DEFAULT NULL,
  `nama_jabatan` varchar(255) NOT NULL,
  `singkatan_jabatan` varchar(100) DEFAULT NULL,
  `jenis_jabatan` enum('STRUKTURAL','PENATAUSAHAAN','FUNGSIONAL','LAINNYA') NOT NULL,
  `eselon` varchar(10) DEFAULT NULL,
  `kelas_jabatan` tinyint(4) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_ref_jabatan_nama` (`nama_jabatan`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_kelas_jabatan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_kelas_jabatan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kelas` tinyint(3) unsigned NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `berlaku_mulai` date NOT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_kelas` (`kelas`,`berlaku_mulai`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_tpp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_tpp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kelas_jabatan_id` int(11) DEFAULT NULL,
  `uraian` varchar(255) DEFAULT NULL,
  `nominal` int(11) NOT NULL DEFAULT 0,
  `perbup` varchar(100) DEFAULT NULL,
  `berlaku_mulai` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_tunjangan_fungsional`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_tunjangan_fungsional` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kdjabatan` varchar(6) NOT NULL,
  `nama_jabatan` varchar(250) NOT NULL,
  `nominal` int(11) NOT NULL DEFAULT 0,
  `bup_usia` tinyint(3) unsigned NOT NULL DEFAULT 58,
  `kategori` tinyint(3) unsigned DEFAULT 0,
  `is_active` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_kdfungsi` (`kdjabatan`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_tunjangan_jabatan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_tunjangan_jabatan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jenis` enum('STRUKTURAL','FUNGSIONAL','UMUM') NOT NULL,
  `nama` varchar(200) NOT NULL,
  `kode` varchar(30) DEFAULT NULL,
  `nominal` int(11) NOT NULL DEFAULT 0,
  `bup_usia` tinyint(4) DEFAULT 58,
  `pp_nomor` varchar(60) DEFAULT NULL,
  `berlaku_mulai` date NOT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_jenis_aktif` (`jenis`,`is_active`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_tunjangan_khusus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_tunjangan_khusus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kdjabatan` varchar(6) NOT NULL,
  `nama_jabatan` varchar(250) NOT NULL,
  `nominal` int(11) NOT NULL DEFAULT 0,
  `bup_usia` tinyint(3) unsigned NOT NULL DEFAULT 58,
  `is_active` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_kdkhusus` (`kdjabatan`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` enum('superadmin','admin_opd','user_opd') NOT NULL,
  `page_key` varchar(60) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 0,
  `can_create` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_role_page` (`role`,`page_key`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_akses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_akses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `opd_unit_id` int(11) DEFAULT NULL,
  `bidang_urusan_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_user_akses_user` (`user_id`) USING BTREE,
  KEY `fk_user_akses_unit` (`opd_unit_id`) USING BTREE,
  KEY `fk_user_akses_bidang` (`bidang_urusan_id`) USING BTREE,
  CONSTRAINT `fk_user_akses_bidang` FOREIGN KEY (`bidang_urusan_id`) REFERENCES `master_bidang` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_akses_unit` FOREIGN KEY (`opd_unit_id`) REFERENCES `master_opd_unit` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_akses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nip` varchar(30) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `pegawai_id` bigint(20) DEFAULT NULL,
  `role` enum('superadmin','admin_opd','user_opd') NOT NULL DEFAULT 'user_opd',
  `opd_id` int(11) DEFAULT NULL,
  `opd_unit_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `akses_semua_bidang` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'user_opd: 1=CRUD semua bidang OPD, 0=hanya bidang tertaut (user_akses)',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_users_nip` (`nip`) USING BTREE,
  UNIQUE KEY `uk_users_username` (`username`) USING BTREE,
  KEY `idx_users_pegawai` (`pegawai_id`) USING BTREE,
  KEY `fk_users_opd` (`opd_id`) USING BTREE,
  KEY `fk_users_opd_unit` (`opd_unit_id`) USING BTREE,
  CONSTRAINT `fk_users_opd` FOREIGN KEY (`opd_id`) REFERENCES `master_opd` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_opd_unit` FOREIGN KEY (`opd_unit_id`) REFERENCES `master_opd_unit` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

