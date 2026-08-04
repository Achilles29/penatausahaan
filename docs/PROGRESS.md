# PROGRESS — Catatan Teknis (aman untuk pindah device)

**Terakhir diperbarui:** 2026-08-04 · **Status:** Penerima auto-sync ke master_penerima + cetak pejabat dari data + kolom jabatan pegawai digabung. Teruji. (Manajemen sidebar sys_menu = ditunda.)

---

## Log Perkembangan

### [2026-08-04] Penerima→master, cetak pejabat variabel, kolom jabatan
- **Penerima auto-sync ke `master_penerima`** (`Npd::ensure_penerima`): saat simpan npd_penerima
  (batch & single), penerima dipastikan ada di master lalu `penerima_id` diisi. Dari **pegawai** →
  dedup by `pegawai_id` (buat baru dgn npwp/golongan/bank dari data pegawai bila belum ada); **manual**
  → dedup by nama (pegawai_id NULL). Tidak ada penerima ganda. Teruji: buat+dedup OK.
- **Cetak /npd/cetak/* variabel (tanpa hardcode)** — pejabat ditarik dari **data pegawai**
  (`jabatan_penatausahaan_id`→`ref_jabatan`, dicocokkan by NAMA jabatan agar tahan seed) via
  `Npd::pejabat_of($opd,$unit)`: **PPTK** (PELAKSANA TEKNIS, utamakan unit NPD; jabatan "PPTK Bidang
  <unit> <singkatan OPD>"), **PPK** (PENATAUSAHAAN KEUANGAN), **Bendahara** (BENDAHARA PENGELUARAN).
  Surat NPD: Nama/NIP/Jabatan = PPTK; addressee = Bendahara OPD; 3 ttd = PPK/Bendahara/PPTK dari data.
  Pindah Buku ttd = PPTK dari data. Teruji NPD 9 (PPTK Anwar Fuadi, PPK Adi Bagus, Bend. Fiscalita).
- **/master/pegawai**: kolom "Jab. Struktural"+"Jab. Fungsional" digabung jadi **satu kolom "Jabatan"**
  yang **menumpuk** semua jabatan aktif (Struktural / Keuangan / Fungsional) dengan badge jenis —
  renderer `jabatan_multi` di `views/master/index.php` (render kini menerima full row).

### [2026-08-04] Cetak NPD & Pindah Buku (template + unduh PDF/Excel/Word)
- **Cetak NPD** (`views/npd/cetak_npd.php`) — format **surat resmi** sesuai `docs/master/npd_bertutur_dak.xlsx`:
  kop surat (pemda + OPD + alamat + logo), Nomor/Lampiran/Perihal, blok "Kepada Yth Bendahara
  Pengeluaran", identitas **PPTK** (dari `master_opd_unit.kepala` via `npd.opd_unit_id`),
  Program/Kegiatan/SubKeg/Pekerjaan/Sumber Dana, kalimat "Mohon menyiapkan dana … (terbilang)",
  rincian rekening, **3 tanda tangan** (PPK SKPD, Bendahara, PPTK).
- **Cetak Pindah Buku** (`cetak_pindahbuku.php`) — tabel **DAFTAR PEMINDAHBUKUAN** sesuai sheet Pinbuk:
  No · Nama Penerima (+NIP) · Nomor Rekening Bank · Jumlah (netto) · Keterangan Belanja · JUMLAH,
  no rekening dari `pegawai_rekening`(primary)/`master_penerima`; rekap setoran pajak bila ada.
- **Unduh 3 format** dari toolbar tiap dokumen: **Cetak/Simpan PDF** (window.print), **Excel**
  (`?format=excel`, mime `application/vnd.ms-excel`, `.xls`), **Word** (`?format=word`,
  `application/msword`, `.doc`, header MSO). Tanpa dependensi (grid `border="1"` agar Excel bergaris).
- **Kop surat** dikonfigurasi di `application/config/instansi.php` (pemda, alamat, kontak, website,
  kota, logo, pejabat penanda tangan) — mudah disesuaikan per daerah.
