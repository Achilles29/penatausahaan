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





sebelum masuk NPD, kita pindah tema sebentar

rencananya aplikasi ini juga akan saya gunakan untuk perhitungan gaji. untuk perencaan kebutuhan gaji
jadi saya butuh master pegawai mengakomodir data data yang digunakan untuk perhitungan gaji, seperti istri, jumlah anak, BUP, tmt kenaikan pangkat YAD, jabatan (struktural / fungsional), dan lain sebagainya.
lalu master gaji pns dan p3k, master golongan pns dan p3k, intinya semua yang berkaitan untuk perhitungan gaji asn.
 ada ide ? coba cari pengetahuan tentang itu dan sesuaikan. kejutkan saya!




 - rapikan modal edit dan tambah pegawai, karena data yang masuk sekarnag banyak , agar lebih nyaman
 - rekening gaji pegawai seperti yang ada di master rekening 5.1.01, coba cari referensi masing masing rumusnya, lalu data apa saja yang kira kira dibutuhkan untuk master pegawai yang belum ada.
 misal : 
 gaji pokok PNS => golongan dan masa kerja, berarti butuh tmt cpns / masa kerja
 tunjangan beras =>  Rp7.242 per jiwa per bulan
 dan seterusnya
- yang agak kesulitan adalah rumus perhitungan pajak / pph dan pembulatan, saya belum nemu rumusnya
- untuk tambahan penghasilan pegawai besaran diambil dari perbup tpp rembang sesuai kelas jabatan. kita buat master tpp yang tertaging langsung dengan  jabatan terntentu
- Belanja PPH dan Iuran Jaminan Kesehatan ini diperhitungkan untuk gaji dan tpp (bpjs untuk tpp dihitung 4% dari tpp yang diterima masing masing pegawai). pisahkan perhitungannya, tapi nanti tetap bisa disatukan di UI untuk rekening yang sama

- kunci dari perhitungan gaji adalah data pegawai yang valid.

- hasil akhir adalah dengan klik hitung, bisa menghitung estimasi gaji pegawai berdasarkan data dasar sampai dengan bulan tertentu. data dihitung per pegawai, dengan memperhatikan kenaikan pangkat, kenaikan gaji berkala, pensiun, dan tunjangan lainnya.

sesuaikan database dan UI yang perlu disesuaikan. kalau sudah nanti saya eksekusi data yang dibutuhkan

ralat tunjangan beras betul 10 kg per jiwa per bulan, tapi harganya yang disesuaikan tadi.


revisi ref_gaji_pokok , gaji pokok bukan hanya bergantung pada golongan, tapi juga masa kerja. yang tertulis di tabel baru masa kerja 0




- jangan lupa gaji 13 dan 14. gaji 13 dan 14 ini nanti bisa diatur, besarannya berdasarkan bulan apa. misal gaji 14 berdasarkan bulan maret. lalu ingat pph gaji 13 dan 14 ini jauh lebih besar dari gaji biasa
- status pegawai ada pns , cpns , ppk. jika pegawai cpns maka ada tanggal pengangkatan cpns nya, karena nanti gajinya berubah dari 80% jadi 100%. lalu untuk gaji sendiri juga memungkinkan 50% jika sedang terkena hudis.

- master tpp bisa kamu isi berdasarkan besaran di lampiran perbup_tpp pada folder master


- tmt cpns dan pns kan sudah ada, walaupun master kgb, kp , diisi, tapi tetap perlu pengecekan jika ada anomali data yang tidak sinkron berdasarkan data master yang diinput dan berdasarkan data dasar pegawai tmt cpns dan pns serta tanggal lahirnya

- jangan lupa sebulan sebelum pensiun, pegawai mendapatkan kenaikan pangkat sehingga gaji juga naik

- master tunjangan fungsional (tertentu) dan tunjangan khusus bisa dilihat dari file GARAP_GAJI di master. tunjangan khusus ini tidak semua pegawai dapat, tapi perlu dimasukkan ke data pegawai (opsional) jika memang pegawai tsbt berhak mendapatkan 
===========

