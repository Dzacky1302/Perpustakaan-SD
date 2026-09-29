<div align="center">

# PustakaSD

Aplikasi perpustakaan sekolah dasar. Ganti buku tamu kertas jadi digital, plus
sirkulasi koleksi, buku paket, denda, sama laporannya dalam satu tempat.

`Laravel 12` · `Inertia.js 2` · `React 18` · `Tailwind CSS` · `SQLite`

</div>

---

## Masalah

Kebanyakan perpustakaan sekolah masih andalin buku tamu kertas dan pencatatan manual. Yang terjadi:

| Masalah | Akibatnya |
|---|---|
| Buku tamu hilang atau tercecer | Rekap kunjungan harian nggak bisa disusun ulang |
| Catatan peminjaman di buku tulis | Susah dicari waktu siswa telat atau bukunya hilang |
| Rekap buku paket per kelas | Dihitung manual, makan waktu berjam-jam tiap akhir tahun |
| Surat bebas pustaka | Dibuat manual, gampang salah karena datanya bercabang |
| Arsipnya cuma kertas | Hilang permanen kalo fire, rusak, atau kepindah |

## Solusi

Aplikasinya dipakai dua cara:

- **Portal siswa** (`/peminjaman`) — tanpa login, buat catatan kunjungan. Siswa milih kelas, namanya, sama keperluan; waktu datang dicatat sistem.
- **Panel petugas** — buat pustakawan sama kepala sekolah, dengan hak akses yang beda.

## Fitur

| Modul | Kemampuan |
|---|---|
| **Buku Tamu Digital** | Kios layar sentuh + portal publik. Kunjungan, keperluan, dan buku yang dibaca tercatat otomatis. |
| **Sirkulasi Koleksi** | Peminjaman, pengembalian, batas kuota, jatuh tempo, dan pencatatan buku hilang. |
| **Denda** | Dihitung otomatis per **hari sekolah**, dibayar di loket, dan memblokir peminjaman berikutnya bila belum lunas. |
| **Buku Paket** | Distribusi massal satu kelas sekali klik, dengan matriks penerimaan dan pengembalian per siswa. |
| **Kenaikan Kelas** | Memindahkan seluruh siswa satu tingkat dalam satu aksi, tanpa merusak riwayat kelas. |
| **Slip Cetak** | Surat peminjaman, surat pengembalian, dan kuitansi denda dalam PDF A5 ber-kop sekolah. |
| **Laporan** | PDF dan Excel ber-kop sekolah: buku tamu, peminjaman, buku paket, denda, serta surat bebas pustaka. |
| **Cadangan Otomatis** | `VACUUM INTO` terjadwal harian, rotasi 14 berkas, dan pemulihan data yang aman. |
| **Manajemen Data** | Kelas, siswa (impor Excel), kategori, dan katalog buku lengkap dengan pengelolaan stok. |

## Peran Akses

Dua level, dijaga di sisi server lewat middleware:

| Peran | Hak Akses |
|---|---|
| **Pustakawan** (`admin`) | Lihat, nambah, ubah, hapus, cetak, kelola cadangan |
| **Kepala Sekolah** (`kepsek`) | Lihat semua halaman, cetak, unduh laporan. Nggak bisa ubah data. |

Tombol yang nggak kepake disembunyiin di tampilan, tapi penjaganya beneran ada di server — coba `POST`/`PATCH`/`DELETE` langsung, ditolak **403**.


## Screenshot

> **TODO: ganti pakai tangkapan layar asli.**
> Taruh gambarnya di folder `docs/`, lalu arahin kayak gini: `![Dashboard](docs/dashboard.png)`.
> Saran: Dashboard, Kios Buku Tamu, Peminjaman, sama Laporan.

| Dashboard | Buku Tamu (Kios) |
|---|---|
| ![Dashboard](docs/dashboard.png) | ![Kios](docs/kiosk.png) |

| Peminjaman | Laporan |
|---|---|
| ![Peminjaman](docs/peminjaman.png) | ![Laporan](docs/laporan.png) |

---

## Tech Stack

| Lapisan | Teknologi | Alasan |
|---|---|---|
| Backend | Laravel 12, PHP 8.2 | Ekosistem matang, cocok untuk aplikasi bisnis berbasis CRUD |
| Frontend | Inertia.js 2 + React 18 | Transisi halaman mulus tanpa reload penuh, tetap satu aplikasi |
| Styling | Tailwind CSS 3 | Konsisten dan cepat untuk UI dashboard |
| Build | Vite 7 | Build produksi cepat dengan pemecahan kode per halaman |
| Database | SQLite | Ringan, tanpa server database — cukup untuk satu sekolah |
| Routing JS | Ziggy | Nama route PHP tersedia di React, jadi tidak ada path yang salah ketik |
| PDF | DomPDF | Slip dan laporan ber-kop sekolah dicetak langsung dari PHP |
| Excel | OpenSpout | Impor data siswa dan ekspor laporan, ringan untuk skala sekolah |
| Testing | PHPUnit 11 | 64 feature test menutup logika bisnis dan hak akses |

