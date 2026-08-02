<?php
$db = new PDO('mysql:host=localhost;dbname=penatus', 'root', '');

// Check current value
$s = $db->query("SELECT kode, nama, nominal FROM ref_tunjangan_jabatan WHERE kode='ES_IIIB'");
$row = $s->fetch(PDO::FETCH_ASSOC);
echo "Current: kode={$row['kode']}, nama={$row['nama']}, nominal={$row['nominal']}\n";

// Fix: 1,035,000 → 980,000
$affected = $db->exec("UPDATE ref_tunjangan_jabatan SET nominal=980000 WHERE kode='ES_IIIB'");
echo "Rows updated: {$affected}\n";

// Verify
$s = $db->query("SELECT kode, nama, nominal FROM ref_tunjangan_jabatan WHERE kode='ES_IIIB'");
$row = $s->fetch(PDO::FETCH_ASSOC);
echo "After fix: nominal={$row['nominal']}\n";