- **/npd index**: tombol per-baris kini + dropdown **Cetak** (NPD / Pindah Buku / C5). C5 ikut
  dapat unduh Excel/Word gratis via engine yang sama. Teruji NPD 9 (7 penerima), semua format 200 OK.

### [2026-08-04] NPD: nomor & modal penerima
- **Nomor NPD** format `900 / 0001 / 06 / 2026` (0001=urut per OPD/tahun, 06=bulan dari tanggal,
  2026=tahun) — `Npd_model::next_nomor($opd,$tahun,$bulan)`; regenerasi saat tanggal berubah.
- **Modal Tambah Penerima → multi-baris** (`npd/view`): cari pegawai/penerima → klik = langsung
  jadi baris; tombol "Baris manual"; harga satuan default = **sisa yang dicairkan** (editable,
  karena penerima bisa >1); total & sisa live; tombol Simpan disabled bila melebihi pagu.
- Endpoint baru `Npd::penerima_batch()` (`insert_batch`, validasi Σ ≤ jumlah baris). Edit satu
  penerima pindah ke modal terpisah `#penEditModal` (`penerima_save` tetap). Teruji: 2 penerima
  masuk, guard over-limit menolak baris ke-3.

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
- **JS load order**: jQuery/Bootstrap/DataTables/app.js dipindah ke `<head>`.
- **Ikon**: FontAwesome v5 → **v6** (cocok sintaks `fa-solid`).
- **Logo & favicon**: `assets/img/logo.png` + `favicon.ico`.

### [2026-07-30] Peningkatan UI
- **Sidebar collapse** (default mini/collapsed, hover-expand, submenu toggle; state di localStorage).
- **Skema Pajak** → controller khusus `skema_pajak`: accordion besaran tiap aturan + CRUD aturan.
- **Filter bertingkat (cascade)** di program/kegiatan/subkegiatan + DPA + Arus Kas.

> Setelah update UI, lakukan **hard-refresh (Ctrl+Shift+R)** sekali untuk membuang cache lama.

---

### [2026-08-01 – 2026-08-03] Modul Gaji ASN — SELESAI

#### Data & Referensi
- 6 tabel master gaji baru: `ref_gaji_pokok` (271 baris PP5/2024), `ref_tunjangan_jabatan`,
  `ref_kelas_jabatan`, `ref_harga_beras`, `ref_iuran_gaji`, `ref_tpp`.
- Tabel `pegawai` diperluas: golongan, masa kerja, status pernikahan, jumlah anak, jenis kelamin,
  jenis kepegawaian (PNS/PPPK/CPNS/NON_ASN), persen gaji, tmt_cpns/pns/pensiun.
- **ref_tpp**: TPP per jabatan (FK → ref_jabatan_id), dari Perbup Rembang 2024.
  Fallback → `ref_kelas_jabatan.tpp` jika belum diisi.
- **T.Pangan**: jiwa × `harga_per_jiwa` (Rp 7.242/jiwa/bln, bukan per-kg).
- **DB fix**: LILIK SRI DARYATI (pegawai.id=12) jabatan_struktural_id 106→105 (eselon 3A→3B).

#### PPh21 — Metode TER (PMK 168/2023 / PP 58/2023, berlaku 2024)
- **Bukan progressive tahunan** — pakai TER (Tarif Efektif Rata-Rata) per bulan.
- Bruto TER = gapok + t_istri + t_anak + t_jabatan + t_khusus + t_pangan (**tanpa TPP**).
- Kategori TER:
  - **A** (PTKP ≤ 58,5 jt): TK/0, TK/1, K/0, **perempuan kawin** (suami klaim tanggungan)
  - **B** (PTKP 63–67,5 jt): K/1, K/2, perempuan punya 2+ anak
  - **C** (PTKP 72 jt): K/3
