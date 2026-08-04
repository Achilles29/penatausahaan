<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Var: $cfg, $filters (chain), $data_url */
$cols = $cfg['columns'];
$js_cols = array();
foreach ($cols as $c) $js_cols[] = array('data' => $c['field'], 'render' => isset($c['render']) ? $c['render'] : NULL, 'order' => isset($c['order']));
?>
<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
    <span><i class="fa-solid fa-file-lines me-2 text-primary"></i>NPD — Nota Pencairan Dana</span>
    <a href="<?= site_url('npd/form') ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> Buat NPD</a>
  </div>

  <div class="card-body border-bottom py-3">
    <div class="row g-2 align-items-end">
      <?php foreach ($filters as $f): ?>
      <div class="col-md-3 col-sm-6">
        <label class="form-label small mb-1"><?= html_escape($f['label']) ?></label>
        <select class="form-select form-select-sm filter-input" data-filter="f_<?= md5($f['name']) ?>"
                data-cascade="1" data-level="<?= html_escape($f['source']) ?>" data-label="<?= html_escape($f['label']) ?>"
                data-opturl="<?= $f['opturl'] ?>">
          <option value="">— Semua <?= html_escape($f['label']) ?> —</option>
        </select>
      </div>
      <?php endforeach; ?>
      <div class="col-md-2 col-sm-6">
        <label class="form-label small mb-1">Status</label>
        <select class="form-select form-select-sm filter-input" id="flt_status" data-filter="f_status">
          <option value="">— Semua —</option>
          <option value="draft">Draft</option>
          <option value="final">Final</option>
          <option value="dibayar">Dibayar</option>
        </select>
      </div>
      <div class="col-md-2 col-sm-6">
        <button type="button" class="btn btn-sm btn-label-secondary w-100" id="btnResetFilter"><i class="fa-solid fa-rotate-left me-1"></i>Reset</button>
      </div>
    </div>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover table-striped w-100" id="tbl" data-manual="1">
        <thead><tr>
          <th style="width:44px">#</th>
          <?php foreach ($cols as $c): ?><th <?= isset($c['width'])?'style="width:'.$c['width'].'"':'' ?>><?= html_escape($c['label']) ?></th><?php endforeach; ?>
          <th style="width:120px" class="text-end">Aksi</th>
        </tr></thead>
      </table>
    </div>
  </div>
</div>

<form id="deleteForm" action="<?= site_url('npd/delete') ?>" method="post"><input type="hidden" name="id" id="del_id"></form>

<script>
var NCFG = { columns: <?= json_encode($js_cols) ?>, data_url: '<?= $data_url ?>',
             view_url: '<?= site_url('npd/view') ?>', form_url: '<?= site_url('npd/form') ?>',
             cetak_url: '<?= site_url('npd/cetak') ?>', pinbuk_url: '<?= site_url('npd/pindah_buku') ?>',
             c5_url: '<?= site_url('npd/c5') ?>' };
(function () {
  function money(v){ return '<span class="text-nowrap">Rp '+Number(v||0).toLocaleString('id-ID')+'</span>'; }
  function tgl(v){ if(!v) return '-'; var p=String(v).split('-'); return p.length===3? p[2]+'/'+p[1]+'/'+p[0] : v; }
  function statusBadge(v){
    var m={draft:'badge-soft-secondary',final:'badge-soft-primary',dibayar:'badge-soft-success'};
    return '<span class="badge '+(m[v]||'badge-soft-secondary')+' text-capitalize">'+v+'</span>';
  }
  var esc=function(v){return v===null||v===undefined?'-':$('<div>').text(v).html();};
  var columns=[{data:null,orderable:false,searchable:false,render:function(d,t,r,meta){return meta.row+meta.settings._iDisplayStart+1;}}];
  NCFG.columns.forEach(function(c){
    columns.push({ data:c.data, orderable:c.order!==false, className: c.render==='money'?'text-end':'',
      render:function(v){ if(c.render==='money')return money(v); if(c.render==='date')return tgl(v); if(c.render==='status')return statusBadge(v); return esc(v); } });
  });
  columns.push({ data:'id', orderable:false, searchable:false, className:'text-end', render:function(id){
    return '<div class="btn-group">'
         +   '<a href="'+NCFG.view_url+'/'+id+'" class="btn btn-sm btn-outline-secondary" title="Lihat"><i class="fa-solid fa-eye"></i></a>'
         +   '<a href="'+NCFG.form_url+'/'+id+'" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa-solid fa-pen"></i></a>'
         +   '<button type="button" class="btn btn-sm btn-outline-info dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" title="Cetak"><i class="fa-solid fa-print"></i></button>'
         +   '<ul class="dropdown-menu dropdown-menu-end">'
         +     '<li><a class="dropdown-item" target="_blank" href="'+NCFG.cetak_url+'/'+id+'"><i class="fa-solid fa-file-lines me-2"></i>Cetak NPD</a></li>'
         +     '<li><a class="dropdown-item" target="_blank" href="'+NCFG.pinbuk_url+'/'+id+'"><i class="fa-solid fa-right-left me-2"></i>Pindah Buku</a></li>'
         +     '<li><a class="dropdown-item" target="_blank" href="'+NCFG.c5_url+'/'+id+'"><i class="fa-solid fa-receipt me-2"></i>Cetak C5</a></li>'
         +   '</ul>'
         +   '<button class="btn btn-sm btn-outline-danger btn-del" data-id="'+id+'" title="Hapus"><i class="fa-solid fa-trash"></i></button>'
         + '</div>'; }});

  var table=$('#tbl').DataTable({
    processing:true, serverSide:true, order:[[2,'desc']],
    ajax:{ url:NCFG.data_url, data:function(d){ $('.filter-input').each(function(){ d[$(this).data('filter')]=$(this).val(); }); } },
    columns:columns, pageLength:25, lengthMenu:[[25,50,100,-1],[25,50,100,'Semua']],
    language:{ processing:'Memuat…', search:'Cari:', lengthMenu:'Tampil _MENU_ baris', info:'Menampilkan _START_–_END_ dari _TOTAL_ data',
      infoEmpty:'Tidak ada data', infoFiltered:'(disaring dari _MAX_)', zeroRecords:'Belum ada NPD',
      paginate:{first:'Awal',last:'Akhir',next:'›',previous:'‹'} }
  });
  $('.filter-input:not([data-cascade])').on('change',function(){ table.ajax.reload(); });
  if(window.initCascadeFilters) window.initCascadeFilters(table);
  $('#btnResetFilter').on('click',function(){ $('.filter-input').val(''); if(window.initCascadeFilters) window.initCascadeFilters(table); table.ajax.reload(); });
  $('#tbl').on('click','.btn-del',function(){ if(confirm('Hapus NPD ini?')){ $('#del_id').val($(this).data('id')); document.getElementById('deleteForm').submit(); } });
})();
</script>
