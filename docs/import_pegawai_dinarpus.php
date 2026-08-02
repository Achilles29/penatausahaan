<?php
$pdo = new PDO('mysql:host=localhost;dbname=penatus;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$today = '2026-08-02';

$golMap     = ['2D'=>'II/d','3A'=>'III/a','3B'=>'III/b','3C'=>'III/c','3D'=>'III/d','4A'=>'IV/a','4B'=>'IV/b','4C'=>'IV/c'];
$pangkatMap = ['2D'=>'Pengatur Tingkat I','3A'=>'Penata Muda','3B'=>'Penata Muda Tingkat I','3C'=>'Penata','3D'=>'Penata Tingkat I','4A'=>'Pembina','4B'=>'Pembina Tingkat I','4C'=>'Pembina Utama Muda'];

function nextKP($tmt_pns, $today_str) {
    if (!$tmt_pns) return null;
    $pns   = new DateTime($tmt_pns);
    $today = new DateTime($today_str);
    $m = (int)$pns->format('n');
    $y = (int)$pns->format('Y') + 4;
    $kp = ($m <= 6) ? new DateTime("{$y}-04-01") : new DateTime("{$y}-10-01");
    while ($kp <= $today) $kp->modify('+4 years');
    return $kp->format('Y-m-d');
}

$opd_id = 16;

// [nip, nama, jk, tgl_lahir, tmt_cpns, tmt_pns, gol_code, mkg, status_nik, janak, persen_gaji, kd_jabatan_fungsional, jabatan_struktural_id, jabatan_fungsional_id, ref_tpp_id]
$employees = [
    ['196908302008012009','SRI BARLIYANTI','P','1969-08-30','2008-01-01','2008-01-01','3A',18,'KAWIN',0,100,null,null,null,72],
    ['196909141991031012','Mashudi, A.Md.','L','1969-09-14','1991-03-01','1991-03-01','3C',30,'KAWIN',0,100,null,null,null,72],
    ['197011041992031006','H. Arif Romadlon, SH, MM','L','1970-11-04','1992-03-01','1992-03-01','4C',29,'KAWIN',0,100,null,7,null,33],
    ['197108131993031003','Sukardi','L','1971-08-13','1993-03-01','1993-03-01','3B',28,'KAWIN',2,100,null,null,null,72],
    ['197207202009011002','Joko Suryono','L','1972-07-20','2009-01-01','2009-01-01','3A',16,'KAWIN',2,100,null,null,null,72],
    ['197302262008011006','Gunawan, S.I.Pust.','L','1973-02-26','2008-01-01','2008-01-01','3B',18,'KAWIN',2,100,'03306',null,1,72],
    ['197304031992031002','Adi Bagus Satriyo, S.Sos.','L','1973-04-03','1992-03-01','1992-03-01','4B',28,'KAWIN',2,100,null,105,null,39],
    ['197606162009011007','Dwi Junaidi','L','1976-06-16','2009-01-01','2009-01-01','3A',16,'KAWIN',1,100,null,null,null,72],
    ['197706202010011004','Heri Siswanto, SE','L','1977-06-20','2010-01-01','2010-01-01','3B',16,'DUDA',2,100,null,null,null,72],
    ['197812272009012007','Lilik Sri Daryati, SE','P','1978-12-27','2009-01-01','2009-01-01','4A',22,'KAWIN',1,100,null,106,null,37],
    ['197906162009012004','Suprapti','P','1979-06-16','2009-01-01','2009-01-01','3A',16,'KAWIN',1,100,null,null,null,72],
    ['197909222009011003','Ikhsan Rofingi','L','1979-09-22','2009-01-01','2009-01-01','3A',16,'BELUM_KAWIN',0,100,null,null,null,72],
    ['198008312014071001','Wahyudi Wirawanto, SE','L','1980-08-31','2014-07-01','2014-07-01','3C',20,'BELUM_KAWIN',0,100,null,null,null,72],
    ['198110312010012002','Candrawati Oktaviani, SH','P','1981-10-31','2010-01-01','2010-01-01','3D',19,'KAWIN',0,100,'03205',null,1,65],
    ['198407232009032012','Eva Martina Afriana, ST, MM','P','1984-07-23','2009-03-01','2009-03-01','4A',16,'BELUM_KAWIN',0,100,null,107,null,43],
    ['198901292012061001','Mukhammad Anwar Fuadi, SSTP, M.Si','L','1989-01-29','2012-06-01','2012-06-01','3D',12,'KAWIN',2,100,null,1,null,39],
    ['199101092012062001','Hanita Ary Prastiwi, SSTP','P','1991-01-09','2012-06-01','2012-06-01','3D',12,'BELUM_KAWIN',0,100,null,108,null,43],
    ['199203092022032008','Pursilah, S.IP.','P','1992-03-09','2022-03-01','2022-05-01','3A',4,'KAWIN',2,100,'03304',null,1,65],
    ['199507092022031007','Sirajuddin Akbar Setiajati, S.Ptk.','L','1995-07-09','2022-03-01','2022-05-01','3B',4,'KAWIN',0,100,'03304',null,1,65],
    ['199701312020122004','Fiscalita Mustafa, A.Md.S.I.','P','1997-01-31','2020-12-01','2021-01-01','2D',7,'KAWIN',2,100,null,null,null,78],
    ['199704232022032020','Dones Dewintasari, A.Md.S.I.','P','1997-04-23','2022-03-01','2022-05-01','2D',7,'KAWIN',1,100,null,null,null,78],
    ['199905182022031004','Ahmad Choiruman Qotadah, A.Md.T.','L','1999-05-18','2022-03-01','2022-05-01','2D',7,'BELUM_KAWIN',0,100,null,null,null,78],
    ['200012172025052004','Naila Faza, S.Hum.','P','2000-12-17','2025-05-01','2025-05-02','3A',1,'BELUM_KAWIN',0,100,null,null,null,72],
];

// Delete dummy
$pdo->exec('DELETE FROM pegawai WHERE id=2');
echo 'Deleted dummy pegawai id=2' . PHP_EOL;

$stmtInsert = $pdo->prepare('INSERT INTO pegawai
    (nip, nama_lengkap, jenis_kelamin, jenis_kepegawaian, golongan, pangkat,
     masa_kerja_golongan, tgl_lahir, tgl_cpns, tgl_pns, tmt_kenaikan_pangkat,
     status_pernikahan, jumlah_anak, persen_gaji, kd_jabatan_fungsional,
     jabatan_struktural_id, jabatan_fungsional_id, ref_tpp_id, opd_id, is_active)
    VALUES (?,?,?,\'PNS\',?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)');

$stmtUpdate = $pdo->prepare('UPDATE pegawai SET
    nama_lengkap=?, jenis_kelamin=?, jenis_kepegawaian=\'PNS\',
    golongan=?, pangkat=?, masa_kerja_golongan=?, tgl_lahir=?,
    tgl_cpns=?, tgl_pns=?, tmt_kenaikan_pangkat=?,
    status_pernikahan=?, jumlah_anak=?, persen_gaji=?,
    kd_jabatan_fungsional=?, jabatan_struktural_id=?, jabatan_fungsional_id=?,
    ref_tpp_id=?, opd_id=?
    WHERE nip=?');

$inserted = 0; $updated = 0;
foreach ($employees as $e) {
    [$nip,$nama,$jk,$tgl_lahir,$tmt_cpns,$tmt_pns,$gol_code,$mkg,$status,$janak,$persen,$kd_fungsi,$jab_str_id,$jab_fun_id,$tpp_id] = $e;
    $golongan = $golMap[$gol_code];
    $pangkat  = $pangkatMap[$gol_code];
    $tmt_kp   = nextKP($tmt_pns, $today);

    $ex = $pdo->prepare('SELECT id FROM pegawai WHERE nip=?');
    $ex->execute([$nip]);
    $existing = $ex->fetchColumn();

    if ($existing) {
        $stmtUpdate->execute([$nama,$jk,$golongan,$pangkat,$mkg,$tgl_lahir,$tmt_cpns,$tmt_pns,$tmt_kp,$status,$janak,$persen,$kd_fungsi,$jab_str_id,$jab_fun_id,$tpp_id,$opd_id,$nip]);
        echo "UPDATED: {$nip} {$nama}" . PHP_EOL;
        $updated++;
    } else {
        $stmtInsert->execute([$nip,$nama,$jk,$golongan,$pangkat,$mkg,$tgl_lahir,$tmt_cpns,$tmt_pns,$tmt_kp,$status,$janak,$persen,$kd_fungsi,$jab_str_id,$jab_fun_id,$tpp_id,$opd_id]);
        echo "INSERTED: {$nip} {$nama}" . PHP_EOL;
        $inserted++;
    }
}

echo PHP_EOL . "=== SELESAI === Inserted:{$inserted} Updated:{$updated}" . PHP_EOL;
$cnt = $pdo->query('SELECT COUNT(*) FROM pegawai WHERE opd_id=16')->fetchColumn();
echo "Total pegawai Dinarpus di DB: {$cnt}" . PHP_EOL;

// Verifikasi sampel
echo PHP_EOL . '--- Verifikasi 5 baris ---' . PHP_EOL;
$rows = $pdo->query('SELECT nip,nama_lengkap,golongan,masa_kerja_golongan,tmt_kenaikan_pangkat,status_pernikahan,ref_tpp_id,jabatan_struktural_id FROM pegawai WHERE opd_id=16 ORDER BY golongan DESC,nip LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo implode(' | ', $r) . PHP_EOL;
