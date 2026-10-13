# Laporan Pengembangan — PustakaSD (Polish Frontend + Histori Rapi)

> Template laporan. Isi tanggal + bukti screenshot tiap fase.
> Histori tanggal & daftar perubahan diambil otomatis dari git (lihat Lampiran A).

## 1. Identitas

| Item | Isi |
|---|---|
| Nama aplikasi | PustakaSD — Perpustakaan Sekolah Dasar |
| Repo | https://github.com/Dzacky1302/Perpustakaan-SD |
| Periode polish | ... s.d. ... |
| Tech stack | Laravel 12 · Inertia 2 · React 18 · Tailwind 3 · SQLite · Vite 7 |

## 2. Ringkasan Pekerjaan

| Fase | Branch / PR | Tanggal merge | Hasil |
|---|---|---|---|
| A — Design system (`ui.jsx`, CSS, Tailwind) | `polish/ui-system` | 2026-10-13 | Palet dikunci, komponen konsisten |
| B — Layout & navigasi | `polish/layout-nav` | 2026-10-13 | Sidebar berkelompok, toast rapi |
| C — Dashboard responsif | `polish/dashboard-charts` | 2026-10-13 | Tabel aman di HP, empty state |
| D — Error page + docs | `polish/error-pages-docs` | 2026-10-13 | Error page ID, CHANGELOG, laporan |
| Sesi 1 — Chart Recharts + trend | `feat/dashboard-chart` | 2026-10-13 | AreaChart + tooltip, StatCard trend, Modal/Dropdown slate |
| Sesi 2 — Typo + screenshot + README | `docs/sesi2` | 2026-10-13 | Fix "Kelas Kelas", 4 PNG, TODO tutup |

## 3. Sebelum vs Sesudah

| Area | Sebelum | Sesudah | Bukti |
|---|---|---|---|
| Navigasi | Menu datar 1 daftar | 4 kelompok + active state + aria-current | `docs/before-nav.png` → `docs/after-nav.png` |
| Toast | Teks polos | Ikon + tombol tutup + progress 6 detik | screenshot |
| Dashboard | Tabel kepotong di HP | Scroll-x + sticky head + min-width | screenshot HP 390px |
| Error | Bawaan Inggris | Halaman Indonesia + tombol dashboard | screenshot 404 |

## 4. Cara Verifikasi (untuk penguji)

```bash
git log --date=short --pretty="%ad %h %s" -20
composer test
npm run build
```

## Lampiran A — Riwayat Git (otomatis)

> Tempel output `scripts/git-history-report.ps1` di sini, atau lampirkan file
> `docs/history-report-YYYY-MM-DD.md` hasil generate script.

```
(paste hasil script di sini)
```

## Lampiran B — Tautan GitHub

- Pull Requests: `https://github.com/Dzacky1302/Perpustakaan-SD/pulls?q=is%3Apr`
- Releases: `https://github.com/Dzacky1302/Perpustakaan-SD/releases`
- Insights/Contributors: `https://github.com/Dzacky1302/Perpustakaan-SD/graphs/contributors`
