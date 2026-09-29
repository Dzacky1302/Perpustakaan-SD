import { usePage } from '@inertiajs/react';
import { BookOpen } from 'lucide-react';
import { useEffect, useState } from 'react';

/**
 * Layout layar sentuh untuk portal siswa.
 * Sengaja tanpa sidebar/menu apa pun supaya siswa
 * tidak pernah melihat navigasi pengelola.
 */
export default function StudentLayout({ children }) {
    const { school } = usePage().props;
    const [now, setNow] = useState(() => new Date());

    useEffect(() => {
        const timer = setInterval(() => setNow(new Date()), 30_000);

        return () => clearInterval(timer);
    }, []);

    return (
        <div className="flex min-h-screen flex-col bg-slate-100">
            <header className="bg-emerald-700 text-white">
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
