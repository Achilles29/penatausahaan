<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Var: $roles,$opd_opts,$is_super,$my_opd,$data_url */
?>
<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <span><i class="fa-solid fa-users-gear me-2 text-primary"></i>Manajemen Pengguna</span>
    <button class="btn btn-primary btn-sm" id="btnAdd"><i class="fa-solid fa-plus me-1"></i> Tambah Pengguna</button>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover table-striped w-100" id="tbl" data-manual="1">
        <thead><tr>
          <th style="width:48px">#</th>
          <th>Nama</th><th style="width:190px">NIP / Username</th><th style="width:120px">Role</th>
          <th>OPD</th><th style="width:100px">Status</th><th style="width:110px" class="text-end">Aksi</th>
        </tr></thead>
      </table>
    </div>
  </div>
</div>

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

          <div class="mb-3 blk-super">
            <label class="form-label">Username <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="username" id="fld_username">
          </div>

          <div class="mb-3 blk-opd">
            <label class="form-label">NIP <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nip" id="fld_nip">
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
            <label class="form-label">Unit OPD <small class="text-muted">(opsional, untuk User OPD)</small></label>
            <select class="form-select" name="opd_unit_id" id="fld_opd_unit_id" data-source="unit">
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

<form id="deleteForm" action="<?= site_url('user/delete') ?>" method="post"><input type="hidden" name="id" id="del_id"></form>

<script>
var UCFG = { data_url:'<?= $data_url ?>', get_url:'<?= site_url('user/get') ?>',
             unit_url:'<?= site_url('user/unit_options') ?>', is_super:<?= $is_super ? 'true':'false' ?>,
             my_opd:'<?= $my_opd ?>' };
(function () {
  var esc=function(v){return v===null||v===undefined?'-':$('<div>').text(v).html();};
  var roleLabel={superadmin:'Super Admin',admin_opd:'Admin OPD',user_opd:'User OPD'};
  var table = $('#tbl').DataTable({
    processing:true, serverSide:true, order:[[1,'asc']],
    ajax:{ url:UCFG.data_url },
    columns:[
      { data:null, orderable:false, searchable:false, render:function(d,t,r,m){return m.row+m.settings._iDisplayStart+1;} },
      { data:'nama' , render:esc },
      { data:'identitas', render:esc },
      { data:'role', orderable:false, render:function(v){return '<span class="badge badge-soft-primary">'+(roleLabel[v]||v)+'</span>';} },
      { data:'opd_nama', render:esc },
      { data:'is_active', orderable:false, render:function(v){return Number(v)?'<span class="badge badge-soft-success">Aktif</span>':'<span class="badge badge-soft-secondary">Nonaktif</span>';} },
      { data:'id', orderable:false, searchable:false, className:'text-end', render:function(id){
          return '<button class="btn btn-sm btn-outline-primary me-1 btn-edit" data-id="'+id+'"><i class="fa-solid fa-pen"></i></button>'
               + '<button class="btn btn-sm btn-outline-danger btn-del" data-id="'+id+'"><i class="fa-solid fa-trash"></i></button>'; } }
    ],
    pageLength:25, lengthMenu:[[25,50,100,-1],[25,50,100,'Semua']],
    language:{ processing:'Memuat…', search:'Cari:', lengthMenu:'Tampil _MENU_ baris',
      info:'Menampilkan _START_–_END_ dari _TOTAL_ data', infoEmpty:'Tidak ada data',
      infoFiltered:'(disaring dari _MAX_)', zeroRecords:'Data tidak ditemukan',
      paginate:{first:'Awal',last:'Akhir',next:'›',previous:'‹'} }
  });

  function toggleByRole() {
    var r = $('#fld_role').val();
    var isSuper = (r === 'superadmin');
    $('.blk-super').toggle(isSuper);
    $('.blk-opd').toggle(!isSuper);
  }
  $('#fld_role').on('change', toggleByRole);

  function loadUnits(opd, cb) {
    $.getJSON(UCFG.unit_url + '?parent=' + encodeURIComponent(opd||''), function (opts) {
      var html='<option value="">— Pilih Unit —</option>';
      Object.keys(opts).forEach(function(k){ html+='<option value="'+k+'">'+esc(opts[k])+'</option>'; });
      $('#fld_opd_unit_id').html(html); if (cb) cb();
    });
  }
  $('#fld_opd_id').on('change', function(){ loadUnits(this.value); });

  $('#btnAdd').on('click', function () {
    document.querySelector('#formModal form').reset();
    $('#f_id').val(''); $('#modalTitle').text('Tambah Pengguna');
    $('#pwdHint').text('(wajib untuk pengguna baru)');
    $('#fld_opd_unit_id').html('<option value="">— Pilih Unit —</option>');
    <?php if (!$is_super): ?> loadUnits(UCFG.my_opd); <?php endif; ?>
    toggleByRole();
    new bootstrap.Modal('#formModal').show();
  });

  $('#tbl').on('click', '.btn-edit', function () {
    var id=$(this).data('id');
    $.getJSON(UCFG.get_url + '/' + id, function (row) {
      document.querySelector('#formModal form').reset();
      $('#f_id').val(row.id); $('#modalTitle').text('Edit Pengguna');
      $('#pwdHint').text('(kosongkan bila tidak diubah)');
      $('#fld_nama').val(row.nama); $('#fld_role').val(row.role);
      $('#fld_username').val(row.username||''); $('#fld_nip').val(row.nip||'');
      <?php if ($is_super): ?> $('#fld_opd_id').val(row.opd_id||''); <?php endif; ?>
      $('#fld_is_active').prop('checked', Number(row.is_active)===1);
      toggleByRole();
      var opd = row.opd_id || UCFG.my_opd;
      if (row.role !== 'superadmin') loadUnits(opd, function(){ $('#fld_opd_unit_id').val(row.opd_unit_id||''); });
      new bootstrap.Modal('#formModal').show();
    });
  });

  $('#tbl').on('click', '.btn-del', function () {
    $('#del_id').val($(this).data('id'));
    if (confirm('Yakin ingin menghapus pengguna ini?')) document.getElementById('deleteForm').submit();
  });
})();
</script>
