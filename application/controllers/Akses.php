<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Hak Akses Menu (Role Matrix). superadmin mengatur menu mana yang boleh
 * diakses oleh admin_opd & user_opd. superadmin selalu penuh.
 * Override disimpan di role_menu; kosong = pakai default katalog.
 */
class Akses extends MY_Controller {

	private $roles = array('admin_opd', 'user_opd');

	public function __construct()
	{
		parent::__construct();
		$this->require_role('superadmin');
	}

	public function index()
	{
		// Susun katalog per grup + status saat ini per role
		$grup = array();
		foreach (menu_catalog() as $key => $c)
		{
			$grup[$c[1]][] = array(
				'key'   => $key,
				'label' => $c[0],
				'state' => array(
					'admin_opd' => menu_allowed($key, 'admin_opd'),
					'user_opd'  => menu_allowed($key, 'user_opd'),
				),
			);
		}
		$this->render('akses/index', array(
			'grup'         => $grup,
			'has_override' => $this->db->where_in('role', $this->roles)->count_all_results('role_menu') > 0,
		), 'Hak Akses Menu');
	}

	public function save()
	{
		$posted = $this->input->post('m');
		if ( ! is_array($posted)) $posted = array();

		$this->db->where_in('role', $this->roles)->delete('role_menu');

		$batch = array();
		foreach ($this->roles as $r)
		{
			foreach (array_keys(menu_catalog()) as $key)
			{
				$batch[] = array(
					'role'     => $r,
					'menu_key' => $key,
					'allowed'  => (isset($posted[$r][$key]) && $posted[$r][$key]) ? 1 : 0,
				);
			}
		}
		if ($batch) $this->db->insert_batch('role_menu', $batch);

		$this->session->set_flashdata('success', 'Hak akses menu berhasil disimpan.');
		redirect('akses');
	}

	/** Kembalikan ke default katalog (hapus semua override). */
	public function reset()
	{
		$this->db->where_in('role', $this->roles)->delete('role_menu');
		$this->session->set_flashdata('success', 'Hak akses dikembalikan ke default.');
		redirect('akses');
	}
}
