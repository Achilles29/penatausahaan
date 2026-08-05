<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<style>
.slip-card { border-left: 4px solid var(--bs-primary); }
.slip-header { background: linear-gradient(135deg,#696cff,#9155fd); color:#fff; border-radius:.5rem .5rem 0 0; padding:1.25rem 1.5rem; }
.slip-section-title { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#6c757d; margin:1rem 0 .4rem; border-bottom:1px solid #eee; padding-bottom:.25rem; }
.slip-section-title.tpp-title { color:#0d6efd; border-color:#c9d3ff; }
.slip-row { display:flex; justify-content:space-between; align-items:baseline; padding:.3rem 0; font-size:.9rem; border-bottom:1px dashed #f0f0f0; }
.slip-row:last-child { border-bottom:none; }
.slip-row .label { color:#495057; flex:1; }
.slip-row .note  { color:#adb5bd; font-size:.75rem; margin:0 .5rem; }
.slip-row .amount { font-variant-numeric:tabular-nums; min-width:140px; text-align:right; font-weight:500; }
.slip-row.zero .amount { color:#ced4da; }
.slip-row.gov-paid .amount { color:#fd7e14; }
.slip-row.tpp-deduct .amount { color:#dc3545; }
.slip-total { background:#f8f9fa; border-radius:.4rem; display:flex; justify-content:space-between; padding:.6rem .75rem; font-weight:700; margin:.5rem 0; }
.slip-total.tpp-total { background:#f1f3ff; }
.slip-bersih-gaji { background:#e8f5e9; border:1px solid #a5d6a7; border-radius:.5rem; display:flex; justify-content:space-between; align-items:center; padding:.75rem 1.25rem; margin:.5rem 0; }
.slip-bersih-gaji .label { color:#1b5e20; font-weight:700; }
.slip-bersih-gaji .amount { color:#1b5e20; font-size:1.1rem; font-weight:700; font-variant-numeric:tabular-nums; }
.slip-tpp-bersih { background:#e3f2fd; border:1px solid #90caf9; border-radius:.5rem; display:flex; justify-content:space-between; align-items:center; padding:.75rem 1.25rem; margin:.5rem 0; }
.slip-tpp-bersih .label { color:#0d47a1; font-weight:700; }
.slip-tpp-bersih .amount { color:#0d47a1; font-size:1.1rem; font-weight:700; font-variant-numeric:tabular-nums; }
.slip-bersih { background:linear-gradient(90deg,#696cff,#9155fd); color:#fff; border-radius:.5rem; display:flex; justify-content:space-between; align-items:center; padding:.9rem 1.25rem; }
.badge-jenis { font-size:.75rem; padding:.3em .7em; }
.career-chip { display:inline-flex; align-items:center; gap:.3rem; background:#f1f3ff; border:1px solid #c9d3ff; color:#4a5b8c; border-radius:2rem; padding:.2rem .7rem; font-size:.78rem; margin:.15rem; }
.career-chip.warn { background:#fff8e1; border-color:#ffd54f; color:#7a5d00; }
.career-chip.danger { background:#fce4ec; border-color:#f48fb1; color:#8b0033; }
.career-chip.ok { background:#e8f5e9; border-color:#a5d6a7; color:#1b5e20; }
.peg-picker { position:relative; }
#peg_dropdown_sim { position:absolute; z-index:999; left:0; right:0; top:100%; max-height:280px; overflow-y:auto; background:#fff; border:1px solid #dee2e6; border-radius:0 0 .375rem .375rem; box-shadow:0 4px 12px rgba(0,0,0,.12); }
.slip-wrap { animation: fadeInUp .35s ease; }
@keyframes fadeInUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:none} }
.catatan-tag { font-size:.7rem; color:#adb5bd; font-style:italic; }
.tpp-divider { border:none; border-top:2px dashed #c9d3ff; margin:1rem 0 .5rem; }
.rekening-badge { font-size:.65rem; padding:1px 5px; background:#e8eaf6; color:#3949ab; border-radius:3px; font-family:monospace; display:inline-block; vertical-align:middle; }
</style>

<div class="row justify-content-center">
  <div class="col-xl-9 col-lg-10">

    <!-- Picker -->
    <div class="card mb-4">
      <div class="card-header"><i class="fa-solid fa-calculator me-2 text-primary"></i>Simulasi Slip Gaji ASN</div>
      <div class="card-body">
        <div class="row g-2 align-items-end">
          <div class="col-md-6">
            <label class="form-label fw-semibold mb-1">Pilih Pegawai</label>
            <div class="peg-picker">
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-user-tie"></i></span>
                <input type="text" class="form-control" id="peg_q" placeholder="Ketik nama atau NIP pegawai…" autocomplete="off">
                <button class="btn btn-outline-secondary" id="btnClearPeg" title="Reset"><i class="fa-solid fa-xmark"></i></button>
              </div>
              <div id="peg_dropdown_sim"></div>
            </div>
            <input type="hidden" id="sel_pegawai_id">
          </div>
          <div class="col-md-2">
            <label class="form-label small mb-1">Tahun</label>
            <input type="number" class="form-control form-control-sm" id="sel_tahun"
              value="<?= date('Y') ?>" min="2020" max="2099">
          </div>
          <div class="col-md-2">
            <label class="form-label small mb-1">Bulan</label>
            <select class="form-select form-select-sm" id="sel_bulan">
              <?php
              $bln = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
              for ($i = 1; $i <= 12; $i++): ?>
              <option value="<?= $i ?>" <?= $i == (int)date('n') ? 'selected' : '' ?>><?= $bln[$i] ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="col-md-1">
            <label class="form-label small mb-1">Jenis</label>
            <select class="form-select form-select-sm" id="sel_ke">
              <option value="0">Normal</option>
              <?php foreach ($ke_rows as $ke): ?>
              <option value="<?= (int)$ke['no'] ?>"><?= html_escape($ke['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-1">
            <button class="btn btn-primary w-100" id="btnHitung" disabled title="Hitung Gaji">
              <i class="fa-solid fa-calculator"></i>
            </button>
          </div>
        </div>
        <div id="peg_preview" class="mt-2" style="display:none">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <i class="fa-solid fa-user-check text-success"></i>
            <strong id="prev_nama"></strong>
            <span class="text-muted" id="prev_nip"></span>
            <span class="badge badge-soft-primary badge-jenis" id="prev_jenis"></span>
            <span class="text-muted small" id="prev_opd"></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Slip Gaji -->
    <div id="slip_wrap" style="display:none" class="slip-wrap">
      <!-- Header slip -->
      <div class="slip-header d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
          <div class="fs-5 fw-bold" id="sl_nama"></div>
          <div class="small opacity-75" id="sl_nip"></div>
          <div class="mt-1 d-flex flex-wrap gap-2" id="sl_jabatan_row"></div>
        </div>
        <div class="text-end">
          <span class="badge bg-white text-primary fs-6 fw-bold" id="sl_gol"></span>
          <div class="small opacity-75 mt-1" id="sl_mkg_info"></div>
          <div class="small opacity-75" id="sl_opd"></div>
          <div class="small opacity-75 mt-1 fw-semibold" id="sl_periode"></div>
        </div>
      </div>

      <div class="card border-0 shadow-sm mb-3" style="border-radius:0 0 .5rem .5rem">
        <div class="card-body">

          <!-- Penanda Karir -->
          <div id="sl_career" class="mb-3"></div>

          <!-- ─── GAJI ─── -->
          <div class="slip-section-title"><i class="fa-solid fa-money-bill-wave me-1"></i>Komponen Gaji</div>
          <div id="sl_gaji_rows"></div>
          <div class="slip-total">
            <span>Total Bruto Gaji</span>
            <span id="sl_bruto_gaji" class="text-primary"></span>
          </div>

          <!-- Potongan Gaji -->
          <div class="slip-section-title">Potongan &amp; Penyetoran</div>
          <div id="sl_potongan"></div>
          <div class="slip-total">
            <span>Total Potongan &amp; Penyetoran</span>
            <span id="sl_total_pot" class="text-danger"></span>
          </div>

          <!-- Bersih Gaji -->
          <div class="slip-bersih-gaji">
            <span class="label"><i class="fa-solid fa-circle-check me-1"></i>Bersih Gaji</span>
            <span class="amount" id="sl_bersih_gaji"></span>
          </div>

          <!-- ─── TPP ─── -->
          <div id="sl_tpp_section">
            <hr class="tpp-divider">
            <div class="slip-section-title tpp-title"><i class="fa-solid fa-hand-holding-dollar me-1"></i>Tambahan Penghasilan Pegawai (TPP) &amp; Penyetoran</div>
            <div id="sl_tpp_rows"></div>
            <div class="slip-tpp-bersih">
              <span class="label"><i class="fa-solid fa-circle-check me-1"></i>TPP Bersih</span>
              <span class="amount" id="sl_tpp_bersih"></span>
            </div>
          </div>

          <!-- Total Bersih -->
          <div class="slip-bersih mt-3">
            <div>
              <div class="small opacity-75">Total Take Home Pay</div>
              <div class="fw-bold small opacity-75">Gaji Bersih + TPP Bersih</div>
            </div>
            <div class="fs-4 fw-bold" id="sl_bersih"></div>
          </div>

          <!-- Belanja Pemerintah -->
          <div id="sl_belanja_section"></div>

          <p class="text-muted mt-3 mb-0" style="font-size:.72rem">
            <i class="fa-solid fa-circle-info me-1"></i>
            Simulasi estimasi perencanaan anggaran. Komponen bertanda <span class="badge bg-warning text-dark" style="font-size:.6rem">DTP</span> adalah belanja pemberi kerja yang
            dicatat diterima (penghasilan) <em>dan</em> dibayarkan (penyetoran) — net nihil terhadap take-home pegawai.
            PPh 21 tarif progresif UU HPP, PTKP 2024. Pajak TPP: 5% (non-Gol IV) / 15% (PNS Gol IV).
            <span class="text-warning fw-semibold">Bukan dokumen resmi.</span>
          </p>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div id="slip_loading" style="display:none" class="text-center py-5">
      <i class="fa-solid fa-spinner fa-spin fa-2x text-primary mb-3"></i>
      <div class="text-muted">Menghitung komponen gaji…</div>
    </div>

    <!-- Empty state -->
    <div id="slip_empty" class="text-center py-5 text-muted">
      <i class="fa-solid fa-file-invoice-dollar fa-3x mb-3 opacity-25"></i>
      <div>Pilih pegawai di atas untuk melihat simulasi slip gaji.</div>
    </div>

  </div>
</div>

<script>
var SIM = { peg_url:'<?= $peg_url ?>', hitung_url:'<?= $hitung_url ?>' };

(function () {
  var fmt = function (n) {
    if (!n && n !== 0) return '-';
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
  };
  var esc = function (v) { return v ? $('<div>').text(String(v)).html() : ''; };

  // ── Pegawai picker ──────────────────────────────────────────────────────
  var pegTimer, selId = null;

  $('#peg_q').on('input', function () {
    clearTimeout(pegTimer);
    var q = $(this).val().trim();
    selId = null; $('#sel_pegawai_id').val(''); $('#btnHitung').prop('disabled', true); $('#peg_preview').hide();
    if (q.length < 2) { $('#peg_dropdown_sim').html('').hide(); return; }
    pegTimer = setTimeout(function () {
      $.getJSON(SIM.peg_url + '?q=' + encodeURIComponent(q), function (rows) {
        if (!rows.length) { $('#peg_dropdown_sim').html('<div class="p-2 text-muted small">Tidak ditemukan</div>').show(); return; }
        var html = '';
        rows.forEach(function (p) {
          html += '<div class="peg-opt p-2 border-bottom d-flex justify-content-between align-items-center" style="cursor:pointer" data-id="' + p.id + '">'
            + '<div><strong>' + esc(p.nama_lengkap) + '</strong>'
            + '<span class="text-muted ms-2 small">' + esc(p.nip || '—') + '</span>'
            + '<div class="text-muted" style="font-size:.78rem">' + esc(p.nama_opd || '') + (p.jab_struktural ? ' · ' + esc(p.jab_struktural) : '') + '</div></div>'
            + '<span class="badge badge-soft-primary">' + esc(p.golongan || '') + '</span>'
            + '</div>';
        });
        $('#peg_dropdown_sim').html(html).show();
      });
    }, 260);
  });

  $(document).on('click', '.peg-opt', function () {
    selId = $(this).data('id');
    var name = $(this).find('strong').text();
    var nip  = $(this).find('.text-muted.ms-2').text();
    $('#peg_q').val(name + (nip && nip !== '—' ? ' — ' + nip : ''));
    $('#sel_pegawai_id').val(selId);
    $('#peg_dropdown_sim').hide();
    $('#btnHitung').prop('disabled', false);
    var opd = $(this).find('[style]').eq(0).text();
    $('#prev_nama').text(name); $('#prev_nip').text(nip);
    $('#prev_opd').text(opd); $('#prev_jenis').text($(this).find('.badge').text());
    $('#peg_preview').show();
  });

  $(document).on('mousedown', function (e) {
    if (!$(e.target).closest('#peg_q,#peg_dropdown_sim').length) $('#peg_dropdown_sim').hide();
  });

  $('#btnClearPeg').on('click', function () {
    $('#peg_q').val(''); $('#sel_pegawai_id').val(''); selId = null;
    $('#btnHitung').prop('disabled', true); $('#peg_preview').hide();
    $('#peg_dropdown_sim').hide(); showEmpty();
  });

  // ── Hitung ──────────────────────────────────────────────────────────────
  $('#btnHitung').on('click', function () {
    if (!selId) return;
    var bulan = parseInt($('#sel_bulan').val(), 10);
    var tahun = parseInt($('#sel_tahun').val(), 10);
    var is_ke = parseInt($('#sel_ke').val(), 10);
    $('#slip_wrap').hide(); $('#slip_empty').hide(); $('#slip_loading').show();
    $.post(SIM.hitung_url, { pegawai_id: selId, bulan: bulan, tahun: tahun, is_ke: is_ke }, function (res) {
      $('#slip_loading').hide();
      if (!res.ok) { alert(res.msg || 'Error'); showEmpty(); return; }
      renderSlip(res);
    }, 'json').fail(function () { $('#slip_loading').hide(); showEmpty(); alert('Gagal menghubungi server.'); });
  });

  function showEmpty() { $('#slip_wrap').hide(); $('#slip_loading').hide(); $('#slip_empty').show(); }

  // ── Render slip ──────────────────────────────────────────────────────────
  var BLN_NAMA = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  function renderSlip(d) {
    var p = d.pegawai;

    // Periode label di header
    var periodeLabel = (p.ke_nama ? p.ke_nama + ' — ' : '') + (BLN_NAMA[p.target_bulan] || '') + ' ' + (p.target_tahun || '');
    $('#sl_periode').text(periodeLabel);

    // Header
    $('#sl_nama').text(p.nama);
    $('#sl_nip').text(p.nip ? 'NIP ' + p.nip : (p.jenis === 'PPPK' ? 'NI PPPK' : ''));
    $('#sl_gol').text(p.golongan || '—');
    $('#sl_mkg_info').text('MKG ' + p.mkg + ' tahun' + (p.gapok_mkg !== p.mkg ? ' (data dari MKG ' + p.gapok_mkg + ')' : ''));
    $('#sl_opd').text((p.opd || '') + (p.unit ? ' · ' + p.unit : ''));

    var jRow = '';
    if (p.jab_struktural) jRow += '<span class="badge bg-white bg-opacity-25">' + esc(p.jab_struktural) + (p.eselon ? ' ('+p.eselon+')' : '') + '</span>';
    if (p.jab_fungsional) jRow += '<span class="badge bg-white bg-opacity-25">' + esc(p.jab_fungsional) + '</span>';
    if (p.jab_penatausahaan) jRow += '<span class="badge bg-white bg-opacity-25">' + esc(p.jab_penatausahaan) + '</span>';
    $('#sl_jabatan_row').html(jRow || '<span class="small opacity-75">Jabatan Pelaksana / Tidak ber-jabatan</span>');

    // Career markers
    var chips = '';
    if (p.usia !== null) {
      var bupCls = p.sisa_bup <= 2 ? 'danger' : (p.sisa_bup <= 5 ? 'warn' : 'ok');
      chips += '<span class="career-chip"><i class="fa-solid fa-cake-candles"></i> Usia ' + p.usia + ' tahun</span>';
      if (p.bup) chips += '<span class="career-chip ' + bupCls + '"><i class="fa-solid fa-hourglass-half"></i> BUP ' + p.bup + ' th · sisa ' + p.sisa_bup + ' th</span>';
    }
    if (p.tmt_kp) {
      var kpCls = p.hari_kp > 0 ? (p.hari_kp <= 180 ? 'warn' : '') : 'danger';
      var kpLabel = p.hari_kp > 0 ? 'KP dalam ' + p.hari_kp + ' hari' : 'KP terlambat ' + Math.abs(p.hari_kp) + ' hari';
      chips += '<span class="career-chip ' + kpCls + '"><i class="fa-solid fa-arrow-up"></i> ' + esc(kpLabel) + ' (' + p.tmt_kp + ')</span>';
    }
    if (p.kpp_aktif)       chips += '<span class="career-chip danger"><i class="fa-solid fa-medal"></i> KPP: Gol. ' + esc(p.golongan_asli) + ' → ' + esc(p.golongan) + ' (gaji pensiun PP 11/2017)</span>';
    if (p.kgb_berikutnya) chips += '<span class="career-chip warn"><i class="fa-solid fa-rotate"></i> KGB berikutnya: ' + p.kgb_berikutnya + '</span>';
    if (p.kgb_info)       chips += '<span class="career-chip ok"><i class="fa-solid fa-circle-up"></i> ' + esc(p.kgb_info) + '</span>';
    if (p.kelas_jabatan)  chips += '<span class="career-chip"><i class="fa-solid fa-layer-group"></i> Kelas ' + p.kelas_jabatan + (p.tpp_uraian ? ' — ' + esc(p.tpp_uraian) : '') + '</span>';
    var familyLabel = { BELUM_KAWIN:'Belum Kawin', KAWIN:'Kawin', JANDA:'Janda', DUDA:'Duda' };
    chips += '<span class="career-chip"><i class="fa-solid fa-users"></i> ' + (familyLabel[p.status_pernikahan] || p.status_pernikahan)
      + (p.jumlah_anak > 0 ? ' · ' + p.jumlah_anak + ' anak (' + p.anak_kena + ' kena T.Anak)' : '') + '</span>';
    $('#sl_career').html(chips ? '<div class="d-flex flex-wrap">' + chips + '</div>' : '');

    // ── Pisahkan komponen gaji dan TPP ──────────────────────────────────
    var gajiRows = [], tppRow = null;
    d.penghasilan.forEach(function (row) {
      if (row.rekening && row.rekening.indexOf('5.1.01.02') === 0) {
        tppRow = row;
      } else {
        gajiRows.push(row);
      }
    });

    var tpp = tppRow ? (tppRow.nominal || 0) : 0;

    // Gov-paid items
    var bel = d.belanja || {};

    // PPh21 TPP: DTP, Gol IV → 15%, selain itu → 5% (flat rate, tidak ikut marginal ke-13)
    var golongan = p.golongan || '';
    var rateTpp  = (golongan.indexOf('IV') === 0) ? 0.15 : 0.05;
    var pajakTpp = (bel.pph21_tpp !== undefined) ? (bel.pph21_tpp || 0)
                 : (tpp > 0 && p.jenis !== 'NON_ASN' ? Math.round(tpp * rateTpp) : 0);
    // BPJS TPP 1% pegawai (satu-satunya potongan dari TPP)
    var bpjsTppPeg = (d.iuran && d.iuran.bpjs_tpp_pegawai) ? d.iuran.bpjs_tpp_pegawai : 0;
    var rek_sfx = (p.jenis === 'PNS') ? '.00001' : '.00002';
    var govGaji = [];
    if (bel.pph21)             govGaji.push({ rek:'5.1.01.01.007'+rek_sfx, lbl:'Tunjangan PPh 21 — Ditanggung Pemerintah', val: bel.pph21 });
    if (bel.bpjs_kes_employer) govGaji.push({ rek:'5.1.01.01.009'+rek_sfx, lbl:'BPJS Kesehatan — Pemberi Kerja (4%)',      val: bel.bpjs_kes_employer });
    if (bel.jkk)               govGaji.push({ rek:'5.1.01.01.010'+rek_sfx, lbl:'Iuran JKK — Pemberi Kerja (0,24%)',        val: bel.jkk });
    if (bel.jkm)               govGaji.push({ rek:'5.1.01.01.011'+rek_sfx, lbl:'Iuran JKM — Pemberi Kerja (0,30%)',        val: bel.jkm });
    var govGajiTotal = govGaji.reduce(function(s, r) { return s + (r.val || 0); }, 0);

    var govTpp = (bel.bpjs_tpp && tpp > 0) ? bel.bpjs_tpp : 0;

    // Bruto includes gov contributions for proper gross recording
    var brutoGaji = (d.bruto || 0) - tpp + govGajiTotal;
    // TPP bruto grossed up: nominal + PPh21 DTP + BPJS 4% DTP (keduanya keluar-masuk)
    var tppBruto  = tpp + pajakTpp + govTpp;

    // Gaji potongan = total_potong (minus bpjsTppPeg yang pindah ke sisi TPP) + govGajiTotal DTP
    var totalPot  = (d.total_potong || 0) - bpjsTppPeg + govGajiTotal;
    // TPP potongan = PPh21 DTP (disetor) + BPJS 4% DTP (disetor) + BPJS 1% pegawai
    var tppPot    = pajakTpp + govTpp + bpjsTppPeg;

    var bersihGaji  = brutoGaji - totalPot;   // = d.bruto-tpp - (d.total_potong-bpjsTppPeg) = net gaji
    var tppBersih   = tppBruto  - tppPot;     // = tpp - bpjsTppPeg
    var totalBersih = bersihGaji + tppBersih; // = d.bersih

    // ── Render baris gaji ───────────────────────────────────────────────
    var gajiHtml = '';
    gajiRows.forEach(function (row) {
      var zero = row.nominal === 0;
      gajiHtml += '<div class="slip-row' + (zero ? ' zero' : '') + '">'
        + '<span class="label"><span class="rekening-badge me-1">' + esc(row.rekening || '') + '</span> ' + esc(row.label) + '</span>'
        + (row.catatan ? '<span class="catatan-tag">' + esc(row.catatan) + '</span>' : '')
        + '<span class="amount">' + (zero ? '—' : fmt(row.nominal)) + '</span>'
        + '</div>';
    });
    // Gov-paid penghasilan (diterima/dicatat)
    govGaji.forEach(function (gi) {
      gajiHtml += '<div class="slip-row gov-paid">'
        + '<span class="label"><span class="rekening-badge me-1">' + esc(gi.rek) + '</span> ' + esc(gi.lbl)
        + ' <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">DTP</span></span>'
        + '<span class="amount">' + fmt(gi.val) + '</span>'
        + '</div>';
    });
    $('#sl_gaji_rows').html(gajiHtml);
    $('#sl_bruto_gaji').html(fmt(brutoGaji) + (govGajiTotal > 0 ? ' <small class="fw-normal text-muted">(termasuk belanja pemberi kerja)</small>' : ''));

    // ── Render potongan ─────────────────────────────────────────────────
    var potongan = d.potongan ? Object.values(d.potongan) : [];
    // Filter BPJS TPP 1% dari potongan gaji (ditampilkan di sisi TPP)
    var gajiPotongan = potongan.filter(function(row) {
      return row.label.indexOf('TPP') === -1;
    });
    var potHtml = '';
    gajiPotongan.forEach(function (row) {
      potHtml += '<div class="slip-row">'
        + '<span class="label"><span class="rekening-badge me-1">' + esc(row.rekening || '') + '</span> ' + esc(row.label) + '</span>'
        + '<span class="amount">' + fmt(row.nominal) + '</span>'
        + '</div>';
    });
    // Gov-paid potongan (dibayarkan/disetor ke kas negara & BPJS)
    govGaji.forEach(function (gi) {
      potHtml += '<div class="slip-row tpp-deduct">'
        + '<span class="label"><span class="rekening-badge me-1">' + esc(gi.rek) + '</span> ' + esc(gi.lbl) + ' <em class="text-muted" style="font-size:.75rem">(disetor)</em></span>'
        + '<span class="amount">' + fmt(gi.val) + '</span>'
        + '</div>';
    });
    if (!gajiPotongan.length && !govGaji.length) potHtml = '<div class="text-muted small py-2">Tidak ada potongan dari gaji pegawai.</div>';
    $('#sl_potongan').html(potHtml);
    $('#sl_total_pot').text(fmt(totalPot));
    $('#sl_bersih_gaji').text(fmt(bersihGaji));

    // ── Render TPP section (konsep anggaran) ────────────────────────────
    if (tpp > 0 && tppRow) {
      var tppPct = Math.round(rateTpp * 100);
      var tppAnggaranBruto = tpp + pajakTpp + govTpp;

      // Rekening TPP
      var tppHtml = '<div class="slip-row">'
        + '<span class="label"><span class="rekening-badge me-1">' + esc(tppRow.rekening || '') + '</span> ' + esc(tppRow.label) + '</span>'
        + (tppRow.catatan ? '<span class="catatan-tag">' + esc(tppRow.catatan) + '</span>' : '')
        + '<span class="amount">' + fmt(tpp) + '</span>'
        + '</div>';

      // Rekening PPh DTP
      if (pajakTpp > 0) {
        tppHtml += '<div class="slip-row gov-paid">'
          + '<span class="label"><span class="rekening-badge me-1">5.1.01.01.007'+rek_sfx+'</span> Tunjangan PPh 21 TPP (' + tppPct + '%) — Ditanggung Pemerintah'
          + (tppPct === 15 ? ' <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">Gol IV</span>' : '')
          + ' <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">DTP</span></span>'
          + '<span class="amount">' + fmt(pajakTpp) + '</span>'
          + '</div>';
      }

      // Rekening BPJS Pemberi Kerja DTP
      if (govTpp > 0) {
        tppHtml += '<div class="slip-row gov-paid">'
          + '<span class="label"><span class="rekening-badge me-1">5.1.01.01.009'+rek_sfx+'</span> BPJS Kesehatan TPP — Pemberi Kerja (4%)'
          + ' <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">DTP</span></span>'
          + '<span class="amount">' + fmt(govTpp) + '</span>'
          + '</div>';
      }

      // Sub-total bruto anggaran
      tppHtml += '<div class="slip-total tpp-total" style="background:#fef9c3">'
        + '<span style="color:#78350f">Bruto Anggaran TPP</span>'
        + '<span style="color:#78350f">' + fmt(tppAnggaranBruto) + '</span>'
        + '</div>';

      // Potongan BPJS mandiri pegawai
      if (bpjsTppPeg > 0) {
        tppHtml += '<div class="slip-row tpp-deduct">'
          + '<span class="label"><span class="rekening-badge me-1">5.1.01.01.009'+rek_sfx+'</span> BPJS Kesehatan TPP — Pegawai (1%) <em class="text-muted" style="font-size:.75rem">(dipotong dari TPP)</em></span>'
          + '<span class="amount">(' + fmt(bpjsTppPeg) + ')</span>'
          + '</div>';
      }

      $('#sl_tpp_rows').html(tppHtml);
      $('#sl_tpp_bersih').text(fmt(tppBersih));
      $('#sl_tpp_section').show();
    } else {
      $('#sl_tpp_section').hide();
    }

    // ── Total bersih ────────────────────────────────────────────────────
    $('#sl_bersih').text(fmt(totalBersih));

    // Belanja pemerintah sudah dicatat di atas (penghasilan & potongan)
    $('#sl_belanja_section').html('');

    $('#slip_empty').hide();
    $('#slip_wrap').show();
  }

  showEmpty();
})();
</script>
