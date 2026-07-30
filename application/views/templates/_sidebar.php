<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Sidebar menu — role-aware. Aktif ditentukan dari URI.
 */
$seg1 = $this->uri->segment(1);          // controller
$seg2 = $this->uri->segment(2);          // method
$seg3 = $this->uri->segment(3);          // entity (mis. master/index/urusan)
$role = current_role();

// helper penanda aktif
$is = function ($c, $entity = NULL) use ($seg1, $seg3) {
	if ($entity === NULL) return ($seg1 === $c);
	return ($seg1 === 'master' && $seg3 === $entity);
};
$nom_active  = ($seg1 === 'master' && in_array($seg3, array('urusan','bidang','program','kegiatan','subkegiatan','rekening','sumber_dana'), TRUE));
$org_active  = ($seg1 === 'master' && in_array($seg3, array('opd','opd_unit','pemetaan'), TRUE));
?>
<aside class="layout-sidebar">
  <div class="sidebar-brand">
    <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" style="height:34px;width:auto;">
    <span>Penatausahaan</span>
  </div>

  <ul class="sidebar-menu">
    <li class="menu-item">
      <a class="menu-link <?= $is('dashboard') ? 'active' : '' ?>" href="<?= site_url('dashboard') ?>">
        <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
      </a>
    </li>

    <li class="menu-header">Master Data</li>

    <li class="menu-item <?= $nom_active ? 'open' : '' ?>">
      <a class="menu-link menu-toggle" href="javascript:void(0)">
        <i class="fa-solid fa-sitemap"></i><span>Nomenklatur</span>
        <i class="fa-solid fa-chevron-right arrow"></i>
      </a>
      <ul class="menu-sub">
        <li class="menu-item"><a class="menu-link <?= $is('master','urusan')?'active':'' ?>" href="<?= site_url('master/urusan') ?>">Urusan</a></li>
        <li class="menu-item"><a class="menu-link <?= $is('master','bidang')?'active':'' ?>" href="<?= site_url('master/bidang') ?>">Bidang Urusan</a></li>
        <li class="menu-item"><a class="menu-link <?= $is('master','program')?'active':'' ?>" href="<?= site_url('master/program') ?>">Program</a></li>
        <li class="menu-item"><a class="menu-link <?= $is('master','kegiatan')?'active':'' ?>" href="<?= site_url('master/kegiatan') ?>">Kegiatan</a></li>
        <li class="menu-item"><a class="menu-link <?= $is('master','subkegiatan')?'active':'' ?>" href="<?= site_url('master/subkegiatan') ?>">Sub Kegiatan</a></li>
        <li class="menu-item"><a class="menu-link <?= $is('master','rekening')?'active':'' ?>" href="<?= site_url('master/rekening') ?>">Rekening</a></li>
        <li class="menu-item"><a class="menu-link <?= $is('master','sumber_dana')?'active':'' ?>" href="<?= site_url('master/sumber_dana') ?>">Sumber Dana</a></li>
      </ul>
    </li>

    <li class="menu-item <?= $org_active ? 'open' : '' ?>">
      <a class="menu-link menu-toggle" href="javascript:void(0)">
        <i class="fa-solid fa-building"></i><span>Organisasi</span>
        <i class="fa-solid fa-chevron-right arrow"></i>
      </a>
      <ul class="menu-sub">
        <li class="menu-item"><a class="menu-link <?= $is('master','opd')?'active':'' ?>" href="<?= site_url('master/opd') ?>">OPD</a></li>
        <li class="menu-item"><a class="menu-link <?= $is('master','opd_unit')?'active':'' ?>" href="<?= site_url('master/opd_unit') ?>">Unit OPD</a></li>
        <?php if ($role === 'superadmin'): ?>
        <li class="menu-item"><a class="menu-link <?= $is('master','pemetaan')?'active':'' ?>" href="<?= site_url('master/pemetaan') ?>">Pemetaan OPD</a></li>
        <?php endif; ?>
      </ul>
    </li>

    <li class="menu-item">
      <a class="menu-link <?= $is('master','pegawai')?'active':'' ?>" href="<?= site_url('master/pegawai') ?>">
        <i class="fa-solid fa-user-tie"></i><span>Pegawai</span>
      </a>
    </li>
    <li class="menu-item">
      <a class="menu-link <?= $is('master','ref_jabatan')?'active':'' ?>" href="<?= site_url('master/ref_jabatan') ?>">
        <i class="fa-solid fa-sitemap"></i><span>Master Jabatan</span>
      </a>
    </li>
    <li class="menu-item">
      <a class="menu-link <?= $is('master','penerima')?'active':'' ?>" href="<?= site_url('master/penerima') ?>">
        <i class="fa-solid fa-hand-holding-dollar"></i><span>Penerima</span>
      </a>
    </li>
    <li class="menu-item">
      <a class="menu-link <?= $is('skema_pajak')?'active':'' ?>" href="<?= site_url('skema_pajak') ?>">
        <i class="fa-solid fa-percent"></i><span>Skema Pajak</span>
      </a>
    </li>

    <li class="menu-header">Anggaran</li>
    <li class="menu-item">
      <a class="menu-link <?= $is('anggaran') && $seg2==='dpa' ? 'active':'' ?>" href="<?= site_url('anggaran/dpa') ?>">
        <i class="fa-solid fa-file-invoice-dollar"></i><span>DPA</span>
      </a>
    </li>
    <li class="menu-item">
      <a class="menu-link <?= $is('anggaran') && $seg2==='arus_kas' ? 'active':'' ?>" href="<?= site_url('anggaran/arus_kas') ?>">
        <i class="fa-solid fa-money-bill-trend-up"></i><span>Arus Kas</span>
      </a>
    </li>

    <?php if (in_array($role, array('superadmin','admin_opd'), TRUE)): ?>
    <li class="menu-header">Pengaturan</li>
    <li class="menu-item">
      <a class="menu-link <?= $is('user') ? 'active':'' ?>" href="<?= site_url('user') ?>">
        <i class="fa-solid fa-users-gear"></i><span>Pengguna</span>
      </a>
    </li>
    <?php endif; ?>
  </ul>
</aside>
