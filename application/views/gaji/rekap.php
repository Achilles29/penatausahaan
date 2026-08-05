<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<style>
.rekap-header { background: linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; border-radius:10px 10px 0 0; }
.sum-box { background:#f8f9fa; border-radius:8px; padding:12px 16px; text-align:center; }
.sum-box .lbl { font-size:.75rem; color:#6c757d; text-transform:uppercase; letter-spacing:.05em; }
.sum-box .val { font-size:1.1rem; font-weight:700; color:#1e293b; margin-top:2px; }
.sum-box.primary .val { color:#6366f1; }
.rekening-badge { font-size:.7rem; padding:2px 6px; background:#e8eaf6; color:#3949ab; border-radius:4px; font-family:monospace; }
.tbl-rekap th { font-size:.8rem; white-space:nowrap; }
.tbl-rekap td { font-size:.82rem; vertical-align:middle; }
.tbl-rekap tr.zero-row td { color:#bbb; }
.peringatan-chip { background:#fef3c7; color:#92400e; border-radius:20px; font-size:.75rem; padding:2px 10px; display:inline-block; }
.pensiun-chip    { background:#fee2e2; color:#991b1b; border-radius:20px; font-size:.75rem; padding:2px 10px; display:inline-block; }
.section-sep td  { background:#f1f5f9 !important; font-weight:700; font-size:.75rem; color:#475569; text-transform:uppercase; letter-spacing:.06em; padding:4px 8px; }
.rek-tbl th { font-size:.8rem; white-space:nowrap; background:#f8f9fa; }
.rek-tbl td { font-size:.82rem; }
.rek-tbl .row-potongan td { color:#dc3545; }
.rek-tbl .row-zero td { color:#bbb; }
.rek-tbl .row-subtotal td { background:#fef9c3; font-weight:700; color:#78350f; }
.rek-tbl .row-subtotal-pot td { background:#fce7f3; font-weight:700; color:#9d174d; }
.rek-tbl .row-bersih td { background:#d1fae5; font-weight:700; color:#065f46; }
.rek-tbl tfoot td { font-weight:700; background:#f1f5f9; }
</style>

<div class="card mb-4">
  <div class="card-body rekap-header py-3 px-4">
    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-table-cells me-2"></i>Rekap Gaji ASN</h5>
    <small class="opacity-75">Kalkulasi komponen gaji per pegawai untuk satu periode</small>
  </div>
  <div class="card-body border-bottom py-3">
    <form id="formRekap" class="row g-2 align-items-end">
      <?php if ($is_super): ?>
      <div class="col-md-4">
        <label class="form-label small mb-1">OPD <span class="text-muted fw-normal">(kosongkan = semua)</span></label>
        <select class="form-select form-select-sm" name="opd_id" id="sel_opd">
          <option value="">— Semua OPD —</option>
          <?php foreach ($opd_list as $k => $v): ?>
          <option value="<?= $k ?>"><?= html_escape($v) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php else: ?>
      <input type="hidden" name="opd_id" value="<?= $default_opd ?>">
      <?php endif; ?>
      <div class="col-md-2">
        <label class="form-label small mb-1">Bulan</label>
        <select class="form-select form-select-sm" name="bulan" id="sel_bulan">
          <?php $bln = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
          for ($i=1;$i<=12;$i++): ?>
          <option value="<?= $i ?>" <?= $i==(int)date('n')?'selected':'' ?>><?= $bln[$i-1] ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Tahun</label>
        <select class="form-select form-select-sm" name="tahun" id="sel_tahun">
          <?php for ($y=(int)date('Y')-1; $y<=(int)date('Y')+3; $y++): ?>
          <option value="<?= $y ?>" <?= $y==(int)date('Y')?'selected':'' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Jenis Gaji</label>
        <select class="form-select form-select-sm" name="is_ke" id="sel_ke">
          <option value="0">Normal (Reguler)</option>
          <?php foreach ($ke_rows as $ke): ?>
          <option value="<?= (int)$ke['no'] ?>"><?= html_escape($ke['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="fa-solid fa-calculator me-1"></i> Hitung
        </button>
      </div>
    </form>
  </div>
</div>

<div id="rekapLoading" style="display:none; text-align:center; padding:40px">
  <div class="spinner-border text-primary"></div>
  <p class="mt-2 text-muted">Menghitung gaji untuk semua pegawai…</p>
</div>

<div id="rekapResult" style="display:none">
  <!-- Summary boxes -->
  <div class="row g-3 mb-4" id="summaryBoxes"></div>

  <!-- Main tabs -->
  <ul class="nav nav-tabs mb-0" id="mainTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabRingkasan" type="button" role="tab">
        <i class="fa-solid fa-table-list me-1" style="color:#6d28d9"></i>Ringkasan
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabRekening" type="button" role="tab">
        <i class="fa-solid fa-receipt me-1"></i>Per Rekening
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPegawai" type="button" role="tab">
        <i class="fa-solid fa-users me-1"></i>Per Pegawai
      </button>
    </li>
  </ul>

  <div class="tab-content border border-top-0 rounded-bottom bg-white shadow-sm mb-4">

    <!-- ══ TAB: RINGKASAN ══ -->
    <div class="tab-pane fade show active p-3" id="tabRingkasan" role="tabpanel">
      <div id="ringkasanKpi" class="mb-3"></div>
      <div class="d-flex justify-content-end mb-2">
        <button class="btn btn-success btn-sm" onclick="downloadTable('tblRingkasan','Ringkasan')">
          <i class="fa-solid fa-file-excel me-1"></i>Download Excel
        </button>
      </div>
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
          <tbody id="ringkasanBody">
            <tr><td colspan="10" class="text-center text-muted py-4">— Tekan <strong>Hitung</strong> untuk memuat data —</td></tr>
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
            <input type="number" class="form-control form-control-sm text-end" id="gCadPct"
                   value="0" min="0" max="50" step="0.5" placeholder="0">
            <span class="input-group-text" style="font-size:.8rem">%</span>
          </div>
          <div id="gCadResult" class="flex-grow-1 pt-1">
            <span class="text-muted" style="font-size:.75rem">Tekan Hitung terlebih dahulu, lalu ubah a# untuk proyeksi</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ TAB: PER REKENING ══ -->
    <div class="tab-pane fade p-3" id="tabRekening" role="tabpanel">

      <div class="d-flex align-items-center justify-content-between mb-3">
        <ul class="nav nav-pills" id="rekenSubTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active py-1 px-3" data-bs-toggle="pill" data-bs-target="#rtGajiPNS" type="button" role="tab">
              Gaji PNS
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link py-1 px-3" data-bs-toggle="pill" data-bs-target="#rtGajiPPPK" type="button" role="tab">
              Gaji PPPK
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link py-1 px-3" data-bs-toggle="pill" data-bs-target="#rtTPPPNS" type="button" role="tab">
              TPP PNS
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link py-1 px-3" data-bs-toggle="pill" data-bs-target="#rtTPPPPPK" type="button" role="tab">
              TPP PPPK
            </button>
          </li>
        </ul>
        <button class="btn btn-success btn-sm" onclick="downloadActiveRekTable()">
          <i class="fa-solid fa-file-excel me-1"></i> Download Excel
        </button>
      </div>

      <div class="tab-content">

        <div class="tab-pane fade show active" id="rtGajiPNS" role="tabpanel">
          <table id="tblGajiPNS" class="table table-sm table-bordered rek-tbl mb-0">
            <thead><tr><th style="width:145px">Rekening</th><th>Komponen</th><th class="text-end" style="width:170px">Jumlah (Rp)</th></tr></thead>
            <tbody id="bdyGajiPNS"></tbody>
            <tfoot><tr><td colspan="2">TOTAL BELANJA GAJI PNS</td><td class="text-end" id="totGajiPNS">—</td></tr></tfoot>
          </table>
        </div>

        <div class="tab-pane fade" id="rtGajiPPPK" role="tabpanel">
          <table id="tblGajiPPPK" class="table table-sm table-bordered rek-tbl mb-0">
            <thead><tr><th style="width:145px">Rekening</th><th>Komponen</th><th class="text-end" style="width:170px">Jumlah (Rp)</th></tr></thead>
            <tbody id="bdyGajiPPPK"></tbody>
            <tfoot><tr><td colspan="2">TOTAL BELANJA GAJI PPPK</td><td class="text-end" id="totGajiPPPK">—</td></tr></tfoot>
          </table>
        </div>

        <div class="tab-pane fade" id="rtTPPPNS" role="tabpanel">
          <table id="tblTPPPNS" class="table table-sm table-bordered rek-tbl mb-0">
            <thead><tr><th style="width:145px">Rekening</th><th>Komponen</th><th class="text-end" style="width:170px">Jumlah (Rp)</th></tr></thead>
            <tbody id="bdyTPPPNS"></tbody>
            <tfoot><tr><td colspan="2">TOTAL BELANJA TPP PNS</td><td class="text-end" id="totTPPPNS">—</td></tr></tfoot>
          </table>
        </div>

        <div class="tab-pane fade" id="rtTPPPPPK" role="tabpanel">
          <table id="tblTPPPPPK" class="table table-sm table-bordered rek-tbl mb-0">
            <thead><tr><th style="width:145px">Rekening</th><th>Komponen</th><th class="text-end" style="width:170px">Jumlah (Rp)</th></tr></thead>
            <tbody id="bdyTPPPPPK"></tbody>
            <tfoot><tr><td colspan="2">TOTAL BELANJA TPP PPPK</td><td class="text-end" id="totTPPPPPK">—</td></tr></tfoot>
          </table>
        </div>

      </div><!-- /tab-content inner -->
    </div><!-- /tabRekening -->

    <!-- ══ TAB: PER PEGAWAI ══ -->
    <div class="tab-pane fade p-0" id="tabPegawai" role="tabpanel">
      <div class="d-flex align-items-center justify-content-end p-2 border-bottom">
        <button class="btn btn-success btn-sm" onclick="downloadPegawaiTable()">
          <i class="fa-solid fa-file-excel me-1"></i> Download Excel
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover tbl-rekap mb-0" id="tblDetail">
          <thead class="table-light" style="font-size:.72rem">
            <tr>
              <th rowspan="2" class="text-center align-middle">#</th>
              <th rowspan="2" class="align-middle" style="min-width:140px">Nama / NIP</th>
              <th rowspan="2" class="text-center align-middle">Jenis</th>
              <th rowspan="2" class="text-center align-middle">Gol</th>
              <th rowspan="2" class="align-middle">Eselon / Jabatan</th>
              <th rowspan="2" class="align-middle">OPD</th>
              <th colspan="12" class="text-center" style="background:#dbeafe;color:#1e40af">KOMPONEN GAJI (001–012)</th>
              <th rowspan="2" class="text-end align-middle" style="background:#fef9c3;color:#78350f;min-width:75px">Total Bruto</th>
              <th colspan="7" class="text-center" style="background:#fce7f3;color:#9d174d">POTONGAN &amp; PENYETORAN</th>
              <th rowspan="2" class="text-end align-middle" style="background:#fce7f3;color:#9d174d;min-width:75px">Total Pot.</th>
              <th rowspan="2" class="text-end align-middle" style="background:#d1fae5;color:#065f46;min-width:75px">Gaji Bersih</th>
              <th colspan="2" class="text-center" style="background:#dcfce7;color:#14532d">TPP</th>
              <th rowspan="2" class="text-end align-middle" style="background:#dcfce7;color:#14532d;min-width:65px">TPP Bersih</th>
              <th rowspan="2" class="text-end align-middle fw-bold" style="background:#ede9fe;color:#4c1d95;min-width:75px">Total THP</th>
              <th rowspan="2" class="text-center align-middle">Info</th>
            </tr>
            <tr>
              <th class="text-end">001<br>Gaji Pokok</th>
              <th class="text-end">002<br>T.Keluarga</th>
              <th class="text-end">003<br>T.Jabatan</th>
              <th class="text-end">004<br>T.Fungsional</th>
              <th class="text-end">005<br>T.Fung.Umum</th>
              <th class="text-end">006<br>T.Beras</th>
              <th class="text-end">007<br>T.PPh/Khusus</th>
              <th class="text-end">008<br>Pembulatan</th>
              <th class="text-end">009<br>BPJS Empl</th>
              <th class="text-end">010<br>JKK</th>
              <th class="text-end">011<br>JKM</th>
              <th class="text-end">012<br>Tapera</th>
              <th class="text-end">009 Peg<br>BPJS (1%)</th>
              <th class="text-end">013<br>Pensiun</th>
              <th class="text-end">013<br>JHT</th>
              <th class="text-end">007<br>PPh DTP</th>
              <th class="text-end">009 Empl<br>BPJS (4%)</th>
              <th class="text-end">010<br>JKK</th>
              <th class="text-end">011<br>JKM</th>
              <th class="text-end">TPP<br>Nominal</th>
              <th class="text-end">BPJS TPP<br>Peg (1%)</th>
            </tr>
          </thead>
          <tbody id="tblDetailBody"></tbody>
          <tfoot class="table-light fw-bold" id="tblDetailFoot"></tfoot>
        </table>
      </div>
    </div>

  </div><!-- /tab-content main -->
</div><!-- /rekapResult -->

<script>
var HITUNG_URL = '<?= $hitung_url ?>';
var BLN_NAMA   = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

// Ringkasan: baris per rekening dengan DARI GAJI dan DARI TPP
var REK_RINGKASAN = [
  { type:'group', rek:'5.1.01.01', lbl:'5.1.01.01   Belanja Gaji dan Tunjangan ASN' },
  { rek:'5.1.01.01.001', lbl:'Belanja Gaji Pokok ASN',
    gaji:function(t){return t.gaji_pokok||0;}, tpp:function(t){return 0;} },
  { rek:'5.1.01.01.002', lbl:'Belanja Tunjangan Keluarga ASN',
    gaji:function(t){return t.t_keluarga||0;}, tpp:function(t){return 0;} },
  { rek:'5.1.01.01.003', lbl:'Belanja Tunjangan Jabatan ASN',
    gaji:function(t){return t.t_jabatan_str||0;}, tpp:function(t){return 0;} },
  { rek:'5.1.01.01.004', lbl:'Belanja Tunjangan Fungsional ASN',
    gaji:function(t){return t.t_jabatan_fung||0;}, tpp:function(t){return 0;} },
  { rek:'5.1.01.01.005', lbl:'Belanja Tunjangan Fungsional Umum ASN',
    gaji:function(t){return t.t_jabatan_umum||0;}, tpp:function(t){return 0;} },
  { rek:'5.1.01.01.006', lbl:'Belanja Tunjangan Beras ASN',
    gaji:function(t){return t.t_pangan||0;}, tpp:function(t){return 0;} },
  { rek:'5.1.01.01.007', lbl:'Belanja Tunjangan PPh/Tunjangan Khusus ASN',
    gaji:function(t){return (t.t_khusus||0)+(t.bel_pph21||0);},
    tpp:function(t){return t.bel_pph21_tpp||0;} },
  { rek:'5.1.01.01.008', lbl:'Belanja Pembulatan Gaji ASN',
    gaji:function(t){return t.t_pembulatan||0;}, tpp:function(t){return 0;} },
  { rek:'5.1.01.01.009', lbl:'Belanja Iuran Jaminan Kesehatan ASN',
    gaji:function(t){return t.bel_bpjs_gaji||0;},
    tpp:function(t){return t.bel_bpjs_tpp||0;} },
  { rek:'5.1.01.01.010', lbl:'Belanja Iuran Jaminan Kecelakaan Kerja ASN',
    gaji:function(t){return t.bel_jkk||0;}, tpp:function(t){return 0;} },
  { rek:'5.1.01.01.011', lbl:'Belanja Iuran Jaminan Kematian ASN',
    gaji:function(t){return t.bel_jkm||0;}, tpp:function(t){return 0;} },
  { rek:'5.1.01.01.012', lbl:'Belanja Iuran Simpanan Peserta Tabungan Perumahan Rakyat ASN',
    gaji:function(t){return 0;}, tpp:function(t){return 0;} },
  { type:'subtotal_group', lbl:'Subtotal Belanja Gaji dan Tunjangan (5.1.01.01)' },
  { type:'spacer' },
  { type:'group', rek:'5.1.01.02', lbl:'5.1.01.02   Belanja Tambahan Penghasilan ASN' },
  { rek:'5.1.01.02.001', lbl:'Belanja Tambahan Penghasilan berdasarkan Beban Kerja ASN',
    gaji:function(t){return 0;}, tpp:function(t){return t.tpp||0;} },
  { type:'subtotal_group', lbl:'Subtotal Belanja Tambahan Penghasilan (5.1.01.02)' },
  { type:'spacer' },
  { type:'jumlah', lbl:'JUMLAH' },
];

var _ringTotals = null; // stored for a# cadangan reactivity

function updateCadanganGaji() {
  var pct  = parseFloat(document.getElementById('gCadPct').value) || 0;
  var mult = 1 + pct / 100;
  document.querySelectorAll('#tblRingkasan tbody td[data-orig]').forEach(function(td) {
    var v = parseInt(td.dataset.orig) || 0;
    td.textContent = v ? Math.round(v * mult).toLocaleString('id-ID') : '—';
  });
  var el = document.getElementById('gCadResult');
  if (!el) return;
  if (pct > 0 && _ringTotals) {
    var totA = _ringTotals.totAll;
    var buf  = Math.round(totA * pct / 100);
    el.innerHTML = '<div class="d-flex flex-wrap gap-3 align-items-center" style="font-size:.78rem">'
      + '<span class="badge bg-warning text-dark fw-semibold">a# '+pct+'% aktif — nilai tabel sudah disesuaikan</span>'
      + '<span class="text-muted">Total asal: <b>Rp '+totA.toLocaleString('id-ID')+'</b>'
      + ' &rarr; Proyeksi: <b style="color:#4c1d95">Rp '+Math.round(totA*mult).toLocaleString('id-ID')+'</b>'
      + ' <span style="color:#dc2626">(+Rp '+buf.toLocaleString('id-ID')+')</span></span>'
      + '</div>';
  } else {
    el.innerHTML = '<span class="text-muted" style="font-size:.75rem">Masukkan a# &gt; 0 — nilai semua rekening akan dikalikan (1 + a#)</span>';
  }
}

function renderRingkasan(res) {
  var tPNS  = res.total_pns  || {};
  var tPPPK = res.total_pppk || {};
  var rows  = res.rows || [];
  var pnsCount  = rows.filter(function(h){ return (h.pegawai.jenis||'').toUpperCase()==='PNS'; }).length;
  var pppkCount = rows.filter(function(h){ return (h.pegawai.jenis||'').toUpperCase()==='PPPK'; }).length;
  var rh = function(n) { return n ? Math.round(n).toLocaleString('id-ID') : '—'; };

  // Pre-compute grand totals
  var gt_pns  = {g:0,t:0}, gt_pppk = {g:0,t:0};
  var grp_pns = {g:0,t:0}, grp_pppk = {g:0,t:0};
  REK_RINGKASAN.forEach(function(r) {
    if (!r.gaji) return;
    gt_pns.g  += Math.round(r.gaji(tPNS));  gt_pns.t  += Math.round(r.tpp(tPNS));
    gt_pppk.g += Math.round(r.gaji(tPPPK)); gt_pppk.t += Math.round(r.tpp(tPPPK));
  });

  // Store for a# cadangan
  _ringTotals = {
    totG:   gt_pns.g  + gt_pppk.g,
    totT:   gt_pns.t  + gt_pppk.t,
    totAll: gt_pns.g + gt_pns.t + gt_pppk.g + gt_pppk.t
  };

  // Bersih THP breakdown
  var pnsBersihG  = Math.round((tPNS.bersih  || 0));
  var pnsBersihT  = Math.round((tPNS.tpp     || 0) - (tPNS.pot_bpjs_tpp_peg  || 0));
  var pppkBersihG = Math.round((tPPPK.bersih || 0));
  var pppkBersihT = Math.round((tPPPK.tpp    || 0) - (tPPPK.pot_bpjs_tpp_peg || 0));
  var totBersihG  = pnsBersihG + pppkBersihG;
  var totBersihT  = pnsBersihT + pppkBersihT;
  var totBersih   = totBersihG + totBersihT;

  // KPI bar — 4 cards
  $('#ringkasanKpi').html(
    '<div class="row g-2">'
    // PNS card
    +'<div class="col-12 col-sm-6 col-xl-3">'
    +'<div class="rounded p-3 h-100" style="background:#dbeafe;border-left:4px solid #1e40af">'
    +'<div class="fw-bold mb-2" style="color:#1e40af;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em">'
    +'PNS &nbsp;<span class="fw-normal">'+pnsCount+' orang</span></div>'
    +'<div class="d-flex justify-content-between" style="font-size:.78rem">'
    +'<span style="color:#64748b">Belanja Gaji</span>'
    +'<span class="fw-semibold" style="color:#1e3a8a">Rp '+rh(gt_pns.g)+'</span></div>'
    +'<div class="d-flex justify-content-between" style="font-size:.78rem">'
    +'<span style="color:#64748b">Belanja TPP</span>'
    +'<span class="fw-semibold" style="color:#1e3a8a">Rp '+rh(gt_pns.t)+'</span></div>'
    +'<div class="d-flex justify-content-between mt-1 pt-1 border-top" style="font-size:.8rem">'
    +'<span style="color:#1e40af;font-weight:600">Total</span>'
    +'<span style="color:#1e40af;font-weight:700">Rp '+rh(gt_pns.g+gt_pns.t)+'</span></div>'
    +'</div></div>'
    // PPPK card
    +'<div class="col-12 col-sm-6 col-xl-3">'
    +'<div class="rounded p-3 h-100" style="background:#fef3c7;border-left:4px solid #92400e">'
    +'<div class="fw-bold mb-2" style="color:#92400e;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em">'
    +'PPPK &nbsp;<span class="fw-normal">'+pppkCount+' orang</span></div>'
    +'<div class="d-flex justify-content-between" style="font-size:.78rem">'
    +'<span style="color:#64748b">Belanja Gaji</span>'
    +'<span class="fw-semibold" style="color:#78350f">Rp '+rh(gt_pppk.g)+'</span></div>'
    +'<div class="d-flex justify-content-between" style="font-size:.78rem">'
    +'<span style="color:#64748b">Belanja TPP</span>'
    +'<span class="fw-semibold" style="color:#78350f">Rp '+rh(gt_pppk.t)+'</span></div>'
    +'<div class="d-flex justify-content-between mt-1 pt-1 border-top" style="font-size:.8rem">'
    +'<span style="color:#92400e;font-weight:600">Total</span>'
    +'<span style="color:#92400e;font-weight:700">Rp '+rh(gt_pppk.g+gt_pppk.t)+'</span></div>'
    +'</div></div>'
    // Total Belanja card
    +'<div class="col-12 col-sm-6 col-xl-3">'
    +'<div class="rounded p-3 h-100" style="background:#f1f5f9;border-left:4px solid #475569">'
    +'<div class="fw-bold mb-2" style="color:#475569;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em">Total Belanja</div>'
    +'<div class="d-flex justify-content-between" style="font-size:.78rem">'
    +'<span style="color:#64748b">Dari Gaji</span>'
    +'<span class="fw-semibold" style="color:#334155">Rp '+rh(_ringTotals.totG)+'</span></div>'
    +'<div class="d-flex justify-content-between" style="font-size:.78rem">'
    +'<span style="color:#64748b">Dari TPP</span>'
    +'<span class="fw-semibold" style="color:#334155">Rp '+rh(_ringTotals.totT)+'</span></div>'
    +'<div class="d-flex justify-content-between mt-1 pt-1 border-top" style="font-size:.8rem">'
    +'<span style="color:#475569;font-weight:600">Grand Total</span>'
    +'<span style="color:#0f172a;font-weight:700">Rp '+rh(_ringTotals.totAll)+'</span></div>'
    +'</div></div>'
    // Bersih THP card
    +'<div class="col-12 col-sm-6 col-xl-3">'
    +'<div class="rounded p-3 h-100" style="background:#dcfce7;border-left:4px solid #065f46">'
    +'<div class="fw-bold mb-2" style="color:#065f46;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em">Bersih THP</div>'
    +'<div class="d-flex justify-content-between" style="font-size:.78rem">'
    +'<span style="color:#64748b">Bersih Gaji</span>'
    +'<span class="fw-semibold" style="color:#064e3b">Rp '+rh(totBersihG)+'</span></div>'
    +'<div class="d-flex justify-content-between" style="font-size:.78rem">'
    +'<span style="color:#64748b">Bersih TPP</span>'
    +'<span class="fw-semibold" style="color:#064e3b">Rp '+rh(totBersihT)+'</span></div>'
    +'<div class="d-flex justify-content-between mt-1 pt-1 border-top" style="font-size:.8rem">'
    +'<span style="color:#065f46;font-weight:600">Total THP</span>'
    +'<span style="color:#065f46;font-weight:700">Rp '+rh(totBersih)+'</span></div>'
    +'</div></div>'
    +'</div>'
  );

  // Build table rows (with subtotal_group support)
  grp_pns  = {g:0,t:0};
  grp_pppk = {g:0,t:0};
  var html = '';
  REK_RINGKASAN.forEach(function(r) {
    if (r.type === 'spacer') {
      html += '<tr><td colspan="10" style="padding:3px;background:#f8fafc;border-color:#e2e8f0"></td></tr>';
      return;
    }
    if (r.type === 'group') {
      grp_pns  = {g:0,t:0};
      grp_pppk = {g:0,t:0};
      html += '<tr style="background:#1e3a8a;color:#e0e7ff">'
        +'<td colspan="10" style="padding:8px 14px;font-weight:700;font-size:.78rem;letter-spacing:.06em;text-transform:uppercase">'+esc(r.lbl)+'</td></tr>';
      return;
    }
    if (r.type === 'subtotal_group') {
      var sg=grp_pns.g, st=grp_pns.t, sppg=grp_pppk.g, sppt=grp_pppk.t;
      html += '<tr style="background:#dbeafe;color:#1e3a8a;font-weight:600;font-size:.77rem;border-top:2px solid #93c5fd;border-bottom:2px solid #93c5fd">'
        +'<td style="padding:6px 14px 6px 22px">Σ&nbsp; '+esc(r.lbl)+'</td>'
        +'<td class="text-end" data-orig="'+sg+'">'+rh(sg)+'</td>'
        +'<td class="text-end" data-orig="'+st+'">'+rh(st)+'</td>'
        +'<td class="text-end fw-bold" data-orig="'+(sg+st)+'" style="background:#bfdbfe;color:#1e40af">'+rh(sg+st)+'</td>'
        +'<td class="text-end" data-orig="'+sppg+'">'+rh(sppg)+'</td>'
        +'<td class="text-end" data-orig="'+sppt+'">'+rh(sppt)+'</td>'
        +'<td class="text-end fw-bold" data-orig="'+(sppg+sppt)+'" style="background:#fde68a;color:#92400e">'+rh(sppg+sppt)+'</td>'
        +'<td class="text-end" data-orig="'+(sg+sppg)+'">'+rh(sg+sppg)+'</td>'
        +'<td class="text-end" data-orig="'+(st+sppt)+'">'+rh(st+sppt)+'</td>'
        +'<td class="text-end fw-bold" data-orig="'+(sg+st+sppg+sppt)+'" style="background:#ddd6fe;color:#4c1d95">'+rh(sg+st+sppg+sppt)+'</td>'
        +'</tr>';
      return;
    }
    if (r.type === 'jumlah') {
      var pg=gt_pns.g, pt=gt_pns.t, ppg=gt_pppk.g, ppt=gt_pppk.t;
      html += '<tr style="background:#0f172a;color:#fff;font-weight:700;border-top:3px solid #4c1d95">'
        +'<td style="padding:10px 14px;font-size:.85rem;letter-spacing:.04em">JUMLAH</td>'
        +'<td class="text-end" data-orig="'+pg+'" style="background:#1e3a8a">'+rh(pg)+'</td>'
        +'<td class="text-end" data-orig="'+pt+'" style="background:#1e3a8a">'+rh(pt)+'</td>'
        +'<td class="text-end" data-orig="'+(pg+pt)+'" style="background:#1d4ed8">'+rh(pg+pt)+'</td>'
        +'<td class="text-end" data-orig="'+ppg+'" style="background:#78350f">'+rh(ppg)+'</td>'
        +'<td class="text-end" data-orig="'+ppt+'" style="background:#78350f">'+rh(ppt)+'</td>'
        +'<td class="text-end" data-orig="'+(ppg+ppt)+'" style="background:#b45309">'+rh(ppg+ppt)+'</td>'
        +'<td class="text-end" data-orig="'+(pg+ppg)+'" style="background:#3730a3">'+rh(pg+ppg)+'</td>'
        +'<td class="text-end" data-orig="'+(pt+ppt)+'" style="background:#3730a3">'+rh(pt+ppt)+'</td>'
        +'<td class="text-end" data-orig="'+(pg+pt+ppg+ppt)+'" style="background:#5b21b6">'+rh(pg+pt+ppg+ppt)+'</td>'
        +'</tr>';
      return;
    }
    // Regular row
    var pg=Math.round(r.gaji(tPNS)), pt=Math.round(r.tpp(tPNS));
    var ppg=Math.round(r.gaji(tPPPK)), ppt=Math.round(r.tpp(tPPPK));
    var tg=pg+ppg, tt=pt+ppt;
    grp_pns.g+=pg;  grp_pns.t+=pt;
    grp_pppk.g+=ppg; grp_pppk.t+=ppt;
    var isZ=(pg+pt+ppg+ppt===0);
    var zs=isZ?'color:#b0b8c8':'';
    html += '<tr style="'+zs+'">'
      +'<td style="padding:5px 5px 5px 28px;color:#374151">'
      +'<span style="font-family:monospace;font-size:.67rem;background:#e8eaf6;color:#3949ab;'
      +'padding:1px 5px;border-radius:3px;margin-right:6px">'+esc(r.rek)+'</span>'
      +esc(r.lbl)+'</td>'
      +'<td class="text-end" data-orig="'+pg+'">'+rh(pg)+'</td>'
      +'<td class="text-end" data-orig="'+pt+'">'+rh(pt)+'</td>'
      +'<td class="text-end fw-semibold" data-orig="'+(pg+pt)+'"'+(isZ?'':' style="background:#eff6ff;color:#1e40af"')+'>'+rh(pg+pt)+'</td>'
      +'<td class="text-end" data-orig="'+ppg+'">'+rh(ppg)+'</td>'
      +'<td class="text-end" data-orig="'+ppt+'">'+rh(ppt)+'</td>'
      +'<td class="text-end fw-semibold" data-orig="'+(ppg+ppt)+'"'+(isZ?'':' style="background:#fef9c3;color:#92400e"')+'>'+rh(ppg+ppt)+'</td>'
      +'<td class="text-end" data-orig="'+tg+'">'+rh(tg)+'</td>'
      +'<td class="text-end" data-orig="'+tt+'">'+rh(tt)+'</td>'
      +'<td class="text-end fw-semibold" data-orig="'+(tg+tt)+'"'+(isZ?'':' style="background:#f5f3ff;color:#4c1d95"')+'>'+rh(tg+tt)+'</td>'
      +'</tr>';
  });
  $('#ringkasanBody').html(html);

  // Wire a# input (once — idempotent via flag)
  var inp = document.getElementById('gCadPct');
  if (inp && !inp._wired) {
    inp._wired = true;
    inp.addEventListener('input', updateCadanganGaji);
  }
  updateCadanganGaji();
}

// Rekening Gaji (urut 001→013, suffix .00001/.00002 ditambah saat render per tab)
var REK_GAJI = [
  { rek:'5.1.01.01.001', lbl:'Belanja Gaji Pokok ASN',                                            key:'gaji_pokok',        neg:false },
  { rek:'5.1.01.01.002', lbl:'Belanja Tunjangan Keluarga ASN',                                    key:'t_keluarga',        neg:false },
  { rek:'5.1.01.01.003', lbl:'Belanja Tunjangan Jabatan ASN',                                     key:'t_jabatan_str',     neg:false },
  { rek:'5.1.01.01.004', lbl:'Belanja Tunjangan Fungsional ASN',                                  key:'t_jabatan_fung',    neg:false },
  { rek:'5.1.01.01.005', lbl:'Belanja Tunjangan Fungsional Umum ASN',                             key:'t_jabatan_umum',    neg:false },
  { rek:'5.1.01.01.006', lbl:'Belanja Tunjangan Beras ASN',                                       key:'t_pangan',          neg:false },
  // 007 = t_khusus + PPh DTP (full rekening obligation)
  { rek:'5.1.01.01.007', lbl:'Belanja Tunjangan PPh/Tunjangan Khusus ASN',
    compute: function(t) { return (t.t_khusus||0)+(t.bel_pph21||0); },                                                     neg:false },
  { rek:'5.1.01.01.008', lbl:'Belanja Pembulatan Gaji ASN',                                       key:'t_pembulatan',      neg:false },
  { rek:'5.1.01.01.009', lbl:'Belanja Iuran Jaminan Kesehatan ASN',                               key:'bel_bpjs_gaji',     neg:false },
  { rek:'5.1.01.01.010', lbl:'Belanja Iuran Jaminan Kecelakaan Kerja ASN',                        key:'bel_jkk',           neg:false },
  { rek:'5.1.01.01.011', lbl:'Belanja Iuran Jaminan Kematian ASN',                                key:'bel_jkm',           neg:false },
  { rek:'5.1.01.01.012', lbl:'Belanja Iuran Simpanan Peserta Tapera ASN',                         key:'bel_tapera',        neg:false },
  { type:'subtotal', lbl:'Total Bruto Gaji', compute: function(t) {
      return (t.gaji_pokok||0)+(t.t_keluarga||0)+(t.t_jabatan_str||0)+(t.t_jabatan_fung||0)
            +(t.t_jabatan_umum||0)+(t.t_khusus||0)+(t.t_pangan||0)+(t.t_pembulatan||0)
            +(t.bel_pph21||0)+(t.bel_bpjs_gaji||0)+(t.bel_jkk||0)+(t.bel_jkm||0)+(t.bel_tapera||0);
  }},
  { rek:'5.1.01.01.009', lbl:'BPJS Kesehatan Gaji ASN — Pegawai (1%)',                            key:'pot_bpjs_kes',      neg:false, noSum:true },
  { rek:'5.1.01.01.013', lbl:'Taspen — Iuran Pensiun ASN (4,75%)',                                key:'pot_pensiun_peg',   neg:false, noSum:true },
  { rek:'5.1.01.01.013', lbl:'Taspen — JHT ASN (3,25%)',                                          key:'pot_jht_taspen',   neg:false, noSum:true },
  { rek:'5.1.01.01.007', lbl:'Tunjangan PPh 21 ASN — DTP (disetor ke KPP)',                       key:'bel_pph21',         neg:false, noSum:true },
  { rek:'5.1.01.01.009', lbl:'BPJS Kesehatan ASN — Pemberi Kerja (4%) (disetor ke BPJS)',         key:'bel_bpjs_gaji',     neg:false, noSum:true },
  { rek:'5.1.01.01.010', lbl:'Iuran JKK ASN — Pemberi Kerja (disetor)',                           key:'bel_jkk',           neg:false, noSum:true },
  { rek:'5.1.01.01.011', lbl:'Iuran JKM ASN — Pemberi Kerja (disetor)',                           key:'bel_jkm',           neg:false, noSum:true },
  { type:'subtotal-pot', lbl:'Total Potongan & Penyetoran', compute: function(t) {
      return (t.pot_bpjs_kes||0)+(t.pot_pensiun_peg||0)+(t.pot_jht_taspen||0)
            +(t.pot_jht||0)+(t.pot_jp||0)
            +(t.bel_pph21||0)+(t.bel_bpjs_gaji||0)+(t.bel_jkk||0)+(t.bel_jkm||0);
  }},
  { type:'bersih', lbl:'Gaji Bersih (Diterima Pegawai)', compute: function(t) {
      var bruto = (t.gaji_pokok||0)+(t.t_keluarga||0)+(t.t_jabatan_str||0)+(t.t_jabatan_fung||0)
                 +(t.t_jabatan_umum||0)+(t.t_khusus||0)+(t.t_pangan||0)+(t.t_pembulatan||0)
                 +(t.bel_pph21||0)+(t.bel_bpjs_gaji||0)+(t.bel_jkk||0)+(t.bel_jkm||0);
      var pot = (t.pot_bpjs_kes||0)+(t.pot_pensiun_peg||0)+(t.pot_jht_taspen||0)
               +(t.pot_jht||0)+(t.pot_jp||0)
               +(t.bel_pph21||0)+(t.bel_bpjs_gaji||0)+(t.bel_jkk||0)+(t.bel_jkm||0);
      return bruto - pot;
  }},
];

// Rekening TPP (urut ascending: TPP dulu, lalu DTP items, lalu bersih)
var REK_TPP = [
  { rek:'5.1.01.02.001', lbl:'Belanja Tambahan Penghasilan berdasarkan Beban Kerja ASN',         key:'tpp',              neg:false },
  { rek:'5.1.01.01.007', lbl:'Belanja Tunjangan PPh/Tunjangan Khusus ASN — TPP (DTP)',          key:'bel_pph21_tpp',    neg:false },
  { rek:'5.1.01.01.009', lbl:'Belanja Iuran Jaminan Kesehatan ASN — TPP Pemberi Kerja (4%)',    key:'bel_bpjs_tpp',     neg:false },
  { type:'subtotal', lbl:'Bruto Anggaran TPP', compute: function(t) {
      return (t.tpp||0) + (t.bel_pph21_tpp||0) + (t.bel_bpjs_tpp||0);
  }},
  { rek:'5.1.01.01.009', lbl:'Belanja Iuran Jaminan Kesehatan ASN — TPP Pegawai (1%) [dipotong]', key:'pot_bpjs_tpp_peg', neg:false },
  { type:'bersih', lbl:'Bersih TPP (Diterima Pegawai)', compute: function(t) {
      return (t.tpp||0) - (t.pot_bpjs_tpp_peg||0);
  }},
];

function rupiah(n) {
  if (n === undefined || n === null || n === '') return '—';
  n = Number(n);
  return n < 0 ? '-Rp '+Math.abs(Math.round(n)).toLocaleString('id-ID') : 'Rp '+Math.round(n).toLocaleString('id-ID');
}
function rupiahPlain(n) {
  if (!n) return '0';
  return Math.round(n).toString();
}
function esc(v) { return v ? $('<div>').text(String(v)).html() : '—'; }

var GOL_ORDER = ['I/a','I/b','I/c','I/d','II/a','II/b','II/c','II/d','III/a','III/b','III/c','III/d',
  'IV/a','IV/b','IV/c','IV/d','IV/e',
  'I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII','XIII','XIV','XV','XVI','XVII'];
var ESELON_RANK = {'2A':1,'2B':2,'3A':3,'3B':4,'4A':5,'4B':6};
function eselonRank(e) { return ESELON_RANK[e] || 99; }
function golonganRank(g) { var i = GOL_ORDER.indexOf(g); return i < 0 ? -1 : i; }

$('#formRekap').on('submit', function(e) {
  e.preventDefault();
  $('#rekapResult').hide();
  $('#rekapLoading').show();
  $.ajax({
    url: HITUNG_URL, type: 'POST', data: $(this).serialize(), dataType: 'json',
    success: function(res) {
      $('#rekapLoading').hide();
      if (!res.ok) { alert(res.msg || 'Gagal'); return; }
      renderRekap(res);
      $('#rekapResult').show();
    },
    error: function() { $('#rekapLoading').hide(); alert('Terjadi kesalahan server'); }
  });
});

// Resolve computed keys from total object
function resolveKey(t, key) {
  return t[key] || 0;
}

// Render one rekening table; suffix = '.00001' (PNS) or '.00002' (PPPK); jenis = 'PNS'/'PPPK'
function renderRekTable(bodyId, footId, rekDef, totalObj, suffix, jenis) {
  suffix = suffix || '';
  jenis  = jenis  || 'ASN';
  var html = '', grandTotal = 0;
  rekDef.forEach(function(r) {
    var lbl = r.lbl.replace(/ASN/g, jenis);
    if (r.type === 'section') {
      html += '<tr><th colspan="3" style="background:#e8eaf6;color:#3730a3;font-size:.72rem;padding:5px 8px;letter-spacing:.06em">'+esc(r.lbl.toUpperCase())+'</th></tr>';
      return;
    }
    if (r.type === 'subtotal' || r.type === 'subtotal-pot' || r.type === 'bersih') {
      var v = r.compute ? r.compute(totalObj) : resolveKey(totalObj, r.key || '');
      var rowCls = r.type === 'bersih' ? 'row-bersih' : (r.type === 'subtotal-pot' ? 'row-subtotal-pot' : 'row-subtotal');
      html += '<tr class="'+rowCls+'">'
        + '<td colspan="2"><strong>'+esc(lbl)+'</strong></td>'
        + '<td class="text-end"><strong>'+(Math.round(v)===0 ? '—' : rupiah(v))+'</strong></td>'
        + '</tr>';
      return;
    }
    var v = r.compute ? r.compute(totalObj) : resolveKey(totalObj, r.key);
    if (!r.neg && !r.noSum) grandTotal += v;
    var isZero = (Math.round(v) === 0);
    var cls = isZero ? 'row-zero' : (r.neg ? 'row-potongan' : '');
    var valHtml;
    if (isZero) {
      valHtml = '<span class="text-muted">—</span>';
    } else if (r.neg) {
      valHtml = '<span>(' + rupiah(v) + ')</span>';
    } else {
      valHtml = rupiah(v);
    }
    html += '<tr class="'+cls+'">'
      + '<td><span class="rekening-badge">'+esc(r.rek+suffix)+'</span></td>'
      + '<td>'+esc(lbl)+'</td>'
      + '<td class="text-end">'+valHtml+'</td>'
      + '</tr>';
  });
  $('#'+bodyId).html(html);
  $('#'+footId).text(rupiah(grandTotal));
}

function renderRekap(res) {
  var t = res.total, tPNS = res.total_pns || {}, tPPPK = res.total_pppk || {};
  var jumlah = res.jumlah;

  // Periode label
  var periodeLabel = (res.ke_nama ? res.ke_nama + ' — ' : '') + BLN_NAMA[res.bulan] + ' ' + res.tahun;

  // Summary totals (combined)
  var bpjsTppPeg = t.pot_bpjs_tpp_peg || 0;
  var tppBersihTotal  = (t.tpp || 0) - bpjsTppPeg;
  var brutoGajiTotal  = (t.bruto || 0) - (t.tpp || 0);
  var potonganGajiTotal = (t.pot_bpjs_kes || 0) + (t.pot_pensiun || 0) + (t.pot_jht || 0) + (t.pot_jp || 0);
  var bersihGajiTotal = brutoGajiTotal - potonganGajiTotal;
  var totalBersih = bersihGajiTotal + tppBersihTotal;
  var totalBelanjaGaji = (t.gaji_pokok||0)+(t.t_keluarga||0)
    +(t.t_jabatan_str||0)+(t.t_jabatan_fung||0)+(t.t_jabatan_umum||0)
    +(t.t_khusus||0)+(t.t_pangan||0)+(t.t_pembulatan||0)
    +(t.bel_pph21||0)+(t.pot_bpjs_kes||0)+(t.bel_bpjs_gaji||0)+(t.bel_jkk||0)+(t.bel_jkm||0);
  var totalBelanjaTPP  = (t.tpp||0)+(t.bel_pph21_tpp||0)+(t.pot_bpjs_tpp_peg||0)+(t.bel_bpjs_tpp||0);

  var boxes = [
    { lbl:'Periode',             val: periodeLabel,              cls:'' },
    { lbl:'Pegawai Aktif',       val: jumlah+' orang',           cls:'' },
    { lbl:'PNS',                 val: (tPNS.pensiun_count !== undefined ? (res.rows.filter(function(h){return (h.pegawai.jenis||'').toUpperCase()==='PNS';}).length)+' org' : '—'), cls:'' },
    { lbl:'PPPK',                val: (tPPPK.pensiun_count !== undefined ? (res.rows.filter(function(h){return (h.pegawai.jenis||'').toUpperCase()==='PPPK';}).length)+' org' : '—'), cls:'' },
    { lbl:'Total Bersih Gaji',   val: rupiah(bersihGajiTotal),   cls:'primary' },
    { lbl:'Total TPP Bersih',    val: rupiah(tppBersihTotal),    cls:'primary' },
    { lbl:'Total THP',           val: rupiah(totalBersih),       cls:'' },
    { lbl:'Total Belanja Gaji',  val: rupiah(totalBelanjaGaji),  cls:'' },
    { lbl:'Total Belanja TPP',   val: rupiah(totalBelanjaTPP),   cls:'' },
  ];
  var bhtml = '';
  boxes.forEach(function(b) {
    bhtml += '<div class="col-6 col-lg-auto flex-grow-1"><div class="sum-box '+b.cls+'"><div class="lbl">'+b.lbl+'</div><div class="val">'+b.val+'</div></div></div>';
  });
  $('#summaryBoxes').html(bhtml);

  // Render ringkasan tab
  renderRingkasan(res);

  // Render 4 rekening sub-tabs (PNS = .00001, PPPK = .00002)
  renderRekTable('bdyGajiPNS',  'totGajiPNS',  REK_GAJI, tPNS,  '.00001', 'PNS');
  renderRekTable('bdyGajiPPPK', 'totGajiPPPK', REK_GAJI, tPPPK, '.00002', 'PPPK');
  renderRekTable('bdyTPPPNS',   'totTPPPNS',   REK_TPP,  tPNS,  '.00001', 'PNS');
  renderRekTable('bdyTPPPPPK',  'totTPPPPPK',  REK_TPP,  tPPPK, '.00002', 'PPPK');

  // Per Pegawai tab
  renderPegawai(res);
}

var JENIS_RANK = {PNS:1, PPPK:2, NON_ASN:3};

function renderPegawai(res) {
  var sorted = res.rows.slice().sort(function(a, b) {
    var pa = a.pegawai, pb = b.pegawai;
    var opdA = pa.kode_opd || pa.opd || '', opdB = pb.kode_opd || pb.opd || '';
    if (opdA < opdB) return -1; if (opdA > opdB) return 1;
    var ja = JENIS_RANK[(pa.jenis||'').toUpperCase()] || 99;
    var jb = JENIS_RANK[(pb.jenis||'').toUpperCase()] || 99;
    if (ja !== jb) return ja - jb;
    var ea = eselonRank(pa.eselon || ''), eb = eselonRank(pb.eselon || '');
    if (ea !== eb) return ea - eb;
    var ga = golonganRank(pa.golongan || ''), gb = golonganRank(pb.golongan || '');
    if (ga !== gb) return gb - ga;
    var ta = pa.tgl_lahir || '9999', tb = pb.tgl_lahir || '9999';
    if (ta < tb) return -1; if (ta > tb) return 1;
    return 0;
  });

  var dhtml = '';
  var ft = {
    gapok:0, tkel:0, tjabStr:0, tjabFung:0, tjabUmum:0, tpan:0, t007:0, tpemb:0,
    bpjsEmpl:0, jkk:0, jkm:0, bruto:0,
    bpjsPeg:0, pensiun:0, jht:0, pphDtp:0, bpjsEmplDis:0, jkkDis:0, jkmDis:0,
    totalPot:0, bersih:0,
    tpp:0, bpjsTppPeg:0, tppBersih:0, totalBersih:0
  };

  sorted.forEach(function(h, idx) {
    var p = h.pegawai, k = h.komponen;
    var bel = h.belanja || {}, iu = h.iuran || {};
    var tpp         = k.tpp || 0;
    var tpemb       = k.t_pembulatan || 0;
    var tjabStr     = k.t_jabatan_str || 0;
    var tjabFung    = k.t_jabatan_fung || 0;
    var tjabUmum    = k.t_jabatan_umum || 0;
    var tkhusus     = k.t_khusus || 0;
    var tkel        = (k.t_istri||0) + (k.t_anak||0);
    var pphDtp      = bel.pph21 || 0;
    var bpjsEmpl    = bel.bpjs_kes_employer || 0;
    var jkk         = bel.jkk || 0;
    var jkm         = bel.jkm || 0;
    var t007        = tkhusus + pphDtp; // combined rekening 007
    var bruto       = (k.gaji_pokok||0)+tkel+tjabStr+tjabFung+tjabUmum+tkhusus+(k.t_pangan||0)+tpemb
                     +pphDtp+bpjsEmpl+jkk+jkm;
    var bpjsPeg     = iu.bpjs_kes_pegawai || 0;
    var pensiunPeg  = iu.pensiun_pegawai || 0;
    var jhtTaspen   = iu.jht_taspen || 0;
    var jhtBpjs     = iu.jht || 0;
    var jp          = iu.jp || 0;
    var totalPot    = bpjsPeg + pensiunPeg + jhtTaspen + jhtBpjs + jp + pphDtp + bpjsEmpl + jkk + jkm;
    var bersihGaji  = bruto - totalPot;
    var bpjsTppPeg  = iu.bpjs_tpp_pegawai || (tpp > 0 ? Math.round(tpp * 0.01) : 0);
    var tppBersih   = tpp - bpjsTppPeg;
    var totalB      = bersihGaji + tppBersih;

    ft.gapok       += k.gaji_pokok || 0;
    ft.tkel        += tkel;
    ft.tjabStr     += tjabStr;
    ft.tjabFung    += tjabFung;
    ft.tjabUmum    += tjabUmum;
    ft.tpan        += k.t_pangan || 0;
    ft.t007        += t007;
    ft.tpemb       += tpemb;
    ft.bpjsEmpl    += bpjsEmpl;
    ft.jkk         += jkk;
    ft.jkm         += jkm;
    ft.bruto       += bruto;
    ft.bpjsPeg     += bpjsPeg;
    ft.pensiun     += pensiunPeg;
    ft.jht         += jhtTaspen;
    ft.pphDtp      += pphDtp;
    ft.bpjsEmplDis += bpjsEmpl;
    ft.jkkDis      += jkk;
    ft.jkmDis      += jkm;
    ft.totalPot    += totalPot;
    ft.bersih      += bersihGaji;
    ft.tpp         += tpp;
    ft.bpjsTppPeg  += bpjsTppPeg;
    ft.tppBersih   += tppBersih;
    ft.totalBersih += totalB;

    var chips = '';
    if (p.hari_kp !== null && p.hari_kp !== undefined && p.hari_kp >= 0 && p.hari_kp <= 180)
      chips += '<span class="peringatan-chip">KP ~'+p.hari_kp+' hr</span> ';
    if (p.sisa_bup !== null && p.sisa_bup !== undefined && p.sisa_bup <= 2)
      chips += '<span class="pensiun-chip">BUP '+p.bup+'th</span> ';
    if (p.kpp_aktif) chips += '<span class="pensiun-chip" title="Kenaikan Pangkat Pengabdian">KPP '+esc(p.golongan_asli)+'→'+esc(p.golongan)+'</span> ';
    if (p.kgb_info) chips += '<span class="peringatan-chip">'+esc(p.kgb_info)+'</span>';

    var eselon  = p.eselon ? '<span class="badge bg-secondary me-1" style="font-size:.65rem">'+esc(p.eselon)+'</span>' : '';
    var jabatan = p.jab_struktural || p.jab_fungsional || p.jab_penatausahaan || '—';
    var jenisBadge = (p.jenis||'').toUpperCase() === 'PNS'
      ? '<span class="badge bg-primary" style="font-size:.65rem">PNS</span>'
      : '<span class="badge bg-warning text-dark" style="font-size:.65rem">PPPK</span>';

    dhtml += '<tr>'
      +'<td class="text-center">'+(idx+1)+'</td>'
      +'<td><strong>'+esc(p.nama)+'</strong><br><small class="text-muted font-monospace">'+esc(p.nip||'—')+'</small></td>'
      +'<td>'+jenisBadge+'</td>'
      +'<td>'+esc(p.golongan)+'<br><small class="text-muted">MKG '+p.mkg+'</small></td>'
      +'<td>'+eselon+esc(jabatan)+'</td>'
      +'<td><small>'+esc(p.opd||'—')+'</small></td>'
      // komponen 001–012
      +'<td class="text-end">'+rupiah(k.gaji_pokok)+'</td>'
      +'<td class="text-end">'+rupiah(tkel)+'</td>'
      +'<td class="text-end">'+rupiah(tjabStr)+'</td>'
      +'<td class="text-end">'+rupiah(tjabFung)+'</td>'
      +'<td class="text-end">'+rupiah(tjabUmum)+'</td>'
      +'<td class="text-end">'+rupiah(k.t_pangan||0)+'</td>'
      +'<td class="text-end">'+rupiah(t007)+'</td>'
      +'<td class="text-end">'+rupiah(tpemb)+'</td>'
      +'<td class="text-end">'+rupiah(bpjsEmpl)+'</td>'
      +'<td class="text-end">'+rupiah(jkk)+'</td>'
      +'<td class="text-end">'+rupiah(jkm)+'</td>'
      +'<td class="text-end text-muted">—</td>'
      // Total Bruto
      +'<td class="text-end fw-semibold" style="background:#fef9c3">'+rupiah(bruto)+'</td>'
      // potongan
      +'<td class="text-end">'+rupiah(bpjsPeg)+'</td>'
      +'<td class="text-end">'+rupiah(pensiunPeg)+'</td>'
      +'<td class="text-end">'+rupiah(jhtTaspen)+'</td>'
      +'<td class="text-end">'+rupiah(pphDtp)+'</td>'
      +'<td class="text-end">'+rupiah(bpjsEmpl)+'</td>'
      +'<td class="text-end">'+rupiah(jkk)+'</td>'
      +'<td class="text-end">'+rupiah(jkm)+'</td>'
      // Total Potongan
      +'<td class="text-end fw-semibold" style="background:#fce7f3;color:#9d174d">'+rupiah(totalPot)+'</td>'
      // Gaji Bersih
      +'<td class="text-end fw-semibold" style="background:#d1fae5;color:#065f46">'+rupiah(bersihGaji)+'</td>'
      // TPP
      +'<td class="text-end">'+rupiah(tpp)+'</td>'
      +'<td class="text-end">'+rupiah(bpjsTppPeg)+'</td>'
      // TPP Bersih & Total THP
      +'<td class="text-end fw-semibold" style="color:#0d47a1">'+rupiah(tppBersih)+'</td>'
      +'<td class="text-end fw-bold" style="color:#5b21b6">'+rupiah(totalB)+'</td>'
      +'<td class="text-center">'+chips+'</td>'
      +'</tr>';
  });

  $('#tblDetailBody').html(dhtml || '<tr><td colspan="33" class="text-center text-muted py-3">Tidak ada data</td></tr>');
  $('#tblDetailFoot').html(
    '<tr><td colspan="6" class="text-end fw-bold">TOTAL</td>'
    // komponen 001–012
    +'<td class="text-end">'+rupiah(ft.gapok)+'</td>'
    +'<td class="text-end">'+rupiah(ft.tkel)+'</td>'
    +'<td class="text-end">'+rupiah(ft.tjabStr)+'</td>'
    +'<td class="text-end">'+rupiah(ft.tjabFung)+'</td>'
    +'<td class="text-end">'+rupiah(ft.tjabUmum)+'</td>'
    +'<td class="text-end">'+rupiah(ft.tpan)+'</td>'
    +'<td class="text-end">'+rupiah(ft.t007)+'</td>'
    +'<td class="text-end">'+rupiah(ft.tpemb)+'</td>'
    +'<td class="text-end">'+rupiah(ft.bpjsEmpl)+'</td>'
    +'<td class="text-end">'+rupiah(ft.jkk)+'</td>'
    +'<td class="text-end">'+rupiah(ft.jkm)+'</td>'
    +'<td class="text-end">—</td>'
    // Total Bruto
    +'<td class="text-end fw-bold" style="background:#fef9c3">'+rupiah(ft.bruto)+'</td>'
    // potongan
    +'<td class="text-end">'+rupiah(ft.bpjsPeg)+'</td>'
    +'<td class="text-end">'+rupiah(ft.pensiun)+'</td>'
    +'<td class="text-end">'+rupiah(ft.jht)+'</td>'
    +'<td class="text-end">'+rupiah(ft.pphDtp)+'</td>'
    +'<td class="text-end">'+rupiah(ft.bpjsEmplDis)+'</td>'
    +'<td class="text-end">'+rupiah(ft.jkkDis)+'</td>'
    +'<td class="text-end">'+rupiah(ft.jkmDis)+'</td>'
    // Total Potongan
    +'<td class="text-end fw-bold" style="background:#fce7f3;color:#9d174d">'+rupiah(ft.totalPot)+'</td>'
    // Gaji Bersih
    +'<td class="text-end fw-bold" style="background:#d1fae5;color:#065f46">'+rupiah(ft.bersih)+'</td>'
    // TPP
    +'<td class="text-end">'+rupiah(ft.tpp)+'</td>'
    +'<td class="text-end">'+rupiah(ft.bpjsTppPeg)+'</td>'
    // TPP Bersih & Total THP
    +'<td class="text-end" style="color:#0d47a1">'+rupiah(ft.tppBersih)+'</td>'
    +'<td class="text-end fw-bold" style="color:#5b21b6">'+rupiah(ft.totalBersih)+'</td>'
    +'<td></td></tr>'
  );
}

// ── Excel download helpers ──────────────────────────────────────────────────

function tableToCSV(tableEl) {
  var rows = tableEl.querySelectorAll('tr');
  var csv = [];
  rows.forEach(function(row) {
    var cols = row.querySelectorAll('th, td');
    var rowData = [];
    cols.forEach(function(col) {
      var txt = col.innerText.replace(/\r?\n/g, ' ').replace(/"/g, '""').trim();
      rowData.push('"' + txt + '"');
    });
    csv.push(rowData.join(','));
  });
  return csv.join('\r\n');
}

function downloadCSV(csv, filename) {
  var BOM = '﻿';
  var blob = new Blob([BOM + csv], { type: 'text/csv;charset=utf-8;' });
  var url  = URL.createObjectURL(blob);
  var a    = document.createElement('a');
  a.href = url; a.download = filename; a.style.display = 'none';
  document.body.appendChild(a); a.click();
  setTimeout(function() { document.body.removeChild(a); URL.revokeObjectURL(url); }, 500);
}

function downloadTable(tableId, label) {
  var tbl = document.getElementById(tableId);
  if (!tbl) return;
  var periode = ($('#summaryBoxes .sum-box').first().find('.val').text() || 'rekap').replace(/\s+/g,'_');
  downloadCSV(tableToCSV(tbl), label+'_'+periode+'.csv');
}

function downloadActiveRekTable() {
  var activePane = document.querySelector('#rekenSubTabs .nav-link.active');
  var target = activePane ? activePane.getAttribute('data-bs-target') : null;
  var tableMap = {
    '#rtGajiPNS':  { el:'tblGajiPNS',  name:'Gaji_PNS' },
    '#rtGajiPPPK': { el:'tblGajiPPPK', name:'Gaji_PPPK' },
    '#rtTPPPNS':   { el:'tblTPPPNS',   name:'TPP_PNS' },
    '#rtTPPPPPK':  { el:'tblTPPPPPK',  name:'TPP_PPPK' },
  };
  var info = tableMap[target];
  if (!info) { alert('Pilih tab terlebih dahulu'); return; }
  var tbl = document.getElementById(info.el);
  if (!tbl) return;
  var periode = ($('#summaryBoxes .sum-box').first().find('.val').text() || 'rekap').replace(/\s+/g,'_');
  downloadCSV(tableToCSV(tbl), 'Rekap_'+info.name+'_'+periode+'.csv');
}

function downloadPegawaiTable() {
  var tbl = document.getElementById('tblDetail');
  if (!tbl) return;
  var periode = ($('#summaryBoxes .sum-box').first().find('.val').text() || 'rekap').replace(/\s+/g,'_');
  downloadCSV(tableToCSV(tbl), 'Rekap_PerPegawai_'+periode+'.csv');
}
</script>
