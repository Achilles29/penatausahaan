<?php defined('BASEPATH') OR exit('No direct script access allowed');
$u = $current_user;
$role_label = array('superadmin' => 'Super Admin', 'admin_opd' => 'Admin OPD', 'user_opd' => 'User OPD');
$rl = isset($role_label[$u['role']]) ? $role_label[$u['role']] : $u['role'];
$initial = strtoupper(mb_substr($u['nama'], 0, 1));
?>
<nav class="layout-navbar">
  <button class="navbar-toggle me-2"><i class="fa-solid fa-bars"></i></button>

  <div class="ms-auto d-flex align-items-center">
    <?php if ( ! empty($u['opd_nama'])): ?>
      <span class="d-none d-md-inline text-muted me-3 small">
        <i class="fa-solid fa-building me-1"></i><?= html_escape($u['opd_nama']) ?>
      </span>
    <?php endif; ?>

    <div class="dropdown">
      <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="d-flex align-items-center justify-content-center rounded-circle text-white"
              style="width:38px;height:38px;background:var(--p-primary);font-weight:600;"><?= $initial ?></span>
        <span class="ms-2 d-none d-sm-block text-start">
          <span class="d-block fw-semibold" style="color:var(--p-heading);line-height:1.1;"><?= html_escape($u['nama']) ?></span>
          <small class="text-muted"><?= html_escape($rl) ?></small>
        </span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end shadow">
        <li class="px-3 py-2">
          <div class="fw-semibold"><?= html_escape($u['nama']) ?></div>
          <small class="text-muted"><?= $u['nip'] ? 'NIP: '.html_escape($u['nip']) : html_escape($u['username']) ?></small>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="<?= site_url('auth/logout') ?>"><i class="fa-solid fa-right-from-bracket me-2"></i>Keluar</a></li>
      </ul>
    </div>
  </div>
</nav>
