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
