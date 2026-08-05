<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Pindah Buku — dicetak PER REKENING (1 halaman/rekening), format menyesuaikan
 * kategori_pajak rekening:
 *   perjalanan_dinas -> lampiran penerimaan uang perjalanan (SPPD/Representasi/Penginapan/Tol)
 *   honorarium       -> pemindahbukuan honor (Rincian + PPN + PPh 21)
 *   lainnya          -> pemindahbukuan barang/jasa (PPN + PPh Ps 23)
 * Var: $row(+details), $info, $penmap, $pejabat, $instansi, $format, $judul
 */
$this->load->view('npd/_print_head', array('judul' => 'Pindah Buku ' . $row->nomor_npd, 'format' => $format));

$ins  = $instansi;
$kota = $ins['kota'] ?: '';
$opd  = $info->nama_opd ?? '-';
$opd_s = trim((string) ($info->singkatan ?? '')) ?: $opd;
$unit = trim((string) ($info->unit_nama ?? ''));
$pptk_jab = 'PPTK ' . ($unit !== '' ? ucwords(strtolower($unit)) . ' ' : '') . $opd_s;
$pptk_nm  = ($pejabat['pptk']->nama_lengkap ?? '') ?: ($info->pptk_nama ?: '');
$pptk_nip = ($pejabat['pptk']->nip          ?? '') ?: ($info->pptk_nip ?: '');

if ( ! function_exists('pinbuk_split_pajak')) {
	function pinbuk_split_pajak($pajak) {
		$ppn = 0; $pph = 0;
		foreach ($pajak['lines'] as $ln) { if ($ln['jenis'] === 'PPN') $ppn += $ln['nilai']; else $pph += $ln['nilai']; }
		return array($ppn, $pph);
	}
}
if ( ! function_exists('pinbuk_komponen_pd')) {
	function pinbuk_komponen_pd($uraian) {
		$u = strtolower((string) $uraian);
		if (strpos($u, 'representasi') !== FALSE) return 'representasi';
		if (strpos($u, 'penginap') !== FALSE || strpos($u, 'hotel') !== FALSE) return 'penginapan';
		if (strpos($u, 'tol') !== FALSE || strpos($u, 'transpor') !== FALSE) return 'tol';
		return 'sppd';
	}
}
$rp = function ($n) { return number_format((float) $n, 0, ',', '.'); };

// blok tanda tangan (PPTK) — dipakai di tiap halaman
$ttd = function () use ($kota, $row, $pptk_jab, $pptk_nm, $pptk_nip) { ?>
	<table style="width:100%; margin-top:16px"><tr>
		<td style="width:52%">&nbsp;</td>
		<td style="width:48%; text-align:center">
			<?= html_escape($kota) ?>, <?= tanggal_id($row->tanggal) ?><br><?= html_escape($pptk_jab) ?>
			<div style="height:52px"></div>
			<b><u><?= html_escape($pptk_nm ?: '....................') ?></u></b><br>
			NIP. <?= html_escape($pptk_nip ?: '....................') ?>
		</td>
	</tr></table>
<?php };

$details = array();
foreach ($row->details as $d) if ( ! empty($penmap[$d->id])) $details[] = $d;
$last = count($details) - 1;

foreach ($details as $di => $d):
	$pens  = $penmap[$d->id];
	$jenis = $d->jenis_belanja ?: jenis_belanja_kategori($d->kategori_pajak);
	$brk   = $di < $last ? ' style="page-break-after:always"' : '';
?>
<div class="page"<?= $brk ?>>

<?php if ($jenis === 'perjalanan'): // ===== FORMAT A: PERJALANAN DINAS =====
	$grp = array();
	foreach ($pens as $p) {
		$key = $p->pegawai_id ? 'p' . $p->pegawai_id : ($p->penerima_id ? 'm' . $p->penerima_id : 'n' . $p->nama_live);
		if ( ! isset($grp[$key])) $grp[$key] = array(
			'nama' => $p->nama_live,
			'nip'  => $p->sumber === 'pegawai' ? $p->peg_nip : '',
			'jab'  => $p->sumber === 'pegawai' ? $p->jabatan_live : '',
			'gol'  => $p->sumber === 'pegawai' ? $p->peg_gol : '',
			'norek' => $p->norek_live,
			'sppd' => 0, 'representasi' => 0, 'penginapan' => 0, 'tol' => 0,
		);
		$komp = $p->komponen_pd ?: pinbuk_komponen_pd($p->uraian);
		$grp[$key][$komp] += (float) $p->jumlah;
	}
	$t = array('sppd' => 0, 'representasi' => 0, 'penginapan' => 0, 'tol' => 0, 'total' => 0);
