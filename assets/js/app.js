/* App JS — interaksi umum: sidebar toggle, submenu, konfirmasi hapus, DataTables default */
(function () {
  'use strict';

  // Sidebar toggle (mobile)
  document.addEventListener('click', function (e) {
    var toggle = e.target.closest('.navbar-toggle');
    if (toggle) {
      document.querySelector('.layout-sidebar').classList.toggle('show');
      document.querySelector('.sidebar-backdrop').classList.toggle('show');
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

  document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && jQuery.fn.DataTable) {
      jQuery('table.datatable').each(function () {
        var $t = jQuery(this);
        if (!$t.data('manual')) { window.initDataTable(this); }
      });
    }
  });
})();
