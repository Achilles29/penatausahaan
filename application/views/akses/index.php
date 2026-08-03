<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Var: $grup (grup => [ {key,label,state{admin_opd,user_opd}} ]), $has_override */
?>
<form action="<?= site_url('akses/save') ?>" method="post">
<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
    <span><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Hak Akses Menu (Role Matrix)</span>
    <div class="d-flex gap-2">
      <?php if ($has_override): ?>
      <a href="<?= site_url('akses/reset') ?>" class="btn btn-sm btn-label-secondary" onclick="return confirm('Kembalikan semua ke default?')"><i class="fa-solid fa-rotate-left me-1"></i>Reset Default</a>
      <?php endif; ?>
      <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan</button>
    </div>
  </div>
  <div class="card-body">
    <p class="text-muted small">
      Centang menu yang boleh diakses tiap role. <b>Superadmin</b> selalu memiliki akses penuh.
      Pembatasan <b>data</b> (per bidang/OPD) diatur terpisah di menu <a href="<?= site_url('user') ?>">Pengguna</a>.
    </p>
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle">
        <thead class="table-light">
          <tr>
            <th style="min-width:240px">Menu</th>
            <th class="text-center" style="width:120px">Superadmin</th>
            <th class="text-center" style="width:120px">Admin OPD</th>
            <th class="text-center" style="width:120px">User OPD</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($grup as $namaGrup => $items): ?>
          <tr class="table-light">
            <td colspan="4" class="fw-semibold"><i class="fa-solid fa-folder-open me-1 text-primary"></i><?= html_escape($namaGrup) ?>
              <a href="javascript:void(0)" class="small ms-2 grp-toggle" data-grp="<?= md5($namaGrup) ?>">(pilih semua / batal)</a></td>
          </tr>
          <?php foreach ($items as $it): ?>
          <tr>
            <td><?= html_escape($it['label']) ?> <span class="text-muted-2 small">(<?= html_escape($it['key']) ?>)</span></td>
            <td class="text-center"><input type="checkbox" class="form-check-input" checked disabled title="Superadmin selalu penuh"></td>
            <td class="text-center"><input type="checkbox" class="form-check-input chk grp-<?= md5($namaGrup) ?>" data-role="admin_opd" name="m[admin_opd][<?= html_escape($it['key']) ?>]" value="1" <?= $it['state']['admin_opd'] ? 'checked' : '' ?>></td>
            <td class="text-center"><input type="checkbox" class="form-check-input chk grp-<?= md5($namaGrup) ?>" data-role="user_opd" name="m[user_opd][<?= html_escape($it['key']) ?>]" value="1" <?= $it['state']['user_opd'] ? 'checked' : '' ?>></td>
          </tr>
          <?php endforeach; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="text-end"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Hak Akses</button></div>
  </div>
</div>
</form>
<script>
document.querySelectorAll('.grp-toggle').forEach(function(a){
  a.addEventListener('click', function(){
    var boxes = document.querySelectorAll('.grp-'+this.dataset.grp);
    var allOn = Array.prototype.every.call(boxes, function(b){ return b.checked; });
    boxes.forEach(function(b){ b.checked = !allOn; });
  });
});
</script>
