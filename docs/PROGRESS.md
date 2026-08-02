# PROGRESS — Catatan Teknis (aman untuk pindah device)

**Terakhir diperbarui:** 2026-08-03 · **Status:** Modul Gaji ASN SELESAI & teruji. Siap lanjut Tahap 2 (modul NPD).

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
| NPD | controllers/Npd.php | ⏳ Belum dimulai |

## Utang teknis / berikutnya
- ⚠️ **Tarif pajak Tahap 3 masih DRAFT** — koreksi via menu Skema Pajak.
- **Ref gaji pokok**: 271 baris dari PP 15/2019 × 1,08 — perlu diverifikasi terhadap PP 5/2024 riil.
- **Data anggaran hanya OPD 16**; 39 OPD lain baru "cangkang".
- Detail arus kas bulanan belum ditampilkan (baru pagu tahunan).
- Scope DPA untuk user_opd masih se-OPD (belum per subkegiatan kewenangan).
- Tabel transaksi (npd, npd_pinbuk, dst.) sudah ada di skema, siap diisi **Tahap 2 (modul NPD)**.

---
Lihat **ROADMAP.md** (tahapan), **DECISIONS.md** (keputusan arsitektur), **DB_SCHEMA.md** (struktur DB).
