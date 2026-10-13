# Changelog

Semua perubahan penting proyek ini dicatat di sini, mengikuti format
[Keep a Changelog](https://keepachangelog.com/id/1.1.0/).

## [Unreleased]

### Ditambahkan

- Komponen UI baru: `Skeleton`, `StatSkeleton`, `TableSkeleton`, `TableWrapper` untuk tabel responsif.
- Halaman error Inertia (`Error.jsx`) berbahasa Indonesia dengan tombol kembali ke dashboard.
- Script `git-history-report.ps1` untuk bikin lampiran laporan (tanggal + daftar perubahan otomatis dari git log).
- Template `docs/LAPORAN.md` buat laporan pengembangan.

### Diubah

- Fase A polish design system: `Badge` (opsi dot), `Card` (shadow konsisten + hover), `StatCard` (dukungan trend), `Button` (varian size + loading), `Field/Input/Select` (state invalid + aksesibilitas), `EmptyState` compact.
- Fase B polish navigasi: sidebar & mobile menu dikelompokkan (Utama / Sirkulasi / Data Master / Sistem), active state jelas, toast ada tombol tutup + progress bar.
- Fase C polish Dashboard: tabel responsif (`TableWrapper` + `min-w`) dan empty state untuk data kosong.
- Fondasi visual global: `app.css` (selection, focus-visible, sticky table head) + `tailwind.config.js` (shadow card/pop, animasi fade-up, font Inter).
