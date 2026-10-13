import { Link } from '@inertiajs/react';
import { BookOpen, Home } from 'lucide-react';

export default function Error({ status }) {
    const title = status === 404 ? 'Halaman tidak ditemukan' : `Terjadi kesalahan (${status})`;
    const description =
        status === 404
            ? 'Alamat yang kamu buka sudah dipindah atau dihapus. Balik ke dashboard atau pakai menu navigasi.'
            : 'Maaf, ada gangguan di server. Coba muat ulang halaman, kalau masih gagal hubungi pustakawan.';

    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-100/70 px-4 py-10">
            <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-card">
                <span className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <BookOpen className="h-6 w-6" />
                </span>
                <p className="text-4xl font-extrabold tracking-tight text-slate-900">{status}</p>
                <h1 className="mt-2 text-base font-bold text-slate-800">{title}</h1>
                <p className="mt-1.5 text-sm leading-relaxed text-slate-500">{description}</p>
                <div className="mt-6 flex justify-center gap-2">
                    <Link
                        href={route('dashboard')}
                        className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700"
                    >
                        <Home className="h-4 w-4" />
                        Kembali ke Dashboard
                    </Link>
                </div>
            </div>
        </div>
    );
}