---

## Arsitektur

Pola **Controller → Service → Model**. Aturan bisnis disimpan di Service,
bukan di Controller, supaya dapat dipakai ulang dan diuji terpisah.

```
app/Http/Controllers/     app/Services/                 app/Console/Commands/
├── DailyLoanController   ├── FineService              ├── BackupDatabaseCommand
├── ReportController      ├── SlipService              └── RecalculateFinesCommand
├── BackupController      ├── BackupService
└── ...                   ├── LibraryVisitService
                         ├── BookStockService
resources/js/Pages/       └── SpreadsheetService
├── Circulation/  ├── Master/
├── Kiosk/        └── System/Backups.jsx
```

`app/Http/Middleware/EnsureAdmin.php` menjadi satu-satunya penjaga hak akses
seluruh aplikasi — cukup satu tempat, aturan tidak tercecer di tiap controller.


---

## Keputusan Desain

Beberapa hal yang kelihatan sepele dulu, ternyata jadi penting. Saya tulis alasannya di sini, bukan cuma apa yang dikerjain.

### 1. Snapshot kelas

Ini yang paling penting menurut saya.

Kalau laporan cuma `JOIN` ke `students.classroom_id`, begitu siswa naik kelas, laporan tahun lalu ikut berubah. Anak yang dulu di kelas 4 sekarang udah kelas 5, dan rekap "kunjungan kelas 4 tahun 2024" jadi nunjukin dia di saat udah kelas 5. Salah.

Jadi `classroom_id` ikut disimpen waktu kunjungan dicatat, bukan dicari saat laporan dibuat.

Sisanya, data lama udah terlanjur nggak ada kelasnya. Saya pilih nggak nebak — ditampilin "tidak tercatat". Mending nggak ada yang bisa ngadi daripada ngadi salah.

### 2. Denda dihitung per hari sekolah

Awalnya cuma pakai `diffInDays()`. Terus aku cek sendiri, ternyata buku yang jatuh tempo Jumat terus dikembalikan Senin kena denda 3 hari. Padahal weekend doang, nggak ada hari sekolah yang kelewat. Yang lebih kacau, hari Sabtu udah keliatan "Terlambat" di layar.

Jadi diganti: `schoolDaysBetween()` cuma ngitung Senin-Jumat dan lewatin hari libur, terus `effectiveDeadline()` geser tenggat ke hari sekolah berikutnya kalo tanggal jatuh temponya nempel weekend.

Hari liburnya aku ambil dari SKB 3 Menteri, disimpan di `config/perpustakaan.php` — dan **wajib** ditambahin tiap tahun karena tanggal hijriah geser.

### 3. Denda dikunci pas buku balik

Nominalnya disimpan di kolom `fine_amount` waktu pengembalian dicatat. Selama buku masih dipinjam, angkanya dihitung ulang tiap tampil biar dashboard-nya always update. Setelah dibalik, jadi beku.

Alasannya: kalo terus dihitung dari tanggal sekarang, laporan tahun lalu ikut berubah. buat arsip sekolah, angka yang udah lunas itu harus tetap.

### 4. `VACUUM INTO`, bukan copy file

Dulu saya kepikiran copy `database.sqlite` aja. Ternyata nggak aman — SQLite nulisnya bertahap, jadi salinannya bisa keotong di tengah dan hasilin file korup.

Sekarang pakai `VACUUM INTO` bawaan SQLite. Hasilnya konsisten, dan nggak nge-lock database aslinya, jadi aman jalan otomatis pas jam sibuk. Sisanya rotasi: cuma 14 file terbaru yang disimpan, plus `basename()` biar filename dari URL nggak bisa ngeluarin file di luar folder.

### 5. Pendaftaran publik nggak pernah jadi admin

Kalau registrasi dibuka ke internet, siapa aja bisa daftar lalu langsung dapat akses ubah data. Jadi `RegisteredUserController` selalu ngasih role `kepsek` (baca doang). Role pustakawan hanya lewat seeder atau database.

### 6. Satu sumber kebenaran buat hitungan keterlambatan

Ini bug beneran. Logika "berapa hari telat" awalnya nyebar di **empat tempat**: service, model (`liveFine` dan `isOverdue`), sama controller. Akibatnya tiap layar bisa nampilin angka yang beda-beda — dan itu bukan risiko teori, emang kejadian.

Sekarang semuanya lewat `FineService`. Tampilan, kuitansi, sama laporan dijamin konsisten karena sumbernya cuma satu.


## Instalasi

**Prasyarat:** PHP 8.2 atau lebih baru, Composer, dan Node.js 18+.