- PPh21 TPP: **flat** 5% (Gol I-III) / 15% (Gol IV) — PP 80/2010, **DTP** (negara bayar).
- Fungsi: `_hitung_pph21()` + `_ter_rate()` di `controllers/Gaji.php`.

#### Aturan BPJS & Pembulatan
| Item | Pegawai | Negara (DTP) | Basis |
|------|---------|-------------|-------|
| BPJS Kes gaji | 1% | 4% | GP + T.Keluarga + T.Jabatan (capped 12 jt) |
| BPJS Kes TPP | 1% (dipotong dr TPP) | 4% | Nominal TPP |
| JKK | — | 0,24% | Gaji Pokok |
| JKM | — | 0,72% (PNS Taspen) / 0,30% (PPPK) | Gaji Pokok |
| Pensiun/JHT | 4,75% (PNS) / 2% JHT + 1% JP (PPPK) | — | GP + T.Keluarga |

- **Pembulatan**: `ceil` ke kelipatan Rp 100 (selalu bulatkan ke atas, tidak pernah memotong).
- **Net TPP**: TPP − 1% BPJS pegawai (pajak DTP, tidak mengurangi penerimaan pegawai).
- **BPJS TPP 1% pegawai**: tanggungan pegawai, **TIDAK dihitung sebagai beban negara/anggaran**.

#### Rekening yang Benar untuk Komponen TPP
| Rekening | Untuk |
|----------|-------|
| `5.1.01.02.001` | TPP nominal saja |
| `5.1.01.01.007` | PPh21 TPP (DTP) — **bukan** 5.1.01.02.001 |
| `5.1.01.01.009` | BPJS TPP (employer 4% DTP & pegawai 1%) — **bukan** 5.1.01.02.001 |

#### Model Beban Negara (Anggaran)
```
Beban negara per pegawai =
  PPh21 gaji (DTP)     [5.1.01.01.007]
+ BPJS 4% gaji (DTP)   [5.1.01.01.009]
+ JKK (DTP)            [5.1.01.01.010]
+ JKM (DTP)            [5.1.01.01.011]
+ PPh21 TPP (DTP)      [5.1.01.01.007]
+ BPJS 4% TPP (DTP)    [5.1.01.01.009]
```
TIDAK termasuk: BPJS 1% pegawai dari gaji & TPP (tanggungan pegawai).

#### File yang Dimodifikasi
| File | Perubahan |
|------|-----------|
| `controllers/Gaji.php` | TER PPh21, TPP rules, pembulatan ceil, initial $total lengkap |
| `controllers/Rekap.php` | `_totals_from()`, `_zero_totals()`, `detail_months` — bersih_gaji/tpp_bersih benar |
| `views/gaji/rekap.php` | Summary boxes, rekDef rekening benar, totalBelanjaPemda, per-pegawai loop |
| `views/gaji/simulasi.php` | TPP slip gross-up (PPh21+BPJS DTP keluar-masuk, BPJS 1% deduction real) |
| `views/rekap/index.php` | Kolom BPJS TPP 1%, header "BPJS TPP (1%)", JS cell mapping |
| `views/rekap/detail.php` | BPJS TPP pegawai (5.1.01.01.009), Pajak TPP DTP (5.1.01.01.007) |

---

### [2026-08-03] Tahap 2a — Modul NPD (inti) — SELESAI
Alur sinkron DPA→NPD: pilih sub kegiatan (yang ber-DPA) → tabel rekening tampil
**pagu / terpakai / sisa** → isi jumlah cair (validasi ≤ sisa).
- **Sisa anggaran** = pagu (Σ dpa_detail.total_harga) − realisasi (Σ npd_detail.jumlah),
  per (opd, subkegiatan, rekening). `Npd_model` (rekening_sisa, pagu_map, realisasi_map).
- **Nomor NPD** auto (`900.1.11/<urut>/NPD/<singkatan>/<romawi bln>/<tahun>`), bisa diedit.
- **Scope**: superadmin pilih OPD; admin_opd → OPD-nya; user_opd → subkegiatan kewenangan
  (`scope_subkegiatan_ids()`). Validasi sisa & otorisasi di server (authoritative).
