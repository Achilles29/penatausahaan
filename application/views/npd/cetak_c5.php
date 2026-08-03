<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Var: $row(+details), $info, $penmap, $judul.  C5 = daftar penerimaan / tanda terima. */
$this->load->view('npd/_print_head', array('judul' => 'C5 ' . $row->nomor_npd));
$g_netto = 0;
?>
<div class="page">
  <h1 class="doc-title">DAFTAR PENERIMAAN PEMBAYARAN (C5)</h1>
  <div class="doc-sub">NPD Nomor: <?= html_escape($row->nomor_npd) ?> &middot; <?= tanggal_id($row->tanggal) ?></div>

  <table class="meta" style="margin-bottom:8px">
    <tr><td style="width:130px">OPD</td><td style="width:8px">:</td><td><?= html_escape($info->nama_opd ?? '-') ?></td></tr>
    <tr><td>Sub Kegiatan</td><td>:</td><td><?= html_escape($info->nama_subkegiatan ?? '-') ?></td></tr>
    <tr><td>Perihal</td><td>:</td><td><?= html_escape($row->perihal) ?></td></tr>
  </table>

  <table class="grid">
    <thead><tr>
      <th style="width:28px">No</th><th>Nama Penerima</th><th>Uraian</th>
      <th style="width:130px">Jumlah Diterima (Rp)</th><th style="width:150px">Tanda Tangan</th>
    </tr></thead>
    <tbody>
      <?php $no = 0; foreach ($row->details as $d): $pens = $penmap[$d->id] ?? array();
        foreach ($pens as $p): $g_netto += $p->pajak['netto']; ?>
        <tr>
          <td class="c"><?= ++$no ?></td>
          <td><?= html_escape($p->nama_live) ?><?php if ($p->sumber === 'pegawai'): ?><br><span class="muted" style="font-size:10px">NIP <?= html_escape($p->peg_nip) ?></span><?php endif; ?></td>
          <td><?= html_escape($p->uraian ?: $d->uraian) ?></td>
          <td class="r"><?= number_format($p->pajak['netto'], 0, ',', '.') ?></td>
          <td class="c"><?= $no ?>. ...................</td>
        </tr>
      <?php endforeach; endforeach; ?>
      <?php if ($no === 0): ?><tr><td colspan="5" class="c muted">Belum ada penerima.</td></tr><?php endif; ?>
      <tr class="totrow"><td colspan="3" class="r">JUMLAH</td><td class="r"><?= number_format($g_netto, 0, ',', '.') ?></td><td></td></tr>
    </tbody>
  </table>
  <p style="margin:6px 0"><em>Terbilang: <?= ucfirst(trim(terbilang_rupiah($g_netto))) ?></em></p>

  <div class="ttd-wrap">
    <div class="ttd">Mengetahui,<br>Pengguna Anggaran / Kepala OPD<div class="sp"></div><b><u><?= html_escape($info->kepala_opd ?: '....................') ?></u></b><br>NIP. <?= html_escape($info->nip_kepala ?: '....................') ?></div>
    <div class="ttd">&nbsp;<div class="sp" style="height:20px"></div><?= date('d F Y', strtotime($row->tanggal)) ?><br>Bendahara Pengeluaran<div class="sp"></div><b><u>....................</u></b><br>NIP. ....................</div>
  </div>
</div>
</body></html>
