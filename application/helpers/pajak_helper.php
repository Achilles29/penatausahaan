<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper pajak berbasis rekening.
 *
 * Prinsip: jenis pajak ditentukan oleh KATEGORI rekening (master_rekening.kategori_pajak),
 * yang tertaut ke master_skema_pajak.kategori. Bukan input manual / hardcode.
 * Dipakai sebagai fondasi engine penghitungan pajak (Tahap 3 — pinbuk).
 */

if ( ! function_exists('kategori_pajak_rekening'))
{
	/** Kategori pajak dari sebuah rekening (mis. 'honorarium','barang','jasa'). */
	function kategori_pajak_rekening($rekening_id)
	{
		$CI =& get_instance();
		$row = $CI->db->select('kategori_pajak')->get_where('master_rekening', array('id' => (int) $rekening_id))->row();
		return $row ? $row->kategori_pajak : NULL;
	}
}

if ( ! function_exists('skema_pajak_by_kategori'))
{
	/**
	 * Skema pajak + detail aturan untuk sebuah kategori.
	 * @return array|null ['skema'=>row, 'detail'=>[rows]] atau NULL bila tak ada.
	 */
	function skema_pajak_by_kategori($kategori)
	{
		if ( ! $kategori) return NULL;
		$CI =& get_instance();
		$skema = $CI->db->get_where('master_skema_pajak', array('kategori' => $kategori, 'is_active' => 1))->row();
		if ( ! $skema) return NULL;
		$detail = $CI->db->order_by('id')->get_where('master_skema_pajak_detail', array('skema_id' => $skema->id))->result();
		return array('skema' => $skema, 'detail' => $detail);
	}
}

if ( ! function_exists('pajak_untuk_rekening'))
{
	/**
	 * Aturan pajak yang berlaku untuk sebuah rekening.
	 * @return array ['kategori'=>string|null, 'skema'=>row|null, 'detail'=>array]
	 */
	function pajak_untuk_rekening($rekening_id)
	{
		$kategori = kategori_pajak_rekening($rekening_id);
		$sk = skema_pajak_by_kategori($kategori);
		return array(
			'kategori' => $kategori,
			'skema'    => $sk ? $sk['skema'] : NULL,
			'detail'   => $sk ? $sk['detail'] : array(),
		);
	}
}

if ( ! function_exists('label_kategori_pajak'))
{
	/** Label tampilan untuk slug kategori pajak. */
	function label_kategori_pajak($slug)
	{
		$map = array(
			'honorarium' => 'Honorarium', 'barang' => 'Barang', 'jasa' => 'Jasa',
			'jasa_boga' => 'Jasa Boga/Katering', 'makan_minum' => 'Makan & Minum',
			'sewa' => 'Sewa', 'konstruksi' => 'Konstruksi', 'modal' => 'Modal',
			'perjalanan_dinas' => 'Perjalanan Dinas', 'pegawai' => 'Pegawai/Gaji',
			'non_pajak' => 'Non Pajak', 'lainnya' => 'Lainnya',
		);
		return isset($map[$slug]) ? $map[$slug] : $slug;
	}
}