- File: `controllers/Npd.php`, `models/Npd_model.php`, `views/npd/{index,form,view}.php`,
  menu sidebar "Penatausahaan → NPD".
- **Teruji (superadmin)**: buat (sisa berkurang tepat), edit (exclude-self), over-limit
  ditolak, hapus cascade. Data uji sudah dibersihkan (tabel npd kosong).
- Catatan: uji login user_opd tertunda krn password seed berubah di sesi sebelumnya
  (bukan bug NPD; scope memakai helper yang sama & sudah terbukti).

### [2026-08-03] Tahap 2b — Daftar Penerima per baris NPD — SELESAI
- Tabel baru **`npd_penerima`** (npd_detail_id, penerima_id, nama, uraian, volume,
  harga_satuan, jumlah). Ditambahkan ke `penatus_schema.sql`.
- Dikelola di **halaman detail NPD** (`npd/view`): tiap baris rekening bisa punya daftar
  penerima; `jumlah = volume × harga_satuan`; **validasi Σ penerima ≤ jumlah baris**
  (indikator "sisa alokasi"). Autocomplete cari `master_penerima` (`npd/penerima_search`).
- Method: `penerima_search/get/save/delete` di `Npd.php`; view `npd/view.php` ditulis ulang.
- **Teruji**: tambah (auto 5×300rb=1,5jt), over-alokasi ditolak, render, cascade delete.

### [2026-08-03] Bug fix
- **Pencarian pegawai di modal Penerima tidak keluar** → `master/pegawai_search` dibajak
  route catch-all `master/([a-z_]+)`. Fix: daftarkan `master/pegawai_search` **sebelum**
  catch-all di `routes.php`. (Berlaku juga untuk endpoint metode 1-segmen lain di masa depan.)
- **NPD gagal simpan bila `pekerjaan` kosong** → kolom NOT NULL. Fix: coalesce
  perihal/pekerjaan ke string di `Npd::save()` (+ guard `trim`/`str_replace` dari null utk PHP 8).
- **terbilang() warning float→int (PHP 8.1)** → `floor()` input di `format_helper`.

### [2026-08-03] Penerima ⇄ Pegawai (data live)
- Kolom **`npd_penerima.pegawai_id`** (FK pegawai, ON DELETE SET NULL). Bila penerima adalah
  pegawai, disimpan pegawai_id → nama/NIP/NPWP/golongan diambil **live** (perubahan data
  pegawai otomatis terpantul di NPD). Autocomplete gabungan **pegawai + master_penerima**
  (`Npd::penerima_search`, tag `source`). **Propagasi terbukti** (ubah nama pegawai → view berubah).

### [2026-08-03] Tahap 2c — Pindah Buku + Pajak Otomatis — SELESAI
- **Engine pajak** `hitung_pajak_rekening($rekening_id, $bruto, $ctx)` di `pajak_helper.php`:
  kategori rekening → skema → aturan; menghitung PPh21/22/23/PPh4(2)/PPN sesuai **NPWP &
  golongan**; urutan PPN dulu agar DPP PPh benar. `+ label_jenis_pajak()`, `golongan_roman()`.
- NPD `view` menampilkan **Bruto / Pajak (rincian) / Netto** per penerima + ringkasan pajak NPD.
- **Teruji**: barang bruto 2jt (NPWP) → PPN 198.198 + PPh22 27.027 = pajak 225.225, netto
  1.774.775; honorarium PNS Gol III/d 1jt → PPh21 5% = 50.000, netto 950.000.

### [2026-08-03] Tahap 2d — Cetak — SELESAI
- Cetak **NPD**, **Pindah Buku** (per penerima: bruto/pajak/netto + rekap pajak disetor),
  **C5** (daftar penerimaan/tanda terima). Layout cetak sendiri (`_print_head.php`, A4, tombol
  cetak). Diakses dari tombol di halaman detail NPD (`npd/cetak|pindah_buku|c5/<id>`).
