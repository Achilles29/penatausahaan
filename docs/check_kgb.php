<?php
$db = new PDO('mysql:host=localhost;dbname=penatus', 'root', '');

// Check columns in pegawai table
$s = $db->query("SHOW COLUMNS FROM pegawai LIKE '%kgb%'");
echo "=== kolom pegawai LIKE kgb ===\n";
print_r($s->fetchAll(PDO::FETCH_ASSOC));

$s = $db->query("SHOW COLUMNS FROM pegawai LIKE '%pns%'");
echo "=== kolom pegawai LIKE pns ===\n";
print_r($s->fetchAll(PDO::FETCH_ASSOC));

// Sample kgb data
$s = $db->query("SELECT id, nama_lengkap, tgl_pns, masa_kerja_golongan FROM pegawai WHERE jenis_kepegawaian='PNS' AND tgl_pns IS NOT NULL LIMIT 5");
echo "=== sample PNS tgl_pns ===\n";
print_r($s->fetchAll(PDO::FETCH_ASSOC));
