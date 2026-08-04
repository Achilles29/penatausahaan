<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Hak Akses (Role Matrix) — pola pustaka: izin CRUD (view/create/edit/delete)
 * per halaman/menu untuk tiap role. superadmin selalu penuh.
 * Override disimpan di role_permission; kosong = default katalog.
 */
class Akses extends MY_Controller {

	private $roles = array('admin_opd', 'user_opd');
	private $actions = array('view', 'create', 'edit', 'delete');

	public function __construct()
	{
		parent::__construct();
		$this->require_role('superadmin');
	}

	public function index()
	{
		$grup = array();
		foreach (menu_catalog() as $key => $c)
		{
			$row = array('key' => $key, 'label' => $c[0], 'perm' => array());
			foreach ($this->roles as $r)
				foreach ($this->actions as $a)
					$row['perm'][$r][$a] = can($a, $key, $r);
			$grup[$c[1]][] = $row;
		}
		$this->render('akses/index', array(
			'grup'         => $grup,
			'roles'        => $this->roles,
			'actions'      => $this->actions,
			'has_override' => $this->db->where_in('role', $this->roles)->count_all_results('role_permission') > 0,
		), 'Hak Akses & Menu');
	}

	public function save()
	{
		$posted = $this->input->post('p'); // p[role][key][action] = 1
		if ( ! is_array($posted)) $posted = array();

		$this->db->where_in('role', $this->roles)->delete('role_permission');

		$batch = array();
		foreach ($this->roles as $r)
		{
			foreach (array_keys(menu_catalog()) as $key)
			{
				$view   = ! empty($posted[$r][$key]['view']) ? 1 : 0;
				$create = ! empty($posted[$r][$key]['create']) ? 1 : 0;
				$edit   = ! empty($posted[$r][$key]['edit']) ? 1 : 0;
				$delete = ! empty($posted[$r][$key]['delete']) ? 1 : 0;
				// create/edit/delete implies view
				if ($create || $edit || $delete) $view = 1;
				$batch[] = array('role' => $r, 'page_key' => $key,
					'can_view' => $view, 'can_create' => $create, 'can_edit' => $edit, 'can_delete' => $delete);
			}
		}
		if ($batch) $this->db->insert_batch('role_permission', $batch);

		$this->session->set_flashdata('success', 'Hak akses berhasil disimpan.');
		redirect('akses');
	}

	public function reset()
	{
		$this->db->where_in('role', $this->roles)->delete('role_permission');
		$this->session->set_flashdata('success', 'Hak akses dikembalikan ke default.');
		redirect('akses');
	}
}
