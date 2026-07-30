# DECISIONS — Keputusan Arsitektur

## 1. Stack: CI3 + Materio Bootstrap (bukan Next.js)
Template Materio yang ter-download di `penatausahaan/materio` adalah versi **Next.js/React**
yang tidak cocok dengan CI3 (view server-rendered). Diputuskan pakai **Materio versi
Bootstrap**, diintegrasikan **fresh** ke CI3. Aset (Bootstrap 5, DataTables) di-vendor lokal;
FontAwesome & jQuery disalin dari distribusi bersih; template layer CI3 ditulis ulang.
Folder Next.js diarsipkan (`_archive_materio_nextjs`).

## 2. Skema DB dinormalisasi (bukan salinan literasi apa adanya)
`literasi` punya **4 tabel pemetaan** OPD↔urusan/bidang yang tumpang tindih
(`map_opd_bidang_urusan`, `map_opd_bidang_to_bidang_urusan`, `master_opd_bidang_urusan`,
`master_opd_urusan_bidang`) — sumber kebingungan/bug. Di `penatus` disederhanakan jadi **2
junction bersih**:
- `opd_bidang_urusan` — OPD menjalankan bidang-urusan apa (1 OPD bisa banyak).
- `opd_unit_bidang_urusan` — unit internal OPD menangani bidang-urusan apa.
Selain itu `master_opd_bidang` di-rename jadi `master_opd_unit` (lebih jelas: unit internal
OPD = sekretariat + bidang). Data master lain diimpor apa adanya (struktur sama).

## 3. Auth: 3 role, OPD login via NIP
- `superadmin` — login **username**, akses penuh.
- `admin_opd` (kepala/operator OPD) — login **NIP**, scope 1 OPD.
- `user_opd` — login **NIP**, scope OPD + unit + tag di `user_akses`.
Satu kolom "identitas" di form login menerima NIP maupun username.
Scope diturunkan lewat junction (helper `scope_helper.php`), menggantikan view
`v_user_scope_*` literasi dengan logika berbasis tabel bersih.

## 4. Engine Master generik (config-driven)
12 entitas master memakai **satu** controller `Master` + `Master_model` + view
`master/index`, digerakkan array registry per-entitas (kolom, field form, filter, hak kelola).
DataTables **server-side** untuk semua entitas agar skala ke tabel besar (rekening 15k).
Menghindari duplikasi 12× CRUD. Anggaran & User memakai ulang engine DataTables yang sama.

## 5. Data awal diimpor dari `literasi`, transaksi tidak
Nomenklatur, DPA, arus kas, OPD, pegawai, ref bank/jabatan diimpor penuh.
Data transaksi uji literasi (npd 1–2 baris) **tidak** diimpor. `master_skema_pajak` yang
kosong di literasi **di-seed** template standar (dapat diedit via CRUD).

## 6. Repeatability lewat controller `Setup`
Agar aman pindah device, `Setup` bisa rebuild penuh (skema + import + seed) via browser/CLI,
menjalankan file SQL di `docs/master/`. Hanya localhost.
