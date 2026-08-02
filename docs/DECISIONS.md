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

## 7. Pajak ditentukan REKENING, bukan hardcode/input manual
Temuan: di literasi penentuan jenis pajak (`$kategori`) **dipilih manual per transaksi**,
tidak diturunkan dari kode rekening (dan tabel skema pajaknya kosong). Ini rawan salah
(mis. jasa boga dikenai PPN padahal dikecualikan). Keputusan untuk `penatus`:
- **Klasifikasi rekening**: kolom `master_rekening.kategori_pajak` diisi otomatis dari
  prefix kode (5.1.01/5.1.02.01/5.1.02.02/5.2) + kata kunci uraian (Honorarium, Makan-Minum,
  Jasa Boga, Sewa, Konstruksi, Perjalanan Dinas). Hanya akun belanja `5%`. Bisa dikoreksi
  manual via CRUD rekening (kolom + filter tersedia).
- **Aturan pajak sebagai DATA**: `master_skema_pajak.kategori` == `master_rekening.kategori_pajak`.
  Satu skema per kategori, detail aturan (tarif/threshold/NPWP/golongan/basis PPN) di
  `master_skema_pajak_detail`. Tarif saat ini **draft** — wajib dikoreksi sesuai regulasi.
- **Lookup** via `helpers/pajak_helper.php::pajak_untuk_rekening($id)` → kategori + skema + aturan.
  Jadi fondasi engine penghitungan pajak di Tahap 3 (pinbuk), tanpa hardcode.
- Enum `jenis_pajak` ditambah `PPH4_2` (final: sewa tanah/bangunan, jasa konstruksi).

## 8. PPh21 Gaji menggunakan metode TER, bukan progressive tahunan
Sebelumnya sistem menghitung PPh21 dengan metode lama (proyeksi penghasilan setahun → tarif
progressive). Referensi data pegawai (file Excel Dinarpus) menunjukkan metode TER (Tarif Efektif
Rata-Rata) sesuai PMK 168/2023 / PP 58/2023 yang berlaku efektif Januari 2024.
- Kategori A/B/C ditentukan dari PTKP (status pernikahan + tanggungan).
- **Perempuan kawin**: diperlakukan sebagai K/0 → Kategori A (suami klaim tanggungan di tempatnya).
- Bruto TER = komponen gaji saja (tanpa TPP).
- Implementasi: `_hitung_pph21()` + `_ter_rate()` di `controllers/Gaji.php`.

## 9. Rekening PPh21 & BPJS dari TPP tetap 5.1.01.01.xxx, bukan 5.1.01.02.xxx
TPP menggunakan rekening `5.1.01.02.001`, tetapi pajak dan BPJS yang timbul dari TPP
dikategorikan sebagai **Belanja Gaji dan Tunjangan** (`5.1.01.01`), bukan Tambahan Penghasilan:
- PPh21 TPP (DTP) → `5.1.01.01.007`
- BPJS Kes TPP (employer 4% DTP & pegawai 1%) → `5.1.01.01.009`
Alasan: secara BAS (Bagan Akun Standar), PPh21 dan iuran BPJS adalah komponen gaji/tunjangan
yang dicatat di rekening gaji, terlepas dari mana penghasilan dasarnya berasal.

## 10. BPJS TPP 1% pegawai = tanggungan pegawai, bukan beban negara
BPJS 1% dari TPP (iuran pegawai) dipotong dari TPP yang diterima pegawai. Ini TIDAK dihitung
sebagai beban negara/anggaran. Berbeda dengan BPJS 4% employer (DTP = ditanggung pemerintah).
Model anggaran belanja gaji = PPh21 DTP + BPJS 4% gaji + JKK + JKM + PPh21 TPP DTP + BPJS 4% TPP.

## 11. TPP dalam slip gaji: model gross-up DTP (keluar-masuk)
Untuk ketepatan pencatatan keuangan (rekening `5.1.01.01.007` dan `5.1.01.01.009`), item DTP
dicatat sebagai penghasilan yang diterima lalu disetor kembali (keluar-masuk). Ini mengikuti
pola yang sama dengan komponen gaji DTP:
- PPh21 TPP DTP: tampil sebagai +penerimaan, lalu −setoran ke kas negara (netto 0).
- BPJS 4% employer TPP: tampil sebagai +penerimaan, lalu −setoran ke BPJS (netto 0).
- BPJS 1% pegawai TPP: tampil sebagai −potongan nyata dari THP pegawai (bukan DTP).

## 12. Pembulatan gaji: ceil (atas), bukan round (terdekat)
Tunjangan pembulatan harus selalu ≥ 0 (tidak pernah memotong gaji). Formula:
`sisa = bersih_gaji_kasar % 100; pembulatan = sisa > 0 ? (100 - sisa) : 0`.
Penggunaan `round()` sebelumnya menyebabkan ~50% pegawai tidak dapat pembulatan
(kasus bersih berakhiran 01–49 dibulatkan ke bawah).
