# Roadmap Perbaikan dan Komersialisasi Namua Penatausahaan

**Tanggal audit awal:** 6 September 2026  
**Aplikasi:** Namua Penatausahaan  
**Domain produksi:** `https://efin.namuaprojects.com`  
**Batas pekerjaan:** aplikasi `penatausahaan` dan integrasinya dengan Control Center; tidak
menyentuh kode maupun database Finance.

## 1. Ringkasan hasil scan cepat

Namua Penatausahaan adalah aplikasi penatausahaan keuangan pemerintah daerah yang menghubungkan:

1. perencanaan/penganggaran berupa DPA dan anggaran kas;
2. pelaksanaan berupa NPD, daftar penerima, pindah buku, dan perhitungan pajak;
3. penggajian ASN beserta PPh 21, BPJS, TPP, gaji ke-13/14, dan rekap;
4. pertanggungjawaban berupa cetak NPD/C5/pindah buku dan Laporan Realisasi Anggaran;
5. pengaturan pengguna, scope OPD/unit, dan hak akses per tindakan.

Stack saat ini adalah CodeIgniter 3.1.13, PHP 8.1, MySQL, Bootstrap 5/Materio, jQuery,
dan DataTables. Baseline bisnis memiliki 41 tabel; instance staging kini juga memiliki dua tabel
teknis untuk history migration dan metadata instalasi. Data live yang terbaca saat
audit terdiri dari 40 OPD, 182 pegawai, 1 DPA dengan 300 detail, 1 NPD dengan 2 detail dan
7 penerima. Tabel pindah buku tersedia tetapi belum berisi transaksi persisted.

### Modul yang sudah tersedia

- autentikasi username/NIP dan role `superadmin`, `admin_opd`, `user_opd`;
- master nomenklatur, OPD/unit, pegawai, penerima, rekening, sumber dana, serta pajak;
- DPA, arus kas, dan realisasi;
- NPD beserta rincian dan penerima;
- engine perhitungan pajak berbasis rekening;
- cetak NPD, pindah buku, dan C5;
- simulasi serta rekap gaji ASN;
- matrix izin view/create/edit/delete dan scope per OPD/unit.

### Masalah yang ditemukan saat audit

| Prioritas | Temuan | Dampak/status |
|---|---|---|
| Kritis | Rewrite Nginx kosong sehingga route CI3 seperti `/auth/login` menjadi 404 | Selesai diperbaiki |
| Kritis | Dump SQL dan folder internal berada di webroot dan sempat dapat diakses publik | Dump dipindah ke arsip private; path internal diblok Nginx dan dikeluarkan dari artifact |
| Kritis | Endpoint rebuild database menganggap koneksi Tunnel dari `127.0.0.1` sebagai localhost | Sudah dijadikan CLI-only dan diblok Nginx |
| Tinggi | Aplikasi memakai mode `development` di produksi dan pernah menampilkan stack trace | Selesai diubah ke `production` |
| Tinggi | Direktori sesi belum ada/tidak writable untuk proses PHP | Selesai dibuat dengan owner dan izin terbatas |
| Tinggi | Credential database masih berada di file config dan koneksi memakai akun berhak tinggi | Selesai untuk aplikasi: secret eksternal dan user runtime least-privilege; rotasi root server bersama tetap terpisah |
| Tinggi | CSRF masih nonaktif dan belum ada throttling login/lockout/MFA | CSRF, throttling, lockout, dan pencabutan sesi selesai; MFA belum |
| Tinggi | Seeder lama memakai password mudah ditebak dan dokumentasi menyimpannya | Seeder dan dokumentasi sudah dibersihkan; akun live tetap wajib diaudit/dirotasi |
| Tinggi | Skema SQL terdokumentasi tidak sama dengan database live; belum ada migration runner | Selesai: baseline 41 tabel, runner ber-checksum/lock/backup guard, dan upgrade/rollback disposable |
| Sedang | Belum ada test suite otomatis yang memadai | Preflight, security scan, HTTP smoke, authenticated smoke, backup verification, clean install, dan restore test tersedia |
| Sedang | Identitas, TPP, dasar hukum, dan contoh data masih spesifik pelanggan lama | Identitas tenant dan branding produk sudah dipisah; rule pack regulasi masih tahap lanjutan |
| Sedang | Workflow status NPD, persist pindah buku, BKU, dan kelengkapan SPJ belum selesai | Belum |
| Sedang | Ekspor `.xls`/`.doc` masih berupa HTML ber-MIME Office, bukan generator dokumen native | Perlu ditingkatkan |

