# DB_SCHEMA — Database `penatus`

DDL lengkap: `docs/master/penatus_schema.sql` · Import: `docs/master/penatus_import.sql`
Charset: utf8mb4. Total 30 tabel.

## Nomenklatur (hierarki)
```
master_urusan (9)
  └─ master_bidang (32)            [= "bidang urusan", FK urusan_id]
       └─ master_program (148)     [FK bidang_id]
            └─ master_kegiatan (339)      [FK program_id]
                 └─ master_subkegiatan (2.057)  [FK kegiatan_id]
master_rekening (15.288)           [flat; hierarki via kode 1..6]
master_sumber_dana (850)
```

## Organisasi & pemetaan
```
master_opd (40)                    [SKPD]
master_opd_unit (3)                [unit internal OPD: sekretariat + bidang; FK opd_id]

opd_bidang_urusan (2)              [OPD ⇄ bidang-urusan; is_dominant]
opd_unit_bidang_urusan (4)         [unit OPD ⇄ bidang-urusan]
```
> Junction inilah yang menentukan **scope**: bidang-urusan → program → kegiatan →
> subkegiatan yang boleh diakses user.

## Anggaran (impor dari SIPD via literasi)
```
dpa (1)                            [header; FK opd_id]
  └─ dpa_detail (1.266)            [baris DPA + FK nomenklatur + koefisien/harga/total]
anggaran_kas (223)                 [pagu tahunan per subkeg/rekening]
  └─ anggaran_kas_bulanan (2.676)  [rincian per bulan]
```
> `dpa_detail.total_harga` = pagu; sisa anggaran = pagu − realisasi NPD (Tahap 2).
> Kolom nomenklatur di `dpa_detail` diindeks (bukan FK) agar toleran data raw SIPD.

## Pegawai, penerima, pajak
```
pegawai (1)  ── pegawai_jabatan, pegawai_rekening ── ref_bank (2), ref_jabatan (5)
master_penerima (2)                [orang/badan, npwp, golongan, bank]
master_skema_pajak (12) ─ master_skema_pajak_detail (18)  [PPh21/22/23, PPh4(2), PPN, PDRD]
```

### Pajak berbasis rekening (bukan hardcode)
```
master_rekening.kategori_pajak  ──(sama dengan)──►  master_skema_pajak.kategori
   (honorarium/barang/jasa/jasa_boga/makan_minum/sewa/                │
    konstruksi/modal/perjalanan_dinas/pegawai/non_pajak/lainnya)      ▼
                                              master_skema_pajak_detail (aturan tarif/NPWP/dll)
```
- `kategori_pajak` diisi otomatis (prefix kode + kata kunci uraian, hanya akun `5%`),
  dapat dikoreksi via CRUD rekening.
- Lookup aturan: `pajak_untuk_rekening($rekening_id)` di `helpers/pajak_helper.php`.
- Tarif/ketentuan saat ini **DRAFT** — koreksi via menu Skema Pajak sesuai regulasi.

## Transaksi (skema siap, diisi Tahap 2+)
```
npd ─ npd_detail ─ npd_pinbuk ─ npd_pinbuk_rincian
                              └─ npd_pinbuk_pajak
```

## User & hak akses
```
users        [nip? / username? / password(bcrypt) / role / opd_id / opd_unit_id / pegawai_id]
             role ∈ {superadmin, admin_opd, user_opd}
user_akses   [tag scope granular opsional: user ⇄ opd_unit / bidang-urusan]
```

## Aturan scope (diimplementasi di `helpers/scope_helper.php`)
- **superadmin** → semua.
- **admin_opd** → data OPD-nya (`opd_bidang_urusan` OPD tsb).
- **user_opd** → OPD + unit (`opd_unit_bidang_urusan`) + tag `user_akses`.
