<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Katalog menu + hak akses menu per role (role matrix).
 * Default = perilaku bawaan (agar tidak ada yang berubah tanpa konfigurasi).
 * Override disimpan di tabel role_menu (role, menu_key, allowed).
 * superadmin selalu punya akses penuh.
 */

if ( ! function_exists('menu_catalog'))
{
	/** key => [label, grup, default_roles[]]. */
	function menu_catalog()
	{
		$A  = array('superadmin', 'admin_opd', 'user_opd');
		$SA = array('superadmin', 'admin_opd');
		$S  = array('superadmin');
		return array(
			'dashboard'            => array('Dashboard', 'Umum', $A),

			'master.urusan'        => array('Urusan', 'Master · Nomenklatur', $A),
			'master.bidang'        => array('Bidang Urusan', 'Master · Nomenklatur', $A),
			'master.program'       => array('Program', 'Master · Nomenklatur', $A),
			'master.kegiatan'      => array('Kegiatan', 'Master · Nomenklatur', $A),
			'master.subkegiatan'   => array('Sub Kegiatan', 'Master · Nomenklatur', $A),
			'master.rekening'      => array('Rekening', 'Master · Nomenklatur', $A),
			'master.sumber_dana'   => array('Sumber Dana', 'Master · Nomenklatur', $A),

			'master.opd'           => array('OPD', 'Master · Organisasi', $A),
			'master.opd_unit'      => array('Unit OPD', 'Master · Organisasi', $A),
			'master.pemetaan'      => array('Pemetaan OPD', 'Master · Organisasi', $S),
			'master.unit_pemetaan' => array('Pemetaan Unit OPD', 'Master · Organisasi', $S),

			'master.pegawai'       => array('Pegawai', 'Master · Data', $A),
			'master.ref_jabatan'   => array('Master Jabatan', 'Master · Data', $A),
			'master.penerima'      => array('Penerima', 'Master · Data', $A),
			'skema_pajak'          => array('Skema Pajak', 'Master · Data', $A),

			'gaji.simulasi'        => array('Simulasi Slip Gaji', 'Kepegawaian & Gaji', $A),
			'gaji.rekap'           => array('Rekap Gaji per OPD', 'Kepegawaian & Gaji', $A),
			'rekap'                => array('Rekap Tahunan & TPP', 'Kepegawaian & Gaji', $A),
			'gaji.ref'             => array('Referensi Gaji (TPP, Tabel, dll)', 'Kepegawaian & Gaji', $S),

			'anggaran.dpa'         => array('DPA', 'Anggaran', $A),
			'anggaran.arus_kas'    => array('Arus Kas', 'Anggaran', $A),

			'npd'                  => array('NPD', 'Penatausahaan', $A),

			'user'                 => array('Pengguna', 'Pengaturan', $SA),
			'hak_akses'            => array('Hak Akses Menu', 'Pengaturan', $S),
		);
	}
}

if ( ! function_exists('menu_default_roles'))
{
	function menu_default_roles($key)
	{
		$c = menu_catalog();
		return isset($c[$key]) ? $c[$key][2] : array();
	}
}

if ( ! function_exists('role_menu_overrides'))
{
	/** [menu_key => 0|1] override untuk sebuah role (cache per-request). */
	function role_menu_overrides($role)
	{
		static $cache = array();
		if ( ! isset($cache[$role]))
		{
			$CI =& get_instance();
			$rows = $CI->db->get_where('role_menu', array('role' => $role))->result();
			$m = array();
			foreach ($rows as $r) $m[$r->menu_key] = (int) $r->allowed;
			$cache[$role] = $m;
		}
		return $cache[$role];
	}
}

if ( ! function_exists('menu_allowed'))
{
	/** Apakah role boleh mengakses menu $key. */
	function menu_allowed($key, $role = NULL)
	{
		if ($role === NULL) $role = current_role();
		if ($role === 'superadmin') return TRUE;      // akses penuh
		if ($role === NULL) return FALSE;
		$ov = role_menu_overrides($role);
		if (array_key_exists($key, $ov)) return $ov[$key] === 1;
		return in_array($role, menu_default_roles($key), TRUE);
	}
}

if ( ! function_exists('menu_group_visible'))
{
	/** Apakah minimal satu key dalam grup boleh diakses (utk header grup sidebar). */
	function menu_group_visible($keys)
	{
		foreach ($keys as $k) if (menu_allowed($k)) return TRUE;
		return FALSE;
	}
}

if ( ! function_exists('current_menu_key'))
{
	/**
	 * Petakan URI saat ini ke menu_key untuk enforcement.
	 * NULL = tidak dipetakan (utility/publik) -> selalu diizinkan.
	 */
	function current_menu_key()
	{
		$CI =& get_instance();
		$s1 = $CI->uri->segment(1); $s2 = $CI->uri->segment(2); $s3 = $CI->uri->segment(3);
		if ( ! $s1 || in_array($s1, array('auth','setup'), TRUE)) return NULL;

		if ($s1 === 'master')
		{
			// endpoint utility bersama (cascade dropdown, dsb) -> jangan diblok
			if (in_array($s2, array('options','pegawai_search'), TRUE)) return NULL;
			$methods = array('index','data','get','save','delete');
			$entity  = in_array($s2, $methods, TRUE) ? $s3 : $s2;
			if ( ! $entity) return NULL;
			$gaji_refs = array('ref_tpp','ref_gaji_pokok','ref_tunjangan_jabatan','ref_kelas_jabatan',
				'ref_harga_beras','ref_iuran_gaji','ref_tunjangan_fungsional','ref_tunjangan_khusus','ref_gaji_ke');
			if (in_array($entity, $gaji_refs, TRUE)) return 'gaji.ref';
			return 'master.' . $entity;
		}
		if ($s1 === 'anggaran')  return (strpos((string)$s2, 'arus_kas') === 0 || strpos((string)$s2, 'ak_') === 0) ? 'anggaran.arus_kas' : 'anggaran.dpa';
		if ($s1 === 'gaji')      return ($s2 === 'rekap') ? 'gaji.rekap' : 'gaji.simulasi';
		if ($s1 === 'rekap')     return 'rekap';
		if ($s1 === 'npd')       return 'npd';
		if ($s1 === 'skema_pajak') return 'skema_pajak';
		if ($s1 === 'user')      return 'user';
		if ($s1 === 'akses')     return 'hak_akses';
		if ($s1 === 'dashboard' || $s1 === '') return 'dashboard';
		return NULL;
	}
}
