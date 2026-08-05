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
.rek-tbl .row-subtotal td { background:#dbeafe; font-weight:700; color:#1e3a5f; }
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
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabRekening" type="button" role="tab">
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

    <!-- ══ TAB: PER REKENING ══ -->
    <div class="tab-pane fade show active p-3" id="tabRekening" role="tabpanel">

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
          <thead class="table-light">
            <tr>
              <th rowspan="2">#</th>
              <th rowspan="2">Nama / NIP</th>
              <th rowspan="2">Jenis</th>
              <th rowspan="2">Gol</th>
              <th rowspan="2">Eselon / Jabatan</th>
              <th rowspan="2">OPD</th>
              <th colspan="9" class="text-center" style="background:#dbeafe;color:#1e3a5f">Komponen Gaji</th>
              <th colspan="4" class="text-center" style="background:#fef9c3;color:#78350f">DTP + Iuran</th>
              <th colspan="2" class="text-center" style="background:#fce7f3;color:#9d174d">Potongan Peg</th>
              <th rowspan="2" class="text-end" style="background:#d1fae5;color:#065f46">Bersih Gaji</th>
              <th colspan="4" class="text-center" style="background:#dcfce7;color:#14532d">TPP</th>
              <th rowspan="2" class="text-end fw-semibold" style="background:#bbf7d0;color:#14532d">Bersih TPP</th>
              <th rowspan="2" class="text-end fw-bold" style="background:#ede9fe;color:#4c1d95">Total THP</th>
              <th rowspan="2" class="text-center">Info</th>
            </tr>
            <tr>
              <th class="text-end">Gaji Pokok</th>
              <th class="text-end">T.Kel</th>
              <th class="text-end">Jab Str</th>
              <th class="text-end">Jab Fung</th>
              <th class="text-end">Jab Umum</th>
              <th class="text-end">T.Khusus</th>
              <th class="text-end">T.Pangan</th>
              <th class="text-end">Pembulatan</th>
              <th class="text-end fw-semibold" style="background:#dbeafe;color:#1e3a5f">Bruto</th>
              <th class="text-end">PPh DTP</th>
              <th class="text-end">BPJS Empl 4%</th>
              <th class="text-end">JKK</th>
              <th class="text-end">JKM</th>
              <th class="text-end">BPJS Peg 1%</th>
              <th class="text-end">Pensiun/JHT</th>
              <th class="text-end">TPP</th>
              <th class="text-end">PPh TPP</th>
              <th class="text-end">BPJS TPP Empl</th>
              <th class="text-end">BPJS TPP Peg</th>
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

// Rekening Gaji (urut 001→011, suffix .00001/.00002 ditambah saat render per tab)
var REK_GAJI = [
  { rek:'5.1.01.01.001', lbl:'Gaji Pokok ASN',                              key:'gaji_pokok',     neg:false },
  { rek:'5.1.01.01.002', lbl:'Tunjangan Keluarga (Istri + Anak)',            key:'t_keluarga',     neg:false },
  { rek:'5.1.01.01.003', lbl:'Tunjangan Jabatan (Struktural)',               key:'t_jabatan_str',  neg:false },
  { rek:'5.1.01.01.004', lbl:'Tunjangan Fungsional',                         key:'t_jabatan_fung', neg:false },
  { rek:'5.1.01.01.005', lbl:'Tunjangan Fungsional Umum',                    key:'t_jabatan_umum', neg:false },
  { rek:'5.1.01.01.006', lbl:'Tunjangan Pangan / Beras',                     key:'t_pangan',       neg:false },
  { rek:'5.1.01.01.007', lbl:'Tunjangan Khusus',                             key:'t_khusus',       neg:false },
  { rek:'5.1.01.01.008', lbl:'Tunjangan Pembulatan',                         key:'t_pembulatan',   neg:false },
  { type:'subtotal', lbl:'Gaji Bruto (Komponen)', compute: function(t) {
      return (t.gaji_pokok||0)+(t.t_keluarga||0)+(t.t_jabatan_str||0)+(t.t_jabatan_fung||0)
            +(t.t_jabatan_umum||0)+(t.t_khusus||0)+(t.t_pangan||0)+(t.t_pembulatan||0);
  }},
  { rek:'5.1.01.01.007', lbl:'Tunjangan PPh Gaji — Ditanggung Pemerintah',  key:'bel_pph21',      neg:false },
  { rek:'5.1.01.01.009', lbl:'BPJS Kes Gaji — Pegawai (1%)',                key:'pot_bpjs_kes',   neg:false },
  { rek:'5.1.01.01.009', lbl:'BPJS Kes Gaji — Pemberi Kerja (4%)',          key:'bel_bpjs_gaji',  neg:false },
  { rek:'5.1.01.01.010', lbl:'Iuran JKK — Pemberi Kerja (0,24%)',           key:'bel_jkk',        neg:false },
  { rek:'5.1.01.01.011', lbl:'Iuran JKM — Pemberi Kerja (0,30%)',           key:'bel_jkm',        neg:false },
  { type:'bersih', lbl:'Bersih Gaji (Diterima Pegawai)', compute: function(t) {
      var bruto = (t.gaji_pokok||0)+(t.t_keluarga||0)+(t.t_jabatan_str||0)+(t.t_jabatan_fung||0)
                 +(t.t_jabatan_umum||0)+(t.t_khusus||0)+(t.t_pangan||0)+(t.t_pembulatan||0);
      return bruto - (t.pot_bpjs_kes||0) - (t.pot_pensiun||0) - (t.pot_jht||0) - (t.pot_jp||0);
  }},
];

// Rekening TPP (urut ascending: TPP dulu, lalu DTP items, lalu bersih)
var REK_TPP = [
  { rek:'5.1.01.02.001', lbl:'Tambahan Penghasilan Pegawai (TPP)',           key:'tpp',              neg:false },
  { rek:'5.1.01.01.007', lbl:'Tunjangan PPh TPP — Ditanggung Pemerintah',   key:'bel_pph21_tpp',    neg:false },
  { rek:'5.1.01.01.009', lbl:'BPJS Kes TPP — Pemberi Kerja (4%)',           key:'bel_bpjs_tpp',     neg:false },
  { type:'subtotal', lbl:'Bruto Anggaran TPP', compute: function(t) {
      return (t.tpp||0) + (t.bel_pph21_tpp||0) + (t.bel_bpjs_tpp||0);
  }},
  { rek:'5.1.01.01.009', lbl:'BPJS Kes TPP — Pegawai (1%) [dipotong dari TPP]', key:'pot_bpjs_tpp_peg', neg:false },
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

// Render one rekening table; suffix = '.00001' (PNS) or '.00002' (PPPK)
function renderRekTable(bodyId, footId, rekDef, totalObj, suffix) {
  suffix = suffix || '';
  var html = '', grandTotal = 0;
  rekDef.forEach(function(r) {
    if (r.type === 'subtotal' || r.type === 'bersih') {
      var v = r.compute ? r.compute(totalObj) : resolveKey(totalObj, r.key || '');
      var rowCls = r.type === 'bersih' ? 'row-bersih' : 'row-subtotal';
      html += '<tr class="'+rowCls+'">'
        + '<td colspan="2"><strong>'+esc(r.lbl)+'</strong></td>'
        + '<td class="text-end"><strong>'+(Math.round(v)===0 ? '—' : rupiah(v))+'</strong></td>'
        + '</tr>';
      return;
    }
    var v = resolveKey(totalObj, r.key);
    if (!r.neg) grandTotal += v;
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
      + '<td>'+esc(r.lbl)+'</td>'
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

  // Render 4 rekening sub-tabs (PNS = .00001, PPPK = .00002)
  renderRekTable('bdyGajiPNS',  'totGajiPNS',  REK_GAJI, tPNS,  '.00001');
  renderRekTable('bdyGajiPPPK', 'totGajiPPPK', REK_GAJI, tPPPK, '.00002');
  renderRekTable('bdyTPPPNS',   'totTPPPNS',   REK_TPP,  tPNS,  '.00001');
  renderRekTable('bdyTPPPPPK',  'totTPPPPPK',  REK_TPP,  tPPPK, '.00002');

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
    gapok:0, tkel:0, tjabStr:0, tjabFung:0, tjabUmum:0, tkhusus:0, tpan:0, tpemb:0, brutoGaji:0,
    pphDtp:0, bpjsEmpl:0, jkk:0, jkm:0, bpjsPeg:0, pensiun:0, bersihGaji:0,
    tpp:0, pphTppDtp:0, bpjsTppEmpl:0, bpjsTppPeg:0, tppBersih:0, totalBersih:0
  };

  sorted.forEach(function(h, idx) {
    var p = h.pegawai, k = h.komponen;
    var bel = h.belanja || {}, iu = h.iuran || {};
    var tpp       = k.tpp || 0;
    var tpemb     = k.t_pembulatan || 0;
    var tjabStr   = k.t_jabatan_str || 0;
    var tjabFung  = k.t_jabatan_fung || 0;
    var tjabUmum  = k.t_jabatan_umum || 0;
    var tkhusus   = k.t_khusus || 0;
    var tkel      = (k.t_istri||0) + (k.t_anak||0);
    var brutoGaji = (k.gaji_pokok||0) + tkel + tjabStr + tjabFung + tjabUmum + tkhusus + (k.t_pangan||0) + tpemb;
    var pphDtp    = bel.pph21 || 0;
    var bpjsEmpl  = bel.bpjs_kes_employer || 0;
    var jkk       = bel.jkk || 0;
    var jkm       = bel.jkm || 0;
    var bpjsPeg   = iu.bpjs_kes_pegawai || 0;
    var pensiun   = (iu.pensiun_pegawai||0) + (iu.jht_taspen||0) + (iu.jht||0) + (iu.jp||0);
    var bersihGaji = brutoGaji - bpjsPeg - pensiun;
    var pphTppDtp  = bel.pph21_tpp || 0;
    var bpjsTppEmpl = bel.bpjs_tpp || 0;
    var bpjsTppPeg = iu.bpjs_tpp_pegawai || (tpp > 0 ? Math.round(tpp * 0.01) : 0);
    var tppBersih  = tpp - bpjsTppPeg;
    var totalB     = bersihGaji + tppBersih;

    ft.gapok      += k.gaji_pokok || 0;
    ft.tkel       += tkel;
    ft.tjabStr    += tjabStr;
    ft.tjabFung   += tjabFung;
    ft.tjabUmum   += tjabUmum;
    ft.tkhusus    += tkhusus;
    ft.tpan       += k.t_pangan || 0;
    ft.tpemb      += tpemb;
    ft.brutoGaji  += brutoGaji;
    ft.pphDtp     += pphDtp;
    ft.bpjsEmpl   += bpjsEmpl;
    ft.jkk        += jkk;
    ft.jkm        += jkm;
    ft.bpjsPeg    += bpjsPeg;
    ft.pensiun    += pensiun;
    ft.bersihGaji += bersihGaji;
    ft.tpp        += tpp;
    ft.pphTppDtp  += pphTppDtp;
    ft.bpjsTppEmpl += bpjsTppEmpl;
    ft.bpjsTppPeg += bpjsTppPeg;
    ft.tppBersih  += tppBersih;
    ft.totalBersih += totalB;

    var chips = '';
    if (p.hari_kp !== null && p.hari_kp !== undefined && p.hari_kp >= 0 && p.hari_kp <= 180)
      chips += '<span class="peringatan-chip">KP ~'+p.hari_kp+' hr</span> ';
    if (p.sisa_bup !== null && p.sisa_bup !== undefined && p.sisa_bup <= 2)
      chips += '<span class="pensiun-chip">BUP '+p.bup+'th</span> ';
    if (p.kgb_info) chips += '<span class="peringatan-chip">'+esc(p.kgb_info)+'</span>';

    var eselon  = p.eselon ? '<span class="badge bg-secondary me-1" style="font-size:.65rem">'+esc(p.eselon)+'</span>' : '';
    var jabatan = p.jab_struktural || p.jab_fungsional || p.jab_penatausahaan || '—';
    var jenisBadge = (p.jenis||'').toUpperCase() === 'PNS'
      ? '<span class="badge bg-primary" style="font-size:.65rem">PNS</span>'
      : '<span class="badge bg-warning text-dark" style="font-size:.65rem">PPPK</span>';

    dhtml += '<tr>'
      +'<td>'+(idx+1)+'</td>'
      +'<td><strong>'+esc(p.nama)+'</strong><br><small class="text-muted font-monospace">'+esc(p.nip||'—')+'</small></td>'
      +'<td>'+jenisBadge+'</td>'
      +'<td>'+esc(p.golongan)+'<br><small class="text-muted">MKG '+p.mkg+'</small></td>'
      +'<td>'+eselon+esc(jabatan)+'</td>'
      +'<td><small>'+esc(p.opd||'—')+'</small></td>'
      +'<td class="text-end">'+rupiah(k.gaji_pokok)+'</td>'
      +'<td class="text-end">'+rupiah(tkel)+'</td>'
      +'<td class="text-end">'+rupiah(tjabStr)+'</td>'
      +'<td class="text-end">'+rupiah(tjabFung)+'</td>'
      +'<td class="text-end">'+rupiah(tjabUmum)+'</td>'
      +'<td class="text-end">'+rupiah(tkhusus)+'</td>'
      +'<td class="text-end">'+rupiah(k.t_pangan||0)+'</td>'
      +'<td class="text-end">'+rupiah(tpemb)+'</td>'
      +'<td class="text-end fw-semibold" style="background:#dbeafe">'+rupiah(brutoGaji)+'</td>'
      +'<td class="text-end">'+rupiah(pphDtp)+'</td>'
      +'<td class="text-end">'+rupiah(bpjsEmpl)+'</td>'
      +'<td class="text-end">'+rupiah(jkk)+'</td>'
      +'<td class="text-end">'+rupiah(jkm)+'</td>'
      +'<td class="text-end">'+rupiah(bpjsPeg)+'</td>'
      +'<td class="text-end">'+rupiah(pensiun)+'</td>'
      +'<td class="text-end fw-semibold" style="background:#d1fae5;color:#065f46">'+rupiah(bersihGaji)+'</td>'
      +'<td class="text-end">'+rupiah(tpp)+'</td>'
      +'<td class="text-end">'+rupiah(pphTppDtp)+'</td>'
      +'<td class="text-end">'+rupiah(bpjsTppEmpl)+'</td>'
      +'<td class="text-end">'+rupiah(bpjsTppPeg)+'</td>'
      +'<td class="text-end fw-semibold" style="color:#0d47a1">'+rupiah(tppBersih)+'</td>'
      +'<td class="text-end fw-bold" style="color:#5b21b6">'+rupiah(totalB)+'</td>'
      +'<td class="text-center">'+chips+'</td>'
      +'</tr>';
  });

  $('#tblDetailBody').html(dhtml || '<tr><td colspan="29" class="text-center text-muted py-3">Tidak ada data</td></tr>');
  $('#tblDetailFoot').html(
    '<tr><td colspan="6" class="text-end fw-bold">TOTAL</td>'
    +'<td class="text-end">'+rupiah(ft.gapok)+'</td>'
    +'<td class="text-end">'+rupiah(ft.tkel)+'</td>'
    +'<td class="text-end">'+rupiah(ft.tjabStr)+'</td>'
    +'<td class="text-end">'+rupiah(ft.tjabFung)+'</td>'
    +'<td class="text-end">'+rupiah(ft.tjabUmum)+'</td>'
    +'<td class="text-end">'+rupiah(ft.tkhusus)+'</td>'
    +'<td class="text-end">'+rupiah(ft.tpan)+'</td>'
    +'<td class="text-end">'+rupiah(ft.tpemb)+'</td>'
    +'<td class="text-end fw-bold" style="background:#dbeafe">'+rupiah(ft.brutoGaji)+'</td>'
    +'<td class="text-end">'+rupiah(ft.pphDtp)+'</td>'
    +'<td class="text-end">'+rupiah(ft.bpjsEmpl)+'</td>'
    +'<td class="text-end">'+rupiah(ft.jkk)+'</td>'
    +'<td class="text-end">'+rupiah(ft.jkm)+'</td>'
    +'<td class="text-end">'+rupiah(ft.bpjsPeg)+'</td>'
    +'<td class="text-end">'+rupiah(ft.pensiun)+'</td>'
    +'<td class="text-end fw-bold" style="background:#d1fae5;color:#065f46">'+rupiah(ft.bersihGaji)+'</td>'
    +'<td class="text-end">'+rupiah(ft.tpp)+'</td>'
    +'<td class="text-end">'+rupiah(ft.pphTppDtp)+'</td>'
    +'<td class="text-end">'+rupiah(ft.bpjsTppEmpl)+'</td>'
    +'<td class="text-end">'+rupiah(ft.bpjsTppPeg)+'</td>'
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