```bash
# 1. Pasang dependensi, buat .env, generate key, migrate, build frontend
composer setup

# 2. Isi data contoh (kelas, siswa, buku, riwayat peminjaman)
php artisan migrate --seed

# 3. Jalankan untuk pengembangan
composer dev
```

Buka `http://localhost:8000`. Portal siswa publik tersedia di
`http://localhost:8000/peminjaman` tanpa perlu login.

Untuk menjalankan server dan Vite secara terpisah:

```bash
php artisan serve
npm run dev
```

### Konfigurasi

Salinan `.env.example` sudah memuat seluruh konfigurasi sekolah, aturan
peminjaman, aturan denda, dan jadwal cadangan, lengkap dengan komentar
penjelasan. Nilai yang paling sering diubah:

| Kunci `.env` | Default | Keterangan |
|---|---|---|
| `SEKOLAH_NAMA` | `SD Negeri 1 Nusantara` | Dicetak pada kop laporan dan slip |
| `PERPUS_LOAN_DAYS` | `7` | Lama peminjaman dalam hari |
| `PERPUS_MAX_LOANS` | `2` | Kuota buku koleksi per siswa |
| `PERPUS_DENDA_PER_HARI` | `500` | Denda per **hari sekolah** |
| `PERPUS_DENDA_MAKS` | `10000` | Plafon denda per buku |
| `PERPUS_HITUNG_CUTI_BERSAMA` | `true` | Cuti bersama diperlakukan sebagai hari sekolah tutup |
| `PERPUS_BACKUP_KEEP` | `14` | Jumlah berkas cadangan yang disimpan |

> Daftar hari libur nasional dan cuti bersama berada di
> `config/perpustakaan.php` pada kunci `fine.holidays` dan `fine.joint_leaves`.
> **Wajib diperbarui setiap tahun** karena tanggal hijriah bergeser 1–2 hari.

## Akun Demo

Dibuat otomatis oleh seeder:

| Email | Password | Peran | Kemampuan |
|---|---|---|---|
| `admin@perpus-sd.test` | `password` | Pustakawan | Akses penuh, termasuk mengubah data dan mengelola cadangan |
| `kepsek@perpus-sd.test` | `password` | Kepala Sekolah | Hanya membaca dan mengunduh laporan |

Cara tercepat untuk melihat perbedaan hak akses: login sebagai
`kepsek`, lalu buka menu **Peminjaman** atau **Siswa**. Tombol tambah, ubah,
dan hapus tidak ada. Coba juga kirim permintaan `POST` secara langsung —
server akan menolak dengan **403**, meskipun tombolnya memang tidak terlihat.

## Perintah Kustom

```bash
# Cadangkan database sekarang juga (juga berjalan otomatis setiap hari)
php artisan perpustakaan:backup

# Lihat daftar cadangan beserta ukuran dan umurnya
php artisan perpustakaan:backup --list

# Jalankan rotasi: hapus cadangan lama sesuai batas PERPUS_BACKUP_KEEP
php artisan perpustakaan:backup --prune-only

# Hitung ulang nominal denda (perlu setelah aturan denda diubah)
php artisan perpustakaan:recalc-denda

# Simulasikan tanpa menyimpan
php artisan perpustakaan:recalc-denda --dry-run
```

### Menjalankan Backup Otomatis

Backup terjadwal didaftarkan di `routes/console.php` dan dijalankan oleh
scheduler Laravel. Pada server, tambahkan satu baris cron:

```cron
* * * * * cd /path/ke/Perpustakaan-SD && php artisan schedule:run >> /dev/null 2>&1
```

## Testing

```bash
composer test                              # atau: php artisan test
php artisan test --testdox                 # output lebih mudah dibaca
php artisan test --filter Fine             # hanya test denda
```

Status saat ini: **64 test, 151 assertion, semuanya lulus.**

Cakupan test meliputi snapshot kelas saat siswa naik kelas, pembatasan role
pustakawan dan kepala sekolah, perhitungan denda per hari sekolah (termasuk
akhir pekan, libur nasional, dan cuti bersama), slip PDF, serta backup database
termasuk rotasi dan penolakan path traversal.

## Keterbatasan

Yang belum ada, aku tulis aja biar jelas:

- **Belum ada REST API.** Semua masih server-rendered Inertia.
- **Login belum dibatasi laju.** Di produksi, `/login` perlu `throttle`.
- **Belum ada test frontend.** 64 test semuanya di sisi PHP, komponen React belum disentuh.
- **Frontend masih JavaScript**, belum TypeScript.
- **Belum ada halaman error** (404/500) dan belum ada error boundary di React.
- **Backup cuma di storage lokal server.** Kalau perangkatnya rusak, filenya perlu disalin manual ke media lain.
- **Riwayat git masih tipis.** Repo ini di-upload sekaligus, jadi nggak kelihatan prosesnya dari awal.

## Lisensi

MIT
