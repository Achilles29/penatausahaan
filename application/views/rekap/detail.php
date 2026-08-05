<?php defined('BASEPATH') OR exit('No direct script access allowed');
$bln_names = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$p = $peg_info;
$rek_sfx = ($p['jenis'] === 'PNS') ? '.00001' : '.00002';
?>
<style>
.det-hdr { background:linear-gradient(135deg,#1d4ed8,#3b82f6); color:#fff; border-radius:10px 10px 0 0; }
.tbl-det { font-size:.78rem; }
.tbl-det th { font-size:.72rem; white-space:nowrap; background:#f1f5f9; }
.tbl-det td { white-space:nowrap; vertical-align:middle; }
.tbl-det td.num { text-align:right; font-variant-numeric:tabular-nums; }
.tbl-det tr.ke-row { background:#fef9c3; }
.tbl-det tr.seksion th { background:#1d4ed8; color:#fff; }
.tbl-det tr.sub-total td { background:#dbeafe; font-weight:700; color:#1e3a5f; }
.tbl-det tr.bersih td { background:#d1fae5; font-weight:700; color:#065f46; }
.tbl-det tr.tpp-hdr td { background:#dcfce7; font-weight:600; color:#14532d; }
.tbl-det tr.tpp-bersih td { background:#bbf7d0; font-weight:700; color:#14532d; }
.tbl-det tr.grand-total td { background:#ede9fe; font-weight:700; color:#4c1d95; font-size:.84rem; }
.tbl-det tr.dtp-row td { background:#fef9c3; }
.tbl-det tr.pot-row td { background:#fce7f3; }
.tbl-det td.neg { color:#b91c1c; }
.tbl-det th.kpp-col { background:#fee2e2 !important; color:#991b1b; }
.tbl-det td.kpp-col { background:#fff5f5 !important; }
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
        <th class="align-middle" style="min-width:280px">Komponen / Rekening</th>
        <?php foreach ($detail_months as $dm): ?>
        <th class="text-center <?= !empty($dm['pensiun']) ? 'kpp-col' : ($dm['is_ke'] ? 'ke-row' : '') ?>" style="min-width:110px">
          <?= $bln_names[$dm['bulan']] ?><br>
          <small><?= $dm['tahun'] ?></small>
          <?php if ($dm['is_ke']): ?><br><span class="badge bg-warning text-dark" style="font-size:.65rem">Ke-<?= $dm['is_ke'] ?></span><?php endif; ?>
          <?php if (!empty($dm['pensiun'])): ?><br><span class="badge bg-danger text-white" style="font-size:.65rem">KPP</span><?php endif; ?>
        </th>
        <?php endforeach; ?>
        <th class="text-center" style="min-width:120px; background:#ede9fe">Total</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $active_months = $detail_months; // includes KPP pensiun month (shown with red badge)
    $ncols = count($active_months);

    // Helper: render a data row
    function det_row($label, $months, $cb, $cls='', $neg=false) {
        $grand = 0;
        $cells = '';
        foreach ($months as $dm) {
            $v = $cb($dm);
            $grand += $v;
            $keCls  = $dm['is_ke']           ? ' mat-ke'  : '';
            $kppCls = !empty($dm['pensiun']) ? ' kpp-col' : '';
            $cells .= '<td class="num'.($neg?' neg':'').$keCls.$kppCls.'">'.($v ? number_format($v) : '—').'</td>';
        }
        $rowCls = $cls ? ' class="'.$cls.'"' : '';
        return '<tr'.$rowCls.'><td>'.$label.'</td>'.$cells
              .'<td class="num fw-semibold'.($neg?' neg':'').'">'.($grand ? number_format($grand) : '—').'</td></tr>';
    }
    function det_section($label, $ncols, $bg='#dbeafe', $color='#1e40af') {
        return '<tr><th colspan="'.($ncols+2).'" class="text-center" style="background:'.$bg.'; color:'.$color.'; font-size:.73rem; padding:3px">'.$label.'</th></tr>';
    }
    ?>

    <!-- ══ KOMPONEN GAJI (001–012) ══ -->
    <?= det_section('KOMPONEN GAJI (Rekening 5.1.01.01.001–012'.$rek_sfx.')', $ncols, '#dbeafe', '#1e40af') ?>

    <?php
    $jl = $p['jenis']; // 'PNS' atau 'PPPK'
    $k_rows = [
      ['5.1.01.01.001', 'Belanja Gaji Pokok '.$jl,                                    fn($dm) => $dm['hitung']['komponen']['gaji_pokok']    ?? 0],
      ['5.1.01.01.002', 'Belanja Tunjangan Keluarga '.$jl.' — Istri/Suami (10%)',     fn($dm) => $dm['hitung']['komponen']['t_istri']       ?? 0],
      ['5.1.01.01.002', 'Belanja Tunjangan Keluarga '.$jl.' — Anak (2%/anak)',        fn($dm) => $dm['hitung']['komponen']['t_anak']        ?? 0],
      ['5.1.01.01.003', 'Belanja Tunjangan Jabatan '.$jl.' (Struktural)',              fn($dm) => $dm['hitung']['komponen']['t_jabatan_str']  ?? 0],
      ['5.1.01.01.004', 'Belanja Tunjangan Fungsional '.$jl,                           fn($dm) => $dm['hitung']['komponen']['t_jabatan_fung'] ?? 0],
      ['5.1.01.01.005', 'Belanja Tunjangan Fungsional Umum '.$jl,                     fn($dm) => $dm['hitung']['komponen']['t_jabatan_umum'] ?? 0],
      ['5.1.01.01.006', 'Belanja Tunjangan Beras '.$jl,                               fn($dm) => $dm['hitung']['komponen']['t_pangan']      ?? 0],
      // 007 = t_khusus + PPh DTP (total kewajiban rekening 007)
      ['5.1.01.01.007', 'Belanja Tunjangan PPh/Tunjangan Khusus '.$jl,
        fn($dm) => ($dm['hitung']['komponen']['t_khusus']??0)+($dm['hitung']['belanja']['pph21']??0)],
      ['5.1.01.01.008', 'Belanja Pembulatan Gaji '.$jl,                               fn($dm) => $dm['hitung']['komponen']['t_pembulatan']  ?? 0],
      ['5.1.01.01.009', 'Belanja Iuran Jaminan Kesehatan '.$jl,                       fn($dm) => $dm['hitung']['belanja']['bpjs_kes_employer'] ?? 0],
      ['5.1.01.01.010', 'Belanja Iuran Jaminan Kecelakaan Kerja '.$jl,               fn($dm) => $dm['hitung']['belanja']['jkk'] ?? 0],
      ['5.1.01.01.011', 'Belanja Iuran Jaminan Kematian '.$jl,                       fn($dm) => $dm['hitung']['belanja']['jkm'] ?? 0],
      ['5.1.01.01.012', 'Belanja Iuran Simpanan Peserta Tapera '.$jl,                fn($dm) => 0],
    ];
    foreach ($k_rows as [$rek, $lbl, $cb]):
        $lbl_html = '<span class="rekening-badge">'.$rek.$rek_sfx.'</span> '.$lbl;
        echo det_row($lbl_html, $active_months, $cb);
    endforeach;
    ?>

    <!-- Gaji Bruto subtotal — komponen + DTP (PPh + BPJS Empl + JKK + JKM) -->
    <tr class="sub-total">
      <td><strong>Total Bruto Gaji</strong></td>
      <?php $grand_bruto=0; foreach ($active_months as $dm):
        $k = $dm['hitung']['komponen'];
        $b = $dm['hitung']['belanja'];
        $v = ($k['gaji_pokok']??0)+($k['t_istri']??0)+($k['t_anak']??0)
            +($k['t_jabatan_str']??0)+($k['t_jabatan_fung']??0)+($k['t_jabatan_umum']??0)
            +($k['t_khusus']??0)+($k['t_pangan']??0)+($k['t_pembulatan']??0)
            +($b['pph21']??0)+($b['bpjs_kes_employer']??0)+($b['jkk']??0)+($b['jkm']??0);
        $grand_bruto += $v;
        $kppCls = !empty($dm['pensiun']) ? ' kpp-col' : '';
      ?><td class="num<?= $kppCls ?>"><?= number_format($v) ?></td><?php endforeach; ?>
      <td class="num"><?= number_format($grand_bruto) ?></td>
    </tr>

    <!-- ══ POTONGAN & PENYETORAN ══ -->
    <?= det_section('POTONGAN & PENYETORAN (Dipotong dari Gaji + Disetor ke Pihak Ketiga)', $ncols, '#fce7f3', '#9d174d') ?>

    <?php
    $pot_rows = [
      ['5.1.01.01.009', 'BPJS Kesehatan Gaji '.$jl.' — Pegawai (1%) [dipotong]',
        fn($dm) => $dm['hitung']['iuran']['bpjs_kes_pegawai'] ?? 0],
      ['5.1.01.01.013', 'Taspen — Iuran Pensiun '.$jl.' (4,75%) [dipotong]',
        fn($dm) => $dm['hitung']['iuran']['pensiun_pegawai'] ?? 0],
      ['5.1.01.01.013', 'Taspen — JHT '.$jl.' (3,25%) [dipotong]',
        fn($dm) => $dm['hitung']['iuran']['jht_taspen'] ?? 0],
      ['5.1.01.01.007', 'Tunjangan PPh 21 '.$jl.' — DTP (disetor ke KPP)',
        fn($dm) => $dm['hitung']['belanja']['pph21'] ?? 0],
      ['5.1.01.01.009', 'Belanja Iuran Jaminan Kesehatan '.$jl.' — Pemberi Kerja (4%) (disetor ke BPJS)',
        fn($dm) => $dm['hitung']['belanja']['bpjs_kes_employer'] ?? 0],
      ['5.1.01.01.010', 'Belanja Iuran Jaminan Kecelakaan Kerja '.$jl.' (disetor)',
        fn($dm) => $dm['hitung']['belanja']['jkk'] ?? 0],
      ['5.1.01.01.011', 'Belanja Iuran Jaminan Kematian '.$jl.' (disetor)',
        fn($dm) => $dm['hitung']['belanja']['jkm'] ?? 0],
    ];
    foreach ($pot_rows as [$rek, $lbl, $cb]):
        $lbl_html = '<span class="rekening-badge">'.$rek.$rek_sfx.'</span> '.$lbl;
        echo det_row($lbl_html, $active_months, $cb, 'pot-row');
    endforeach;
    ?>

    <!-- Total Potongan & Penyetoran -->
    <tr style="background:#fce7f3;color:#9d174d;font-weight:700">
      <td><strong>Total Potongan &amp; Penyetoran</strong></td>
      <?php $grand_pot=0; foreach ($active_months as $dm):
        $iu = $dm['hitung']['iuran'];
        $b  = $dm['hitung']['belanja'];
        $v  = ($iu['bpjs_kes_pegawai']??0)
             +($iu['pensiun_pegawai']??0)+($iu['jht_taspen']??0)+($iu['jht']??0)+($iu['jp']??0)
             +($b['pph21']??0)+($b['bpjs_kes_employer']??0)+($b['jkk']??0)+($b['jkm']??0);
        $grand_pot += $v;
        $kppCls = !empty($dm['pensiun']) ? ' kpp-col' : '';
      ?><td class="num<?= $kppCls ?>"><?= number_format($v) ?></td><?php endforeach; ?>
      <td class="num"><?= number_format($grand_pot) ?></td>
    </tr>

    <!-- Bersih Gaji -->
    <tr class="bersih">
      <td><strong>Bersih Gaji (Diterima Pegawai)</strong></td>
      <?php $grand_bg=0; foreach ($active_months as $dm):
        $v = $dm['bersih_gaji'] ?? 0; $grand_bg += $v;
        $kppCls = !empty($dm['pensiun']) ? ' kpp-col' : '';
      ?><td class="num<?= $kppCls ?>"><?= number_format($v) ?></td><?php endforeach; ?>
      <td class="num"><?= number_format($grand_bg) ?></td>
    </tr>

    <!-- ══ TPP ══ -->
    <?= det_section('TAMBAHAN PENGHASILAN PEGAWAI — TPP (Rekening 5.1.01.02.001'.$rek_sfx.')', $ncols, '#dcfce7', '#14532d') ?>

    <?php
    foreach ($active_months as $dm) { $g = $dm['hitung']['pegawai']['golongan'] ?? ''; break; }
    $tpp_rate_info = (strpos($g, 'IV') === 0) ? 'Pajak flat 15% (Gol IV)' : 'Pajak flat 5%';
    ?>
    <tr class="tpp-hdr">
      <td><span class="rekening-badge">5.1.01.02.001<?= $rek_sfx ?></span>
        Belanja Tambahan Penghasilan berdasarkan Beban Kerja <?= html_escape($jl) ?>
        <small class="text-muted ms-2">(<?= $tpp_rate_info ?>)</small>
      </td>
      <?php $grand_tpp=0; foreach ($active_months as $dm):
        $v = $dm['hitung']['komponen']['tpp'] ?? 0; $grand_tpp += $v;
        $kppCls = !empty($dm['pensiun']) ? ' kpp-col' : '';
      ?><td class="num<?= $kppCls ?>"><?= $v ? number_format($v) : '—' ?></td><?php endforeach; ?>
      <td class="num fw-semibold"><?= number_format($grand_tpp) ?></td>
    </tr>

    <!-- PPh TPP DTP -->
    <?php
    echo det_row('<span class="rekening-badge">5.1.01.01.007'.$rek_sfx.'</span> Belanja Tunjangan PPh/Tunjangan Khusus '.html_escape($jl).' — TPP (DTP) <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">DTP</span>',
        $active_months, fn($dm) => $dm['pajak_tpp'] ?? 0, 'dtp-row');
    echo det_row('<span class="rekening-badge">5.1.01.01.009'.$rek_sfx.'</span> Belanja Iuran Jaminan Kesehatan '.html_escape($jl).' — TPP Pemberi Kerja (4%) <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">DTP</span>',
        $active_months, fn($dm) => $dm['hitung']['belanja']['bpjs_tpp'] ?? 0, 'dtp-row');
    ?>

    <!-- Bruto Anggaran TPP subtotal -->
    <tr style="background:#fef9c3;color:#78350f;font-weight:700">
      <td><strong>Bruto Anggaran TPP</strong></td>
      <?php $grand_tpp_angg=0; foreach ($active_months as $dm):
        $v = ($dm['hitung']['komponen']['tpp']??0)
            +($dm['pajak_tpp']??0)
            +($dm['hitung']['belanja']['bpjs_tpp']??0);
        $grand_tpp_angg += $v;
        $kppCls = !empty($dm['pensiun']) ? ' kpp-col' : '';
      ?><td class="num<?= $kppCls ?>" <?= empty($dm['pensiun']) ? 'style="background:#fef9c3;color:#78350f"' : '' ?>><?= $v ? number_format($v) : '—' ?></td><?php endforeach; ?>
      <td class="num" style="background:#fef9c3;color:#78350f"><?= $grand_tpp_angg ? number_format($grand_tpp_angg) : '—' ?></td>
    </tr>

    <!-- BPJS TPP Pegawai (dipotong dari TPP) -->
    <?php
    echo det_row('<span class="rekening-badge">5.1.01.01.009'.$rek_sfx.'</span> Belanja Iuran Jaminan Kesehatan '.html_escape($jl).' — TPP Pegawai (1%) <em class="text-muted" style="font-size:.75rem">(dipotong dari TPP)</em>',
        $active_months, fn($dm) => $dm['bpjs_tpp_peg'] ?? 0, 'pot-row', true);
    ?>

    <tr class="tpp-bersih">
      <td><strong>Bersih TPP (Diterima Pegawai)</strong></td>
      <?php $grand_tb=0; foreach ($active_months as $dm):
        $v = $dm['tpp_bersih'] ?? 0; $grand_tb += $v;
        $kppCls = !empty($dm['pensiun']) ? ' kpp-col' : '';
      ?><td class="num<?= $kppCls ?>"><?= $v ? number_format($v) : '—' ?></td><?php endforeach; ?>
      <td class="num"><?= number_format($grand_tb) ?></td>
    </tr>

    <!-- Grand Total -->
    <tr class="grand-total">
      <td><strong>TOTAL BERSIH TERIMA (Gaji + TPP)</strong></td>
      <?php $grand_tot=0; foreach ($active_months as $dm):
        $v = $dm['bersih_total'] ?? 0; $grand_tot += $v;
        $kppCls = !empty($dm['pensiun']) ? ' kpp-col' : '';
      ?><td class="num<?= $kppCls ?>"><?= number_format($v) ?></td><?php endforeach; ?>
      <td class="num"><?= number_format($grand_tot) ?></td>
    </tr>

    </tbody>
  </table>
  </div>
  </div>
</div>

<?php
$kpp_months = array_values(array_filter($detail_months, fn($dm) => $dm['pensiun']));
if (count($kpp_months)):
    $km       = $kpp_months[0];
    $gol_asli = $km['hitung']['pegawai']['golongan_asli'] ?? '?';
    $gol_kpp  = $km['hitung']['pegawai']['golongan']      ?? '?';
    $bup_usia = $km['hitung']['pegawai']['bup']           ?? 58;
?>
<div class="alert alert-info mt-3 small">
  <i class="fa-solid fa-circle-info me-1"></i>
  <strong>Kenaikan Pangkat Pengabdian (KPP)</strong> —
  <?= html_escape($p['nama']) ?> mencapai BUP <?= $bup_usia ?> tahun pada
  <?= $bln_names[$km['bulan']].' '.$km['tahun'] ?>.
  Kolom <span class="badge bg-danger text-white">KPP</span> menunjukkan gaji bulan pensiun
  dengan pangkat lebih tinggi (<?= html_escape($gol_asli) ?> → <?= html_escape($gol_kpp) ?>)
  sesuai PP 11/2017 Ps.166. Tidak termasuk dalam total rekap OPD.
</div>
<?php endif; ?>
