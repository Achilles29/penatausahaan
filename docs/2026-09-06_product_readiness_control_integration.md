# Laporan kesiapan test jual dan integrasi Control

**Tanggal:** 6 September 2026  
**Versi:** `0.3.5-test`  
**Status:** master source sudah masuk Local Source Registry; **belum siap test jual ulang** sampai
katalog disetujui pengguna dan runtime demo terisolasi dibuat dari artifact.

> **Koreksi arsitektur 6 September 2026 malam:** direktori ini adalah master
> source, bukan aplikasi staging/produksi. Registry pilot, customer internal,
> instance `namua-penatausahaan-efin-staging`, heartbeat, receipt, dan artifact
> aktif telah dihapus dari Control. Cron heartbeat master dinonaktifkan. Hasil
> test source/build di bawah tetap menjadi bukti teknis, tetapi status health
> lama tidak boleh dianggap sebagai bukti instance runtime.

> **Integrasi baru:** `app-manifest.json` versi 2 kini mendeklarasikan identitas
> produk, 14 feature beserta maturity, edition `INTERNAL_TEST`, runtime contract,
> dan licensing policy. Scanner root Control membacanya dari master source lokal
> tanpa mengeksekusi source, lalu menerbitkan snapshot privat untuk preview.
> Produk sengaja belum diimpor agar proses approval dapat dicoba manual dari
> menu **Produk → Tambah dari source**.

## Ruang lingkup yang selesai

- Aplikasi berjalan melalui route CI3 tanpa 404 dan HTTPS tetap aktif.
- Secret aplikasi, credential DB, tenant config, dan secret heartbeat berada di luar repository/webroot.
- User database runtime `namua_penatus_app` hanya memiliki hak `SELECT`, `INSERT`, `UPDATE`, `DELETE`.
- CSRF aktif untuk form/AJAX; mutasi penting menolak GET; cookie `Secure`, `HttpOnly`, `SameSite=Lax`.
- Login dibatasi lima kegagalan per identitas/IP dalam 15 menit.
- Password minimal 12 karakter, tombol lihat password tersedia, password lama dipaksa diganti.
- Reset password admin dan penonaktifan akun mencabut sesi pada request berikutnya; role/scope dibaca
  ulang dari database di tiap request terautentikasi.
- IDOR lintas OPD pada simulasi/detail payroll dan pemilihan unit/bidang pengguna ditutup.
- Respons autentikasi/data memakai `no-store`; tenant logo dibatasi ke path asset relatif yang aman.
- Dump legacy dipindahkan dari webroot ke `/var/lib/penatausahaan-legacy/20260906/` (mode private).
- Backup harian mengenkripsi stream dump dengan XChaCha20-Poly1305 dan gzip tanpa file SQL plaintext,
  menyimpan 14 backup, lalu memverifikasi autentikasi, dekompresi, dan struktur SQL. Restore menolak
  format lama yang tidak ditandai aman untuk database disposable.
- Health endpoint dan heartbeat HMAC lima menit mengirim status web, database, session, runtime config,
  backup, serta disk—tanpa PII maupun nilai transaksi.
- Log aplikasi berbentuk JSON Lines private dengan request ID, method, path tanpa query string, level,
  environment, dan timestamp; file mode `0600` serta retensi otomatis 30 hari. Respons normal dan 404
  aplikasi mempertahankan `X-Request-ID` yang dapat dikorelasikan ke log.
- Artifact hanya berisi source runtime, asset produk, schema, deployment config, dan tools; tenant logo,
  cache, log, docs internal, Setup legacy, dump, backup, credential, data customer, serta tool signing
  milik publisher dikeluarkan.
- Clean install mendapat 41 tabel tanpa user/data bawaan. Admin pertama dibuat via CLI/env dan perintah
  menolak berjalan jika superadmin aktif sudah tersedia.
- Migration memakai akun DDL root-only yang terpisah dari runtime, advisory lock, checksum history,
  backup guard wajib, dan verifikasi pasca-up/pasca-down. Siklus baseline → upgrade → rollback telah
  lulus dua kali pada database disposable; instance staging berada pada schema `20260906170000`.
- Manifest release ditandatangani Ed25519 menggunakan private key root-only di Control. Aktivasi dan
  rollback memverifikasi signature, hash package, source, serta schema lalu mengirim receipt HMAC dengan
  idempotency dan replay protection ke endpoint Control.

## Bukti verifikasi

