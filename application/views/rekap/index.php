<?php defined('BASEPATH') OR exit('No direct script access allowed');
$bln_names = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
function rp($n){ return 'Rp '.number_format((int)$n,0,',','.'); }
$p = $params;

// Rekening Gaji (urut 001→011, suffix .00001/.00002 ditambah per tab)
$rek_gaji = [
    ['rek'=>'5.1.01.01.001', 'lbl'=>'Gaji Pokok ASN',                        'key'=>'gaji_pokok',    'neg'=>false],
    ['rek'=>'5.1.01.01.002', 'lbl'=>'Tunjangan Keluarga (Istri+Anak)',        'key'=>'t_keluarga',    'neg'=>false],
    ['rek'=>'5.1.01.01.003', 'lbl'=>'Tunjangan Jabatan (Struktural)',         'key'=>'t_jabatan_str', 'neg'=>false],
    ['rek'=>'5.1.01.01.004', 'lbl'=>'Tunjangan Fungsional',                   'key'=>'t_jabatan_fung','neg'=>false],
    ['rek'=>'5.1.01.01.005', 'lbl'=>'Tunjangan Fungsional Umum',              'key'=>'t_jabatan_umum','neg'=>false],
    ['rek'=>'5.1.01.01.006', 'lbl'=>'Tunjangan Pangan / Beras',               'key'=>'t_pangan',      'neg'=>false],
    ['rek'=>'5.1.01.01.007', 'lbl'=>'Tunjangan Khusus',                       'key'=>'t_khusus',      'neg'=>false],
    ['rek'=>'5.1.01.01.008', 'lbl'=>'Tunjangan Pembulatan',                   'key'=>'t_pembulatan',  'neg'=>false],
    ['type'=>'subtotal',     'lbl'=>'Gaji Bruto (Komponen)',                   'key'=>'bruto_gaji',    'rek'=>'—'],
    ['rek'=>'5.1.01.01.007', 'lbl'=>'Tunjangan PPh Gaji — Ditanggung Pemda', 'key'=>'pph21',         'neg'=>false],
    ['rek'=>'5.1.01.01.009', 'lbl'=>'BPJS Kes Gaji — Pegawai (1%)',          'key'=>'pot_bpjs',      'neg'=>false],
    ['rek'=>'5.1.01.01.009', 'lbl'=>'BPJS Kes Gaji — Pemberi Kerja (4%)',    'key'=>'bel_bpjs_gaji', 'neg'=>false],
    ['rek'=>'5.1.01.01.010', 'lbl'=>'Iuran JKK — Pemberi Kerja (0,24%)',     'key'=>'bel_jkk',       'neg'=>false],
    ['rek'=>'5.1.01.01.011', 'lbl'=>'Iuran JKM — Pemberi Kerja (0,30%)',     'key'=>'bel_jkm',       'neg'=>false],
    ['type'=>'bersih',       'lbl'=>'Bersih Gaji (Diterima Pegawai)',          'key'=>'bersih_gaji',   'rek'=>'—'],
];

// Rekening TPP (urut: TPP → PPh DTP → BPJS empl DTP → bruto anggaran → BPJS peg → bersih)
$rek_tpp = [
    ['rek'=>'5.1.01.02.001', 'lbl'=>'Tambahan Penghasilan Pegawai (TPP)',          'key'=>'tpp_bruto',    'neg'=>false],
    ['rek'=>'5.1.01.01.007', 'lbl'=>'Tunjangan PPh TPP — Ditanggung Pemerintah',  'key'=>'pajak_tpp',    'neg'=>false],
    ['rek'=>'5.1.01.01.009', 'lbl'=>'BPJS Kes TPP — Pemberi Kerja (4%)',          'key'=>'bel_tpp_bpjs', 'neg'=>false],
    ['type'=>'subtotal', 'lbl'=>'Bruto Anggaran TPP', 'rek'=>'—',
        'compute'=>function($d){ return ($d['tpp_bruto']??0)+($d['pajak_tpp']??0)+($d['bel_tpp_bpjs']??0); }],
    ['rek'=>'5.1.01.01.009', 'lbl'=>'BPJS Kes TPP — Pegawai (1%) [dipotong]',    'key'=>'bpjs_tpp_peg', 'neg'=>false],
    ['type'=>'bersih',       'lbl'=>'Bersih TPP (Diterima Pegawai)',                'key'=>'tpp_bersih',   'rek'=>'—'],
];

