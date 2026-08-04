<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Var: $row(+details), $info, $penmap, $instansi, $format, $judul */
$this->load->view('npd/_print_head', array('judul' => 'Pindah Buku ' . $row->nomor_npd, 'format' => $format));

$ins  = $instansi;
$kota = $ins['kota'] ?: '';
$opd  = $info->nama_opd ?? '-';
$opd_s = trim((string) ($info->singkatan ?? '')) ?: $opd;
$unit = trim((string) ($info->unit_nama ?? ''));
$pptk_jab = 'PPTK ' . ($unit !== '' ? ucwords(strtolower($unit)) . ' ' : '') . $opd_s;
$pptk_nm  = ($pejabat['pptk']->nama_lengkap ?? '') ?: ($info->pptk_nama ?: '');
$pptk_nip = ($pejabat['pptk']->nip          ?? '') ?: ($info->pptk_nip ?: '');

// flatten penerima
$rows = array(); $g_netto = 0; $g_pajak = 0; $pajak_by = array();
foreach ($row->details as $d) {
  foreach (($penmap[$d->id] ?? array()) as $p) {
    $netto = isset($p->pajak['netto']) ? (float) $p->pajak['netto'] : (float) $p->jumlah;
    $g_netto += $netto; $g_pajak += (float) $p->pajak['total_pajak'];
    foreach ($p->pajak['lines'] as $ln) $pajak_by[$ln['jenis']] = ($pajak_by[$ln['jenis']] ?? 0) + $ln['nilai'];
    $rows[] = array('p' => $p, 'netto' => $netto);
  }
}
$g_total = $g_netto + $g_pajak;
?>
<div class="page">
  <h1 class="doc-title">DAFTAR PEMINDAHBUKUAN</h1>
  <div class="doc-sub"><?= html_escape(strtoupper($row->perihal)) ?></div>

  <table class="meta" style="margin-bottom:8px">
    <tr><td style="width:130px"><?= html_escape(strtoupper($opd)) ?></td></tr>
    <tr><td>NPD Nomor</td><td style="width:8px">:</td><td><?= html_escape($row->nomor_npd) ?> &middot; <?= tanggal_id($row->tanggal) ?></td></tr>
  </table>

  <table class="grid" border="1" cellspacing="0" style="width:100%">
    <thead><tr>
      <th style="width:32px">No</th><th>Nama Penerima</th><th style="width:150px">Nomor Rekening Bank</th>
      <th style="width:130px">Jumlah (Rp)</th><th style="width:180px">Keterangan Belanja</th>
    </tr></thead>
    <tbody>
      <?php $no = 0; foreach ($rows as $r): $p = $r['p']; ?>
      <tr>
        <td class="c"><?= ++$no ?></td>
        <td><?= html_escape($p->nama_live) ?>
          <?php if ($p->sumber === 'pegawai' && $p->peg_nip): ?><br><span class="muted" style="font-size:10px">NIP <?= html_escape($p->peg_nip) ?></span><?php endif; ?>
        </td>
        <td class="kode"><?= html_escape(trim(($p->bank_live ? $p->bank_live . ' ' : '') . ($p->norek_live ?: '-'))) ?></td>
        <td class="r"><?= number_format($r['netto'], 0, ',', '.') ?></td>
        <td><?= html_escape($p->uraian ?: '') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if ($no === 0): ?><tr><td colspan="5" class="c muted">Belum ada penerima pada NPD ini.</td></tr><?php endif; ?>
      <tr class="totrow"><td colspan="3" class="r">JUMLAH</td><td class="r"><?= number_format($g_netto, 0, ',', '.') ?></td><td></td></tr>
    </tbody>
  </table>

  <?php if ($g_pajak > 0): ?>
  <p style="margin:10px 0 3px"><b>Setoran Pajak ke Kas Negara/Daerah:</b></p>
  <table class="grid" border="1" cellspacing="0" style="width:65%">
    <tbody>
      <?php foreach ($pajak_by as $j => $v): ?>
      <tr><td><?= html_escape(label_jenis_pajak($j)) ?></td><td class="r" style="width:150px"><?= number_format($v, 0, ',', '.') ?></td></tr>
      <?php endforeach; ?>
      <tr class="totrow"><td class="r">Total Pajak</td><td class="r"><?= number_format($g_pajak, 0, ',', '.') ?></td></tr>
    </tbody>
  </table>
  <p style="margin:6px 0"><em>Total dipindahbukukan (netto penerima + setoran pajak): <b>Rp <?= number_format($g_total, 0, ',', '.') ?></b>
    (<?= ucfirst(trim(terbilang_rupiah($g_total))) ?>)</em></p>
  <?php else: ?>
  <p style="margin:6px 0"><em>Jumlah dipindahbukukan: <b>Rp <?= number_format($g_netto, 0, ',', '.') ?></b>
    (<?= ucfirst(trim(terbilang_rupiah($g_netto))) ?>)</em></p>
  <?php endif; ?>

  <!-- TANDA TANGAN -->
  <table class="ttd-wrap"><tr>
    <td class="ttd" style="width:60%">&nbsp;</td>
    <td class="ttd" style="width:40%">
      <?= html_escape($kota) ?>, <?= tanggal_id($row->tanggal) ?><br><?= html_escape($pptk_jab) ?>
      <div class="sp"></div>
      <b><u><?= html_escape($pptk_nm ?: '....................') ?></u></b><br>
      NIP. <?= html_escape($pptk_nip ?: '....................') ?>
    </td>
  </tr></table>
</div>
</body></html>
