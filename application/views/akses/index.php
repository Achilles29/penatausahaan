<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Var: $grup (grup => [ {key,label,perm{role{view,create,edit,delete}}} ]), $roles, $actions, $has_override */
$act_label = array('view'=>'Lihat','create'=>'Tambah','edit'=>'Edit','delete'=>'Hapus');
$role_label = array('admin_opd'=>'Admin OPD','user_opd'=>'User OPD');
?>
<form action="<?= site_url('akses/save') ?>" method="post">
<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
    <span><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Hak Akses (Role Matrix) — izin CRUD per menu</span>
    <div class="d-flex gap-2">
      <?php if ($has_override): ?><a href="<?= site_url('akses/reset') ?>" class="btn btn-sm btn-label-secondary" onclick="return confirm('Kembalikan semua ke default?')"><i class="fa-solid fa-rotate-left me-1"></i>Reset Default</a><?php endif; ?>
      <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan</button>
    </div>
  </div>
  <div class="card-body">
    <p class="text-muted small mb-2">
      <b>Superadmin</b> selalu akses penuh. Mencentang Tambah/Edit/Hapus otomatis mengaktifkan Lihat.
      Batasan <b>data OPD/bidang</b> diatur di menu <a href="<?= site_url('user') ?>">Pengguna</a> (tiap user hanya OPD-nya).
    </p>
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle mb-0" style="min-width:760px">
        <thead class="table-light text-center">
          <tr>
            <th rowspan="2" class="align-middle text-start" style="min-width:230px">Menu / Halaman</th>
            <?php foreach ($roles as $r): ?><th colspan="4"><?= html_escape($role_label[$r]) ?></th><?php endforeach; ?>
          </tr>
          <tr>
            <?php foreach ($roles as $r): foreach ($actions as $a): ?>
              <th style="width:56px" class="small">
                <?= $act_label[$a] ?><br>
                <a href="javascript:void(0)" class="col-toggle text-primary" data-role="<?= $r ?>" data-act="<?= $a ?>" title="pilih/batal semua"><i class="fa-solid fa-check-double"></i></a>
              </th>
            <?php endforeach; endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($grup as $namaGrup => $items): ?>
          <tr class="table-light"><td colspan="<?= 1 + count($roles)*4 ?>" class="fw-semibold"><i class="fa-solid fa-folder-open me-1 text-primary"></i><?= html_escape($namaGrup) ?></td></tr>
          <?php foreach ($items as $it): ?>
          <tr>
            <td><?= html_escape($it['label']) ?> <span class="text-muted-2 small">(<?= html_escape($it['key']) ?>)</span></td>
            <?php foreach ($roles as $r): foreach ($actions as $a): ?>
              <td class="text-center">
                <input type="checkbox" class="form-check-input chk chk-<?= $r ?>-<?= $a ?> <?= $a==='view'?('view-'.$r):'' ?>"
                       data-role="<?= $r ?>" data-act="<?= $a ?>"
                       name="p[<?= $r ?>][<?= html_escape($it['key']) ?>][<?= $a ?>]" value="1"
                       <?= $it['perm'][$r][$a] ? 'checked' : '' ?>>
              </td>
            <?php endforeach; endforeach; ?>
          </tr>
          <?php endforeach; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="text-end mt-3"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Hak Akses</button></div>
  </div>
</div>
</form>
<script>
(function(){
  // Toggle kolom
  document.querySelectorAll('.col-toggle').forEach(function(a){
    a.addEventListener('click', function(){
      var boxes = document.querySelectorAll('.chk-'+this.dataset.role+'-'+this.dataset.act);
      var allOn = Array.prototype.every.call(boxes, function(b){return b.checked;});
      boxes.forEach(function(b){ b.checked=!allOn; b.dispatchEvent(new Event('change')); });
    });
  });
  // CRUD implies view
  document.querySelectorAll('.chk').forEach(function(b){
    b.addEventListener('change', function(){
      if(this.dataset.act!=='view' && this.checked){
        var v = this.closest('tr').querySelector('.view-'+this.dataset.role);
        if(v) v.checked = true;
      }
    });
  });
})();
</script>
