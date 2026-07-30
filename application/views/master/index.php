<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * View generik master. Variabel: $entity, $cfg, $can_manage, $filter_options, $field_options
 */
$cols = $cfg['columns'];
// susun definisi kolom utk JS
$js_cols = array();
foreach ($cols as $c) {
	$js_cols[] = array(
		'data'   => $c['field'],
		'title'  => $c['label'],
		'render' => isset($c['render']) ? $c['render'] : NULL,
		'order'  => isset($c['order']),
	);
}
?>
<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
    <span><i class="fa-solid fa-table-list me-2 text-primary"></i><?= html_escape($cfg['title']) ?></span>
    <?php if ($can_manage): ?>
      <button class="btn btn-primary btn-sm" id="btnAdd"><i class="fa-solid fa-plus me-1"></i> Tambah</button>
    <?php endif; ?>
  </div>

  <?php if ( ! empty($cfg['filters'])): ?>
  <div class="card-body border-bottom py-3">
    <div class="row g-2 align-items-end">
      <?php foreach ($cfg['filters'] as $f): $cascade = isset($f['source']); ?>
      <div class="col-md-3 col-sm-6">
        <label class="form-label small mb-1"><?= html_escape($f['label']) ?></label>
        <select class="form-select form-select-sm filter-input"
                data-filter="f_<?= md5($f['name']) ?>"
                <?= $cascade ? 'data-cascade="1" data-level="'.html_escape($f['source']).'" data-label="'.html_escape($f['label']).'" data-opturl="'.site_url('master/options/'.$f['source']).'"' : '' ?>>
          <option value="">— Semua <?= html_escape($f['label']) ?> —</option>
          <?php if ( ! $cascade && isset($filter_options[$f['name']])): foreach ($filter_options[$f['name']] as $k => $v): ?>
            <option value="<?= html_escape($k) ?>"><?= html_escape($v) ?></option>
          <?php endforeach; endif; ?>
        </select>
      </div>
      <?php endforeach; ?>
      <div class="col-md-2 col-sm-6">
        <button type="button" class="btn btn-sm btn-label-secondary w-100" id="btnResetFilter"><i class="fa-solid fa-rotate-left me-1"></i>Reset</button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover table-striped w-100" id="tbl" data-manual="1">
        <thead>
          <tr>
            <th style="width:52px">#</th>
            <?php foreach ($cols as $c): ?>
              <th <?= isset($c['width']) ? 'style="width:'.$c['width'].'"' : '' ?>><?= html_escape($c['label']) ?></th>
            <?php endforeach; ?>
            <?php if ($can_manage): ?><th style="width:110px" class="text-end">Aksi</th><?php endif; ?>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<?php if ($can_manage): ?>
