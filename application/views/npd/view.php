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
                data-jumlah="<?= (float) $d->jumlah ?>" data-uraian="<?= html_escape($d->uraian) ?>"
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
<!-- Modal Tambah Penerima (multi-baris) -->
<div class="modal fade" id="penModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
  <form action="<?= site_url('npd/penerima_batch') ?>" method="post" id="penBatchForm">
    <div class="modal-header">
      <h5 class="modal-title"><i class="fa-solid fa-user-plus me-2"></i>Tambah Penerima</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <input type="hidden" name="npd_detail_id" id="p_detail">
      <div class="alert alert-info py-2 small mb-2" id="p_rekinfo"></div>

      <div class="mb-2 position-relative">
        <label class="form-label mb-1"><i class="fa-solid fa-magnifying-glass me-1 text-primary"></i>Cari Pegawai / Penerima <small class="text-muted">(klik hasil → langsung masuk daftar)</small></label>
        <input type="text" class="form-control form-control-sm" id="p_search" placeholder="Ketik nama, NIP, atau NPWP…" autocomplete="off">
        <div id="p_dropdown" class="list-group shadow" style="display:none; position:absolute; z-index:1060; left:0; right:0; max-height:210px; overflow-y:auto; top:100%"></div>
      </div>
      <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="p_add_manual"><i class="fa-solid fa-plus me-1"></i>Baris manual</button>

      <div class="table-responsive">
        <table class="table table-sm align-middle mb-1">
          <thead class="text-muted small"><tr>
            <th>Nama Penerima</th><th>Uraian</th>
            <th class="text-end" style="width:70px">Vol</th><th class="text-end" style="width:130px">Harga</th>
            <th class="text-end" style="width:130px">Jumlah</th><th style="width:34px"></th>
          </tr></thead>
          <tbody id="p_rows"></tbody>
          <tfoot><tr class="fw-semibold"><td colspan="4" class="text-end">Total</td><td class="text-end" id="p_total">Rp 0</td><td></td></tr></tfoot>
        </table>
      </div>
      <div id="p_empty" class="text-center text-muted small py-2">Cari &amp; pilih pegawai/penerima, atau klik "Baris manual".</div>
      <div class="small mt-1" id="p_remain"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="p_save_btn"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Semua</button></div>
  </form>
</div></div></div>

<!-- Modal Edit Penerima (satu) -->
<div class="modal fade" id="penEditModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form action="<?= site_url('npd/penerima_save') ?>" method="post">
    <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-user-pen me-2"></i>Edit Penerima</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" name="id" id="e_id"><input type="hidden" name="npd_detail_id" id="e_detail">
      <input type="hidden" name="pegawai_id" id="e_peg"><input type="hidden" name="penerima_id" id="e_pen">
      <div class="alert alert-info py-2 small mb-3" id="e_rekinfo"></div>
      <div class="mb-3"><label class="form-label">Nama Penerima <span class="text-danger">*</span></label><input type="text" class="form-control" name="nama_penerima" id="e_nama" required></div>
      <div class="mb-3"><label class="form-label">Uraian</label><input type="text" class="form-control" name="uraian" id="e_ur"></div>
      <div class="row g-2">
        <div class="col-3"><label class="form-label">Volume</label><input type="text" inputmode="numeric" class="form-control text-end" name="volume" id="e_vol" value="1"></div>
        <div class="col-5"><label class="form-label">Harga Satuan</label><input type="text" inputmode="numeric" class="form-control text-end" name="harga_satuan" id="e_hrg"></div>
        <div class="col-4"><label class="form-label">Jumlah</label><input type="text" class="form-control text-end fw-semibold" id="e_jml" value="0" readonly></div>
      </div>
      <div class="mt-3"><label class="form-label">Keterangan</label><input type="text" class="form-control" name="keterangan" id="e_ket"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
  </form>
</div></div></div>

