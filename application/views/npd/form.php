<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Var: $row (null|obj+details), $is_super, $opd_opts, $my_opd_id, $sumber_opts */
$edit = $row ? TRUE : FALSE;
$preset = array(
  'id'             => $edit ? $row->id : '',
  'opd_id'         => $edit ? $row->opd_id : ($is_super ? '' : $my_opd_id),
  'subkegiatan_id' => $edit ? $row->subkegiatan_id : '',
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
            <?php foreach ($opd_opts as $k=>$v): ?><option value="<?= $k ?>"><?= html_escape($v) ?></option><?php endforeach; ?>
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

        <div class="col-md-12">
          <label class="form-label">Sub Kegiatan <span class="text-danger">*</span></label>
          <select class="form-select" name="subkegiatan_id" id="subkegiatan_id" required <?= ($is_super && !$edit)?'disabled':'' ?>>
            <option value="">— Pilih OPD dulu —</option>
          </select>
          <div class="form-text">Hanya sub kegiatan yang memiliki DPA <?= $is_super?'':'dan dalam kewenangan Anda' ?>.</div>
        </div>

        <div class="col-md-8">
          <label class="form-label">Perihal / Uraian Pekerjaan <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="perihal" id="perihal" value="<?= $edit ? html_escape($row->perihal) : '' ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Sumber Dana</label>
          <select class="form-select" name="sumber_dana_id" id="sumber_dana_id">
            <option value="">— (opsional) —</option>
            <?php foreach ($sumber_opts as $k=>$v): ?><option value="<?= $k ?>" <?= ($edit && $row->sumber_dana_id==$k)?'selected':'' ?>><?= html_escape($v) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-8">
          <label class="form-label">Rincian Pekerjaan</label>
          <textarea class="form-control" name="pekerjaan" id="pekerjaan" rows="2"><?= $edit ? html_escape($row->pekerjaan) : '' ?></textarea>
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
var URL_SUB = '<?= site_url('npd/subkegiatan_options') ?>',
    URL_REK = '<?= site_url('npd/rekening_sisa') ?>',
    URL_NOM = '<?= site_url('npd/next_nomor') ?>';
(function(){
  function fmt(n){ return 'Rp '+Number(n||0).toLocaleString('id-ID'); }
  function digits(s){ return String(s).replace(/[^\d]/g,''); }
  function esc(v){ return v==null?'':$('<div>').text(String(v)).html(); }
  function opdVal(){ return document.getElementById('opd_id').value; }

  function loadSubkeg(preselect){
    var opd = opdVal();
    var $s = $('#subkegiatan_id');
    if(!opd){ $s.html('<option value="">— Pilih OPD dulu —</option>').prop('disabled',true); return; }
    $s.prop('disabled',true).html('<option>Memuat…</option>');
    $.getJSON(URL_SUB, {opd_id:opd}, function(list){
      var h='<option value="">— Pilih Sub Kegiatan —</option>';
      list.forEach(function(o){ h+='<option value="'+o.id+'">'+esc(o.label)+'</option>'; });
      $s.html(h).prop('disabled',false);
      if(preselect){ $s.val(preselect); if($s.val()) loadRek(); }
    });
  }

  function genNomor(){
    var opd=opdVal(); if(!opd) return;
    var th=(document.getElementById('tanggal').value||'').substring(0,4)||new Date().getFullYear();
    $.getJSON(URL_NOM,{tahun:th},function(r){ if(r.nomor && !document.getElementById('nomor_npd').value) document.getElementById('nomor_npd').value=r.nomor; });
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
    var opd=opdVal(), sub=$('#subkegiatan_id').val();
    var st=document.getElementById('rek_state'), wrap=document.getElementById('rek_wrap');
    if(!opd||!sub){ wrap.style.display='none'; st.style.display=''; st.innerHTML='Pilih sub kegiatan.'; return; }
    st.style.display=''; st.innerHTML='<i class="fa-solid fa-spinner fa-spin me-1"></i>Memuat rekening…'; wrap.style.display='none';
    var params={opd_id:opd, subkegiatan_id:sub}; if(IS_EDIT&&PRESET.id) params.npd_id=PRESET.id;
    $.getJSON(URL_REK, params, function(rows){
      if(!rows.length){ st.innerHTML='Tidak ada rekening pada sub kegiatan ini.'; wrap.style.display='none'; return; }
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

  // events
  if(IS_SUPER && !IS_EDIT){
    document.getElementById('opd_id').addEventListener('change', function(){ document.getElementById('nomor_npd').value=''; loadSubkeg(); genNomor(); });
  }
  document.getElementById('subkegiatan_id').addEventListener('change', loadRek);
  document.getElementById('btnGenNomor').addEventListener('click', function(){ document.getElementById('nomor_npd').value=''; genNomor(); });
  document.getElementById('rekBody').addEventListener('input', function(e){ if(e.target.classList.contains('jml')){ var d=digits(e.target.value); e.target.value=d?Number(d).toLocaleString('id-ID'):''; recalc(); } });

  // init
  if(IS_EDIT){ loadSubkeg(PRESET.subkegiatan_id); }
  else if(!IS_SUPER){ loadSubkeg(); genNomor(); }
})();
</script>
<style>.dpa-kode{font-family:Consolas,monospace;font-size:.78em;background:rgba(0,0,0,.06);padding:1px 5px;border-radius:3px;}</style>
