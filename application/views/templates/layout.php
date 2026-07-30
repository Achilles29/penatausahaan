<?php defined('BASEPATH') OR exit('No direct script access allowed');
$assets = base_url('assets/');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($page_title) ? html_escape($page_title) : 'Penatausahaan' ?> &middot; Penatausahaan</title>
  <link rel="icon" type="image/x-icon" href="<?= $assets ?>img/favicon.ico">
  <link rel="stylesheet" href="<?= $assets ?>vendor/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="<?= $assets ?>vendor/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="<?= $assets ?>vendor/datatables/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="<?= $assets ?>css/app.css">
  <?php if (isset($extra_css)) echo $extra_css; ?>
</head>
<body>
<div class="layout-wrapper">
  <?php $this->load->view('templates/_sidebar'); ?>
  <div class="sidebar-backdrop"></div>

  <div class="layout-page">
    <?php $this->load->view('templates/_navbar'); ?>

    <div class="content-wrapper">
      <div class="container-fluid">
        <?php if ($msg = $this->session->flashdata('success')): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-1"></i><?= html_escape($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <?php if ($msg = $this->session->flashdata('error')): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-1"></i><?= html_escape($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <?= isset($content) ? $content : '' ?>
      </div>
    </div>

    <footer class="text-center text-muted-2 py-3 small">
      &copy; <?= date('Y') ?> Aplikasi Penatausahaan &middot; v0.1 (Tahap 1)
    </footer>
  </div>
</div>

<script src="<?= $assets ?>vendor/jquery/jquery.min.js"></script>
<script src="<?= $assets ?>vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="<?= $assets ?>vendor/datatables/dataTables.min.js"></script>
<script src="<?= $assets ?>vendor/datatables/dataTables.bootstrap5.min.js"></script>
<script src="<?= $assets ?>js/app.js"></script>
<?php if (isset($extra_js)) echo $extra_js; ?>
</body>
</html>
