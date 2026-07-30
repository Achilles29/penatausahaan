<?php defined('BASEPATH') OR exit('No direct script access allowed');
$assets = base_url('assets/');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk &middot; Penatausahaan</title>
  <link rel="icon" type="image/x-icon" href="<?= $assets ?>img/favicon.ico">
  <link rel="stylesheet" href="<?= $assets ?>vendor/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="<?= $assets ?>vendor/fontawesome/css/all.min.css?v=fa6">
  <link rel="stylesheet" href="<?= $assets ?>css/app.css?v=2">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-brand">
      <img src="<?= $assets ?>img/logo.png" alt="Logo" style="height:48px;width:auto;"><br>
      <span class="mt-2 d-inline-block">Penatausahaan</span>
    </div>
    <div class="card">
      <div class="card-body p-4">
        <h5 class="mb-1">Selamat datang 👋</h5>
        <p class="text-muted mb-4">Masuk untuk melanjutkan ke aplikasi.</p>

        <?php if ($err = $this->session->flashdata('login_error')): ?>
          <div class="alert alert-danger py-2"><i class="fa-solid fa-circle-exclamation me-1"></i><?= html_escape($err) ?></div>
        <?php endif; ?>
        <?php if (validation_errors()): ?>
          <div class="alert alert-danger py-2"><?= validation_errors() ?></div>
        <?php endif; ?>

        <form action="<?= site_url('auth/login') ?>" method="post">
          <div class="mb-3">
            <label class="form-label">NIP / Username</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
              <input type="text" name="identitas" class="form-control" placeholder="Masukkan NIP atau username"
                     value="<?= set_value('identitas') ?>" autofocus required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Kata sandi</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
              <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-right-to-bracket me-1"></i> Masuk</button>
        </form>
      </div>
    </div>
    <p class="text-center text-muted-2 small mt-3 mb-0">&copy; <?= date('Y') ?> Aplikasi Penatausahaan</p>
  </div>
</div>
</body>
</html>
