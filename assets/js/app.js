/* App JS — interaksi umum: sidebar toggle, submenu, konfirmasi hapus, DataTables default */
(function () {
  'use strict';

  // Sidebar toggle (mobile)
  document.addEventListener('click', function (e) {
    var toggle = e.target.closest('.navbar-toggle');
    if (toggle) {
      if (window.innerWidth >= 992) {
        // Desktop: mini-collapse (disimpan di localStorage)
        var collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
        try { localStorage.setItem('sidebarCollapsed', collapsed ? 'true' : 'false'); } catch (err) {}
      } else {
        // Mobile: off-canvas
        document.querySelector('.layout-sidebar').classList.toggle('show');
        document.querySelector('.sidebar-backdrop').classList.toggle('show');
      }
    }
    if (e.target.closest('.sidebar-backdrop')) {
      document.querySelector('.layout-sidebar').classList.remove('show');
      document.querySelector('.sidebar-backdrop').classList.remove('show');
    }
    // Submenu expand/collapse
    var menuToggle = e.target.closest('.menu-toggle');
    if (menuToggle) {
      e.preventDefault();
      menuToggle.closest('.menu-item').classList.toggle('open');
    }
  });

  // Konfirmasi hapus (untuk form/link ber-atribut data-confirm)
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.hasAttribute('data-confirm')) {
      if (!window.confirm(form.getAttribute('data-confirm') || 'Yakin ingin menghapus data ini?')) {
        e.preventDefault();
      }
    }
  });

  // Inisialisasi DataTables untuk tabel ber-class .datatable
  window.initDataTable = function (selector, opts) {
    if (!window.jQuery || !jQuery.fn.DataTable) return null;
    var defaults = {
      pageLength: 25,
      lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'Semua']],
      language: {
        search: 'Cari:', lengthMenu: 'Tampil _MENU_ baris', info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
        infoEmpty: 'Tidak ada data', infoFiltered: '(disaring dari _MAX_ total)', zeroRecords: 'Data tidak ditemukan',
        paginate: { first: 'Awal', last: 'Akhir', next: 'Next', previous: 'Prev' }
      }
    };
    return jQuery(selector).DataTable(jQuery.extend(true, {}, defaults, opts || {}));
  };

  // Cascade filter bertingkat. Select ber-atribut .filter-input[data-cascade]
  // dengan data-level, data-label, data-opturl (URL opsi lengkap termasuk level).
  // Memilih level induk akan memuat ulang opsi level di bawahnya lalu reload tabel.
  window.initCascadeFilters = function (table) {
    var els = Array.prototype.slice.call(document.querySelectorAll('.filter-input[data-cascade]'));
    if (!els.length) return;
    var levels = els.map(function (el) {
      return { el: el, level: el.getAttribute('data-level'), label: el.getAttribute('data-label'), url: el.getAttribute('data-opturl') };
    });
    function params(idx) {
      var p = {};
      for (var i = 0; i < idx; i++) { if (levels[i].el.value) p[levels[i].level] = levels[i].el.value; }
      if (idx > 0) p.parent = levels[idx - 1].el.value || '';
      return p;
    }
    function load(idx, keep) {
      var f = levels[idx]; if (!f.url) return;
      var prev = keep ? f.el.value : '';
      jQuery.getJSON(f.url, params(idx), function (opts) {
        var h = '<option value="">— Semua ' + f.label + ' —</option>';
        Object.keys(opts).forEach(function (k) { h += '<option value="' + k + '">' + jQuery('<div>').text(opts[k]).html() + '</option>'; });
        f.el.innerHTML = h;
        if (keep && prev) f.el.value = prev;
      });
    }
    levels.forEach(function (f, idx) {
      f.el.addEventListener('change', function () {
        for (var j = idx + 1; j < levels.length; j++) { levels[j].el.value = ''; load(j, false); }
        table.ajax.reload();
      });
    });
    levels.forEach(function (f, idx) { load(idx, true); });
  };

  document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && jQuery.fn.DataTable) {
      jQuery('table.datatable').each(function () {
        var $t = jQuery(this);
        if (!$t.data('manual')) { window.initDataTable(this); }
      });
    }
  });
})();
