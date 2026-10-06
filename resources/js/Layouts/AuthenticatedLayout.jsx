import Dropdown from '@/Components/Dropdown';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
    AlertCircle,
    Banknote,
    BookOpen,
    Calendar,
    CheckCircle2,
    DatabaseBackup,
    FileSpreadsheet,
    Layers,
    LayoutDashboard,
    LibraryBig,
    Menu,
    Receipt,
    Repeat,
    School,
    Tag,
    Tv,
    Users,
    X,
} from 'lucide-react';

export const NAV_ITEMS = [
    { label: 'Dashboard', routeName: 'dashboard', routeKey: 'dashboard', icon: LayoutDashboard },
    { label: 'Buku Tamu', routeName: 'kiosk.*', routeKey: 'kiosk.index', icon: Tv, highlight: true, adminOnly: true },
    { label: 'Koleksi Buku', routeName: 'books.*', routeKey: 'books.index', icon: BookOpen },
    { label: 'Peminjaman', routeName: 'loans.*', routeKey: 'loans.index', icon: Repeat, exclude: ['loans.fines', 'loans.fines.excel'] },
    { label: 'Denda', routeKey: 'loans.fines', icon: Banknote, matchAll: ['loans.fines', 'loans.fines.excel'] },
    { label: 'Kuitansi', routeName: 'receipts.*', routeKey: 'receipts.index', icon: Receipt, adminOnly: true },
    { label: 'Buku Paket', routeName: 'package-loans.*', routeKey: 'package-loans.index', icon: LibraryBig },
    { label: 'Siswa', routeName: 'students.*', routeKey: 'students.index', icon: Users },
    { label: 'Kelas', routeName: 'classrooms.*', routeKey: 'classrooms.index', icon: Layers },
    { label: 'Kategori', routeName: 'categories.*', routeKey: 'categories.index', icon: Tag },
    { label: 'Laporan', routeName: 'reports.*', routeKey: 'reports.index', icon: FileSpreadsheet },
    { label: 'Cadangan', routeName: 'backups.*', routeKey: 'backups.index', icon: DatabaseBackup, adminOnly: true },
];

export default function AuthenticatedLayout({ header, children }) {
    const { auth, flash, school } = usePage().props;
    const user = auth?.user;
    const [mobileOpen, setMobileOpen] = useState(false);
    const [toast, setToast] = useState(null);

    useEffect(() => {
        if (!flash) return;

        if (flash.success) setToast({ type: 'success', text: flash.success });
        else if (flash.error) setToast({ type: 'error', text: flash.error });
        else if (flash.warning) setToast({ type: 'warning', text: flash.warning });
        else if (flash.info) setToast({ type: 'info', text: flash.info });
    }, [flash]);

    useEffect(() => {
        if (!toast) return;

        const timer = setTimeout(() => setToast(null), 6000);

        return () => clearTimeout(timer);
    }, [toast]);

    const toastStyles = {
        success: 'bg-emerald-50 border-emerald-200 text-emerald-900',
        error: 'bg-rose-50 border-rose-200 text-rose-900',
        warning: 'bg-amber-50 border-amber-200 text-amber-900',
        info: 'bg-sky-50 border-sky-200 text-sky-900',
    };

    return (
        <div className="flex min-h-screen flex-col bg-slate-100/70 text-slate-800 antialiased xl:pl-64">
            {/* Sidebar kiri (xl ke atas): daftar menu yang dulu nempel di
                navbar atas, tampilannya sama persis dengan menu versi HP.
                Di bawah xl, menu tetap lewat tombol hamburger. */}
            <Sidebar user={user} school={school} />

            <TopBar
                user={user}
                school={school}
                mobileOpen={mobileOpen}
                setMobileOpen={setMobileOpen}
            />

            {toast && (
                <div className="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div
                        className={
                            'mt-4 flex items-center justify-between gap-3 rounded-xl border p-3.5 text-sm font-medium shadow-sm ' +
                            (toastStyles[toast.type] ?? toastStyles.info)
                        }
                    >
                        <span className="flex items-center gap-2.5">
                            {toast.type === 'success' ? (
                                <CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-600" />
                            ) : (
                                <AlertCircle className="h-5 w-5 shrink-0 text-rose-600" />
                            )}
                            {toast.text}
                        </span>
                        <button
                            type="button"
                            onClick={() => setToast(null)}
                            className="rounded-md p-1 text-slate-400 transition hover:text-slate-600"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                </div>
            )}

            {header && (
                <div className="border-b border-slate-200 bg-white">
                    <div className="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">{header}</div>
                </div>
            )}

            <main className="flex-1 pb-16 pt-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">{children}</div>
            </main>

            <footer className="mt-auto border-t border-slate-200 bg-white py-4 text-xs text-slate-500">
                <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 sm:flex-row sm:px-6 lg:px-8">
                    <p>
                        © {new Date().getFullYear()}{' '}
                        <strong className="text-slate-700">{school?.name ?? 'Perpustakaan SD'}</strong> — Sistem
                        Informasi Perpustakaan Sekolah
                    </p>
                    <p className="text-slate-400">
                        NPSN {school?.npsn ?? '-'} • Tahun Ajaran {school?.academic_year ?? '2025/2026'}
                    </p>
                </div>
            </footer>
        </div>
    );
}
/**
 * Menu yang boleh dilihat pengguna saat ini.
 * "Cadangan" hanya untuk pustakawan, kepala sekolah tidak boleh mengelolanya.
 */