?>
	<h1 class="doc-title">LAMPIRAN PENERIMAAN UANG PERJALANAN DINAS</h1>
	<div class="doc-sub"><?= html_escape(strtoupper($row->perihal)) ?></div>
	<table class="grid" border="1" cellspacing="0" style="width:100%">
		<thead>
			<tr>
				<th rowspan="2" style="width:4%">NO</th><th rowspan="2" style="width:16%">NAMA</th>
				<th rowspan="2" style="width:14%">NIP</th><th rowspan="2" style="width:7%">JABATAN</th><th rowspan="2" style="width:4%">GOL</th>
				<th colspan="4" style="width:30%">JUMLAH</th>
				<th rowspan="2" style="width:12%">JUMLAH PENERIMAAN</th><th rowspan="2" style="width:13%">NO REKENING</th>
			</tr>
			<tr><th style="width:8%">SPPD</th><th style="width:8%">REPRESENTASI</th><th style="width:8%">PENGINAPAN</th><th style="width:6%">TOL</th></tr>
		</thead>
		<tbody>
			<?php $no = 0; foreach ($grp as $g): $jml = $g['sppd'] + $g['representasi'] + $g['penginapan'] + $g['tol'];
				$t['sppd'] += $g['sppd']; $t['representasi'] += $g['representasi']; $t['penginapan'] += $g['penginapan']; $t['tol'] += $g['tol']; $t['total'] += $jml; ?>
			<tr>
				<td class="c"><?= ++$no ?></td>
				<td><?= html_escape($g['nama']) ?></td>
				<td class="c"><?= html_escape($g['nip'] ?: '-') ?></td>
				<td><?= html_escape($g['jab'] ?: '-') ?></td>
				<td class="c"><?= html_escape($g['gol'] ?: '-') ?></td>
				<td class="r"><?= $g['sppd'] ? $rp($g['sppd']) : '' ?></td>
				<td class="r"><?= $g['representasi'] ? $rp($g['representasi']) : '' ?></td>
				<td class="r"><?= $g['penginapan'] ? $rp($g['penginapan']) : '' ?></td>
				<td class="r"><?= $g['tol'] ? $rp($g['tol']) : '' ?></td>
				<td class="r"><?= $rp($jml) ?></td>
				<td class="c kode"><?= html_escape($g['norek'] ?: '-') ?></td>
			</tr>
			<?php endforeach; ?>
			<tr class="totrow">
				<td colspan="5" class="r">JUMLAH</td>
				<td class="r"><?= $rp($t['sppd']) ?></td><td class="r"><?= $rp($t['representasi']) ?></td>
				<td class="r"><?= $rp($t['penginapan']) ?></td><td class="r"><?= $rp($t['tol']) ?></td>
				<td class="r"><?= $rp($t['total']) ?></td><td></td>
			</tr>
		</tbody>
	</table>

<?php else: // ===== FORMAT B (honor) / C (barang-jasa) =====
	$is_honor = ($jenis === 'honor');
	$pph_label = $is_honor ? 'PPh 21' : 'PPh Ps 23';
	$has_tax = FALSE; foreach ($pens as $p) if ((float) $p->pajak['total_pajak'] > 0) { $has_tax = TRUE; break; }
	$tk = 0; $tppn = 0; $tpph = 0; $tb = 0;
