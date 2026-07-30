<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper hak akses (scope) berbasis role + OPD/unit/bidang-urusan.
 *
 * Model role:
 *  - superadmin : akses penuh (semua OPD, semua bidang-urusan, semua subkegiatan).
 *  - admin_opd  : terbatas pada OPD-nya (semua unit & bidang-urusan yang diampu OPD).
 *  - user_opd   : terbatas pada OPD + unit-nya (bidang-urusan yang diampu unit)
 *                 ditambah tag tambahan di user_akses.
 *
 * Hasil kueri di-cache per-request (static) agar tidak berulang.
 */

if ( ! function_exists('current_user'))
{
	/** Ambil data user login dari session (array) atau NULL. */
	function current_user()
	{
		$CI =& get_instance();
		$u = $CI->session->userdata('user');
		return is_array($u) ? $u : NULL;
	}
}

if ( ! function_exists('is_login'))
{
	function is_login()
	{
		return current_user() !== NULL;
	}
}

if ( ! function_exists('current_role'))
{
	function current_role()
	{
		$u = current_user();
		return $u ? $u['role'] : NULL;
	}
}

if ( ! function_exists('has_role'))
{
	/** Cek role user termasuk salah satu dari $roles (string|array). */
	function has_role($roles)
	{
		$r = current_role();
		if ($r === NULL) return FALSE;
		if (is_string($roles)) $roles = array($roles);
		return in_array($r, $roles, TRUE);
	}
}

if ( ! function_exists('is_super'))
{
	function is_super()
	{
		return current_role() === 'superadmin';
	}
}

if ( ! function_exists('scope_opd_id'))
{
	/** OPD user (int) atau NULL bila superadmin (berarti semua OPD). */
	function scope_opd_id()
	{
		if (is_super()) return NULL;
		$u = current_user();
		return $u ? (int) $u['opd_id'] : NULL;
	}
}

if ( ! function_exists('scope_bidang_urusan_ids'))
{
	/**
	 * Daftar bidang_urusan_id yang boleh diakses user.
	 * Mengembalikan NULL bila superadmin (artinya tak dibatasi / semua).
	 * Array kosong berarti user tak punya scope apa pun.
	 */
	function scope_bidang_urusan_ids()
	{
		static $cache = FALSE;
		if ($cache !== FALSE) return $cache;

		if (is_super()) { $cache = NULL; return NULL; }

		$CI =& get_instance();
		$u = current_user();
		$ids = array();

		if ( ! $u) { $cache = array(); return $cache; }

		if ($u['role'] === 'admin_opd' && ! empty($u['opd_id']))
		{
			$rows = $CI->db->select('bidang_urusan_id')
				->get_where('opd_bidang_urusan', array('opd_id' => (int) $u['opd_id']))->result();
			foreach ($rows as $r) $ids[] = (int) $r->bidang_urusan_id;
		}
		elseif ($u['role'] === 'user_opd')
		{
			if ( ! empty($u['opd_unit_id']))
			{
				$rows = $CI->db->select('bidang_urusan_id')
					->get_where('opd_unit_bidang_urusan', array('opd_unit_id' => (int) $u['opd_unit_id']))->result();
				foreach ($rows as $r) $ids[] = (int) $r->bidang_urusan_id;
			}
			// Tag tambahan di user_akses
			$rows = $CI->db->select('bidang_urusan_id')
				->where('user_id', (int) $u['id'])
				->where('bidang_urusan_id IS NOT NULL', NULL, FALSE)
				->get('user_akses')->result();
			foreach ($rows as $r) $ids[] = (int) $r->bidang_urusan_id;
		}

		$cache = array_values(array_unique($ids));
		return $cache;
	}
}

if ( ! function_exists('scope_subkegiatan_ids'))
{
	/**
	 * Daftar subkegiatan_id dalam kewenangan user (turunan bidang-urusan).
	 * NULL bila superadmin (tak dibatasi).
	 */
	function scope_subkegiatan_ids()
	{
		static $cache = FALSE;
		if ($cache !== FALSE) return $cache;

		$bu = scope_bidang_urusan_ids();
		if ($bu === NULL) { $cache = NULL; return NULL; }
		if (empty($bu)) { $cache = array(); return $cache; }

		$CI =& get_instance();
		$rows = $CI->db->select('sk.id AS id', FALSE)
			->from('master_subkegiatan sk')
			->join('master_kegiatan k', 'k.id = sk.kegiatan_id')
			->join('master_program p', 'p.id = k.program_id')
			->where_in('p.bidang_id', $bu)
			->get()->result();

		$ids = array();
		foreach ($rows as $r) $ids[] = (int) $r->id;
		$cache = $ids;
		return $cache;
	}
}

if ( ! function_exists('can_access_opd'))
{
	/** Apakah user boleh mengakses data OPD tertentu. */
	function can_access_opd($opd_id)
	{
		if (is_super()) return TRUE;
		return (int) $opd_id === (int) scope_opd_id();
	}
}

if ( ! function_exists('can_manage_master'))
{
	/** Hanya superadmin yang boleh mengelola (CRUD) nomenklatur master global. */
	function can_manage_master()
	{
		return is_super();
	}
}

if ( ! function_exists('can_manage_user'))
{
	/** Superadmin kelola semua user; admin_opd kelola user di OPD-nya. */
	function can_manage_user()
	{
		return has_role(array('superadmin', 'admin_opd'));
	}
}
