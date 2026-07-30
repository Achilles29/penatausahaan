<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Var: $roles, $opd_opts, $is_super, $my_opd, $data_url */
?>
<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
    <span><i class="fa-solid fa-users-gear me-2 text-primary"></i>Manajemen Pengguna</span>
    <button class="btn btn-primary btn-sm" id="btnAdd"><i class="fa-solid fa-plus me-1"></i> Tambah</button>
  </div>

  <div class="card-body border-bottom py-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-3 col-sm-6">
        <label class="form-label small mb-1">Role</label>
        <select class="form-select form-select-sm filter-input" id="flt_role">
          <option value="">— Semua Role —</option>
          <?php foreach ($roles as $rv => $rl): ?><option value="<?= $rv ?>"><?= html_escape($rl) ?></option><?php endforeach; ?>
        </select>
      </div>
      <?php if ($is_super): ?>
      <div class="col-md-3 col-sm-6">
        <label class="form-label small mb-1">OPD</label>
        <select class="form-select form-select-sm filter-input" id="flt_opd">
          <option value="">— Semua OPD —</option>
          <?php foreach ($opd_opts as $k => $v): ?><option value="<?= $k ?>"><?= html_escape($v) ?></option><?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-md-2 col-sm-6">
        <label class="form-label small mb-1">Status</label>
        <select class="form-select form-select-sm filter-input" id="flt_status">
          <option value="">— Semua Status —</option>
          <option value="1">Aktif</option>
          <option value="0">Nonaktif</option>
        </select>
      </div>
      <div class="col-md-2 col-sm-6">
        <button type="button" class="btn btn-sm btn-label-secondary w-100" id="btnResetFilter">
          <i class="fa-solid fa-rotate-left me-1"></i>Reset
        </button>
      </div>
    </div>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover table-striped w-100" id="tbl" data-manual="1">
        <thead><tr>
          <th style="width:48px">#</th>
          <th>Nama</th>
          <th style="width:190px">NIP / Username</th>
          <th style="width:120px">Role</th>
          <th>OPD</th>
          <th style="width:100px">Status</th>
          <th style="width:110px" class="text-end">Aksi</th>
        </tr></thead>
      </table>
    </div>
  </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="formModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="<?= site_url('user/save') ?>" method="post">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa-solid fa-user-pen me-2"></i><span id="modalTitle">Tambah Pengguna</span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="f_id">
          <div class="mb-3">
            <label class="form-label">Nama <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nama" id="fld_nama" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Role <span class="text-danger">*</span></label>
            <select class="form-select" name="role" id="fld_role" required>
              <?php foreach ($roles as $rv => $rl): ?><option value="<?= $rv ?>"><?= html_escape($rl) ?></option><?php endforeach; ?>
            </select>
          </div>

          <!-- Superadmin block -->
          <div class="mb-3 blk-super">
            <label class="form-label">Username <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="username" id="fld_username">
          </div>

          <!-- Non-superadmin block: pegawai picker -->
          <div class="mb-3 blk-opd" style="position:relative">
            <label class="form-label">Pegawai <span class="text-danger">*</span></label>
            <input type="hidden" name="pegawai_id" id="fld_pegawai_id">
            <input type="text" class="form-control" id="peg_search" placeholder="Ketik nama atau NIP..." autocomplete="off">
            <div id="peg_dropdown"
                 class="list-group shadow-sm"
                 style="display:none; position:absolute; z-index:1060; left:0; right:0; max-height:220px; overflow-y:auto; top:100%">
            </div>
            <div id="peg_info" class="form-text text-success mt-1" style="display:none">
              <i class="fa-solid fa-user-check me-1"></i><span id="peg_info_text"></span>
            </div>
          </div>

          <?php if ($is_super): ?>
          <div class="mb-3 blk-opd">
            <label class="form-label">OPD <span class="text-danger">*</span></label>
            <select class="form-select" name="opd_id" id="fld_opd_id">
              <option value="">— Pilih OPD —</option>
              <?php foreach ($opd_opts as $k => $v): ?><option value="<?= $k ?>"><?= html_escape($v) ?></option><?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>

          <div class="mb-3 blk-opd">
            <label class="form-label">Unit OPD <small class="text-muted">(opsional)</small></label>
            <select class="form-select" name="opd_unit_id" id="fld_opd_unit_id">
              <option value="">— Pilih Unit —</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Kata sandi <small class="text-muted" id="pwdHint">(wajib untuk pengguna baru)</small></label>
            <input type="password" class="form-control" name="password" id="fld_password" autocomplete="new-password">
          </div>
          <div class="form-check form-switch">
            <input type="checkbox" class="form-check-input" name="is_active" id="fld_is_active" value="1" checked>
            <label class="form-check-label" for="fld_is_active">Aktif</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<form id="deleteForm" action="<?= site_url('user/delete') ?>" method="post">
  <input type="hidden" name="id" id="del_id">
