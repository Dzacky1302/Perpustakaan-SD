# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Tiga pengguna dengan pentingnya setara — tidak ada yang dominan:

- **Pustakawan** (role `admin`): mengoperasikan sirkulasi, denda, katalog, cadangan, dan mencetak surat hampir tiap hari; sering di satu komputer sekolah yang dipakai bergantian.
- **Kepala Sekolah** (role `kepsek`): memantau dashboard, membaca data & laporan, mencetak/mengunduh laporan; tidak menulis data (dicegah di server, bukan hanya di UI).
- **Siswa**: memakai portal publik `/peminjaman` tanpa login di perangkat bersama untuk mencatat kunjungan dan meminjam buku; identitas diverifikasi lewat NISN.

## Product Purpose

Menggantikan buku tamu kertas dan pencatatan manual perpustakaan sekolah dasar dengan satu aplikasi: kunjungan, sirkulasi koleksi, denda, buku paket, kenaikan kelas, laporan, dan cadangan data. Keberhasilan berarti rekap kunjungan/peminjaman dapat disusun ulang kapan pun, denda dihitung adil menurut hari sekolah, dan arsip tidak hilang karena kertas.

## Positioning

Mekanisme yang tidak bisa ditiru produk sejenis tanpa meniru seluruhnya: portal kunjungan/peminjaman siswa **tanpa login yang tetap ketat** — nama yang dipilih harus cocok dengan NISN yang diketik, divalidasi ulang di server — digabung dengan denda yang dihitung per **hari sekolah** (akhir pekan & hari libur nasional tidak menambah denda), sehingga aturannya mencerminkan keseharian sekolah, bukan kalender umum.

## Operating Context

- Berjalan di server lokal sekolah (XAMPP, PHP, SQLite) secara offline di jaringan sekolah — tanpa internet andal, tanpa CDN.
- Seluruh antarmuka dan dokumen dalam bahasa Indonesia.
- Cetak fisik adalah bagian dari hasil kerja: surat peminjaman/pengembalian PDF A5 ber-kop, laporan PDF/Excel ber-kop sekolah, surat bebas pustaka.
- Tahun ajaran aktif dan hari libur nasional dikonfigurasi per tahun; daftar libur wajib diperbarui tiap tahun karena tanggal hijriah bergeser.
- Dua peran dengan penjagaan akses di server (middleware), bukan sekadar sembunyikan tombol.

## Capabilities and Constraints

- Portal publik `/peminjaman`: pilih kelas → nama → NISN 10 angka → tujuan → buku; endpoint publik diberi throttle per IP.
- Sirkulasi harian (pinjam/kembali, kuota, jatuh tempo, buku hilang) dan buku paket (distribusi massal per kelas dengan matriks penerimaan/pengembalian).
- Denda per hari sekolah (default Rp500/hari, maks Rp10.000/buku) dan dapat memblokir peminjaman berikutnya bila belum lunas.
- Kenaikan kelas satu aksi, impor siswa dari Excel, katalog buku dengan sinkron stok.
- Slip cetak bernomor urut per hari dengan barcode eksemplar dan riwayat cetak.
- Laporan PDF/Excel ber-kop; cadangan `VACUUM INTO` terjadwal harian, rotasi 14 berkas, pemulihan aman.
- Aturan pinjam default: 7 hari, maks 2 buku per siswa (via `.env`).
- Belum diputuskan: kebutuhan aksesibilitas produk yang spesifik (standar/wajib belum dinyatakan).

## Brand Commitments

- Nama produk: **PustakaSD**.
- Identitas sekolah terkunci untuk satu SD dan dipakai di kop surat/laporan (konfigurasi `config/perpustakaan.php`, default "SD Negeri 1 Nusantara", Dinas Pendidikan Kota Nusantara) — dipastikan user: satu sekolah.
- Suara yang terlihat di copy: santai, langsung, bahasa Indonesia sehari-hari, tanpa jargon teknis.
- Aset yang ada: hanya `public/favicon.ico`. Logo sekolah/pustakawan belum ada di repo — pekerjaan visual tidak boleh mengarangnya.

## Evidence on Hand

- `README.md` (masalah, solusi, tabel fitur, peran akses) dan `CHANGELOG.md` (rilis 1.2.0).
- 111 test fitur hijau, termasuk portal NISN dan konsistensi hitung telat.
- Konfigurasi identitas sekolah & aturan denda di `config/perpustakaan.php`.
- Data seed demo (siswa, kelas, katalog) — bukan data nyata sekolah.
- Tidak ada di repo: logo sekolah, tangkapan layar resmi, testimoni, atau klaim pengguna nyata — jangan ditambahi.

## Product Principles

1. Kecocokan data di atas kecepatan: portal tanpa login bukan berarti tanpa verifikasi — NISN harus cocok sebelum data tersimpan.
2. Satu sumber angka: hitungan telat/denda yang sama dipakai dashboard, filter, dan laporan.
3. Keadilan kalender sekolah: denda hanya menghitung hari sekolah, bukan hari kalender.
4. Hasil kerja harus bisa dicetak dan diarsipkan: surat/berkas ber-kop rapi, cadangan terjadwal.
5. Aman meski dipakai staf non-teknis: jaga akses di server, tampilkan konsekuensi dengan jelas di UI.