## 2. Keputusan produk dan skema penjualan

### 2.1 Model instalasi awal

Gunakan **satu instalasi dan satu database terisolasi per pelanggan** pada fase awal. Jangan
langsung membuat satu database multi-tenant. Data keuangan daerah bersifat sensitif dan isolasi
per pelanggan membuat backup, restore, retensi, pemutakhiran, serta penanganan insiden lebih aman.

Control Center menjadi control plane untuk mencatat customer, instance, domain, versi, edition,
entitlement, status backup, dan heartbeat. Control Center tidak boleh membaca isi transaksi
keuangan pelanggan. Integrasi pertama bersifat **push heartbeat read-only** dari aplikasi.

### 2.2 Identitas produk yang disarankan

- Product code: `NAMUA_PENATAUSAHAAN`
- Nama publik: **Namua Penatausahaan**
- Instance ID: unik per instalasi, dibuat oleh Control Center
- Environment: `DEMO`, `STAGING`, atau `PRODUCTION`
- Versi aplikasi: SemVer dan dicatat di manifest rilis

### 2.3 Draft edition dan entitlement

| Edition | Sasaran | Fitur utama |
|---|---|---|
| Essential | Satu OPD/pilot | Master, DPA, anggaran kas, NPD, penerima, cetak dasar, LRA |
| Professional | Banyak OPD dalam satu pemda | Essential + approval workflow, pindah buku persisted, pajak, SPJ, BKU, audit lengkap |
| Enterprise Managed | Pemda dengan kebutuhan integrasi/SLA | Professional + SSO, integrasi, managed backup, staging, SLA, dan dukungan prioritas |

Penggajian ASN disarankan sebagai **add-on** tersendiri (`PAYROLL_ASN`) karena aturan TPP,
referensi gaji, dan kebijakan daerah membutuhkan validasi serta pemutakhiran regulasi yang lebih
ketat daripada modul NPD.

Contoh feature key untuk registry Control:

- `BUDGET_DPA`, `CASH_PLAN`, `NPD_CORE`, `NPD_APPROVAL`;
- `TRANSFER_BOOK`, `TAX_ENGINE`, `SPJ_DOCUMENTS`, `BKU_REGISTER`, `LRA_REPORT`;
- `PAYROLL_ASN`, `SSO`, `AUDIT_EXPORT`, `MANAGED_BACKUP`, `API_INTEGRATION`.

Lisensi tidak boleh mengunci akses ke data pelanggan secara mendadak. Ketika lisensi berakhir,
gunakan masa tenggang lalu mode baca/ekspor; operasi administratif berisiko dapat dibatasi tanpa
menghilangkan hak pelanggan untuk mengambil datanya.

## 3. Roadmap pelaksanaan

### P0 — Pulihkan produksi dan tutup risiko kritis

**Target:** aplikasi dapat dibuka tanpa 404/stack trace dan permukaan serangan mendesak tertutup.