</form>

<script>
var UCFG = {
  data_url    : '<?= $data_url ?>',
  get_url     : '<?= site_url('user/get') ?>',
  unit_url    : '<?= site_url('user/unit_options') ?>',
  peg_url     : '<?= site_url('user/pegawai_search') ?>',
  is_super    : <?= $is_super ? 'true' : 'false' ?>,
  my_opd      : '<?= $my_opd ?>'
};
(function () {
  var esc = function (v) { return v === null || v === undefined ? '-' : $('<div>').text(String(v)).html(); };
  var roleLabel = { superadmin:'Super Admin', admin_opd:'Admin OPD', user_opd:'User OPD' };

  // ---- DataTable ----
  var table = $('#tbl').DataTable({
    processing  : true,
    serverSide  : true,
    order       : [[1, 'asc']],
    ajax: {
      url  : UCFG.data_url,
      data : function (d) {
        d.f_role   = $('#flt_role').val();
        d.f_opd_id = $('#flt_opd').val() || '';
        d.f_status = $('#flt_status').val();
      }
    },
    columns: [
      { data: null, orderable: false, searchable: false,
        render: function (d, t, r, m) { return m.row + m.settings._iDisplayStart + 1; } },
      { data: 'nama',      render: esc },
      { data: 'identitas', orderable: false, render: esc },
      { data: 'role', orderable: false,
        render: function (v) { return '<span class="badge badge-soft-primary">' + (roleLabel[v] || esc(v)) + '</span>'; } },
      { data: 'opd_nama', render: esc },
      { data: 'is_active', orderable: false,
        render: function (v) { return Number(v)
          ? '<span class="badge badge-soft-success">Aktif</span>'
          : '<span class="badge badge-soft-secondary">Nonaktif</span>'; } },
      { data: 'id', orderable: false, searchable: false, className: 'text-end',
        render: function (id) {
          return '<button class="btn btn-sm btn-icon btn-outline-primary me-1 btn-edit" data-id="' + id + '" title="Edit"><i class="fa-solid fa-pen"></i></button>'
               + '<button class="btn btn-sm btn-icon btn-outline-danger btn-del" data-id="' + id + '" title="Hapus"><i class="fa-solid fa-trash"></i></button>';
        }}
    ],
    pageLength  : 25,
    lengthMenu  : [[25, 50, 100, -1], [25, 50, 100, 'Semua']],
    language    : {
      processing:'Memuat…', search:'Cari:', lengthMenu:'Tampil _MENU_ baris',
      info:'Menampilkan _START_–_END_ dari _TOTAL_ data', infoEmpty:'Tidak ada data',
      infoFiltered:'(disaring dari _MAX_ total)', zeroRecords:'Data tidak ditemukan',
      paginate:{ first:'Awal', last:'Akhir', next:'›', previous:'‹' }
    }
  });

  // Filter reload
  $('.filter-input').on('change', function () { table.ajax.reload(); });
  $('#btnResetFilter').on('click', function () { $('.filter-input').val(''); table.ajax.reload(); });

  // ---- Role toggle ----
  function toggleByRole() {
    var r = $('#fld_role').val();
    var isSuper = (r === 'superadmin');
    $('.blk-super').toggle(isSuper);
    $('.blk-opd').toggle(!isSuper);
  }
  $('#fld_role').on('change', toggleByRole);

  // ---- Unit OPD cascade ----
  function loadUnits(opd, cb) {
    $.getJSON(UCFG.unit_url + '?parent=' + encodeURIComponent(opd || ''), function (opts) {
      var html = '<option value="">— Pilih Unit —</option>';
      Object.keys(opts).forEach(function (k) { html += '<option value="' + k + '">' + esc(opts[k]) + '</option>'; });
      $('#fld_opd_unit_id').html(html);
      if (cb) cb();
    });
  }
  $('#fld_opd_id').on('change', function () { loadUnits(this.value); });

  // ---- Pegawai search widget ----
  var pegTimer = null;

  function clearPegawai() {
    $('#fld_pegawai_id').val('');
    $('#peg_info').hide();
    $('#peg_dropdown').hide();
  }

  function selectPegawai(p) {
    $('#fld_pegawai_id').val(p.id);
    $('#fld_nama').val(p.nama_lengkap);
    $('#peg_search').val(p.nama_lengkap + (p.nip ? ' — ' + p.nip : ''));
    $('#peg_info_text').text(p.jenis_kepegawaian + (p.opd_nama ? ' · ' + p.opd_nama : ''));
    $('#peg_info').show();
    $('#peg_dropdown').hide();
    if (UCFG.is_super) {
      $('#fld_opd_id').val(p.opd_id || '');
      loadUnits(p.opd_id || '', function () { $('#fld_opd_unit_id').val(p.opd_unit_id || ''); });
    } else {
      loadUnits(UCFG.my_opd, function () { $('#fld_opd_unit_id').val(p.opd_unit_id || ''); });
    }
  }

  $('#peg_search').on('input', function () {
    clearTimeout(pegTimer);
    var q = $(this).val().trim();
    $('#fld_pegawai_id').val('');
    $('#peg_info').hide();
    if (q.length < 2) { $('#peg_dropdown').hide(); return; }
    pegTimer = setTimeout(function () {
      $.getJSON(UCFG.peg_url + '?q=' + encodeURIComponent(q), function (rows) {
        var html = '';
        if (rows.length === 0) {
          html = '<button type="button" class="list-group-item list-group-item-action text-muted small disabled">Tidak ditemukan</button>';
        } else {
          rows.forEach(function (p) {
            html += '<button type="button" class="list-group-item list-group-item-action peg-item small"'
              + ' data-id="' + p.id + '"'
              + ' data-nama="' + esc(p.nama_lengkap) + '"'
              + ' data-nip="' + esc(p.nip || '') + '"'
              + ' data-opd="' + (p.opd_id || '') + '"'
              + ' data-unit="' + (p.opd_unit_id || '') + '"'
              + ' data-opd-nama="' + esc(p.nama_opd || '') + '"'
              + ' data-jenis="' + esc(p.jenis_kepegawaian || '') + '">'
              + '<strong>' + esc(p.nama_lengkap) + '</strong>'
              + '<span class="text-muted ms-2">' + esc(p.nip || '—') + '</span>'
              + '<span class="d-block text-muted" style="font-size:.8em">' + esc(p.nama_opd || '') + '</span>'
              + '</button>';
          });
        }
        $('#peg_dropdown').html(html).show();
      });
    }, 280);
  });

  $(document).on('click', '.peg-item', function () {
    selectPegawai({
      id             : $(this).data('id'),
      nama_lengkap   : $(this).data('nama'),
      nip            : $(this).data('nip'),
      opd_id         : $(this).data('opd'),
      opd_unit_id    : $(this).data('unit'),
      nama_opd       : $(this).data('opd-nama'),
      jenis_kepegawaian: $(this).data('jenis')
    });
  });

  $(document).on('mousedown', function (e) {
    if (!$(e.target).closest('#peg_search, #peg_dropdown').length) {
      $('#peg_dropdown').hide();
    }
  });

  // ---- Reset form helper ----
  function resetForm() {
    document.querySelector('#formModal form').reset();
    $('#f_id').val('');
    $('#fld_opd_unit_id').html('<option value="">— Pilih Unit —</option>');
    clearPegawai();
    $('#peg_search').val('');
  }

  // ---- Tambah ----
  $('#btnAdd').on('click', function () {
    resetForm();
    $('#modalTitle').text('Tambah Pengguna');
    $('#pwdHint').text('(wajib untuk pengguna baru)');
    <?php if (!$is_super): ?>loadUnits(UCFG.my_opd);<?php endif; ?>
    toggleByRole();
    new bootstrap.Modal('#formModal').show();
  });

  // ---- Edit ----
  $('#tbl').on('click', '.btn-edit', function () {
    var id = $(this).data('id');
    $.getJSON(UCFG.get_url + '/' + id, function (row) {
      resetForm();
      $('#f_id').val(row.id);
      $('#modalTitle').text('Edit Pengguna');
      $('#pwdHint').text('(kosongkan bila tidak diubah)');
      $('#fld_nama').val(row.nama);
      $('#fld_role').val(row.role);
      $('#fld_username').val(row.username || '');
      $('#fld_is_active').prop('checked', Number(row.is_active) === 1);
      toggleByRole();

      if (row.role !== 'superadmin') {
        $('#fld_pegawai_id').val(row.pegawai_id || '');
        if (row.pegawai_nama) {
          $('#peg_search').val(row.pegawai_nama + (row.nip ? ' — ' + row.nip : ''));
          $('#peg_info_text').text((row.pegawai_jenis || '') + (row.opd_nama ? ' · ' + row.opd_nama : ''));
          $('#peg_info').show();
        }
        <?php if ($is_super): ?>$('#fld_opd_id').val(row.opd_id || '');<?php endif; ?>
        var opd = row.opd_id || UCFG.my_opd;
        loadUnits(opd, function () { $('#fld_opd_unit_id').val(row.opd_unit_id || ''); });
      }
      new bootstrap.Modal('#formModal').show();
    });
  });

  // ---- Hapus ----
  $('#tbl').on('click', '.btn-del', function () {
    $('#del_id').val($(this).data('id'));
    if (confirm('Yakin ingin menghapus pengguna ini?')) document.getElementById('deleteForm').submit();
  });
})();
</script>