- **Menu/role**: cetak = aksi kontekstual per NPD (bukan menu baru), ikut guard scope NPD.
  Menu **NPD** sudah ada di sidebar (semua role, ter-scope). Tidak ada menu top-level baru.

---

### [2026-08-03] Role Matrix + penerima⇄pegawai + tampilan OPD
- **master_penerima.pegawai_id** (FK, ON DELETE SET NULL): bila penerima adalah pegawai,
  nama/NPWP/golongan diambil **live** (COALESCE pegawai). Prefill di modal Penerima menautkan
  `pegawai_id`; ketik nama manual = lepas tautan. **Propagasi terbukti**.
- **Tampilan OPD** diseragamkan ke **kode + nama** (bukan singkatan) di dropdown Master/NPD/
  Anggaran/User/Gaji. Pedoman: selalu tampilkan OPD sebagai `kode_opd - nama_opd`.
- **Role Matrix / Manajemen Menu** (BARU):
  - Tabel `role_menu` (override) + katalog menu di `helpers/menu_helper.php`
    (`menu_allowed`, `current_menu_key`, `menu_group_visible`). Default = perilaku bawaan.
  - Sidebar kini **data-driven** (visibilitas per role). superadmin selalu penuh.
  - **Enforcement** di `MY_Controller` (403 bila menu ditolak; endpoint utility dikecualikan,
    fail-open). Menu **Pengaturan → Hak Akses Menu** (`controllers/Akses.php`, superadmin):
    matrix menu × role dengan centang + Reset Default.
  - **Teruji**: user_opd (npd ditolak) → npd **403**, dashboard/rekening 200 (tak over-block),
    NPD hilang dari sidebar; simpan/reset override OK.
- ⚠️ **`docs/master/penatus_schema.sql` sudah DIVERGEN dari DB live** (sesi-sesi lain meng-ALTER
  tanpa update file: enum jenis_penerima, banyak kolom pegawai, ref_* gaji, dll). Untuk jalur
  **rebuild** yang akurat, **dump ulang** skema live: `mysqldump -u root --no-data penatus`.

### [2026-08-03] RBAC v2 (izin CRUD, pola pustaka) + fix NPD + penerima⇄pegawai
Referensi pola: `C:\xampp\htdocs\pustaka` (sys_page + auth_role_permission CRUD + sys_menu).

**1. Fix cascade NPD (bug: program/turunan tak sesuai OPD).**
- Form NPD kini **OPD → Program → Kegiatan → Sub Kegiatan**, semua opsi **bersumber DPA OPD
  terpilih** (endpoint `npd/program_options|kegiatan_options|subkegiatan_options` +
  `Npd_model::dpa_programs/dpa_kegiatan/dpa_subkegiatan`). Teruji: OPD16 → 2 prog/3 keg/5 subkeg,
  cascade menyaring benar; create tersimpan (program/keg/subkeg konsisten).

**2. RBAC v2 — izin CRUD (ganti role_menu on/off).**
- Tabel **`role_permission`** (role, page_key, can_view/create/edit/delete) + katalog di
  `helpers/menu_helper.php` (`can($action,$key)`, `can_view/create/edit/delete`, fallback default).
- Enforcement: `MY_Controller` blok VIEW; **engine Master** cek granular create/edit/delete
  (save→create/edit sesuai id, delete→delete) + `scope_ok` (admin_opd hanya baris OPD-nya).
- Tombol CRUD di list Master di-gate per izin (Tambah=create, Edit/Hapus per izin).
- **UI Hak Akses** (`controllers/Akses.php`, superadmin): matrix halaman × (Lihat/Tambah/Edit/Hapus)
  per role admin_opd & user_opd + Reset Default. `role_menu` dibuang.
- **Teruji** (user_opd default): urusan view-only (no Tambah, save/delete→403); penerima full
  CRUD (Tambah ada); user page→403.

