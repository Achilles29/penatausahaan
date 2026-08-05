<?php defined('BASEPATH') OR exit('No direct script access allowed');
$bln_names = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
function rp($n){ return 'Rp '.number_format((int)$n,0,',','.'); }
$p = $params;

// Rekening Gaji (urut 001→013, suffix .00001/.00002 ditambah per tab)
$rek_gaji = [
    ['rek'=>'5.1.01.01.001', 'lbl'=>'Belanja Gaji Pokok ASN',                                         'key'=>'gaji_pokok',      'neg'=>false],
    ['rek'=>'5.1.01.01.002', 'lbl'=>'Belanja Tunjangan Keluarga ASN',                                 'key'=>'t_keluarga',      'neg'=>false],
    ['rek'=>'5.1.01.01.003', 'lbl'=>'Belanja Tunjangan Jabatan ASN',                                  'key'=>'t_jabatan_str',   'neg'=>false],
    ['rek'=>'5.1.01.01.004', 'lbl'=>'Belanja Tunjangan Fungsional ASN',                               'key'=>'t_jabatan_fung',  'neg'=>false],
    ['rek'=>'5.1.01.01.005', 'lbl'=>'Belanja Tunjangan Fungsional Umum ASN',                          'key'=>'t_jabatan_umum',  'neg'=>false],
    ['rek'=>'5.1.01.01.006', 'lbl'=>'Belanja Tunjangan Beras ASN',                                    'key'=>'t_pangan',        'neg'=>false],
    // 007 = t_khusus + PPh DTP (full rekening obligation)
    ['rek'=>'5.1.01.01.007', 'lbl'=>'Belanja Tunjangan PPh/Tunjangan Khusus ASN',
        'compute'=>function($d){ return ($d['t_khusus']??0)+($d['pph21']??0); },                                               'neg'=>false],
    ['rek'=>'5.1.01.01.008', 'lbl'=>'Belanja Pembulatan Gaji ASN',                                    'key'=>'t_pembulatan',    'neg'=>false],
    ['rek'=>'5.1.01.01.009', 'lbl'=>'Belanja Iuran Jaminan Kesehatan ASN',                            'key'=>'bel_bpjs_gaji',   'neg'=>false],
    ['rek'=>'5.1.01.01.010', 'lbl'=>'Belanja Iuran Jaminan Kecelakaan Kerja ASN',                     'key'=>'bel_jkk',         'neg'=>false],
    ['rek'=>'5.1.01.01.011', 'lbl'=>'Belanja Iuran Jaminan Kematian ASN',                             'key'=>'bel_jkm',         'neg'=>false],
    ['rek'=>'5.1.01.01.012', 'lbl'=>'Belanja Iuran Simpanan Peserta Tabungan Perumahan Rakyat ASN',   'key'=>'bel_tapera',      'neg'=>false],
    ['type'=>'subtotal',     'lbl'=>'Total Bruto Gaji',                                                'key'=>'bruto_gaji',      'rek'=>'—'],
    ['rek'=>'5.1.01.01.009', 'lbl'=>'BPJS Kesehatan Gaji ASN — Pegawai (1%)',                         'key'=>'pot_bpjs',        'neg'=>false],
    ['rek'=>'5.1.01.01.013', 'lbl'=>'Taspen — Iuran Pensiun ASN (4,75%)',                             'key'=>'pot_pensiun_peg', 'neg'=>false],
    ['rek'=>'5.1.01.01.013', 'lbl'=>'Taspen — JHT ASN (3,25%)',                                       'key'=>'pot_jht_taspen',  'neg'=>false],
    ['rek'=>'5.1.01.01.007', 'lbl'=>'Tunjangan PPh 21 ASN — Ditanggung Pemerintah (disetor ke KPP)', 'key'=>'pph21',           'neg'=>false],
    ['rek'=>'5.1.01.01.009', 'lbl'=>'BPJS Kesehatan ASN — Pemberi Kerja (4%) (disetor ke BPJS)',     'key'=>'bel_bpjs_gaji',   'neg'=>false],
    ['rek'=>'5.1.01.01.010', 'lbl'=>'Iuran JKK ASN — Pemberi Kerja (disetor)',                        'key'=>'bel_jkk',         'neg'=>false],
    ['rek'=>'5.1.01.01.011', 'lbl'=>'Iuran JKM ASN — Pemberi Kerja (disetor)',                        'key'=>'bel_jkm',         'neg'=>false],
    ['type'=>'subtotal-pot', 'lbl'=>'Total Potongan & Penyetoran', 'rek'=>'—',
        'compute'=>function($d){
            return ($d['pot_bpjs']??0)+($d['pot_pensiun_peg']??0)+($d['pot_jht_taspen']??0)
                  +($d['pph21']??0)+($d['bel_bpjs_gaji']??0)+($d['bel_jkk']??0)+($d['bel_jkm']??0); }],
    ['type'=>'bersih',       'lbl'=>'Gaji Bersih (Diterima Pegawai)',                                  'key'=>'bersih_gaji',     'rek'=>'—'],
];

// Rekening TPP (urut: TPP → PPh DTP → BPJS empl DTP → bruto anggaran → BPJS peg → bersih)
$rek_tpp = [
    ['rek'=>'5.1.01.02.001', 'lbl'=>'Belanja Tambahan Penghasilan berdasarkan Beban Kerja ASN',         'key'=>'tpp_bruto',    'neg'=>false],
    ['rek'=>'5.1.01.01.007', 'lbl'=>'Belanja Tunjangan PPh/Tunjangan Khusus ASN — TPP (DTP)',           'key'=>'pajak_tpp',    'neg'=>false],
    ['rek'=>'5.1.01.01.009', 'lbl'=>'Belanja Iuran Jaminan Kesehatan ASN — TPP Pemberi Kerja (4%)',     'key'=>'bel_tpp_bpjs', 'neg'=>false],
    ['type'=>'subtotal', 'lbl'=>'Bruto Anggaran TPP', 'rek'=>'—',
        'compute'=>function($d){ return ($d['tpp_bruto']??0)+($d['pajak_tpp']??0)+($d['bel_tpp_bpjs']??0); }],
    ['rek'=>'5.1.01.01.009', 'lbl'=>'Belanja Iuran Jaminan Kesehatan ASN — TPP Pegawai (1%) [dipotong]','key'=>'bpjs_tpp_peg', 'neg'=>false],
    ['type'=>'bersih',       'lbl'=>'Bersih TPP (Diterima Pegawai)',                                      'key'=>'tpp_bersih',   'rek'=>'—'],
];

