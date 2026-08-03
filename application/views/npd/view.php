<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Var: $row (obj + details), $info, $penmap (detail_id => [penerima]), $can_edit */
$total = 0; foreach ($row->details as $d) $total += (float) $d->jumlah;
$stmap = array('draft'=>'badge-soft-secondary','final'=>'badge-soft-primary','dibayar'=>'badge-soft-success');
?>
<div class="card mb-3">
  <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
    <span><i class="fa-solid fa-file-lines me-2 text-primary"></i>Detail NPD</span>
    <div class="d-flex gap-2">
      <a href="<?= site_url('npd') ?>" class="btn btn-sm btn-label-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Kembali</a>
      <?php if ($can_edit): ?><a href="<?= site_url('npd/form/'.$row->id) ?>" class="btn btn-sm btn-primary"><i class="fa-solid fa-pen me-1"></i>Edit NPD</a><?php endif; ?>
    </div>
  </div>
  <div class="card-body">
    <div class="row g-3 mb-2">
      <div class="col-md-6">
        <table class="table table-sm table-borderless mb-0">
          <tr><td class="text-muted" style="width:130px">Nomor</td><td class="fw-semibold"><?= html_escape($row->nomor_npd) ?></td></tr>
          <tr><td class="text-muted">Tanggal</td><td><?= tanggal_id($row->tanggal) ?></td></tr>
          <tr><td class="text-muted">Status</td><td><span class="badge <?= $stmap[$row->status] ?? 'badge-soft-secondary' ?> text-capitalize"><?= html_escape($row->status) ?></span></td></tr>
          <tr><td class="text-muted">OPD</td><td><?= html_escape($info->nama_opd ?? '-') ?></td></tr>
        </table>
      </div>
      <div class="col-md-6">
        <table class="table table-sm table-borderless mb-0">
          <tr><td class="text-muted" style="width:130px">Program</td><td><?= html_escape($info->nama_program ?? '-') ?></td></tr>
          <tr><td class="text-muted">Kegiatan</td><td><?= html_escape($info->nama_kegiatan ?? '-') ?></td></tr>
          <tr><td class="text-muted">Sub Kegiatan</td><td><?= html_escape(($info->kode_subkegiatan ?? '').' '.($info->nama_subkegiatan ?? '')) ?></td></tr>
          <tr><td class="text-muted">Sumber Dana</td><td><?= html_escape($info->sumber_dana ?? '-') ?></td></tr>
        </table>
      </div>
      <div class="col-12">
        <div class="border rounded p-2 bg-light">
          <div class="small text-muted">Perihal</div><div class="fw-semibold"><?= html_escape($row->perihal) ?></div>
          <?php if ($row->pekerjaan): ?><div class="small text-muted mt-2">Rincian Pekerjaan</div><div><?= nl2br(html_escape($row->pekerjaan)) ?></div><?php endif; ?>
        </div>
      </div>
    </div>

    <h6 class="mt-3 mb-2"><i class="fa-solid fa-coins text-primary me-1"></i>Rincian Rekening &amp; Penerima</h6>

    <?php foreach ($row->details as $i => $d):
      $pens = isset($penmap[$d->id]) ? $penmap[$d->id] : array();
      $pen_total = 0; foreach ($pens as $p) $pen_total += (float) $p->jumlah;
      $sisa_alok = (float) $d->jumlah - $pen_total;
    ?>
    <div class="border rounded mb-3">
      <div class="d-flex flex-wrap align-items-center gap-2 px-3 py-2 bg-light border-bottom">
        <span class="badge bg-secondary"><?= $i+1 ?></span>
        <span style="font-family:Consolas,monospace;font-size:.85em"><?= html_escape($d->kode_rekening) ?></span>
        <span class="flex-grow-1"><?= html_escape($d->uraian) ?>
          <?= $d->kategori_pajak ? ' <span class="badge bg-label-primary">'.html_escape($d->kategori_pajak).'</span>' : '' ?></span>
        <span class="fw-semibold text-nowrap"><?= rupiah($d->jumlah) ?></span>
        <?php if ($can_edit): ?>
        <button class="btn btn-sm btn-primary btn-add-pen" data-detail="<?= $d->id ?>" data-sisa="<?= $sisa_alok ?>"
                data-rek="<?= html_escape($d->kode_rekening) ?>"><i class="fa-solid fa-user-plus me-1"></i>Penerima</button>
        <?php endif; ?>
      </div>

      <div class="px-3 py-2">
        <?php if (empty($pens)): ?>
          <div class="text-muted small py-1">Belum ada penerima untuk rekening ini.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm mb-1">
              <thead class="text-muted small"><tr>
                <th style="width:34px">#</th><th>Penerima</th><th>Uraian</th>
                <th class="text-end" style="width:130px">Bruto</th>
                <th class="text-end" style="width:150px">Pajak</th>
                <th class="text-end" style="width:130px">Netto</th><?php if ($can_edit): ?><th style="width:80px" class="text-end">Aksi</th><?php endif; ?>
              </tr></thead>
              <tbody>
              <?php foreach ($pens as $j => $p): ?>
                <tr>
                  <td><?= $j+1 ?></td>
                  <td>
                    <?= html_escape($p->nama_live) ?>
                    <?php if ($p->sumber === 'pegawai'): ?>
                      <span class="badge badge-soft-primary">Pegawai</span>
                      <small class="text-muted d-block">NIP <?= html_escape($p->peg_nip) ?> · <?= $p->npwp_live ? 'NPWP '.html_escape($p->npwp_live) : 'non-NPWP' ?></small>
                    <?php elseif ($p->sumber === 'penerima'): ?>
                      <span class="badge badge-soft-secondary">Penerima</span>
                      <?php if ($p->npwp_live): ?><small class="text-muted d-block">NPWP <?= html_escape($p->npwp_live) ?></small><?php endif; ?>
                    <?php endif; ?>
                  </td>
                  <td class="small text-muted"><?= html_escape($p->uraian) ?><br><span class="text-muted-2"><?= rtrim(rtrim(number_format($p->volume,2,',','.'),'0'),',') ?> × <?= rupiah($p->harga_satuan) ?></span></td>
                  <td class="text-end"><?= rupiah($p->jumlah) ?></td>
                  <td class="text-end">
                    <?php if ($p->pajak['total_pajak'] > 0): ?>
                      <span class="text-danger"><?= rupiah($p->pajak['total_pajak']) ?></span>
                      <small class="d-block text-muted"><?php foreach ($p->pajak['lines'] as $ln) echo html_escape(label_jenis_pajak($ln['jenis'])).' '.rtrim(rtrim(number_format($ln['tarif'],2,',','.'),'0'),',').'%&nbsp; '; ?></small>
                    <?php else: ?><span class="text-muted">–</span><?php endif; ?>
                  </td>
                  <td class="text-end fw-semibold"><?= rupiah($p->pajak['netto']) ?></td>
                  <?php if ($can_edit): ?>
                  <td class="text-end">
                    <button class="btn btn-xs btn-outline-primary btn-edit-pen" data-id="<?= $p->id ?>" data-detail="<?= $d->id ?>" data-sisa="<?= $sisa_alok ?>"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn btn-xs btn-outline-danger btn-del-pen" data-id="<?= $p->id ?>"><i class="fa-solid fa-trash"></i></button>
                  </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
        <div class="d-flex justify-content-end gap-3 small">
          <span>Total penerima: <strong><?= rupiah($pen_total) ?></strong></span>
          <span class="<?= $sisa_alok < -0.001 ? 'text-danger' : ($sisa_alok > 0.001 ? 'text-warning' : 'text-success') ?>">
            Sisa alokasi: <strong><?= rupiah($sisa_alok) ?></strong></span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <?php
      $sum_bruto = 0; $sum_pajak = 0; $sum_netto = 0; $pajak_by = array();
      foreach ($penmap as $plist) foreach ($plist as $p) {
        $sum_bruto += (float) $p->jumlah; $sum_pajak += $p->pajak['total_pajak']; $sum_netto += $p->pajak['netto'];
        foreach ($p->pajak['lines'] as $ln) { $k = $ln['jenis']; $pajak_by[$k] = ($pajak_by[$k] ?? 0) + $ln['nilai']; }
      }
    ?>
    <?php if ($sum_bruto > 0): ?>
    <div class="row g-2 mt-2">
      <div class="col-md-3"><div class="border rounded p-2 text-center"><div class="small text-muted">Total Bruto (penerima)</div><div class="fw-bold"><?= rupiah($sum_bruto) ?></div></div></div>
      <div class="col-md-3"><div class="border rounded p-2 text-center"><div class="small text-muted">Total Pajak</div><div class="fw-bold text-danger"><?= rupiah($sum_pajak) ?></div></div></div>
      <div class="col-md-3"><div class="border rounded p-2 text-center"><div class="small text-muted">Total Netto (diterima)</div><div class="fw-bold text-success"><?= rupiah($sum_netto) ?></div></div></div>
      <div class="col-md-3"><div class="border rounded p-2"><div class="small text-muted mb-1">Rincian pajak</div>
        <?php if ($pajak_by): foreach ($pajak_by as $k => $v) echo '<div class="d-flex justify-content-between small"><span>'.html_escape(label_jenis_pajak($k)).'</span><span>'.rupiah($v).'</span></div>'; else: ?><span class="small text-muted">–</span><?php endif; ?>
      </div></div>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-end mt-3 flex-wrap gap-2">
      <div>
        <div class="fw-bold">TOTAL NPD: <?= rupiah($total) ?></div>
        <p class="text-muted small mb-0">Terbilang: <em><?= ucfirst(trim(terbilang_rupiah($total))) ?></em></p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= site_url('npd/cetak/'.$row->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-print me-1"></i>Cetak NPD</a>
        <a href="<?= site_url('npd/pindah_buku/'.$row->id) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-right-left me-1"></i>Pindah Buku</a>
        <a href="<?= site_url('npd/c5/'.$row->id) ?>" target="_blank" class="btn btn-sm btn-outline-info"><i class="fa-solid fa-receipt me-1"></i>Cetak C5</a>
      </div>
    </div>
  </div>
</div>

<?php if ($can_edit): ?>
<!-- Modal Penerima -->
<div class="modal fade" id="penModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form action="<?= site_url('npd/penerima_save') ?>" method="post">
    <div class="modal-header">
      <h5 class="modal-title"><i class="fa-solid fa-user-plus me-2"></i><span id="penTitle">Tambah Penerima</span></h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <input type="hidden" name="id" id="p_id">
      <input type="hidden" name="npd_detail_id" id="p_detail">
      <input type="hidden" name="pegawai_id" id="p_pegawai_id">
      <input type="hidden" name="penerima_id" id="p_penerima_id">
      <div class="alert alert-info py-2 small mb-3" id="p_rekinfo"></div>

      <div class="mb-3 position-relative">
        <label class="form-label"><i class="fa-solid fa-magnifying-glass me-1 text-primary"></i>Cari Pegawai / Penerima <small class="text-muted">(pegawai → data ikut berubah)</small></label>
        <input type="text" class="form-control form-control-sm" id="p_search" placeholder="Ketik nama, NIP, atau NPWP…" autocomplete="off">
        <div id="p_dropdown" class="list-group shadow" style="display:none; position:absolute; z-index:1060; left:0; right:0; max-height:210px; overflow-y:auto; top:100%"></div>
      </div>

      <div class="mb-3">
        <label class="form-label">Nama Penerima <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="nama_penerima" id="p_nama" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Uraian</label>
        <input type="text" class="form-control" name="uraian" id="p_uraian" placeholder="mis. Honor narasumber">
      </div>
      <div class="row g-2">
        <div class="col-3"><label class="form-label">Volume</label><input type="text" inputmode="numeric" class="form-control text-end" name="volume" id="p_vol" value="1"></div>
        <div class="col-5"><label class="form-label">Harga Satuan</label><input type="text" inputmode="numeric" class="form-control text-end" name="harga_satuan" id="p_harga" value=""></div>
        <div class="col-4"><label class="form-label">Jumlah</label><input type="text" class="form-control text-end fw-semibold" id="p_jumlah" value="0" readonly></div>
      </div>
      <div class="mt-3"><label class="form-label">Keterangan</label><input type="text" class="form-control" name="keterangan" id="p_ket"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
  </form>
</div></div></div>

<form id="delPenForm" action="<?= site_url('npd/penerima_delete') ?>" method="post"><input type="hidden" name="id" id="del_pen_id"></form>

<script>
(function(){
  var SEARCH='<?= site_url('npd/penerima_search') ?>', GET='<?= site_url('npd/penerima_get') ?>';
  function digits(s){return String(s).replace(/[^\d]/g,'');}
  function fmt(n){return Number(n||0).toLocaleString('id-ID');}
  function esc(v){return v==null?'':$('<div>').text(String(v)).html();}
  function recompute(){
    var v=parseInt(digits($('#p_vol').val())||'0',10)||0, h=parseInt(digits($('#p_harga').val())||'0',10)||0;
    $('#p_jumlah').val(fmt(v*h));
  }
  $('#p_vol,#p_harga').on('input',function(){ var d=digits(this.value); this.value=d?Number(d).toLocaleString('id-ID'):''; recompute(); });

  function openModal(){ new bootstrap.Modal('#penModal').show(); }
  $('.btn-add-pen').on('click',function(){
    var f=document.querySelector('#penModal form'); f.reset();
    $('#p_id').val(''); $('#p_pegawai_id').val(''); $('#p_penerima_id').val(''); $('#p_detail').val(this.dataset.detail);
    $('#p_vol').val('1'); $('#p_jumlah').val('0'); $('#p_search').val(''); $('#p_dropdown').hide();
    $('#penTitle').text('Tambah Penerima');
    $('#p_rekinfo').html('Rekening <strong>'+esc(this.dataset.rek)+'</strong> · sisa alokasi <strong>Rp '+fmt(this.dataset.sisa)+'</strong>');
    openModal();
  });
  $('.btn-edit-pen').on('click',function(){
    var sisa=this.dataset.sisa;
    $.getJSON(GET+'/'+this.dataset.id,function(r){
      var f=document.querySelector('#penModal form'); f.reset();
      $('#p_id').val(r.id); $('#p_detail').val(r.npd_detail_id);
      $('#p_pegawai_id').val(r.pegawai_id||''); $('#p_penerima_id').val(r.penerima_id||'');
      $('#p_nama').val(r.nama_penerima); $('#p_uraian').val(r.uraian||''); $('#p_ket').val(r.keterangan||'');
      $('#p_vol').val(fmt(r.volume)); $('#p_harga').val(fmt(r.harga_satuan)); recompute();
      $('#penTitle').text('Edit Penerima');
      $('#p_rekinfo').html('Sisa alokasi (belum termasuk baris ini) <strong>Rp '+fmt(sisa)+'</strong>');
      openModal();
    });
  });
  $('.btn-del-pen').on('click',function(){ if(confirm('Hapus penerima ini?')){ $('#del_pen_id').val(this.dataset.id); document.getElementById('delPenForm').submit(); } });

  // autocomplete penerima
  var t=null;
  $('#p_search').on('input',function(){
    clearTimeout(t); var q=this.value.trim();
    if(q.length<2){ $('#p_dropdown').hide(); return; }
    t=setTimeout(function(){
      $.getJSON(SEARCH+'?q='+encodeURIComponent(q),function(rows){
        var h=''; if(!rows.length){ h='<div class="list-group-item small text-muted">Tidak ada. Ketik nama manual di bawah.</div>'; }
        rows.forEach(function(r){
          var badge = r.source==='pegawai'
            ? '<span class="badge badge-soft-primary ms-1">Pegawai</span>'
            : '<span class="badge badge-soft-secondary ms-1">Penerima</span>';
          h+='<button type="button" class="list-group-item list-group-item-action py-1" '
            +'data-source="'+r.source+'" data-id="'+r.id+'" data-nama="'+esc(r.nama)+'">'
            +'<span class="fw-semibold">'+esc(r.nama)+'</span>'+badge
            +(r.sub?'<small class="text-muted d-block">'+esc(r.sub)+'</small>':'')+'</button>';
        });
        $('#p_dropdown').html(h).show();
      });
    },280);
  });
  $('#p_dropdown').on('click','.list-group-item-action',function(){
    if(this.dataset.source==='pegawai'){ $('#p_pegawai_id').val(this.dataset.id); $('#p_penerima_id').val(''); }
    else { $('#p_penerima_id').val(this.dataset.id); $('#p_pegawai_id').val(''); }
    $('#p_nama').val(this.dataset.nama);
    $('#p_dropdown').hide(); $('#p_search').val('');
  });
  $(document).on('mousedown',function(e){ if(!$(e.target).closest('#p_search,#p_dropdown').length) $('#p_dropdown').hide(); });
})();
</script>
<?php endif; ?>
