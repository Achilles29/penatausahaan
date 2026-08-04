<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kop surat & pejabat penatausahaan untuk dokumen cetak (NPD, Pindah Buku, C5).
 * Silakan sesuaikan dengan data daerah. Ditarik oleh Npd::cetak_doc().
 */
$config['instansi'] = array(
	'pemda'   => 'PEMERINTAH KABUPATEN REMBANG',
	'alamat'  => 'Jl. Pierre Tendean No. 2 Rembang',
	'kontak'  => 'Telp./Fax. (0295) 691103',
	'website' => 'Website: http://www.rembangkab.go.id',
	'kota'    => 'Rembang',
	'logo'    => 'assets/img/logo.png', // relatif ke base_url; kosongkan bila tanpa logo

	// Sebutan perihal baku pada surat NPD
	'perihal_npd' => 'Permintaan Pembayaran Kegiatan Non Tunai',

	// Pejabat penanda tangan (fallback bila tidak tersedia dari data OPD/unit).
	// Kosongkan untuk menampilkan garis titik-titik yang diisi manual.
	'ppk_nama'       => '',
	'ppk_nip'        => '',
	'bendahara_nama' => '',
	'bendahara_nip'  => '',
);