**3. Scope OPD & bidang.**
- Non-super sudah ter-scope ke OPD-nya (scope_helper) di seluruh modul.
- **`users.akses_semua_bidang`** (flag di form Pengguna, untuk user_opd): 1=CRUD **semua bidang**
  OPD, 0=hanya bidang unit-nya (+ user_akses). Diterapkan di `scope_bidang_urusan_ids()` + session login.

**4. Tampilan OPD** = `kode - nama` (bukan singkatan) di dropdown. **Sidebar** dirapikan:
  "Anggaran & Penatausahaan" digabung (DPA/Arus Kas/NPD).

**5. master_penerima.pegawai_id** — bila penerima adalah pegawai, nama/NPWP/golongan **live**
  (COALESCE pegawai). Prefill di modal Penerima menautkan; ketik manual = lepas. **Propagasi terbukti**.

**DITUNDA (didokumentasikan):** *Manajemen Sidebar penuh ala pustaka* (tabel `sys_menu`:
reorder drag, ikon, rename, nesting, visibilitas per item). Saat ini visibilitas menu per role
sudah diatur via **Hak Akses** (matrix), dan sidebar sudah data-driven per izin. Untuk kelola
susunan/ikon/urutan menu dari UI, tinggal adopsi `sys_menu` (lihat
`pustaka/sql/2026-07-28a_auth_rbac_sidebar_foundation.sql` + `models/Menu_model.php`).

### [2026-08-04] Tagging NPD DPA-scoped + scope program non-super
- **Filter index NPD** (Program/Kegiatan/Sub Kegiatan) kini **bersumber DPA OPD**, bukan master
  global (dulu 148 program). Endpoint `npd/flt_program|flt_kegiatan|flt_subkegiatan`
  (super: dari OPD filter; non-super: OPD-nya). Form NPD sudah DPA-scoped sebelumnya.
- **`master/options` (bidang/program/kegiatan/subkegiatan) di-scope untuk NON-SUPER** ke
  bidang-urusan OPD-nya (`source_options` di Master.php). Berlaku di **semua** cascade
  (Master, Anggaran, NPD) → admin_opd/user_opd hanya lihat program urusan OPD-nya.
  **Teruji**: user_opd OPD16 → program 6 (2.23.x + 2.24.x), bidang 2; super tetap 148.
- Bug fix: `scope_*_ids()` dipanggil SEBELUM membangun query builder (agar tak merusak state).
- Catatan: data DPA OPD16 sudah benar (program 2.23.02/2.23.03, 300/300 baris konsisten);
  screenshot "2.16 Kominfo" adalah state lama/cache sebelum fix.

### [2026-08-04] Form NPD ikut struktur DPA: Pekerjaan → Sumber Dana → Rekening
Alur form kini: OPD → Program → Kegiatan → Sub Kegiatan → **Pekerjaan (paket_belanja)** →
**Sumber Dana** (dari paket) → **Rincian Rekening** (sisa per paket+sumber dana).
- **Perihal** jadi **select Pekerjaan** (daftar paket_belanja DPA sub kegiatan), bukan free text.
- **Sumber Dana** dependent pada pekerjaan (dari DPA paket tsb).
- **Rincian Rekening** hanya rekening milik (subkeg+paket+sumber dana) terpilih.
- **Grain sisa** dipersempit ke (opd, subkeg, paket, sumber_dana, rekening) — realisasi
  dicocokkan `npd.perihal` + `sumber_dana_id`. Endpoint: `npd/pekerjaan_options`,
  `npd/sumber_dana_options`, `npd/rekening_sisa` (update). Model: `dpa_pekerjaan`,
  `dpa_sumber_dana`, `rekening_sisa/pagu_map/realisasi_map` (grain baru).
- **Teruji**: subkeg 1606 → 4 paket → sumber dana per paket → 8 rekening (cocok DB); create
  mengurangi sisa paket tsb (4,648jt→3,648jt), paket lain tak terpengaruh.
