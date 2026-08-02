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
.belanja-row td { background:#fff9e6 !important; }
.peringatan-chip { background:#fef3c7; color:#92400e; border-radius:20px; font-size:.75rem; padding:2px 10px; display:inline-block; }
.pensiun-chip    { background:#fee2e2; color:#991b1b; border-radius:20px; font-size:.75rem; padding:2px 10px; display:inline-block; }
.section-sep td  { background:#f1f5f9 !important; font-weight:700; font-size:.75rem; color:#475569; text-transform:uppercase; letter-spacing:.06em; padding:4px 8px; }
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
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="fa-solid fa-calculator me-1"></i> Hitung
        </button>
      </div>
    </form>
  </div>
</div>

<div id="rekapResult" style="display:none">
  <!-- Summary boxes -->
  <div class="row g-3 mb-4" id="summaryBoxes"></div>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-0" id="rekapTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="tab-rek-btn" data-bs-toggle="tab" data-bs-target="#tabRekening" type="button" role="tab">
        <i class="fa-solid fa-receipt me-1"></i>Per Rekening
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-peg-btn" data-bs-toggle="tab" data-bs-target="#tabPegawai" type="button" role="tab">
        <i class="fa-solid fa-users me-1"></i>Per Pegawai
      </button>
    </li>
  </ul>

  <div class="tab-content border border-top-0 rounded-bottom bg-white shadow-sm mb-4">

    <!-- TAB: PER REKENING -->
    <div class="tab-pane fade show active p-0" id="tabRekening" role="tabpanel">
      <table class="table table-sm mb-0" id="tblRekening">
        <thead class="table-light">
          <tr>
            <th style="width:140px">Rekening</th>
            <th>Komponen</th>
            <th class="text-end" style="width:160px">Jumlah (Rp)</th>
          </tr>
        </thead>
        <tbody id="tblRekenBody"></tbody>
        <tfoot class="table-light fw-bold">
          <tr>
            <td colspan="2">TOTAL BELANJA PEGAWAI</td>
            <td class="text-end" id="totalBelanja">—</td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- TAB: PER PEGAWAI -->
    <div class="tab-pane fade p-0" id="tabPegawai" role="tabpanel">
      <div class="table-responsive">
        <table class="table table-sm table-hover tbl-rekap mb-0" id="tblDetail">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Nama / NIP</th>
              <th>Gol</th>
              <th>Eselon / Jabatan</th>
              <th>OPD</th>
              <th class="text-end">Gaji Pokok</th>
              <th class="text-end">T.Keluarga</th>
              <th class="text-end">T.Jabatan</th>
              <th class="text-end">T.Pangan</th>
              <th class="text-end">Bruto Gaji</th>
              <th class="text-end">Potongan</th>
              <th class="text-end">Bersih Gaji</th>
              <th class="text-end">TPP</th>
              <th class="text-end">Pajak TPP</th>
              <th class="text-end">TPP Bersih</th>
              <th class="text-end fw-bold">Total Bersih</th>
              <th class="text-center">Info</th>
            </tr>
          </thead>
          <tbody id="tblDetailBody"></tbody>
          <tfoot class="table-light fw-bold" id="tblDetailFoot"></tfoot>
        </table>
      </div>
    </div>

  </div>
</div>

<div id="rekapLoading" style="display:none; text-align:center; padding:40px">
  <div class="spinner-border text-primary"></div>
  <p class="mt-2 text-muted">Menghitung gaji untuk semua pegawai…</p>
</div>

<script>
var HITUNG_URL = '<?= $hitung_url ?>';
var BLN_NAMA   = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

// Urutan golongan untuk sorting (indeks = prioritas, lebih besar = lebih tinggi)
var GOL_ORDER = ['I/a','I/b','I/c','I/d','II/a','II/b','II/c','II/d','III/a','III/b','III/c','III/d',
  'IV/a','IV/b','IV/c','IV/d','IV/e',
  'I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII','XIII','XIV','XV','XVI','XVII'];
// Rank eselon (semakin kecil = lebih tinggi)
var ESELON_RANK = {'2A':1,'2B':2,'3A':3,'3B':4,'4A':5,'4B':6};

function eselonRank(e) { return ESELON_RANK[e] || 99; }
function golonganRank(g) { var i = GOL_ORDER.indexOf(g); return i < 0 ? -1 : i; }

function rupiah(n) {
  if (n === undefined || n === null) return '—';
  return n < 0 ? '-Rp '+Math.abs(Math.round(n)).toLocaleString('id-ID') : 'Rp '+Math.round(n).toLocaleString('id-ID');
}
function esc(v) { return v ? $('<div>').text(String(v)).html() : '—'; }

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

function renderRekap(res) {
  var t = res.total, jumlah = res.jumlah;

  // Summary boxes
  // pajakTppTotal = PPh21 TPP (DTP, ditanggung negara) — bukan potongan pegawai
  // bpjsTppPegTotal = BPJS TPP 1% (dipotong dari TPP pegawai)
  var pajakTppTotal = 0, bpjsTppPegTotal = 0;
  res.rows.forEach(function(h) {
    var gol = h.pegawai.golongan || '';
    var tpp = h.komponen.tpp || 0;
    if (tpp > 0 && h.pegawai.jenis !== 'NON_ASN') {
      var rate = (gol.indexOf('IV') === 0) ? 0.15 : 0.05;
      pajakTppTotal  += Math.round(tpp * rate);
      bpjsTppPegTotal += Math.round(tpp * 0.01);
    }
  });
  // TPP bersih = TPP - 1% BPJS (pajak DTP tidak dipotong dari pegawai)
  var tppBersihTotal  = (t.tpp || 0) - bpjsTppPegTotal;
  var brutoGajiTotal  = (t.bruto || 0) - (t.tpp || 0);
  // pot_total sudah termasuk bpjs_tpp_pegawai 1% dari Gaji.php
  var potonganGajiTotal = (t.pot_total || 0) - bpjsTppPegTotal;
  var bersihGajiTotal = brutoGajiTotal - potonganGajiTotal;
  var totalBersih = bersihGajiTotal + tppBersihTotal;
  // Belanja negara: PPh21+BPJS dari gaji DTP, PPh21+BPJS 4% dari TPP DTP, JKK, JKM
  // BPJS TPP 1% pegawai TIDAK termasuk (tanggungan pegawai, bukan beban negara)
  var totalBelanjaPemda = (t.bel_pph21||0) + (t.bel_bpjs_gaji||0) + (t.bel_jkk||0) + (t.bel_jkm||0)
                        + (t.bel_pph21_tpp || pajakTppTotal) + (t.bel_bpjs_tpp||0);

  var boxes = [
    { lbl: 'Pegawai Aktif',       val: jumlah + ' orang',        cls: '' },
    { lbl: 'Pensiun / Dilewati',  val: (t.pensiun_count||0)+' orang', cls: '' },
    { lbl: 'Total Bersih Gaji',   val: rupiah(bersihGajiTotal),  cls: 'primary' },
    { lbl: 'Total TPP Bersih',    val: rupiah(tppBersihTotal),   cls: 'primary' },
    { lbl: 'Total Take Home Pay', val: rupiah(totalBersih),      cls: '' },
    { lbl: 'Total Belanja Pemda', val: rupiah(totalBelanjaPemda), cls: '' },
  ];
  var bhtml = '';
  boxes.forEach(function(b) {
    bhtml += '<div class="col-6 col-lg-2"><div class="sum-box '+b.cls+'"><div class="lbl">'+b.lbl+'</div><div class="val">'+b.val+'</div></div></div>';
  });
  $('#summaryBoxes').html(bhtml);

  // ─── Tab 1: Per Rekening ───────────────────────────────────────────────
  var potBersihGaji = t.pot_bpjs_kes + t.pot_pensiun + t.pot_jht + t.pot_jp;

  var rekDef = [
    // KOMPONEN GAJI
    { section: 'KOMPONEN GAJI', isSection: true },
    { rek: '5.1.01.01.001', label: 'Gaji Pokok ASN',                         val: t.gaji_pokok },
    { rek: '5.1.01.01.002', label: 'Tunjangan Keluarga (Istri + Anak)',        val: t.t_keluarga },
    { rek: '5.1.01.01.003', label: 'Tunjangan Jabatan (Struktural/Fungsional/Umum)', val: t.t_jabatan },
    { rek: '5.1.01.01.006', label: 'Tunjangan Beras / Pangan',                val: t.t_pangan },
    { rek: '5.1.01.02.001', label: 'Tambahan Penghasilan Pegawai (TPP)',       val: t.tpp },
    // POTONGAN PEGAWAI
    { section: 'POTONGAN (DITANGGUNG PEGAWAI)', isSection: true },
    { rek: '5.1.01.01.009', label: 'BPJS Kesehatan Gaji — Pegawai (1%)',      val: t.pot_bpjs_kes, neg: true },
    { rek: '5.1.01.01.013', label: 'Iuran Pensiun / JHT / JP (pegawai)',       val: t.pot_pensiun + t.pot_jht + t.pot_jp, neg: true },
    { rek: '5.1.01.01.009', label: 'BPJS Kesehatan TPP — Pegawai (1%)',       val: bpjsTppPegTotal || t.pot_bpjs_tpp_peg, neg: true },
    // BELANJA PEMBERI KERJA
    { section: 'BELANJA PEMBERI KERJA (DTP / Ditanggung Negara)', isSection: true },
    { rek: '5.1.01.01.007', label: 'PPh 21 Gaji — Ditanggung Pemerintah',     val: t.bel_pph21 },
    { rek: '5.1.01.01.007', label: 'PPh 21 TPP — Ditanggung Pemerintah',      val: t.bel_pph21_tpp || pajakTppTotal },
    { rek: '5.1.01.01.009', label: 'BPJS Kesehatan Gaji — Pemberi Kerja (4%)', val: t.bel_bpjs_gaji },
    { rek: '5.1.01.01.010', label: 'Iuran JKK — Pemberi Kerja (0,24%)',       val: t.bel_jkk },
    { rek: '5.1.01.01.011', label: 'Iuran JKM — Pemberi Kerja (0,30%)',       val: t.bel_jkm },
    { rek: '5.1.01.01.009', label: 'BPJS Kesehatan TPP — Pemberi Kerja (4%)', val: t.bel_bpjs_tpp },
  ];

  var rhtml = '';
  var totalBelanjaDef = 0;
  rekDef.forEach(function(r) {
    if (r.isSection) {
      rhtml += '<tr class="section-sep"><td colspan="3">'+r.section+'</td></tr>';
      return;
    }
    var v = r.val || 0;
    if (!r.neg) totalBelanjaDef += v;
    var isZero = (v === 0);
    var valHtml = isZero ? '<span class="text-muted">—</span>' : (r.neg ? '<span class="text-danger">-'+rupiah(v)+'</span>' : rupiah(v));
    rhtml += '<tr'+(isZero?' class="zero-row"':'')+'>'
      + '<td><span class="rekening-badge">'+esc(r.rek)+'</span></td>'
      + '<td>'+esc(r.label)+'</td>'
      + '<td class="text-end">'+valHtml+'</td>'
      + '</tr>';
  });
  $('#tblRekenBody').html(rhtml);
  $('#totalBelanja').text(rupiah(totalBelanjaDef));

  // ─── Tab 2: Per Pegawai ───────────────────────────────────────────────
  // Sort: OPD kode → PNS/PPPK → eselon rank ASC → golongan rank DESC → tgl_lahir ASC
  var JENIS_RANK = {PNS:1, PPPK:2, NON_ASN:3};
  var sorted = res.rows.slice().sort(function(a, b) {
    var pa = a.pegawai, pb = b.pegawai;
    // OPD code
    var opdA = pa.kode_opd || pa.opd || '';
    var opdB = pb.kode_opd || pb.opd || '';
    if (opdA < opdB) return -1; if (opdA > opdB) return 1;
    // Jenis kepegawaian: PNS dulu, baru PPPK
    var ja = JENIS_RANK[(pa.jenis||'').toUpperCase()] || 99;
    var jb = JENIS_RANK[(pb.jenis||'').toUpperCase()] || 99;
    if (ja !== jb) return ja - jb;
    // Eselon (lebih tinggi dulu = rank kecil dulu)
    var ea = eselonRank(pa.eselon || ''), eb = eselonRank(pb.eselon || '');
    if (ea !== eb) return ea - eb;
    // Golongan (lebih tinggi dulu = rank besar dulu → descending)
    var ga = golonganRank(pa.golongan || ''), gb = golonganRank(pb.golongan || '');
    if (ga !== gb) return gb - ga;
    // Usia tertinggi = tgl_lahir terlama (ascending tgl_lahir)
    var ta = pa.tgl_lahir || '9999', tb = pb.tgl_lahir || '9999';
    if (ta < tb) return -1; if (ta > tb) return 1;
    return 0;
  });

  $('#jmlPegawai').text(jumlah + ' pegawai');
  var dhtml = '';
  var ftotal = { gapok:0, tkel:0, tjab:0, tpan:0, brutoGaji:0, gajiPot:0, bersihGaji:0, tpp:0, bpjsTppPeg:0, tppBersih:0, totalBersih:0 };

  sorted.forEach(function(h, idx) {
    var p = h.pegawai, k = h.komponen;
    var tpp = k.tpp || 0;
    // BPJS TPP 1% = satu-satunya potongan pegawai dari TPP; pajak TPP = DTP (negara)
    var bpjsTppPeg = (tpp > 0 && p.jenis !== 'NON_ASN') ? Math.round(tpp * 0.01) : 0;
    var tppBersih  = tpp - bpjsTppPeg;
    // total_potong dari server sudah termasuk bpjsTppPeg; pisahkan agar tidak dikurangi dua kali dari sisi gaji
    var gajiPot    = (h.total_potong || 0) - bpjsTppPeg;
    var brutoGaji  = (h.bruto || 0) - tpp;
    var bersihGaji = brutoGaji - gajiPot;
    var totalB     = bersihGaji + tppBersih;  // == h.bersih
    // PPh21 TPP (DTP) — untuk referensi tampilan saja
    var rate       = (p.golongan && p.golongan.indexOf('IV') === 0) ? 0.15 : 0.05;
    var pajakTpp   = (tpp > 0 && p.jenis !== 'NON_ASN') ? Math.round(tpp * rate) : 0;

    ftotal.gapok      += k.gaji_pokok || 0;
    ftotal.tkel       += (k.t_istri || 0) + (k.t_anak || 0);
    ftotal.tjab       += (k.t_jabatan || 0) + (k.t_khusus || 0);
    ftotal.tpan       += k.t_pangan || 0;
    ftotal.brutoGaji  += brutoGaji;
    ftotal.gajiPot    += gajiPot;
    ftotal.bersihGaji += bersihGaji;
    ftotal.tpp        += tpp;
    ftotal.bpjsTppPeg += bpjsTppPeg;
    ftotal.tppBersih  += tppBersih;
    ftotal.totalBersih+= totalB;

    var chips = '';
    if (p.hari_kp !== null && p.hari_kp !== undefined && p.hari_kp >= 0 && p.hari_kp <= 180)
      chips += '<span class="peringatan-chip">KP ~'+p.hari_kp+' hr</span> ';
    if (p.sisa_bup !== null && p.sisa_bup !== undefined && p.sisa_bup <= 2)
      chips += '<span class="pensiun-chip">BUP '+p.bup+'th</span> ';
    if (p.kgb_info) chips += '<span class="peringatan-chip">'+esc(p.kgb_info)+'</span>';

    var eselon  = p.eselon ? '<span class="badge bg-secondary me-1" style="font-size:.65rem">'+esc(p.eselon)+'</span>' : '';
    var jabatan = p.jab_struktural || p.jab_fungsional || p.jab_penatausahaan || '—';

    dhtml += '<tr>';
    dhtml += '<td>'+(idx+1)+'</td>';
    dhtml += '<td><strong>'+esc(p.nama)+'</strong><br><small class="text-muted font-monospace">'+esc(p.nip||'—')+'</small></td>';
    dhtml += '<td>'+esc(p.golongan)+'<br><small class="text-muted">MKG '+p.mkg+'</small></td>';
    dhtml += '<td>'+eselon+esc(jabatan)+'</td>';
    dhtml += '<td><small>'+esc(p.opd||'—')+'</small></td>';
    dhtml += '<td class="text-end">'+rupiah(k.gaji_pokok)+'</td>';
    dhtml += '<td class="text-end">'+rupiah((k.t_istri||0)+(k.t_anak||0))+'</td>';
    dhtml += '<td class="text-end">'+rupiah((k.t_jabatan||0)+(k.t_khusus||0))+'</td>';
    dhtml += '<td class="text-end">'+rupiah(k.t_pangan||0)+'</td>';
    dhtml += '<td class="text-end">'+rupiah(brutoGaji)+'</td>';
    dhtml += '<td class="text-end text-danger">'+rupiah(gajiPot)+'</td>';
    dhtml += '<td class="text-end text-success fw-semibold">'+rupiah(bersihGaji)+'</td>';
    dhtml += '<td class="text-end">'+rupiah(tpp)+'</td>';
    dhtml += '<td class="text-end text-danger"><small>-'+rupiah(bpjsTppPeg)+' (1% BPJS)'
           + (pajakTpp ? '<br><span class="text-muted">DTP: '+rupiah(pajakTpp)+'</span>' : '')
           + '</small></td>';
    dhtml += '<td class="text-end fw-semibold" style="color:#0d47a1">'+rupiah(tppBersih)+'</td>';
    dhtml += '<td class="text-end fw-bold" style="color:#5b21b6">'+rupiah(totalB)+'</td>';
    dhtml += '<td class="text-center">'+chips+'</td>';
    dhtml += '</tr>';
  });

  $('#tblDetailBody').html(dhtml || '<tr><td colspan="17" class="text-center text-muted py-3">Tidak ada data</td></tr>');
  $('#tblDetailFoot').html(
    '<tr><td colspan="5" class="text-end">TOTAL</td>'
    +'<td class="text-end">'+rupiah(ftotal.gapok)+'</td>'
    +'<td class="text-end">'+rupiah(ftotal.tkel)+'</td>'
    +'<td class="text-end">'+rupiah(ftotal.tjab)+'</td>'
    +'<td class="text-end">'+rupiah(ftotal.tpan)+'</td>'
    +'<td class="text-end">'+rupiah(ftotal.brutoGaji)+'</td>'
    +'<td class="text-end text-danger">'+rupiah(ftotal.gajiPot)+'</td>'
    +'<td class="text-end text-success">'+rupiah(ftotal.bersihGaji)+'</td>'
    +'<td class="text-end">'+rupiah(ftotal.tpp)+'</td>'
    +'<td class="text-end text-danger">'+rupiah(ftotal.bpjsTppPeg)+'</td>'
    +'<td class="text-end" style="color:#0d47a1">'+rupiah(ftotal.tppBersih)+'</td>'
    +'<td class="text-end" style="color:#5b21b6">'+rupiah(ftotal.totalBersih)+'</td>'
    +'<td></td></tr>'
  );
}
</script>