- [x] Tambahkan front-controller rewrite Nginx untuk CI3.
- [x] Ubah default environment menjadi `production`.
- [x] Tetapkan canonical base URL; dukung override aman melalui `APP_URL`.
- [x] Buat session directory writable untuk user PHP.
- [x] Aktifkan cookie `Secure`, `HttpOnly`, dan `SameSite=Lax`.
- [x] Blok `application`, `system`, `docs`, dump SQL, backup, archive, dan file internal lain.
- [x] Ubah Setup/rebuild menjadi CLI-only; blok endpoint web termasuk bentuk `index.php`.
- [x] Hapus password default dari seeder dan dokumentasi.
- [x] Matikan TLS 1.1/3DES, session ticket, dan TLS early data; tambah header keamanan dasar.
- [ ] Rotasi seluruh password akun hasil seed dan audit akun aktif database live.
- [x] Buat user MySQL khusus aplikasi dengan hak `SELECT/INSERT/UPDATE/DELETE` saja dan simpan secret di luar webroot.
- [ ] Rotasi credential root aaPanel/server bersama setelah dependensi seluruh aplikasi lain diaudit.
- [x] Pindahkan dump legacy dan backup keluar dari webroot; exclude dokumentasi/archive dari artifact.
- [x] Enkripsi backup, verifikasi kriptografis, dan lakukan uji restore streaming pada database disposable.

**Gerbang P0:** login dan seluruh route utama tidak 404, tidak ada stack trace publik, endpoint Setup
dan file internal selalu 404, tidak ada akun/credential bawaan, serta restore backup terbukti.

### P1 — Stabilkan fondasi aplikasi

**Target:** instalasi dapat dibangun dan diuji ulang secara konsisten.

- [x] Buat konfigurasi berbasis environment/runtime eksternal untuk URL, DB, environment, tenant, backup, dan instance metadata.
- [x] Ganti penggunaan credential langsung dalam repository dengan secret di luar webroot.
- [x] Aktifkan CSRF dan sesuaikan seluruh form/AJAX; tambah rate limit dan lockout login.
- [x] Tambahkan change password, forced change untuk password lama, kebijakan password, dan session revoke berbasis fingerprint hash.
- [x] Susun migration versioned dari kondisi database live 41 tabel.
- [ ] Buat seed demo terpisah dari master regulasi dan data pelanggan.
- [ ] Hapus ketergantungan rebuild terhadap database `literasi`.
- [x] Tambahkan structured log JSONL, correlation/request ID pada respons termasuk error aplikasi,
      serta retensi otomatis 30 hari.
- [ ] Tambahkan integrasi pelaporan exception/error ke kanal operasi sebelum pilot data nyata.
- [ ] Lengkapi test master, DPA, NPD, cetak, dan laporan; smoke auth, CSRF, session revoke, dan scope lintas OPD sudah lulus.
- [x] Buat `app-manifest.json`, baseline schema, release manifest, dan checksum artifact.

**Gerbang P1:** clean install dari migration berhasil pada database kosong dan smoke test lulus tanpa
menggunakan data maupun credential pelanggan.

### P2 — Benahi integritas transaksi penatausahaan

**Target:** alur transaksi dapat dipertanggungjawabkan dan aman dari race condition.

- [ ] Finalkan state machine NPD: draft → diajukan → diverifikasi → disetujui → dibayar/ditolak/batal.
- [ ] Pisahkan permission maker, checker, approver, bendahara, dan auditor.
- [ ] Gunakan transaksi database dan row locking untuk nomor dokumen, sisa anggaran, dan pembayaran.
- [ ] Tambahkan idempotency untuk submit/approval agar klik ganda tidak menggandakan transaksi.
- [ ] Persist pindah buku, rincian, pajak, status, nomor, dan jejak persetujuannya.
- [ ] Simpan snapshot nama/rekening/NPWP/tarif pada dokumen final tanpa kehilangan link ke master.
- [ ] Versikan rule pack pajak berdasarkan masa berlaku; tarif harus mendapat sign-off ahli pajak.
- [ ] Tambahkan reversal/koreksi resmi tanpa menghapus histori transaksi final.
- [ ] Lengkapi audit log before/after untuk perubahan keuangan dan ekspor audit.

**Gerbang P2:** transaksi yang disetujui immutable, tidak dapat melebihi pagu, dapat ditelusuri sampai
aktor dan rule version, serta lolos skenario retry/concurrent submit.

### P3 — Selesaikan SPJ dan pelaporan

**Target:** siklus DPA sampai pertanggungjawaban tersedia utuh.

