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
      (Tarif masih draft — dikoreksi via CRUD.)

## Tahap 2 — Modul NPD (Nota Pencairan Dana)
- [ ] Viewer sisa anggaran DPA (pagu − realisasi) per subkegiatan/rekening.
- [ ] Buat NPD: pilih program/kegiatan/subkegiatan/pekerjaan/rekening dari sisa DPA,
      isi pagu sesuai kewenangan bidang OPD.
- [ ] NPD detail per rekening + daftar penerima.
- [ ] Pemetaan filter bertingkat penuh (urusan→bidang→program→kegiatan→subkegiatan).

## Tahap 3 — Pindah Buku + Engine Pajak
- [ ] Pindah buku (pinbuk) dari NPD detail.
- [ ] Engine penghitungan pajak memakai `pajak_untuk_rekening()` (fondasi Tahap 1):
      rekening → kategori → skema → hitung PPh21/22/23/4(2), PPN, PDRD sesuai NPWP/golongan.
- [ ] Finalisasi tarif/ketentuan draft agar sesuai regulasi.
- [ ] Rincian pajak & netto per penerima.

## Tahap 4 — Cetak
- [ ] Cetak NPD.
- [ ] Cetak Pindah Buku.
- [ ] Cetak C5.

## Tahap 5 — SPJ & Laporan
- [ ] Kelengkapan SPJ.
- [ ] Laporan realisasi anggaran.

---

Lihat **PROGRESS.md** untuk status teknis rinci, kredensial, dan cara menjalankan/rebuild.
Lihat **DECISIONS.md** untuk keputusan arsitektur, **DB_SCHEMA.md** untuk struktur database.
