<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Schema_state
{
	const BASELINE_SCHEMA = 'legacy-41-20260906';
	const HISTORY_TABLE = 'namua_schema_migrations';

	private $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	public function current()
	{
		if ( ! $this->CI->db->table_exists(self::HISTORY_TABLE)) return self::BASELINE_SCHEMA;
		$row = $this->CI->db
			->select('to_schema')
			->order_by('version', 'DESC')
			->limit(1)
			->get(self::HISTORY_TABLE)
			->row_array();
		if ( ! $row) return self::BASELINE_SCHEMA;
		$version = isset($row['to_schema']) ? (string) $row['to_schema'] : '';
		if (preg_match('/^[A-Za-z0-9._-]{1,80}$/D', $version) !== 1)
		{
			throw new RuntimeException('Stored schema version is invalid.');
		}
		return $version;
	}
}
