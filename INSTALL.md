# Instalasi aman Namua Penatausahaan

Dokumen ini ditujukan untuk instalasi `DEMO` atau `STAGING` terisolasi. Jangan memakai data
pelanggan nyata untuk test jual.

## Persyaratan

- Linux, Nginx, HTTPS aktif, PHP 8.1–8.4;
- ekstensi PHP `curl`, `intl`, `mbstring`, `mysqli`, `pdo_mysql`, `sodium`, dan `zlib`;
- MySQL/MariaDB serta `mysqldump`;
- satu database dan satu user aplikasi khusus untuk setiap instance;
- akses operator/root untuk provisioning, konfigurasi Nginx, dan cron.

## Urutan instalasi

1. Ekstrak artifact ke document root baru. Jangan menyalin file runtime, cache, log, upload,
   backup, dump, atau data tenant dari instance lain.
2. Buat database kosong, lalu import `database/schema/20260906_baseline.sql` menggunakan akun
   migrasi/deployment. Jangan memberi hak DDL kepada user runtime aplikasi.
3. Provision konfigurasi dan user runtime dari shell operator:

   ```sh
   PENATUS_DB_ADMIN_USERNAME='akun-deploy' \
   PENATUS_DB_ADMIN_PASSWORD='isi-dari-secret-manager' \
   PENATUS_DB_DATABASE='penatus_customer' \
   APP_URL='https://subdomain.example.com' \
   PENATUS_APP_ENVIRONMENT='STAGING' \
   php tools/provision_runtime.php
   ```

   Secret disimpan di `/var/lib/penatausahaan-config/runtime.json`, bukan di webroot. Hapus
   variabel admin dari shell setelah provisioning. Akun runtime hanya memperoleh hak
   `SELECT`, `INSERT`, `UPDATE`, dan `DELETE` pada satu database.
4. Provision akun migrasi DDL yang terpisah dan root-only, lalu jalankan migration. Perintah
   migration selalu membuat serta memverifikasi backup terenkripsi baru sebelum mengubah schema:

   ```sh
   PENATUS_DB_ADMIN_USERNAME='akun-admin-db' \
   PENATUS_DB_ADMIN_PASSWORD='isi-dari-secret-manager' \
   php tools/provision_migration_user.php

   php tools/migrate.php status
   php tools/migrate.php up
   ```

   Credential migrasi disimpan sebagai `/var/lib/penatausahaan-config/migration.json` dengan
   owner `root:root` dan mode `0600`; PHP-FPM tidak dapat membacanya. User runtime aplikasi
   tetap tidak memperoleh hak DDL.
5. Buat superadmin pertama tanpa akun/password bawaan:

   ```sh
   PENATUS_ADMIN_USERNAME='admin-instance' \
   PENATUS_ADMIN_NAME='Administrator Instance' \
   PENATUS_ADMIN_PASSWORD='isi-dari-secret-manager' \
   php tools/create_admin.php
   ```

   Perintah menolak berjalan jika superadmin aktif sudah ada. Pengelolaan berikutnya dilakukan
   melalui layar pengguna setelah login.
6. Konfigurasikan identitas tenant dengan environment `PENATUS_TENANT_*` dan jalankan
   `php tools/configure_tenant.php`. Contoh field tersedia di `deploy/tenant.example.json`.
7. Di Control Center, buat customer/instance, edition, dan kredensial heartbeat unik. Simpan file
   hasil provisioning sebagai `/var/lib/penatausahaan-config/control-center-heartbeat.json`
   dengan owner `root:www` dan mode `0640`. Pasang **public trust key** produk yang diberikan
   operator Control pada
   `/var/lib/namua-control/release-signing/trusted/NAMUA_PENATAUSAHAAN.json` dengan owner
   `root:root` dan mode `0600`. Private signing key tidak boleh berada di server pelanggan.
