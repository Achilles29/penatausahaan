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
  var renderers = {
    active: function (v) { return Number(v) ? '<span class="badge badge-soft-success">Aktif</span>' : '<span class="badge badge-soft-secondary">Nonaktif</span>'; },
    badge:  function (v) { return v ? '<span class="badge badge-soft-primary text-uppercase">'+$('<div>').text(v).html()+'</span>' : '-'; },
    pegawai_badge: function (v) { return Number(v) ? '<span class="badge badge-soft-primary"><i class="fa-solid fa-user-tie me-1"></i>Pegawai</span>' : '<span class="badge badge-soft-secondary">Manual</span>'; }
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
})();
</script>
