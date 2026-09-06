<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['instansi'] = array(
	'pemda' => 'PEMERINTAH DAERAH',
	'alamat' => '',
	'kontak' => '',
	'website' => '',
	'kota' => '',
	'logo' => '',
	'perihal_npd' => 'Permintaan Pembayaran Kegiatan Non Tunai',
	'ppk_nama' => '',
	'ppk_nip' => '',
	'bendahara_nama' => '',
	'bendahara_nip' => '',
);

$tenant_path = penatus_env('PENATUS_TENANT_CONFIG', '/var/lib/penatausahaan-config/tenant.json');
if (is_file($tenant_path) && is_readable($tenant_path))
{
	$tenant = json_decode((string) file_get_contents($tenant_path), TRUE);
	if (is_array($tenant))
	{
		$sanitized = array();
		foreach (array_keys($config['instansi']) as $key)
		{
			if (isset($tenant[$key]) && is_scalar($tenant[$key]))
			{
				$sanitized[$key] = mb_substr(trim((string) $tenant[$key]), 0, 500);
			}
		}
		if (isset($sanitized['logo']) && $sanitized['logo'] !== ''
			&& (strpos($sanitized['logo'], '..') !== FALSE
				|| preg_match('~^assets/img/[A-Za-z0-9_./-]+\.(?:png|jpe?g|svg)$~D', $sanitized['logo']) !== 1))
		{
			$sanitized['logo'] = '';
		}
		$config['instansi'] = array_merge($config['instansi'], $sanitized);
	}
}