8. Terapkan `deploy/nginx-ci3.conf` pada server block aaPanel, ganti `__APP_ROOT__`, aktifkan
   sertifikat SSL, lalu validasi dengan `nginx -t` sebelum reload. Cloudflare SSL/TLS wajib
   `Full (strict)`; Tunnel boleh diarahkan ke HTTP lokal karena koneksi publik berakhir di edge.
9. Buat direktori runtime private dengan menjalankan ulang `php tools/provision_runtime.php`.
   Pasang jadwal dari `deploy/cron.example` setelah mengganti `__APP_ROOT__` dan
   `__PHP_BINARY__`.
10. Buat serta verifikasi backup pertama:

   ```sh
   php tools/backup.php
   php tools/verify_backup.php
   ```

   Uji restore penuh memakai akun deployment pada database disposable:

   ```sh
   PENATUS_DB_RESTORE_USERNAME='akun-deploy' \
   PENATUS_DB_RESTORE_PASSWORD='isi-dari-secret-manager' \
   php tools/restore_test.php
   ```

11. Aktifkan identitas release menggunakan manifest dan signature yang dikirim bersama artifact.
    Perintah ini memverifikasi trust key Ed25519, hash manifest/artifact, kecocokan source dan schema,
    lalu mengirim receipt bertanda HMAC ke Control:

   ```sh
   php tools/activate_release.php /path/release/namua-penatausahaan-X.release.json activate
   ```

   Jika Control tidak dapat dijangkau saat aktivasi, receipt tetap tersimpan secara private dan dapat
   dikirim ulang tanpa membuat receipt baru:

   ```sh
   php tools/deployment_receipt.php retry
   ```

12. Jalankan gerbang teknis sebelum instance dibuka untuk penguji:

   ```sh
   php tools/preflight.php
   php tools/schema_install_test.php
   PENATUS_DB_RESTORE_USERNAME='akun-admin-db' \
   PENATUS_DB_RESTORE_PASSWORD='isi-dari-secret-manager' \
   php tools/migration_test.php
   php tools/security_scan.php
   php tools/http_smoke.php
   php tools/authenticated_smoke.php
   php index.php control_sync/status
   php index.php control_sync/send
   ```

## Rollback release

1. Buat dan verifikasi backup terenkripsi baru sebelum perubahan.
2. Jika target memakai schema lebih lama, jalankan `php tools/migrate.php down` dari release yang
   sedang aktif sampai target schema tercapai; jangan menghapus tabel secara manual.
3. Deploy artifact lama yang signature dan manifest-nya masih dipercaya, lalu alihkan document root
   secara atomik ke source tersebut.
4. Catat rollback serta kirim receipt ke Control:

   ```sh
   php tools/activate_release.php /path/release/namua-penatausahaan-LAMA.release.json rollback
   php tools/deployment_receipt.php retry
   ```

Rollback hanya diterima jika versi target lebih lama, signature sah, artifact/source cocok, tidak ada
migration tertunda, dan database tepat pada target schema. Receipt membuktikan hasil operasi, tetapi
tidak menggantikan approval deployment yang harus dilakukan terpisah di Control.

## Aturan data dan akun test

- Pakai data sintetis dan akun unik per tester; kata sandi minimal 12 karakter.
- Jangan menjalankan `Setup` pada database berisi data karena perintah itu destruktif dan hanya
  dipertahankan sebagai utilitas CLI legacy.
- Setelah admin mengganti password user atau menonaktifkan user, sesi lama user tersebut otomatis
  ditolak pada request berikutnya.
- Backup terenkripsi tidak menggantikan uji restore. Sebelum pilot dengan data nyata, decrypt dan
  restore pada database disposable menggunakan prosedur operator yang disetujui.

## Batas status test jual

Paket ini aman untuk demo/test produk dari sisi konfigurasi, autentikasi dasar, isolasi secret,
backup, migration upgrade/rollback teknis, health, heartbeat, dan packaging. Modul proses bisnis,
perhitungan regulasi, approval, audit transaksi, serta UAT tetap merupakan gerbang terpisah sebelum
penjualan atau pemakaian produksi pelanggan.
