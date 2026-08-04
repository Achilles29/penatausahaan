<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Katalog halaman/menu + hak akses per role dengan izin CRUD
 * (view/create/edit/delete) — pola seperti pustaka (auth_role_permission).
 * Default = perilaku bawaan; override disimpan di role_permission.
 * superadmin selalu akses penuh.
 */

if ( ! function_exists('menu_catalog'))
{
	/** key => [label, grup, view_roles[], crud_roles[]] (selain superadmin). */
	function menu_catalog()
	{
		$A = array('superadmin', 'admin_opd', 'user_opd');
		$SA = array('superadmin', 'admin_opd');
		$S  = array('superadmin');
		$N  = array();
		return array(
			'dashboard'            => array('Dashboard', 'Umum', $A, $N),

			'master.urusan'        => array('Urusan', 'Master · Nomenklatur', $A, $N),
			'master.bidang'        => array('Bidang Urusan', 'Master · Nomenklatur', $A, $N),
			'master.program'       => array('Program', 'Master · Nomenklatur', $A, $N),
			'master.kegiatan'      => array('Kegiatan', 'Master · Nomenklatur', $A, $N),
			'master.subkegiatan'   => array('Sub Kegiatan', 'Master · Nomenklatur', $A, $N),
			'master.rekening'      => array('Rekening', 'Master · Nomenklatur', $A, $N),
			'master.sumber_dana'   => array('Sumber Dana', 'Master · Nomenklatur', $A, $N),

			'master.opd'           => array('OPD', 'Master · Organisasi', $A, $N),
			'master.opd_unit'      => array('Unit OPD', 'Master · Organisasi', $A, array('admin_opd')),
			'master.pemetaan'      => array('Pemetaan OPD', 'Master · Organisasi', $S, $N),
			'master.unit_pemetaan' => array('Pemetaan Unit OPD', 'Master · Organisasi', $S, $N),

			'master.pegawai'       => array('Pegawai', 'Master · Data', $A, array('admin_opd')),
			'master.ref_jabatan'   => array('Master Jabatan', 'Master · Data', $A, $N),
			'master.penerima'      => array('Penerima', 'Master · Data', $A, array('admin_opd', 'user_opd')),
			'skema_pajak'          => array('Skema Pajak', 'Master · Data', $A, $N),

			'gaji.simulasi'        => array('Simulasi Slip Gaji', 'Kepegawaian & Gaji', $A, $N),
			'gaji.rekap'           => array('Rekap Gaji per OPD', 'Kepegawaian & Gaji', $A, $N),
			'rekap'                => array('Rekap Tahunan & TPP', 'Kepegawaian & Gaji', $A, $N),
			'gaji.ref'             => array('Referensi Gaji', 'Kepegawaian & Gaji', $S, $N),

			'anggaran.dpa'         => array('DPA', 'Anggaran', $A, $N),
			'anggaran.arus_kas'    => array('Arus Kas', 'Anggaran', $A, $N),

			'npd'                  => array('NPD', 'Penatausahaan', $A, array('admin_opd', 'user_opd')),

			'user'                 => array('Pengguna', 'Pengaturan', $SA, array('admin_opd')),
			'hak_akses'            => array('Hak Akses & Menu', 'Pengaturan', $S, $N),
		);
	}
}

if ( ! function_exists('role_perms'))
{
	/** [page_key => ['can_view'=>,'can_create'=>,'can_edit'=>,'can_delete'=>]] override untuk role. */
	function role_perms($role)
	{
		static $cache = array();
		if ( ! isset($cache[$role]))
		{
			$CI =& get_instance();
			$rows = $CI->db->get_where('role_permission', array('role' => $role))->result();
			$m = array();
			foreach ($rows as $r)
			{
				$m[$r->page_key] = array(
					'can_view'   => (int) $r->can_view,
					'can_create' => (int) $r->can_create,
					'can_edit'   => (int) $r->can_edit,
					'can_delete' => (int) $r->can_delete,
				);
			}
			$cache[$role] = $m;
		}
		return $cache[$role];
	}
}

