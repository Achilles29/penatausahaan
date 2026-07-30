# PROGRESS — Catatan Teknis (aman untuk pindah device)

**Terakhir diperbarui:** 2026-07-30 · **Status:** Tahap 1 SELESAI & teruji (+ peningkatan UI & pajak berbasis rekening). Siap lanjut Tahap 2 (modul NPD).

---

## Log Perkembangan

### [2026-07-30] Tahap 1 — Fondasi + Master + Auth
- Relokasi CI3 ke root `penatausahaan/`, `.htaccess` clean URL, config DB `penatus`.
- Skema DB `penatus` (30 tabel, normalisasi) + import data master/DPA/arus kas dari `literasi`
  (4 tabel pemetaan OPD → 2 junction bersih). Row count cocok 100%.
- Template Materio-Bootstrap (fresh) + layout/sidebar/navbar responsif.
- Auth 3 role: superadmin (username), admin_opd & user_opd (login **NIP**) + scope guard.
- Dashboard per scope. Modul Master 12 entitas (engine generik + DataTables server-side).
- Modul Anggaran (DPA, Arus Kas) + Manajemen Pengguna.

### [2026-07-30] Pajak berbasis rekening (bukan hardcode)
- Kolom `master_rekening.kategori_pajak` + klasifikasi otomatis 15.288 rekening (kode+uraian).
- Skema pajak per kategori (`master_skema_pajak.kategori` == `kategori_pajak`) sebagai DATA.
- Helper `pajak_untuk_rekening()` (fondasi engine pajak Tahap 3). Enum + `PPH4_2`.
- ⚠️ Tarif/ketentuan masih **DRAFT** — dikoreksi via CRUD.

### [2026-07-30] Perbaikan UI kritis
- **JS load order**: jQuery/Bootstrap/DataTables/app.js dipindah ke `<head>` → memperbaiki
  DataTables tak tampil & tombol tambah/edit/hapus mati (akar: script view jalan sebelum jQuery).
- **Ikon**: FontAwesome v5 → **v6** (cocok sintaks `fa-solid`).
- **Logo & favicon**: `assets/img/logo.png` + `favicon.ico` dipakai di sidebar/login/favicon.

### [2026-07-30] Peningkatan UI
- **Sidebar collapse** (default mini/collapsed, hover-expand, submenu toggle; state di localStorage).
- **Skema Pajak** → controller khusus `skema_pajak`: accordion menampilkan **besaran tiap aturan**
  (tarif, batas nilai, syarat NPWP, golongan, basis) + CRUD aturan.
- **Filter bertingkat (cascade)** di program/kegiatan/subkegiatan + DPA + Arus Kas
  (urusan→bidang→program→kegiatan→subkegiatan→rekening). Opsi rekening DPA/Arus Kas data-driven.

> Setelah update UI, lakukan **hard-refresh (Ctrl+Shift+R)** sekali untuk membuang cache lama.

---

## Cara menjalankan (device yang sudah ada XAMPP)
1. Start **Apache** + **MySQL** dari XAMPP Control Panel.
2. Pastikan folder app ada di `C:\xampp\htdocs\penatausahaan`.
3. Buka `http://localhost/penatausahaan` (hard-refresh bila baru pindah/aset berubah).

## Kredensial default (hasil seed)
| Role       | Login (identitas)      | Password  | Keterangan                               |
|------------|------------------------|-----------|------------------------------------------|
| superadmin | `superadmin`           | `admin123`| akses penuh semua OPD                    |
| admin_opd  | `197001011990031001`   | `opd123`  | Kepala Dinas Kearsipan & Perpus (OPD 16) |
| user_opd   | `198901292012061001`   | `user123` | Operator, OPD 16 unit Perpustakaan       |

> superadmin login **username**, OPD login **NIP**. Ganti password via menu Pengguna.

## Pindah ke device baru — 2 opsi
- **A (bawa data):** ekspor+impor database `penatus` (mysqldump) + salin folder `penatausahaan`.
- **B (rebuild dari literasi):** butuh DB `literasi`. Salin folder, lalu buka
  `http://localhost/penatausahaan/setup` → **Rebuild penuh** (atau CLI `php index.php setup rebuild`).
  Menjalankan `docs/master/penatus_schema.sql` + `penatus_import.sql` + seed user.
  > `Setup` hanya bisa diakses dari localhost; nonaktifkan di produksi.

---

## Status komponen
| Komponen | File utama | Status |
|----------|-----------|--------|
| Config CI3 | application/config/{database,config,routes,autoload}.php | ✅ |
| Skema DB (30 tabel) | docs/master/penatus_schema.sql | ✅ |
| Import + klasifikasi pajak | docs/master/penatus_import.sql + controllers/Setup.php | ✅ teruji |
| Helper | helpers/{format,scope,pajak}_helper.php | ✅ |
| Base controller | core/MY_Controller.php | ✅ |
| Auth + Dashboard | controllers/{Auth,Dashboard}.php, views/auth/login.php | ✅ |
| Template + aset | views/templates/*, assets/{css/app.css,js/app.js,vendor,img} | ✅ |
| Master (12 entitas) | controllers/Master.php, models/Master_model.php, views/master/index.php | ✅ CRUD teruji |
| Cascade filter | assets/js/app.js `initCascadeFilters` | ✅ |
| Anggaran (DPA, Arus Kas) | controllers/Anggaran.php, views/anggaran/viewer.php | ✅ |
| Skema Pajak (header+aturan) | controllers/Skema_pajak.php, views/skema_pajak/index.php | ✅ |
| Pengguna | controllers/User.php, views/user/index.php | ✅ |

## Hasil verifikasi
- Import row-count cocok `literasi` (rekening 15.288, subkeg 2.057, DPA detail 1.266, arus kas 223/2.676).
- Login superadmin → Total Pagu DPA **Rp 14.625.774.319** (data nyata). 16 halaman render 200 tanpa error.
- RBAC/scope: admin_opd hanya data OPD-nya (user 2, unit 3, pegawai 1); nomenklatur read-only non-super; POST tak sah → 403.
- Cascade: kegiatan urusan 2→194/urusan 1→79; DPA filter subkeg 1266→142; rekening DPA 77→17 (dipersempit subkeg).
- Klasifikasi pajak: honorarium 14, barang 837, jasa 590, jasa_boga 2, makan_minum 8, sewa 793, konstruksi 31, modal 978, non_pajak 10.413.

## Utang teknis / berikutnya
- ⚠️ **Tarif pajak masih DRAFT** — koreksi via menu Skema Pajak + kolom Kategori Pajak di menu Rekening.
- Scope DPA untuk user_opd masih se-OPD (belum per subkegiatan kewenangan).
- Detail arus kas **bulanan** (`anggaran_kas_bulanan`) belum ditampilkan (baru pagu tahunan).
- **Data anggaran hanya OPD 16**; 39 OPD lain baru "cangkang". OPD lain terisi saat DPA-nya diimpor / dipetakan manual.
- Tabel transaksi (npd, npd_pinbuk, dst.) sudah ada di skema, siap diisi **Tahap 2 (modul NPD)**.
- Folder `_archive_materio_nextjs` (template Next.js) tak dipakai, boleh dihapus.

---
Lihat **ROADMAP.md** (tahapan), **DECISIONS.md** (keputusan arsitektur), **DB_SCHEMA.md** (struktur DB).
