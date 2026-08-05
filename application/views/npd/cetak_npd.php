<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Var: $row(+details), $info, $penmap, $instansi, $format, $judul */
$this->load->view('npd/_print_head', array('judul' => 'NPD ' . $row->nomor_npd, 'format' => $format));

$total = 0; foreach ($row->details as $d) $total += (float) $d->jumlah;
$ins   = $instansi;
$kota  = $ins['kota'] ?: '';
$opd   = $info->nama_opd ?? '-';
$opd_s = trim((string) ($info->singkatan ?? '')) ?: $opd;
$unit  = trim((string) ($info->unit_nama ?? ''));
$pptk_jab = 'PPTK ' . ($unit !== '' ? ucwords(strtolower($unit)) . ' ' : '') . $opd_s;

// PPTK dari data pegawai (variabel, tanpa hardcode). Bendahara & PPK cukup paraf (tanpa nama).
$pj       = $pejabat;
$pptk_nm  = ($pj['pptk']->nama_lengkap ?? '') ?: ($info->pptk_nama ?: '');
$pptk_nip = ($pj['pptk']->nip          ?? '') ?: ($info->pptk_nip ?: '');
$logo = $ins['logo'] ? base_url($ins['logo']) : '';
?>
<div class="page">

  <!-- KOP SURAT -->
  <table class="kop" style="width:100%">
    <tr>
      <?php if ($logo): ?><td style="width:64px; text-align:center"><img src="<?= $logo ?>" alt="logo" width="43" height="64" style="width:43px; height:64px"></td><?php endif; ?>
      <td style="text-align:center">
        <div class="nm1"><?= html_escape($ins['pemda']) ?></div>
        <div class="nm2"><?= html_escape(strtoupper($opd)) ?></div>
        <div class="adr"><?= html_escape($ins['alamat']) ?> &nbsp; <?= html_escape($ins['kontak']) ?></div>
        <div class="adr"><?= html_escape($ins['website']) ?></div>
      </td>
      <?php if ($logo): ?><td style="width:64px">&nbsp;</td><?php endif; ?>
    </tr>
  </table>
  <div class="kop-line"></div>

  <!-- NOMOR / KEPADA -->
  <table style="width:100%; margin-bottom:6px"><tr>
    <td style="width:55%; vertical-align:top">
      <table class="kv">
        <tr><td style="width:70px">Nomor</td><td style="width:8px">:</td><td><?= html_escape($row->nomor_npd) ?></td></tr>
        <tr><td>Lampiran</td><td>:</td><td>-</td></tr>
        <tr><td>Perihal</td><td>:</td><td><b><?= html_escape($ins['perihal_npd']) ?></b></td></tr>
      </table>
    </td>
    <td style="width:45%; vertical-align:top">
      <div style="text-align:right"><?= html_escape($kota) ?>, <?= tanggal_id($row->tanggal) ?></div>
      <div style="margin-top:6px">Kepada Yth.</div>
      <div><b>Bendahara Pengeluaran</b></div>
      <div><?= html_escape($opd) ?></div>
      <div>di -</div>
      <div style="text-align:right; padding-right:30px"><b><?= html_escape($kota) ?></b></div>
    </td>
  </tr></table>

  <p style="margin:6px 0 2px">Dengan hormat,</p>
  <p style="margin:0 0 4px">Yang bertanda tangan di bawah ini :</p>
  <table class="kv" style="margin-bottom:8px">
    <tr><td style="width:95px">Nama</td><td style="width:8px">:</td><td><?= html_escape($pptk_nm ?: '....................') ?></td></tr>
    <tr><td>NIP</td><td>:</td><td><?= html_escape($pptk_nip ?: '....................') ?></td></tr>
    <tr><td>Jabatan</td><td>:</td><td><?= html_escape($pptk_jab) ?></td></tr>
    <tr><td>Program</td><td>:</td><td><?= html_escape($info->nama_program ?? '-') ?></td></tr>
    <tr><td>Kegiatan</td><td>:</td><td><?= html_escape($info->nama_kegiatan ?? '-') ?></td></tr>
    <tr><td>Sub Kegiatan</td><td>:</td><td><?= html_escape($info->nama_subkegiatan ?? '-') ?></td></tr>
    <tr><td>Pekerjaan</td><td>:</td><td><?= html_escape($row->perihal) ?></td></tr>
    <tr><td>Sumber Dana</td><td>:</td><td><?= html_escape($info->sumber_dana ?? '-') ?></td></tr>
  </table>

  <p style="margin:6px 0; text-align:justify">
    Mohon untuk dapat menyiapkan dana sebesar <b>Rp <?= number_format($total, 0, ',', '.') ?>,-</b>
    ( <?= ucfirst(trim(terbilang_rupiah($total))) ?> ) guna pembayaran secara Non Tunai atas
    kegiatan belanja daerah dengan perincian sebagai berikut :
  </p>

  <?php $kode_sk = trim((string) ($info->kode_subkegiatan ?? '')); ?>
  <table class="grid" border="1" cellspacing="0" style="width:100%">
    <thead><tr>
      <th style="width:32px">No</th><th style="width:235px">Kode Rekening</th><th>Belanja</th><th style="width:140px">Jumlah (Rp)</th>
    </tr></thead>
    <tbody>
      <?php foreach ($row->details as $i => $d): ?>
      <tr>
        <td class="c"><?= $i + 1 ?></td>
        <td class="kode"><?= html_escape(($kode_sk !== '' ? $kode_sk . '.' : '') . $d->kode_rekening) ?></td>
        <td><?= html_escape($d->uraian) ?></td>
        <td class="r"><?= number_format($d->jumlah, 0, ',', '.') ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="totrow"><td colspan="3" class="r">JUMLAH</td><td class="r"><?= number_format($total, 0, ',', '.') ?></td></tr>
    </tbody>
  </table>

  <!-- TANDA TANGAN: PPTK penanda tangan utama; Bendahara & PPK cukup paraf (tanpa nama) -->
  <table style="width:100%; margin-top:20px"><tr>
    <td style="width:45%">&nbsp;</td>
    <td style="width:55%; text-align:center">
      <?= html_escape($kota) ?>, <?= tanggal_id($row->tanggal) ?><br>
      PPTK <?= html_escape($unit !== '' ? ucwords(strtolower($unit)) : 'Kegiatan') ?><br><?= html_escape($opd) ?>
      <div style="height:58px"></div>
      <b><u><?= html_escape($pptk_nm ?: '....................') ?></u></b><br>
      NIP. <?= html_escape($pptk_nip ?: '....................') ?>
    </td>
  </tr></table>

  <table border="1" cellspacing="0" style="width:55%; margin-left:auto; margin-top:8px; text-align:center">
    <tr><td style="width:62%; padding:5px 4px">Bendahara Pengeluaran</td><td style="padding:5px 4px">PPK SKPD</td></tr>
    <tr><td style="height:48px">&nbsp;</td><td>&nbsp;</td></tr>
  </table>
</div>
</body></html>
