# Namua Penatausahaan

Aplikasi penatausahaan keuangan daerah berbasis CodeIgniter 3 untuk mengelola DPA dan
anggaran kas, NPD, daftar penerima, pindah buku dan pajak, penggajian ASN, serta laporan
realisasi dan dokumen pertanggungjawaban.

Produksi: `https://efin.namuaprojects.com`

Versi saat ini: `0.3.5-test` — siap untuk test jual internal dengan data sintetis, belum untuk
penjualan atau produksi pelanggan. Ikuti `INSTALL.md`; instalasi baru tidak membawa akun,
credential, konfigurasi tenant, maupun data bawaan.

Dokumentasi internal repository (tidak disertakan dalam artifact customer):

- `docs/2026-09-06_roadmap_perbaikan_dan_komersialisasi_penatausahaan.md`
- `docs/ROADMAP.md`
- `docs/PROGRESS.md`
- `docs/DECISIONS.md`
- `docs/DB_SCHEMA.md`

Catatan keamanan: endpoint Setup hanya dapat dijalankan melalui CLI. Jangan meletakkan
credential, dump database, log, atau data pelanggan di dalam paket rilis maupun webroot.
Gunakan `tools/preflight.php`, `tools/security_scan.php`, `tools/http_smoke.php`, dan
`tools/authenticated_smoke.php` sebagai gerbang sebelum membuka instance test.
Artifact customer wajib diaktifkan menggunakan release manifest Ed25519 yang dipercaya dan
`tools/activate_release.php`; aktivasi atau rollback akan mengirim receipt HMAC ke Control.