perlu kita perbaiki:
- di reff kelas jabatan ada kolom tpp tapi kosong
- di reff tpp seharusnya ada kolom kelas jabatan.
- reff jabatan perlu diubah , isinya nama jabatan khusus per opd, bukan nama jabatan sesuai perbup tpp, karena jabatan perbup tpp itu global menggabungkan beberapa jabatan. hapus jabatan yang sudah diinput 
- reff tpp 
 perlu ada resolusi kedua tabel itu, menurut saya kolom tpp dan tukin di kelas jabatan dihapus, lalu di reff tpp tambahkan kolom kelas jabatan id, tidak perlu kolom jabatan, tapi tambahkan uraian yang berisi nama jabatan di excel, jadi logika taging nya dibalik, bukan tpp yang melekat pada jabatan id, tapi jabatan id yang mempunyai tpp id.  dan di crud pegawai cukup form tpp saja tidak perlu kelas jabatan, tapi preview yang ditampilkan : Kelas Jabatan - Nama Jabtan (sesuai excel) - besaran TPP


 urutkan tampilan /master/pegawai : id opd, eselon tertinggi, pangkat golongan tertinggi, usia tertinggi

 
rekap gaji (perhitungan gaji) pegawai, buat beberapa modul yang bisa menggenreate :
- gaji pada range bulan tertentu (misal januari 2027 - desember 2027 , termasuk 13 dan 14)
- bisa dipisahkan pns dan pppk
- bisa dipisahkan antara gaji dan tpp , namun bisa tetap menghitung total dikarenakan Pph dan bpjs tpp diambil dari rekening gaji. dan untuk pajak tpp 5% dan 15 % tergantung golongannya
- bisa dilihat detail per pegawai untuk melihat gaji per bulan per rekeningnya


perlu ada penyesuaian pemisahan antara status kawin dan tunjangan istri. jika data di excel tj istri 0 bukan berarti tidak kawin, bisa jadi istrinya juga asn dengan tunjangan yg lebih besar. jadi tunjangannya ikut istri


/rekap error, tampilan acak acakan
Fatal error: Cannot redeclare row_cells() (previously declared in C:\xampp\htdocs\penatausahaan\application\views\rekap\index.php:222) in C:\xampp\htdocs\penatausahaan\application\views\rekap\index.php on line 222
A PHP Error was encountered

Severity: Error

Message: Cannot redeclare row_cells() (previously declared in C:\xampp\htdocs\penatausahaan\application\views\rekap\index.php:222)

Filename: rekap/index.php

Line Number: 222

Backtrace:


/gaji/rekap hasil hitungan buat 2 tab , rekap per rekening dan tab per pegawai. pastikan data ditampilkan berdasarkan rekening belanja pegawai (yang tidak ada hitungannya, misal tidak dikenai pajak berarti ditampilkan 0), urutan tampilan  :  urutkan tampilan /master/pegawai : id opd, eselon tertinggi, pangkat golongan tertinggi, usia tertinggi
cek juga di master pegawai saya cek tidak ada eselon, HARUSNya ada kolom eselon, dan sinrkon dengan reff jabatan yang dipilih


halaman /gaji/rekap kenapa hanya menampilkan pns? pppk mana?
semua halaman yang menampilkan pegawai, urutan seharusnya adalah id opd, eselon tertinggi, pangkat golongan tertinggi, usia tertinggi

pph dan pbjs memang ditanggung negara, tapi tetap harus dicatat keluar masuknya ya. jadi diterima tetap dicatat, dibayarkan juga dicatat


- revisi urutan pegawai, eselon tertinggi itu yang angkanya terendah! II A, IIB, IIIA dan seterusnya, PNS dulu baru PPPK. jadi saya ulangi : PNS , PPPK , baru eselon
- 

