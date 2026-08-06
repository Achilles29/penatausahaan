<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Var: $row (null|obj+details), $is_super, $opd_opts, $my_opd_id, $sumber_opts */
$edit = $row ? TRUE : FALSE;
$preset = array(
  'id'             => $edit ? $row->id : '',
  'opd_id'         => $edit ? $row->opd_id : ($is_super ? '' : $my_opd_id),
  'program_id'     => $edit ? $row->program_id : '',
  'kegiatan_id'    => $edit ? $row->kegiatan_id : '',
  'subkegiatan_id' => $edit ? $row->subkegiatan_id : '',
  'perihal'        => $edit ? $row->perihal : '',
  'sumber_dana_id' => $edit ? $row->sumber_dana_id : '',
  'details'        => array(),
);
if ($edit) foreach ($row->details as $d) $preset['details'][(int)$d->rekening_id] = (float)$d->jumlah;
?>
<form action="<?= site_url('npd/save') ?>" method="post" id="npdForm">
  <input type="hidden" name="id" value="<?= $edit ? $row->id : '' ?>">

  <div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
      <span><i class="fa-solid fa-file-pen me-2 text-primary"></i><?= $edit ? 'Edit' : 'Buat' ?> NPD</span>
      <a href="<?= site_url('npd') ?>" class="btn btn-sm btn-label-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Kembali</a>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <?php if ($is_super): ?>
        <div class="col-md-6">
          <label class="form-label">OPD <span class="text-danger">*</span></label>
          <select class="form-select" name="opd_id" id="opd_id" <?= $edit?'':'required' ?>>
            <option value="">— Pilih OPD —</option>
            <?php foreach ($opd_opts as $k=>$v): ?><option value="<?= $k ?>" <?= ($edit && (int) $preset['opd_id'] === (int) $k) ? 'selected' : '' ?>><?= html_escape($v) ?></option><?php endforeach; ?>
          </select>
        </div>
        <?php else: ?>
          <input type="hidden" name="opd_id" id="opd_id" value="<?= $my_opd_id ?>">
        <?php endif; ?>

        <div class="col-md-3">
          <label class="form-label">Tanggal <span class="text-danger">*</span></label>
          <input type="date" class="form-control" name="tanggal" id="tanggal" value="<?= $edit ? $row->tanggal : date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Nomor NPD</label>
          <div class="input-group">
            <input type="text" class="form-control" name="nomor_npd" id="nomor_npd" value="<?= $edit ? html_escape($row->nomor_npd) : '' ?>" placeholder="otomatis">
            <button type="button" class="btn btn-outline-secondary" id="btnGenNomor" title="Buat ulang nomor"><i class="fa-solid fa-rotate"></i></button>
          </div>
        </div>

        <div class="col-md-12"><hr class="my-1"><small class="text-muted"><i class="fa-solid fa-diagram-project me-1"></i>Pilih dari DPA OPD (Program → Kegiatan → Sub Kegiatan sesuai data DPA):</small></div>
        <div class="col-md-6">
          <label class="form-label">Program <span class="text-danger">*</span></label>
          <select class="form-select" name="program_id" id="program_id" <?= ($is_super && !$edit)?'disabled':'' ?>>
            <option value="">— Pilih OPD dulu —</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Kegiatan <span class="text-danger">*</span></label>
          <select class="form-select" name="kegiatan_id" id="kegiatan_id" disabled>
            <option value="">— Pilih Program dulu —</option>
          </select>
        </div>
        <div class="col-md-12">
          <label class="form-label">Sub Kegiatan <span class="text-danger">*</span></label>
          <select class="form-select" name="subkegiatan_id" id="subkegiatan_id" required disabled>
            <option value="">— Pilih Kegiatan dulu —</option>
          </select>
          <div class="form-text">Hanya program/kegiatan/sub kegiatan yang memiliki DPA <?= $is_super?'pada OPD terpilih':'dan dalam kewenangan Anda' ?>.</div>
        </div>

        <div class="col-md-12">
          <label class="form-label">Pekerjaan (Perihal) <span class="text-danger">*</span></label>
          <select class="form-select" name="perihal" id="perihal" required disabled>
            <option value="">— Pilih Sub Kegiatan dulu —</option>
          </select>
          <div class="form-text">Daftar paket/pekerjaan sesuai DPA sub kegiatan terpilih.</div>
        </div>
        <div class="col-md-12">
          <label class="form-label">Sumber Dana <span class="text-danger">*</span></label>
          <select class="form-select" name="sumber_dana_id" id="sumber_dana_id" required disabled>
            <option value="">— Pilih Pekerjaan dulu —</option>
          </select>
          <div class="form-text">Sumber dana sesuai pekerjaan yang dipilih di DPA.</div>
        </div>
        <div class="col-md-8">
          <label class="form-label">Catatan / Keterangan <small class="text-muted">(opsional)</small></label>
          <textarea class="form-control" name="pekerjaan" id="pekerjaan" rows="2" placeholder="Catatan tambahan bila perlu…"><?= $edit ? html_escape($row->pekerjaan) : '' ?></textarea>
        </div>
        <div class="col-md-4">
          <label class="form-label">Status</label>
          <select class="form-select" name="status">
            <?php foreach (array('draft'=>'Draft','final'=>'Final','dibayar'=>'Dibayar') as $sv=>$sl): ?>
              <option value="<?= $sv ?>" <?= ($edit && $row->status===$sv)?'selected':'' ?>><?= $sl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-coins me-2 text-primary"></i>Rincian Rekening (isi jumlah pencairan sesuai sisa anggaran)</div>
    <div class="card-body p-0">
      <div id="rek_state" class="text-center text-muted py-4">
        <i class="fa-solid fa-hand-point-up mb-2 d-block fa-lg"></i>Pilih sub kegiatan untuk menampilkan rekening & sisa anggaran.
      </div>
      <div class="table-responsive" id="rek_wrap" style="display:none">
        <table class="table table-bordered align-middle mb-0" id="rekTable">
          <thead class="table-light">
            <tr>
              <th style="width:170px">Kode Rekening</th><th>Uraian</th>
              <th class="text-end" style="width:150px">Pagu</th>
              <th class="text-end" style="width:150px">Terpakai</th>
              <th class="text-end" style="width:150px">Sisa</th>
              <th class="text-end" style="width:180px">Jumlah Cair</th>
            </tr>
          </thead>
          <tbody id="rekBody"></tbody>
          <tfoot>
            <tr class="table-light fw-bold">
              <td colspan="5" class="text-end">TOTAL NPD</td>
              <td class="text-end" id="grandTotal">Rp 0</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2 mb-4">
    <a href="<?= site_url('npd') ?>" class="btn btn-label-secondary">Batal</a>
    <button type="submit" class="btn btn-primary" id="btnSave"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan NPD</button>
  </div>
