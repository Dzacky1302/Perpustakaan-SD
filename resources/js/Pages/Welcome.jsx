import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpen,
    FileSpreadsheet,
    LibraryBig,
    School,
    ShieldCheck,
    Tv,
} from 'lucide-react';

const FEATURES = [
    {
        icon: Tv,
        title: 'Buku Tamu Digital',
        description:
            'Kios layar sentuh: siswa memilih kelas dan namanya sendiri, kunjungan tercatat otomatis beserta keperluan dan waktu datang.',
    },
    {
        icon: LibraryBig,
        title: 'Distribusi Buku Paket',
        description:
            'Matriks per siswa untuk Kurikulum Merdeka. Satu klik membagikan seluruh buku paket ke satu kelas, lengkap dengan checklist pengembalian.',
    },
    {
        icon: BookOpen,
        title: 'Sirkulasi Koleksi',
        description:
            'Peminjaman dan pengembalian buku bacaan dengan pemantauan jatuh tempo otomatis serta penandaan buku hilang.',
    },
    {
        icon: FileSpreadsheet,
        title: 'Laporan Siap Cetak',
        description:
            'Rekap PDF ber-kop sekolah dan ekspor Excel untuk buku tamu, buku paket, peminjaman, hingga Surat Bebas Pustaka.',
    },
];

export default function Welcome({ canLogin, canRegister, appName }) {
    const { school } = usePage().props;

    return (
        <>
            <Head title="Sistem Informasi Perpustakaan Sekolah" />

            <div className="min-h-screen bg-slate-50 text-slate-800 antialiased">
                <header className="border-b border-slate-200 bg-white">
                    <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                        <div className="flex items-center gap-2.5">
                            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white">
                                <School className="h-5 w-5" />
                            </span>
                            <span className="leading-tight">
                                <span className="block text-lg font-extrabold tracking-tight text-slate-900">
                                    Pustaka<span className="text-emerald-600">SD</span>
                                </span>
                                <span className="block text-[11px] text-slate-500">
                                    {school?.name ?? appName}
                                </span>
                            </span>
                        </div>

                        <nav className="flex items-center gap-2">
                            {canLogin && (
                                <Link
                                    href={route('login')}
                                    className="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                                >
                                    Masuk
                                </Link>
                            )}
                            {canRegister && (
                                <Link
                                    href={route('register')}
                                    className="rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white transition hover:bg-emerald-700"
                                >
                                    Daftar
                                </Link>
                            )}
                        </nav>
                    </div>
                </header>
                <section className="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
                    <div className="grid items-center gap-10 lg:grid-cols-2">
                        <div>
                            <span className="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-700">
                                <ShieldCheck className="h-3.5 w-3.5" />
                                Tahun Ajaran {school?.academic_year ?? '2025/2026'}
                            </span>
                            <h1 className="mt-4 text-3xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-4xl">
                                Kelola perpustakaan sekolah dasar tanpa buku besar manual
                            </h1>
                            <p className="mt-4 text-sm leading-relaxed text-slate-600">
                                {appName} menggantikan buku tamu kertas, kartu peminjaman, dan rekap buku paket yang
                                ditulis tangan. Pustakawan cukup mengoperasikan satu aplikasi web: siswa mencatat
                                kunjungan sendiri, buku paket dibagikan per kelas dalam satu klik, dan seluruh laporan
                                siap dicetak ber-kop sekolah.
                            </p>

                            <div className="mt-6 flex flex-wrap items-center gap-3">
                                <Link
                                    href={route('peminjaman.index')}
                                    className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700"
                                >
                                    Masuk sebagai Siswa
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                                <a
                                    href="#fitur"
                                    className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100"
                                >
                                    Lihat Fitur
                                </a>
                            </div>

                            <dl className="mt-8 grid grid-cols-3 gap-4 border-t border-slate-200 pt-6">
                                <div>
                                    <dt className="text-[11px] font-bold uppercase text-slate-500">Kelas</dt>
                                    <dd className="text-lg font-extrabold text-slate-900">Kelas 1 – 6</dd>
                                </div>
                                <div>
                                    <dt className="text-[11px] font-bold uppercase text-slate-500">Koleksi</dt>
                                    <dd className="text-lg font-extrabold text-slate-900">Paket &amp; Bacaan</dd>
                                </div>
                                <div>
                                    <dt className="text-[11px] font-bold uppercase text-slate-500">Laporan</dt>
                                    <dd className="text-lg font-extrabold text-slate-900">PDF &amp; Excel</dd>
                                </div>
                            </dl>
                        </div>

                        <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <p className="text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                Identitas Perpustakaan
                            </p>
                            <div className="mt-4 space-y-3 text-xs">
                                <div className="rounded-xl bg-slate-50 px-4 py-3">
                                    <p className="font-bold text-slate-800">
                                        {school?.library ?? 'Perpustakaan Sekolah'}
                                    </p>
                                    <p className="text-slate-500">{school?.name ?? '-'}</p>
                                </div>
                                <div className="rounded-xl bg-slate-50 px-4 py-3">
                                    <p className="font-bold text-slate-800">NPSN {school?.npsn ?? '-'}</p>
                                    <p className="text-slate-500">{school?.address ?? '-'}</p>
                                </div>
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="rounded-xl bg-slate-50 px-4 py-3">
                                        <p className="text-[10px] font-bold uppercase text-slate-500">
                                            Kepala Sekolah
                                        </p>
                                        <p className="mt-0.5 font-bold text-slate-800">
                                            {school?.headmaster ?? '-'}
                                        </p>
                                    </div>
                                    <div className="rounded-xl bg-slate-50 px-4 py-3">
                                        <p className="text-[10px] font-bold uppercase text-slate-500">Pustakawan</p>
                                        <p className="mt-0.5 font-bold text-slate-800">
                                            {school?.librarian ?? '-'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <section id="fitur" className="border-t border-slate-200 bg-white py-14">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                        <h2 className="text-xl font-extrabold tracking-tight text-slate-900">
                            Empat pekerjaan pustakawan yang disederhanakan
                        </h2>
                        <p className="mt-2 max-w-2xl text-sm text-slate-500">
                            Dirancang khusus untuk rutinitas perpustakaan sekolah dasar di Indonesia.
                        </p>

                        <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {FEATURES.map((feature) => {
                                const Icon = feature.icon;

                                return (
                                    <div
                                        key={feature.title}
                                        className="rounded-2xl border border-slate-200 p-5 transition hover:border-emerald-300 hover:shadow-sm"
                                    >
                                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                                            <Icon className="h-5 w-5" />
                                        </span>
                                        <h3 className="mt-3 text-sm font-extrabold text-slate-900">
                                            {feature.title}
                                        </h3>
                                        <p className="mt-1.5 text-xs leading-relaxed text-slate-500">
                                            {feature.description}
                                        </p>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </section>

                <footer className="border-t border-slate-200 bg-slate-50 py-6">
                    <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 text-[11px] text-slate-500 sm:flex-row sm:px-6 lg:px-8">
                        <p>
                            © {new Date().getFullYear()} {appName} — {school?.name ?? 'Perpustakaan Sekolah Dasar'}
                        </p>
                        <p>
                            {school?.library ?? 'Perpustakaan Sekolah'} • NPSN {school?.npsn ?? '-'}
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
