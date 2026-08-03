<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Var: $row(+details), $info, $penmap, $judul */
$this->load->view('npd/_print_head', array('judul' => 'NPD ' . $row->nomor_npd));
$total = 0; foreach ($row->details as $d) $total += (float) $d->jumlah;
?>
<div class="page">
  <h1 class="doc-title">NOTA PENCAIRAN DANA (NPD)</h1>
  <div class="doc-sub">Nomor: <?= html_escape($row->nomor_npd) ?></div>

  <table class="meta" style="margin-bottom:8px">
    <tr><td style="width:130px">OPD</td><td style="width:8px">:</td><td><?= html_escape($info->nama_opd ?? '-') ?></td></tr>
    <tr><td>Program</td><td>:</td><td><?= html_escape($info->nama_program ?? '-') ?></td></tr>
    <tr><td>Kegiatan</td><td>:</td><td><?= html_escape($info->nama_kegiatan ?? '-') ?></td></tr>
    <tr><td>Sub Kegiatan</td><td>:</td><td><span class="kode"><?= html_escape($info->kode_subkegiatan ?? '') ?></span> <?= html_escape($info->nama_subkegiatan ?? '-') ?></td></tr>
    <tr><td>Sumber Dana</td><td>:</td><td><?= html_escape($info->sumber_dana ?? '-') ?></td></tr>
    <tr><td>Perihal</td><td>:</td><td><?= html_escape($row->perihal) ?></td></tr>
    <?php if ($row->pekerjaan): ?><tr><td>Pekerjaan</td><td>:</td><td><?= nl2br(html_escape($row->pekerjaan)) ?></td></tr><?php endif; ?>
    <tr><td>Tanggal</td><td>:</td><td><?= tanggal_id($row->tanggal) ?></td></tr>
  </table>

  <table class="grid">
    <thead><tr><th style="width:32px">No</th><th style="width:170px">Kode Rekening</th><th>Uraian</th><th style="width:150px">Jumlah (Rp)</th></tr></thead>
    <tbody>
      <?php foreach ($row->details as $i => $d): ?>
      <tr>
        <td class="c"><?= $i+1 ?></td>
        <td class="kode"><?= html_escape($d->kode_rekening) ?></td>
        <td><?= html_escape($d->uraian) ?></td>
        <td class="r"><?= number_format($d->jumlah, 0, ',', '.') ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="totrow"><td colspan="3" class="r">JUMLAH</td><td class="r"><?= number_format($total, 0, ',', '.') ?></td></tr>
    </tbody>
  </table>
  <p style="margin:6px 0"><em>Terbilang: <?= ucfirst(trim(terbilang_rupiah($total))) ?></em></p>

  <div class="ttd-wrap">
    <div class="ttd">
      Mengetahui,<br>Pengguna Anggaran / Kepala OPD
      <div class="sp"></div>
      <b><u><?= html_escape($info->kepala_opd ?: '....................') ?></u></b><br>
      NIP. <?= html_escape($info->nip_kepala ?: '....................') ?>
    </div>
    <div class="ttd">
      &nbsp;<br>PPTK
      <div class="sp"></div>
      <b><u>....................</u></b><br>NIP. ....................
    </div>
    <div class="ttd">
      <?= html_escape($info->nama_opd ? '' : '' ) ?><?= date('d F Y', strtotime($row->tanggal)) ?><br>Bendahara Pengeluaran
      <div class="sp"></div>
      <b><u>....................</u></b><br>NIP. ....................
    </div>
  </div>
</div>
</body></html>
