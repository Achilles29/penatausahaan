<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * View generik master. Variabel: $entity, $cfg, $can_manage, $filter_options, $field_options
 */
$cols = $cfg['columns'];

// Deteksi tab dan ukuran modal
$modal_size_class = isset($cfg['modal_size']) ? 'modal-'.$cfg['modal_size'] : '';
$form_tabs = array();
foreach ($cfg['fields'] as $_fld) {
    if (!empty($_fld['tab']) && !in_array($_fld['tab'], $form_tabs)) $form_tabs[] = $_fld['tab'];
}
$has_tabs = !empty($form_tabs);

// Helper closure untuk render satu field
$renderField = function($fld) use ($field_options) {
    $name = $fld['name'];
    // Field tersembunyi (mis. pegawai_id) — tanpa wrapper/label
    if (($fld['type'] ?? '') === 'hidden') {
        echo '<input type="hidden" name="'.htmlspecialchars($name).'" id="fld_'.htmlspecialchars($name).'">';
        return;
    }
    $req  = !empty($fld['required']) ? 'required' : '';
    $lreq = $req ? ' <span class="text-danger">*</span>' : '';
    echo '<div class="mb-3" data-field="'.htmlspecialchars($name).'">';
    echo '<label class="form-label">'.htmlspecialchars($fld['label']).$lreq.'</label>';
    $type = $fld['type'];
    if ($type === 'text') {
        echo '<input type="text" class="form-control" name="'.htmlspecialchars($name).'" id="fld_'.htmlspecialchars($name).'" '.$req.' placeholder="'.htmlspecialchars($fld['placeholder'] ?? '').'">';
    } elseif ($type === 'date') {
        echo '<input type="date" class="form-control" name="'.htmlspecialchars($name).'" id="fld_'.htmlspecialchars($name).'" '.$req.'>';
    } elseif ($type === 'number') {
        $min  = isset($fld['min'])  ? 'min="'.(int)$fld['min'].'"'  : '';
        $max  = isset($fld['max'])  ? 'max="'.(int)$fld['max'].'"'  : '';
        $step = 'step="'.($fld['step'] ?? 1).'"';
        echo '<input type="number" class="form-control" name="'.htmlspecialchars($name).'" id="fld_'.htmlspecialchars($name).'" '.$min.' '.$max.' '.$step.' placeholder="'.htmlspecialchars($fld['placeholder'] ?? '').'" '.$req.'>';
    } elseif ($type === 'textarea') {
        echo '<textarea class="form-control" name="'.htmlspecialchars($name).'" id="fld_'.htmlspecialchars($name).'" rows="2" '.$req.'></textarea>';
    } elseif ($type === 'checkbox') {
        $chk = !empty($fld['default']) ? 'checked' : '';
        echo '<div class="form-check form-switch">';
        echo '<input type="checkbox" class="form-check-input" name="'.htmlspecialchars($name).'" id="fld_'.htmlspecialchars($name).'" value="1" '.$chk.'>';
        echo '<label class="form-check-label" for="fld_'.htmlspecialchars($name).'">Ya</label></div>';
    } elseif ($type === 'enum') {
        echo '<select class="form-select" name="'.htmlspecialchars($name).'" id="fld_'.htmlspecialchars($name).'" '.$req.'>';
        foreach ($fld['options'] as $ov => $ol) {
            if (is_array($ol)) {
                // $ov = group label, $ol = array of options
                echo '<optgroup label="'.htmlspecialchars($ov).'">';
                foreach ($ol as $gv => $gl) {
                    $sel = (isset($fld['default']) && $fld['default'] == $gv) ? 'selected' : '';
                    echo '<option value="'.htmlspecialchars($gv).'" '.$sel.'>'.htmlspecialchars($gl).'</option>';
                }
                echo '</optgroup>';
            } else {
                $sel = (isset($fld['default']) && $fld['default'] == $ov) ? 'selected' : '';
                echo '<option value="'.htmlspecialchars($ov).'" '.$sel.'>'.htmlspecialchars($ol).'</option>';
            }
        }
        echo '</select>';
    } elseif ($type === 'select') {
        $dep = !empty($fld['depends']) ? 'data-depends="'.htmlspecialchars($fld['depends']).'" data-source="'.htmlspecialchars($fld['source']).'"' : '';
        echo '<select class="form-select" name="'.htmlspecialchars($name).'" id="fld_'.htmlspecialchars($name).'" '.$req.' '.$dep.'>';
        echo '<option value="">— Pilih —</option>';
        if (empty($fld['depends']) && isset($field_options[$name])) {
            foreach ($field_options[$name] as $k => $v) {
                if (is_array($v)) {
                    // Rich option: ['label' => '...', 'eselon' => '...', ...]
                    $extra = '';
                    foreach ($v as $dk => $dv) {
                        if ($dk === 'label') continue;
                        $extra .= ' data-'.htmlspecialchars($dk).'="'.htmlspecialchars((string)$dv).'"';
                    }
                    echo '<option value="'.htmlspecialchars($k).'"'.$extra.'>'.htmlspecialchars($v['label']).'</option>';
                } else {
                    echo '<option value="'.htmlspecialchars($k).'">'.htmlspecialchars($v).'</option>';
                }
            }
        }
        echo '</select>';
        // Eselon badge for jabatan_struktural_id field
        if ($name === 'jabatan_struktural_id') {
            echo '<div class="mt-1" id="eselon_badge_wrap"><small class="text-muted">Eselon: </small><span id="eselon_badge" class="badge bg-info">—</span></div>';
        }
    }
    if (!empty($fld['hint'])) {
        echo '<div class="form-text text-muted small">'.htmlspecialchars($fld['hint']).'</div>';
    }
    echo '</div>';
};
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
<?php if ($entity === 'pegawai'): ?>
<div id="peg_stats" class="row g-3 mb-4">
  <?php for ($__i = 0; $__i < 5; $__i++): ?>
  <div class="col-xl col-md-4 col-6">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body d-flex align-items-center gap-3 p-3">
        <div class="rounded-3 bg-light d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px">
          <span class="spinner-border spinner-border-sm text-secondary"></span>
        </div>
        <div><div class="fw-bold fs-5 lh-1 mb-1 text-muted">—</div><div class="text-muted small">Memuat…</div></div>
      </div>
    </div>
  </div>
  <?php endfor; ?>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
    <span><i class="fa-solid fa-table-list me-2 text-primary"></i><?= html_escape($cfg['title']) ?></span>
    <?php if (!empty($can_create)): ?>
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
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable <?= $modal_size_class ?>">
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

          <?php if ($has_tabs): ?>
          <!-- Tabbed form -->
          <ul class="nav nav-pills mb-3 flex-wrap gap-1" role="tablist">
            <?php foreach ($form_tabs as $_ti => $_tn): ?>
            <li class="nav-item" role="presentation">
              <button class="nav-link <?= $_ti===0?'active':'' ?> px-3 py-1 small fw-semibold"
                      type="button" role="tab"
                      data-bs-toggle="tab" data-bs-target="#ftab_<?= md5($_tn) ?>"
                      id="ftab_btn_<?= md5($_tn) ?>">
                <?= html_escape($_tn) ?>
              </button>
            </li>
            <?php endforeach; ?>
          </ul>
          <div class="tab-content">
            <?php foreach ($form_tabs as $_ti => $_tn): ?>
            <div class="tab-pane fade <?= $_ti===0?'show active':'' ?>" id="ftab_<?= md5($_tn) ?>" role="tabpanel">
              <?php foreach ($cfg['fields'] as $_fld): if (($_fld['tab'] ?? '') !== $_tn) continue; $renderField($_fld); endforeach; ?>
            </div>
            <?php endforeach; ?>
          </div>

          <?php else: ?>
          <!-- Flat form (tanpa tab) -->
          <?php foreach ($cfg['fields'] as $fld): $renderField($fld); endforeach; ?>
          <?php endif; ?>

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
	'can_edit'   => ! empty($can_edit),
	'can_delete' => ! empty($can_delete),
	'data_url'   => site_url('master/data/'.$entity),
	'get_url'    => site_url('master/get/'.$entity),
	'opt_url'    => site_url('master/options'),
	'peg_url'    => ($entity === 'penerima') ? site_url('master/pegawai_search') : NULL,
	'order_col'  => !empty($cfg['order_by_raw']) ? 0 : 1,
));
?>
<script>
var MCFG = <?= $cfg_js ?>;
(function () {
  function _esc(x){ return x==null?'':$('<div>').text(String(x)).html(); }
  var renderers = {
    active: function (v) { return Number(v) ? '<span class="badge badge-soft-success">Aktif</span>' : '<span class="badge badge-soft-secondary">Nonaktif</span>'; },
    badge:  function (v) { return v ? '<span class="badge badge-soft-primary text-uppercase">'+_esc(v)+'</span>' : '-'; },
    pegawai_badge: function (v) { return Number(v) ? '<span class="badge badge-soft-primary"><i class="fa-solid fa-user-tie me-1"></i>Pegawai</span>' : '<span class="badge badge-soft-secondary">Manual</span>'; },
    jabatan_multi: function (v, row) {
      var out = [];
      function add(nm, tag, cls){ if (nm) out.push('<div class="mb-1">'+_esc(nm)+' <span class="badge badge-soft-'+cls+'" style="font-size:8px;vertical-align:middle">'+tag+'</span></div>'); }
      add(row.jabatan_struktural_nama,    'STRUKTURAL', 'primary');
      add(row.jabatan_penatausahaan_nama, 'KEUANGAN',   'warning');
      add(row.jabatan_fungsional_nama,    'FUNGSIONAL', 'info');
      return out.length ? out.join('') : '<span class="text-muted">-</span>';
    },
    peg_nama_nip: function (v, row) {
      var h = '<strong>' + _esc(v) + '</strong>';
      if (row.nip) h += '<br><small class="text-muted font-monospace">' + _esc(row.nip) + '</small>';
      return h;
    },
    peg_kepangkatan: function (v, row) {
      var clsMap = { PNS: 'primary', PPPK: 'warning', NON_ASN: 'secondary' };
      var cls = clsMap[v] || 'secondary';
      var h = '<span class="badge badge-soft-' + cls + ' text-uppercase">' + _esc(v || '-') + '</span>';
      if (row.pangkat) h += '<br><small class="text-muted">' + _esc(row.pangkat) + '</small>';
      if (row.golongan) {
        var gol = row.golongan;
        var gc = gol.indexOf('IV') === 0 ? '#7b1fa2'
               : gol.indexOf('III') === 0 ? '#1565c0'
               : gol.indexOf('II') === 0  ? '#00695c'
               : '#2e7d32';
        var pct = (row.persen_gaji && String(row.persen_gaji) !== '100')
          ? ' <span class="badge bg-warning text-dark" style="font-size:9px">' + _esc(row.persen_gaji) + '%</span>' : '';
        h += '<br><span class="badge rounded-pill" style="background:' + gc + '20;color:' + gc + ';font-size:10px">Gol. ' + _esc(gol) + '</span>' + pct;
      }
      return h;
    },
    peg_mkg: function (v, row) {
      var today = new Date(); today.setHours(0,0,0,0);
      var h = '';
      if (v !== null && v !== undefined && v !== '') {
        h += '<span class="badge rounded-pill" style="background:#e8eaf6;color:#3949ab;font-size:10px">MKG ' + _esc(v) + ' thn</span>';
      }
      if (row.tmt_kgb) {
        var d = new Date(row.tmt_kgb); d.setHours(0,0,0,0);
        var days = Math.round((d - today) / 86400000);
        var cc = days <= 90 ? 'text-danger fw-semibold' : days <= 180 ? 'text-warning fw-semibold' : 'text-muted';
        h += '<br><small class="' + cc + '">KGB: ' + _esc(row.tmt_kgb) + '</small>';
      }
      if (row.tmt_kenaikan_pangkat) {
        var d2 = new Date(row.tmt_kenaikan_pangkat); d2.setHours(0,0,0,0);
        var days2 = Math.round((d2 - today) / 86400000);
        var cc2 = days2 <= 90 ? 'text-danger fw-semibold' : days2 <= 180 ? 'text-warning fw-semibold' : 'text-muted';
        h += '<br><small class="' + cc2 + '">KP: ' + _esc(row.tmt_kenaikan_pangkat) + '</small>';
      }
      return h || '-';
    },
    peg_jabatan: function (v, row) {
      var h = '';
      if (row.eselon) {
        h += '<span class="badge rounded-pill mb-1" style="background:#e0f7fa;color:#00697a;font-size:9px;font-weight:600">Eselon '
           + _esc(row.eselon) + '</span><br>';
      }
      if (row.jabatan_struktural_nama) {
        h += '<div class="mb-1"><span style="font-size:.82em">' + _esc(row.jabatan_struktural_nama)
           + '</span> <span class="badge" style="background:#e8eaf6;color:#3949ab;font-size:7.5px">STR</span></div>';
      }
      if (row.jabatan_penatausahaan_nama) {
        h += '<div class="mb-1"><span style="font-size:.82em">' + _esc(row.jabatan_penatausahaan_nama)
           + '</span> <span class="badge" style="background:#fff3e0;color:#e65100;font-size:7.5px">KEU</span></div>';
      }
      if (row.jabatan_fungsional_nama) {
        h += '<div><span style="font-size:.82em">' + _esc(row.jabatan_fungsional_nama)
           + '</span> <span class="badge" style="background:#e8f5e9;color:#2e7d32;font-size:7.5px">FUNG</span></div>';
      }
      return h || '<span class="text-muted">-</span>';
    },
    peg_keluarga: function (v, row) {
      var tkBadge = Number(v)
        ? '<span class="badge badge-soft-success" style="font-size:10px">Ya</span>'
        : '<span class="badge badge-soft-secondary" style="font-size:10px">Tidak</span>';
      var h = '<div class="d-flex align-items-center gap-1 mb-1"><small class="text-muted">T.Kel:</small>' + tkBadge + '</div>';
      var spMap = { KAWIN: 'Kawin', BELUM_KAWIN: 'Belum Kawin', JANDA: 'Janda', DUDA: 'Duda' };
      var spIc  = { KAWIN: '💍', BELUM_KAWIN: '—', JANDA: '🕊', DUDA: '🕊' };
      if (row.status_pernikahan) h += '<small class="text-muted">' + _esc(spMap[row.status_pernikahan] || row.status_pernikahan) + '</small>';
      if (row.jumlah_anak !== null && row.jumlah_anak !== undefined) h += '<br><small class="text-muted">' + _esc(row.jumlah_anak) + ' anak</small>';
      return h;
    }
  };
  var columns = [{ data: null, orderable:false, searchable:false, title:'#', width:'52px',
      render: function(d,t,r,meta){ return meta.row + meta.settings._iDisplayStart + 1; } }];

  MCFG.columns.forEach(function (c) {
    columns.push({
      data: c.data, title: c.title, orderable: c.order !== false,
      render: function (v, t, row) {
        if (c.render && renderers[c.render]) return renderers[c.render](v, row);
        return v === null || v === undefined ? '-' : $('<div>').text(v).html();
      }
    });
  });

  if (MCFG.can_edit || MCFG.can_delete) {
    columns.push({ data:'id', orderable:false, searchable:false, className:'text-end',
      render: function (id, t, row) {
        var h = '';
        if (MCFG.can_edit)   h += '<button class="btn btn-sm btn-icon btn-outline-primary me-1 btn-edit" data-id="'+id+'" title="Edit"><i class="fa-solid fa-pen"></i></button>';
        if (MCFG.can_delete) h += '<button class="btn btn-sm btn-icon btn-outline-danger btn-del" data-id="'+id+'" title="Hapus"><i class="fa-solid fa-trash"></i></button>';
        return h;
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

  // ── Eselon sync untuk jabatan_struktural_id ──────────────────────────
  function updateEselonBadge() {
    var sel = document.getElementById('fld_jabatan_struktural_id');
    if (!sel) return;
    var opt = sel.options[sel.selectedIndex];
    var eselon = (opt && opt.dataset && opt.dataset.eselon) ? opt.dataset.eselon : '';
    var badge = document.getElementById('eselon_badge');
    if (badge) {
      badge.textContent = eselon || '—';
      badge.className = eselon ? 'badge bg-info' : 'badge bg-secondary';
    }
  }
  $('#formModal').on('change', '#fld_jabatan_struktural_id', updateEselonBadge);
  $('#formModal').on('shown.bs.modal', updateEselonBadge);

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
              var jabInfo = p.jabatan_penatausahaan || p.jabatan_struktural || '';
              html += '<button type="button" class="list-group-item list-group-item-action peg-penerima-item small py-2"'
                + ' data-id="'      + esc2(p.id) + '"'
                + ' data-nama="'    + esc2(p.nama_lengkap) + '"'
                + ' data-nip="'     + esc2(p.nip) + '"'
                + ' data-jenis="'   + esc2(p.jenis_kepegawaian) + '"'
                + ' data-npwp="'    + esc2(p.npwp) + '"'
                + ' data-golongan="'+ esc2(p.golongan) + '">'
                + '<strong>' + esc2(p.nama_lengkap) + '</strong>'
                + '<span class="text-muted ms-2">' + esc2(p.nip || '—') + '</span>'
                + '<span class="d-block text-muted" style="font-size:.8em">'
                  + esc2(p.nama_opd || '') + (p.nama_opd ? ' · ' : '') + jLabel + golInfo
                  + (jabInfo ? ' · <em>'+esc2(jabInfo)+'</em>' : '')
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
      $('#fld_pegawai_id').val($(this).data('id'));   // TAUTKAN ke pegawai (data live)
      $('#fld_nama_penerima').val($(this).data('nama'));
      $('#fld_jenis_penerima').val(jenisVal);
      $('#fld_npwp').val(npwp);
      $('#fld_punya_npwp').prop('checked', npwp !== '');
      if (golEnum) $('#fld_golongan').val(golEnum);
      $('#peg_search_penerima').val('');
      $('#peg_dropdown_penerima').hide();
    });
    // Ketik nama manual => lepas tautan pegawai (jadi entri manual)
    $(document).on('input', '#fld_nama_penerima', function () {
      if (document.activeElement === this) $('#fld_pegawai_id').val('');
    });

    $(document).on('mousedown', function (e) {
      if (!$(e.target).closest('#peg_search_penerima, #peg_dropdown_penerima').length) {
        $('#peg_dropdown_penerima').hide();
      }
    });
  }

  // ── Ringkasan statistik pegawai ──────────────────────────────────────────
  if (MCFG.entity === 'pegawai') {
    var _golBadge = function (gol, n, color) {
      return '<span class="d-flex align-items-center gap-1">'
        + '<span class="badge rounded-pill" style="background:' + color + '20;color:' + color + '">' + gol + '</span>'
        + '<strong style="color:' + color + '">' + (n || 0) + '</strong>'
        + '<span class="text-muted small">org</span></span>';
    };
    $.getJSON('<?= site_url('master/stats/pegawai') ?>', function (s) {
      var defs = [
        { label:'Total Pegawai', val:s.total,    icon:'fa-users',          bg:'#e8eaf6', ic:'#3949ab' },
        { label:'PNS',           val:s.pns,      icon:'fa-id-badge',       bg:'#e3f2fd', ic:'#1565c0' },
        { label:'PPPK',          val:s.pppk,     icon:'fa-file-contract',  bg:'#fff3e0', ic:'#e65100' },
        { label:'Non ASN',       val:s.non_asn,  icon:'fa-user',           bg:'#f3e5f5', ic:'#7b1fa2' },
        { label:'KGB ≤ 3 Bln',  val:s.kgb_soon, icon:'fa-calendar-check',
          bg: s.kgb_soon > 0 ? '#fffde7' : '#f5f5f5',
          ic: s.kgb_soon > 0 ? '#f9a825' : '#9e9e9e' },
      ];
      var cardsHtml = defs.map(function (c) {
        return '<div class="col-xl col-md-4 col-6">'
          + '<div class="card h-100 border-0 shadow-sm">'
          + '<div class="card-body d-flex align-items-center gap-3 p-3">'
          + '<div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"'
          + ' style="width:48px;height:48px;background:' + c.bg + '">'
          + '<i class="fa-solid ' + c.icon + ' fa-lg" style="color:' + c.ic + '"></i></div>'
          + '<div>'
          + '<div class="fw-bold fs-4 lh-1 mb-1" style="color:' + c.ic + '">' + c.val + '</div>'
          + '<div class="text-muted small">' + c.label + '</div>'
          + '</div></div></div></div>';
      }).join('');
      var golRow = '<div class="col-12"><div class="card border-0 shadow-sm">'
        + '<div class="card-body py-2 px-3 d-flex flex-wrap gap-3 align-items-center">'
        + '<small class="text-muted fw-semibold me-1">Breakdown Gol PNS:</small>'
        + _golBadge('IV',  s.gol_iv,  '#7b1fa2')
        + _golBadge('III', s.gol_iii, '#1565c0')
        + _golBadge('II',  s.gol_ii,  '#00695c')
        + _golBadge('I',   s.gol_i,   '#2e7d32')
        + '</div></div></div>';
      $('#peg_stats').html(cardsHtml + golRow);
    });
  }
})();
</script>
