<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper format untuk tampilan Indonesia: rupiah, terbilang, tanggal.
 */

if ( ! function_exists('rupiah'))
{
	/**
	 * Format angka ke Rupiah. rupiah(1500000) => "Rp 1.500.000"
	 * @param float $angka
	 * @param bool  $with_prefix  sertakan "Rp "
	 * @param int   $desimal      jumlah angka desimal
	 */
	function rupiah($angka, $with_prefix = TRUE, $desimal = 0)
	{
		$angka = (float) $angka;
		$hasil = number_format($angka, $desimal, ',', '.');
		return $with_prefix ? 'Rp ' . $hasil : $hasil;
	}
}

if ( ! function_exists('angka'))
{
	/** Format angka biasa dengan pemisah ribuan. */
	function angka($angka, $desimal = 0)
	{
		return number_format((float) $angka, $desimal, ',', '.');
	}
}

if ( ! function_exists('tanggal_id'))
{
	/**
	 * Format tanggal ke Bahasa Indonesia. tanggal_id('2026-07-30') => "30 Juli 2026"
	 * @param string $date  string tanggal yang bisa di-strtotime
	 * @param bool   $with_time  sertakan jam
	 */
	function tanggal_id($date, $with_time = FALSE)
	{
		if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00')
		{
			return '-';
		}
		$bulan = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
			'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
		$ts = strtotime($date);
		if ($ts === FALSE) return $date;
		$hasil = date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
		if ($with_time)
		{
			$hasil .= ' ' . date('H:i', $ts);
		}
		return $hasil;
	}
}

if ( ! function_exists('terbilang'))
{
	/**
	 * Konversi angka ke kata (terbilang) Bahasa Indonesia.
	 * terbilang(1500) => "seribu lima ratus"
	 */
	function terbilang($angka)
	{
		$angka = floor((float) $angka); // terbilang untuk nilai bulat (hindari float % di PHP 8)
		if ($angka < 0)
		{
			return 'minus ' . trim(terbilang(abs($angka)));
		}
		$huruf = array('', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam',
			'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas');

		if ($angka < 12)
		{
			return ' ' . $huruf[(int) $angka];
		}
		elseif ($angka < 20)
		{
			return terbilang($angka - 10) . ' belas';
		}
		elseif ($angka < 100)
		{
			return terbilang((int)($angka / 10)) . ' puluh' . terbilang($angka % 10);
		}
		elseif ($angka < 200)
		{
			return ' seratus' . terbilang($angka - 100);
		}
		elseif ($angka < 1000)
		{
			return terbilang((int)($angka / 100)) . ' ratus' . terbilang($angka % 100);
		}
		elseif ($angka < 2000)
		{
			return ' seribu' . terbilang($angka - 1000);
		}
		elseif ($angka < 1000000)
		{
			return terbilang((int)($angka / 1000)) . ' ribu' . terbilang($angka % 1000);
		}
		elseif ($angka < 1000000000)
		{
			return terbilang((int)($angka / 1000000)) . ' juta' . terbilang($angka % 1000000);
		}
		elseif ($angka < 1000000000000)
		{
			return terbilang((int)($angka / 1000000000)) . ' miliar' . terbilang(fmod($angka, 1000000000));
		}
		return terbilang((int)($angka / 1000000000000)) . ' triliun' . terbilang(fmod($angka, 1000000000000));
	}
}

if ( ! function_exists('terbilang_rupiah'))
{
	/** terbilang_rupiah(1500) => "Seribu lima ratus rupiah" */
	function terbilang_rupiah($angka)
	{
		$kata = trim(preg_replace('/\s+/', ' ', terbilang($angka)));
		return ucfirst($kata) . ' rupiah';
	}
}