- [ ] Checklist SPJ per jenis belanja: kwitansi, SPTB, daftar penerima, bukti bayar, dan bukti pajak.
- [ ] Upload dokumen ke private storage, antivirus scan, checksum, ukuran/tipe file, dan access control.
- [ ] Buku Kas Umum, buku pembantu pajak, register NPD/pindah buku, serta rekonsiliasi periodik.
- [ ] Dashboard outstanding approval, kekurangan SPJ, jatuh tempo pajak, dan selisih rekonsiliasi.
- [ ] Tingkatkan output menjadi PDF dan XLSX native dengan nomor/QR verifikasi dokumen.
- [ ] Tambahkan period lock dan proses tutup bulan/tahun.

**Gerbang P3:** satu transaksi dapat ditelusuri dari DPA sampai bayar, pajak, SPJ, BKU, dan LRA dengan
angka yang direkonsiliasi.

### P4 — Jadikan aplikasi configurable per pelanggan

**Target:** tidak ada fork kode khusus satu daerah.

- [x] Pindahkan identitas pemda, logo, alamat, kontak, kota, dan pejabat dasar ke tenant config eksternal.
- [ ] Ubah label/data khusus Rembang menjadi rule pack atau dataset milik customer.
- [ ] Versioning nomenklatur per tahun anggaran dan dukung import/mapping dari sumber resmi.
- [ ] Buat wizard onboarding: profil instansi, OPD/unit, pejabat, tahun, rekening, pajak, dan template.
- [ ] Gunakan feature flag yang dipetakan ke entitlement edition.
- [ ] Sediakan template dokumen per customer tanpa mengubah controller inti.
- [ ] Tambahkan locale/timezone/currency dan kebijakan tahun fiskal pada konfigurasi.
- [ ] Pisahkan add-on Payroll ASN dari domain transaksi NPD.

**Gerbang P4:** customer demo baru dapat disiapkan melalui konfigurasi/import tanpa mengedit source.

### P5 — Integrasi sebagai produk pertama di Control Center

**Target:** Penatausahaan terdaftar dan terpantau tanpa memberi Control akses ke data transaksi.

- [x] Daftarkan product, edition, feature, customer demo, dan instance Penatausahaan di Control.
- [x] Provision `instance_id` dan secret HMAC unik untuk setiap instalasi.
- [x] Implement heartbeat tiap 5 menit dengan timeout offline 15 menit.
- [x] Payload minimum: version, environment, app/db health, migration version, queue/storage status,
      backup freshness, disk usage, dan timestamp; tanpa PII maupun nilai transaksi.
- [x] Tambahkan endpoint lokal health yang tidak mengungkap detail sensitif.
- [x] Terapkan signed release manifest Ed25519, checksum artifact, identitas release aktif, serta
      receipt aktivasi/rollback bertanda HMAC yang tersimpan di Control.
- [ ] Tambahkan approval dan eksekusi deployment dari Control serta tautkan receipt ke deployment
      yang disetujui; receipt out-of-band saat ini sengaja tidak dianggap sebagai approval.
- [ ] Tambahkan offline-tolerant entitlement cache dan audit perubahan lisensi.

**Gerbang P5:** heartbeat valid tampil `HEALTHY` di Control, replay/signature salah ditolak, dan tidak ada
data keuangan atau identitas pegawai pada payload.

### P6 — Packaging, QA, dan keamanan rilis

**Target:** paket customer repeatable dan aman dipasang/di-upgrade.

- [x] Builder artifact hanya memasukkan source/vendor/assets/schema/tool deployment yang diperlukan.
- [x] Exclude `.git`, docs internal, dump, cache, log, backup, credential, logo tenant, dan data pelanggan.
- [x] Preflight PHP extension, DB version, permission, domain/TLS, Control config, tenant config, dan backup.
- [ ] Jalankan unit/integration/E2E test untuk tiap edition dan role.
- [ ] Dependency/SAST/secret scan dan review OWASP untuk auth, upload, IDOR, XSS, CSRF, serta SQLi.
- [x] Uji migration upgrade/rollback; clean schema install dan restore database disposable sudah lulus.
- [ ] Lengkapi admin guide, data retention, dan incident runbook; install/operator guide dasar sudah tersedia.

