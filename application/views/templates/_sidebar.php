<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Sidebar menu — role-aware + role matrix (menu_allowed).
 * Visibilitas tiap menu mengikuti hak akses role (default = perilaku bawaan,
 * dapat diatur di Pengaturan → Hak Akses Menu). superadmin selalu penuh.
 */
$seg1 = $this->uri->segment(1);
$seg2 = $this->uri->segment(2);
$seg3 = $this->uri->segment(3);

$is = function ($c, $entity = NULL) use ($seg1, $seg3) {
	if ($entity === NULL) return ($seg1 === $c);
	return ($seg1 === 'master' && $seg3 === $entity);
};
$nom_keys  = array('master.urusan','master.bidang','master.program','master.kegiatan','master.subkegiatan','master.rekening','master.sumber_dana');
$org_keys  = array('master.opd','master.opd_unit','master.pemetaan','master.unit_pemetaan');
$gaji_keys = array('gaji.simulasi','gaji.rekap','rekap','gaji.ref');
$master_keys = array_merge($nom_keys, $org_keys, array('master.pegawai','master.ref_jabatan','master.penerima','skema_pajak'));

$nom_active  = ($seg1 === 'master' && in_array($seg3, array('urusan','bidang','program','kegiatan','subkegiatan','rekening','sumber_dana'), TRUE));
$org_active  = ($seg1 === 'master' && in_array($seg3, array('opd','opd_unit','pemetaan','unit_pemetaan'), TRUE));
$gaji_active = ($seg1 === 'gaji' || $seg1 === 'rekap' || ($seg1 === 'master' && in_array($seg3, array('ref_gaji_pokok','ref_tunjangan_jabatan','ref_kelas_jabatan','ref_harga_beras','ref_iuran_gaji','ref_tpp','ref_tunjangan_fungsional','ref_tunjangan_khusus','ref_gaji_ke'), TRUE)));
?>
<aside class="layout-sidebar">
  <div class="sidebar-brand">
    <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" style="height:34px;width:auto;">
    <span>Penatausahaan</span>
  </div>

  <ul class="sidebar-menu">
    <?php if (menu_allowed('dashboard')): ?>
    <li class="menu-item">
      <a class="menu-link <?= $is('dashboard') ? 'active' : '' ?>" href="<?= site_url('dashboard') ?>">
        <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (menu_group_visible($master_keys)): ?>
    <li class="menu-header">Master Data</li>

    <?php if (menu_group_visible($nom_keys)): ?>
    <li class="menu-item <?= $nom_active ? 'open' : '' ?>">
      <a class="menu-link menu-toggle" href="javascript:void(0)">
        <i class="fa-solid fa-sitemap"></i><span>Nomenklatur</span>
        <i class="fa-solid fa-chevron-right arrow"></i>
      </a>
      <ul class="menu-sub">
        <?php if (menu_allowed('master.urusan')): ?><li class="menu-item"><a class="menu-link <?= $is('master','urusan')?'active':'' ?>" href="<?= site_url('master/urusan') ?>">Urusan</a></li><?php endif; ?>
        <?php if (menu_allowed('master.bidang')): ?><li class="menu-item"><a class="menu-link <?= $is('master','bidang')?'active':'' ?>" href="<?= site_url('master/bidang') ?>">Bidang Urusan</a></li><?php endif; ?>
        <?php if (menu_allowed('master.program')): ?><li class="menu-item"><a class="menu-link <?= $is('master','program')?'active':'' ?>" href="<?= site_url('master/program') ?>">Program</a></li><?php endif; ?>
        <?php if (menu_allowed('master.kegiatan')): ?><li class="menu-item"><a class="menu-link <?= $is('master','kegiatan')?'active':'' ?>" href="<?= site_url('master/kegiatan') ?>">Kegiatan</a></li><?php endif; ?>
        <?php if (menu_allowed('master.subkegiatan')): ?><li class="menu-item"><a class="menu-link <?= $is('master','subkegiatan')?'active':'' ?>" href="<?= site_url('master/subkegiatan') ?>">Sub Kegiatan</a></li><?php endif; ?>
        <?php if (menu_allowed('master.rekening')): ?><li class="menu-item"><a class="menu-link <?= $is('master','rekening')?'active':'' ?>" href="<?= site_url('master/rekening') ?>">Rekening</a></li><?php endif; ?>
        <?php if (menu_allowed('master.sumber_dana')): ?><li class="menu-item"><a class="menu-link <?= $is('master','sumber_dana')?'active':'' ?>" href="<?= site_url('master/sumber_dana') ?>">Sumber Dana</a></li><?php endif; ?>
      </ul>
    </li>
    <?php endif; ?>

    <?php if (menu_group_visible($org_keys)): ?>
    <li class="menu-item <?= $org_active ? 'open' : '' ?>">
      <a class="menu-link menu-toggle" href="javascript:void(0)">
        <i class="fa-solid fa-building"></i><span>Organisasi</span>
        <i class="fa-solid fa-chevron-right arrow"></i>
      </a>
      <ul class="menu-sub">
        <?php if (menu_allowed('master.opd')): ?><li class="menu-item"><a class="menu-link <?= $is('master','opd')?'active':'' ?>" href="<?= site_url('master/opd') ?>">OPD</a></li><?php endif; ?>
        <?php if (menu_allowed('master.opd_unit')): ?><li class="menu-item"><a class="menu-link <?= $is('master','opd_unit')?'active':'' ?>" href="<?= site_url('master/opd_unit') ?>">Unit OPD</a></li><?php endif; ?>
        <?php if (menu_allowed('master.pemetaan')): ?><li class="menu-item"><a class="menu-link <?= $is('master','pemetaan')?'active':'' ?>" href="<?= site_url('master/pemetaan') ?>">Pemetaan OPD</a></li><?php endif; ?>
        <?php if (menu_allowed('master.unit_pemetaan')): ?><li class="menu-item"><a class="menu-link <?= $is('master','unit_pemetaan')?'active':'' ?>" href="<?= site_url('master/unit_pemetaan') ?>">Pemetaan Unit OPD</a></li><?php endif; ?>
      </ul>
    </li>
    <?php endif; ?>

    <?php if (menu_allowed('master.pegawai')): ?><li class="menu-item"><a class="menu-link <?= $is('master','pegawai')?'active':'' ?>" href="<?= site_url('master/pegawai') ?>"><i class="fa-solid fa-user-tie"></i><span>Pegawai</span></a></li><?php endif; ?>
    <?php if (menu_allowed('master.ref_jabatan')): ?><li class="menu-item"><a class="menu-link <?= $is('master','ref_jabatan')?'active':'' ?>" href="<?= site_url('master/ref_jabatan') ?>"><i class="fa-solid fa-sitemap"></i><span>Master Jabatan</span></a></li><?php endif; ?>
    <?php if (menu_allowed('master.penerima')): ?><li class="menu-item"><a class="menu-link <?= $is('master','penerima')?'active':'' ?>" href="<?= site_url('master/penerima') ?>"><i class="fa-solid fa-hand-holding-dollar"></i><span>Penerima</span></a></li><?php endif; ?>
    <?php if (menu_allowed('skema_pajak')): ?><li class="menu-item"><a class="menu-link <?= $is('skema_pajak')?'active':'' ?>" href="<?= site_url('skema_pajak') ?>"><i class="fa-solid fa-percent"></i><span>Skema Pajak</span></a></li><?php endif; ?>
    <?php endif; /* master group */ ?>

    <?php if (menu_group_visible($gaji_keys)): ?>
    <li class="menu-header">Kepegawaian & Gaji</li>
    <li class="menu-item <?= $gaji_active ? 'open' : '' ?>">
      <a class="menu-link menu-toggle" href="javascript:void(0)">
        <i class="fa-solid fa-money-bill-wave"></i><span>Gaji ASN</span>
        <i class="fa-solid fa-chevron-right arrow"></i>
      </a>
      <ul class="menu-sub">
        <?php if (menu_allowed('gaji.simulasi')): ?><li class="menu-item"><a class="menu-link <?= $seg1==='gaji'&&$seg2==='simulasi'?'active':'' ?>" href="<?= site_url('gaji/simulasi') ?>">Simulasi Slip Gaji</a></li><?php endif; ?>
        <?php if (menu_allowed('gaji.rekap')): ?><li class="menu-item"><a class="menu-link <?= $seg1==='gaji'&&$seg2==='rekap'?'active':'' ?>" href="<?= site_url('gaji/rekap') ?>">Rekap Gaji per OPD</a></li><?php endif; ?>
        <?php if (menu_allowed('rekap')): ?><li class="menu-item"><a class="menu-link <?= $seg1==='rekap'?'active':'' ?>" href="<?= site_url('rekap') ?>">Rekap Tahunan &amp; TPP</a></li><?php endif; ?>
        <?php if (menu_allowed('gaji.ref')): ?>
        <li class="menu-item"><a class="menu-link <?= $is('master','ref_tpp')?'active':'' ?>" href="<?= site_url('master/ref_tpp') ?>">TPP per Jabatan</a></li>
        <li class="menu-item"><a class="menu-link <?= $is('master','ref_gaji_pokok')?'active':'' ?>" href="<?= site_url('master/ref_gaji_pokok') ?>">Tabel Gaji Pokok</a></li>
        <li class="menu-item"><a class="menu-link <?= $is('master','ref_tunjangan_jabatan')?'active':'' ?>" href="<?= site_url('master/ref_tunjangan_jabatan') ?>">Tunjangan Jabatan</a></li>
        <li class="menu-item"><a class="menu-link <?= $is('master','ref_kelas_jabatan')?'active':'' ?>" href="<?= site_url('master/ref_kelas_jabatan') ?>">Kelas Jabatan</a></li>
        <li class="menu-item"><a class="menu-link <?= $is('master','ref_harga_beras')?'active':'' ?>" href="<?= site_url('master/ref_harga_beras') ?>">Tunjangan Pangan</a></li>
        <li class="menu-item"><a class="menu-link <?= $is('master','ref_iuran_gaji')?'active':'' ?>" href="<?= site_url('master/ref_iuran_gaji') ?>">Iuran & Potongan</a></li>
        <li class="menu-item"><a class="menu-link <?= $is('master','ref_tunjangan_fungsional')?'active':'' ?>" href="<?= site_url('master/ref_tunjangan_fungsional') ?>">Tunjangan Fungsional</a></li>
        <li class="menu-item"><a class="menu-link <?= $is('master','ref_tunjangan_khusus')?'active':'' ?>" href="<?= site_url('master/ref_tunjangan_khusus') ?>">Tunjangan Khusus</a></li>
        <li class="menu-item"><a class="menu-link <?= $is('master','ref_gaji_ke')?'active':'' ?>" href="<?= site_url('master/ref_gaji_ke') ?>">Gaji Ke-13/14</a></li>
        <?php endif; ?>
      </ul>
    </li>
    <?php endif; ?>

    <?php if (menu_group_visible(array('anggaran.dpa','anggaran.arus_kas','npd'))): ?>
    <li class="menu-header">Anggaran & Penatausahaan</li>
    <?php if (menu_allowed('anggaran.dpa')): ?><li class="menu-item"><a class="menu-link <?= $is('anggaran') && $seg2==='dpa' ? 'active':'' ?>" href="<?= site_url('anggaran/dpa') ?>"><i class="fa-solid fa-file-invoice-dollar"></i><span>DPA</span></a></li><?php endif; ?>
    <?php if (menu_allowed('anggaran.arus_kas')): ?><li class="menu-item"><a class="menu-link <?= $is('anggaran') && $seg2==='arus_kas' ? 'active':'' ?>" href="<?= site_url('anggaran/arus_kas') ?>"><i class="fa-solid fa-money-bill-trend-up"></i><span>Arus Kas</span></a></li><?php endif; ?>
    <?php if (menu_allowed('npd')): ?><li class="menu-item"><a class="menu-link <?= $is('npd') ? 'active':'' ?>" href="<?= site_url('npd') ?>"><i class="fa-solid fa-file-invoice"></i><span>NPD</span></a></li><?php endif; ?>
    <?php endif; ?>

    <?php if (menu_group_visible(array('user','hak_akses'))): ?>
    <li class="menu-header">Pengaturan</li>
    <?php if (menu_allowed('user')): ?><li class="menu-item"><a class="menu-link <?= $is('user') ? 'active':'' ?>" href="<?= site_url('user') ?>"><i class="fa-solid fa-users-gear"></i><span>Pengguna</span></a></li><?php endif; ?>
    <?php if (menu_allowed('hak_akses')): ?><li class="menu-item"><a class="menu-link <?= $is('akses') ? 'active':'' ?>" href="<?= site_url('akses') ?>"><i class="fa-solid fa-shield-halved"></i><span>Hak Akses Menu</span></a></li><?php endif; ?>
    <?php endif; ?>
  </ul>
</aside>
