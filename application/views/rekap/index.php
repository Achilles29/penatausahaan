<?php defined('BASEPATH') OR exit('No direct script access allowed');
$bln_names = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
function rp($n){ return 'Rp '.number_format((int)$n,0,',','.'); }
function rp0($n){ return $n ? 'Rp '.number_format((int)$n,0,',','.') : '—'; }
$p = $params; // shorthand

function row_cells($t) {
  $r = '<td class="num mode-all mode-gaji">'.number_format($t['jml']).'</td>';
  $r .= '<td class="num mode-all mode-gaji">'.number_format($t['gaji_pokok']).'</td>';
  $r .= '<td class="num mode-all mode-gaji">'.number_format($t['t_keluarga']).'</td>';
  $r .= '<td class="num mode-all mode-gaji">'.number_format($t['t_jabatan']).'</td>';
  $r .= '<td class="num mode-all mode-gaji">'.number_format($t['t_pangan']).'</td>';
  $r .= '<td class="num mode-all mode-gaji fw-semibold">'.number_format($t['bruto_gaji']).'</td>';
  $r .= '<td class="num mode-all mode-gaji neg">'.number_format($t['pot_bpjs']).'</td>';
  $r .= '<td class="num mode-all mode-gaji neg">'.number_format($t['pot_pensiun']).'</td>';
  $r .= '<td class="num mode-all mode-gaji neg text-muted">'.number_format($t['pph21']).'</td>';
  $r .= '<td class="num mode-all mode-gaji bersih">'.number_format($t['bersih_gaji']).'</td>';
  $r .= '<td class="num mode-all mode-tpp tpp-col">'.number_format($t['tpp_bruto']).'</td>';
  $r .= '<td class="num mode-all mode-tpp neg">'.number_format($t['bpjs_tpp_peg']).'</td>';
  $r .= '<td class="num mode-all mode-tpp tpp-col bersih">'.number_format($t['tpp_bersih']).'</td>';
  $r .= '<td class="num mode-all fw-bold" style="color:#5b21b6">'.number_format($t['total_bersih']).'</td>';
  $r .= '<td class="num mode-all mode-gaji">'.number_format($t['bel_employer']).'</td>';
  $r .= '<td class="num mode-all mode-gaji">'.number_format($t['bel_tpp_bpjs']).'</td>';
  return $r;
}