if ( ! function_exists('can'))
{
	/**
	 * Izin akses. $action = view|create|edit|delete.
	 * superadmin selalu TRUE. Cek override role_permission; jika tak ada -> default katalog.
	 */
	function can($action, $key, $role = NULL)
	{
		if ($role === NULL) $role = current_role();
		if ($role === 'superadmin') return TRUE;
		if ($role === NULL) return FALSE;

		$ov = role_perms($role);
		if (isset($ov[$key])) return ! empty($ov[$key]['can_' . $action]);

		$c = menu_catalog();
		if ( ! isset($c[$key])) return FALSE;
		return ($action === 'view')
			? in_array($role, $c[$key][2], TRUE)   // view_roles
			: in_array($role, $c[$key][3], TRUE);  // crud_roles
	}
}

if ( ! function_exists('can_view'))   { function can_view($k, $r = NULL)   { return can('view', $k, $r); } }
if ( ! function_exists('can_create')) { function can_create($k, $r = NULL) { return can('create', $k, $r); } }
if ( ! function_exists('can_edit'))   { function can_edit($k, $r = NULL)   { return can('edit', $k, $r); } }
if ( ! function_exists('can_delete')) { function can_delete($k, $r = NULL) { return can('delete', $k, $r); } }

if ( ! function_exists('menu_allowed'))
{
	/** Visibilitas menu = izin view. (kompatibilitas nama lama) */
	function menu_allowed($key, $role = NULL) { return can('view', $key, $role); }
}

if ( ! function_exists('menu_group_visible'))
{
	function menu_group_visible($keys)
	{
		foreach ($keys as $k) if (can('view', $k)) return TRUE;
		return FALSE;
	}
}

if ( ! function_exists('current_menu_key'))
{
	/** Petakan URI saat ini ke page key untuk enforcement. NULL = utility -> diizinkan. */
	function current_menu_key()
	{
		$CI =& get_instance();
		$s1 = $CI->uri->segment(1); $s2 = $CI->uri->segment(2); $s3 = $CI->uri->segment(3);
		if ( ! $s1 || in_array($s1, array('auth','setup'), TRUE)) return NULL;

		if ($s1 === 'master')
		{
			if (in_array($s2, array('options','pegawai_search'), TRUE)) return NULL;
			$methods = array('index','data','get','save','delete');
			$entity  = in_array($s2, $methods, TRUE) ? $s3 : $s2;
			if ( ! $entity) return NULL;
			$gaji_refs = array('ref_tpp','ref_gaji_pokok','ref_tunjangan_jabatan','ref_kelas_jabatan',
				'ref_harga_beras','ref_iuran_gaji','ref_tunjangan_fungsional','ref_tunjangan_khusus','ref_gaji_ke');
			if (in_array($entity, $gaji_refs, TRUE)) return 'gaji.ref';
			return 'master.' . $entity;
		}
		if ($s1 === 'anggaran')    return (strpos((string)$s2, 'arus_kas') === 0 || strpos((string)$s2, 'ak_') === 0) ? 'anggaran.arus_kas' : 'anggaran.dpa';
		if ($s1 === 'gaji')        return ($s2 === 'rekap') ? 'gaji.rekap' : 'gaji.simulasi';
		if ($s1 === 'rekap')       return 'rekap';
		if ($s1 === 'npd')         return 'npd';
		if ($s1 === 'skema_pajak') return 'skema_pajak';
		if ($s1 === 'user')        return 'user';
		if ($s1 === 'akses')       return 'hak_akses';
		if ($s1 === 'sidebar')     return 'hak_akses';
		if ($s1 === 'dashboard' || $s1 === '') return 'dashboard';
		return NULL;
	}
}

if ( ! function_exists('current_menu_action'))
{
	/** Aksi CRUD dari method URI (untuk enforcement granular). */
	function current_menu_action()
	{
		$CI =& get_instance();
		$s1 = $CI->uri->segment(1); $s2 = (string) $CI->uri->segment(2);
		// master: master/save|delete/<entity>
		if ($s1 === 'master')
		{
			if ($s2 === 'save') return 'create'; // create/edit dibedakan via id di controller
			if ($s2 === 'delete') return 'delete';
			return 'view';
		}
		if (in_array($s2, array('save','store','create'), TRUE)) return 'create';
		if (in_array($s2, array('delete','destroy'), TRUE)) return 'delete';
		if (in_array($s2, array('update','edit'), TRUE)) return 'edit';
		return 'view';
	}
}
