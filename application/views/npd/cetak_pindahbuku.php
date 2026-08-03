<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Var: $row(+details), $info, $penmap, $judul */
$this->load->view('npd/_print_head', array('judul' => 'Pindah Buku ' . $row->nomor_npd));
$rekmap = array(); foreach ($row->details as $d) $rekmap[$d->id] = $d;
$g_bruto = 0; $g_pajak = 0; $g_netto = 0; $pajak_by = array();
?>
<div class="page">
  <h1 class="doc-title">DAFTAR PINDAH BUKU</h1>
  <div class="doc-sub">NPD Nomor: <?= html_escape($row->nomor_npd) ?> &middot; <?= tanggal_id($row->tanggal) ?></div>

  <table class="meta" style="margin-bottom:8px">
    <tr><td style="width:130px">OPD</td><td style="width:8px">:</td><td><?= html_escape($info->nama_opd ?? '-') ?></td></tr>
    <tr><td>Sub Kegiatan</td><td>:</td><td><span class="kode"><?= html_escape($info->kode_subkegiatan ?? '') ?></span> <?= html_escape($info->nama_subkegiatan ?? '-') ?></td></tr>
    <tr><td>Perihal</td><td>:</td><td><?= html_escape($row->perihal) ?></td></tr>
  </table>

  <table class="grid">
    <thead><tr>
      <th style="width:28px">No</th><th>Penerima</th><th>Uraian</th>
      <th style="width:110px">Bruto</th><th style="width:120px">Pajak</th><th style="width:110px">Netto</th>
    </tr></thead>
    <tbody>
      <?php $no = 0; foreach ($row->details as $d): $pens = $penmap[$d->id] ?? array(); if (empty($pens)) continue; ?>
        <tr><td colspan="6" style="background:#f0f0f0"><span class="kode"><?= html_escape($d->kode_rekening) ?></span> — <?= html_escape($d->uraian) ?></td></tr>
        <?php foreach ($pens as $p):
          $g_bruto += (float) $p->jumlah; $g_pajak += $p->pajak['total_pajak']; $g_netto += $p->pajak['netto'];
          foreach ($p->pajak['lines'] as $ln) { $pajak_by[$ln['jenis']] = ($pajak_by[$ln['jenis']] ?? 0) + $ln['nilai']; }
        ?>
        <tr>
          <td class="c"><?= ++$no ?></td>
          <td><?= html_escape($p->nama_live) ?>
            <?php if ($p->sumber === 'pegawai'): ?><br><span class="muted" style="font-size:10px">NIP <?= html_escape($p->peg_nip) ?><?= $p->npwp_live ? ' · NPWP '.html_escape($p->npwp_live) : '' ?></span>
            <?php elseif ($p->nama_bank): ?><br><span class="muted" style="font-size:10px"><?= html_escape($p->nama_bank.' '.$p->no_rekening) ?></span><?php endif; ?>
          </td>
          <td><?= html_escape($p->uraian) ?></td>
          <td class="r"><?= number_format($p->jumlah, 0, ',', '.') ?></td>
          <td class="r"><?= $p->pajak['total_pajak'] > 0 ? number_format($p->pajak['total_pajak'], 0, ',', '.') : '-' ?>
            <?php if (!empty($p->pajak['lines'])): ?><br><span class="muted" style="font-size:9px"><?php foreach ($p->pajak['lines'] as $ln) echo label_jenis_pajak($ln['jenis']).' '.rtrim(rtrim(number_format($ln['tarif'],2,',','.'),'0'),',').'% '; ?></span><?php endif; ?>
          </td>
          <td class="r"><?= number_format($p->pajak['netto'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <?php if ($no === 0): ?><tr><td colspan="6" class="c muted">Belum ada penerima pada NPD ini.</td></tr><?php endif; ?>
      <tr class="totrow"><td colspan="3" class="r">JUMLAH</td>
        <td class="r"><?= number_format($g_bruto, 0, ',', '.') ?></td>
        <td class="r"><?= number_format($g_pajak, 0, ',', '.') ?></td>
        <td class="r"><?= number_format($g_netto, 0, ',', '.') ?></td>
      </tr>
    </tbody>
  </table>

  <?php if ($pajak_by): ?>
  <table class="grid" style="width:60%; margin-top:10px">
    <thead><tr><th colspan="2">Rekapitulasi Pajak yang Dipungut/Disetor</th></tr></thead>
    <tbody>
      <?php foreach ($pajak_by as $j => $v): ?>
      <tr><td><?= html_escape(label_jenis_pajak($j)) ?></td><td class="r" style="width:140px"><?= number_format($v, 0, ',', '.') ?></td></tr>
      <?php endforeach; ?>
      <tr class="totrow"><td class="r">Total Pajak</td><td class="r"><?= number_format($g_pajak, 0, ',', '.') ?></td></tr>
    </tbody>
  </table>
  <?php endif; ?>

  <p style="margin:6px 0"><em>Jumlah bersih dipindahbukukan: Rp <?= number_format($g_netto, 0, ',', '.') ?> (<?= ucfirst(trim(terbilang_rupiah($g_netto))) ?>)</em></p>

  <div class="ttd-wrap">
    <div class="ttd">Mengetahui,<br>Pengguna Anggaran / Kepala OPD<div class="sp"></div><b><u><?= html_escape($info->kepala_opd ?: '....................') ?></u></b><br>NIP. <?= html_escape($info->nip_kepala ?: '....................') ?></div>
    <div class="ttd">&nbsp;<div class="sp" style="height:20px"></div><?= date('d F Y', strtotime($row->tanggal)) ?><br>Bendahara Pengeluaran<div class="sp"></div><b><u>....................</u></b><br>NIP. ....................</div>
  </div>
</div>
</body></html>
