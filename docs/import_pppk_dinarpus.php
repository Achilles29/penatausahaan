<?php
$pdo = new PDO('mysql:host=localhost;dbname=penatus;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$today = '2026-08-02';

// Roman numeral golongan dari kode angka SAPK
$golMap = [
    '1' => 'I', '2' => 'II', '3' => 'III', '4' => 'IV', '5' => 'V',
    '6' => 'VI', '7' => 'VII', '8' => 'VIII', '9' => 'IX', '10' => 'X',
    '11' => 'XI', '12' => 'XII', '13' => 'XIII', '14' => 'XIV',
    '15' => 'XV', '16' => 'XVI', '17' => 'XVII',
];

// KGB PPPK: setiap 2 tahun, tanggal 1 April (TMT bulan ≤6) atau 1 Oktober (bulan >6)
function nextKGB($tmt_pppk, $today_str) {
    if (!$tmt_pppk) return null;
    $tmt   = new DateTime($tmt_pppk);
    $today = new DateTime($today_str);
    $m = (int)$tmt->format('n');
    $y = (int)$tmt->format('Y') + 2;
    $kgb = ($m <= 6) ? new DateTime("{$y}-04-01") : new DateTime("{$y}-10-01");
    while ($kgb <= $today) $kgb->modify('+2 years');
    return $kgb->format('Y-m-d');
}

$opd_id = 16;

// [nip, nama, kdjenkel(1=L/2=P), tgl_lahir, tmt_pppk, mkgolt, kdpangkat(angka), janak, ref_tpp_id]
// ref_tpp: 90=S1/D4, 91=D3, 92=SLTA/D1/D2
$employees = [
    ['197910042025212009', "UMI FARIHAH, S.E",                     2, '1979-10-04', '2025-07-01', 0, '9', 0, 90],
    ['198208102025211050', "JUMARI MUHAMMAD SHOLIHIN, SP",          1, '1982-08-10', '2025-07-01', 0, '9', 2, 90],
    ['198308312025212015', "MARFU'AH, S.I.Pust",                   2, '1983-08-31', '2025-07-01', 0, '9', 0, 90],
    ['198409202024212002', "SRI SUBEKTI, S.I.Pust.",               2, '1984-09-20', '2024-04-05', 2, '9', 2, 90],
    ['198605242025211038', "MOHAMMAD 'AINUR ROFIQ",                 1, '1986-05-24', '2025-09-01', 0, '1', 0, 92],
    ['198708012025211041', "HANIF NURYADIN, S.Pd.",                 1, '1987-08-01', '2025-07-01', 0, '9', 0, 90],
    ['198708302024212002', "INDAH WAHYUNINGSIH, S.I.Pust.",        2, '1987-08-30', '2024-04-05', 2, '9', 2, 90],
    ['199005052025211068', "ALI MAHMUDI, S.Pd",                    1, '1990-05-05', '2025-07-01', 0, '9', 1, 90],
    ['199105182023212038', "FUTRI SAL SABILA, S.Hum.",             2, '1991-05-18', '2023-07-18', 2, '9', 0, 90],
    ['199109092023211029', "RICCO SATRIA DWISASONO, S.Kom",        1, '1991-09-09', '2023-11-01', 2, '9', 0, 90],
    ['199106302024211005', "FANNI INDRA KUSUMA, S.Kom",            1, '1991-06-30', '2024-04-05', 2, '9', 1, 90],
    ['199112272025212029', "INDAH YUNITA",                          2, '1991-12-27', '2025-07-01', 0, '5', 1, 92],
    ['199302242025211039', "ANGGA BAGUS FEBRIANTO, S.Hum.",        1, '1993-02-24', '2025-07-01', 0, '9', 2, 90],
    ['199406042025212043', "LUTHFIA ANGGRAENI, S.Pd",              2, '1994-06-04', '2025-07-01', 0, '9', 0, 90],
    ['199505222025212032', "NORA DWI WIDIYANI, S.Pd",              2, '1995-05-22', '2025-07-01', 0, '9', 0, 90],
    ['199511212025212031', "SITI MUTIATUN, S.M.",                   2, '1995-11-21', '2025-07-01', 0, '9', 0, 90],
    ['199610252024212017', "ALFIYATURROHMANIYYAH, S.Hum",          2, '1996-10-25', '2024-04-05', 2, '9', 0, 90],
    ['199702262025211016', "MUHAMMAD ALI SODIQIN",                  1, '1997-02-26', '2025-07-01', 4, '5', 1, 92],
    ['199705052025212042', "NINIK NUR KHOLIP, A.Md.Kep.",          2, '1997-05-05', '2025-07-01', 3, '7', 0, 91],
    ['199705082025211017', "IMAM SETIADI, S.I.Pust.",              1, '1997-05-08', '2025-07-01', 0, '9', 0, 90],
    ['199804172025211026', "ROYAN ISTOFA",                          1, '1998-04-17', '2025-09-01', 0, '5', 1, 92],
    ['199808222025211018', "FAJAR ADI PRANOWO",                     1, '1998-08-22', '2025-07-01', 0, '5', 0, 92],
    ['200010122025211005', "AHMAD CHOIRUL ANAM",                    1, '2000-10-12', '2025-07-01', 0, '5', 0, 92],
];

$stmtInsert = $pdo->prepare('INSERT INTO pegawai
    (nip, nama_lengkap, jenis_kelamin, jenis_kepegawaian, golongan, pangkat,
     masa_kerja_golongan, tgl_lahir, tgl_cpns, tgl_pns, tmt_kenaikan_pangkat,
     status_pernikahan, jumlah_anak, persen_gaji,
     kd_jabatan_fungsional, jabatan_struktural_id, jabatan_fungsional_id, ref_tpp_id, opd_id, is_active)
    VALUES (?,?,?,\'PPPK\',?,?,?,?,?,?,?,\'KAWIN\',?,100,NULL,NULL,NULL,?,?,1)');

$stmtUpdate = $pdo->prepare('UPDATE pegawai SET
    nama_lengkap=?, jenis_kelamin=?, jenis_kepegawaian=\'PPPK\',
    golongan=?, pangkat=?, masa_kerja_golongan=?, tgl_lahir=?,
    tgl_cpns=?, tgl_pns=?, tmt_kenaikan_pangkat=?,
    status_pernikahan=\'KAWIN\', jumlah_anak=?, persen_gaji=100,
    kd_jabatan_fungsional=NULL, jabatan_struktural_id=NULL, jabatan_fungsional_id=NULL,
    ref_tpp_id=?, opd_id=?
    WHERE nip=?');

$inserted = 0; $updated = 0;

foreach ($employees as $e) {
    [$nip, $nama, $kdjenkel, $tgl_lahir, $tmt_pppk, $mkg, $kd_pangkat, $janak, $tpp_id] = $e;
    $jk      = ($kdjenkel == 1) ? 'L' : 'P';
    $gol     = $golMap[$kd_pangkat] ?? $kd_pangkat;
    $pangkat = 'PPPK Golongan ' . $gol;
    $tmt_kgb = nextKGB($tmt_pppk, $today);

    $ex = $pdo->prepare('SELECT id FROM pegawai WHERE nip=?');
    $ex->execute([$nip]);
    $existing = $ex->fetchColumn();

    if ($existing) {
        $stmtUpdate->execute([$nama, $jk, $gol, $pangkat, $mkg, $tgl_lahir,
            $tmt_pppk, $tmt_pppk, $tmt_kgb, $janak, $tpp_id, $opd_id, $nip]);
        echo "UPDATED : {$nip} {$nama}" . PHP_EOL;
        $updated++;
    } else {
        $stmtInsert->execute([$nip, $nama, $jk, $gol, $pangkat, $mkg, $tgl_lahir,
            $tmt_pppk, $tmt_pppk, $tmt_kgb, $janak, $tpp_id, $opd_id]);
        echo "INSERTED: {$nip} {$nama}" . PHP_EOL;
        $inserted++;
    }
}

echo PHP_EOL . "=== SELESAI === Inserted:{$inserted} Updated:{$updated}" . PHP_EOL;
$cnt = $pdo->query('SELECT COUNT(*) FROM pegawai WHERE opd_id=16')->fetchColumn();
echo "Total pegawai Dinarpus di DB: {$cnt}" . PHP_EOL;

echo PHP_EOL . '--- Verifikasi PPPK ---' . PHP_EOL;
$rows = $pdo->query("SELECT nip,nama_lengkap,jenis_kepegawaian,golongan,masa_kerja_golongan,tmt_kenaikan_pangkat,ref_tpp_id
    FROM pegawai WHERE opd_id=16 AND jenis_kepegawaian='PPPK' ORDER BY golongan DESC,nip")
    ->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo implode(' | ', $r) . PHP_EOL;