</form>

<script>
var PRESET = <?= json_encode($preset) ?>;
var IS_SUPER = <?= $is_super ? 'true':'false' ?>, IS_EDIT = <?= $edit ? 'true':'false' ?>;
var URL_PROG = '<?= site_url('npd/program_options') ?>',
    URL_KEG  = '<?= site_url('npd/kegiatan_options') ?>',
    URL_SUB  = '<?= site_url('npd/subkegiatan_options') ?>',
    URL_PEK  = '<?= site_url('npd/pekerjaan_options') ?>',
    URL_SD   = '<?= site_url('npd/sumber_dana_options') ?>',
    URL_REK  = '<?= site_url('npd/rekening_sisa') ?>',
    URL_NOM  = '<?= site_url('npd/next_nomor') ?>';
(function(){
  function fmt(n){ return 'Rp '+Number(n||0).toLocaleString('id-ID'); }
  function digits(s){ return String(s).replace(/[^\d]/g,''); }
  function esc(v){ return v==null?'':$('<div>').text(String(v)).html(); }
  function opdVal(){ return document.getElementById('opd_id').value; }

  function fillSel($s, list, placeholder, preselect, after){
    var h='<option value="">'+placeholder+'</option>';
    list.forEach(function(o){ h+='<option value="'+o.id+'">'+esc(o.label)+'</option>'; });
    $s.html(h).prop('disabled',false);
    if(preselect){ $s.val(String(preselect)); }
    if(after) after($s.val());
  }
  function loadPrograms(preselect){
    var opd=opdVal(), $p=$('#program_id'), $k=$('#kegiatan_id'), $s=$('#subkegiatan_id');
    $k.html('<option value="">— Pilih Program dulu —</option>').prop('disabled',true);
    $s.html('<option value="">— Pilih Kegiatan dulu —</option>').prop('disabled',true);
    if(!opd){ $p.html('<option value="">— Pilih OPD dulu —</option>').prop('disabled',true); return; }
    $p.prop('disabled',true).html('<option>Memuat…</option>');
    $.getJSON(URL_PROG, {opd_id:opd}, function(list){
      fillSel($p, list, '— Pilih Program —', preselect, function(v){ if(v) loadKegiatan(PRESET.kegiatan_id); });
    });
  }
  function loadKegiatan(preselect){
    var opd=opdVal(), prog=$('#program_id').val(), $k=$('#kegiatan_id'), $s=$('#subkegiatan_id');
    $s.html('<option value="">— Pilih Kegiatan dulu —</option>').prop('disabled',true);
    if(!opd||!prog){ $k.html('<option value="">— Pilih Program dulu —</option>').prop('disabled',true); return; }
    $k.prop('disabled',true).html('<option>Memuat…</option>');
    $.getJSON(URL_KEG, {opd_id:opd, program_id:prog}, function(list){
      fillSel($k, list, '— Pilih Kegiatan —', preselect, function(v){ if(v) loadSubkeg(PRESET.subkegiatan_id); });
    });
  }
  function loadSubkeg(preselect){
    var opd=opdVal(), keg=$('#kegiatan_id').val(), $s=$('#subkegiatan_id');
    if(!opd||!keg){ $s.html('<option value="">— Pilih Kegiatan dulu —</option>').prop('disabled',true); return; }
    $s.prop('disabled',true).html('<option>Memuat…</option>');
    $.getJSON(URL_SUB, {opd_id:opd, kegiatan_id:keg}, function(list){
      fillSel($s, list, '— Pilih Sub Kegiatan —', preselect, function(v){ if(v) loadPekerjaan(PRESET.perihal); });
    });
  }
  function loadPekerjaan(preselect){
    var opd=opdVal(), sub=$('#subkegiatan_id').val(), $p=$('#perihal'), $sd=$('#sumber_dana_id');
    $sd.html('<option value="">— Pilih Pekerjaan dulu —</option>').prop('disabled',true);
    resetRek();
    if(!opd||!sub){ $p.html('<option value="">— Pilih Sub Kegiatan dulu —</option>').prop('disabled',true); return; }
    $p.prop('disabled',true).html('<option>Memuat…</option>');
    $.getJSON(URL_PEK, {opd_id:opd, subkegiatan_id:sub}, function(list){
      fillSel($p, list, '— Pilih Pekerjaan —', preselect, function(v){ if(v) loadSumber(PRESET.sumber_dana_id); });
    });
  }
  function loadSumber(preselect){
    var opd=opdVal(), sub=$('#subkegiatan_id').val(), paket=$('#perihal').val(), $sd=$('#sumber_dana_id');
    resetRek();
    if(!opd||!sub||!paket){ $sd.html('<option value="">— Pilih Pekerjaan dulu —</option>').prop('disabled',true); return; }
    $sd.prop('disabled',true).html('<option>Memuat…</option>');
    $.getJSON(URL_SD, {opd_id:opd, subkegiatan_id:sub, pekerjaan:paket}, function(list){
      fillSel($sd, list, '— Pilih Sumber Dana —', preselect, function(v){ if(v) loadRek(); });
    });
  }
  function resetRek(){
    var st=document.getElementById('rek_state'), wrap=document.getElementById('rek_wrap');
    wrap.style.display='none'; st.style.display='';
    st.innerHTML='<i class="fa-solid fa-hand-point-up mb-2 d-block fa-lg"></i>Pilih pekerjaan & sumber dana untuk menampilkan rekening & sisa.';
  }

  function genNomor(){
    var opd=opdVal(); if(!opd) return;
    var tgl=document.getElementById('tanggal').value||'';
    var th=tgl.substring(0,4)||new Date().getFullYear();
    var bl=tgl.substring(5,7)||'';
    $.getJSON(URL_NOM,{tahun:th,bulan:bl},function(r){ if(r.nomor && !document.getElementById('nomor_npd').value) document.getElementById('nomor_npd').value=r.nomor; });
  }

  function recalc(){
    var total=0, over=false;
    $('#rekBody tr').each(function(){
      var inp=this.querySelector('.jml'); if(!inp) return;
      var val=parseInt(digits(inp.value)||'0',10);
      var sisa=parseFloat(inp.dataset.sisa||'0');
      var cell=this.querySelector('.rem');
      if(val>sisa+0.001){ inp.classList.add('is-invalid'); over=true; } else { inp.classList.remove('is-invalid'); }
      if(cell) cell.textContent=fmt(sisa-val);
      total+=val;
    });
    document.getElementById('grandTotal').textContent=fmt(total);
    document.getElementById('btnSave').disabled = over;
  }

  function loadRek(){
    var opd=opdVal(), sub=$('#subkegiatan_id').val(), paket=$('#perihal').val(), sd=$('#sumber_dana_id').val();
    var st=document.getElementById('rek_state'), wrap=document.getElementById('rek_wrap');
    if(!opd||!sub||!paket||!sd){ resetRek(); return; }
    st.style.display=''; st.innerHTML='<i class="fa-solid fa-spinner fa-spin me-1"></i>Memuat rekening…'; wrap.style.display='none';
    var params={opd_id:opd, subkegiatan_id:sub, pekerjaan:paket, sumber_dana_id:sd}; if(IS_EDIT&&PRESET.id) params.npd_id=PRESET.id;
    $.getJSON(URL_REK, params, function(rows){
      if(!rows.length){ st.innerHTML='Tidak ada rekening pada pekerjaan/sumber dana ini.'; wrap.style.display='none'; return; }
      var body=document.getElementById('rekBody'); body.innerHTML='';
      rows.forEach(function(r){
        var pre = PRESET.details[r.rekening_id] || 0;
        var tr=document.createElement('tr');
        tr.innerHTML =
          '<td><span class="dpa-kode">'+esc(r.kode)+'</span></td>'+
          '<td>'+esc(r.uraian)+(r.kategori_pajak?' <span class="badge bg-label-primary">'+esc(r.kategori_pajak)+'</span>':'')+'</td>'+
          '<td class="text-end">'+fmt(r.pagu)+'</td>'+
          '<td class="text-end text-muted">'+fmt(r.realisasi)+'</td>'+
          '<td class="text-end rem">'+fmt(r.sisa)+'</td>'+
          '<td><input type="hidden" name="rekening_id[]" value="'+r.rekening_id+'">'+
          '<input type="text" inputmode="numeric" class="form-control form-control-sm text-end jml" name="jumlah[]" data-sisa="'+r.sisa+'" value="'+(pre?Number(pre).toLocaleString('id-ID'):'')+'" placeholder="0"></td>';
        body.appendChild(tr);
      });
      st.style.display='none'; wrap.style.display='';
      recalc();
    });
  }

  // events cascade
  if(IS_SUPER){
    document.getElementById('opd_id').addEventListener('change', function(){ document.getElementById('nomor_npd').value=''; loadPrograms(); genNomor(); });
  }
  document.getElementById('program_id').addEventListener('change', function(){ loadKegiatan(); });
  document.getElementById('kegiatan_id').addEventListener('change', function(){ loadSubkeg(); });
  document.getElementById('subkegiatan_id').addEventListener('change', function(){ loadPekerjaan(); });
  document.getElementById('perihal').addEventListener('change', function(){ loadSumber(); });
  document.getElementById('sumber_dana_id').addEventListener('change', loadRek);
  document.getElementById('btnGenNomor').addEventListener('click', function(){ document.getElementById('nomor_npd').value=''; genNomor(); });
  document.getElementById('tanggal').addEventListener('change', function(){ if(!IS_EDIT){ document.getElementById('nomor_npd').value=''; genNomor(); } });
  document.getElementById('rekBody').addEventListener('input', function(e){ if(e.target.classList.contains('jml')){ var d=digits(e.target.value); e.target.value=d?Number(d).toLocaleString('id-ID'):''; recalc(); } });

  // init: edit -> rantai preselect; baru + non-super -> muat program OPD sendiri
  if(IS_EDIT){ loadPrograms(PRESET.program_id); }
  else if(!IS_SUPER){ loadPrograms(); genNomor(); }
})();
</script>
<style>.dpa-kode{font-family:Consolas,monospace;font-size:.78em;background:rgba(0,0,0,.06);padding:1px 5px;border-radius:3px;}</style>