| Pemeriksaan | Hasil |
|---|---:|
| PHP syntax lint | Lulus untuk seluruh file PHP aplikasi/framework/tool |
| Preflight environment | 44/44 lulus |
| Security scan Penatausahaan | 48/48 lulus |
| HTTP smoke publik Penatausahaan | 30/30 lulus |
| HTTP smoke terautentikasi | 9/9 lulus; akun sementara otomatis dihapus |
| Clean schema install disposable | 41 tabel, 0 user/data bawaan, database test dihapus |
| Migration upgrade/rollback disposable | Lulus dua siklus; kembali tepat ke 41 tabel baseline |
| Migration dari artifact rilis | Lulus dua siklus dari package yang tersimpan di Control |
| Encrypted backup verification | Lulus autentikasi, dekompresi, dan struktur SQL |
| Restore disposable | 43 tabel aktual dan seluruh row count sama; database test dihapus |
| Signature negatif | Manifest yang diubah dalam memori ditolak oleh verifikasi Ed25519 |
| Kontrak receipt Control | Duplikat idempotent `200`; replay `409`; signature salah `401` |
| Control security/static scan | 57/57 lulus |
| Control HTTP smoke | Lulus termasuk endpoint receipt yang menolak method tidak sah |
| Nginx config | `nginx -t` lulus |
| Control heartbeat | HTTP 202, overall `OK`, seluruh enam komponen `OK` |

## Registrasi Control historis (sudah dihapus)

- Product: `NAMUA_PENATAUSAHAAN`
- Customer internal: `NAMUA_PRODUCT_LAB`
- Instance: `namua-penatausahaan-efin-staging`
- Environment/edition: `STAGING` / `ESSENTIAL`
- App/schema target: `0.3.5-test` / `20260906170000`
- Release channel/status: `ALPHA` / `DRAFT`
- Control release ID: `22` (tiga artifact terverifikasi: package, manifest, dan signature)
- Artifact SHA-256: `d1d54b840b226f2da598e619c6d18ad2e2794d3533a0370a029ca427849f92db`
- Manifest SHA-256: `3598cd0e42f685765931544f98d28c212e08a4b048afd20cb0c39adcdec61e17`
- Signing key ID: `48d46eb6-15aa-4e52-8e4f-f59f01c1c3f4`
- Release manifest v2 mengikat version, target/baseline schema, checksum package, serta checksum
  setiap migration; sidecar signature mengikat hash manifest dan diverifikasi ulang oleh Control.
- Receipt aktivasi `945042d1-f90b-41de-a85d-0a03968c7d96` diterima dengan status `SUCCEEDED`.
  `deployment_id` sengaja kosong karena aktivasi dilakukan out-of-band dan tidak boleh dianggap sebagai
  approval atau eksekusi deployment dari Control.
- Validasi staging tersimpan pada audit log Control sebagai `release.staging_validation_passed`;
  release sengaja tetap `DRAFT/ALPHA` dan tidak diberi status deployment/approval palsu.

Seluruh record pada daftar di atas sudah tidak aktif di Control. Backup database
dan archive private disimpan untuk pemulihan administratif; audit log tetap
append-only. Pendaftaran katalog berikutnya wajib melalui preview manifest pada
halaman `/products/import` di Control. Instance hanya dibuat setelah runtime
terpisah tersedia.

## Batas dan pekerjaan lanjutan

1. Rotasi akun live serta credential root aaPanel harus dilakukan setelah dependensi aplikasi lain di
   server bersama diaudit; perubahan ini sengaja tidak dilakukan sepihak.
2. Approval/eksekusi deployment dari Control dan pengaitan receipt ke deployment yang disetujui belum
   selesai; manifest/receipt bertanda tangan dan rollback schema teknis sudah tersedia.
3. Entitlement offline/read-only grace mode belum ditegakkan di aplikasi.
4. Test otomatis belum mencakup seluruh master, DPA, NPD, cetak, dan laporan.
5. Audit trail transaksi, workflow approval, persist pindah buku, SPJ/BKU, rule pack regulasi, dan modul
   proses bisnis lain tetap mengikuti P2–P4 dan memerlukan UAT/domain sign-off.
6. MFA, CSP tanpa inline script, dependency/SAST scan penuh, pelaporan error operasional, dan incident
   runbook masih merupakan hardening sebelum pilot dengan data nyata.

Karena itu label yang benar adalah **test-ready**, bukan **sell-ready**. Test jual harus memakai data
sintetis, akun unik per tester, serta release DRAFT/ALPHA yang tercatat di Control.