function footer_cells($t) {
  $r = '<td class="num mode-all mode-gaji fw-bold">'.number_format($t['jml']).'</td>';
  $r .= '<td class="num mode-all mode-gaji fw-bold">'.number_format($t['gaji_pokok']).'</td>';
  $r .= '<td class="num mode-all mode-gaji fw-bold">'.number_format($t['t_keluarga']).'</td>';
  $r .= '<td class="num mode-all mode-gaji fw-bold">'.number_format($t['t_jabatan']).'</td>';
  $r .= '<td class="num mode-all mode-gaji fw-bold">'.number_format($t['t_pangan']).'</td>';
  $r .= '<td class="num mode-all mode-gaji fw-bold">'.number_format($t['bruto_gaji']).'</td>';
  $r .= '<td class="num mode-all mode-gaji neg fw-bold">'.number_format($t['pot_bpjs']).'</td>';
  $r .= '<td class="num mode-all mode-gaji neg fw-bold">'.number_format($t['pot_pensiun']).'</td>';
  $r .= '<td class="num mode-all mode-gaji neg fw-bold text-muted">'.number_format($t['pph21']).'</td>';
  $r .= '<td class="num mode-all mode-gaji bersih fw-bold">'.number_format($t['bersih_gaji']).'</td>';
  $r .= '<td class="num mode-all mode-tpp tpp-col fw-bold">'.number_format($t['tpp_bruto']).'</td>';
  $r .= '<td class="num mode-all mode-tpp neg fw-bold">'.number_format($t['bpjs_tpp_peg']).'</td>';
  $r .= '<td class="num mode-all mode-tpp tpp-col bersih fw-bold">'.number_format($t['tpp_bersih']).'</td>';
  $r .= '<td class="num mode-all fw-bold" style="color:#5b21b6">'.number_format($t['total_bersih']).'</td>';
  $r .= '<td class="num mode-all mode-gaji fw-bold">'.number_format($t['bel_employer']).'</td>';
  $r .= '<td class="num mode-all mode-gaji fw-bold">'.number_format($t['bel_tpp_bpjs']).'</td>';
  return $r;
}
?>
<style>
.rekap-hdr { background:linear-gradient(135deg,#0f4c81,#1d6fa4); color:#fff; border-radius:10px 10px 0 0; }
.tbl-rekap { font-size:.79rem; }
.tbl-rekap th { font-size:.73rem; white-space:nowrap; background:#f1f5f9; }
.tbl-rekap td { white-space:nowrap; vertical-align:middle; }
.tbl-rekap tr.ke-row { background:#fef9c3; }
.tbl-rekap tr.footer-row td { font-weight:700; background:#e8f4fd; }
.tbl-rekap td.num { text-align:right; font-variant-numeric:tabular-nums; }
.tbl-rekap td.bersih { color:#0f5132; font-weight:600; }
.tbl-rekap td.tpp-col { background:#f0fdf4; }
.tbl-rekap td.neg { color:#b91c1c; }
.chip-tab { border:none; background:#e2e8f0; border-radius:20px; padding:3px 14px; font-size:.8rem; cursor:pointer; }
.chip-tab.active { background:#0f4c81; color:#fff; }
.mode-tab { border:none; background:#e8f4fd; border-radius:20px; padding:3px 14px; font-size:.8rem; cursor:pointer; margin-left:4px; }
.mode-tab.active { background:#0ea5e9; color:#fff; }
.sum-chip { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 14px; text-align:center; }
.sum-chip .lbl { font-size:.7rem; color:#64748b; text-transform:uppercase; letter-spacing:.04em; }
.sum-chip .val { font-size:1rem; font-weight:700; color:#0f172a; }
.sum-chip .val.blue { color:#0f4c81; }
.sum-chip .val.green { color:#15803d; }
.peg-tbl { font-size:.78rem; }
.peg-tbl th { font-size:.72rem; background:#f8fafc; white-space:nowrap; }
.peg-tbl td { vertical-align:middle; white-space:nowrap; }
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
      <div class="col-md-2">
        <label class="form-label small mb-1">Jenis ASN</label>
        <select class="form-select form-select-sm" name="jenis_filter">
          <option value="SEMUA" <?= (!$p || $p['jenis_filter']==='SEMUA')?'selected':'' ?>>Semua (PNS+PPPK)</option>
          <option value="PNS"   <?= ($p && $p['jenis_filter']==='PNS')?'selected':'' ?>>PNS saja</option>
          <option value="PPPK"  <?= ($p && $p['jenis_filter']==='PPPK')?'selected':'' ?>>PPPK saja</option>
        </select>
      </div>
      <div class="col-md-1 d-flex align-items-end pb-1">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="include_ke" id="chk_ke" value="1"
            <?= (!$p || $p['include_ke'])?'checked':'' ?>>
          <label class="form-check-label small" for="chk_ke">+Ke-13/14</label>
        </div>
      </div>
      <div class="col-md-1">
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
<?php $grand = $result['grand']; $months = $result['months']; $peg_rows = $result['peg_rows']; ?>

<!-- CHIPS SUMMARY -->
<?php
$jf = $p['jenis_filter'];
$show_pns  = ($jf !== 'PPPK');
$show_pppk = ($jf !== 'PNS');
$combined  = $grand['combined'];
$total_jml = ($show_pns ? $grand['pns']['jml'] : 0) + ($show_pppk ? $grand['pppk']['jml'] : 0);
// Ambil unique count pegawai
$uniq_pns  = count(array_filter($peg_rows, fn($r) => $r['jenis'] === 'PNS'));
$uniq_pppk = count(array_filter($peg_rows, fn($r) => $r['jenis'] === 'PPPK'));
?>
<div class="row g-2 mb-3">
  <?php if ($show_pns && $show_pppk): ?>
  <div class="col-6 col-md-3">
    <div class="sum-chip"><div class="lbl">Pegawai PNS</div><div class="val"><?= $uniq_pns ?> orang</div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="sum-chip"><div class="lbl">Pegawai PPPK</div><div class="val"><?= $uniq_pppk ?> orang</div></div>
  </div>
  <?php else: ?>
  <div class="col-6 col-md-3">
    <div class="sum-chip"><div class="lbl">Jumlah Pegawai</div><div class="val"><?= $show_pns ? $uniq_pns : $uniq_pppk ?> orang</div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="sum-chip"><div class="lbl">&nbsp;</div><div class="val">&nbsp;</div></div>
  </div>
  <?php endif; ?>
  <div class="col-6 col-md-3">
    <div class="sum-chip"><div class="lbl">Total Bersih Gaji</div><div class="val blue"><?= rp($combined['bersih_gaji']) ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="sum-chip"><div class="lbl">Total TPP Bersih</div><div class="val green"><?= rp($combined['tpp_bersih']) ?></div></div>
  </div>
</div>

<!-- TABS JENIS + MODE -->
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
  <span class="small text-muted me-1">Tampilkan:</span>
  <?php if ($show_pns && $show_pppk): ?>
  <button class="chip-tab active" onclick="switchJenis('combined',this)">Semua</button>
  <button class="chip-tab"        onclick="switchJenis('pns',this)">PNS</button>
  <button class="chip-tab"        onclick="switchJenis('pppk',this)">PPPK</button>
  <?php elseif ($show_pns): ?>
  <button class="chip-tab active" onclick="switchJenis('pns',this)">PNS</button>
  <?php else: ?>
  <button class="chip-tab active" onclick="switchJenis('pppk',this)">PPPK</button>
  <?php endif; ?>
  <span class="ms-3 small text-muted me-1">Kolom:</span>
  <button class="mode-tab active" onclick="switchMode('all',this)">Gaji &amp; TPP</button>
  <button class="mode-tab"        onclick="switchMode('gaji',this)">Gaji saja</button>
  <button class="mode-tab"        onclick="switchMode('tpp',this)">TPP saja</button>
</div>

<!-- TABEL REKAP BULANAN -->
<div class="card shadow-sm mb-4">
  <div class="card-header py-2 px-3"><strong>Rekap Bulanan</strong>
    <span class="badge bg-secondary ms-2" id="lbl_opd"><?= html_escape($p['opd_nama'] ?: 'Semua OPD') ?></span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-bordered table-sm tbl-rekap mb-0" id="tblBulanan">
      <thead>
        <tr>
          <th rowspan="2" class="text-center align-middle">#</th>
          <th rowspan="2" class="align-middle">Periode</th>
          <th rowspan="2" class="text-center align-middle mode-all mode-gaji">Jml</th>
          <!-- GAJI -->
          <th colspan="5" class="text-center mode-all mode-gaji" style="background:#dbeafe">Komponen Gaji</th>
          <th colspan="3" class="text-center mode-all mode-gaji" style="background:#fce7f3">Potongan Gaji</th>
          <th rowspan="2" class="text-center align-middle mode-all mode-gaji" style="background:#d1fae5">Bersih Gaji</th>
          <!-- TPP -->
          <th colspan="3" class="text-center mode-all mode-tpp" style="background:#dcfce7">TPP</th>
          <!-- TOTAL -->
          <th rowspan="2" class="text-center align-middle mode-all" style="background:#ede9fe">Total Bersih</th>
          <!-- BELANJA PEM -->
          <th colspan="2" class="text-center mode-all mode-gaji" style="background:#fef3c7">Belanja Pemerintah</th>
        </tr>
        <tr>
          <!-- Gaji -->
          <th class="text-end mode-all mode-gaji" style="background:#dbeafe">Gaji Pokok</th>
          <th class="text-end mode-all mode-gaji" style="background:#dbeafe">T.Keluarga</th>
          <th class="text-end mode-all mode-gaji" style="background:#dbeafe">T.Jabatan</th>
          <th class="text-end mode-all mode-gaji" style="background:#dbeafe">T.Pangan</th>
          <th class="text-end mode-all mode-gaji" style="background:#bfdbfe">Bruto Gaji</th>
          <th class="text-end mode-all mode-gaji" style="background:#fce7f3">BPJS (1%)</th>
          <th class="text-end mode-all mode-gaji" style="background:#fce7f3">Pensiun/JHT</th>
          <th class="text-end mode-all mode-gaji" style="background:#fce7f3">PPh21 DTP</th>
          <!-- TPP -->
          <th class="text-end mode-all mode-tpp" style="background:#dcfce7">TPP Bruto</th>
          <th class="text-end mode-all mode-tpp" style="background:#dcfce7">BPJS TPP (1%)</th>
          <th class="text-end mode-all mode-tpp tpp-col" style="background:#bbf7d0">TPP Bersih</th>
          <!-- Belanja Pem -->
          <th class="text-end mode-all mode-gaji" style="background:#fef3c7">BPJS Employer</th>
          <th class="text-end mode-all mode-gaji" style="background:#fef3c7">BPJS TPP</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($months as $idx => $md): ?>
      <?php
        // Data dipilih berdasarkan jenis aktif (JS akan filter kolom, tapi kita pre-render semua)
        // Akan di-toggle by JS; render data-pns / data-pppk / data-combined sebagai data attrs
        $d_combined = json_encode($md['combined']);
        $d_pns      = json_encode($md['pns']);
        $d_pppk     = json_encode($md['pppk']);
      ?>
      <tr class="<?= $md['is_ke'] ? 'ke-row' : '' ?>"
          data-combined='<?= $d_combined ?>'
          data-pns='<?= $d_pns ?>'
          data-pppk='<?= $d_pppk ?>'>
        <td class="text-center"><?= $idx+1 ?></td>
        <td><?= html_escape($md['label']) ?><?= $md['is_ke'] ? ' <span class="badge bg-warning text-dark ms-1">Ke-'.$md['is_ke'].'</span>' : '' ?></td>
        <?= row_cells($md['combined']) ?>
      </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="footer-row">
          <td colspan="2" class="fw-bold">TOTAL</td>
          <?= footer_cells($grand['combined']) ?>
        </tr>
      </tfoot>
    </table>
    </div>
  </div>
</div>

<!-- TABEL PER PEGAWAI -->
<div class="card shadow-sm mb-4">
  <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center">
    <strong>Akumulasi Per Pegawai</strong>
    <span class="text-muted small">Klik nama untuk lihat detail per bulan</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-bordered table-sm table-hover peg-tbl mb-0" id="tblPegawai">
      <thead>
        <tr>
          <th>#</th>
          <th>Nama / NIP</th>
          <th class="text-center">Gol</th>
          <th class="text-center">Jenis</th>
          <th class="text-end mode-all mode-gaji">Gaji Pokok</th>
          <th class="text-end mode-all mode-gaji">Bruto Gaji</th>
          <th class="text-end mode-all mode-gaji">Potongan</th>
          <th class="text-end mode-all mode-gaji">Bersih Gaji</th>
          <th class="text-end mode-all mode-tpp">TPP Bruto</th>
          <th class="text-end mode-all mode-tpp">BPJS TPP (1%)</th>
          <th class="text-end mode-all mode-tpp">TPP Bersih</th>
          <th class="text-end mode-all">Total Bersih</th>
        </tr>
      </thead>
      <tbody>
      <?php $no=0; foreach ($peg_rows as $pr):
        $jf2 = $p['jenis_filter'];
        if ($jf2 !== 'SEMUA' && $pr['jenis'] !== $jf2) continue;
        $no++;
        $t = $pr['totals'];
        $detail_url = site_url('rekap/detail/'.$pr['id'].'/'.$p['tahun'].'/'.$p['bm'].'/'.$p['ba']);
      ?>
      <tr class="peg-row-<?= strtolower($pr['jenis']) ?>">
        <td><?= $no ?></td>
        <td>
          <a href="<?= $detail_url ?>" target="_blank" class="fw-semibold text-decoration-none">
            <?= html_escape($pr['nama']) ?>
          </a><br>
          <small class="text-muted font-monospace"><?= $pr['nip'] ?></small>
        </td>
        <td class="text-center"><?= html_escape($pr['golongan']) ?></td>
        <td class="text-center">
          <span class="badge <?= $pr['jenis']==='PNS'?'bg-primary':'bg-success' ?>">
            <?= $pr['jenis'] ?>
          </span>
        </td>
        <td class="num mode-all mode-gaji"><?= number_format($t['gaji_pokok']) ?></td>
        <td class="num mode-all mode-gaji"><?= number_format($t['bruto_gaji']) ?></td>
        <td class="num mode-all mode-gaji neg"><?= number_format($t['pot_bpjs']+$t['pot_pensiun']) ?></td>
        <td class="num mode-all mode-gaji bersih"><?= number_format($t['bersih_gaji']) ?></td>
        <td class="num mode-all mode-tpp tpp-col"><?= number_format($t['tpp_bruto']) ?></td>
        <td class="num mode-all mode-tpp neg"><?= number_format($t['bpjs_tpp_peg']) ?></td>
        <td class="num mode-all mode-tpp tpp-col bersih"><?= number_format($t['tpp_bersih']) ?></td>
        <td class="num mode-all fw-bold" style="color:#5b21b6"><?= number_format($t['total_bersih']) ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<script>
var currentJenis = '<?= ($show_pns && $show_pppk) ? 'combined' : ($show_pns ? 'pns' : 'pppk') ?>';
var currentMode  = 'all';

function switchJenis(jenis, btn) {
  currentJenis = jenis;
  document.querySelectorAll('.chip-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  updateTable();
}
function switchMode(mode, btn) {
  currentMode = mode;
  document.querySelectorAll('.mode-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  // Show/hide columns
  document.querySelectorAll('.mode-gaji').forEach(el => el.style.display = (mode === 'tpp') ? 'none' : '');
  document.querySelectorAll('.mode-tpp').forEach(el => el.style.display = (mode === 'gaji') ? 'none' : '');
}
function fmt(n) { return new Intl.NumberFormat('id-ID').format(Math.round(n||0)); }
function updateTable() {
  document.querySelectorAll('#tblBulanan tbody tr').forEach(function(tr) {
    var d = JSON.parse(tr.getAttribute('data-' + currentJenis));
    var cells = tr.querySelectorAll('td');
    // cells[0]=no, cells[1]=label, then data cells
    var i = 2;
    cells[i++].textContent = fmt(d.jml);
    cells[i++].textContent = fmt(d.gaji_pokok);
    cells[i++].textContent = fmt(d.t_keluarga);
    cells[i++].textContent = fmt(d.t_jabatan);
    cells[i++].textContent = fmt(d.t_pangan);
    cells[i++].textContent = fmt(d.bruto_gaji);
    cells[i++].textContent = fmt(d.pot_bpjs);
    cells[i++].textContent = fmt(d.pot_pensiun);
    cells[i++].textContent = fmt(d.pph21);
    cells[i++].textContent = fmt(d.bersih_gaji);
    cells[i++].textContent = fmt(d.tpp_bruto);
    cells[i++].textContent = fmt(d.bpjs_tpp_peg);
    cells[i++].textContent = fmt(d.tpp_bersih);
    cells[i++].textContent = fmt(d.total_bersih);
    cells[i++].textContent = fmt(d.bel_employer);
    cells[i++].textContent = fmt(d.bel_tpp_bpjs);
  });
  // Filter pegawai rows
  document.querySelectorAll('#tblPegawai tbody tr').forEach(function(tr) {
    if (currentJenis === 'combined') tr.style.display = '';
    else tr.style.display = tr.classList.contains('peg-row-' + currentJenis) ? '' : 'none';
  });
}
</script>
<?php endif; ?>
