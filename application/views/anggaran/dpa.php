<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Var: $opd_opts, $is_super, $my_opd_id, $tree_url */
?>
<style>
/* ===== DPA Tree ===== */
.dpa-tree { table-layout:fixed; border-collapse:collapse; width:100%; }
.dpa-tree col.c-uraian  { /* flex */ }
.dpa-tree col.c-koef    { width:155px; }
.dpa-tree col.c-harga   { width:148px; }
.dpa-tree col.c-total   { width:162px; }

.dpa-tree td { padding:.38rem .75rem; border-bottom:1px solid rgba(0,0,0,.045); vertical-align:middle; line-height:1.45; }

/* levels */
.dr0>td { background:linear-gradient(90deg,#1a3a6c,#1d4ed8); color:#fff; font-weight:700; font-size:.93rem; border-bottom:2px solid #1e3a8a; }
.dr0:hover>td { background:linear-gradient(90deg,#1e3a8a,#2563eb); }
.dr1>td { background:#dbeafe; border-left:4px solid #3b82f6; }
.dr1:hover>td { background:#bfdbfe; }
.dr2>td { background:#eff6ff; border-left:4px solid #93c5fd; }
.dr2:hover>td { background:#dbeafe; }
.dr3>td { background:#f0fdf4; border-left:4px solid #4ade80; }
.dr3:hover>td { background:#dcfce7; }
.dr4>td { background:#fffbeb; border-left:4px solid #f59e0b; }
.dr4:hover>td { background:#fef3c7; }
.dr5>td { background:#fdf4ff; border-left:4px solid #a855f7; }/* Tab2 dana level */
.dr5:hover>td { background:#fae8ff; }
.dr6>td { background:#fff; border-left:4px solid #e2e8f0; font-size:.8rem; color:#555; }
.dr6:hover>td { background:#f8fafc; }

/* toggle chevron */
.tog { display:inline-block; width:14px; font-size:.7rem; transition:transform .17s ease; opacity:.65; flex-shrink:0; }
.dpa-r.open>.uraian-cell>.tog { transform:rotate(90deg); opacity:1; }
.dr0.open>.uraian-cell>.tog { opacity:1; }

/* kode badge */
.dpa-kode { font-family:'Cascadia Code',Consolas,monospace; font-size:.72em;
  background:rgba(0,0,0,.08); padding:0 5px; border-radius:3px; margin-right:.35rem; white-space:nowrap; }
.dr0 .dpa-kode { background:rgba(255,255,255,.2); color:#fff; }

/* level label chip */
.lv-chip { font-size:.62rem; font-weight:700; letter-spacing:.5px; padding:1px 5px;
  border-radius:3px; margin-right:.4rem; opacity:.75; vertical-align:middle; }
.lv-prog { background:#bfdbfe; color:#1e40af; }
.lv-keg  { background:#bae6fd; color:#075985; }
.lv-sk   { background:#bbf7d0; color:#166534; }
.lv-rek  { background:#fde68a; color:#92400e; }
.lv-sd   { background:#e9d5ff; color:#6b21a8; }

/* sumber dana badge on items */
.sd-badge { font-size:.63rem; padding:1px 6px; border-radius:99px; margin-left:.4rem; font-weight:600;
  white-space:nowrap; vertical-align:middle; }
.sd-1 { background:#dbeafe; color:#1d4ed8; }
.sd-2 { background:#dcfce7; color:#15803d; }
.sd-3 { background:#fef3c7; color:#b45309; }
.sd-4 { background:#f3e8ff; color:#7e22ce; }

/* total column */
.dpa-total { font-variant-numeric:tabular-nums; white-space:nowrap; }

/* states */
.dpa-state { padding:3rem 1rem; text-align:center; color:#94a3b8; }

/* summary bar */
#dpa-summary { display:none; }

/* Grand total footer */
.dpa-tfoot td { background:#1e293b; color:#fff; font-weight:700; padding:.5rem .75rem; }

/* nav pills override */
.dpa-nav .nav-link { color:#64748b; font-size:.86rem; padding:.38rem .9rem; }
.dpa-nav .nav-link.active { background:#3b82f6; color:#fff; }
.dpa-nav .nav-link i { opacity:.8; }
</style>

<div class="card shadow-sm" id="dpa-card">

  <!-- ── Header ── -->
  <div class="card-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
    <div class="d-flex align-items-center gap-2">
      <span class="fs-5 fw-semibold">
        <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>DPA
      </span>
      <span class="text-muted small d-none d-md-inline">Dokumen Pelaksanaan Anggaran</span>
    </div>
    <!-- Tab switcher -->
    <ul class="nav nav-pills dpa-nav mb-0" id="dpaTabs" role="tablist">
      <li class="nav-item">
        <button class="nav-link active" id="tab-prog-btn" data-tab="0">
          <i class="fa-solid fa-sitemap me-1"></i>Hierarki Program
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" id="tab-dana-btn" data-tab="1">
          <i class="fa-solid fa-coins me-1"></i>Hierarki Sumber Dana
        </button>
      </li>
    </ul>
  </div>

  <!-- ── Toolbar ── -->
  <div class="card-body border-bottom py-2">
    <div class="row g-2 align-items-end">
      <?php if ($is_super): ?>
      <div class="col-md-4 col-sm-8">
        <label class="form-label small mb-1">OPD</label>
        <select class="form-select form-select-sm" id="dpa_opd">
          <option value="">— Pilih OPD —</option>
          <?php foreach ($opd_opts as $k => $v): ?>
            <option value="<?= $k ?>"><?= html_escape($v) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div class="col-md-3 col-sm-6">
        <label class="form-label small mb-1">Cari</label>
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
          <input type="text" class="form-control border-start-0 ps-0" id="dpa_search" placeholder="nama, kode…">
          <button class="btn btn-outline-secondary" id="btnClearSearch" style="display:none">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </div>

      <div class="col-auto ms-auto d-flex gap-2">
        <button class="btn btn-sm btn-outline-primary px-3" id="btnExpandAll">
          <i class="fa-solid fa-maximize me-1"></i>Expand
        </button>
        <button class="btn btn-sm btn-outline-secondary px-3" id="btnCollapseAll">
          <i class="fa-solid fa-minimize me-1"></i>Collapse
        </button>
      </div>
    </div>
  </div>

  <!-- ── Summary bar ── -->
  <div id="dpa-summary" class="px-3 py-2 border-bottom small">
    <div class="d-flex flex-wrap gap-3 align-items-center" id="dpa-summary-text"></div>
  </div>

  <!-- ── Content ── -->
  <div class="card-body p-0" id="dpa-content">

    <div class="dpa-state" id="dpa-loading" style="display:none">
      <div class="spinner-border text-primary mb-2" style="width:2rem;height:2rem"></div>
      <div class="small mt-1">Memuat data DPA…</div>
    </div>
    <div class="dpa-state" id="dpa-empty" style="display:none">
      <i class="fa-solid fa-file-circle-question fa-2x text-muted mb-2 d-block"></i>
      <span>Data DPA tidak ditemukan untuk OPD ini.</span>
    </div>
    <div class="dpa-state" id="dpa-prompt" style="<?= $is_super ? '' : 'display:none' ?>">
      <i class="fa-solid fa-hand-point-up fa-2x text-muted mb-2 d-block"></i>
      <span>Pilih OPD untuk menampilkan data DPA.</span>
    </div>

    <!-- Tab 0: Program -->
    <div id="dpa-wrap-0" class="dpa-wrap table-responsive" style="display:none">
      <table class="dpa-tree">
        <colgroup><col class="c-uraian"><col class="c-koef"><col class="c-harga"><col class="c-total"></colgroup>
        <thead class="table-light border-bottom">
          <tr>
            <th class="ps-3 uraian-cell">Uraian / Nama</th>
            <th>Koefisien</th>
            <th class="text-end">Harga Satuan</th>
            <th class="text-end pe-3">Jumlah (Rp)</th>
          </tr>
        </thead>
        <tbody id="dpa-tbody-0"></tbody>
        <tfoot class="dpa-tfoot" id="dpa-foot-0" style="display:none">
          <tr><td colspan="3" class="ps-3">TOTAL DPA</td><td class="text-end pe-3 dpa-total" id="dpa-grand-0"></td></tr>
        </tfoot>
      </table>
    </div>

    <!-- Tab 1: Sumber Dana -->
    <div id="dpa-wrap-1" class="dpa-wrap table-responsive" style="display:none">
      <table class="dpa-tree">
        <colgroup><col class="c-uraian"><col class="c-koef"><col class="c-harga"><col class="c-total"></colgroup>
        <thead class="table-light border-bottom">
          <tr>
            <th class="ps-3 uraian-cell">Sumber Dana / Sub Kegiatan / Rekening</th>
            <th>Koefisien</th>
            <th class="text-end">Harga Satuan</th>
            <th class="text-end pe-3">Jumlah (Rp)</th>
          </tr>
        </thead>
        <tbody id="dpa-tbody-1"></tbody>
        <tfoot class="dpa-tfoot" id="dpa-foot-1" style="display:none">
          <tr><td colspan="3" class="ps-3">TOTAL DPA</td><td class="text-end pe-3 dpa-total" id="dpa-grand-1"></td></tr>
        </tfoot>
      </table>
    </div>

  </div><!-- /card-body -->
</div>

<script>
(function () {
'use strict';

var TREE_URL = '<?= $tree_url ?>';
var IS_SUPER = <?= $is_super ? 'true' : 'false' ?>;
var MY_OPD   = '<?= $my_opd_id ?>';

/* ── Util ── */
function money(v) {
  return Number(v || 0).toLocaleString('id-ID', { minimumFractionDigits:0, maximumFractionDigits:0 });
}
function esc(v) {
  return v == null ? '' : $('<div>').text(String(v)).html();
}

/* sumber dana color class cycling */
var SD_COLORS = ['sd-1','sd-2','sd-3','sd-4'];
var sdColorMap = {};
var sdColorIdx = 0;
function sdColor(sdId) {
  if (!sdId) return 'sd-1';
  if (!sdColorMap[sdId]) sdColorMap[sdId] = SD_COLORS[(sdColorIdx++) % SD_COLORS.length];
  return sdColorMap[sdId];
}

/* ── TreeView factory ── */
function TreeView(tbodyId, footId, grandId, mode) {
  var expandState = {}, nodeChildren = {}, nodeRows = {}, detN = 0, preSearchState = null;
  var INDENT = mode === 'dana' ? [14,30,54,76] : [14,30,52,74,96,118];
  var LVCLS  = mode === 'dana' ? ['dr0','dr5','dr3','dr4','dr6'] : ['dr0','dr1','dr2','dr3','dr4','dr6'];

  function makeHdrRow(nid, pid, lv, kode, nama, chipClass, total, hasKids) {
    var tr = document.createElement('tr');
    tr.className = 'dpa-r ' + (LVCLS[lv] || 'dr6');
    tr.dataset.nid = nid;
    if (pid) tr.dataset.pid = pid;

    var pad = INDENT[lv] || 14;
    var td1 = document.createElement('td');
    td1.className = 'uraian-cell';
    td1.style.paddingLeft = pad + 'px';
    if (hasKids) td1.style.cursor = 'pointer';

    if (hasKids) {
      var ic = document.createElement('i');
      ic.className = 'fa-solid fa-chevron-right tog me-2';
      td1.appendChild(ic);
    } else {
      td1.insertAdjacentHTML('beforeend','<span style="display:inline-block;width:20px"></span>');
    }
    if (chipClass) td1.insertAdjacentHTML('beforeend', '<span class="lv-chip '+chipClass+'">'+chipClass.replace('lv-','').toUpperCase()+'</span>');
    if (kode) td1.insertAdjacentHTML('beforeend', '<span class="dpa-kode">'+esc(kode)+'</span>');
    td1.insertAdjacentHTML('beforeend', '<span>'+esc(nama)+'</span>');

    var td4 = document.createElement('td');
    td4.className = 'text-end dpa-total fw-semibold pe-3';
    td4.textContent = money(total);

    tr.appendChild(td1); tr.appendChild(document.createElement('td')); tr.appendChild(document.createElement('td')); tr.appendChild(td4);
    if (hasKids) tr.addEventListener('click', function(){ toggleNode(nid); });
    return tr;
  }

  function makeDetRow(pid, item) {
    var nid = 'dt'+(++detN);
    var tr = document.createElement('tr');
    tr.className = 'dpa-r ' + (LVCLS[LVCLS.length-1] || 'dr6');
    tr.dataset.nid = nid; tr.dataset.pid = pid;

    var pad = INDENT[INDENT.length-1] || 118;
    var td1 = document.createElement('td');
    td1.style.paddingLeft = pad + 'px';
    td1.innerHTML = esc(item.paket || '—')
      + (item.sd ? '<span class="sd-badge '+sdColor(item.sd_id)+'">'+esc(item.sd)+'</span>' : '');

    var td2 = document.createElement('td');
    td2.style.color='#777'; td2.textContent = item.koef || '—';

    var td3 = document.createElement('td');
    td3.className='text-end'; td3.style.color='#777';
    td3.textContent = item.harga ? money(item.harga) : '—';

    var td4 = document.createElement('td');
    td4.className = 'text-end dpa-total pe-3';
    td4.textContent = money(item.total);

    tr.appendChild(td1); tr.appendChild(td2); tr.appendChild(td3); tr.appendChild(td4);
    return { nid:nid, tr:tr };
  }

  function cascade(nid, show) {
    (nodeChildren[nid]||[]).forEach(function(cid){
      var cr = nodeRows[cid]; if (!cr) return;
      cr.style.display = show ? '' : 'none';
      cascade(cid, show && !!expandState[cid]);
    });
  }

  function toggleNode(nid) {
    expandState[nid] = !expandState[nid];
    var row = nodeRows[nid]; if (!row) return;
    if (expandState[nid]) row.classList.add('open'); else row.classList.remove('open');
    cascade(nid, expandState[nid]);
  }

  function addNode(tr, nid, pid, expanded, visible) {
    nodeRows[nid] = tr; expandState[nid] = expanded;
    nodeChildren[nid] = [];
    if (pid) (nodeChildren[pid] = nodeChildren[pid]||[]).push(nid);
    tr.style.display = visible ? '' : 'none';
  }

  function buildProgram(tbody, data) {
    var opd = data.opd, opdId = 'opd'+opd.id;
    var opdRow = makeHdrRow(opdId,null,0,null,opd.singkat||opd.nama,null,opd.total,true);
    opdRow.classList.add('open');
    tbody.appendChild(opdRow); addNode(opdRow,opdId,null,true,true);

    data.programs.forEach(function(prog){
      var pid='pr'+prog.id;
      var pr=makeHdrRow(pid,opdId,1,prog.kode,prog.nama,'lv-prog',prog.total,true);
      tbody.appendChild(pr); addNode(pr,pid,opdId,false,true);

      prog.kegiatans.forEach(function(keg){
        var kid='kg'+keg.id;
        var kr=makeHdrRow(kid,pid,2,keg.kode,keg.nama,'lv-keg',keg.total,true);
        tbody.appendChild(kr); addNode(kr,kid,pid,false,false);

        keg.subkegiatans.forEach(function(sk){
          var skid='sk'+sk.id;
          var skr=makeHdrRow(skid,kid,3,sk.kode,sk.nama,'lv-sk',sk.total,true);
          tbody.appendChild(skr); addNode(skr,skid,kid,false,false);

          sk.rekenings.forEach(function(rek,ri){
            var rid='rk'+sk.id+'_'+ri;
            var rr=makeHdrRow(rid,skid,4,rek.kode,rek.nama,'lv-rek',rek.total,true);
            tbody.appendChild(rr); addNode(rr,rid,skid,false,false);

            rek.items.forEach(function(item){
              var d=makeDetRow(rid,item);
              tbody.appendChild(d.tr); addNode(d.tr,d.nid,rid,false,false);
            });
          });
        });
      });
    });
  }

  function buildDana(tbody, data) {
    var opd=data.opd, opdId='opd'+opd.id;
    var opdRow=makeHdrRow(opdId,null,0,null,opd.singkat||opd.nama,null,opd.total,true);
    opdRow.classList.add('open');
    tbody.appendChild(opdRow); addNode(opdRow,opdId,null,true,true);

    /* Regroup: sd → subkeg → rekening */
    var sdMap={};
    data.programs.forEach(function(prog){
      prog.kegiatans.forEach(function(keg){
        keg.subkegiatans.forEach(function(sk){
          sk.rekenings.forEach(function(rek,ri){
            rek.items.forEach(function(item){
              var sdid=item.sd_id||0, sdnm=item.sd||'(Sumber Dana Tidak Ada)';
              if(!sdMap[sdid]) sdMap[sdid]={id:sdid,nama:sdnm,total:0,subkegiatans:{}};
              sdMap[sdid].total+=item.total;
              var skid=sk.id;
              if(!sdMap[sdid].subkegiatans[skid])
                sdMap[sdid].subkegiatans[skid]={id:sk.id,kode:sk.kode,nama:sk.nama,total:0,rekenings:{}};
              sdMap[sdid].subkegiatans[skid].total+=item.total;
              var rkid=sk.id+'_'+ri;
              if(!sdMap[sdid].subkegiatans[skid].rekenings[rkid])
                sdMap[sdid].subkegiatans[skid].rekenings[rkid]={id:rek.id,kode:rek.kode,nama:rek.nama,total:0,items:[]};
              sdMap[sdid].subkegiatans[skid].rekenings[rkid].total+=item.total;
              sdMap[sdid].subkegiatans[skid].rekenings[rkid].items.push(item);
            });
          });
        });
      });
    });

    Object.values(sdMap).sort(function(a,b){return b.total-a.total;}).forEach(function(sd){
      var sdnid='sd'+sd.id;
      var sdr=makeHdrRow(sdnid,opdId,1,null,sd.nama,'lv-sd',sd.total,true);
      tbody.appendChild(sdr); addNode(sdr,sdnid,opdId,false,true);

      Object.values(sd.subkegiatans).sort(function(a,b){return (a.kode||'').localeCompare(b.kode||'');}).forEach(function(sk){
        var skinid='sd'+sd.id+'sk'+sk.id;
        var skr=makeHdrRow(skinid,sdnid,2,sk.kode,sk.nama,'lv-sk',sk.total,true);
        tbody.appendChild(skr); addNode(skr,skinid,sdnid,false,false);

        Object.values(sk.rekenings).forEach(function(rek,ri){
          var rinid='sd'+sd.id+'sk'+sk.id+'rk'+ri;
          var rr=makeHdrRow(rinid,skinid,3,rek.kode,rek.nama,'lv-rek',rek.total,true);
          tbody.appendChild(rr); addNode(rr,rinid,skinid,false,false);

          rek.items.forEach(function(item){
            var d=makeDetRow(rinid,item);
            tbody.appendChild(d.tr); addNode(d.tr,d.nid,rinid,false,false);
          });
        });
      });
    });
  }

  return {
    build: function(data) {
      var tbody = document.getElementById(tbodyId);
      tbody.innerHTML = '';
      expandState={}; nodeChildren={}; nodeRows={}; detN=0; preSearchState=null; sdColorMap={}; sdColorIdx=0;
      if (mode==='dana') buildDana(tbody,data); else buildProgram(tbody,data);
      document.getElementById(footId).style.display='';
      document.getElementById(grandId).textContent = money(data.opd.total);
    },
    expandAll: function() {
      Object.keys(expandState).forEach(function(nid){
        expandState[nid]=true;
        if(nodeRows[nid]) nodeRows[nid].classList.add('open');
      });
      Object.values(nodeRows).forEach(function(r){ r.style.display=''; });
    },
    collapseAll: function() {
      Object.keys(expandState).forEach(function(nid){
        if(!nid.startsWith('opd')){ expandState[nid]=false; if(nodeRows[nid]) nodeRows[nid].classList.remove('open'); }
      });
      Object.values(nodeRows).forEach(function(r){
        var pid=r.dataset?r.dataset.pid:null;
        r.style.display = pid?'none':'';
      });
      /* expand OPD so top-level children show */
      Object.keys(nodeRows).filter(function(k){return k.startsWith('opd');}).forEach(function(nid){
        expandState[nid]=true; nodeRows[nid].classList.add('open'); cascade(nid,true);
      });
    },
    search: function(q, restore) {
      if (restore) {
        /* restore pre-search expandState if we have a snapshot */
        if (preSearchState) {
          Object.keys(preSearchState).forEach(function(k){ expandState[k]=preSearchState[k]; });
          preSearchState = null;
        }
        Object.values(nodeRows).forEach(function(r){
          var pid=r.dataset?r.dataset.pid:null; r.style.display=pid?'none':'';
        });
        Object.keys(nodeRows).filter(function(k){return k.startsWith('opd');}).forEach(function(nid){
          if(expandState[nid]) nodeRows[nid].classList.add('open'); else nodeRows[nid].classList.remove('open');
          cascade(nid, expandState[nid]);
        });
        return;
      }
      /* snapshot expandState once before first search */
      if (!preSearchState) {
        preSearchState = {};
        Object.keys(expandState).forEach(function(k){ preSearchState[k]=expandState[k]; });
      }
      var vis={};
      Object.keys(nodeRows).forEach(function(nid){
        var text=(nodeRows[nid].textContent||'').toLowerCase();
        if(text.indexOf(q)<0) return;
        vis[nid]=true;
        var pid=nodeRows[nid].dataset?nodeRows[nid].dataset.pid:null;
        while(pid && nodeRows[pid]){
          vis[pid]=true; expandState[pid]=true; nodeRows[pid].classList.add('open');
          pid=nodeRows[pid].dataset?nodeRows[pid].dataset.pid:null;
        }
      });
      Object.keys(nodeRows).forEach(function(nid){ nodeRows[nid].style.display=vis[nid]?'':'none'; });
    },
    empty: function() { return Object.keys(nodeRows).length===0; }
  };
}

/* ── Init ── */
var trees = [
  TreeView('dpa-tbody-0','dpa-foot-0','dpa-grand-0','program'),
  TreeView('dpa-tbody-1','dpa-foot-1','dpa-grand-1','dana')
];
var activeTab = 0;
var currentData = null;

function show(id){ document.getElementById(id).style.display=''; }
function hide(id){ document.getElementById(id).style.display='none'; }

function showTab(idx) {
  /* hide all wraps */
  document.querySelectorAll('.dpa-wrap').forEach(function(el){ el.style.display='none'; });
  document.querySelectorAll('.dpa-nav .nav-link').forEach(function(btn){ btn.classList.remove('active'); });
  activeTab = idx;
  document.querySelector('[data-tab="'+idx+'"]').classList.add('active');
  if (!currentData) return;
  if (trees[idx].empty()) trees[idx].build(currentData);
  show('dpa-wrap-'+idx);
}

/* ── Load ── */
function loadTree(opdId) {
  show('dpa-loading');
  ['dpa-empty','dpa-prompt'].forEach(hide);
  document.querySelectorAll('.dpa-wrap').forEach(function(el){ el.style.display='none'; });
  document.getElementById('dpa-summary').style.display='none';
  currentData=null;

  $.getJSON(TREE_URL+(opdId?'?opd_id='+encodeURIComponent(opdId):''))
    .done(function(data){
      hide('dpa-loading');
      if(!data||!data.opd){ show('dpa-empty'); return; }
      currentData=data;
      /* reset both trees */
      trees.forEach(function(t){ /* will rebuild lazily when tab shown */ });
      trees[0] = TreeView('dpa-tbody-0','dpa-foot-0','dpa-grand-0','program');
      trees[1] = TreeView('dpa-tbody-1','dpa-foot-1','dpa-grand-1','dana');
      showTab(activeTab);

      /* summary */
      var opd=data.opd;
      var progN=data.programs.length;
      var kegN=data.programs.reduce(function(s,p){return s+p.kegiatans.length;},0);
      var skN=data.programs.reduce(function(s,p){
        return s+p.kegiatans.reduce(function(s2,k){return s2+k.subkegiatans.length;},0);
      },0);
      document.getElementById('dpa-summary-text').innerHTML =
        '<i class="fa-solid fa-building-columns text-primary"></i><strong>'+esc(opd.nama)+'</strong>'
        +'<span class="text-muted">|</span><span class="badge bg-primary-subtle text-primary-emphasis">'+progN+' Program</span>'
        +'<span class="badge bg-info-subtle text-info-emphasis">'+kegN+' Kegiatan</span>'
        +'<span class="badge bg-success-subtle text-success-emphasis">'+skN+' Sub Kegiatan</span>'
        +'<span class="text-muted">|</span>'
        +'<strong class="text-primary">Rp '+money(opd.total)+'</strong>';
      document.getElementById('dpa-summary').style.display='';
    })
    .fail(function(){ hide('dpa-loading'); show('dpa-empty'); });
}

/* ── Tab switch ── */
document.querySelectorAll('.dpa-nav .nav-link').forEach(function(btn){
  btn.addEventListener('click', function(){
    /* reset search so new tab starts clean */
    document.getElementById('dpa_search').value='';
    document.getElementById('btnClearSearch').style.display='none';
    hasSearch=false;
    showTab(parseInt(this.dataset.tab));
  });
});

/* ── Expand / Collapse ── */
document.getElementById('btnExpandAll').addEventListener('click', function(){ trees[activeTab].expandAll(); });
document.getElementById('btnCollapseAll').addEventListener('click', function(){
  trees[activeTab].collapseAll();
  clearSearch();
});

/* ── Search ── */
var searchTimer=null, hasSearch=false;
document.getElementById('dpa_search').addEventListener('input', function(){
  clearTimeout(searchTimer);
  var q=this.value.trim().toLowerCase();
  document.getElementById('btnClearSearch').style.display=q?'':'none';
  if(!q){ if(hasSearch){ hasSearch=false; trees[activeTab].search('',true); } return; }
  searchTimer=setTimeout(function(){
    hasSearch=true; trees[activeTab].search(q,false);
  },220);
});
document.getElementById('btnClearSearch').addEventListener('click', function(){ clearSearch(); });
function clearSearch(){
  document.getElementById('dpa_search').value='';
  document.getElementById('btnClearSearch').style.display='none';
  if(hasSearch){ hasSearch=false; trees[activeTab].search('',true); }
}

/* ── OPD selector ── */
if (IS_SUPER) {
  document.getElementById('dpa_opd').addEventListener('change', function(){
    if(this.value) loadTree(this.value);
    else {
      ['dpa-loading','dpa-empty'].forEach(hide);
      document.querySelectorAll('.dpa-wrap').forEach(function(el){ el.style.display='none'; });
      document.getElementById('dpa-summary').style.display='none';
      show('dpa-prompt');
    }
  });
} else {
  loadTree(MY_OPD);
}

})();
</script>
