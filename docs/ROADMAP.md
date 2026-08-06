# ROADMAP — Aplikasi Penatausahaan

Aplikasi penatausahaan keuangan daerah yang sinkron: perencanaan/penganggaran
(DPA + arus kas SIPD) → penatausahaan (NPD, pindah buku, pajak) → pertanggungjawaban (SPJ).

Stack: **CodeIgniter 3** + **Bootstrap 5 (tema Materio)** + MySQL (`penatus`).
URL lokal: `http://localhost/penatausahaan`

---

## Tahap 1 — Fondasi + Master + Auth  ✅ SELESAI

- [x] Setup CI3 (relokasi ke root, config DB `penatus`, .htaccess clean URL).
- [x] Skema DB `penatus` (normalisasi) + import data master/DPA/arus kas dari `literasi`.
- [x] Aset Materio-Bootstrap (fresh) + template layout/sidebar/navbar responsif.
- [x] Auth 3 role: superadmin (username), admin_opd & user_opd (login **NIP**).
- [x] Hak akses (scope) per OPD/unit/bidang-urusan + guard `MY_Controller`.
- [x] Dashboard ringkas per scope.
- [x] Modul Master (engine CRUD generik + DataTables server-side + filter):
      urusan, bidang, program, kegiatan, subkegiatan, rekening, sumber dana,
      OPD, unit OPD, pegawai, penerima, skema pajak.
- [x] Modul Anggaran (viewer): DPA, Arus Kas.
- [x] Modul Pengguna (CRUD user, aturan role & OPD).
- [x] Fondasi pajak berbasis rekening: klasifikasi `kategori_pajak` otomatis +
      skema pajak per kategori (data, bukan hardcode) + `pajak_untuk_rekening()`.

## Modul Gaji ASN  ✅ SELESAI (2026-08-03)

- [x] 6 tabel referensi gaji: ref_gaji_pokok, ref_tunjangan_jabatan, ref_kelas_jabatan,
      ref_harga_beras, ref_iuran_gaji, ref_tpp.
- [x] Kalkulasi gaji lengkap: gapok, t.keluarga, t.jabatan, t.pangan, t.khusus, t.pembulatan, TPP.
- [x] PPh21 metode **TER** (PMK 168/2023) — bukan progressive tahunan.
- [x] PPh21 TPP: flat 5%/15% DTP (PP 80/2010).
- [x] BPJS: gaji (1% peg + 4% negara) + TPP (1% peg dipotong + 4% negara DTP).
- [x] JKK (0,24%), JKM (0,72% PNS Taspen / 0,30% PPPK), Pensiun/JHT/JP.
- [x] Pembulatan `ceil` ke kelipatan Rp 100.
- [x] Gaji Ke-13 dan Ke-14 (via `ref_gaji_ke`).
- [x] Proyeksi KP, KGB, BUP per pegawai.
- [x] Slip simulasi per pegawai (gross-up DTP keluar-masuk).
- [x] Rekap gaji per OPD/bulan (2 tab: per rekening + per pegawai).
- [x] Rekap per pegawai — akumulasi range bulan + detail per bulan.
- [x] Rekening komponen TPP yang benar: PPh21 → 5.1.01.01.007, BPJS → 5.1.01.01.009.

## Tahap 2 — Modul NPD (Nota Pencairan Dana)  ✅ SELESAI
- [x] Buat NPD dari sisa DPA (pagu − realisasi), cascade OPD→Program→Kegiatan→SubKegiatan
      dari DPA, validasi jumlah ≤ sisa, nomor auto.
- [x] NPD detail per rekening + daftar penerima (tertaut pegawai/master_penerima, data live).
- [x] Filter index NPD DPA-scoped + scope program non-super ke urusan OPD-nya.

## Tahap 3 — Pindah Buku + Engine Pajak  ✅ SELESAI
- [x] Engine pajak `hitung_pajak_rekening()`: rekening→kategori→skema→PPh21/22/23/4(2)/PPN
      sesuai NPWP/golongan. Tampil bruto/pajak/netto per penerima + ringkasan.
- [ ] ⚠️ Finalisasi tarif/ketentuan **draft** agar sesuai regulasi (data — via menu Skema Pajak).
- [ ] Persist pindah buku (nomor/status) — kini dihitung live dari npd_penerima.

## Tahap 4 — Cetak  ✅ SELESAI
- [x] Cetak NPD (surat resmi + kop + PPTK + 3 ttd, sesuai template `npd_bertutur_dak.xlsx`).
- [x] Cetak Pindah Buku (DAFTAR PEMINDAHBUKUAN: nama, no rek bank, jumlah, keterangan + rekap pajak).
- [x] Cetak C5 (tanda terima).
- [x] **Unduh PDF (print), Excel (.xls), Word (.doc)** tiap dokumen — tanpa dependensi. Dropdown Cetak di /npd.
- [x] Kop surat via `config/instansi.php` (pemda, alamat, logo, pejabat) — disesuaikan per daerah.
- [ ] Sesuaikan **format C5** bila daerah punya template resmi berbeda.

## RBAC v2 (Hak Akses CRUD, pola pustaka)  ✅ SELESAI (2026-08-04)
- [x] `role_permission` (view/create/edit/delete per halaman/role) + UI matrix Hak Akses.
- [x] Enforcement view + CRUD granular di engine Master + gate tombol.
- [x] Scope OPD non-super + flag `akses_semua_bidang` untuk user_opd.

## Tahap 5 — SPJ & Laporan  ⏳ BERJALAN
- [x] **Laporan Realisasi Anggaran (LRA)** (`anggaran/realisasi`): pohon Pagu (DPA) vs Realisasi
      (NPD **final/dibayar**) vs Sisa per OPD→program→kegiatan→subkeg→rekening, gaya pohon DPA.
- [ ] **Kelengkapan SPJ**: dokumen pertanggungjawaban (kwitansi, SPTB, daftar penerima, bukti pajak).
- [ ] **Buku Kas Umum / register**: rekap pencairan & pajak per periode.
- [ ] NPD status workflow (draft → diajukan → disetujui → dibayar) bila diperlukan.

## Utang teknis
- [ ] **Manajemen Sidebar penuh** (`sys_menu` ala pustaka: drag-reorder, ikon, rename).
- [ ] **Re-dump `penatus_schema.sql`** (sudah divergen dari DB live) agar jalur rebuild akurat.

---

Lihat **PROGRESS.md** untuk status teknis rinci, kredensial, dan cara menjalankan/rebuild.
Lihat **DECISIONS.md** untuk keputusan arsitektur, **DB_SCHEMA.md** untuk struktur database.
