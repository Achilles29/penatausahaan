<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model generik untuk CRUD & DataTables server-side seluruh entitas master.
 * Konfigurasi entitas didefinisikan di controller Master.
 */
class Master_model extends CI_Model {

	/**
	 * Ambil data untuk DataTables server-side.
	 *
	 * @param array $cfg     konfigurasi entitas (from, alias, select, joins, searchable)
	 * @param array $dt      parameter DataTables (start, length, search, order, columns)
	 * @param array $filters filter eksak tambahan: [ "alias.kolom" => nilai ]
	 * @param array $scope   ['column' => 'alias.kolom', 'ids' => array|NULL] batasan scope
	 * @return array [data, recordsTotal, recordsFiltered]
	 */
	public function datatables($cfg, $dt, $filters = array(), $scope = NULL)
	{
		$alias = $cfg['alias'];

		// recordsTotal (dengan scope, tanpa search/filter user)
		$this->_base_query($cfg, $scope);
		$records_total = $this->db->count_all_results();

		// Query utama + filter + search
		$this->_base_query($cfg, $scope);

		foreach ($filters as $col => $val)
		{
			if ($val !== '' && $val !== NULL) $this->db->where($col, $val);
		}

		$search = isset($dt['search']['value']) ? trim($dt['search']['value']) : '';
		if ($search !== '' && ! empty($cfg['searchable']))
		{
			$this->db->group_start();
			foreach ($cfg['searchable'] as $i => $col)
			{
				$i === 0 ? $this->db->like($col, $search) : $this->db->or_like($col, $search);
			}
			$this->db->group_end();
		}

		// recordsFiltered
		$records_filtered = $this->db->count_all_results('', FALSE); // pertahankan query

		// Ordering
		// DataTables column index includes the '#' prefix column (not in $cfg['columns']),
		// so subtract 1 to map to the correct cfg column.
		$order_applied = FALSE;
		if ( ! empty($dt['order']) && isset($cfg['columns']))
		{
			$col_idx = (int) $dt['order'][0]['column'] - 1;
			$dir     = strtolower($dt['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
			$cols    = array_values($cfg['columns']);
			if (isset($cols[$col_idx]) && ! empty($cols[$col_idx]['order']))
			{
				$this->db->order_by($cols[$col_idx]['order'], $dir);
				$order_applied = TRUE;
			}
			elseif (isset($cols[$col_idx]) && ! empty($cols[$col_idx]['field']))
			{
				$this->db->order_by($cols[$col_idx]['field'], $dir);
				$order_applied = TRUE;
			}
		}
		if ( ! $order_applied && ! empty($cfg['order_by']))
		{
			$escape = ! empty($cfg['order_by_raw']) ? FALSE : NULL;
			$this->db->order_by($cfg['order_by'], '', $escape);
		}

		// Paging
		$length = isset($dt['length']) ? (int) $dt['length'] : 25;
		$start  = isset($dt['start']) ? (int) $dt['start'] : 0;
		if ($length > 0) $this->db->limit($length, $start);

		$data = $this->db->get()->result_array();

		return array(
			'data'            => $data,
			'recordsTotal'    => $records_total,
			'recordsFiltered' => $records_filtered,
		);
	}

	private function _base_query($cfg, $scope = NULL)
	{
		$this->db->from($cfg['from']);
		if ( ! empty($cfg['select'])) $this->db->select($cfg['select'], FALSE);
		if ( ! empty($cfg['joins']))
		{
			foreach ($cfg['joins'] as $j)
			{
				$this->db->join($j[0], $j[1], isset($j[2]) ? $j[2] : 'left');
			}
		}
		if ($scope !== NULL && $scope['ids'] !== NULL)
		{
			if (empty($scope['ids'])) $this->db->where('1 = 0', NULL, FALSE); // scope kosong
			else $this->db->where_in($scope['column'], $scope['ids']);
		}
	}

	public function get_row($table, $id, $pk = 'id')
	{
		return $this->db->get_where($table, array($pk => $id))->row_array();
	}

	public function insert($table, $data)
	{
		$this->db->insert($table, $data);
		return $this->db->insert_id();
	}

	public function update($table, $id, $data, $pk = 'id')
	{
		return $this->db->where($pk, $id)->update($table, $data);
	}

	public function delete($table, $id, $pk = 'id')
	{
		return $this->db->where($pk, $id)->delete($table);
	}

	/** Opsi dropdown [id => label]. */
	public function options($table, $key, $label_expr, $where = array(), $order = NULL)
	{
		$this->db->select($key . ' AS k, (' . $label_expr . ') AS v', FALSE)->from($table);
		if ( ! empty($where)) $this->db->where($where);
		$this->db->order_by($order ? $order : $label_expr);
		$rows = $this->db->get()->result();
		$out = array();
		foreach ($rows as $r) $out[$r->k] = $r->v;
		return $out;
	}

	/** Cek keunikan nilai (untuk validasi). */
	public function is_unique_value($table, $column, $value, $exclude_id = NULL, $pk = 'id')
	{
		$this->db->where($column, $value);
		if ($exclude_id !== NULL) $this->db->where($pk . ' !=', $exclude_id);
		return $this->db->count_all_results($table) === 0;
	}
}
