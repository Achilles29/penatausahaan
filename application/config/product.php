<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH.'config/runtime.php';
$runtime = penatus_runtime_config();
$runtime_app = isset($runtime['application']) && is_array($runtime['application'])
	? $runtime['application']
	: array();

$control_path = penatus_env(
	'PENATUS_CONTROL_CONFIG',
	'/var/lib/penatausahaan-config/control-center-heartbeat.json'
);
$control_public = array();
if (is_file($control_path) && is_readable($control_path))
{
	$control_decoded = json_decode((string) file_get_contents($control_path), TRUE);
	if (is_array($control_decoded))
	{
		$control_public = array(
			'instance_id' => isset($control_decoded['instance_id']) ? $control_decoded['instance_id'] : NULL,
			'environment' => isset($control_decoded['environment']) ? $control_decoded['environment'] : NULL,
		);
	}
}

$config['product'] = array(
	'code' => 'NAMUA_PENATAUSAHAAN',
	'name' => 'Namua Penatausahaan',
	'version' => '0.3.5-test',
	'schema_version' => '20260906170000',
	'environment' => isset($control_public['environment']) && $control_public['environment']
		? $control_public['environment']
		: (isset($runtime_app['environment']) ? $runtime_app['environment'] : 'STAGING'),
	'instance_id' => isset($control_public['instance_id']) ? $control_public['instance_id'] : NULL,
	'control_config_path' => $control_path,
	'backup_status_path' => penatus_env('PENATUS_BACKUP_STATUS', '/var/lib/penatausahaan-backups/status.json'),
);
