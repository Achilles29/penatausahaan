<?php
$pdo = new PDO('mysql:host=localhost;dbname=penatus;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$perbup = 'Perbup Rembang 2024';
$tgl    = '2024-01-01';

$data = [
  ['TPP_001','Sekretaris Daerah','STRUKTURAL',15,19000000],
  ['TPP_002','Inspektur','STRUKTURAL',14,12500000],
  ['TPP_003','Asisten Sekretariat Daerah','STRUKTURAL',14,12000000],
  ['TPP_004','Kepala Dinas/Badan/Satpol PP/Sekretaris DPRD dan Kepala Pelaksana BPBD','STRUKTURAL',14,9000000],
  ['TPP_005','Staf Ahli Bupati','STRUKTURAL',13,8500000],
  ['TPP_006','Camat, Kepala Bagian pada Setda/Sekretariat DPRD','STRUKTURAL',12,6000000],
  ['TPP_007','Sekretaris Inspektorat Daerah','STRUKTURAL',12,5500000],
  ['TPP_008','Sekretaris Dinas/Badan/Satpol PP','STRUKTURAL',12,4500000],
  ['TPP_009','Inspektur Pembantu','STRUKTURAL',11,5000000],
  ['TPP_010','Kepala Bidang pada Dinas/Badan/Satpol PP','STRUKTURAL',11,4000000],
  ['TPP_011','Sekretaris Kecamatan','STRUKTURAL',11,3500000],
  ['TPP_012','Kasubbag pada Setda','STRUKTURAL',9,2600000],
  ['TPP_013','Kasubbag pada Inspektorat Daerah','STRUKTURAL',9,2550000],
  ['TPP_014','Kasubbag/Kasubbid/Kasi pd Dinas/Badan/Satpol PP/Kecamatan, Kepala UPT, dan Lurah','STRUKTURAL',9,2500000],
  ['TPP_015','Kasubbag TU pada UPT, Sekretaris Kelurahan dan Kasi pada Kelurahan','STRUKTURAL',8,2000000],
  ['TPP_016','JF Ahli Utama','FUNGSIONAL',13,4500000],
  ['TPP_017','JF Auditor/PPUPD Ahli Madya','FUNGSIONAL',12,3600000],
  ['TPP_018','JF Ahli Madya sebagai Sub Koordinator - Setda/Inspektorat','FUNGSIONAL',12,3000000],
  ['TPP_019','JF Ahli Madya - Setda/Inspektorat','FUNGSIONAL',12,2500000],
  ['TPP_020','JF Ahli Madya sebagai Sub Koordinator - Dinas/Badan','FUNGSIONAL',11,2800000],
  ['TPP_021','JF Ahli Madya pada Setda dan Satpol PP','FUNGSIONAL',11,2500000],
  ['TPP_022','JF Ahli Madya - Dinas/Badan','FUNGSIONAL',11,2000000],
  ['TPP_023','JF Guru Ahli Madya (tidak menerima sertifikasi)','FUNGSIONAL',11,1400000],
  ['TPP_024','JF Guru Ahli Madya - Koordinator Wilayah Kecamatan/Kepala Sekolah SD','FUNGSIONAL',11,750000],
  ['TPP_025','JF Auditor/PPUPD Ahli Muda','FUNGSIONAL',10,2500000],
  ['TPP_026','JF Ahli Muda sebagai Sub Koordinator - Dinas/Badan','FUNGSIONAL',10,2600000],
  ['TPP_027','JF Ahli Muda - Dinas/Badan (Kelas 10)','FUNGSIONAL',10,1800000],
  ['TPP_028','JF Ahli Muda sebagai Sub Koordinator - Setda','FUNGSIONAL',9,2500000],
  ['TPP_029','JF Ahli Muda pada Setda dan Satpol PP','FUNGSIONAL',9,1800000],
  ['TPP_030','JF Ahli Muda - Dinas/Badan (Kelas 9)','FUNGSIONAL',9,1750000],
  ['TPP_031','JF Guru Ahli Muda (tidak menerima sertifikasi)','FUNGSIONAL',9,1350000],
  ['TPP_032','JF Guru Ahli Muda - Koordinator Wilayah Kecamatan/Kepala Sekolah SD','FUNGSIONAL',9,700000],
  ['TPP_033','JF Ahli Pertama sebagai Sub Koordinator','FUNGSIONAL',8,2300000],
  ['TPP_034','JF Auditor/PPUPD Ahli Pertama','FUNGSIONAL',8,1900000],
  ['TPP_035','JF Ahli Pertama/Penyelia pada Setda dan Satpol PP','FUNGSIONAL',8,1600000],
  ['TPP_036','JF Ahli Pertama/Penyelia','FUNGSIONAL',8,1550000],
  ['TPP_037','JF Guru Ahli Pertama (tidak menerima sertifikasi)','FUNGSIONAL',8,1300000],
  ['TPP_038','Pelaksana sebagai Sub Koordinator (Kelas 7)','FUNGSIONAL',7,2000000],
  ['TPP_039','Pelaksana/Calon JF/JF Pelaksana Lanjutan pada Setda dan Inspektorat Daerah','FUNGSIONAL',7,1350000],
  ['TPP_040','JF Polisi Pamong Praja Pelaksana Lanjutan','FUNGSIONAL',7,1350000],
  ['TPP_041','Analis Penyidikan/Analis Penanganan Pelanggaran/Analis Keamanan pada Satpol PP','FUNGSIONAL',7,1350000],
  ['TPP_042','Analis Kebakaran pada BPBD','FUNGSIONAL',7,1350000],
  ['TPP_043','Pelaksana/Calon JF/JF Pelaksana Lanjutan pd Dinas/Badan/Satpol PP/Kecamatan/Kelurahan','FUNGSIONAL',7,1300000],
  ['TPP_044','CPNS (Kelas 7)','LAINNYA',7,1150000],
  ['TPP_045','Pengelola Keamanan dan Ketertiban pada Satpol PP','FUNGSIONAL',6,1280000],
  ['TPP_046','Pranata Pemadam Kebakaran pada BPBD','FUNGSIONAL',6,1280000],
  ['TPP_047','Pelaksana sebagai Sub Koordinator (Kelas 6)','FUNGSIONAL',6,1900000],
  ['TPP_048','Pelaksana pada Setda dan Inspektorat Daerah (Kelas 6)','FUNGSIONAL',6,1280000],
  ['TPP_049','Pelaksana/JF Pelaksana pd Dinas/Badan/Satpol PP/Kecamatan/Kelurahan (Kelas 6)','FUNGSIONAL',6,1250000],
  ['TPP_050','CPNS (Kelas 6)','LAINNYA',6,1100000],
  ['TPP_051','Pelaksana pada Setda dan Inspektorat Daerah (Kelas 5)','FUNGSIONAL',5,1170000],
  ['TPP_052','Pengadministrasi Pengaduan Publik/Penanganan Perkara/Hukum pada Satpol PP','FUNGSIONAL',5,1170000],
  ['TPP_053','Pengemudi Mobil Pemadam Kebakaran pada BPBD','FUNGSIONAL',5,1170000],
  ['TPP_054','Pemelihara Penerangan Jalan pada Dinas Perhubungan','FUNGSIONAL',5,1170000],
  ['TPP_055','Pelaksana/Calon JF/JF Pelaksana Pemula pd Dinas/Badan/Kecamatan/Kelurahan (Kelas 5)','FUNGSIONAL',5,1150000],
  ['TPP_056','CPNS (Kelas 5)','LAINNYA',5,1050000],
  ['TPP_057','Pelaksana pada Setda dan Inspektorat Daerah (Kelas 3)','FUNGSIONAL',3,1100000],
  ['TPP_058','Pelaksana pd Dinas/Badan/Satpol PP/Kecamatan/Kelurahan (Kelas 3)','FUNGSIONAL',3,1080000],
  ['TPP_059','Pelaksana pada Setda dan Inspektorat Daerah (Kelas 1)','FUNGSIONAL',1,1050000],
  ['TPP_060','Pelaksana pd Dinas/Badan/Satpol PP/Kecamatan/Kelurahan (Kelas 1)','FUNGSIONAL',1,1040000],
  ['TPP_061','PPPK Pendidikan S.1/D.4','LAINNYA',null,1250000],
  ['TPP_062','PPPK Pendidikan D.3','LAINNYA',null,1150000],
  ['TPP_063','PPPK Pendidikan SLTA/D.1/D.2','LAINNYA',null,1100000],
];

$stmtJab = $pdo->prepare('INSERT INTO ref_jabatan (kode_jabatan, nama_jabatan, jenis_jabatan, is_active) VALUES (?,?,?,1)');
$stmtTpp = $pdo->prepare('INSERT INTO ref_tpp (ref_jabatan_id, nominal, perbup, berlaku_mulai, is_active) VALUES (?,?,?,?,1)');

$inserted = 0;
foreach ($data as $row) {
    [$kode, $nama, $jenis, $kelas, $nominal] = $row;
    $stmtJab->execute([$kode, $nama, $jenis]);
    $jabId = $pdo->lastInsertId();
    $stmtTpp->execute([$jabId, $nominal, $perbup, $tgl]);
    $inserted++;
}
echo "Inserted: $inserted jabatan + $inserted TPP entries\n";

$cnt = $pdo->query('SELECT COUNT(*) FROM ref_tpp')->fetchColumn();
echo "Total ref_tpp: $cnt\n";

$sample = $pdo->query('SELECT rj.kode_jabatan, rj.nama_jabatan, rt.nominal FROM ref_tpp rt JOIN ref_jabatan rj ON rj.id=rt.ref_jabatan_id ORDER BY rt.nominal DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
foreach ($sample as $s) {
    echo '  ' . $s['kode_jabatan'] . ' | ' . mb_substr($s['nama_jabatan'],0,50) . ' | Rp ' . number_format($s['nominal'],0,',','.') . "\n";
}
