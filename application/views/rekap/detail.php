<?php defined('BASEPATH') OR exit('No direct script access allowed');
$bln_names = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$p = $peg_info;
?>
<style>
.det-hdr { background:linear-gradient(135deg,#1d4ed8,#3b82f6); color:#fff; border-radius:10px 10px 0 0; }
.tbl-det { font-size:.78rem; }
.tbl-det th { font-size:.72rem; white-space:nowrap; background:#f1f5f9; }
.tbl-det td { white-space:nowrap; vertical-align:middle; }
.tbl-det td.num { text-align:right; font-variant-numeric:tabular-nums; }
.tbl-det tr.ke-row { background:#fef9c3; }
.tbl-det tr.seksion th { background:#1d4ed8; color:#fff; }
.tbl-det tr.sub-total td { background:#e0f2fe; font-weight:600; }
.tbl-det tr.bersih td { background:#d1fae5; font-weight:700; color:#065f46; }
.tbl-det tr.tpp-hdr td { background:#dcfce7; font-weight:600; color:#14532d; }
.tbl-det tr.tpp-bersih td { background:#bbf7d0; font-weight:700; color:#14532d; }
.tbl-det tr.grand-total td { background:#ede9fe; font-weight:700; color:#4c1d95; font-size:.84rem; }
.tbl-det tr.belanja-row td { background:#fef9c3; }
.tbl-det td.neg { color:#b91c1c; }
.rekening-badge { font-size:.68rem; padding:1px 5px; background:#e8eaf6; color:#3949ab; border-radius:4px; font-family:monospace; }
</style>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
  <div>
    <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">
      <i class="fa-solid fa-arrow-left me-1"></i>Kembali
    </a>
  </div>
  <h6 class="mb-0 text-muted">Detail per rekening — <?= html_escape($p['nama']) ?></h6>
  <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
    <i class="fa-solid fa-print me-1"></i>Cetak
  </button>
</div>

<!-- INFO PEGAWAI -->
<div class="card mb-3 shadow-sm">
  <div class="card-body det-hdr py-2 px-4">
    <strong><?= html_escape($p['nama']) ?></strong>
    <span class="ms-3 opacity-75 small"><?= html_escape($p['nip']) ?></span>
    <span class="ms-3 badge bg-light text-dark"><?= html_escape($p['jenis']) ?> — Gol. <?= html_escape($p['golongan']) ?></span>
    <?php if ($p['jab_struktural']): ?>
    <span class="ms-2 badge bg-warning text-dark"><?= html_escape($p['jab_struktural']) ?> (<?= $p['eselon'] ?>)</span>
    <?php endif; ?>
    <?php if ($p['jab_fungsional']): ?>
    <span class="ms-2 badge bg-info text-dark"><?= html_escape($p['jab_fungsional']) ?></span>
    <?php endif; ?>
    <span class="ms-3 opacity-75 small">Periode: <?= $bln_names[$bm].' '.$tahun ?> – <?= $bln_names[$ba].' '.$tahun ?></span>
  </div>
</div>

<!-- TABEL DETAIL PER BULAN PER REKENING -->
<div class="card shadow-sm">
  <div class="card-body p-0">
  <div class="table-responsive">
  <table class="table table-bordered table-sm tbl-det mb-0">
    <thead>
      <tr>
        <th class="align-middle" style="min-width:220px">Komponen / Rekening</th>
        <?php foreach ($detail_months as $dm): if ($dm['pensiun']) continue; ?>
        <th class="text-center <?= $dm['is_ke'] ? 'ke-row' : '' ?>" style="min-width:110px">
          <?= $bln_names[$dm['bulan']] ?><br>
          <small><?= $dm['tahun'] ?></small>
          <?php if ($dm['is_ke']): ?><br><span class="badge bg-warning text-dark" style="font-size:.65rem">Ke-<?= $dm['is_ke'] ?></span><?php endif; ?>
        </th>
        <?php endforeach; ?>
        <th class="text-center" style="min-width:120px; background:#ede9fe">Total</th>
      </tr>
    </thead>
    <tbody>
    <?php
    // Helper: total kolom
    $active_months = array_filter($detail_months, fn($dm) => !$dm['pensiun']);
    function col_total($field, $months) {
      return array_sum(array_map(fn($dm) => $dm['hitung']['komponen'][$field] ?? ($dm[$field] ?? 0), $months));
    }
    function col_total_key($field, $months) {
      return array_sum(array_map(fn($dm) => $dm[$field] ?? 0, $months));
    }
    ?>

    <!-- GAJI POKOK -->
    <tr><th colspan="<?= count($active_months)+2 ?>" class="text-center" style="background:#dbeafe; color:#1e40af; font-size:.73rem; padding:3px">KOMPONEN GAJI (Rekening 5.1.01.01.xxx)</th></tr>

    <?php
    $komponen_rows = [
      ['key'=>'gaji_pokok',  'label'=>'Gaji Pokok', 'rek'=>'5.1.01.01.001'],
      ['key'=>'t_istri',     'label'=>'Tunjangan Istri/Suami (10%)', 'rek'=>'5.1.01.01.002'],
      ['key'=>'t_anak',      'label'=>'Tunjangan Anak (2%/anak)', 'rek'=>'5.1.01.01.002'],
      ['key'=>'t_jabatan',   'label'=>'Tunjangan Jabatan', 'rek'=>'5.1.01.01.003–005'],
      ['key'=>'t_pangan',    'label'=>'Tunjangan Pangan', 'rek'=>'5.1.01.01.006'],
    ];
    foreach ($komponen_rows as $kr):
      $grand_k = 0;
    ?>
    <tr>
      <td><span class="rekening-badge"><?= $kr['rek'] ?></span> <?= $kr['label'] ?></td>
      <?php foreach ($active_months as $dm):
        $v = $dm['hitung']['komponen'][$kr['key']] ?? 0;
        $grand_k += $v;
      ?>
      <td class="num"><?= $v ? number_format($v) : '—' ?></td>
      <?php endforeach; ?>
      <td class="num fw-semibold"><?= $grand_k ? number_format($grand_k) : '—' ?></td>
    </tr>
    <?php endforeach; ?>

    <!-- Bruto Gaji -->
    <tr class="sub-total">
      <td><strong>Sub-total Bruto Gaji</strong></td>
      <?php $grand_bruto=0; foreach ($active_months as $dm):
        $v = $dm['hitung']['komponen']['gaji_pokok']
           + $dm['hitung']['komponen']['t_istri']
           + $dm['hitung']['komponen']['t_anak']
           + $dm['hitung']['komponen']['t_jabatan']
           + $dm['hitung']['komponen']['t_khusus']
           + $dm['hitung']['komponen']['t_pangan'];
        $grand_bruto += $v;
      ?><td class="num"><?= number_format($v) ?></td><?php endforeach; ?>
      <td class="num"><?= number_format($grand_bruto) ?></td>
    </tr>

    <!-- Potongan -->
    <tr><th colspan="<?= count($active_months)+2 ?>" class="text-center" style="background:#fce7f3; color:#9d174d; font-size:.73rem; padding:3px">POTONGAN GAJI</th></tr>
    <?php
    $potongan_rows = [
      ['label'=>'BPJS Kesehatan Pegawai (1%)', 'rek'=>'5.1.01.01.009', 'src'=>'bpjs_kes_pegawai', 'from'=>'iuran'],
      ['label'=>'Iuran Pensiun/JHT (PNS 4.75% / PPPK JHT+JP)', 'rek'=>'5.1.01.01.013', 'src'=>'pensiun_jht', 'from'=>'calc'],
      ['label'=>'PPh 21 (Ditanggung Pemerintah)', 'rek'=>'—', 'src'=>'pph21', 'from'=>'belanja'],
    ];
    foreach ($potongan_rows as $kr): $grand_k=0; ?>
    <tr>
      <td><span class="rekening-badge"><?= $kr['rek'] ?></span> <?= $kr['label'] ?></td>
      <?php foreach ($active_months as $dm):
        if ($kr['from'] === 'iuran') $v = $dm['hitung']['iuran'][$kr['src']] ?? 0;
        elseif ($kr['from'] === 'belanja') $v = $dm['hitung']['belanja'][$kr['src']] ?? 0;
        else $v = ($dm['hitung']['iuran']['pensiun_pegawai'] ?? 0) + ($dm['hitung']['iuran']['jht'] ?? 0) + ($dm['hitung']['iuran']['jp'] ?? 0);
        $grand_k += $v;
      ?><td class="num neg"><?= $v ? number_format($v) : '—' ?></td><?php endforeach; ?>
      <td class="num neg fw-semibold"><?= $grand_k ? number_format($grand_k) : '—' ?></td>
    </tr>
    <?php endforeach; ?>

    <!-- Bersih Gaji -->
    <tr class="bersih">
      <td><strong>BERSIH GAJI (rekening gaji)</strong></td>
      <?php $grand_bg=0; foreach ($active_months as $dm):
        $v = $dm['bersih_gaji'] ?? 0; $grand_bg += $v;
      ?><td class="num"><?= number_format($v) ?></td><?php endforeach; ?>
      <td class="num"><?= number_format($grand_bg) ?></td>
    </tr>

    <!-- TPP -->
    <tr><th colspan="<?= count($active_months)+2 ?>" class="text-center" style="background:#dcfce7; color:#14532d; font-size:.73rem; padding:3px">TAMBAHAN PENGHASILAN PEGAWAI — TPP (Rekening 5.1.01.02.001)</th></tr>

    <tr class="tpp-hdr">
      <td><span class="rekening-badge">5.1.01.02.001</span> TPP Bruto
        <?php
        $tpp_rate_info = '';
        foreach($active_months as $dm) { $g=$dm['hitung']['pegawai']['golongan']??''; break; }
        $tpp_rate_info = (strpos($g,'IV')===0)?'Pajak 15% (Gol IV)':'Pajak 5%';
        ?>
        <small class="text-muted ms-2">(<?= $tpp_rate_info ?>)</small>
      </td>
      <?php $grand_tpp=0; foreach ($active_months as $dm):
        $v=$dm['hitung']['komponen']['tpp']??0; $grand_tpp+=$v;
      ?><td class="num"><?= $v ? number_format($v) : '—' ?></td><?php endforeach; ?>
      <td class="num fw-semibold"><?= number_format($grand_tpp) ?></td>
    </tr>

    <tr>
      <td><span class="rekening-badge">5.1.01.01.009</span> BPJS Kesehatan TPP — Pegawai (1%)</td>
      <?php $grand_btp=0; foreach ($active_months as $dm):
        $v=$dm['bpjs_tpp_peg']??0; $grand_btp+=$v;
      ?><td class="num neg"><?= $v ? number_format($v) : '—' ?></td><?php endforeach; ?>
      <td class="num neg fw-semibold"><?= $grand_btp ? number_format($grand_btp) : '—' ?></td>
    </tr>
    <tr>
      <td><span class="rekening-badge">5.1.01.01.007</span> Pajak TPP (5% / 15%) — Ditanggung Negara <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem">DTP</span></td>
      <?php $grand_pt=0; foreach ($active_months as $dm):
        $v=$dm['pajak_tpp']??0; $grand_pt+=$v;
      ?><td class="num text-muted"><?= $v ? number_format($v) : '—' ?></td><?php endforeach; ?>
      <td class="num text-muted fw-semibold"><?= $grand_pt ? number_format($grand_pt) : '—' ?></td>
    </tr>

    <tr class="tpp-bersih">
      <td><strong>BERSIH TPP</strong></td>
      <?php $grand_tb=0; foreach ($active_months as $dm):
        $v=$dm['tpp_bersih']??0; $grand_tb+=$v;
      ?><td class="num"><?= $v ? number_format($v) : '—' ?></td><?php endforeach; ?>
      <td class="num"><?= number_format($grand_tb) ?></td>
    </tr>

    <!-- Grand Total -->
    <tr class="grand-total">
      <td><strong>TOTAL BERSIH TERIMA</strong></td>
      <?php $grand_tot=0; foreach ($active_months as $dm):
        $v=$dm['bersih_total']??0; $grand_tot+=$v;
      ?><td class="num"><?= number_format($v) ?></td><?php endforeach; ?>
      <td class="num"><?= number_format($grand_tot) ?></td>
    </tr>

    <!-- Belanja Pemerintah -->
    <tr><th colspan="<?= count($active_months)+2 ?>" class="text-center" style="background:#fef3c7; color:#92400e; font-size:.73rem; padding:3px">BELANJA PEMERINTAH (iuran employer)</th></tr>
    <?php
    $belanja_rows = [
      ['label'=>'BPJS Kes Employer (4% dari gaji)', 'src'=>'bpjs_kes_employer'],
      ['label'=>'BPJS Kes TPP (4% dari TPP)',       'src'=>'bpjs_tpp'],
      ['label'=>'JKK (0.24%) + JKM (0.30%)',        'src'=>'jkk_jkm'],
    ];
    foreach ($belanja_rows as $kr): $grand_k=0; ?>
    <tr class="belanja-row">
      <td><?= $kr['label'] ?></td>
      <?php foreach ($active_months as $dm):
        if ($kr['src']==='jkk_jkm') $v=($dm['hitung']['belanja']['jkk']??0)+($dm['hitung']['belanja']['jkm']??0);
        else $v=$dm['hitung']['belanja'][$kr['src']]??0;
        $grand_k+=$v;
      ?><td class="num"><?= $v ? number_format($v) : '—' ?></td><?php endforeach; ?>
      <td class="num fw-semibold"><?= number_format($grand_k) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  </div>
</div>

<?php if (count(array_filter($detail_months, fn($dm) => $dm['pensiun']))): ?>
<div class="alert alert-warning mt-3">
  <i class="fa-solid fa-triangle-exclamation me-1"></i>
  Bulan yang tidak dihitung karena sudah BUP: <?= implode(', ', array_map(function($dm) use ($bln_names) {
    return $bln_names[$dm['bulan']].' '.$dm['tahun'];
  }, array_filter($detail_months, fn($dm) => $dm['pensiun']))) ?>
</div>
<?php endif; ?>
