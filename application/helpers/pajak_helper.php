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

if ( ! function_exists('label_jenis_pajak'))
{
	/** Label ringkas jenis pajak. */
	function label_jenis_pajak($j)
	{
		$m = array('PPH21'=>'PPh 21','PPH22'=>'PPh 22','PPH23'=>'PPh 23','PPH4_2'=>'PPh 4(2)','PPN'=>'PPN','PDRD'=>'PDRD');
		return isset($m[$j]) ? $m[$j] : $j;
	}
}

if ( ! function_exists('golongan_roman'))
{
	/** Ambil prefix romawi golongan ('III/d' => 'III'). Kosong bila non-PNS. */
	function golongan_roman($gol)
	{
		$gol = strtoupper(trim((string) $gol));
		if ($gol === '' || $gol === 'NON_PNS') return '';
		if (preg_match('/^(IV|III|II|I)/', $gol, $m)) return $m[1];
		return '';
	}
}

if ( ! function_exists('hitung_pajak_rekening'))
{
	/**
	 * Hitung pajak untuk satu pembayaran ke penerima, berdasarkan kategori rekening.
	 *
	 * @param int   $rekening_id
	 * @param float $bruto
	 * @param array $ctx  ['punya_npwp'=>0|1, 'golongan'=>'III/d'|null, 'is_pns'=>bool]
	 * @return array ['kategori','bruto','lines'=>[['jenis','tarif','dpp','nilai','ket']],'total_pajak','netto']
	 */
	function hitung_pajak_rekening($rekening_id, $bruto, $ctx = array())
	{
		$bruto      = round((float) $bruto, 2);
		$info       = pajak_untuk_rekening($rekening_id);
		$details    = $info['detail'];
		$punya_npwp = isset($ctx['punya_npwp']) ? (int) $ctx['punya_npwp'] : 0;
		$gol_roman  = golongan_roman(isset($ctx['golongan']) ? $ctx['golongan'] : '');
		$is_pns     = ! empty($ctx['is_pns']);

		// 1) Saring aturan yang berlaku
		$appl = array();
		foreach ($details as $d)
		{
			if ($bruto + 0.001 < (float) $d->batas_min) continue;
			if ($d->batas_max !== NULL && $d->batas_max !== '' && $bruto - 0.001 > (float) $d->batas_max) continue;
			if ($d->punya_npwp !== NULL && $d->punya_npwp !== '' && (int) $d->punya_npwp !== $punya_npwp) continue;
			if ($d->golongan_honor !== NULL && $d->golongan_honor !== '')
			{
				$g = strtoupper($d->golongan_honor);
				if ($g === 'NON_PNS') { if ($is_pns) continue; }
				else { if ( ! $is_pns || $g !== $gol_roman) continue; }
			}
			$appl[] = $d;
		}

		// 2) Satu aturan per jenis pajak (utamakan yang spesifik NPWP, lalu tarif tertinggi)
		$byJenis = array();
		foreach ($appl as $d)
		{
			$j = $d->jenis_pajak;
			if ( ! isset($byJenis[$j])) { $byJenis[$j] = $d; continue; }
			$cur   = $byJenis[$j];
			$dSpec = ($d->punya_npwp !== NULL && $d->punya_npwp !== '');
			$cSpec = ($cur->punya_npwp !== NULL && $cur->punya_npwp !== '');
			if ($dSpec && ! $cSpec) $byJenis[$j] = $d;
			elseif ($dSpec === $cSpec && (float) $d->tarif > (float) $cur->tarif) $byJenis[$j] = $d;
		}

		// 3) Hitung — PPN (termasuk harga) lebih dulu agar DPP PPh benar
		$chosen = array_values($byJenis);
		usort($chosen, function ($a, $b) {
			return ($a->basis_penghitungan === 'ppn_included' ? 0 : 1) - ($b->basis_penghitungan === 'ppn_included' ? 0 : 1);
		});

		$ppn = 0; $lines = array(); $total = 0;
		foreach ($chosen as $d)
		{
			$tarif = (float) $d->tarif;
			if ($d->basis_penghitungan === 'ppn_included')
			{
				$dpp   = round($bruto * 100 / (100 + $tarif), 2);
				$nilai = round($bruto - $dpp, 2);
				$ppn  += $nilai;
			}
			elseif ($d->basis_penghitungan === 'setelah_ppn')
			{
				$dpp   = round($bruto - $ppn, 2);
				$nilai = round($dpp * $tarif / 100, 2);
			}
			else
			{
				$dpp   = $bruto;
				$nilai = round($bruto * $tarif / 100, 2);
			}
			if ($nilai <= 0) continue;
			$lines[] = array('jenis' => $d->jenis_pajak, 'tarif' => $tarif, 'dpp' => $dpp, 'nilai' => $nilai, 'ket' => $d->keterangan);
			$total  += $nilai;
		}

		return array(
			'kategori'    => $info['kategori'],
			'bruto'       => $bruto,
			'lines'       => $lines,
			'total_pajak' => round($total, 2),
			'netto'       => round($bruto - $total, 2),
		);
	}
}
