<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH.'config/runtime.php';
$runtime = penatus_runtime_config();
$runtime_db = isset($runtime['database']) && is_array($runtime['database'])
	? $runtime['database']
	: array();

$db_hostname = penatus_env('PENATUS_DB_HOST', isset($runtime_db['hostname']) ? $runtime_db['hostname'] : NULL);
$db_username = penatus_env('PENATUS_DB_USERNAME', isset($runtime_db['username']) ? $runtime_db['username'] : NULL);
$db_password = penatus_env('PENATUS_DB_PASSWORD', isset($runtime_db['password']) ? $runtime_db['password'] : NULL);
$db_database = penatus_env('PENATUS_DB_DATABASE', isset($runtime_db['database']) ? $runtime_db['database'] : NULL);

if ($db_hostname === NULL || $db_username === NULL || $db_password === NULL || $db_database === NULL)
{
	throw new RuntimeException('Konfigurasi database Penatausahaan belum dipasang.');
}

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
	'dsn' => '',
	'hostname' => $db_hostname,
	'username' => $db_username,
	'password' => $db_password,
	'database' => $db_database,
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'production'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8mb4',
	'dbcollat' => 'utf8mb4_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => (ENVIRONMENT !== 'production'),
);