?>
	<h1 class="doc-title">PEMINDAHBUKUAN <?= html_escape(strtoupper($d->uraian)) ?></h1>
	<div class="doc-sub"><?= html_escape(strtoupper($row->perihal)) ?></div>
	<?php if ($has_tax): // --- ada pajak: tampilkan kolom pajak --- ?>
	<table class="grid" border="1" cellspacing="0" style="width:100%">
		<thead>
			<tr>
				<th rowspan="2" style="width:4%">No</th><th rowspan="2" style="width:<?= $is_honor ? 18 : 22 ?>%">Nama Penerima</th>
				<th rowspan="2" style="width:14%">Nomor Rekening Bank</th>
				<?php if ($is_honor): ?><th rowspan="2" style="width:11%">Rincian</th><?php endif; ?>
				<th rowspan="2" style="width:11%">Jumlah Kotor</th>
				<th colspan="3" style="width:25%">Pajak</th>
				<th rowspan="2" style="width:11%">Jumlah Bersih</th><th rowspan="2" style="width:<?= $is_honor ? 11 : 14 ?>%">Keterangan Belanja</th>
			</tr>
			<tr><th style="width:8%">PPN</th><th style="width:8%"><?= $pph_label ?></th><th style="width:9%">Total Pajak</th></tr>
		</thead>
		<tbody>
			<?php $no = 0; foreach ($pens as $p):
				list($ppn, $pph) = pinbuk_split_pajak($p->pajak);
				$tot_pjk = (float) $p->pajak['total_pajak']; $bruto = (float) $p->jumlah; $bersih = (float) $p->pajak['netto'];
				$tk += $bruto; $tppn += $ppn; $tpph += $pph; $tb += $bersih; ?>
			<tr>
				<td class="c"><?= ++$no ?></td>
				<td><?= html_escape($p->nama_live) ?></td>
				<td class="c kode"><?= html_escape($p->norek_live ?: '-') ?></td>
				<?php if ($is_honor): ?><td class="c"><?= $rp($p->volume) ?> X <?= $rp($p->harga_satuan) ?></td><?php endif; ?>
				<td class="r"><?= $rp($bruto) ?></td>
				<td class="r"><?= $ppn ? $rp($ppn) : '' ?></td>
				<td class="r"><?= $pph ? $rp($pph) : '' ?></td>
				<td class="r"><?= $tot_pjk ? $rp($tot_pjk) : '' ?></td>
				<td class="r"><?= $rp($bersih) ?></td>
				<td><?= html_escape($p->uraian ? '- ' . $p->uraian : '') ?></td>
			</tr>
			<?php endforeach; ?>
			<tr class="totrow">
				<td colspan="<?= $is_honor ? 5 : 4 ?>" class="r">Jumlah</td>
				<td class="r"><?= $rp($tk) ?></td>
				<td class="r"><?= $tppn ? $rp($tppn) : '' ?></td>
				<td class="r"><?= $tpph ? $rp($tpph) : '' ?></td>
				<td class="r"><?= ($tppn + $tpph) ? $rp($tppn + $tpph) : '' ?></td>
				<td class="r"><?= $rp($tb) ?></td><td></td>
			</tr>
		</tbody>
	</table>
	<?php else: // --- tanpa pajak: tabel sederhana --- ?>
	<table class="grid" border="1" cellspacing="0" style="width:100%">
		<thead><tr>
			<th style="width:5%">No</th><th style="width:<?= $is_honor ? 30 : 37 ?>%">Nama Penerima</th>
			<th style="width:18%">Nomor Rekening Bank</th>
			<?php if ($is_honor): ?><th style="width:15%">Rincian</th><?php endif; ?>
			<th style="width:16%">Jumlah</th>
			<th style="width:<?= $is_honor ? 16 : 24 ?>%">Keterangan Belanja</th>
		</tr></thead>
		<tbody>
			<?php $no = 0; foreach ($pens as $p): $bruto = (float) $p->jumlah; $tk += $bruto; ?>
			<tr>
				<td class="c"><?= ++$no ?></td>
				<td><?= html_escape($p->nama_live) ?></td>
				<td class="c kode"><?= html_escape($p->norek_live ?: '-') ?></td>
				<?php if ($is_honor): ?><td class="c"><?= $rp($p->volume) ?> X <?= $rp($p->harga_satuan) ?></td><?php endif; ?>
				<td class="r"><?= $rp($bruto) ?></td>
				<td><?= html_escape($p->uraian ? '- ' . $p->uraian : '') ?></td>
			</tr>
			<?php endforeach; ?>
			<tr class="totrow"><td colspan="<?= $is_honor ? 4 : 3 ?>" class="r">Jumlah</td><td class="r"><?= $rp($tk) ?></td><td></td></tr>
		</tbody>
	</table>
	<?php endif; ?>
<?php endif; ?>

	<?php $ttd(); ?>
</div>
<?php endforeach; ?>

<?php if (empty($details)): ?>
<div class="page"><p class="muted" style="text-align:center">Belum ada penerima pada NPD ini.</p></div>
<?php endif; ?>
</body></html>