- perbaiki data pegawai, buat semua uppercase agar konsisten, cek UI nya juga
- saya cek MUKHAMMAD ANWAR FU`ADI, SSTP, M.Si , /gaji/simulasi:
  =>Tunjangan PPh 21 muncul Rp 164.508 , padahal seharusnya tidak kena pajak. bisa coba jelaskan kenapa muncul pajak?
  => untuk tunjangan askes / BPJS kesehatan seharunsya 212453 tapi di simulasi muncul 173253 jelaskan
  => JKK harusnya 9119 , jelaskan	
  => JKM harusnya 27356, jekaskan
  => tunjangan pembulatan harusnya 22, jelaskan

  coba cek iwp_beban_pegawai	iwp_beban_pembkerja	iwp_taspen di file excel barangkali dibutuhkan


  coba ya kamu cek C:\xampp\htdocs\penatausahaan\docs\master\pegawai.xlsx atas nama MUKHAMMAD ANWAR FU`ADI, SSTP, M.Si , mulai kolom gapok sampai kolom bersih. hitungannya masih beda dengan hitunganmu. (gaji ya, bukan tpp, tpp kita bahas nanti setelah gaji clear)

sekilas saya cek perhitungan tunjangan pajak dan pembulatan belum sesuai. kamu tahu rumusnya nggak sih?


untuk pengaturan tpp:
- pajak dibayarkan oleh negara
- bpjs dibayarkan negara 4%, dipotong dari tpp 1%.
- jadi secara total pengurangan tpp hanya 1%
- paja dan bpjs diperhitungkan dalam perhitungan total tunjangan pph dan tunjangan bpjs (tapi tetap pisahkan tampilannya agar jelas mana gaji mana tpp, walaupun pada akhirnya digabungkan ke rekningnya)


a#


saya coba untuk sala satu simulasi tpp:
5.1.01.02.001 Tambahan Penghasilan Pegawai (TPP) — Sekretaris Dinas/Badan/Satpol PPDasar: Perbup Rembang 2024Rp 4.500.000
5.1.01.02.001 PPh 21 TPP (15%) — Ditanggung Negara Gol IV DTPRp 675.000
5.1.01.02.001 BPJS Kesehatan dari TPP — Pemberi Kerja (4%) DTPRp 180.000
5.1.01.02.001 PPh 21 TPP — setor ke kas negara (DTP)(Rp 675.000)
5.1.01.02.001 BPJS Kes TPP — setor ke BPJS (4%) (DTP)(Rp 180.000)
5.1.01.02.001 BPJS Kesehatan TPP — Pegawai (1%)

PPH dan BPJS kode rekeningnya salah, harusnya 5.1.01.01.xxx bukan 5.1.01.02.xxx karena masuk ke komponen Gaji dan Tunjangan, bukan komponent tambahan penghasilan. namun untuk tampilannya memang sudah benar dipisah antara Gaji dan tambahan penghasilan. cek lagi!

 BPJS Kesehatan TPP — Pegawai (1%) itu tanggungang pegawai yang dipotong dari tpp yang diterima bukan negara, jadi diperhitungkan untuk thp pegawai tapi tidak diperhitungkan sebagai beban negara.
=====================


kemudian, untuk logika model simulasi sudah benar, sekarang tinggal terapkan ke logika /rekap/detail/, /rekap, /gaji/rekap. harusnya modelnya sama.



- cek KGB apakah sudah diperhitungkan? bagaimana mengitung gaji berkala? apakah dari hitungan masa kerja atau dari kolom KGB yad ?



- buat agar /gaji/simulasi bisa memilih bulan, termasuk gaji 13 dan 14
- /gaji/rekap data yang ditampilkan persis sama dengan simulasi per orang, semua rekening muncul termasuk pembulatan bisa memilih 13 dan 14
- buat tampilan /rekap turun ke bawah per rekening, bulannya ke kanan. dan pastikan rekening mengikuti kodifikasi. pembulatan dijumlahkan berdasarkan hitungan pembulatan per pegawai per orang.


Applicacifus competition. Let me sir me, sir. Misa learners.- sesuaikan lagi /rekap dan /gaji/rekap , seharusnya dipisah antara PNS , PPPK, Gaji, TPP, karena rekeningnya masing masing beda. Rekening Gaji dan TPP PNS dan PPPK beda, jadi buat tab nya semua terpisah. dan pastikan semua rekening tampil meskipun 0, jangan ada yang digabung. tampilan rekening urut dari terkecil sampai terbesar yang gunanya untuk menghitung besaran anggaran yang dibutuhkan. lalu buatkan tombol download excel sesuai data yang di generate

