Saya ingin membuat aplikasi penatausahaan yang sinkron mulai dari perencanaan penganggaran , sampai dengan pertanggungjawaban.
Data awal yang saya gunakan adalah data raw dpa yang diambil dari sipd, beserta arus anggaran kas nya.

ketentuan struktur aplikasi dimulai dari:
- user, (superadmin, admin opd (login dengan NIP, biasanya kepala opd), user opd (login dengan nip))
- hak akses masing masing user yang bisa di taging kan, dan sesuai dengan urusan , bidang dan opd masing masing
- nomenklatur master sesuai dengan peraturan perundang undangan, mulai dari urusan ,bidang, opd ( 1 opd bisa lebih dari 1 urusan ataupun 1 bidang), bidan opd ( beberapa bidang opd bisa mengambu 1 bidang urusan), program , kegiatan, sub kegiatan, kode rekening mulai dari reking 1 sampai dengan rekening 6,

- opd terdiri dari sekretariat dan beberapa bidang
- data master penerima bisa Asn (pns dan pppk) dan non asn
- pola pajak sesuai ketentuan


- proses penatausahaan sinkron mengambil data npd dari sisa anggaran yang tersedia di dpa, user tinggal klik dan memilih program /kegiatan /sub kegiatan /pekerjaan / rekening dan mengisi pagu sesuai dengan bidang opd kewenangan masing masing. mengisi data yang diperlukan sesuai dengan rekening yang dicairkan, misal daftar nama penerima dalam npd, lalu besaran pajak sesuai ketentuan


- standar halaman dengan tambilan form crud terpisah dari halaman index, bisa dengan modal atau halaman terpisah jika memang data banyak. halaman index dengan filter pencairan , pagination, filter baris (standar 25 - semua), filter filter data bertingkat sesuai dengan data

- fitur cetak npd, cetak pindah buku, cetak c5, dan kedepan cetak kelengkapan spj

- catat roadmap dan progres sehingga terukur dan aman ketika pindah device untuk coding

SEBELUMNYA SAYA SUDAH BUAT di folder C:\xampp\htdocs\literasi tapi masih banyak bug dan saya memilih membuat aplikasi baru.

- untuk data awal yang dibutuh kan seperti yang jelaskan diatas kamu bisa import dari database tabel literasi

- database yang kita gunakan disini adalah penatus


- di folder sudah ada master CI3 dan template materio tapi masih bersih, sesuaikan agar siap dipakai



==================

- masih banyak bug, icon belum terlihat, collapse expand belum berfugsi
- dashboard superadmin seharusnya tampul seluruh opd
- dashboard admin opd dan user opd tampil sesuai kewanangannya

- saya lihat kamu sudah import database dari literasi, apakah langsung kamu ambil buta, atau kamu sesuaikan pemetaan yang masih tidak sesuai? lalu penetapan ketentuan pajaknya. karena di literasi database pajak hardcode tidak berdasar ketentuan rekening


===
- icon belum muncul
- logo dan ico ada di assets, pindahkan dan gunakan sesuai ketentuan

- banyak funsgi tambah, edit , hapus yang belum berfungsi. perbaiki semua
- data banyak yang belum tampil, padahal saya login superadmin

=================

- sidebar belum bisa expand collapse. default buat collapse
- semua halaman mempunyai filter bertingkat mulai dari urusan , bidang urusan , opd, sampai sub keghiatan dan rekening sesuai dengan data yang tampil
- skema pajak tampilkan besarna pajaknya masing masing, karena pajak yang sama bisa beda besarannya tergantun nilai pembayaran dan penerima punya npwp atau tidak
==============

urutan tampilan data urut kode terkecil, urusan, bidang , opd dan seterusnya.



mari kita lakukan beberapa penyesuaian dulu untuk user, pegawai dan penerima:

user selain superadmin adalah pegawai , jadi login menggunakan NIP
penerima bisa dari pegawai, jadi kita pisahkan antara pegawai dan penerima. penerima ini adalah penerima pembayaran, sementara pegawai adalah asn , pns dan pppk, yang bisa mempunyai jabatan. sementara penerima berguna untuk pencairan NPD, kalau asn maka pajaknya beda dengan non asn
lakukan pengecekan dan penyesuaian untuk semua halaman terdampak.

tampilan /user urut sesuai id


/master/pemetaan nont found 404



/anggaran/dpa seharusnya tampilannya bukan data mentah begitu, tapi dimulai dari opd, program kegiatan terus kebawah, yang bisa di collapse dan expand. buat tampilannya menarik dan user friendly



- /anggaran/dpa formasi tampilan sudah oke, tapi data yang ditampilkan sepertinya salah, terlalu besar pagu totalnya. entah salah baca, data di tabel database salah, atau salah menarik data dari literasi? coba cek ulang. 
- /anggaran/dpa sumber dana belum ada.  master sumber dana sudah ada tapi di dpa belum terbaca 
- /anggaran/dpa buat tampilan beberapa tab, tab pertama seperti sekarang tinggal tambahkan sumber dana, tab kedua setelah sub kegiatan aktifitas dulu, lalu sumber dana baru rekening, tab ketiga sumber dana dulu baru program. atau cukup 1 tab, tapi sortir datanya bisa dipilih manual (seperti pada pivot). dan untuk tampilan kurang menarik, percantik lagi