<form id="delPenForm" action="<?= site_url('npd/penerima_delete') ?>" method="post"><input type="hidden" name="id" id="del_pen_id"></form>

<script>
(function(){
  var SEARCH='<?= site_url('npd/penerima_search') ?>', GET='<?= site_url('npd/penerima_get') ?>';
  function digits(s){return String(s).replace(/[^\d]/g,'');}
  function num(s){return parseInt(digits(s)||'0',10)||0;}
  function fmt(n){return Number(n||0).toLocaleString('id-ID');}
  function esc(v){return v==null?'':$('<div>').text(String(v)).html();}

  /* ===== Tambah Penerima (multi-baris) ===== */
  var lineSisa=0, lineUraian='';
  function rowJml(tr){ return num(tr.querySelector('.r-vol').value) * num(tr.querySelector('.r-hrg').value); }
  function remaining(){ var used=0; $('#p_rows tr').each(function(){ used+=rowJml(this); }); return lineSisa-used; }
  function recalcAll(){
    var total=0;
    $('#p_rows tr').each(function(){ var j=rowJml(this); total+=j; this.querySelector('.r-jml').textContent='Rp '+fmt(j); });
    $('#p_total').text('Rp '+fmt(total));
    var rem=lineSisa-total, over=rem<-0.001, n=$('#p_rows tr').length;
    $('#p_remain').html('Sisa alokasi setelah ini: <strong class="'+(over?'text-danger':'text-success')+'">Rp '+fmt(rem)+'</strong>'
      + (over?' <span class="text-danger">— melebihi pagu, kurangi nominal.</span>':''));
    document.getElementById('p_save_btn').disabled = over || n===0;
    $('#p_empty').toggle(n===0);
  }
  function addRow(d){
    d=d||{};
    var defHarga = (d.harga!=null) ? d.harga : Math.max(0, remaining());
    var tr=document.createElement('tr');
    tr.innerHTML =
      '<td><input class="form-control form-control-sm r-nama" name="nama_penerima[]" value="'+esc(d.nama||'')+'" placeholder="Nama penerima" required>'
        +'<input type="hidden" name="pegawai_id[]" class="r-peg" value="'+esc(d.pegawai_id||'')+'">'
        +'<input type="hidden" name="penerima_id[]" class="r-pen" value="'+esc(d.penerima_id||'')+'">'
        +'<input type="hidden" name="keterangan[]" value="">'
        +(d.badge?'<span class="badge badge-soft-'+(d.badge==='pegawai'?'primary':'secondary')+' mt-1">'+(d.badge==='pegawai'?'Pegawai':'Penerima')+'</span>':'')
      +'</td>'
      +'<td><input class="form-control form-control-sm r-ur" name="uraian[]" value="'+esc(d.uraian!=null?d.uraian:lineUraian)+'"></td>'
      +'<td><input class="form-control form-control-sm text-end r-vol" name="volume[]" value="1"></td>'
      +'<td><input class="form-control form-control-sm text-end r-hrg" name="harga_satuan[]" value="'+(defHarga?fmt(defHarga):'')+'"></td>'
      +'<td class="text-end r-jml">Rp 0</td>'
      +'<td><button type="button" class="btn btn-sm btn-outline-danger r-del" title="Hapus baris"><i class="fa-solid fa-xmark"></i></button></td>';
    document.getElementById('p_rows').appendChild(tr);
    recalcAll();
    if(!d.nama) tr.querySelector('.r-nama').focus();
  }
  $('#p_rows').on('input','.r-vol,.r-hrg',function(){ var d=digits(this.value); this.value=d?Number(d).toLocaleString('id-ID'):''; recalcAll(); });
  $('#p_rows').on('click','.r-del',function(){ $(this).closest('tr').remove(); recalcAll(); });
  $('#p_add_manual').on('click',function(){ addRow({}); });

  $('.btn-add-pen').on('click',function(){
    lineSisa=parseFloat(this.dataset.sisa||'0'); lineUraian=this.dataset.uraian||'';
    $('#p_detail').val(this.dataset.detail); $('#p_rows').empty(); $('#p_search').val(''); $('#p_dropdown').hide();
    $('#p_rekinfo').html('Rekening <strong>'+esc(this.dataset.rek)+'</strong> · sisa alokasi <strong>Rp '+fmt(this.dataset.sisa)+'</strong>. Harga satuan default = sisa yang dicairkan, dapat diedit.');
    recalcAll();
    new bootstrap.Modal('#penModal').show();
  });

  // autocomplete -> klik hasil = tambah baris langsung
  var t=null;
  $('#p_search').on('input',function(){
    clearTimeout(t); var q=this.value.trim();
    if(q.length<2){ $('#p_dropdown').hide(); return; }
    t=setTimeout(function(){
      $.getJSON(SEARCH+'?q='+encodeURIComponent(q),function(rows){
        var h=''; if(!rows.length){ h='<div class="list-group-item small text-muted">Tidak ada. Gunakan tombol "Baris manual".</div>'; }
        rows.forEach(function(r){
          var badge = r.source==='pegawai'
            ? '<span class="badge badge-soft-primary ms-1">Pegawai</span>'
            : '<span class="badge badge-soft-secondary ms-1">Penerima</span>';
          h+='<button type="button" class="list-group-item list-group-item-action py-1 pen-pick" '
            +'data-source="'+r.source+'" data-id="'+r.id+'" data-nama="'+esc(r.nama)+'">'
            +'<span class="fw-semibold">'+esc(r.nama)+'</span>'+badge
            +(r.sub?'<small class="text-muted d-block">'+esc(r.sub)+'</small>':'')+'</button>';
        });
        $('#p_dropdown').html(h).show();
      });
    },280);
  });
  $('#p_dropdown').on('click','.pen-pick',function(){
    var d={nama:this.dataset.nama, badge:this.dataset.source};
    if(this.dataset.source==='pegawai') d.pegawai_id=this.dataset.id; else d.penerima_id=this.dataset.id;
    addRow(d);
    $('#p_dropdown').hide(); $('#p_search').val('').focus();
  });
  $(document).on('mousedown',function(e){ if(!$(e.target).closest('#p_search,#p_dropdown').length) $('#p_dropdown').hide(); });

  /* ===== Edit Penerima (satu baris) ===== */
  function eRecompute(){ $('#e_jml').val(fmt(num($('#e_vol').val())*num($('#e_hrg').val()))); }
  $('#e_vol,#e_hrg').on('input',function(){ var d=digits(this.value); this.value=d?Number(d).toLocaleString('id-ID'):''; eRecompute(); });
  $('.btn-edit-pen').on('click',function(){
    var sisa=this.dataset.sisa;
    $.getJSON(GET+'/'+this.dataset.id,function(r){
      document.querySelector('#penEditModal form').reset();
      $('#e_id').val(r.id); $('#e_detail').val(r.npd_detail_id);
      $('#e_peg').val(r.pegawai_id||''); $('#e_pen').val(r.penerima_id||'');
      $('#e_nama').val(r.nama_penerima); $('#e_ur').val(r.uraian||''); $('#e_ket').val(r.keterangan||'');
      $('#e_vol').val(fmt(r.volume)); $('#e_hrg').val(fmt(r.harga_satuan)); eRecompute();
      $('#e_rekinfo').html('Sisa alokasi (belum termasuk baris ini): <strong>Rp '+fmt(sisa)+'</strong>');
      new bootstrap.Modal('#penEditModal').show();
    });
  });
  $('.btn-del-pen').on('click',function(){ if(confirm('Hapus penerima ini?')){ $('#del_pen_id').val(this.dataset.id); document.getElementById('delPenForm').submit(); } });
})();
</script>
<?php endif; ?>
