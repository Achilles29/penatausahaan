<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Kepala dokumen cetak — adaptif ke format.
 * Var: $judul, $format ('html'|'pdf'|'excel'|'word')
 * Menyediakan tombol Cetak/PDF, Unduh Excel, Unduh Word saat mode layar.
 */
$format = isset($format) ? $format : 'html';
$is_dl  = ($format === 'excel' || $format === 'word');
$base   = current_url(); // tanpa query string
$css = '
  * { box-sizing: border-box; }
  body { font-family: "Times New Roman", Georgia, serif; font-size: 12px; color:#000; }
  .page { background:#fff; }
  h1.doc-title { text-align:center; font-size:15px; margin:0 0 2px; }
  .doc-sub { text-align:center; font-size:12px; margin:0 0 10px; }
  table { border-collapse:collapse; }
  .kop td { vertical-align:middle; }
  .kop .nm1 { font-size:15px; font-weight:bold; letter-spacing:.3px; }
  .kop .nm2 { font-size:17px; font-weight:bold; letter-spacing:.3px; }
  .kop .adr { font-size:11px; }
  .kop-line { border-bottom:3px solid #000; height:3px; margin:2px 0 10px; }
  .meta td { padding:1px 4px; vertical-align:top; }
  .kv td { padding:1px 4px; vertical-align:top; }
  table.grid th, table.grid td { border:1px solid #000; padding:4px 6px; vertical-align:top; }
  table.grid th { background:#eee; text-align:center; font-weight:bold; }
  .r { text-align:right; } .c { text-align:center; }
  .kode { font-family:Consolas,monospace; font-size:11px; }
  .muted { color:#555; }
  .totrow td { font-weight:bold; background:#f3f3f3; }
  .ttd { width:33%; text-align:center; font-size:12px; vertical-align:top; }
  .ttd .sp { height:56px; }
  table.ttd-wrap { width:100%; margin-top:22px; }
';
?>
<!DOCTYPE html>
<html lang="id" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word">
<head>
<meta charset="utf-8">
<title><?= isset($judul) ? html_escape($judul) : 'Cetak' ?></title>
<?php if ($format === 'word'): ?>
<!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View><w:Zoom>100</w:Zoom></w:WordDocument></xml><![endif]-->
<?php endif; ?>
<style>
<?= $css ?>
<?php if ($is_dl): ?>
  @page { size: A4 portrait; margin: 1.4cm 1.8cm; }
<?php else: ?>
  body { background:#f0f0f0; margin:0; }
  .page { width:210mm; margin:12px auto; padding:16mm 18mm; box-shadow:0 0 6px rgba(0,0,0,.2); }
  .toolbar { text-align:center; padding:10px; }
  .toolbar a, .toolbar button { display:inline-block; padding:7px 16px; margin:0 4px; font-size:13px; cursor:pointer; border:0; border-radius:5px; background:#696cff; color:#fff; text-decoration:none; }
  .toolbar .excel { background:#1d6f42; } .toolbar .word { background:#2b579a; } .toolbar .print { background:#5a5f6a; }
  @media print { body { background:#fff; } .toolbar { display:none; } .page { box-shadow:none; margin:0; width:auto; padding:0; } @page { size:A4; margin:14mm 16mm; } }
<?php endif; ?>
</style>
</head>
<body>
<?php if ( ! $is_dl): ?>
<div class="toolbar">
  <button class="print" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
  <a class="excel" href="<?= $base ?>?format=excel">⬇ Unduh Excel</a>
  <a class="word" href="<?= $base ?>?format=word">⬇ Unduh Word</a>
</div>
<?php if ($format === 'pdf'): ?><script>window.addEventListener('load',function(){setTimeout(function(){window.print();},350);});</script><?php endif; ?>
<?php endif; ?>
