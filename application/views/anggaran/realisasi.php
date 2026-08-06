<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Var: $opd_opts, $is_super, $my_opd_id, $tree_url */
?>
<style>
.lra-tree { table-layout:fixed; border-collapse:collapse; width:100%; }
.lra-tree col.c-pagu, .lra-tree col.c-real, .lra-tree col.c-sisa { width:160px; }
.lra-tree td { padding:.36rem .7rem; border-bottom:1px solid rgba(0,0,0,.045); vertical-align:middle; line-height:1.4; }
.lra-tree td.num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
.dr0>td { background:linear-gradient(90deg,#1a3a6c,#1d4ed8); color:#fff; font-weight:700; font-size:.92rem; }
.dr1>td { background:#dbeafe; border-left:4px solid #3b82f6; }
.dr2>td { background:#eff6ff; border-left:4px solid #93c5fd; }
.dr3>td { background:#f0fdf4; border-left:4px solid #4ade80; }
.dr4>td { background:#fffbeb; border-left:4px solid #f59e0b; }
.dr5>td { background:#fff; border-left:4px solid #fde68a; font-size:.83rem; }
.dr6>td { background:#fff; border-left:4px solid #e2e8f0; font-size:.82rem; }
.dr0:hover>td{background:linear-gradient(90deg,#1e3a8a,#2563eb);} .dr1:hover>td{background:#bfdbfe;} .dr2:hover>td{background:#dbeafe;}
.dr3:hover>td{background:#dcfce7;} .dr4:hover>td{background:#fef3c7;} .dr5:hover>td,.dr6:hover>td{background:#f8fafc;}
.tog { display:inline-block; width:14px; font-size:.7rem; transition:transform .17s; opacity:.65; }
.lra-r.open>.uraian-cell>.tog { transform:rotate(90deg); opacity:1; }
.lra-kode { font-family:Consolas,monospace; font-size:.72em; background:rgba(0,0,0,.08); padding:0 5px; border-radius:3px; margin-right:.35rem; white-space:nowrap; }
.dr0 .lra-kode { background:rgba(255,255,255,.2); color:#fff; }
.lv-chip { font-size:.6rem; font-weight:700; letter-spacing:.5px; padding:1px 5px; border-radius:3px; margin-right:.4rem; opacity:.8; vertical-align:middle; }
.lv-prog{background:#bfdbfe;color:#1e40af;} .lv-keg{background:#bae6fd;color:#075985;} .lv-sk{background:#bbf7d0;color:#166534;}
.lv-rek{background:#fde68a;color:#92400e;} .lv-pek{background:#fbcfe8;color:#9d174e;} .lv-sd{background:#e9d5ff;color:#6b21a8;}
.sisa-pos { color:#15803d; } .sisa-neg { color:#dc2626; font-weight:600; } .real-v { color:#1d4ed8; }
.pct { font-size:.66rem; color:#64748b; margin-left:.35rem; }
.lra-state { padding:3rem 1rem; text-align:center; color:#94a3b8; }
.lra-tfoot td { background:#1e293b; color:#fff; font-weight:700; padding:.5rem .7rem; }
.lra-nav .nav-link { color:#64748b; font-size:.86rem; padding:.38rem .9rem; } .lra-nav .nav-link.active { background:#3b82f6; color:#fff; }
</style>

<div class="card shadow-sm">
  <div class="card-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
    <span class="fs-5 fw-semibold"><i class="fa-solid fa-chart-line text-primary me-2"></i>Realisasi Anggaran (LRA)</span>
    <ul class="nav nav-pills lra-nav mb-0" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-tab="0"><i class="fa-solid fa-sitemap me-1"></i>Program</button></li>
      <li class="nav-item"><button class="nav-link" data-tab="1"><i class="fa-solid fa-briefcase me-1"></i>Pekerjaan</button></li>
      <li class="nav-item"><button class="nav-link" data-tab="2"><i class="fa-solid fa-coins me-1"></i>Sumber Dana</button></li>
    </ul>
  </div>

  <div class="card-body border-bottom py-2">
    <div class="row g-2 align-items-end">
      <?php if ($is_super): ?>
      <div class="col-md-4 col-sm-8">
        <label class="form-label small mb-1">OPD</label>
        <select class="form-select form-select-sm" id="lra_opd">
          <option value="">— Pilih OPD —</option>
          <?php foreach ($opd_opts as $k => $v): ?><option value="<?= $k ?>"><?= html_escape($v) ?></option><?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-md-3 col-sm-6">
        <label class="form-label small mb-1">Cari</label>
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
          <input type="text" class="form-control border-start-0 ps-0" id="lra_search" placeholder="nama, kode…">
        </div>
      </div>
      <div class="col-auto ms-auto d-flex gap-2">
        <button class="btn btn-sm btn-outline-primary px-3" id="btnExpand"><i class="fa-solid fa-maximize me-1"></i>Expand</button>
        <button class="btn btn-sm btn-outline-secondary px-3" id="btnCollapse"><i class="fa-solid fa-minimize me-1"></i>Collapse</button>
      </div>
    </div>
  </div>

  <div id="lra-summary" class="px-3 py-2 border-bottom small" style="display:none"><div class="d-flex flex-wrap gap-3 align-items-center" id="lra-summary-text"></div></div>

  <div class="card-body p-0">
    <div class="lra-state" id="lra-loading" style="display:none"><div class="spinner-border text-primary mb-2" style="width:2rem;height:2rem"></div><div class="small">Memuat…</div></div>
    <div class="lra-state" id="lra-empty" style="display:none"><i class="fa-solid fa-file-circle-question fa-2x text-muted mb-2 d-block"></i>Data DPA tidak ditemukan untuk OPD ini.</div>
    <div class="lra-state" id="lra-prompt" style="<?= $is_super ? '' : 'display:none' ?>"><i class="fa-solid fa-hand-point-up fa-2x text-muted mb-2 d-block"></i>Pilih OPD untuk menampilkan realisasi.</div>

    <?php foreach (array(0,1,2) as $t): ?>
    <div class="lra-wrap table-responsive" id="lra-wrap-<?= $t ?>" style="display:none">
      <table class="lra-tree">
        <colgroup><col><col class="c-pagu"><col class="c-real"><col class="c-sisa"></colgroup>
        <thead class="table-light border-bottom"><tr>
          <th class="ps-3">Uraian / Nama</th><th class="text-end">Pagu (Rp)</th><th class="text-end">Realisasi (Rp)</th><th class="text-end pe-3">Sisa (Rp)</th>
        </tr></thead>
        <tbody id="lra-tbody-<?= $t ?>"></tbody>
        <tfoot class="lra-tfoot" id="lra-foot-<?= $t ?>" style="display:none">
          <tr><td class="ps-3">TOTAL</td><td class="text-end num" id="g-pagu-<?= $t ?>"></td><td class="text-end num" id="g-real-<?= $t ?>"></td><td class="text-end pe-3 num" id="g-sisa-<?= $t ?>"></td></tr>
        </tfoot>
      </table>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
(function(){
'use strict';
var TREE_URL='<?= $tree_url ?>', IS_SUPER=<?= $is_super?'true':'false' ?>, MY_OPD='<?= $my_opd_id ?>';
function money(v){ return Number(v||0).toLocaleString('id-ID',{maximumFractionDigits:0}); }
function esc(v){ return v==null?'':$('<div>').text(String(v)).html(); }
function pct(r,p){ return p>0?Math.round(r/p*100):0; }

/* level extractors per tab */
var fP=function(l){return{key:'p'+l.p.id,kode:l.p.kode,nama:l.p.nama};},
    fK=function(l){return{key:'k'+l.k.id,kode:l.k.kode,nama:l.k.nama};},
    fS=function(l){return{key:'s'+l.s.id,kode:l.s.kode,nama:l.s.nama};},
    fPk=function(l){return{key:'pk'+l.paket,kode:null,nama:l.paket};},
    fSd=function(l){return{key:'sd'+l.sd.id,kode:null,nama:l.sd.nama};},
    fR=function(l){return{key:'r'+l.r.id,kode:l.r.kode,nama:l.r.nama};};
var TABS=[
  { levels:[fP,fK,fS,fR],     chips:['lv-prog','lv-keg','lv-sk','lv-rek'] },
  { levels:[fP,fK,fS,fPk,fR], chips:['lv-prog','lv-keg','lv-sk','lv-pek','lv-rek'] },
  { levels:[fSd,fS,fR],       chips:['lv-sd','lv-sk','lv-rek'] }
];

function groupTree(leaves, levels){
  var root={pagu:0,real:0,children:{},order:[]};
  leaves.forEach(function(l){
    var node=root; node.pagu+=l.pagu; node.real+=l.real;
    levels.forEach(function(fn){
      var m=fn(l);
      if(!node.children[m.key]){ node.children[m.key]={kode:m.kode,nama:m.nama,pagu:0,real:0,children:{},order:[]}; node.order.push(m.key); }
      node=node.children[m.key]; node.pagu+=l.pagu; node.real+=l.real;
    });
  });
  return root;
}

function Tree(idx){
  var tb='lra-tbody-'+idx, ft='lra-foot-'+idx;
  var rows={}, kids={}, exp={};
  var LVCLS=['dr0','dr1','dr2','dr3','dr4','dr5','dr6'];
  function numCell(v,cls){ var td=document.createElement('td'); td.className='num '+(cls||''); td.textContent=money(v); return td; }
  function makeRow(nid,pid,depth,kode,nama,chip,node,hasKids){
    var tr=document.createElement('tr'); tr.className='lra-r '+(LVCLS[Math.min(depth,6)]); tr.dataset.nid=nid; if(pid)tr.dataset.pid=pid;
    var td1=document.createElement('td'); td1.className='uraian-cell'; td1.style.paddingLeft=(14+depth*22)+'px';
    if(hasKids){ td1.style.cursor='pointer'; td1.insertAdjacentHTML('beforeend','<i class="fa-solid fa-chevron-right tog me-2"></i>'); }
    else td1.insertAdjacentHTML('beforeend','<span style="display:inline-block;width:20px"></span>');
    if(chip) td1.insertAdjacentHTML('beforeend','<span class="lv-chip '+chip+'">'+chip.replace('lv-','').toUpperCase()+'</span>');
    if(kode) td1.insertAdjacentHTML('beforeend','<span class="lra-kode">'+esc(kode)+'</span>');
    td1.insertAdjacentHTML('beforeend','<span>'+esc(nama)+'</span>');
    tr.appendChild(td1);
    tr.appendChild(numCell(node.pagu, depth===0?'':'fw-semibold'));
    var tdR=document.createElement('td'); tdR.className='num real-v'; tdR.innerHTML=money(node.real)+'<span class="pct">'+pct(node.real,node.pagu)+'%</span>'; tr.appendChild(tdR);
    var sisa=node.pagu-node.real, tdS=document.createElement('td'); tdS.className='num pe-3 '+(sisa<-0.001?'sisa-neg':'sisa-pos'); tdS.textContent=money(sisa); tr.appendChild(tdS);
    if(hasKids) tr.addEventListener('click',function(){ toggle(nid); });
    return tr;
  }
  function addNode(tr,nid,pid,expanded,visible){ rows[nid]=tr; exp[nid]=expanded; kids[nid]=[]; if(pid)(kids[pid]=kids[pid]||[]).push(nid); tr.style.display=visible?'':'none'; }
  function cascade(nid,show){ (kids[nid]||[]).forEach(function(c){ var r=rows[c]; if(!r)return; r.style.display=show?'':'none'; cascade(c, show&&!!exp[c]); }); }
  function toggle(nid){ exp[nid]=!exp[nid]; var r=rows[nid]; if(!r)return; r.classList.toggle('open',exp[nid]); cascade(nid,exp[nid]); }

  return {
    build:function(opd,root,chips){
      var tbody=document.getElementById(tb); tbody.innerHTML=''; rows={};kids={};exp={};
      var oid='opd'; var orow=makeRow(oid,null,0,null,opd.singkat||opd.nama,null,opd,true); orow.classList.add('open');
      tbody.appendChild(orow); addNode(orow,oid,null,true,true);
      (function walk(node,pid,depth){
        node.order.forEach(function(k){
          var ch=node.children[k], nid=pid+'/'+k, hasKids=ch.order.length>0;
          tbody.appendChild(makeRow(nid,pid,depth,ch.kode,ch.nama,chips[depth-1]||'',ch,hasKids));
          addNode(tbody.lastChild,nid,pid,false,depth===1);
          if(hasKids) walk(ch,nid,depth+1);
        });
      })(root,oid,1);
      document.getElementById(ft).style.display='';
      document.getElementById('g-pagu-'+idx).textContent=money(opd.pagu);
      document.getElementById('g-real-'+idx).textContent=money(opd.real);
      var gs=document.getElementById('g-sisa-'+idx); gs.textContent=money(opd.sisa); gs.className='text-end pe-3 num '+(opd.sisa<-0.001?'sisa-neg':'');
    },
    expandAll:function(){ Object.keys(exp).forEach(function(n){ exp[n]=true; if(rows[n])rows[n].classList.add('open'); }); Object.values(rows).forEach(function(r){ r.style.display=''; }); },
    collapseAll:function(){ Object.keys(exp).forEach(function(n){ if(n!=='opd'){ exp[n]=false; if(rows[n])rows[n].classList.remove('open'); } }); Object.values(rows).forEach(function(r){ r.style.display=(r.dataset&&r.dataset.pid)?'none':''; }); exp['opd']=true; if(rows['opd']){rows['opd'].classList.add('open'); cascade('opd',true);} },
    search:function(q){
      if(!q){ Object.values(rows).forEach(function(r){ r.style.display=(r.dataset&&r.dataset.pid)?'none':''; }); exp['opd']=true; if(rows['opd']){rows['opd'].classList.add('open'); cascade('opd',true);} return; }
      var vis={};
      Object.keys(rows).forEach(function(nid){ if((rows[nid].textContent||'').toLowerCase().indexOf(q)<0)return; vis[nid]=true; var pid=rows[nid].dataset?rows[nid].dataset.pid:null; while(pid&&rows[pid]){ vis[pid]=true; exp[pid]=true; rows[pid].classList.add('open'); pid=rows[pid].dataset?rows[pid].dataset.pid:null; } });
      Object.keys(rows).forEach(function(nid){ rows[nid].style.display=vis[nid]?'':'none'; });
    },
    empty:function(){ return Object.keys(rows).length===0; }
  };
}

var trees=[Tree(0),Tree(1),Tree(2)], activeTab=0, DATA=null, built=[false,false,false];
function show(id){document.getElementById(id).style.display='';} function hide(id){document.getElementById(id).style.display='none';}

function showTab(idx){
  document.querySelectorAll('.lra-wrap').forEach(function(el){ el.style.display='none'; });
  document.querySelectorAll('.lra-nav .nav-link').forEach(function(b){ b.classList.remove('active'); });
  activeTab=idx; document.querySelector('.lra-nav [data-tab="'+idx+'"]').classList.add('active');
  if(!DATA) return;
  if(!built[idx]){ trees[idx].build(DATA.opd, groupTree(DATA.leaves, TABS[idx].levels), TABS[idx].chips); built[idx]=true; }
  show('lra-wrap-'+idx);
}

function load(opdId){
  show('lra-loading'); ['lra-empty','lra-prompt'].forEach(hide); document.querySelectorAll('.lra-wrap').forEach(function(el){el.style.display='none';}); hide('lra-summary'); DATA=null; built=[false,false,false];
  $.getJSON(TREE_URL+(opdId?'?opd_id='+encodeURIComponent(opdId):'')).done(function(d){
    hide('lra-loading');
    if(!d||!d.opd){ show('lra-empty'); return; }
    DATA=d; trees=[Tree(0),Tree(1),Tree(2)]; built=[false,false,false]; showTab(activeTab);
    var o=d.opd;
    document.getElementById('lra-summary-text').innerHTML=
      '<i class="fa-solid fa-building-columns text-primary"></i><strong>'+esc(o.nama)+'</strong>'
      +'<span class="text-muted">|</span>Pagu <strong>Rp '+money(o.pagu)+'</strong>'
      +'<span class="text-muted">|</span>Realisasi <strong class="real-v">Rp '+money(o.real)+'</strong> <span class="badge bg-primary-subtle text-primary-emphasis">'+pct(o.real,o.pagu)+'%</span>'
      +'<span class="text-muted">|</span>Sisa <strong class="'+(o.sisa<0?'sisa-neg':'sisa-pos')+'">Rp '+money(o.sisa)+'</strong>';
    show('lra-summary');
  }).fail(function(){ hide('lra-loading'); show('lra-empty'); });
}

document.querySelectorAll('.lra-nav .nav-link').forEach(function(b){ b.addEventListener('click',function(){ document.getElementById('lra_search').value=''; showTab(parseInt(this.dataset.tab)); }); });
document.getElementById('btnExpand').addEventListener('click',function(){ if(DATA)trees[activeTab].expandAll(); });
document.getElementById('btnCollapse').addEventListener('click',function(){ document.getElementById('lra_search').value=''; if(DATA)trees[activeTab].collapseAll(); });
var st=null; document.getElementById('lra_search').addEventListener('input',function(){ clearTimeout(st); var q=this.value.trim().toLowerCase(); st=setTimeout(function(){ if(DATA)trees[activeTab].search(q); },220); });
if(IS_SUPER){ document.getElementById('lra_opd').addEventListener('change',function(){ if(this.value)load(this.value); else{ document.querySelectorAll('.lra-wrap').forEach(function(el){el.style.display='none';}); hide('lra-summary'); show('lra-prompt'); } }); }
else load(MY_OPD);
})();
</script>