function rekap_mat_table($tbl_id, $rek_def, $months, $grand_jenis, $bln_names, $jenis_key = 'pns', $rek_suffix = '.00001', $jenis_lbl = 'PNS') {
    echo '<div class="table-responsive"><table class="table table-bordered table-sm mat-tbl mb-0" id="'.$tbl_id.'">';
    // Header
    echo '<thead><tr>';
    echo '<th class="lbl-col" style="min-width:130px">Rekening</th>';
    echo '<th class="lbl-col" style="min-width:220px">Komponen</th>';
    foreach ($months as $md) {
        $cls = $md['is_ke'] ? ' class="ke-col"' : '';
        echo '<th'.$cls.' style="min-width:110px;text-align:right">';
        if ($md['is_ke']) {
            echo html_escape($md['ke_nama']);
            echo '<div class="fw-normal" style="font-size:.68rem;color:#7a5d00">'.$bln_names[$md['bulan']].' '.$md['tahun'].'</div>';
        } else {
            echo $bln_names[$md['bulan']];
            echo '<div class="fw-normal text-muted" style="font-size:.68rem">'.$md['tahun'].'</div>';
        }
        echo '</th>';
    }
    echo '<th style="min-width:120px;text-align:right;background:#dbeafe!important;color:#1e3a5f">TOTAL</th>';
    echo '</tr></thead>';
    // Body
    echo '<tbody>';
    foreach ($rek_def as $r) {
        $type = $r['type'] ?? '';
        if ($type === 'section') {
            $ncols_total = count($months) + 3;
            echo '<tr><th colspan="'.$ncols_total.'" style="background:#e8eaf6;color:#3730a3;font-size:.72rem;padding:5px 8px;letter-spacing:.06em">'.strtoupper(html_escape($r['lbl'])).'</th></tr>';
            continue;
        }
        if ($type === 'subtotal' || $type === 'subtotal-pot' || $type === 'bersih') {
            if ($type === 'subtotal')     $rowBg = 'background:#fef9c3;color:#78350f';
            elseif ($type === 'subtotal-pot') $rowBg = 'background:#fce7f3;color:#9d174d';
            else                          $rowBg = 'background:#d1fae5;color:#065f46';
            $compute = $r['compute'] ?? null;
            $grandVal = $compute ? (int)$compute($grand_jenis) : (int)($grand_jenis[$r['key']] ?? 0);
            echo '<tr style="'.$rowBg.';font-weight:700">';
            echo '<td style="'.$rowBg.'">—</td>';
            echo '<td style="'.$rowBg.'">'.html_escape(str_replace('ASN', $jenis_lbl, $r['lbl'])).'</td>';
            foreach ($months as $md) {
                $val = $compute ? (int)$compute($md[$jenis_key]) : (int)($md[$jenis_key][$r['key']] ?? 0);
                $keCls = $md['is_ke'] ? ' mat-ke' : '';
                $display = $val ? number_format($val) : '—';
                echo '<td class="num'.$keCls.'" style="'.$rowBg.'">'.$display.'</td>';
            }
            $display = $grandVal ? number_format($grandVal) : '—';
            echo '<td class="num total-col" style="'.$rowBg.'">'.$display.'</td>';
            echo '</tr>';
            continue;
        }
        $compute = $r['compute'] ?? null;
        $neg = !empty($r['neg']);
        $grandVal = $compute ? (int)$compute($grand_jenis) : (int)($grand_jenis[$r['key']] ?? 0);
        $negCls = $neg ? ' neg' : '';
        echo '<tr>';
        echo '<td><span class="rek-badge">'.html_escape($r['rek'].$rek_suffix).'</span></td>';
        echo '<td>'.html_escape(str_replace('ASN', $jenis_lbl, $r['lbl'])).'</td>';
        foreach ($months as $md) {
            $val = $compute ? (int)$compute($md[$jenis_key]) : (int)($md[$jenis_key][$r['key']] ?? 0);
            $keCls = $md['is_ke'] ? ' mat-ke' : '';
            $display = $val ? number_format($val) : '—';
            echo '<td class="num'.$negCls.$keCls.'">'.$display.'</td>';
        }
        $display = $grandVal ? number_format($grandVal) : '—';
        echo '<td class="num total-col'.$negCls.'">'.$display.'</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    // Footer: jumlah pegawai
    echo '<tfoot><tr style="background:#f8fafc;font-weight:600;font-size:.72rem;color:#475569">';
    echo '<td colspan="2">Jumlah Pegawai (orang)</td>';
    foreach ($months as $md) {
        echo '<td class="num">'.(int)($md[$jenis_key]['jml'] ?? 0).'</td>';
    }
    echo '<td class="num">'.(int)($grand_jenis['jml'] ?? 0).'</td>';
    echo '</tr></tfoot>';
    echo '</table></div>';
}
?>
<style>
.rekap-hdr { background:linear-gradient(135deg,#0f4c81,#1d6fa4); color:#fff; border-radius:10px 10px 0 0; }
.sum-chip { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 14px; text-align:center; }
.sum-chip .lbl { font-size:.7rem; color:#64748b; text-transform:uppercase; letter-spacing:.04em; }
.sum-chip .val { font-size:1rem; font-weight:700; color:#0f172a; }
.sum-chip .val.blue { color:#0f4c81; }
.sum-chip .val.green { color:#15803d; }
.peg-tbl { font-size:.78rem; }
.peg-tbl th { font-size:.72rem; background:#f8fafc; white-space:nowrap; }
.peg-tbl td { vertical-align:middle; white-space:nowrap; }
.mat-tbl { font-size:.77rem; }
.mat-tbl th { font-size:.72rem; white-space:nowrap; background:#f1f5f9; vertical-align:middle; text-align:right; }
.mat-tbl th.lbl-col { text-align:left; }
.mat-tbl td { white-space:nowrap; vertical-align:middle; }
.mat-tbl td.num { text-align:right; font-variant-numeric:tabular-nums; padding-right:10px; }
.mat-tbl td.neg { color:#b91c1c; }
.mat-tbl tr.mat-ke td { background:#fef9c3; }
.mat-tbl td.total-col { background:#e8f4fd; font-weight:700; }
.mat-tbl td.total-col.neg { color:#b91c1c; }
.mat-tbl tr.row-subtotal td { background:#dbeafe; font-weight:700; color:#1e3a5f; }
.mat-tbl tr.row-bersih td { background:#d1fae5; font-weight:700; color:#065f46; }
.rek-badge { font-size:.65rem; padding:1px 5px; background:#e8eaf6; color:#3949ab; border-radius:3px; font-family:monospace; }
th.ke-col { background:#fef9c3 !important; color:#7a5d00; }
</style>

<!-- FORM -->
<div class="card mb-4 shadow-sm">
  <div class="card-body rekap-hdr py-3 px-4">
    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-calculator me-2"></i>Rekap Gaji ASN — Range Tahunan</h5>
    <small class="opacity-80">Hitung akumulasi gaji PNS/PPPK per rekening, termasuk Gaji ke-13 dan ke-14</small>
  </div>
  <div class="card-body py-3 border-bottom">
    <form method="post" action="<?= site_url('rekap') ?>" class="row g-2 align-items-end">
      <?php if ($is_super): ?>
      <div class="col-md-4">
        <label class="form-label small mb-1">OPD <span class="text-muted fw-normal">(kosong = semua OPD)</span></label>
        <select class="form-select form-select-sm" name="opd_id">
          <option value="">— Semua OPD —</option>
          <?php foreach ($opd_list as $k => $v): ?>
          <option value="<?= $k ?>" <?= ($p && $p['opd_id']==$k)?'selected':'' ?>><?= html_escape($v) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php else: ?>
      <input type="hidden" name="opd_id" value="<?= $default_opd ?>">
      <div class="col-md-4"><label class="form-label small mb-1">OPD</label>
        <div class="form-control form-control-sm bg-light"><?= html_escape($opd_list[$default_opd] ?? 'OPD Anda') ?></div>
      </div>
      <?php endif; ?>

      <div class="col-md-1">
        <label class="form-label small mb-1">Tahun</label>
        <input type="number" class="form-control form-control-sm" name="tahun" min="2024" max="2099"
          value="<?= $p ? $p['tahun'] : date('Y') ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Bulan Mulai</label>
        <select class="form-select form-select-sm" name="bulan_mulai">
          <?php for($i=1;$i<=12;$i++): ?>
          <option value="<?= $i ?>" <?= ($p && $p['bm']==$i)?'selected':($p?'':($i==1?'selected':'')); ?>>
            <?= $bln_names[$i] ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Bulan Akhir</label>
        <select class="form-select form-select-sm" name="bulan_akhir">
          <?php for($i=1;$i<=12;$i++): ?>
          <option value="<?= $i ?>" <?= ($p && $p['ba']==$i)?'selected':($p?'':($i==12?'selected':'')); ?>>
            <?= $bln_names[$i] ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-1 d-flex align-items-end pb-1">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="include_ke" id="chk_ke" value="1"
            <?= (!$p || $p['include_ke'])?'checked':'' ?>>
          <label class="form-check-label small" for="chk_ke">+Ke-13/14</label>
        </div>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="fa-solid fa-play me-1"></i>Hitung
        </button>
      </div>
    </form>
  </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><?= html_escape($error) ?></div>
<?php endif; ?>

<?php if ($result): ?>
<?php
$grand    = $result['grand'];
$months   = $result['months'];
$peg_rows = $result['peg_rows'];
$gPNS     = $grand['pns']      ?? [];
$gPPPK    = $grand['pppk']     ?? [];
$gAll     = $grand['combined'] ?? [];
$uniq_pns  = count(array_filter($peg_rows, fn($r) => $r['jenis'] === 'PNS'));
$uniq_pppk = count(array_filter($peg_rows, fn($r) => $r['jenis'] === 'PPPK'));
?>

<!-- SUMMARY -->
<div class="row g-2 mb-3">
  <div class="col-6 col-md-auto flex-grow-1">
    <div class="sum-chip"><div class="lbl">PNS</div><div class="val"><?= $uniq_pns ?> orang</div></div>
  </div>
  <div class="col-6 col-md-auto flex-grow-1">
    <div class="sum-chip"><div class="lbl">PPPK</div><div class="val"><?= $uniq_pppk ?> orang</div></div>
  </div>
  <div class="col-6 col-md-auto flex-grow-1">
    <div class="sum-chip"><div class="lbl">Bersih Gaji</div><div class="val blue"><?= rp($gAll['bersih_gaji'] ?? 0) ?></div></div>
  </div>
  <div class="col-6 col-md-auto flex-grow-1">
    <div class="sum-chip"><div class="lbl">TPP Bersih</div><div class="val green"><?= rp($gAll['tpp_bersih'] ?? 0) ?></div></div>
  </div>
  <div class="col-6 col-md-auto flex-grow-1">
    <div class="sum-chip"><div class="lbl">Total THP</div><div class="val"><?= rp($gAll['total_bersih'] ?? 0) ?></div></div>
  </div>
  <div class="col-6 col-md-auto flex-grow-1">
    <div class="sum-chip"><div class="lbl">Periode</div>
      <div class="val" style="font-size:.8rem"><?= $bln_names[$p['bm']] ?> — <?= $bln_names[$p['ba']] ?> <?= $p['tahun'] ?></div>
    </div>
  </div>
  <div class="col-6 col-md-auto flex-grow-1">
    <div class="sum-chip"><div class="lbl">OPD</div>
      <div class="val" style="font-size:.8rem"><?= html_escape($p['opd_nama'] ?: 'Semua OPD') ?></div>
    </div>
  </div>
</div>

<!-- TAB NAV -->
<ul class="nav nav-tabs mb-0" id="rekapMainTabs" role="tablist">
  <li class="nav-item"><button class="nav-link active"  data-bs-toggle="tab" data-bs-target="#tabRingkasan" type="button"><i class="fa-solid fa-table-list me-1 text-purple" style="color:#6d28d9"></i>Ringkasan</button></li>
  <li class="nav-item"><button class="nav-link"         data-bs-toggle="tab" data-bs-target="#tabGajiPNS"   type="button"><i class="fa-solid fa-file-invoice-dollar me-1 text-primary"></i>Gaji PNS</button></li>
  <li class="nav-item"><button class="nav-link"         data-bs-toggle="tab" data-bs-target="#tabGajiPPPK"  type="button"><i class="fa-solid fa-file-invoice-dollar me-1 text-warning"></i>Gaji PPPK</button></li>
  <li class="nav-item"><button class="nav-link"         data-bs-toggle="tab" data-bs-target="#tabTPPPNS"    type="button"><i class="fa-solid fa-coins me-1 text-success"></i>TPP PNS</button></li>
  <li class="nav-item"><button class="nav-link"         data-bs-toggle="tab" data-bs-target="#tabTPPPPPK"   type="button"><i class="fa-solid fa-coins me-1 text-info"></i>TPP PPPK</button></li>
  <li class="nav-item"><button class="nav-link"         data-bs-toggle="tab" data-bs-target="#tabPegawai"   type="button"><i class="fa-solid fa-users me-1"></i>Per Pegawai</button></li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom bg-white shadow-sm mb-4">

  <!-- ══ TAB: RINGKASAN ══ -->
  <div class="tab-pane fade show active p-3" id="tabRingkasan" role="tabpanel">
    <?php
    $rc = fn($n) => $n ? number_format((int)$n) : '—';
    $pns  = $gPNS  ?? [];
    $pppk = $gPPPK ?? [];

    // Belanja breakdown: gaji side vs TPP side
    $r_pns_g  = $pns['bruto_gaji']  ?? 0;
    $r_pns_t  = ($pns['tpp_bruto']??0)  + ($pns['pajak_tpp']??0)  + ($pns['bel_tpp_bpjs']??0);
    $r_pppk_g = $pppk['bruto_gaji'] ?? 0;
    $r_pppk_t = ($pppk['tpp_bruto']??0) + ($pppk['pajak_tpp']??0) + ($pppk['bel_tpp_bpjs']??0);
    $r_tot_g  = $r_pns_g  + $r_pppk_g;
    $r_tot_t  = $r_pns_t  + $r_pppk_t;
    $r_tot_all = $r_tot_g + $r_tot_t;

    // Bersih breakdown
    $r_bersih_g   = ($pns['bersih_gaji']??0)  + ($pppk['bersih_gaji']??0);
    $r_bersih_t   = ($pns['tpp_bersih']??0)   + ($pppk['tpp_bersih']??0);
    $r_bersih_all = $r_bersih_g + $r_bersih_t;
    ?>

    <!-- KPI Cards -->
    <div class="row g-2 mb-3">
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="rounded p-3 h-100" style="background:#dbeafe;border-left:4px solid #1e40af">
          <div class="fw-bold mb-2" style="color:#1e40af;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em">
            PNS &nbsp;<span class="fw-normal"><?= $uniq_pns ?> orang</span>
          </div>
          <div class="d-flex justify-content-between" style="font-size:.78rem">
            <span style="color:#64748b">Belanja Gaji</span>
            <span class="fw-semibold" style="color:#1e3a8a">Rp <?= $rc($r_pns_g) ?></span>
          </div>
          <div class="d-flex justify-content-between" style="font-size:.78rem">
            <span style="color:#64748b">Belanja TPP</span>
            <span class="fw-semibold" style="color:#1e3a8a">Rp <?= $rc($r_pns_t) ?></span>
          </div>
          <div class="d-flex justify-content-between mt-1 pt-1 border-top" style="font-size:.8rem">
            <span style="color:#1e40af;font-weight:600">Total</span>
            <span style="color:#1e40af;font-weight:700">Rp <?= $rc($r_pns_g+$r_pns_t) ?></span>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="rounded p-3 h-100" style="background:#fef3c7;border-left:4px solid #92400e">
          <div class="fw-bold mb-2" style="color:#92400e;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em">
            PPPK &nbsp;<span class="fw-normal"><?= $uniq_pppk ?> orang</span>
          </div>
          <div class="d-flex justify-content-between" style="font-size:.78rem">
            <span style="color:#64748b">Belanja Gaji</span>
            <span class="fw-semibold" style="color:#78350f">Rp <?= $rc($r_pppk_g) ?></span>
          </div>
          <div class="d-flex justify-content-between" style="font-size:.78rem">
            <span style="color:#64748b">Belanja TPP</span>
            <span class="fw-semibold" style="color:#78350f">Rp <?= $rc($r_pppk_t) ?></span>
          </div>
          <div class="d-flex justify-content-between mt-1 pt-1 border-top" style="font-size:.8rem">
            <span style="color:#92400e;font-weight:600">Total</span>
            <span style="color:#92400e;font-weight:700">Rp <?= $rc($r_pppk_g+$r_pppk_t) ?></span>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="rounded p-3 h-100" style="background:#f1f5f9;border-left:4px solid #475569">
          <div class="fw-bold mb-2" style="color:#475569;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em">Total Belanja</div>
          <div class="d-flex justify-content-between" style="font-size:.78rem">
            <span style="color:#64748b">Dari Gaji</span>
            <span class="fw-semibold" style="color:#334155">Rp <?= $rc($r_tot_g) ?></span>
          </div>
          <div class="d-flex justify-content-between" style="font-size:.78rem">
            <span style="color:#64748b">Dari TPP</span>
            <span class="fw-semibold" style="color:#334155">Rp <?= $rc($r_tot_t) ?></span>
          </div>
          <div class="d-flex justify-content-between mt-1 pt-1 border-top" style="font-size:.8rem">
            <span style="color:#475569;font-weight:600">Grand Total</span>
            <span style="color:#0f172a;font-weight:700">Rp <?= $rc($r_tot_all) ?></span>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="rounded p-3 h-100" style="background:#dcfce7;border-left:4px solid #065f46">
          <div class="fw-bold mb-2" style="color:#065f46;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em">Bersih THP</div>
          <div class="d-flex justify-content-between" style="font-size:.78rem">
            <span style="color:#64748b">Bersih Gaji</span>
            <span class="fw-semibold" style="color:#064e3b">Rp <?= $rc($r_bersih_g) ?></span>
          </div>
          <div class="d-flex justify-content-between" style="font-size:.78rem">
            <span style="color:#64748b">Bersih TPP</span>
            <span class="fw-semibold" style="color:#064e3b">Rp <?= $rc($r_bersih_t) ?></span>
          </div>
          <div class="d-flex justify-content-between mt-1 pt-1 border-top" style="font-size:.8rem">
            <span style="color:#065f46;font-weight:600">Total THP</span>
            <span style="color:#065f46;font-weight:700">Rp <?= $rc($r_bersih_all) ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Table toolbar -->
    <div class="d-flex align-items-center justify-content-end mb-2">
      <button class="btn btn-success btn-sm" onclick="downloadTable('tblRingkasan','Ringkasan_<?= $p['tahun'] ?>_<?= $p['bm'] ?>')">
        <i class="fa-solid fa-file-excel me-1"></i>Download Excel
      </button>
    </div>

    <!-- Ringkasan Table -->
    <div class="table-responsive">
    <table class="table table-bordered table-sm mb-0" id="tblRingkasan" style="font-size:.8rem">
      <thead>
        <tr>
          <th rowspan="2" class="align-middle" style="min-width:360px;background:#1e293b;color:#f1f5f9">REKENING</th>
          <th colspan="3" class="text-center" style="background:#1e40af;color:#fff">PNS (.00001)</th>
          <th colspan="3" class="text-center" style="background:#92400e;color:#fff">PPPK (.00002)</th>
          <th colspan="3" class="text-center" style="background:#4c1d95;color:#fff">TOTAL PNS + PPPK</th>
        </tr>
        <tr>
          <th class="text-end" style="background:#1e40af;color:#bfdbfe;font-size:.7rem;white-space:nowrap">DARI GAJI</th>
          <th class="text-end" style="background:#1e40af;color:#bfdbfe;font-size:.7rem;white-space:nowrap">DARI TPP</th>
          <th class="text-end fw-bold" style="background:#2563eb;color:#fff;font-size:.7rem;white-space:nowrap">JUMLAH</th>
          <th class="text-end" style="background:#92400e;color:#fde68a;font-size:.7rem;white-space:nowrap">DARI GAJI</th>
          <th class="text-end" style="background:#92400e;color:#fde68a;font-size:.7rem;white-space:nowrap">DARI TPP</th>
          <th class="text-end fw-bold" style="background:#b45309;color:#fff;font-size:.7rem;white-space:nowrap">JUMLAH</th>
          <th class="text-end" style="background:#4c1d95;color:#ddd6fe;font-size:.7rem;white-space:nowrap">DARI GAJI</th>
          <th class="text-end" style="background:#4c1d95;color:#ddd6fe;font-size:.7rem;white-space:nowrap">DARI TPP</th>
          <th class="text-end fw-bold" style="background:#6d28d9;color:#fff;font-size:.7rem;white-space:nowrap">JUMLAH</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $ring = [
        ['group', '5.1.01.01', '5.1.01.01   Belanja Gaji dan Tunjangan ASN'],
        ['row', '5.1.01.01.001', 'Belanja Gaji Pokok ASN',
          fn($d)=>$d['gaji_pokok']??0, fn($d)=>0],
        ['row', '5.1.01.01.002', 'Belanja Tunjangan Keluarga ASN',
          fn($d)=>$d['t_keluarga']??0, fn($d)=>0],
        ['row', '5.1.01.01.003', 'Belanja Tunjangan Jabatan ASN',
          fn($d)=>$d['t_jabatan_str']??0, fn($d)=>0],
        ['row', '5.1.01.01.004', 'Belanja Tunjangan Fungsional ASN',
          fn($d)=>$d['t_jabatan_fung']??0, fn($d)=>0],
        ['row', '5.1.01.01.005', 'Belanja Tunjangan Fungsional Umum ASN',
          fn($d)=>$d['t_jabatan_umum']??0, fn($d)=>0],
        ['row', '5.1.01.01.006', 'Belanja Tunjangan Beras ASN',
          fn($d)=>$d['t_pangan']??0, fn($d)=>0],
        ['row', '5.1.01.01.007', 'Belanja Tunjangan PPh/Tunjangan Khusus ASN',
          fn($d)=>($d['t_khusus']??0)+($d['pph21']??0),
          fn($d)=>$d['pajak_tpp']??0],
        ['row', '5.1.01.01.008', 'Belanja Pembulatan Gaji ASN',
          fn($d)=>$d['t_pembulatan']??0, fn($d)=>0],
        ['row', '5.1.01.01.009', 'Belanja Iuran Jaminan Kesehatan ASN',
          fn($d)=>$d['bel_bpjs_gaji']??0,
          fn($d)=>$d['bel_tpp_bpjs']??0],
        ['row', '5.1.01.01.010', 'Belanja Iuran Jaminan Kecelakaan Kerja ASN',
          fn($d)=>$d['bel_jkk']??0, fn($d)=>0],
        ['row', '5.1.01.01.011', 'Belanja Iuran Jaminan Kematian ASN',
          fn($d)=>$d['bel_jkm']??0, fn($d)=>0],
        ['row', '5.1.01.01.012', 'Belanja Iuran Simpanan Peserta Tabungan Perumahan Rakyat ASN',
          fn($d)=>$d['bel_tapera']??0, fn($d)=>0],
        ['subtotal_group', 'Subtotal Belanja Gaji dan Tunjangan (5.1.01.01)'],
        ['spacer'],
        ['group', '5.1.01.02', '5.1.01.02   Belanja Tambahan Penghasilan ASN'],
        ['row', '5.1.01.02.001', 'Belanja Tambahan Penghasilan berdasarkan Beban Kerja ASN',
          fn($d)=>0, fn($d)=>$d['tpp_bruto']??0],
        ['subtotal_group', 'Subtotal Belanja Tambahan Penghasilan (5.1.01.02)'],
        ['spacer'],
        ['jumlah'],
      ];
      $gt_pns   = ['gaji'=>0,'tpp'=>0];
      $gt_pppk  = ['gaji'=>0,'tpp'=>0];
      $grp_pns  = ['gaji'=>0,'tpp'=>0];
      $grp_pppk = ['gaji'=>0,'tpp'=>0];

      foreach ($ring as $r):
        $type = $r[0];
        if ($type === 'spacer'):
      ?>
        <tr><td colspan="10" style="padding:2px;background:#f8fafc;border-color:#e2e8f0"></td></tr>
      <?php elseif ($type === 'group'):
        $grp_pns  = ['gaji'=>0,'tpp'=>0];
        $grp_pppk = ['gaji'=>0,'tpp'=>0];
      ?>
        <tr style="background:#1e3a8a;color:#e0e7ff">
          <td colspan="10" style="padding:8px 14px;font-weight:700;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase">
            <?= html_escape($r[2]) ?>
          </td>
        </tr>
      <?php elseif ($type === 'subtotal_group'):
        $sg=$grp_pns['gaji']; $st=$grp_pns['tpp']; $sppg=$grp_pppk['gaji']; $sppt=$grp_pppk['tpp'];
      ?>
        <tr style="background:#dbeafe;color:#1e3a8a;font-weight:600;font-size:.77rem;border-top:2px solid #93c5fd;border-bottom:2px solid #93c5fd">
          <td style="padding:6px 14px 6px 22px">
            &#931;&nbsp; <?= html_escape($r[1]) ?>
          </td>
          <td class="text-end" data-orig="<?= $sg ?>"><?= $rc($sg) ?></td>
          <td class="text-end" data-orig="<?= $st ?>"><?= $rc($st) ?></td>
          <td class="text-end fw-bold" data-orig="<?= $sg+$st ?>" style="background:#bfdbfe;color:#1e40af"><?= $rc($sg+$st) ?></td>
          <td class="text-end" data-orig="<?= $sppg ?>"><?= $rc($sppg) ?></td>
          <td class="text-end" data-orig="<?= $sppt ?>"><?= $rc($sppt) ?></td>
          <td class="text-end fw-bold" data-orig="<?= $sppg+$sppt ?>" style="background:#fde68a;color:#92400e"><?= $rc($sppg+$sppt) ?></td>
          <td class="text-end" data-orig="<?= $sg+$sppg ?>"><?= $rc($sg+$sppg) ?></td>
          <td class="text-end" data-orig="<?= $st+$sppt ?>"><?= $rc($st+$sppt) ?></td>
          <td class="text-end fw-bold" data-orig="<?= $sg+$st+$sppg+$sppt ?>" style="background:#ddd6fe;color:#4c1d95"><?= $rc($sg+$st+$sppg+$sppt) ?></td>
        </tr>
      <?php elseif ($type === 'jumlah'):
        $pg=$gt_pns['gaji']; $pt=$gt_pns['tpp']; $ppg=$gt_pppk['gaji']; $ppt=$gt_pppk['tpp'];
      ?>
        <tr style="background:#0f172a;color:#fff;font-weight:700;border-top:3px solid #4c1d95">
          <td style="padding:10px 14px;font-size:.85rem;letter-spacing:.04em">JUMLAH</td>
          <td class="text-end" data-orig="<?= $pg ?>" style="background:#1e3a8a"><?= $rc($pg) ?></td>
          <td class="text-end" data-orig="<?= $pt ?>" style="background:#1e3a8a"><?= $rc($pt) ?></td>
          <td class="text-end" data-orig="<?= $pg+$pt ?>" style="background:#1d4ed8"><?= $rc($pg+$pt) ?></td>
          <td class="text-end" data-orig="<?= $ppg ?>" style="background:#78350f"><?= $rc($ppg) ?></td>
          <td class="text-end" data-orig="<?= $ppt ?>" style="background:#78350f"><?= $rc($ppt) ?></td>
          <td class="text-end" data-orig="<?= $ppg+$ppt ?>" style="background:#b45309"><?= $rc($ppg+$ppt) ?></td>
          <td class="text-end" data-orig="<?= $pg+$ppg ?>" style="background:#3730a3"><?= $rc($pg+$ppg) ?></td>
          <td class="text-end" data-orig="<?= $pt+$ppt ?>" style="background:#3730a3"><?= $rc($pt+$ppt) ?></td>
          <td class="text-end" data-orig="<?= $pg+$pt+$ppg+$ppt ?>" style="background:#5b21b6"><?= $rc($pg+$pt+$ppg+$ppt) ?></td>
        </tr>
      <?php else: // 'row'
        $fn_g=$r[3]; $fn_t=$r[4];
        $pg=(int)$fn_g($pns); $pt=(int)$fn_t($pns);
        $ppg=(int)$fn_g($pppk); $ppt=(int)$fn_t($pppk);
        $tg=$pg+$ppg; $tt=$pt+$ppt;
        $gt_pns['gaji']+=$pg;   $gt_pns['tpp']+=$pt;
        $gt_pppk['gaji']+=$ppg; $gt_pppk['tpp']+=$ppt;
        $grp_pns['gaji']+=$pg;  $grp_pns['tpp']+=$pt;
        $grp_pppk['gaji']+=$ppg; $grp_pppk['tpp']+=$ppt;
        $z=($pg+$pt+$ppg+$ppt===0);
      ?>
        <tr<?= $z?' style="color:#b0b8c8"':'' ?>>
          <td style="padding:5px 5px 5px 28px;color:#374151">
            <span style="font-family:monospace;font-size:.67rem;background:#e8eaf6;color:#3949ab;padding:1px 5px;border-radius:3px;margin-right:6px"><?= html_escape($r[1]) ?></span>
            <?= html_escape($r[2]) ?>
          </td>
          <td class="text-end" data-orig="<?= $pg ?>"><?= $rc($pg) ?></td>
          <td class="text-end" data-orig="<?= $pt ?>"><?= $rc($pt) ?></td>
          <td class="text-end fw-semibold" data-orig="<?= $pg+$pt ?>"<?= $z?'':' style="background:#eff6ff;color:#1e40af"' ?>><?= $rc($pg+$pt) ?></td>
          <td class="text-end" data-orig="<?= $ppg ?>"><?= $rc($ppg) ?></td>
          <td class="text-end" data-orig="<?= $ppt ?>"><?= $rc($ppt) ?></td>
          <td class="text-end fw-semibold" data-orig="<?= $ppg+$ppt ?>"<?= $z?'':' style="background:#fef9c3;color:#92400e"' ?>><?= $rc($ppg+$ppt) ?></td>
          <td class="text-end" data-orig="<?= $tg ?>"><?= $rc($tg) ?></td>
          <td class="text-end" data-orig="<?= $tt ?>"><?= $rc($tt) ?></td>
          <td class="text-end fw-semibold" data-orig="<?= $tg+$tt ?>"<?= $z?'':' style="background:#f5f3ff;color:#4c1d95"' ?>><?= $rc($tg+$tt) ?></td>
        </tr>
      <?php endif; endforeach; ?>
      </tbody>
    </table>
    </div>
    <p class="text-muted mt-2 mb-2" style="font-size:.72rem">
      <i class="fa-solid fa-circle-info me-1"></i>
      <strong>DARI GAJI</strong>: komponen yang dibebankan pada periode gaji reguler (001–012).
      <strong>DARI TPP</strong>: komponen yang dibebankan pada anggaran TPP (PPh &amp; BPJS dari sisi TPP).
      Rekening 007 dan 009 dapat bersumber dari keduanya.
    </p>

    <!-- ── Variabel Cadangan a# ── -->
    <div class="border rounded p-3" style="background:#f8fafc">
      <div class="d-flex align-items-start gap-3 flex-wrap">
        <div>
          <div class="fw-semibold" style="font-size:.85rem">
            Variabel Cadangan <code>a#</code>
            <span class="badge bg-secondary ms-1" style="font-size:.6rem;vertical-align:middle">Opsional</span>
          </div>
          <div class="text-muted mt-1" style="font-size:.72rem">Buffer % untuk proyeksi perubahan kebijakan gaji/tunjangan</div>
        </div>
        <div class="input-group flex-shrink-0" style="width:155px">
          <input type="number" class="form-control form-control-sm text-end" id="rCadPct"
                 value="0" min="0" max="50" step="0.5" placeholder="0">
          <span class="input-group-text" style="font-size:.8rem">%</span>
        </div>
        <div id="rCadResult" class="flex-grow-1 pt-1"></div>
      </div>
    </div>
    <script>
    (function(){
      var _a = <?= (int)$r_tot_all ?>;
      function applyAdj() {
        var pct = parseFloat(document.getElementById('rCadPct').value) || 0;
        var mult = 1 + pct / 100;
        document.querySelectorAll('#tblRingkasan tbody td[data-orig]').forEach(function(td) {
          var v = parseInt(td.dataset.orig) || 0;
          td.textContent = v ? Math.round(v * mult).toLocaleString('id-ID') : '—';
        });
        var el = document.getElementById('rCadResult');
        if (pct > 0) {
          var buf = Math.round(_a * pct / 100);
          el.innerHTML = '<div class="d-flex flex-wrap gap-3 align-items-center" style="font-size:.78rem">'
            + '<span class="badge bg-warning text-dark fw-semibold">a# ' + pct + '% aktif — nilai tabel sudah disesuaikan</span>'
            + '<span class="text-muted">Total asal: <b>Rp ' + _a.toLocaleString('id-ID') + '</b>'
            + ' &rarr; Proyeksi: <b style="color:#4c1d95">Rp ' + Math.round(_a * mult).toLocaleString('id-ID') + '</b>'
            + ' <span style="color:#dc2626">(+Rp ' + buf.toLocaleString('id-ID') + ')</span></span>'
            + '</div>';
        } else {
          el.innerHTML = '<span class="text-muted" style="font-size:.75rem">Masukkan a# &gt; 0 — nilai semua rekening akan dikalikan (1 + a#)</span>';
        }
      }
      var inp = document.getElementById('rCadPct');
      if (inp) { inp.addEventListener('input', applyAdj); applyAdj(); }
    })();
    </script>
  </div>

  <!-- ══ TAB: GAJI PNS ══ -->
  <div class="tab-pane fade" id="tabGajiPNS" role="tabpanel">
    <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
      <span class="fw-semibold text-primary"><i class="fa-solid fa-file-invoice-dollar me-1"></i>Rekap Belanja Gaji PNS</span>
      <button class="btn btn-success btn-sm" onclick="downloadTable('tblGajiPNS','Gaji_PNS')"><i class="fa-solid fa-file-excel me-1"></i>Download Excel</button>
    </div>
    <?php rekap_mat_table('tblGajiPNS', $rek_gaji, $months, $gPNS, $bln_names, 'pns', '.00001', 'PNS'); ?>
  </div>

  <!-- ══ TAB: GAJI PPPK ══ -->
  <div class="tab-pane fade" id="tabGajiPPPK" role="tabpanel">
    <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
      <span class="fw-semibold text-warning"><i class="fa-solid fa-file-invoice-dollar me-1"></i>Rekap Belanja Gaji PPPK</span>
      <button class="btn btn-success btn-sm" onclick="downloadTable('tblGajiPPPK','Gaji_PPPK')"><i class="fa-solid fa-file-excel me-1"></i>Download Excel</button>
    </div>
    <?php rekap_mat_table('tblGajiPPPK', $rek_gaji, $months, $gPPPK, $bln_names, 'pppk', '.00002', 'PPPK'); ?>
  </div>

  <!-- ══ TAB: TPP PNS ══ -->
  <div class="tab-pane fade" id="tabTPPPNS" role="tabpanel">
    <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
      <span class="fw-semibold text-success"><i class="fa-solid fa-coins me-1"></i>Rekap Belanja TPP PNS</span>
      <button class="btn btn-success btn-sm" onclick="downloadTable('tblTPPPNS','TPP_PNS')"><i class="fa-solid fa-file-excel me-1"></i>Download Excel</button>
    </div>
    <?php rekap_mat_table('tblTPPPNS', $rek_tpp, $months, $gPNS, $bln_names, 'pns', '.00001', 'PNS'); ?>
  </div>

  <!-- ══ TAB: TPP PPPK ══ -->
  <div class="tab-pane fade" id="tabTPPPPPK" role="tabpanel">
    <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
      <span class="fw-semibold text-info"><i class="fa-solid fa-coins me-1"></i>Rekap Belanja TPP PPPK</span>
      <button class="btn btn-success btn-sm" onclick="downloadTable('tblTPPPPPK','TPP_PPPK')"><i class="fa-solid fa-file-excel me-1"></i>Download Excel</button>
    </div>
    <?php rekap_mat_table('tblTPPPPPK', $rek_tpp, $months, $gPPPK, $bln_names, 'pppk', '.00002', 'PPPK'); ?>
  </div>

  <!-- ══ TAB: PER PEGAWAI ══ -->
  <div class="tab-pane fade" id="tabPegawai" role="tabpanel">
    <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
      <span class="fw-semibold"><i class="fa-solid fa-users me-1"></i>Akumulasi Per Pegawai</span>
      <button class="btn btn-success btn-sm" onclick="downloadTable('tblPegawai','Per_Pegawai')"><i class="fa-solid fa-file-excel me-1"></i>Download Excel</button>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-sm table-hover peg-tbl mb-0" id="tblPegawai" style="font-size:.75rem">
        <thead>
          <tr>
            <th rowspan="2" class="text-center align-middle">#</th>
            <th rowspan="2" class="align-middle" style="min-width:160px">Nama / NIP</th>
            <th rowspan="2" class="text-center align-middle">Jenis</th>
            <th rowspan="2" class="text-center align-middle">Gol</th>
            <th colspan="12" class="text-center" style="background:#dbeafe;color:#1e40af">KOMPONEN GAJI (001–012)</th>
            <th rowspan="2" class="text-end align-middle" style="background:#fef9c3;color:#78350f;min-width:80px">Total Bruto</th>
            <th colspan="8" class="text-center" style="background:#fce7f3;color:#9d174d">POTONGAN &amp; PENYETORAN</th>
            <th rowspan="2" class="text-end align-middle" style="background:#fce7f3;color:#9d174d;min-width:80px">Total Pot.</th>
            <th rowspan="2" class="text-end align-middle" style="background:#d1fae5;color:#065f46;min-width:80px">Gaji Bersih</th>
            <th colspan="2" class="text-center" style="background:#dcfce7;color:#14532d">TPP</th>
            <th rowspan="2" class="text-end align-middle" style="background:#dcfce7;color:#14532d;min-width:70px">TPP Bersih</th>
            <th rowspan="2" class="text-end align-middle fw-bold" style="background:#ede9fe;color:#4c1d95;min-width:80px">Total THP</th>
          </tr>
          <tr>
            <th class="text-end" style="min-width:70px">001<br>Gaji Pokok</th>
            <th class="text-end" style="min-width:60px">002<br>T.Keluarga</th>
            <th class="text-end" style="min-width:60px">003<br>T.Jabatan</th>
            <th class="text-end" style="min-width:60px">004<br>T.Fungsional</th>
            <th class="text-end" style="min-width:60px">005<br>T.Fung.Umum</th>
            <th class="text-end" style="min-width:60px">006<br>T.Beras</th>
            <th class="text-end" style="min-width:70px">007<br>T.PPh/Khusus</th>
            <th class="text-end" style="min-width:55px">008<br>Pembulatan</th>
            <th class="text-end" style="min-width:65px">009<br>BPJS Empl</th>
            <th class="text-end" style="min-width:55px">010<br>JKK</th>
            <th class="text-end" style="min-width:55px">011<br>JKM</th>
            <th class="text-end" style="min-width:55px">012<br>Tapera</th>
            <th class="text-end" style="min-width:65px">009 Peg<br>BPJS (1%)</th>
            <th class="text-end" style="min-width:65px">013<br>Pensiun</th>
            <th class="text-end" style="min-width:55px">013<br>JHT</th>
            <th class="text-end" style="min-width:65px">012<br>Tapera (2,5%)</th>
            <th class="text-end" style="min-width:65px">007<br>PPh DTP</th>
            <th class="text-end" style="min-width:65px">009 Empl<br>BPJS (4%)</th>
            <th class="text-end" style="min-width:55px">010<br>JKK</th>
            <th class="text-end" style="min-width:55px">011<br>JKM</th>
            <th class="text-end" style="min-width:70px">TPP<br>Nominal</th>
            <th class="text-end" style="min-width:60px">BPJS TPP<br>Peg (1%)</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $no = 0;
        $ft = array_fill_keys(['gapok','tkel','tjab','tfung','tfung_umum','tpangan','t007','tpemb',
            'bpjs_empl','jkk','jkm','tapera_pem','bruto','bpjs_peg','pensiun','jht','tapera_peg','pph21',
            'bpjs_empl_dis','jkk_dis','jkm_dis','total_pot','bersih',
            'tpp','bpjs_tpp_peg','tpp_bersih','total_thp'], 0);
        foreach ($peg_rows as $pr):
          $no++;
          $t = $pr['totals'];
          $detail_url = site_url('rekap/detail/'.$pr['id'].'/'.$p['tahun'].'/'.$p['bm'].'/'.$p['ba']);
          $t007      = ($t['t_khusus']??0) + ($t['pph21']??0);
          $total_pot = ($t['pot_bpjs']??0)+($t['pot_pensiun_peg']??0)+($t['pot_jht_taspen']??0)
                      +($t['pot_tapera_peg']??0)
                      +($t['pph21']??0)+($t['bel_bpjs_gaji']??0)+($t['bel_jkk']??0)+($t['bel_jkm']??0);
          $ft['gapok']       += $t['gaji_pokok']??0;
          $ft['tkel']        += $t['t_keluarga']??0;
          $ft['tjab']        += $t['t_jabatan_str']??0;
          $ft['tfung']       += $t['t_jabatan_fung']??0;
          $ft['tfung_umum']  += $t['t_jabatan_umum']??0;
          $ft['tpangan']     += $t['t_pangan']??0;
          $ft['t007']        += $t007;
          $ft['tpemb']       += $t['t_pembulatan']??0;
          $ft['bpjs_empl']   += $t['bel_bpjs_gaji']??0;
          $ft['jkk']         += $t['bel_jkk']??0;
          $ft['jkm']         += $t['bel_jkm']??0;
          $ft['tapera_pem']  += $t['bel_tapera']??0;
          $ft['bruto']       += $t['bruto_gaji']??0;
          $ft['bpjs_peg']    += $t['pot_bpjs']??0;
          $ft['pensiun']     += $t['pot_pensiun_peg']??0;
          $ft['jht']         += $t['pot_jht_taspen']??0;
          $ft['tapera_peg']  += $t['pot_tapera_peg']??0;
          $ft['pph21']       += $t['pph21']??0;
          $ft['bpjs_empl_dis'] += $t['bel_bpjs_gaji']??0;
          $ft['jkk_dis']     += $t['bel_jkk']??0;
          $ft['jkm_dis']     += $t['bel_jkm']??0;
          $ft['total_pot']   += $total_pot;
          $ft['bersih']      += $t['bersih_gaji']??0;
          $ft['tpp']         += $t['tpp_bruto']??0;
          $ft['bpjs_tpp_peg']+= $t['bpjs_tpp_peg']??0;
          $ft['tpp_bersih']  += $t['tpp_bersih']??0;
          $ft['total_thp']   += $t['total_bersih']??0;
        ?>
        <tr>
          <td class="text-center"><?= $no ?></td>
          <td>
            <a href="<?= $detail_url ?>" target="_blank" class="fw-semibold text-decoration-none">
              <?= html_escape($pr['nama']) ?>
            </a><br>
            <small class="text-muted font-monospace"><?= $pr['nip'] ?></small>
          </td>
          <td class="text-center">
            <span class="badge <?= $pr['jenis']==='PNS'?'bg-primary':'bg-warning text-dark' ?>">
              <?= $pr['jenis'] ?>
            </span>
          </td>
          <td class="text-center"><?= html_escape($pr['golongan']) ?></td>
          <td class="text-end"><?= number_format($t['gaji_pokok']??0) ?></td>
          <td class="text-end"><?= ($t['t_keluarga']??0) ? number_format($t['t_keluarga']) : '—' ?></td>
          <td class="text-end"><?= ($t['t_jabatan_str']??0) ? number_format($t['t_jabatan_str']) : '—' ?></td>
          <td class="text-end"><?= ($t['t_jabatan_fung']??0) ? number_format($t['t_jabatan_fung']) : '—' ?></td>
          <td class="text-end"><?= ($t['t_jabatan_umum']??0) ? number_format($t['t_jabatan_umum']) : '—' ?></td>
          <td class="text-end"><?= ($t['t_pangan']??0) ? number_format($t['t_pangan']) : '—' ?></td>
          <td class="text-end"><?= $t007 ? number_format($t007) : '—' ?></td>
          <td class="text-end"><?= ($t['t_pembulatan']??0) ? number_format($t['t_pembulatan']) : '—' ?></td>
          <td class="text-end"><?= ($t['bel_bpjs_gaji']??0) ? number_format($t['bel_bpjs_gaji']) : '—' ?></td>
          <td class="text-end"><?= ($t['bel_jkk']??0) ? number_format($t['bel_jkk']) : '—' ?></td>
          <td class="text-end"><?= ($t['bel_jkm']??0) ? number_format($t['bel_jkm']) : '—' ?></td>
          <td class="text-end"><?= ($t['bel_tapera']??0) ? number_format($t['bel_tapera']) : '—' ?></td>
          <td class="text-end fw-semibold" style="background:#fef9c3"><?= number_format($t['bruto_gaji']??0) ?></td>
          <td class="text-end"><?= ($t['pot_bpjs']??0) ? number_format($t['pot_bpjs']) : '—' ?></td>
          <td class="text-end"><?= ($t['pot_pensiun_peg']??0) ? number_format($t['pot_pensiun_peg']) : '—' ?></td>
          <td class="text-end"><?= ($t['pot_jht_taspen']??0) ? number_format($t['pot_jht_taspen']) : '—' ?></td>
          <td class="text-end"><?= ($t['pot_tapera_peg']??0) ? number_format($t['pot_tapera_peg']) : '—' ?></td>
          <td class="text-end"><?= ($t['pph21']??0) ? number_format($t['pph21']) : '—' ?></td>
          <td class="text-end"><?= ($t['bel_bpjs_gaji']??0) ? number_format($t['bel_bpjs_gaji']) : '—' ?></td>
          <td class="text-end"><?= ($t['bel_jkk']??0) ? number_format($t['bel_jkk']) : '—' ?></td>
          <td class="text-end"><?= ($t['bel_jkm']??0) ? number_format($t['bel_jkm']) : '—' ?></td>
          <td class="text-end fw-semibold" style="background:#fce7f3;color:#9d174d"><?= number_format($total_pot) ?></td>
          <td class="text-end fw-semibold" style="background:#d1fae5;color:#065f46"><?= number_format($t['bersih_gaji']??0) ?></td>
          <td class="text-end"><?= ($t['tpp_bruto']??0) ? number_format($t['tpp_bruto']) : '—' ?></td>
          <td class="text-end"><?= ($t['bpjs_tpp_peg']??0) ? number_format($t['bpjs_tpp_peg']) : '—' ?></td>
          <td class="text-end fw-semibold" style="color:#0d47a1"><?= number_format($t['tpp_bersih']??0) ?></td>
          <td class="text-end fw-bold" style="color:#5b21b6"><?= number_format($t['total_bersih']??0) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background:#f1f5f9;font-weight:700">
            <td colspan="4" class="text-end fw-bold">TOTAL</td>
            <td class="text-end"><?= number_format($ft['gapok']) ?></td>
            <td class="text-end"><?= number_format($ft['tkel']) ?></td>
            <td class="text-end"><?= number_format($ft['tjab']) ?></td>
            <td class="text-end"><?= number_format($ft['tfung']) ?></td>
            <td class="text-end"><?= number_format($ft['tfung_umum']) ?></td>
            <td class="text-end"><?= number_format($ft['tpangan']) ?></td>
            <td class="text-end"><?= number_format($ft['t007']) ?></td>
            <td class="text-end"><?= number_format($ft['tpemb']) ?></td>
            <td class="text-end"><?= number_format($ft['bpjs_empl']) ?></td>
            <td class="text-end"><?= number_format($ft['jkk']) ?></td>
            <td class="text-end"><?= number_format($ft['jkm']) ?></td>
            <td class="text-end"><?= number_format($ft['tapera_pem']) ?></td>
            <td class="text-end" style="background:#fef9c3"><?= number_format($ft['bruto']) ?></td>
            <td class="text-end"><?= number_format($ft['bpjs_peg']) ?></td>
            <td class="text-end"><?= number_format($ft['pensiun']) ?></td>
            <td class="text-end"><?= number_format($ft['jht']) ?></td>
            <td class="text-end"><?= number_format($ft['tapera_peg']) ?></td>
            <td class="text-end"><?= number_format($ft['pph21']) ?></td>
            <td class="text-end"><?= number_format($ft['bpjs_empl_dis']) ?></td>
            <td class="text-end"><?= number_format($ft['jkk_dis']) ?></td>
            <td class="text-end"><?= number_format($ft['jkm_dis']) ?></td>
            <td class="text-end" style="background:#fce7f3;color:#9d174d"><?= number_format($ft['total_pot']) ?></td>
            <td class="text-end" style="background:#d1fae5;color:#065f46"><?= number_format($ft['bersih']) ?></td>
            <td class="text-end"><?= number_format($ft['tpp']) ?></td>
            <td class="text-end"><?= number_format($ft['bpjs_tpp_peg']) ?></td>
            <td class="text-end" style="color:#0d47a1"><?= number_format($ft['tpp_bersih']) ?></td>
            <td class="text-end" style="color:#5b21b6"><?= number_format($ft['total_thp']) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

</div><!-- /tab-content -->

<script>
function tableToCSV(tableEl) {
  var rows = tableEl.querySelectorAll('tr');
  var csv = [];
  rows.forEach(function(row) {
    var cols = row.querySelectorAll('th, td');
    var rowData = [];
    cols.forEach(function(col) {
      var txt = col.innerText.replace(/\r?\n/g,' ').replace(/"/g,'""').trim();
      rowData.push('"'+txt+'"');
    });
    csv.push(rowData.join(','));
  });
  return csv.join('\r\n');
}
function downloadTable(tableId, label) {
  var tbl = document.getElementById(tableId);
  if (!tbl) return;
  var periode = '<?= $p['tahun'].'_'.sprintf('%02d',$p['bm']).'-'.sprintf('%02d',$p['ba']) ?>';
  var csv = tableToCSV(tbl);
  var BOM = '﻿';
  var blob = new Blob([BOM+csv], {type:'text/csv;charset=utf-8;'});
  var url = URL.createObjectURL(blob);
  var a = document.createElement('a');
  a.href=url; a.download='Rekap_'+label+'_'+periode+'.csv'; a.style.display='none';
  document.body.appendChild(a); a.click();
  setTimeout(function(){ document.body.removeChild(a); URL.revokeObjectURL(url); },500);
}
</script>
<?php endif; ?>
