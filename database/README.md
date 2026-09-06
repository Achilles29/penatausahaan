# Database delivery

`schema/20260906_baseline.sql` adalah baseline struktur database live pada versi
`0.2.0-test`. Berkas ini hanya berisi DDL 41 tabel dan tidak memuat data pelanggan.
Versi target paket saat ini adalah `20260906170000`; migration pertama menambah tabel teknis
history dan metadata instalasi tanpa mengubah tabel proses bisnis.

Aturan rilis:

- instalasi baru mengimpor baseline ke database kosong menggunakan akun deployment;
- aplikasi runtime memakai akun DML least-privilege dan tidak boleh menjalankan DDL;
- perubahan setelah baseline harus berupa migration berurutan di `migrations/`;
- jalankan `tools/migrate.php`; upgrade/rollback selalu membuat backup terenkripsi baru,
  memverifikasi checksum history, memakai advisory lock, dan menjalankan post-condition;
- migration dan restore selalu diuji pada database disposable sebelum rollout;
- dump data, credential, dan backup tidak boleh ditempatkan di direktori ini.