<!-- Modal Form -->
<div class="modal fade" id="formModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="<?= site_url('master/save/'.$entity) ?>" method="post">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa-solid fa-pen-to-square me-2"></i><span id="modalTitle">Tambah <?= html_escape($cfg['title']) ?></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="f_id" value="">
          <?php if ($entity === 'penerima'): ?>
          <div class="mb-3 border rounded p-2" id="peg_picker_wrap" style="position:relative; background:#f8f9fa">
            <label class="form-label small fw-semibold mb-1">
              <i class="fa-solid fa-magnifying-glass me-1 text-primary"></i>Isi dari data Pegawai
              <small class="text-muted fw-normal">(opsional)</small>
            </label>
            <input type="text" class="form-control form-control-sm" id="peg_search_penerima"
                   placeholder="Ketik nama atau NIP pegawai…" autocomplete="off">
            <div id="peg_dropdown_penerima" class="list-group shadow"
                 style="display:none; position:absolute; z-index:1060; left:8px; right:8px; max-height:210px; overflow-y:auto; top:calc(100% - 4px)">
            </div>
          </div>
          <?php endif; ?>
          <?php foreach ($cfg['fields'] as $fld): $name = $fld['name']; $req = ! empty($fld['required']) ? 'required' : ''; ?>
          <div class="mb-3" data-field="<?= $name ?>">
            <label class="form-label"><?= html_escape($fld['label']) ?><?= $req ? ' <span class="text-danger">*</span>' : '' ?></label>
            <?php if ($fld['type'] === 'text'): ?>
              <input type="text" class="form-control" name="<?= $name ?>" id="fld_<?= $name ?>" <?= $req ?>>
            <?php elseif ($fld['type'] === 'textarea'): ?>
              <textarea class="form-control" name="<?= $name ?>" id="fld_<?= $name ?>" rows="2" <?= $req ?>></textarea>
            <?php elseif ($fld['type'] === 'checkbox'): ?>
              <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="<?= $name ?>" id="fld_<?= $name ?>" value="1" <?= ! empty($fld['default']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="fld_<?= $name ?>">Ya</label>
              </div>
            <?php elseif ($fld['type'] === 'enum'): ?>
              <select class="form-select" name="<?= $name ?>" id="fld_<?= $name ?>" <?= $req ?>>
                <?php foreach ($fld['options'] as $ov => $ol): ?>
                  <option value="<?= html_escape($ov) ?>" <?= (isset($fld['default']) && $fld['default']==$ov)?'selected':'' ?>><?= html_escape($ol) ?></option>
                <?php endforeach; ?>
              </select>
            <?php elseif ($fld['type'] === 'select'): ?>
              <select class="form-select" name="<?= $name ?>" id="fld_<?= $name ?>" <?= $req ?>
                      <?= ! empty($fld['depends']) ? 'data-depends="'.$fld['depends'].'" data-source="'.$fld['source'].'"' : '' ?>>
                <option value="">— Pilih —</option>
                <?php if (empty($fld['depends']) && isset($field_options[$name])): foreach ($field_options[$name] as $k => $v): ?>
                  <option value="<?= html_escape($k) ?>"><?= html_escape($v) ?></option>
                <?php endforeach; endif; ?>
              </select>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Form hapus tersembunyi -->
<form id="deleteForm" action="<?= site_url('master/delete/'.$entity) ?>" method="post" data-confirm="Yakin ingin menghapus data ini?">
  <input type="hidden" name="id" id="del_id">
</form>
<?php endif; ?>

<?php
$assets = base_url('assets/');
$cfg_js = json_encode(array(
	'entity'     => $entity,
	'columns'    => $js_cols,
	'can_manage' => (bool) $can_manage,
	'data_url'   => site_url('master/data/'.$entity),
	'get_url'    => site_url('master/get/'.$entity),
	'opt_url'    => site_url('master/options'),
	'peg_url'    => ($entity === 'penerima') ? site_url('master/pegawai_search') : NULL,
	'order_col'  => 1,
));
?>
<script>
var MCFG = <?= $cfg_js ?>;
(function () {
  var renderers = {
    active: function (v) { return Number(v) ? '<span class="badge badge-soft-success">Aktif</span>' : '<span class="badge badge-soft-secondary">Nonaktif</span>'; },
    badge:  function (v) { return v ? '<span class="badge badge-soft-primary text-uppercase">'+$('<div>').text(v).html()+'</span>' : '-'; }
  };
  var columns = [{ data: null, orderable:false, searchable:false, title:'#', width:'52px',
      render: function(d,t,r,meta){ return meta.row + meta.settings._iDisplayStart + 1; } }];

  MCFG.columns.forEach(function (c) {
    columns.push({
      data: c.data, title: c.title, orderable: c.order !== false,
      render: function (v) {
        if (c.render && renderers[c.render]) return renderers[c.render](v);
        return v === null || v === undefined ? '-' : $('<div>').text(v).html();
      }
    });
  });

  if (MCFG.can_manage) {
    columns.push({ data:'id', orderable:false, searchable:false, className:'text-end',
      render: function (id, t, row) {
        return '<button class="btn btn-sm btn-icon btn-outline-primary me-1 btn-edit" data-id="'+id+'" title="Edit"><i class="fa-solid fa-pen"></i></button>'
             + '<button class="btn btn-sm btn-icon btn-outline-danger btn-del" data-id="'+id+'" title="Hapus"><i class="fa-solid fa-trash"></i></button>';
      }});
  }

  var table = $('#tbl').DataTable({
    processing: true, serverSide: true, order: [[MCFG.order_col, 'asc']],
    ajax: {
      url: MCFG.data_url,
      data: function (d) {
        $('.filter-input').each(function () { d[$(this).data('filter')] = $(this).val(); });
      }
    },
    columns: columns,
    pageLength: 25, lengthMenu: [[25,50,100,-1],[25,50,100,'Semua']],
    language: {
      processing:'Memuat…', search:'Cari:', lengthMenu:'Tampil _MENU_ baris',
      info:'Menampilkan _START_–_END_ dari _TOTAL_ data', infoEmpty:'Tidak ada data',
      infoFiltered:'(disaring dari _MAX_ total)', zeroRecords:'Data tidak ditemukan',
      paginate:{ first:'Awal', last:'Akhir', next:'›', previous:'‹' }
    }
  });

  // Filter statis (non-cascade) langsung reload; filter cascade ditangani initCascadeFilters
  $('.filter-input:not([data-cascade])').on('change', function () { table.ajax.reload(); });
  if (window.initCascadeFilters) window.initCascadeFilters(table);
  $('#btnResetFilter').on('click', function () {
    $('.filter-input').val('');
    if (window.initCascadeFilters) window.initCascadeFilters(table);
    table.ajax.reload();
  });

  // Tambah
  $('#btnAdd').on('click', function () {
    var f = document.querySelector('#formModal form'); f.reset();
    $('#f_id').val(''); $('#modalTitle').text('Tambah ' + '<?= html_escape($cfg['title']) ?>');
    // reset select dependen
    $('#formModal select[data-depends]').html('<option value="">— Pilih —</option>');
    new bootstrap.Modal('#formModal').show();
  });

  // Edit
  $('#tbl').on('click', '.btn-edit', function () {
    var id = $(this).data('id');
    $.getJSON(MCFG.get_url + '/' + id, function (row) {
      var f = document.querySelector('#formModal form'); f.reset();
      $('#f_id').val(row.id); $('#modalTitle').text('Edit ' + '<?= html_escape($cfg['title']) ?>');

      // muat dulu select dependen bila ada, lalu set nilai
      var deps = $('#formModal select[data-depends]');
      var pending = deps.length;
      function fillValues() {
        Object.keys(row).forEach(function (k) {
          var el = document.getElementById('fld_' + k);
          if (!el) return;
          if (el.type === 'checkbox') el.checked = Number(row[k]) === 1;
          else el.value = row[k] === null ? '' : row[k];
        });
      }
      if (pending === 0) { fillValues(); }
      else {
        deps.each(function () {
          var sel = this, src = $(sel).data('source'), dep = $(sel).data('depends');
          var pval = row[dep];
          $.getJSON(MCFG.opt_url + '/' + src + '?parent=' + encodeURIComponent(pval || ''), function (opts) {
            var html = '<option value="">— Pilih —</option>';
            Object.keys(opts).forEach(function (k) { html += '<option value="'+k+'">'+$('<div>').text(opts[k]).html()+'</option>'; });
            sel.innerHTML = html;
            if (--pending === 0) fillValues();
          });
        });
      }
      new bootstrap.Modal('#formModal').show();
    });
  });

  // Hapus
  $('#tbl').on('click', '.btn-del', function () {
    $('#del_id').val($(this).data('id'));
    if (confirm('Yakin ingin menghapus data ini?')) document.getElementById('deleteForm').submit();
  });

  // Cascading select di form (saat parent berubah)
  $('#formModal').on('change', 'select[data-depends]', function(){});
  $('#formModal select:not([data-depends])').on('change', function () {
    var parentName = this.name;
    var child = $('#formModal select[data-depends="' + parentName + '"]');
    if (!child.length) return;
    var src = child.data('source'), pval = this.value;
    $.getJSON(MCFG.opt_url + '/' + src + '?parent=' + encodeURIComponent(pval), function (opts) {
      var html = '<option value="">— Pilih —</option>';
      Object.keys(opts).forEach(function (k) { html += '<option value="'+k+'">'+$('<div>').text(opts[k]).html()+'</option>'; });
      child.html(html);
    });
  });

  // ---- Penerima: pegawai picker ----
  if (MCFG.peg_url) {
    var esc2 = function (v) { return v ? $('<div>').text(v).html() : ''; };
    var pegTimer = null;

    $('#formModal').on('hide.bs.modal', function () {
      $('#peg_dropdown_penerima').hide();
      $('#peg_search_penerima').val('');
    });

    $('#peg_search_penerima').on('input', function () {
      clearTimeout(pegTimer);
      var q = $(this).val().trim();
      if (q.length < 2) { $('#peg_dropdown_penerima').hide(); return; }
      pegTimer = setTimeout(function () {
        $.getJSON(MCFG.peg_url + '?q=' + encodeURIComponent(q), function (rows) {
          var html = '';
          if (rows.length === 0) {
            html = '<button type="button" class="list-group-item list-group-item-action text-muted small disabled">Tidak ditemukan</button>';
          } else {
            rows.forEach(function (p) {
              var jLabel = { PNS:'PNS', PPPK:'PPPK', NON_ASN:'Non ASN' }[p.jenis_kepegawaian] || p.jenis_kepegawaian;
              var golInfo = p.golongan ? (' Gol.'+p.golongan) : '';
              html += '<button type="button" class="list-group-item list-group-item-action peg-penerima-item small py-2"'
                + ' data-nama="'    + esc2(p.nama_lengkap) + '"'
                + ' data-nip="'     + esc2(p.nip) + '"'
                + ' data-jenis="'   + esc2(p.jenis_kepegawaian) + '"'
                + ' data-npwp="'    + esc2(p.npwp) + '"'
                + ' data-golongan="'+ esc2(p.golongan) + '">'
                + '<strong>' + esc2(p.nama_lengkap) + '</strong>'
                + '<span class="text-muted ms-2">' + esc2(p.nip || '—') + '</span>'
                + '<span class="d-block text-muted" style="font-size:.8em">'
                  + esc2(p.nama_opd || '') + (p.nama_opd ? ' · ' : '') + jLabel + golInfo
                + '</span>'
                + '</button>';
            });
          }
          $('#peg_dropdown_penerima').html(html).show();
        });
      }, 280);
    });

    $(document).on('click', '.peg-penerima-item', function () {
      var jenis    = $(this).data('jenis');
      var npwp     = $(this).data('npwp') || '';
      var golFull  = $(this).data('golongan') || ''; // e.g. 'III/b'
      var golEnum  = golFull ? golFull.replace(/\/.*/, '') : ''; // 'III'
      var jenisVal = (jenis === 'NON_ASN') ? 'non_asn' : 'asn';
      $('#fld_nama_penerima').val($(this).data('nama'));
      $('#fld_jenis_penerima').val(jenisVal);
      $('#fld_npwp').val(npwp);
      $('#fld_punya_npwp').prop('checked', npwp !== '');
      if (golEnum) $('#fld_golongan').val(golEnum);
      $('#peg_search_penerima').val('');
      $('#peg_dropdown_penerima').hide();
    });

    $(document).on('mousedown', function (e) {
      if (!$(e.target).closest('#peg_search_penerima, #peg_dropdown_penerima').length) {
        $('#peg_dropdown_penerima').hide();
      }
    });
  }
})();
</script>
