<?php
$db = new PDO('mysql:host=localhost;dbname=penatus', 'root', '');

echo "=== PPPK pegawai ===\n";
$s = $db->query("SELECT id, nama_lengkap, nip, golongan, masa_kerja_golongan, is_active, opd_id, jenis_kepegawaian, tgl_lahir FROM pegawai WHERE jenis_kepegawaian='PPPK' LIMIT 10");
$rows = $s->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "id={$r['id']} nama={$r['nama_lengkap']} gol={$r['golongan']} mkg={$r['masa_kerja_golongan']} active={$r['is_active']} opd={$r['opd_id']} lahir={$r['tgl_lahir']}\n";
}

echo "\n=== PPPK gapok check ===\n";
foreach ($rows as $r) {
    $stmt = $db->prepare("SELECT gaji_pokok, masa_kerja FROM ref_gaji_pokok WHERE jenis='PPPK' AND golongan=? AND masa_kerja<=? AND is_active=1 ORDER BY masa_kerja DESC, berlaku_mulai DESC LIMIT 1");
    $stmt->execute([$r['golongan'], $r['masa_kerja_golongan']]);
    $gapok = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "id={$r['id']} gol={$r['golongan']} mkg={$r['masa_kerja_golongan']} -> gapok=" . ($gapok ? $gapok['gaji_pokok'] . ' (mkg ' . $gapok['masa_kerja'] . ')' : 'NOT FOUND') . "\n";
}

echo "\n=== hitung_rekap query simulation (opd_id=0 = all, is_active=1) ===\n";
$s = $db->query("SELECT id, nama_lengkap, jenis_kepegawaian, is_active FROM pegawai WHERE is_active=1 AND jenis_kepegawaian IN ('PNS','PPPK') LIMIT 20");
$result = $s->fetchAll(PDO::FETCH_ASSOC);
$pns = 0; $pppk = 0;
foreach ($result as $r) {
    if ($r['jenis_kepegawaian'] === 'PNS') $pns++;
    else $pppk++;
}
echo "Total PNS: $pns, Total PPPK: $pppk\n";
