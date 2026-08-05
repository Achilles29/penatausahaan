-- Migrasi: dukungan jenis belanja (form pinbuk), komponen perjalanan dinas,
-- dan pemilihan skema pajak per penerima. Idempoten (aman dijalankan ulang).

-- npd_detail.jenis_belanja : perjalanan | honor | barang_jasa (default dari kategori rekening)
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'npd_detail' AND COLUMN_NAME = 'jenis_belanja');
SET @sql := IF(@exists = 0,
  'ALTER TABLE npd_detail ADD COLUMN jenis_belanja VARCHAR(20) NULL AFTER rekening_id',
  'SELECT "npd_detail.jenis_belanja sudah ada"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- npd_penerima.komponen_pd : sppd | representasi | penginapan | tol (khusus perjalanan dinas)
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'npd_penerima' AND COLUMN_NAME = 'komponen_pd');
SET @sql := IF(@exists = 0,
  "ALTER TABLE npd_penerima ADD COLUMN komponen_pd ENUM('sppd','representasi','penginapan','tol') NULL AFTER uraian",
  'SELECT "npd_penerima.komponen_pd sudah ada"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- npd_penerima.skema_pajak_id : skema pajak terpilih (default sesuai kategori rekening)
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'npd_penerima' AND COLUMN_NAME = 'skema_pajak_id');
SET @sql := IF(@exists = 0,
  'ALTER TABLE npd_penerima ADD COLUMN skema_pajak_id INT NULL AFTER komponen_pd',
  'SELECT "npd_penerima.skema_pajak_id sudah ada"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Snapshot rekening & npwp yang dipakai saat transaksi (default dari master, wajib rekening)
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'npd_penerima' AND COLUMN_NAME = 'no_rekening');
SET @sql := IF(@exists = 0,
  'ALTER TABLE npd_penerima ADD COLUMN no_rekening VARCHAR(50) NULL AFTER skema_pajak_id',
  'SELECT "npd_penerima.no_rekening sudah ada"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'npd_penerima' AND COLUMN_NAME = 'npwp');
SET @sql := IF(@exists = 0,
  'ALTER TABLE npd_penerima ADD COLUMN npwp VARCHAR(30) NULL AFTER no_rekening',
  'SELECT "npd_penerima.npwp sudah ada"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