export function useNavItems() {
    const { auth } = usePage().props;

    return NAV_ITEMS.filter((item) => !item.adminOnly || auth?.can_manage !== false);
}

/**
 * Apakah sebuah menu sedang aktif.
 *
 * - routeName : pola Ziggy, misal "loans.*"
 * - matchAll  : daftar nama route yang menandai menu aktif
 * - exclude   : nama route yang justru mematikan menu tsb
 */
export function isNavActive(item) {
    if (item.exclude?.some((name) => route().current(name))) {
        return false;
    }

    if (item.matchAll) {
        return item.matchAll.some((name) => route().current(name));
    }

    return route().current(item.routeName);
}

function TopBar({ user, school, mobileOpen, setMobileOpen }) {
    const { auth } = usePage().props;
    const canManage = auth?.can_manage !== false;

    return (
        <header className="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur xl:hidden">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="flex h-16 items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Link href={route('dashboard')} className="group flex items-center gap-2.5">
                            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white shadow-md shadow-emerald-600/20 transition group-hover:scale-105">
                                <School className="h-5 w-5" />
                            </span>
                            <span className="leading-tight">
                                <span className="flex items-center gap-1 text-base font-extrabold tracking-tight text-slate-900 sm:text-lg">
                                    Pustaka<span className="text-emerald-600">SD</span>
                                </span>
                                <span className="hidden max-w-[220px] truncate text-[11px] text-slate-500 sm:block">
                                    {school?.name ?? 'Perpustakaan Sekolah Dasar'}
                                </span>
                            </span>
                        </Link>

                        <span className="ml-3 hidden items-center gap-1.5 border-l border-slate-200 pl-4 text-xs text-slate-500 lg:flex">
                            <Calendar className="h-3.5 w-3.5 text-slate-400" />
                            T.A. <strong className="text-slate-700">{school?.academic_year ?? '2025/2026'}</strong>
                        </span>
                    </div>

                    <div className="hidden items-center gap-2 sm:flex">
                        {canManage && (
                            <Link
                                href={route('kiosk.index')}
                                className="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-800 transition hover:bg-amber-100 xl:hidden"
                            >
                                <Tv className="h-3.5 w-3.5" />
                                Buku Tamu
                            </Link>
                        )}

                        <Dropdown>
                            <Dropdown.Trigger>
                                <button
                                    type="button"
                                    className="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    <span className="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold uppercase text-emerald-700">
                                        {user?.name?.charAt(0) ?? 'P'}
                                    </span>
                                    <span className="max-w-[120px] truncate">{user?.name}</span>
                                    <svg className="h-3.5 w-3.5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            fillRule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clipRule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </Dropdown.Trigger>
                            <Dropdown.Content>
                                <div className="border-b border-slate-100 px-4 py-2 text-xs">
                                    <p className="font-semibold text-slate-800">{user?.name}</p>
                                    <p className="truncate text-slate-500">{user?.email}</p>
                                </div>
                                <Dropdown.Link href={route('profile.edit')}>Profil Saya</Dropdown.Link>
                                <Dropdown.Link href={route('logout')} method="post" as="button">
                                    Keluar
                                </Dropdown.Link>
                            </Dropdown.Content>
                        </Dropdown>
                    </div>

                    <button
                        type="button"
                        onClick={() => setMobileOpen((previous) => !previous)}
                        className="inline-flex items-center justify-center rounded-lg p-2 text-slate-600 transition hover:bg-slate-100 focus:outline-none xl:hidden"
                    >
                        {mobileOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
                    </button>
                </div>
            </div>

            {mobileOpen && <MobileMenu onNavigate={() => setMobileOpen(false)} />}
        </header>
    );
}



/**
 * Sidebar kiri untuk layar lebar (xl ke atas).
 *
 * Isinya daftar menu yang SAMA PERSIS dengan menu versi HP (MobileMenu),
 * jadi tampilan konsisten di semua ukuran layar — cuma dipindah dari
 * navbar atas ke sebelah kiri. Di bawah xl komponen ini tidak dirender,
 * dan menu dibuka lewat tombol hamburger seperti biasa.
 */
function Sidebar({ user, school }) {
    const { auth } = usePage().props;
    const navItems = useNavItems();

    return (
        <aside className="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-slate-200 bg-white xl:flex">
            <div className="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white shadow-md shadow-emerald-600/20">
                    <School className="h-5 w-5" />
                </span>
                <div className="min-w-0">
                    <p className="truncate text-base font-extrabold leading-tight text-slate-900">
                        Pustaka<span className="text-emerald-600">SD</span>
                    </p>
                    <p className="truncate text-[11px] text-slate-500">
                        {school?.name ?? 'Perpustakaan Sekolah Dasar'}
                    </p>
                </div>
            </div>

            <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                {navItems.map((item) => {
                    const Icon = item.icon;
                    const active = isNavActive(item);

                    return (
                        <Link
                            key={item.label}
                            href={route(item.routeKey)}
                            className={
                                'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition ' +
                                (active
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900')
                            }
                        >
                            <Icon
                                className={
                                    'h-4 w-4 ' + (active ? 'text-emerald-600' : 'text-slate-400')
                                }
                            />
                            {item.label}
                        </Link>
                    );
                })}

                <p className="px-3 pb-1 pt-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                    T.A. {school?.academic_year ?? '2025/2026'}
                </p>
            </nav>

            <div className="border-t border-slate-100 px-3 py-3">
                <p className="truncate px-3 pb-0.5 text-xs font-bold text-slate-800">
                    {user?.name}
                </p>
                <p className="truncate px-3 pb-2 text-[11px] text-slate-400">
                    {auth?.role_label ?? 'Petugas'}
                </p>
                <Link
                    href={route('profile.edit')}
                    className="block rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100"
                >
                    Profil Saya
                </Link>
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="w-full rounded-lg px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50"
                >
                    Keluar
                </Link>
            </div>
        </aside>
    );
}

function MobileMenu({ onNavigate }) {
    const { auth } = usePage().props;
    const navItems = useNavItems();

    return (
        <div className="space-y-1 border-t border-slate-200 bg-white px-4 pb-4 pt-3 xl:hidden">
            {navItems.map((item) => {
                const Icon = item.icon;
                const active = isNavActive(item);

                return (
                    <Link
                        key={item.label}
                        href={route(item.routeKey)}
                        onClick={onNavigate}
                        className={
                            'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium ' +
                            (active ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-100')
                        }
                    >
                        <Icon className="h-4 w-4" />
                        {item.label}
                    </Link>
                );
            })}

            <div className="mt-2 border-t border-slate-200 pt-3">
                <p className="px-3 pb-1 text-xs font-bold uppercase tracking-wider text-slate-400">
                    {auth?.user?.name}
                </p>
                <Link
                    href={route('profile.edit')}
                    onClick={onNavigate}
                    className="block rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100"
                >
                    Profil Saya
                </Link>
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    onClick={onNavigate}
                    className="w-full rounded-lg px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50"
                >
                    Keluar
                </Link>
            </div>
        </div>
    );
}

