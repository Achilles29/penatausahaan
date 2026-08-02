<?php
$db = new PDO('mysql:host=localhost;dbname=penatus', 'root', '');

// Add tmt_kgb column after tmt_kenaikan_pangkat
$db->exec("ALTER TABLE pegawai ADD COLUMN tmt_kgb DATE NULL COMMENT 'TMT KGB Berikutnya (fallback jika tgl_pns tidak diisi)' AFTER tmt_kenaikan_pangkat");
echo "Column tmt_kgb added.\n";

// Verify
$s = $db->query("SHOW COLUMNS FROM pegawai LIKE 'tmt_kgb'");
print_r($s->fetch(PDO::FETCH_ASSOC));
