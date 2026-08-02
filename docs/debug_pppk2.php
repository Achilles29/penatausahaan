<?php
$db = new PDO('mysql:host=localhost;dbname=penatus', 'root', '');

echo "=== Full PPPK query (like hitung_rekap) ===\n";
$s = $db->query("SELECT id, nama_lengkap, jenis_kepegawaian, is_active, ref_tpp_id FROM pegawai WHERE is_active=1 AND jenis_kepegawaian IN ('PNS','PPPK')");
$rows = $s->fetchAll(PDO::FETCH_ASSOC);
$pns = 0; $pppk = 0;
foreach ($rows as $r) {
    if ($r['jenis_kepegawaian'] === 'PPPK') {
        $pppk++;
        echo "PPPK: id={$r['id']} {$r['nama_lengkap']} ref_tpp_id={$r['ref_tpp_id']}\n";
    } else {
        $pns++;
    }
}
echo "Total: PNS=$pns, PPPK=$pppk\n";

echo "\n=== ref_tpp for PPPK ===\n";
$s = $db->query("SELECT * FROM ref_tpp LIMIT 10");
$rows = $s->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) print_r($r);
