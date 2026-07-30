<?php defined('BASEPATH') OR exit('No direct script access allowed');
$u = $current_user;
$stats = array(
	array('OPD', number_format($jml_opd, 0, ',', '.'), 'fa-building', 'primary'),
	array('Sub Kegiatan (kewenangan)', number_format($jml_subkeg, 0, ',', '.'), 'fa-list-check', 'success'),
	array('Baris DPA', number_format($jml_dpa, 0, ',', '.'), 'fa-file-invoice-dollar', 'info'),
	array('Penerima', number_format($jml_penerima, 0, ',', '.'), 'fa-hand-holding-dollar', 'warning'),
);
?>
<div class="card mb-4" style="background:linear-gradient(72deg,var(--p-primary),#8a8dff);color:#fff;">
  <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
    <div>
      <h4 class="text-white mb-1">Selamat datang, <?= html_escape($u['nama']) ?> 👋</h4>
      <p class="mb-0 opacity-75">
        <?php if ( ! empty($u['opd_nama'])): ?>
          <?= html_escape($u['opd_nama']) ?><?= ! empty($u['unit_nama']) ? ' &middot; '.html_escape($u['unit_nama']) : '' ?>
        <?php else: ?>
          Anda masuk sebagai Super Administrator dengan akses penuh.
        <?php endif; ?>
      </p>
    </div>
    <i class="fa-solid fa-chart-line d-none d-md-block" style="font-size:3.5rem;opacity:.35;"></i>
  </div>
</div>

<div class="row">
  <?php foreach ($stats as $s): ?>
  <div class="col-sm-6 col-xl-3">
    <div class="card stat-card">
      <div class="card-body d-flex align-items-center">
        <div class="stat-icon bg-<?= $s[3] ?> bg-opacity-10 text-<?= $s[3] ?> me-3">
          <i class="fa-solid <?= $s[2] ?>"></i>
        </div>
        <div>
          <div class="stat-value"><?= $s[1] ?></div>
          <div class="text-muted small"><?= $s[0] ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="fa-solid fa-wallet me-2 text-primary"></i>Total Pagu DPA (sesuai kewenangan)</span>
      </div>
      <div class="card-body">
        <div class="display-6 fw-bold text-primary"><?= rupiah($total_pagu) ?></div>
        <p class="text-muted mb-0"><?= ucfirst(trim(terbilang_rupiah($total_pagu))) ?></p>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-bolt me-2 text-primary"></i>Akses Cepat</div>
      <div class="card-body d-grid gap-2">
        <a href="<?= site_url('anggaran/dpa') ?>" class="btn btn-outline-primary text-start"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Lihat DPA</a>
        <a href="<?= site_url('master/subkegiatan') ?>" class="btn btn-outline-primary text-start"><i class="fa-solid fa-list-check me-2"></i>Sub Kegiatan</a>
        <a href="<?= site_url('master/penerima') ?>" class="btn btn-outline-primary text-start"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Data Penerima</a>
      </div>
    </div>
  </div>
</div>
