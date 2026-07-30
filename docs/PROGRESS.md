# PROGRESS — Catatan Teknis (aman untuk pindah device)

Terakhir diperbarui: **2026-07-30** — Tahap 1 selesai & teruji.

## Cara menjalankan (device yang sudah ada XAMPP)
1. Start **Apache** + **MySQL** dari XAMPP Control Panel.
2. Pastikan folder app ada di `C:\xampp\htdocs\penatausahaan`.
3. Buka `http://localhost/penatausahaan`.

## Kredensial default (hasil seed)
| Role       | Login (identitas)      | Password  | Keterangan                          |
|------------|------------------------|-----------|-------------------------------------|
| superadmin | `superadmin`           | `admin123`| akses penuh semua OPD               |
| admin_opd  | `197001011990031001`   | `opd123`  | Kepala Dinas Kearsipan & Perpus (OPD 16) |
| user_opd   | `198901292012061001`   | `user123` | Operator, OPD 16 unit Perpustakaan  |

> superadmin login pakai **username**, OPD login pakai **NIP**.
> Ganti password default lewat menu Pengguna setelah dipakai.

## Pindah ke device baru — 2 opsi

**Opsi A (bawa data):** ekspor & impor database `penatus` (mysqldump) + salin folder
`C:\xampp\htdocs\penatausahaan`. Selesai.

**Opsi B (rebuild dari literasi):** butuh database `literasi` ada di MySQL.
1. Salin folder `penatausahaan`.
2. Buka `http://localhost/penatausahaan/setup` → klik **Rebuild penuh**
   (atau CLI: `php index.php setup rebuild` dari folder app).
   Ini menjalankan `docs/master/penatus_schema.sql` + `penatus_import.sql` + seed user.

> Controller `Setup` hanya bisa diakses dari localhost; nonaktifkan di produksi.

## Status komponen (Tahap 1)
| Komponen | File utama | Status |
|----------|-----------|--------|
| Config CI3 | application/config/{database,config,routes,autoload}.php | ✅ |
| Skema DB | docs/master/penatus_schema.sql (30 tabel) | ✅ |
| Import data | docs/master/penatus_import.sql + controllers/Setup.php | ✅ teruji (jumlah baris cocok literasi) |
| Helper | helpers/{format,scope}_helper.php | ✅ |
| Base controller | core/MY_Controller.php | ✅ |
| Auth | controllers/Auth.php, views/auth/login.php | ✅ login NIP/username teruji |
| Dashboard | controllers/Dashboard.php | ✅ |
| Template | views/templates/{layout,_sidebar,_navbar}.php + assets/css/app.css | ✅ |
| Master (12 entitas) | controllers/Master.php, models/Master_model.php, views/master/index.php | ✅ create/edit/delete teruji |
| Anggaran (DPA, Arus Kas) | controllers/Anggaran.php, views/anggaran/viewer.php | ✅ |
| Pengguna | controllers/User.php, views/user/index.php | ✅ |

## Hasil verifikasi
- Import: rekening 15.288, subkegiatan 2.057, program 148, kegiatan 339, OPD 40,
  DPA detail 1.266, arus kas 223/2.676 — **semua cocok** dengan `literasi`.
- Login superadmin → Dashboard menampilkan Total Pagu DPA **Rp 14.625.774.319** (data nyata).
- DataTables server-side skala 15k rekening OK; filter bertingkat OK.
- RBAC/scope: admin_opd hanya lihat data OPD-nya (user 2, unit 3, pegawai 1);
  nomenklatur read-only untuk non-super; POST tak sah → 403.

## Catatan / utang teknis untuk Tahap berikutnya
- Filter bertingkat pada Master masih **1 tingkat** (parent langsung). Tahap 2:
  cascade penuh urusan→bidang→program→kegiatan→subkegiatan.
- Scope DPA untuk user_opd masih se-OPD (belum dibatasi per subkegiatan kewenangan).
- Skema pajak baru **template awal** (5 skema, 11 detail) — perlu disesuaikan aturan final.
- Detail arus kas bulanan (`anggaran_kas_bulanan`) belum ditampilkan (baru pagu tahunan).
- Tabel transaksi (npd, npd_pinbuk, dst.) **sudah dibuat** di skema, siap diisi Tahap 2.
- Folder `_archive_materio_nextjs` = template Next.js (tak dipakai), boleh dihapus.
