import { usePage } from '@inertiajs/react';
import { BookOpen, CheckCircle2 } from 'lucide-react';
import { useEffect, useState } from 'react';

/**
 * Layout portal siswa.
 *
 * Di layar kecil tampil seperti biasa: header hijau, isi di tengah.
 * Di layar lebar (xl ke atas) muncul sidebar kiri berisi identitas
 * sekolah, alur langkah pengisian, dan jam — supaya bentuknya konsisten
 * dengan panel petugas. Sengaja TANPA menu pengelola: siswa tidak
 * boleh melihat navigasi pustakawan.
 */
export default function StudentLayout({ children, steps = [], step = null }) {
    const { school } = usePage().props;
    const [now, setNow] = useState(() => new Date());

    useEffect(() => {
        const timer = setInterval(() => setNow(new Date()), 30_000);

        return () => clearInterval(timer);
    }, []);

    return (
        <div className="flex min-h-screen flex-col bg-slate-100 xl:pl-64">
            {/* Sidebar versi layar lebar: identitas sekolah, alur langkah,
                dan jam. Hanya isian milik siswa yang tampil di sini. */}
            <aside className="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col bg-emerald-700 px-4 py-6 text-white xl:flex">
                <div className="flex items-center gap-3 px-2">
                    <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15">
                        <BookOpen className="h-6 w-6" />
                    </span>
                    <div className="min-w-0">
                        <p className="truncate text-sm font-extrabold leading-tight">
                            {school?.library ?? 'Perpustakaan Sekolah'}
                        </p>
                        <p className="truncate text-[11px] text-emerald-100">{school?.name}</p>
                    </div>
                </div>

                {steps.length > 0 && (
                    <ol className="mt-8 space-y-1">
                        {steps.map((label, index) => {
                            const number = index + 1;
                            const current = step === number;
                            const done = typeof step === 'number' && step > number;

                            return (
                                <li
                                    key={label}
                                    className={
                                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium ' +
                                        (current
                                            ? 'bg-white/15 text-white'
                                            : done
                                              ? 'text-emerald-50'
                                              : 'text-emerald-200/70')
                                    }
                                >
                                    <span
                                        className={
                                            'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold ' +
                                            (current ? 'bg-white text-emerald-700' : 'bg-white/15')
                                        }
                                    >
                                        {done ? <CheckCircle2 className="h-3.5 w-3.5" /> : number}
                                    </span>
                                    {label}
                                </li>
                            );
                        })}
                    </ol>
                )}

                <div className="mt-auto border-t border-white/15 px-2 pt-4">
                    <p className="text-xl font-extrabold leading-tight tabular-nums">
                        {now.toLocaleTimeString('id-ID', {
                            hour: '2-digit',
                            minute: '2-digit',
                        })}
                    </p>
                    <p className="text-[11px] capitalize text-emerald-100">
                        {now.toLocaleDateString('id-ID', {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric',
                        })}
                    </p>
                </div>
            </aside>

            <header className="bg-emerald-700 text-white xl:hidden">
                <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6">
                    <div className="flex items-center gap-3">
                        <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
                            <BookOpen className="h-6 w-6" />
                        </span>
                        <div>
                            <p className="text-base font-extrabold leading-tight">
                                {school?.library ?? 'Perpustakaan Sekolah'}
                            </p>
                            <p className="text-[11px] text-emerald-100">{school?.name}</p>
                        </div>
                    </div>

                    <div className="text-right">
                        <p className="text-xl font-extrabold leading-tight tabular-nums">
                            {now.toLocaleTimeString('id-ID', {
                                hour: '2-digit',
                                minute: '2-digit',
                            })}
                        </p>
                        <p className="text-[11px] capitalize text-emerald-100">
                            {now.toLocaleDateString('id-ID', {
                                weekday: 'long',
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric',
                            })}
                        </p>
                    </div>
                </div>
            </header>

            <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-6 sm:px-6">{children}</main>

            <footer className="border-t border-slate-200 bg-white py-3 text-center text-[11px] text-slate-500">
                {school?.name} • {school?.address}
            </footer>
        </div>
    );
}
