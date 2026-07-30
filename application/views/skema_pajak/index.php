<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Var: $schemes (dgn ->details), $can_manage, $kategori_opts */
$npwp_label = function ($v) {
	if ($v === NULL || $v === '') return '<span class="badge badge-soft-secondary">Semua</span>';
	return ((int)$v === 1)
		? '<span class="badge badge-soft-success">Ber-NPWP</span>'
		: '<span class="badge badge-soft-primary">Tanpa NPWP</span>';
};
$batas_label = function ($min, $max) {
	$min = (float) $min; $max = ($max === NULL || $max === '') ? NULL : (float) $max;
	if ($min <= 0 && $max === NULL) return 'Semua nilai';
	if ($max === NULL) return '&ge; Rp ' . number_format($min, 0, ',', '.');
	if ($min <= 0)     return '&le; Rp ' . number_format($max, 0, ',', '.');
	return 'Rp ' . number_format($min, 0, ',', '.') . ' – Rp ' . number_format($max, 0, ',', '.');
};
$basis_label = array('langsung' => 'Langsung dari bruto', 'ppn_included' => 'PPN termasuk harga', 'setelah_ppn' => 'Setelah dikurangi PPN');
$jenis_opts  = array('PPH21'=>'PPh 21','PPH22'=>'PPh 22','PPH23'=>'PPh 23','PPH4_2'=>'PPh Pasal 4(2)','PPN'=>'PPN','PDRD'=>'Pajak Daerah (PDRD)');
?>
<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
    <span><i class="fa-solid fa-percent me-2 text-primary"></i>Skema Pajak</span>
    <div class="d-flex align-items-center gap-2">
      <small class="text-muted d-none d-md-inline">Tarif dapat berbeda menurut nilai pembayaran & status NPWP</small>
      <?php if ($can_manage): ?><button class="btn btn-primary btn-sm" id="btnAddSkema"><i class="fa-solid fa-plus me-1"></i> Tambah Skema</button><?php endif; ?>
    </div>
  </div>
  <div class="card-body">
    <div class="accordion" id="accSkema">
      <?php foreach ($schemes as $i => $s): ?>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button <?= $i>0?'collapsed':'' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#sk<?= $s->id ?>">
            <span class="d-flex flex-wrap align-items-center gap-2 w-100 pe-3">
              <span class="badge badge-soft-primary text-uppercase"><?= html_escape($s->kategori) ?></span>
              <strong><?= html_escape($s->nama_skema) ?></strong>
              <small class="text-muted">(<?= html_escape($s->kode_skema) ?>)</small>
              <span class="badge bg-label-secondary ms-auto"><?= count($s->details) ?> aturan</span>
              <?php if ( ! $s->is_active): ?><span class="badge badge-soft-secondary">Nonaktif</span><?php endif; ?>
            </span>
          </button>
        </h2>
        <div id="sk<?= $s->id ?>" class="accordion-collapse collapse <?= $i===0?'show':'' ?>" data-bs-parent="#accSkema">
          <div class="accordion-body">
            <?php if ($s->keterangan): ?><p class="text-muted small mb-2"><i class="fa-solid fa-circle-info me-1"></i><?= html_escape($s->keterangan) ?></p><?php endif; ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-semibold">Aturan / Besaran Pajak</span>
              <?php if ($can_manage): ?>
              <span>
                <button class="btn btn-sm btn-outline-primary btn-edit-skema" data-id="<?= $s->id ?>"><i class="fa-solid fa-pen me-1"></i>Edit Skema</button>
                <button class="btn btn-sm btn-outline-danger btn-del-skema" data-id="<?= $s->id ?>"><i class="fa-solid fa-trash"></i></button>
                <button class="btn btn-sm btn-primary btn-add-rule" data-skema="<?= $s->id ?>"><i class="fa-solid fa-plus me-1"></i>Tambah Aturan</button>
              </span>
              <?php endif; ?>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Jenis Pajak</th><th class="text-end">Tarif</th><th>Batas Nilai</th>
                    <th>Syarat NPWP</th><th>Golongan</th><th>Basis</th><th>Keterangan</th>
                    <?php if ($can_manage): ?><th style="width:90px" class="text-end">Aksi</th><?php endif; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($s->details)): ?>
                    <tr><td colspan="<?= $can_manage?8:7 ?>" class="text-center text-muted py-3">Tanpa pemotongan pajak (belum ada aturan).</td></tr>
                  <?php else: foreach ($s->details as $d): ?>
                  <tr>
                    <td><span class="badge bg-label-primary"><?= isset($jenis_opts[$d->jenis_pajak])?$jenis_opts[$d->jenis_pajak]:$d->jenis_pajak ?></span></td>
                    <td class="text-end fw-semibold"><?= rtrim(rtrim(number_format($d->tarif,2,',','.'),'0'),',') ?>%</td>
                    <td><?= $batas_label($d->batas_min, $d->batas_max) ?></td>
                    <td><?= $npwp_label($d->punya_npwp) ?></td>
                    <td><?= $d->golongan_honor ? html_escape($d->golongan_honor) : '<span class="text-muted">–</span>' ?></td>
                    <td class="small"><?= isset($basis_label[$d->basis_penghitungan])?$basis_label[$d->basis_penghitungan]:$d->basis_penghitungan ?></td>
                    <td class="small text-muted"><?= html_escape($d->keterangan) ?></td>
                    <?php if ($can_manage): ?>
                    <td class="text-end">
                      <button class="btn btn-xs btn-outline-primary btn-edit-rule" data-id="<?= $d->id ?>" title="Edit"><i class="fa-solid fa-pen"></i></button>
                      <button class="btn btn-xs btn-outline-danger btn-del-rule" data-id="<?= $d->id ?>" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                    </td>
                    <?php endif; ?>
                  </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php if ($can_manage): ?>
