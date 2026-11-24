# Changelog

Semua perubahan penting proyek ini dicatat di sini, mengikuti format
[Keep a Changelog](https://keepachangelog.com/id/1.1.0/).

## [1.2.0] - 2026-11-19

### Ditambahkan

- Verifikasi NISN di portal siswa (`POST /peminjaman/verifikasi`): nama yang dipilih wajib cocok dengan NISN yang diketik, dicek juga ulang saat penyimpanan.
- `FineService::overdueIds()` dan scope `DailyLoan::overdue()` supaya hitungan keterlambatan di dashboard, filter, dan laporan memakai satu sumber yang sama.
- Lama pinjam, nomor registrasi, dan barcode eksemplar pada surat peminjaman.
- Test baru: `StudentPortalTest` (NISN tidak bocor dari daftar nama) dan `OverdueConsistencyTest`.

### Dihapus

- Halaman kios layar sentuh: `KioskController`, `Pages/Kiosk/Index.jsx`, dan seluruh route `/kios*`.
- Kuitansi denda dua versi beserta riwayat cetaknya: controller, model, service, halaman arsip dan verifikasi kode, view `kuitansi-denda.blade.php`, route, test, serta tabel `fine_receipts`/`fine_receipt_prints` (dihapus lewat migrasi baru).

### Diubah

- Portal siswa terbuka tanpa login, tapi kini memakai nama + NISN sebagai kunci verifikasi, dengan pembatasan laju per IP di endpoint daftar, verifikasi, dan simpan.
- Tenggat efektif digeser ke hari sekolah pertama juga saat tanggal jatuh tempo sendiri jatuh di hari libur atau akhir pekan.
- `composer.json` kini mewajibkan ekstensi `zip` (dipakai ekspor Excel) supaya kegagalannya ketahuan saat instalasi, bukan saat ekspor.
- README: dokumentasi kios dan kuitansi diganti dengan portal NISN; status test diperbarui menjadi **108 lulus**.

### Keamanan

- Portal, registrasi, pembagian role, soft delete siswa, dan WAL mode database diperkuat (seri perbaikan P0).
- Endpoint publik portal tidak lagi mengirim NISN, dan pesan galat dibuat sama untuk NISN tidak dikenal maupun nama tidak cocok.

## [1.1.0] - 2026-10-13

### Ditambahkan

- Grafik tren kunjungan memakai Recharts (`AreaChart` + tooltip tanggal `id-ID`).
- Trend di `StatCard` kunjungan (`+X vs kemarin`) dan peminjaman (`N telat` / `Aman`).
- Screenshot asli di `docs/`: dashboard, laporan, halaman depan, peminjaman.
- Komponen UI baru: `Skeleton`, `StatSkeleton`, `TableSkeleton`, `TableWrapper` untuk tabel responsif.
- Halaman error Inertia (`Error.jsx`) berbahasa Indonesia dengan tombol kembali ke dashboard.
- Script `git-history-report.ps1` untuk bikin lampiran laporan (tanggal + daftar perubahan otomatis dari git log).
- Template `docs/LAPORAN.md` buat laporan pengembangan.

### Diubah

- Fase A polish design system: `Badge` (opsi dot), `Card` (shadow konsisten + hover), `StatCard` (dukungan trend), `Button` (varian size + loading), `Field/Input/Select` (state invalid + aksesibilitas), `EmptyState` compact.
- Fase B polish navigasi: sidebar & mobile menu dikelompokkan (Utama / Sirkulasi / Data Master / Sistem), active state jelas, toast ada tombol tutup + progress bar.
- Fase C polish Dashboard: tabel responsif (`TableWrapper` + `min-w`) dan empty state untuk data kosong.
- Fondasi visual global: `app.css` (selection, focus-visible, sticky table head) + `tailwind.config.js` (shadow card/pop, animasi fade-up, font Inter).