- Terkait gaji 13 dan 14, kamu tau ketentuannya nggak? hitungannya beda dengan gaji reguler. pajaknya juga lebih besar. cek lagi



revisi ya:
- /gaji/simulasi Tunjangan PPh 21 Gaji ke 13 sepertinya sudah benar, tapi untuk TPP masih salah. Tunjangan PPh 21 TPP tetap flat 15 % dan 5 %. perbaiki juga untuk /gaji/rekap dan /rekap.

- buat tampilan /gaji/rekap dan /rekap seperti /gaji/simulasi yang menampilkan Gaji Bruto dan Gaji Bersih

- tampilan TPP juga seharusnya menampilkan TPP Bruto dan TPP Bersih untuk ketiga halaman agar ketahuan jumlah anggaran yang digunakan

- tampilan /gaji/rekap per pegawai seharusnya menampilkan semua data per rekening seperti simulasi, jangan ada yang digabung. baik Gaji dan TPP. saya lihat PPH belum muncul di gaji kotor. dan potongan masih digabung.


- tampilan /rekap/detail/**/*/*/** juga belum menampilkan semua rekening seperti tampilan simulasi

Perbaiki

revisi tampilan /master/pegawai:
kolom 1:
NAMA
NIP

kolom 2:
STATUS (PNS /CPNS/PPPK)
PANGKAT
GOLONGAN
% GAJI

Kolom 3:
MKG
KGB YAD
KP YAD

Kolom 4:
ESELON
JABATAN


kOLOM 5:
TERIMA TUNJANGAN KELUARGA ( YA ATAU TIDAK)
STATUS KAWIN
JUMLAH ANAK

KOLOM 6
OPD



- hitung /gaji/simulasi malah tidak tampil apa apa
- /gaji/rekap dan /rekap untuk rekening pph tidak muncul
yang benar kan 
5.1.01.01.007	Belanja Tunjangan PPh/Tunjangan Khusus ASN
5.1.01.01.007.00001	Belanja Tunjangan PPh/Tunjangan Khusus PNS
5.1.01.01.007.00002	Belanja Tunjangan PPh/Tunjangan Khusus PPPK
dan PPH masuk disana

- tampilan /gaji/rekap per pegawai masih kacau kolomnya


perbaikan tampilan untuk TPP ya. jadi sekali lagi konsep kita adalah menghitung kebutuhan anggaran.
jadi tampilan seharusnya kurang lebih seperti ini:
- rekening tpp
- rekeninf pph
- rekening bpjs (pemberi kerja)

itu bruto yang dianggarkan

baru dibawahnya bersih nya adalah tpp dikurangi bpjs mandiri 1% 
secara hitungan memang sudah sesuai tapi secara tampilan kurang pas untuk menghitung kebutuhan anggaran


perbaiki


download excel kok csv

saya punya pertanyaan, carikan jawabannya, lalu apakah sudah sesuai dengan konsep kita? kalau masih ada yang salah maka perbaiki

- jika seorang ASN sudah menikah dan mempunyai anak, tapi pasangannya juga ASN dengan gaji yang lebih besar, maka tunjangan Keluarga (anak dan istri) ikut ke pasangannya. di database kita sudah ada Status Perkawinan, jumlah anak , Terima tunjangan keluarga , apakah jika status kawin dan jumlah anak ada, tapi terima tunjangan keluarga = 0, pegawai tersebut tetap menerima tunjangan istri dan anak? lalu sesuai ketentuan bagaimana dengan tunjangan beras? apakah jika tujangan ikut pasangan maka pegawai tsbt masih menerima tunjangan beras? untuk pegawa YBS saja atau untuk keluarganya juga? lalu bagaimana dengan konsep kita apakah sudah sesuai ketentuan?
- jika anak lebih dari 2, maka yang dihitung tunjangan anak hanya 2, apakah aplikasi kita sudah seperti itu? bagaimana dengan tunjangan beras? 




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


- rapikan lagi tampilan cetak pindah buku