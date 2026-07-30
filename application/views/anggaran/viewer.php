<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Viewer read-only DataTables server-side. Var: $cfg,$opd_opts,$is_super,$data_url,$judul,$ikon,$ket */
$cols = $cfg['columns'];
$js_cols = array();
foreach ($cols as $c) {
	$js_cols[] = array('data' => $c['field'], 'render' => isset($c['render']) ? $c['render'] : NULL, 'order' => isset($c['order']));
}
?>
<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
    <span><i class="fa-solid <?= $ikon ?> me-2 text-primary"></i><?= html_escape($judul) ?></span>
    <small class="text-muted"><?= html_escape($ket) ?></small>
  </div>

  <?php if ($is_super): ?>
  <div class="card-body border-bottom py-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-5">
        <label class="form-label small mb-1">OPD</label>
        <select class="form-select form-select-sm" id="filterOpd">
          <option value="">— Semua OPD —</option>
          <?php foreach ($opd_opts as $k => $v): ?><option value="<?= $k ?>"><?= html_escape($v) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover table-striped w-100" id="tbl" data-manual="1">
        <thead><tr>
          <th style="width:48px">#</th>
          <?php foreach ($cols as $c): ?><th <?= isset($c['width'])?'style="width:'.$c['width'].'"':'' ?>><?= html_escape($c['label']) ?></th><?php endforeach; ?>
        </tr></thead>
      </table>
    </div>
  </div>
</div>

<script>
var VCFG = { columns: <?= json_encode($js_cols) ?>, data_url: '<?= $data_url ?>' };
(function () {
  function money(v){ return '<span class="text-nowrap">Rp '+Number(v||0).toLocaleString('id-ID')+'</span>'; }
  var esc = function(v){ return v===null||v===undefined?'-':$('<div>').text(v).html(); };
  var columns = [{ data:null, orderable:false, searchable:false,
      render:function(d,t,r,meta){ return meta.row + meta.settings._iDisplayStart + 1; } }];
  VCFG.columns.forEach(function (c) {
    columns.push({ data:c.data, orderable:c.order!==false,
      className: c.render==='money' ? 'text-end' : '',
      render:function(v){ return c.render==='money' ? money(v) : esc(v); } });
  });
  var table = $('#tbl').DataTable({
    processing:true, serverSide:true, order:[[1,'asc']],
    ajax:{ url:VCFG.data_url, data:function(d){ var o=$('#filterOpd'); if(o.length) d.f_opd=o.val(); } },
    columns: columns, pageLength:25, lengthMenu:[[25,50,100,-1],[25,50,100,'Semua']],
    language:{ processing:'Memuat…', search:'Cari:', lengthMenu:'Tampil _MENU_ baris',
      info:'Menampilkan _START_–_END_ dari _TOTAL_ data', infoEmpty:'Tidak ada data',
      infoFiltered:'(disaring dari _MAX_ total)', zeroRecords:'Data tidak ditemukan',
      paginate:{ first:'Awal', last:'Akhir', next:'›', previous:'‹' } }
  });
  $('#filterOpd').on('change', function(){ table.ajax.reload(); });
})();
</script>