- Catatan: `pekerjaan` (textarea) kini "Catatan/Keterangan" opsional.

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
  > `Setup` hanya bisa diakses dari localhost; nonaktifkan di produksi.

---

## Status komponen
| Komponen | File utama | Status |
|----------|-----------|--------|
| Config CI3 | application/config/{database,config,routes,autoload}.php | ✅ |
| Skema DB | docs/master/penatus_schema.sql | ✅ |
| Import + klasifikasi pajak | docs/master/penatus_import.sql + controllers/Setup.php | ✅ |
| Helper | helpers/{format,scope,pajak}_helper.php | ✅ |
| Base controller | core/MY_Controller.php | ✅ |
| Auth + Dashboard | controllers/{Auth,Dashboard}.php | ✅ |
| Template + aset | views/templates/*, assets/ | ✅ |
| Master (13 entitas) | controllers/Master.php + views/master/index.php | ✅ CRUD |
| Cascade filter | assets/js/app.js `initCascadeFilters` | ✅ |
| Anggaran (DPA, Arus Kas) | controllers/Anggaran.php | ✅ |
| Skema Pajak | controllers/Skema_pajak.php | ✅ |
| Pengguna | controllers/User.php | ✅ |
| **Gaji — Kalkulasi** | controllers/Gaji.php (`_hitung_gaji`, TER, BPJS, TPP) | ✅ |
| **Gaji — Rekap per OPD** | views/gaji/rekap.php | ✅ |
| **Gaji — Simulasi per Pegawai** | views/gaji/simulasi.php | ✅ |
| **Gaji — Rekap per Pegawai** | controllers/Rekap.php + views/rekap/{index,detail}.php | ✅ |
| **NPD inti (2a)** | controllers/Npd.php, models/Npd_model.php, views/npd/{index,form,view}.php | ✅ CRUD + validasi sisa teruji |
| **NPD penerima (2b) + tautan pegawai** | tabel npd_penerima(+pegawai_id), Npd::penerima_* | ✅ data live teruji |
| **NPD pajak otomatis (2c)** | pajak_helper::hitung_pajak_rekening | ✅ PPh/PPN teruji |
| **NPD cetak (2d)** | Npd::{cetak,pindah_buku,c5}, views/npd/cetak_* | ✅ NPD/pindah buku/C5 |

## Utang teknis / berikutnya
- ⚠️ **Tarif pajak Tahap 3 masih DRAFT** — koreksi via menu Skema Pajak.
- **Ref gaji pokok**: 271 baris dari PP 15/2019 × 1,08 — perlu diverifikasi terhadap PP 5/2024 riil.
- **Data anggaran hanya OPD 16**; 39 OPD lain baru "cangkang".
- Detail arus kas bulanan belum ditampilkan (baru pagu tahunan).
- Scope DPA untuk user_opd masih se-OPD (belum per subkegiatan kewenangan).
- **NPD 2a–2d SELESAI** (modul NPD lengkap: inti, penerima+pegawai, pajak, cetak).
- ⚠️ **Tarif pajak masih DRAFT** — koreksi via menu Skema Pajak agar sesuai regulasi final.
- **Format C5** memakai interpretasi "daftar penerimaan/tanda terima" — sesuaikan bila daerah
  Anda punya template C5 resmi yang berbeda (kirim contohnya).
- Pajak honorarium: PNS/CPNS diperlakukan final per golongan; PPPK/NON_ASN sebagai non-PNS
  (tarif Ps.17) — verifikasi bila perlu.
- Tabel `npd_pinbuk*` lama belum dipakai (pindah buku dihitung live dari npd_penerima); bisa
  dipakai nanti bila perlu persist nomor/status pindah buku.
- Lanjutan modul berikutnya: **kelengkapan SPJ** & **laporan realisasi** (Tahap 5 roadmap).

---
Lihat **ROADMAP.md** (tahapan), **DECISIONS.md** (keputusan arsitektur), **DB_SCHEMA.md** (struktur DB).
