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
  UNIQUE KEY `uq_anggaran_kas_kombinasi` (`tahun`,`opd_id`,`program_id`,`kegiatan_id`,`subkegiatan_id`,`rekening_id`) USING BTREE,
  KEY `idx_anggaran_kas_lookup` (`opd_id`,`program_id`,`kegiatan_id`,`subkegiatan_id`) USING BTREE,
  KEY `fk_ak_urusan` (`urusan_id`) USING BTREE,
  KEY `fk_ak_bidang` (`bidang_id`) USING BTREE,
  KEY `fk_ak_program` (`program_id`) USING BTREE,
  KEY `fk_ak_kegiatan` (`kegiatan_id`) USING BTREE,
  KEY `fk_ak_subkegiatan` (`subkegiatan_id`) USING BTREE,
  KEY `fk_ak_rekening` (`rekening_id`) USING BTREE,
  CONSTRAINT `fk_ak_bidang` FOREIGN KEY (`bidang_id`) REFERENCES `master_bidang` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ak_kegiatan` FOREIGN KEY (`kegiatan_id`) REFERENCES `master_kegiatan` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ak_opd` FOREIGN KEY (`opd_id`) REFERENCES `master_opd` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ak_program` FOREIGN KEY (`program_id`) REFERENCES `master_program` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ak_rekening` FOREIGN KEY (`rekening_id`) REFERENCES `master_rekening` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ak_subkegiatan` FOREIGN KEY (`subkegiatan_id`) REFERENCES `master_subkegiatan` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ak_urusan` FOREIGN KEY (`urusan_id`) REFERENCES `master_urusan` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=224 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  UNIQUE KEY `uq_anggaran_kas_bulanan` (`anggaran_kas_id`,`bulan`) USING BTREE,
  KEY `idx_anggaran_kas_bulanan_bulan` (`bulan`) USING BTREE,
  CONSTRAINT `fk_akb_parent` FOREIGN KEY (`anggaran_kas_id`) REFERENCES `anggaran_kas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2677 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `map_opd_bidang_to_bidang_urusan` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `opd_bidang_id` int(11) NOT NULL,
  `bidang_urusan_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uq_map_opd_bidang_to_bidang_urusan` (`opd_bidang_id`,`bidang_urusan_id`) USING BTREE,
  KEY `idx_map_obb_opd_bidang` (`opd_bidang_id`) USING BTREE,
  KEY `idx_map_obb_bidang_urusan` (`bidang_urusan_id`) USING BTREE,
  CONSTRAINT `fk_map_obb_bidang_urusan` FOREIGN KEY (`bidang_urusan_id`) REFERENCES `master_bidang` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_map_obb_opd_bidang` FOREIGN KEY (`opd_bidang_id`) REFERENCES `master_opd_bidang` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `map_opd_bidang_urusan` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `opd_id` int(11) NOT NULL,
  `bidang_urusan_id` int(11) NOT NULL,
  `is_dominant` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uq_map_opd_bidang_urusan` (`opd_id`,`bidang_urusan_id`) USING BTREE,
  KEY `idx_map_opd_bidang_urusan_opd` (`opd_id`) USING BTREE,
  KEY `idx_map_opd_bidang_urusan_bidang` (`bidang_urusan_id`) USING BTREE,
  CONSTRAINT `fk_map_obu_bidang` FOREIGN KEY (`bidang_urusan_id`) REFERENCES `master_bidang` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_map_obu_opd` FOREIGN KEY (`opd_id`) REFERENCES `master_opd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_bendahara_pengeluaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `opd_id` int(11) NOT NULL,
  `nama_bendahara` varchar(150) NOT NULL,
  `nip` varchar(25) NOT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `no_sk` varchar(100) DEFAULT NULL,
  `tanggal_sk` date DEFAULT NULL,
  `tahun` int(11) NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  KEY `opd_id` (`opd_id`) USING BTREE,
  CONSTRAINT `fk_bendahara_opd` FOREIGN KEY (`opd_id`) REFERENCES `master_opd` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_dpa` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tahun` year(4) NOT NULL,
  `urusan_id` int(10) unsigned NOT NULL,
  `bidang_id` int(10) unsigned NOT NULL,
  `program_id` int(10) unsigned NOT NULL,
  `kegiatan_id` int(10) unsigned NOT NULL,
  `subkegiatan_id` int(10) unsigned NOT NULL,
  `rekening_id` int(10) unsigned NOT NULL,
  `nama_paket` varchar(255) NOT NULL,
  `sumber_dana_id` int(11) DEFAULT NULL,
  `nama_shs` varchar(255) NOT NULL,
  `koefisien` varchar(200) NOT NULL,
  `harga_satuan` decimal(18,2) NOT NULL DEFAULT 0.00,
  `harga_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_tahun` (`tahun`) USING BTREE,
  KEY `idx_urusan` (`urusan_id`) USING BTREE,
  KEY `idx_bidang` (`bidang_id`) USING BTREE,
  KEY `idx_program` (`program_id`) USING BTREE,
  KEY `idx_kegiatan` (`kegiatan_id`) USING BTREE,
  KEY `idx_subkegiatan` (`subkegiatan_id`) USING BTREE,
  KEY `idx_rekening` (`rekening_id`) USING BTREE,
  KEY `idx_master_dpa_sumber_dana_id` (`sumber_dana_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=340 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  KEY `idx_master_opd_kode_opd` (`kode_opd`) USING BTREE,
  KEY `fk_master_opd_dominant_bidang` (`dominant_bidang_id`) USING BTREE,
  CONSTRAINT `fk_master_opd_dominant_bidang` FOREIGN KEY (`dominant_bidang_id`) REFERENCES `master_bidang` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_opd_bidang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `opd_id` int(11) NOT NULL,
  `kode_bidang_opd` varchar(20) DEFAULT NULL,
  `nama_bidang_opd` varchar(255) NOT NULL,
  `kepala_bidang` varchar(255) DEFAULT NULL,
  `nip_kepala` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_opd_bidang` (`opd_id`,`nama_bidang_opd`) USING BTREE,
  CONSTRAINT `master_opd_bidang_ibfk_1` FOREIGN KEY (`opd_id`) REFERENCES `master_opd` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_opd_bidang_urusan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `opd_bidang_id` int(11) NOT NULL,
  `bidang_urusan_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_opd_bidang_urusan` (`opd_bidang_id`,`bidang_urusan_id`) USING BTREE,
  KEY `bidang_urusan_id` (`bidang_urusan_id`) USING BTREE,
  CONSTRAINT `master_opd_bidang_urusan_ibfk_1` FOREIGN KEY (`opd_bidang_id`) REFERENCES `master_opd_bidang` (`id`),
  CONSTRAINT `master_opd_bidang_urusan_ibfk_2` FOREIGN KEY (`bidang_urusan_id`) REFERENCES `master_bidang` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_opd_urusan_bidang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `opd_id` int(11) NOT NULL,
  `urusan_id` int(11) NOT NULL,
  `bidang_urusan_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_opd_urusan_bidang` (`opd_id`,`bidang_urusan_id`) USING BTREE,
  KEY `urusan_id` (`urusan_id`) USING BTREE,
  KEY `bidang_urusan_id` (`bidang_urusan_id`) USING BTREE,
  CONSTRAINT `master_opd_urusan_bidang_ibfk_1` FOREIGN KEY (`opd_id`) REFERENCES `master_opd` (`id`),
  CONSTRAINT `master_opd_urusan_bidang_ibfk_2` FOREIGN KEY (`urusan_id`) REFERENCES `master_urusan` (`id`),
  CONSTRAINT `master_opd_urusan_bidang_ibfk_3` FOREIGN KEY (`bidang_urusan_id`) REFERENCES `master_bidang` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_penerima` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_penerima` varchar(150) NOT NULL,
  `jenis_penerima` enum('orang','badan') NOT NULL,
  `punya_npwp` tinyint(1) NOT NULL DEFAULT 0,
  `npwp` varchar(30) DEFAULT NULL,
  `golongan` enum('I','II','III','IV','NON_PNS') DEFAULT NULL,
  `nama_bank` varchar(50) NOT NULL,
  `no_rekening` varchar(50) NOT NULL,
  `nama_rekening` varchar(150) NOT NULL,
  `alamat` text DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_rekening` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_rekening` varchar(50) NOT NULL,
  `uraian` text NOT NULL,
  `jenis_belanja` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_kode_rekening` (`kode_rekening`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=15289 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  UNIQUE KEY `kode_skema` (`kode_skema`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_skema_pajak_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skema_id` int(11) DEFAULT NULL,
  `jenis_pajak` enum('PPH21','PPH22','PPH23','PPN','PDRD') DEFAULT NULL,
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
  KEY `skema_id` (`skema_id`) USING BTREE,
  CONSTRAINT `master_skema_pajak_detail_ibfk_1` FOREIGN KEY (`skema_id`) REFERENCES `master_skema_pajak` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2089 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  UNIQUE KEY `uk_master_sumber_dana_kode` (`kode`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=860 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `npd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nomor_npd` varchar(100) NOT NULL,
  `tanggal` date NOT NULL,
  `perihal` varchar(255) NOT NULL,
  `opd_id` int(11) DEFAULT NULL,
  `urusan_id` int(11) DEFAULT NULL,
  `bidang_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `kegiatan_id` int(11) DEFAULT NULL,
  `subkegiatan_id` int(11) DEFAULT NULL,
  `dpa_id` int(11) DEFAULT NULL,
  `pekerjaan` text NOT NULL,
  `sumber_dana_id` int(11) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_npd_sumber_dana_id` (`sumber_dana_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `npd_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `npd_id` int(11) NOT NULL,
  `dpa_id` int(11) DEFAULT NULL,
  `rekening_id` int(11) NOT NULL,
  `jumlah` bigint(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  KEY `npd_id` (`npd_id`) USING BTREE,
  CONSTRAINT `npd_detail_ibfk_1` FOREIGN KEY (`npd_id`) REFERENCES `npd` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `npd_pinbuk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nomor` varchar(50) NOT NULL,
  `npd_id` int(11) NOT NULL,
  `npd_detail_id` int(11) NOT NULL,
  `penerima_id` int(11) NOT NULL,
  `jenis_transaksi` varchar(50) NOT NULL,
  `kategori_pajak` varchar(50) DEFAULT NULL,
  `nilai_bruto` decimal(15,2) NOT NULL,
  `total_pajak` decimal(15,2) DEFAULT NULL,
  `nilai_netto` decimal(15,2) DEFAULT NULL,
  `status` enum('draft','final','dibayar') DEFAULT 'draft',
  `tanggal_pinbuk` date DEFAULT NULL,
  `tanggal_persediaan` date DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `npd_pinbuk_rincian` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pinbuk_id` int(11) NOT NULL COMMENT 'Relasi ke npd_pinbuk.id',
  `penerima_id` int(11) NOT NULL COMMENT 'Relasi ke master_penerima.id',
  `volume` int(11) NOT NULL COMMENT 'Jumlah hari / kali',
  `harga_satuan` decimal(15,2) NOT NULL COMMENT 'Nilai per hari / per kali',
  `jumlah` decimal(15,2) NOT NULL COMMENT 'volume x harga_satuan',
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `fk_pinbuk_rincian_pinbuk` (`pinbuk_id`) USING BTREE,
  KEY `fk_pinbuk_rincian_penerima` (`penerima_id`) USING BTREE,
  CONSTRAINT `fk_pinbuk_rincian_penerima` FOREIGN KEY (`penerima_id`) REFERENCES `master_penerima` (`id`),
  CONSTRAINT `fk_pinbuk_rincian_pinbuk` FOREIGN KEY (`pinbuk_id`) REFERENCES `npd_pinbuk` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pegawai` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(255) NOT NULL,
  `status_kepegawaian` enum('ASN','NON_ASN') NOT NULL,
  `jenis_kepegawaian` enum('PNS','PPPK','NON_ASN') NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `npwp` varchar(32) DEFAULT NULL,
  `opd_id` int(11) NOT NULL,
  `opd_bidang_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_pegawai_opd` (`opd_id`) USING BTREE,
  KEY `idx_pegawai_opd_bidang` (`opd_bidang_id`) USING BTREE,
  KEY `idx_pegawai_nip` (`nip`) USING BTREE,
  CONSTRAINT `fk_pegawai_opd` FOREIGN KEY (`opd_id`) REFERENCES `master_opd` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pegawai_opd_bidang` FOREIGN KEY (`opd_bidang_id`) REFERENCES `master_opd_bidang` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pegawai_jabatan` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pegawai_id` bigint(20) NOT NULL,
  `jabatan_id` bigint(20) NOT NULL,
  `opd_id` int(11) NOT NULL,
  `opd_bidang_id` int(11) DEFAULT NULL,
  `tmt_mulai` date DEFAULT NULL,
  `tmt_selesai` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uq_pegawai_jabatan_aktif` (`pegawai_id`,`jabatan_id`,`opd_id`,`opd_bidang_id`,`is_active`) USING BTREE,
  KEY `idx_pegawai_jabatan_lookup` (`opd_id`,`opd_bidang_id`,`jabatan_id`) USING BTREE,
  KEY `fk_pj_jabatan` (`jabatan_id`) USING BTREE,
  KEY `fk_pj_opd_bidang` (`opd_bidang_id`) USING BTREE,
  CONSTRAINT `fk_pj_jabatan` FOREIGN KEY (`jabatan_id`) REFERENCES `ref_jabatan` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pj_opd` FOREIGN KEY (`opd_id`) REFERENCES `master_opd` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pj_opd_bidang` FOREIGN KEY (`opd_bidang_id`) REFERENCES `master_opd_bidang` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pj_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  UNIQUE KEY `uq_pegawai_rekening` (`pegawai_id`,`bank_id`,`no_rekening`) USING BTREE,
  KEY `idx_pegawai_rekening_primary` (`pegawai_id`,`is_primary`) USING BTREE,
  KEY `fk_pegawai_rekening_bank` (`bank_id`) USING BTREE,
  CONSTRAINT `fk_pegawai_rekening_bank` FOREIGN KEY (`bank_id`) REFERENCES `ref_bank` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pegawai_rekening_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  UNIQUE KEY `uq_ref_bank_nama` (`nama_bank`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ref_jabatan` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `kode_jabatan` varchar(50) DEFAULT NULL,
  `nama_jabatan` varchar(255) NOT NULL,
  `singkatan_jabatan` varchar(100) DEFAULT NULL,
  `jenis_jabatan` enum('STRUKTURAL','PENATAUSAHAAN','FUNGSIONAL','LAINNYA') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uq_ref_jabatan_nama` (`nama_jabatan`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trx_dpa` (
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
  KEY `idx_trx_dpa_tahun_opd` (`tahun`,`opd_id`) USING BTREE,
  KEY `fk_trx_dpa_opd` (`opd_id`) USING BTREE,
  CONSTRAINT `fk_trx_dpa_opd` FOREIGN KEY (`opd_id`) REFERENCES `master_opd` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trx_dpa_detail` (
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
  KEY `idx_trx_dpa_detail_dpa` (`dpa_id`) USING BTREE,
  CONSTRAINT `fk_tdd_dpa` FOREIGN KEY (`dpa_id`) REFERENCES `trx_dpa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1267 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `pegawai_id` bigint(20) DEFAULT NULL,
  `role` enum('admin','opd','bidang') NOT NULL DEFAULT 'bidang',
  `opd_id` int(11) DEFAULT NULL,
  `opd_bidang_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `username` (`username`) USING BTREE,
  KEY `idx_users_pegawai_id` (`pegawai_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_user_scope_bidang_urusan` AS SELECT
 1 AS `user_id`,
  1 AS `role`,
  1 AS `opd_id`,
  1 AS `opd_bidang_id`,
  1 AS `bidang_urusan_id` */;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_user_scope_subkegiatan` AS SELECT
 1 AS `user_id`,
  1 AS `program_id`,
  1 AS `kegiatan_id`,
  1 AS `subkegiatan_id`,
  1 AS `bidang_urusan_id` */;
SET character_set_client = @saved_cs_client;
/*!50001 DROP VIEW IF EXISTS `v_user_scope_bidang_urusan`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_user_scope_bidang_urusan` AS select `u`.`id` AS `user_id`,`u`.`role` AS `role`,`u`.`opd_id` AS `opd_id`,`u`.`opd_bidang_id` AS `opd_bidang_id`,case when `u`.`role` = 'admin' then `b`.`id` when `u`.`role` = 'opd' then `m1`.`bidang_urusan_id` when `u`.`role` = 'bidang' then `m2`.`bidang_urusan_id` else NULL end AS `bidang_urusan_id` from (((`users` `u` left join `master_bidang` `b` on(`u`.`role` = 'admin')) left join `map_opd_bidang_urusan` `m1` on(`u`.`role` = 'opd' and `m1`.`opd_id` = `u`.`opd_id`)) left join `map_opd_bidang_to_bidang_urusan` `m2` on(`u`.`role` = 'bidang' and `m2`.`opd_bidang_id` = `u`.`opd_bidang_id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_user_scope_subkegiatan`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_user_scope_subkegiatan` AS select distinct `s`.`user_id` AS `user_id`,`p`.`id` AS `program_id`,`k`.`id` AS `kegiatan_id`,`sk`.`id` AS `subkegiatan_id`,`b`.`id` AS `bidang_urusan_id` from ((((`v_user_scope_bidang_urusan` `s` join `master_program` `p` on(`p`.`bidang_id` = `s`.`bidang_urusan_id`)) join `master_kegiatan` `k` on(`k`.`program_id` = `p`.`id`)) join `master_subkegiatan` `sk` on(`sk`.`kegiatan_id` = `k`.`id`)) join `master_bidang` `b` on(`b`.`id` = `p`.`bidang_id`)) where `s`.`bidang_urusan_id` is not null */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