/master/pegawai kurang lengkap, belum ada pangkat , golongan, jabatan, eselon (jabatan struktural dan fungsional, mulai dari kepala, sekretaris, kepala bidang , kepala seksi, pelaksana dan seterusnya), jika diperlukan tambahkan master jabatan, ini nanti bisa digunakan untuk taging hak akses
/master/penerima ketik nama ajax belum berhasil menemukan pegawai , lakukan penyesuaian terkait poin 1



Tahap 2 — NPD (Nota Pencairan Dana)
Ini yang paling logis dikerjakan berikutnya. Urutannya:

2a — Tabel DB


npd           → header (OPD, bidang, nomor, tanggal, status)
npd_detail    → per rekening (subkegiatan_id, rekening_id, nilai_pencairan)
npd_penerima  → daftar penerima per detail (penerima_id, nominal, pajak, net)
2b — Engine Sisa Anggaran

Query: dpa_detail.total_harga dikurangi SUM(npd_detail.nilai_pencairan) yang sudah disetujui
Tampil sebagai viewer "Sisa DPA" sebelum user membuat NPD
2c — Form NPD

Pilih Subkegiatan → muncul daftar rekening dengan sisa pagu
Pilih rekening → isi nilai pencairan (tidak boleh melebihi sisa)
Tambah penerima dari master penerima + hitung pajak otomatis
Scope: admin_opd hanya bisa akses subkegiatan sesuai bidang OPD-nya
2d — List & Status

Status: draft → diajukan → disetujui → ditolak
Filter: per subkegiatan, rekening, status, tanggal
2e — Cetak NPD (Tahap 4, tapi bisa paralel)

Tahap 3–5 setelahnya
3: Pindah buku + engine pajak final
4: Cetak (NPD, C5, pindah buku)
5: SPJ & laporan realisasi




sesuaikan lagi hak akses menu:
- ubah polanya seperti pola hak akses C:\xampp\htdocs\pustaka, dimana ada ijin CRUD nya, bukan hanya ijin modul
- lalu kecuali superadmin, masing masing user hanya bisa mengakses opd nya masing masing
- untuk crud user opd bisa di set di masing masing hak akses, apakah hanya bisa CRUD sesuai bidangnya atau untuk semua bidang.
paham maksud saya nggak?



lalu untuk management sidebar juga cotoh pola nya di C:\xampp\htdocs\pustaka


Lakukan penyesuaian dan penataan ulang sidebar agar lebih rapi dan sesuai klasifikasinya


setelah itu lanjut cek taging program dan turunannya di /npd , karena opd yang dipilih dan program dan turunannya tidak sesuai. harusnya program dan turunannya sesuai data DPA opd yang dipilih

saya ijinkan kamu pegang kendali full, dan pilih opsi yang menurutmu terbaik, karena akan saya tinggal keluar dulu.


untuk template nomor npd nya  900 / 0001/ 06 / 2026 , 0001 nya nomer urut


buatkan form cetak npd di aksi /npd dan pindbuk , template seperti di C:\xampp\htdocs\penatausahaan\docs\master\npd_bertutur_dak.xlsx
pilihan download file berupa pdf,excel dan word

form input pinbuk sudah ada di npd/view/ , tapi halaman pinbuk nya belum ada. buatkan




- di database ada master_penerima ada npd_penerima. penerima baru belum masuk ke master_penerima, dan penerima_id masih kosong. seharusnya ketika penerima belum ada di master_penerima, maka ketika disimpan tambahkan ke master_penerima, jika dari pegawai jangan lupa id pegawainya. dan jangan sampai penerima yang sama terinput lebih dari 1 kali di master penerima

sesuaikan halaman /master/pegawai, kolom jabatan dijadikan 1, jika menjabat lebih dari 1 maka tampilkan dibawahnya , misal struktural dan keuangan

