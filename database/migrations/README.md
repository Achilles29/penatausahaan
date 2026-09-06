# Versioned migrations

Tambahkan perubahan skema setelah baseline dengan nama `YYYYMMDDHHMMSS_deskripsi.sql`.
Migration tidak boleh berisi data pelanggan atau credential. Setiap migration harus memiliki
preflight, backup requirement, verifikasi sesudah eksekusi, dan catatan rollback aplikasi.