<!-- Modal Skema -->
<div class="modal fade" id="skemaModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form action="<?= site_url('skema_pajak/save_skema') ?>" method="post">
    <div class="modal-header"><h5 class="modal-title" id="skemaTitle">Tambah Skema</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" name="id" id="sk_id">
      <div class="mb-3"><label class="form-label">Kode Skema <span class="text-danger">*</span></label><input type="text" class="form-control" name="kode_skema" id="sk_kode" required></div>
      <div class="mb-3"><label class="form-label">Nama Skema <span class="text-danger">*</span></label><input type="text" class="form-control" name="nama_skema" id="sk_nama" required></div>
      <div class="mb-3"><label class="form-label">Kategori (tertaut ke rekening) <span class="text-danger">*</span></label>
        <select class="form-select" name="kategori" id="sk_kategori" required>
          <?php foreach ($kategori_opts as $kv=>$kl): ?><option value="<?= $kv ?>"><?= html_escape($kl) ?></option><?php endforeach; ?>
        </select></div>
      <div class="mb-3"><label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan" id="sk_ket" rows="2"></textarea></div>
      <div class="form-check form-switch"><input type="checkbox" class="form-check-input" name="is_active" id="sk_aktif" value="1" checked><label class="form-check-label">Aktif</label></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
  </form>
</div></div></div>

<!-- Modal Aturan -->
<div class="modal fade" id="ruleModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form action="<?= site_url('skema_pajak/save_detail') ?>" method="post">
    <div class="modal-header"><h5 class="modal-title" id="ruleTitle">Tambah Aturan Pajak</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" name="id" id="rl_id"><input type="hidden" name="skema_id" id="rl_skema">
      <div class="row g-3">
        <div class="col-6"><label class="form-label">Jenis Pajak <span class="text-danger">*</span></label>
          <select class="form-select" name="jenis_pajak" id="rl_jenis" required>
            <?php foreach ($jenis_opts as $jv=>$jl): ?><option value="<?= $jv ?>"><?= html_escape($jl) ?></option><?php endforeach; ?>
          </select></div>
        <div class="col-6"><label class="form-label">Tarif (%) <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" name="tarif" id="rl_tarif" required></div>
        <div class="col-6"><label class="form-label">Batas Nilai Min (Rp)</label><input type="number" step="1" class="form-control" name="batas_min" id="rl_min" value="0"></div>
        <div class="col-6"><label class="form-label">Batas Nilai Max (Rp)</label><input type="number" step="1" class="form-control" name="batas_max" id="rl_max" placeholder="kosong = tak terbatas"></div>
        <div class="col-6"><label class="form-label">Syarat NPWP</label>
          <select class="form-select" name="punya_npwp" id="rl_npwp"><option value="">Semua</option><option value="1">Ber-NPWP</option><option value="0">Tanpa NPWP</option></select></div>
        <div class="col-6"><label class="form-label">Golongan (honorarium)</label>
          <select class="form-select" name="golongan_honor" id="rl_gol"><option value="">–</option><option>I</option><option>II</option><option>III</option><option>IV</option><option value="NON_PNS">NON_PNS</option></select></div>
        <div class="col-6"><label class="form-label">Basis Penghitungan</label>
          <select class="form-select" name="basis_penghitungan" id="rl_basis"><option value="langsung">Langsung dari bruto</option><option value="ppn_included">PPN termasuk harga</option><option value="setelah_ppn">Setelah dikurangi PPN</option></select></div>
        <div class="col-6"><label class="form-label">Kelompok</label>
          <select class="form-select" name="kelompok" id="rl_kelompok"><option value="opsional">Opsional</option><option value="exclusive">Exclusive (pilih satu)</option></select></div>
        <div class="col-12"><label class="form-label">Keterangan</label><input type="text" class="form-control" name="keterangan" id="rl_ket"></div>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
  </form>
