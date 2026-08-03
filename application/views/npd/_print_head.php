<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Partial: kepala dokumen cetak. Var: $judul */
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title><?= isset($judul) ? html_escape($judul) : 'Cetak' ?></title>
<style>
  * { box-sizing: border-box; }
  body { font-family: "Times New Roman", Georgia, serif; font-size: 12px; color:#000; margin:0; background:#f0f0f0; }
  .page { background:#fff; width:210mm; min-height:auto; margin:12px auto; padding:16mm 18mm; box-shadow:0 0 6px rgba(0,0,0,.2); }
  .toolbar { text-align:center; padding:10px; }
  .toolbar button { padding:7px 18px; font-size:13px; cursor:pointer; border:0; border-radius:5px; background:#696cff; color:#fff; }
  h1.doc-title { text-align:center; font-size:15px; margin:0 0 2px; letter-spacing:.5px; }
  .doc-sub { text-align:center; font-size:12px; margin:0 0 10px; }
  table { width:100%; border-collapse:collapse; }
  table.grid th, table.grid td { border:1px solid #333; padding:4px 6px; vertical-align:top; }
  table.grid th { background:#eee; text-align:center; font-weight:bold; }
  .r { text-align:right; } .c { text-align:center; }
  .meta td { padding:1px 4px; vertical-align:top; }
  .kode { font-family:Consolas,monospace; font-size:11px; }
  .ttd-wrap { display:flex; justify-content:space-between; margin-top:26px; }
  .ttd { width:32%; text-align:center; font-size:12px; }
  .ttd .sp { height:56px; }
  .muted { color:#555; }
  .totrow td { font-weight:bold; background:#f3f3f3; }
  @media print {
    body { background:#fff; }
    .toolbar { display:none; }
    .page { box-shadow:none; margin:0; width:auto; padding:0; }
    @page { size:A4; margin:14mm 16mm; }
  }
</style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">🖨️ Cetak / Simpan PDF</button></div>