/npd/cetak/* semuanya harus variabel, tidak ada yang hard code.
Nama adalah Nama PPTK pengampu sub kegiatan, nip adalah nip pptk, jabatan adalah PPTK Bidang sesuai program
Kepada Bendahara Pengeluran OPD
Program sampai dengan kode rekening, sesuai npd
PPK adalah Pejabat Penatausahaan Keuangan Sesuai data opd
Bendahara Pengeluaran Sesuai data opd
PPTK sesuai PPTK OPD sesuai sub kegiatan yang dipilih
hasil download tidak sesuai template



NPD:
- Form kode rekening termasuk kode sub kegiatan (seperti gambar 1)


PINBUK:
- cetak PINBUK per rekening 1 halaman
- ada beberapa jenis, cetak nya sesuai rekening. jika rekening perjalaan dinas maka bentuk kolom sesuai dengan gambar, No, Nama , NIP untuk ASN, Jabatan Untuk ASN, Gol untuk ASN, lalu jumlah dibagi apakah itu untuk SPPD, REPRESENTASI, PENGINAPAN, atau TOL, Jumlah Penerimaan, Rekening. 

- lalu untuk honor seperti gambar 3
- lalu untuk belanja barang jasa lainnya seperti gambar 4 dan setersunya


jadi mungkin saat membuat pinbuk perlu memilih apakah itu termasuk belanja perjalanan , honor, atau barang jasa lainnya dan perlu ada enum untuk rekening perjalanan dinas sesuai kolom, dan pajak untuk barang jasa, karena besaran pajak pun berbeda antar jenis belanja dan penerima,



- saat menambah penerima, cek rekening dan npwp. rekening wajib isi. npwp jika ada wajib isi. karena besaran pajak berbeda.
- setelah itu ada pilihan honor / perjalanan dinas /barang jasa lain (karena berbeda form nya)
- lalu jika dipilih perjalanan, maka saat tambah baris penerima ada pilihan lagi apakah sppd, representasi,penginapan ata tol

- lalu untuk semuanya tiap input, ada pilihan pajak sesuai yang ada di pengaturan pajak di database. ketika memilih maka secara default dipilhkan tapi tetap bisa diubah barangkali salah


- rapikan lagi tampilan cetak pindah buku




modifikasi /anggaran/dpa tambahkan tab baru lagi, setelah sub kegiatan aktifitas / pekerjaan dulu baru rekening

modifikasi /anggaran/realisasi menampilkan tab seperti /anggaran/dpa juga

=================================
=================================
=================================

download excel kok csv


Gaji terakhir pegawai yang pensiun adalah 1 pangkat diatasnya. jadi pegawai naik pangkat di bulan terakhir. saya cek belum dihitung. perbaiki


/gaji/rekap dan /rekap belum menampilkan rekening sesuai nomoneklatur. cek lagi


cek penamaan rekening di /rekap/detail/**** masih belum sesuai rekening
/gaji/rekap dan /rekap tunjangan pph masih kosong semua


/gaji/rekap dan /rekap pph masih kosong (gambar 1 dan 3). per pegawai juga masih kosong semua (gambar 2 dan 4)

hapus  "— T.Khusus"	 di Belanja Tunjangan PPh/Tunjangan Khusus PNS atau PPK — T.Khusus'


saya cek SRI BARLIYANT , gaji nya belum naik di bulan terakhir sebelum pensiun


REVISI
/rekap dan /gaji/rekap buat urutan tampilannya jadi:

PNS  (PPPK AKHIRAN 2 SESUAI NOMENKLATUR):
5.1.01.01.001.00001	Belanja Gaji Pokok PNS
5.1.01.01.002.00001	Belanja Tunjangan Keluarga PNS
5.1.01.01.003.00001	Belanja Tunjangan Jabatan PNS
5.1.01.01.004.00001	Belanja Tunjangan Fungsional PNS
5.1.01.01.005.00001	Belanja Tunjangan Fungsional Umum PNS
5.1.01.01.006.00001	Belanja Tunjangan Beras PNS
5.1.01.01.007.00001	Belanja Tunjangan PPh/Tunjangan Khusus PNS
5.1.01.01.008.00001	Belanja Pembulatan Gaji PNS
5.1.01.01.009.00001	Belanja Iuran Jaminan Kesehatan PNS
5.1.01.01.010.00001	Belanja Iuran Jaminan Kecelakaan Kerja PNS
5.1.01.01.011.00001	Belanja Iuran Jaminan Kematian PNS
5.1.01.01.012.00001	Belanja Iuran Simpanan Peserta Tabungan Perumahan Rakyat PNS

Total Bruto Gaji :
5.1.01.01.009.00001 BPJS Kesehatan Gaji (1%)
5.1.01.01.013.00001 Taspen — Iuran Pensiun (4,75%)
5.1.01.01.013.00001 Taspen — JHT (3,25%)
5.1.01.01.007.00001 Tunjangan PPh 21 — Ditanggung Pemerintah (disetor)
5.1.01.01.009.00001 BPJS Kesehatan — Pemberi Kerja (4%) (disetor)
5.1.01.01.010.00001 Iuran JKK — Pemberi Kerja (0,24%) (disetor)
5.1.01.01.011.00001 Iuran JKM — Pemberi Kerja (0,30%) (disetor)

Total Potongan & Penyetoran

GAJI BERSIH :



untuk Tapera baiknya tetap dihitung kamu tau hitungannya??



- Subtotal Belanja kolom JUMLAH juga ditotal
- perhitungan a# seharusnya untuk semua rekening, bukan hanya total.
- rapikan lagi tampilan tabel agar terlihat profesional dan tampilan jelas. cek juga pilihan warna