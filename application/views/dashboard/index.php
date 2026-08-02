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

<div class="row mb-4">
  <div class="col-lg-8">
    <div class="card h-100">
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
    <div class="card h-100">
      <div class="card-header"><i class="fa-solid fa-bolt me-2 text-primary"></i>Akses Cepat</div>
      <div class="card-body d-grid gap-2">
        <a href="<?= site_url('anggaran/dpa') ?>" class="btn btn-outline-primary text-start"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Lihat DPA</a>
        <a href="<?= site_url('master/subkegiatan') ?>" class="btn btn-outline-primary text-start"><i class="fa-solid fa-list-check me-2"></i>Sub Kegiatan</a>
        <a href="<?= site_url('master/penerima') ?>" class="btn btn-outline-primary text-start"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Data Penerima</a>
      </div>
    </div>
  </div>
</div>

<?php if ($breakdown['type'] !== 'none' && count($breakdown['rows'])): ?>
<div class="card">
  <div class="card-header">
    <?php if ($breakdown['type'] === 'opd'): ?>
      <i class="fa-solid fa-building me-2 text-primary"></i>Ringkasan DPA per OPD
    <?php elseif ($breakdown['type'] === 'program'): ?>
      <i class="fa-solid fa-diagram-project me-2 text-primary"></i>Ringkasan DPA per Program
    <?php else: ?>
      <i class="fa-solid fa-list-check me-2 text-primary"></i>Sub Kegiatan dalam Kewenangan
    <?php endif; ?>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:40px">#</th>
            <?php if ($breakdown['type'] === 'opd'): ?>
              <th style="width:110px">Kode OPD</th><th>Nama OPD</th>
            <?php elseif ($breakdown['type'] === 'program'): ?>
              <th style="width:150px">Kode Program</th><th>Nama Program</th>
            <?php else: ?>
              <th style="width:180px">Kode Sub Kegiatan</th><th>Nama Sub Kegiatan</th>
            <?php endif; ?>
            <?php if ($breakdown['type'] !== 'subkegiatan'): ?>
              <th style="width:90px" class="text-center">Sub Keg</th>
            <?php endif; ?>
            <th style="width:190px" class="text-end">Pagu (Rp)</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1; $grand = 0; foreach ($breakdown['rows'] as $r): $grand += (float)$r['total_pagu']; ?>
          <tr>
            <td class="text-muted small"><?= $no++ ?></td>
            <?php if ($breakdown['type'] === 'opd'): ?>
              <td><code class="small"><?= html_escape($r['kode_opd']) ?></code></td>
              <td><?= html_escape($r['nama']) ?></td>
            <?php elseif ($breakdown['type'] === 'program'): ?>
              <td><code class="small"><?= html_escape($r['kode_program']) ?></code></td>
              <td><?= html_escape($r['nama_program']) ?></td>
            <?php else: ?>
              <td><code class="small"><?= html_escape($r['kode_subkegiatan']) ?></code></td>
              <td><?= html_escape($r['nama_subkegiatan']) ?></td>
            <?php endif; ?>
            <?php if ($breakdown['type'] !== 'subkegiatan'): ?>
              <td class="text-center"><?= number_format($r['jml_subkeg'], 0, ',', '.') ?></td>
            <?php endif; ?>
            <td class="text-end fw-semibold <?= (float)$r['total_pagu'] > 0 ? '' : 'text-muted' ?>"><?= rupiah((float)$r['total_pagu']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light">
          <tr>
            <td colspan="<?= $breakdown['type'] === 'subkegiatan' ? 3 : 4 ?>" class="fw-bold text-end">Total</td>
            <td class="text-end fw-bold text-primary"><?= rupiah($grand) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>