**Gerbang P6:** instalasi baru, upgrade, rollback, dan restore berhasil dari artifact yang sama dan tercatat
di Control Center.

### P7 — Pilot dan komersialisasi

**Target:** validasi produk serta proses layanan sebelum penjualan luas.

- [ ] Demo internal dengan data sintetis.
- [ ] Pilot satu OPD, lalu pilot lintas OPD setelah UAT dan persetujuan regulasi.
- [ ] Tetapkan SLA, jam dukungan, onboarding, migrasi data, pelatihan, dan exit/export procedure.
- [ ] Susun kontrak mengenai kepemilikan data, lokasi backup, retensi, DPA/privacy, dan tanggung jawab.
- [ ] Ukur keberhasilan: waktu proses NPD, error/rework, kelengkapan SPJ, uptime, dan ticket resolution.
- [ ] Baru aktifkan edition/license komersial setelah P0–P6 lulus.

## 4. Urutan kerja yang direkomendasikan

1. Tuntaskan sisa P0 tanpa menyentuh Finance.
2. Kerjakan P1 agar perubahan berikutnya memiliki migration, test, dan deployment yang repeatable.
3. Prioritaskan NPD/pindah buku/pajak di P2; jadikan Payroll ASN add-on setelah validasi regulasi.
4. Selesaikan SPJ/BKU di P3 agar nilai bisnis produk utuh.
5. Productize di P4, lalu gunakan Penatausahaan sebagai instance produk pertama untuk P5 Control.
6. Lakukan packaging dan pilot; lisensi berbayar tidak didahulukan sebelum jalur upgrade/restore aman.

## 5. Definition of sell-ready

Aplikasi baru dinyatakan siap dijual ketika seluruh kondisi berikut terpenuhi:

- tidak ada credential/default account/data customer di source atau artifact;
- clean install, upgrade, rollback aplikasi, dan restore database teruji;
- RBAC/scope/audit/workflow transaksi lulus UAT;
- aturan pajak dan payroll memiliki sumber, masa berlaku, version, dan sign-off domain expert;
- data antar-customer terisolasi dan backup terenkripsi;
- konfigurasi customer tidak membutuhkan fork source;
- heartbeat, release, instance, edition, dan entitlement tercatat di Control Center;
- lisensi gagal/expired tidak menghilangkan akses customer untuk membaca dan mengekspor data;
- dokumen operasional, SLA, support, incident response, serta exit procedure tersedia.

## 6. Langkah berikutnya yang paling aman

Batch berikutnya adalah menutup sisa fondasi tanpa mengubah proses bisnis: audit/rotasi akun live,
perluasan test master/DPA/NPD/cetak/laporan, entitlement cache, approval deployment dari Control,
pelaporan error operasional, dan panduan insiden. Perubahan workflow transaksi P2–P4 menunggu
keputusan serta UAT proses bisnis dari pemilik produk.

## 7. Status implementasi 6 September 2026

Status saat ini adalah **siap untuk test jual internal dengan data sintetis**, bukan siap dijual atau siap
produksi pelanggan. Versi teknis `0.3.5-test` mencakup instalasi tanpa akun bawaan, provisioning admin
pertama via CLI, secret eksternal, database least-privilege, CSRF, login throttling, pencabutan sesi,
isolasi scope OPD, backup terenkripsi, clean-install/restore test, migration versioned dengan rollback,
structured log dengan request ID, signed release Ed25519, receipt aktivasi/rollback, serta heartbeat
Control yang melaporkan schema aktual.

Gerbang yang masih terbuka sebelum penjualan nyata adalah rotasi akun live dan root server bersama,
approval/eksekusi deployment dari Control, entitlement offline, audit transaksi menyeluruh, penyelesaian
P2–P4, UAT lintas role, validasi regulasi/pajak/payroll, serta dokumen operasi/insiden lengkap. Rincian
bukti teknis ada di `docs/2026-09-06_product_readiness_control_integration.md`.