function rekap_mat_table($tbl_id, $rek_def, $months, $grand_jenis, $bln_names, $jenis_key = 'pns', $rek_suffix = '.00001') {
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
        if ($type === 'subtotal' || $type === 'bersih') {
            $rowBg  = $type === 'subtotal' ? 'background:#fef9c3;color:#78350f' : 'background:#d1fae5;color:#065f46';
            $compute = $r['compute'] ?? null;
            $grandVal = $compute ? (int)$compute($grand_jenis) : (int)($grand_jenis[$r['key']] ?? 0);
            echo '<tr style="'.$rowBg.';font-weight:700">';
            echo '<td style="'.$rowBg.'">—</td>';
            echo '<td style="'.$rowBg.'">'.html_escape($r['lbl']).'</td>';
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
        $neg = !empty($r['neg']);
        $grandVal = (int)($grand_jenis[$r['key']] ?? 0);
        $negCls = $neg ? ' neg' : '';
        echo '<tr>';
        echo '<td><span class="rek-badge">'.html_escape($r['rek'].$rek_suffix).'</span></td>';
        echo '<td>'.html_escape($r['lbl']).'</td>';
        foreach ($months as $md) {
            $val = (int)($md[$jenis_key][$r['key']] ?? 0);
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

<!-- 4-TAB REKENING + PER PEGAWAI TAB -->
<ul class="nav nav-tabs mb-0" id="rekapMainTabs" role="tablist">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabGajiPNS"  type="button"><i class="fa-solid fa-file-invoice-dollar me-1 text-primary"></i>Gaji PNS</button></li>
  <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#tabGajiPPPK" type="button"><i class="fa-solid fa-file-invoice-dollar me-1 text-warning"></i>Gaji PPPK</button></li>
  <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#tabTPPPNS"   type="button"><i class="fa-solid fa-coins me-1 text-success"></i>TPP PNS</button></li>
  <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#tabTPPPPPK"  type="button"><i class="fa-solid fa-coins me-1 text-info"></i>TPP PPPK</button></li>
  <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#tabPegawai"  type="button"><i class="fa-solid fa-users me-1"></i>Per Pegawai</button></li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom bg-white shadow-sm mb-4">

  <!-- ══ TAB: GAJI PNS ══ -->
  <div class="tab-pane fade show active" id="tabGajiPNS" role="tabpanel">
    <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
      <span class="fw-semibold text-primary"><i class="fa-solid fa-file-invoice-dollar me-1"></i>Rekap Belanja Gaji PNS</span>
      <button class="btn btn-success btn-sm" onclick="downloadTable('tblGajiPNS','Gaji_PNS')"><i class="fa-solid fa-file-excel me-1"></i>Download Excel</button>
    </div>
    <?php rekap_mat_table('tblGajiPNS', $rek_gaji, $months, $gPNS, $bln_names, 'pns', '.00001'); ?>
  </div>

  <!-- ══ TAB: GAJI PPPK ══ -->
  <div class="tab-pane fade" id="tabGajiPPPK" role="tabpanel">
    <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
      <span class="fw-semibold text-warning"><i class="fa-solid fa-file-invoice-dollar me-1"></i>Rekap Belanja Gaji PPPK</span>
      <button class="btn btn-success btn-sm" onclick="downloadTable('tblGajiPPPK','Gaji_PPPK')"><i class="fa-solid fa-file-excel me-1"></i>Download Excel</button>
    </div>
    <?php rekap_mat_table('tblGajiPPPK', $rek_gaji, $months, $gPPPK, $bln_names, 'pppk', '.00002'); ?>
  </div>

  <!-- ══ TAB: TPP PNS ══ -->
  <div class="tab-pane fade" id="tabTPPPNS" role="tabpanel">
    <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
      <span class="fw-semibold text-success"><i class="fa-solid fa-coins me-1"></i>Rekap Belanja TPP PNS</span>
      <button class="btn btn-success btn-sm" onclick="downloadTable('tblTPPPNS','TPP_PNS')"><i class="fa-solid fa-file-excel me-1"></i>Download Excel</button>
    </div>
    <?php rekap_mat_table('tblTPPPNS', $rek_tpp, $months, $gPNS, $bln_names, 'pns', '.00001'); ?>
  </div>

  <!-- ══ TAB: TPP PPPK ══ -->
  <div class="tab-pane fade" id="tabTPPPPPK" role="tabpanel">
    <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
      <span class="fw-semibold text-info"><i class="fa-solid fa-coins me-1"></i>Rekap Belanja TPP PPPK</span>
      <button class="btn btn-success btn-sm" onclick="downloadTable('tblTPPPPPK','TPP_PPPK')"><i class="fa-solid fa-file-excel me-1"></i>Download Excel</button>
    </div>
    <?php rekap_mat_table('tblTPPPPPK', $rek_tpp, $months, $gPPPK, $bln_names, 'pppk', '.00002'); ?>
  </div>

  <!-- ══ TAB: PER PEGAWAI ══ -->
  <div class="tab-pane fade" id="tabPegawai" role="tabpanel">
    <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
      <span class="fw-semibold"><i class="fa-solid fa-users me-1"></i>Akumulasi Per Pegawai</span>
      <button class="btn btn-success btn-sm" onclick="downloadTable('tblPegawai','Per_Pegawai')"><i class="fa-solid fa-file-excel me-1"></i>Download Excel</button>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-sm table-hover peg-tbl mb-0" id="tblPegawai">
        <thead>
          <tr>
            <th>#</th>
            <th>Nama / NIP</th>
            <th class="text-center">Jenis</th>
            <th class="text-center">Gol</th>
            <th class="text-end">Gaji Pokok</th>
            <th class="text-end">Bruto Gaji</th>
            <th class="text-end">Potongan</th>
            <th class="text-end" style="color:#0f5132">Bersih Gaji</th>
            <th class="text-end">TPP Nominal</th>
            <th class="text-end">BPJS TPP (1%)</th>
            <th class="text-end" style="color:#0d47a1">TPP Bersih</th>
            <th class="text-end fw-bold" style="color:#5b21b6">Total THP</th>
          </tr>
        </thead>
        <tbody>
        <?php $no=0; foreach ($peg_rows as $pr):
          $no++;
          $t = $pr['totals'];
          $detail_url = site_url('rekap/detail/'.$pr['id'].'/'.$p['tahun'].'/'.$p['bm'].'/'.$p['ba']);
        ?>
        <tr>
          <td><?= $no ?></td>
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
          <td class="text-end"><?= number_format($t['gaji_pokok']) ?></td>
          <td class="text-end"><?= number_format($t['bruto_gaji']) ?></td>
          <td class="text-end" style="color:#b91c1c"><?= number_format($t['pot_bpjs']+$t['pot_pensiun']) ?></td>
          <td class="text-end fw-semibold" style="color:#0f5132"><?= number_format($t['bersih_gaji']) ?></td>
          <td class="text-end"><?= number_format($t['tpp_bruto']) ?></td>
          <td class="text-end" style="color:#b91c1c"><?= number_format($t['bpjs_tpp_peg']) ?></td>
          <td class="text-end fw-semibold" style="color:#0d47a1"><?= number_format($t['tpp_bersih']) ?></td>
          <td class="text-end fw-bold" style="color:#5b21b6"><?= number_format($t['total_bersih']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
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