</div></div></div>

<form id="delSkemaForm" action="<?= site_url('skema_pajak/delete_skema') ?>" method="post"><input type="hidden" name="id" id="del_skema_id"></form>
<form id="delRuleForm" action="<?= site_url('skema_pajak/delete_detail') ?>" method="post"><input type="hidden" name="id" id="del_rule_id"></form>

<script>
(function(){
  var getSkema='<?= site_url('skema_pajak/get_skema') ?>', getDetail='<?= site_url('skema_pajak/get_detail') ?>';
  function setVal(id,v){ var el=document.getElementById(id); if(!el)return; if(el.type==='checkbox')el.checked=Number(v)===1; else el.value=(v===null||v===undefined)?'':v; }

  document.getElementById('btnAddSkema').addEventListener('click',function(){
    document.querySelector('#skemaModal form').reset(); setVal('sk_id',''); document.getElementById('skemaTitle').textContent='Tambah Skema';
    new bootstrap.Modal('#skemaModal').show();
  });
  document.addEventListener('click',function(e){
    var b;
    if(b=e.target.closest('.btn-edit-skema')){ $.getJSON(getSkema+'/'+b.dataset.id,function(r){
      document.querySelector('#skemaModal form').reset(); document.getElementById('skemaTitle').textContent='Edit Skema';
      setVal('sk_id',r.id);setVal('sk_kode',r.kode_skema);setVal('sk_nama',r.nama_skema);setVal('sk_kategori',r.kategori);setVal('sk_ket',r.keterangan);setVal('sk_aktif',r.is_active);
      new bootstrap.Modal('#skemaModal').show(); }); }
    else if(b=e.target.closest('.btn-del-skema')){ if(confirm('Hapus skema ini beserta semua aturannya?')){ document.getElementById('del_skema_id').value=b.dataset.id; document.getElementById('delSkemaForm').submit(); } }
    else if(b=e.target.closest('.btn-add-rule')){ document.querySelector('#ruleModal form').reset(); setVal('rl_id',''); setVal('rl_skema',b.dataset.skema); setVal('rl_min','0'); document.getElementById('ruleTitle').textContent='Tambah Aturan Pajak'; new bootstrap.Modal('#ruleModal').show(); }
    else if(b=e.target.closest('.btn-edit-rule')){ $.getJSON(getDetail+'/'+b.dataset.id,function(r){
      document.querySelector('#ruleModal form').reset(); document.getElementById('ruleTitle').textContent='Edit Aturan Pajak';
      setVal('rl_id',r.id);setVal('rl_skema',r.skema_id);setVal('rl_jenis',r.jenis_pajak);setVal('rl_tarif',r.tarif);setVal('rl_min',r.batas_min);setVal('rl_max',r.batas_max);
      setVal('rl_npwp',r.punya_npwp===null?'':r.punya_npwp);setVal('rl_gol',r.golongan_honor||'');setVal('rl_basis',r.basis_penghitungan);setVal('rl_kelompok',r.kelompok);setVal('rl_ket',r.keterangan);
      new bootstrap.Modal('#ruleModal').show(); }); }
    else if(b=e.target.closest('.btn-del-rule')){ if(confirm('Hapus aturan pajak ini?')){ document.getElementById('del_rule_id').value=b.dataset.id; document.getElementById('delRuleForm').submit(); } }
  });
})();
</script>
<?php endif; ?>
