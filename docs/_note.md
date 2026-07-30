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