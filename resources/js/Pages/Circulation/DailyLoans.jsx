import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import BorrowModal from '@/Components/BorrowModal';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import { Badge, Button, Card, EmptyState, Field, Input, PageHeader, Select, StatCard, cn } from '@/Components/ui';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { CanManage } from '@/hooks/useCanManage';
import {
    AlertOctagon,
    Banknote,
    BookMarked,
    BookOpen,
    CalendarClock,
    CheckCircle2,
    Download,
    FileText,
    Printer,
    RotateCcw,
    Search,
    Trash2,
    X,
} from 'lucide-react';

const STATUS_TABS = [
    { value: 'aktif', label: 'Sedang Dipinjam' },
    { value: 'terlambat', label: 'Terlambat' },
    { value: 'kembali', label: 'Sudah Kembali' },
    { value: 'hilang', label: 'Hilang' },
    { value: 'semua', label: 'Semua Data' },
];

export default function DailyLoansIndex({ loans, filters = {}, summary, students = [], loanDays = 7, maxLoans = 2 }) {
    const today = new Date().toISOString().slice(0, 10);

    const [filterState, setFilterState] = useState({
        status: filters.status ?? 'aktif',
        search: filters.search ?? '',
        from: filters.from ?? '',
        to: filters.to ?? '',
    });
    const [returning, setReturning] = useState(null);
    // Dashboard memakai ?action=catat supaya tombol "Catat Peminjaman"
    // benar-benar membuka formulir, bukan sekadar membuka halaman.
    const [borrowing, setBorrowing] = useState(filters.action === 'catat');

    const returnForm = useForm({ returned_at: today, notes: '' });

    const applyFilters = (next = {}) => {
        const params = { ...filterState, ...next };

        setFilterState(params);
        router.get(route('loans.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const submitReturn = (event) => {
        event.preventDefault();

        returnForm.patch(route('loans.return', returning.id), {
            preserveScroll: true,
            onSuccess: () => {
                setReturning(null);
                returnForm.reset();
            },
        });
    };

    const markLost = (loan) => {
        if (confirm(`Tandai buku "${loan.book}" sebagai hilang?`)) {
            router.patch(route('loans.lost', loan.id), {}, { preserveScroll: true });
        }
    };

    const remove = (loan) => {
        if (confirm('Hapus catatan peminjaman ini?')) {
            router.delete(route('loans.destroy', loan.id), { preserveScroll: true });
        }
    };

    const exportUrl = `${route('loans.export')}?${new URLSearchParams(filterState).toString()}`;

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Peminjaman Buku Koleksi"
                    subtitle="Monitoring peminjaman, pengembalian, dan buku hilang"
                >
                        <div className="flex flex-wrap items-center gap-2">
                            <CanManage>
                                <Button size="sm" onClick={() => setBorrowing(true)}>
                                    <BookMarked className="h-3.5 w-3.5" />
                                    Catat Peminjaman
                                </Button>
                            </CanManage>
                            <a
                                href={exportUrl}
                                className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                            >
                                <Download className="h-3.5 w-3.5" />
                                Ekspor Excel
                            </a>
                            <a
                                href={route('loans.fines')}
                                className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                            >
                                <Banknote className="h-3.5 w-3.5" />
                                Denda
                                {summary?.fine_unpaid > 0 && (
                                    <span className="rounded-full bg-rose-600 px-1.5 py-0.5 text-xs text-white">
                                        {summary.fine_unpaid_label}
                                    </span>
                                )}
                            </a>
                        </div>
                </PageHeader>
            }
        >
            <Head title="Peminjaman Harian" />

            <div className="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-5">
                <StatCard label="Sedang Dipinjam" value={summary?.active ?? 0} icon={BookMarked} tone="slate" />
                <StatCard label="Terlambat" value={summary?.overdue ?? 0} icon={CalendarClock} tone="rose" valueTone="rose" />
                <StatCard label="Dipinjam Hari Ini" value={summary?.borrowed_today ?? 0} icon={BookOpen} tone="sky" valueTone="sky" />
                <StatCard label="Kembali Hari Ini" value={summary?.returned_today ?? 0} icon={CheckCircle2} tone="emerald" valueTone="emerald" />
                <StatCard
                    label="Denda Belum Dibayar"
                    value={summary?.fine_unpaid_label ?? 'Rp0'}
                    valueSize="text-lg sm:text-xl"
                    hint={`${summary?.students_blocked ?? 0} siswa diblokir`}
                    icon={Banknote}
                    tone="amber"
                    valueTone="amber"
                />
            </div>
            <Card bodyClass="p-0">
                <div className="flex flex-wrap items-center gap-2 border-b border-slate-100 px-5 py-3.5">
                    {STATUS_TABS.map((tab) => (
                        <button
                            key={tab.value}
                            type="button"
                            onClick={() => applyFilters({ status: tab.value })}
                            className={cn(
                                'rounded-lg px-3 py-1.5 text-xs font-bold transition',
                                filterState.status === tab.value
                                    ? 'bg-slate-900 text-white'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
                            )}
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        applyFilters();
                    }}
                    className="grid gap-3 border-b border-slate-100 px-5 py-4 lg:grid-cols-12"
                >
                    <Field label="Cari Siswa / Buku" className="lg:col-span-4">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                            <Input
                                value={filterState.search}
                                onChange={(event) =>
                                    setFilterState({ ...filterState, search: event.target.value })
                                }
                                placeholder="Nama siswa, NISN, judul, atau kode buku"
                                className="pl-9"
                            />
                        </div>
                    </Field>
                    <Field label="Dari Tanggal" className="lg:col-span-3">
                        <Input
                            type="date"
                            value={filterState.from}
                            onChange={(event) => applyFilters({ from: event.target.value })}
                        />
                    </Field>
                    <Field label="Sampai Tanggal" className="lg:col-span-3">
                        <Input
                            type="date"
                            value={filterState.to}
                            onChange={(event) => applyFilters({ to: event.target.value })}
                        />
                    </Field>
                    <div className="flex items-end gap-2 lg:col-span-2">
                        <Button type="submit" className="flex-1">
                            <Search className="h-3.5 w-3.5" />
                            Filter
                        </Button>
                        <Button
                            variant="secondary"
                            type="button"
                            title="Reset filter"
                            onClick={() => {
                                const cleared = { status: 'aktif', search: '', from: '', to: '' };
                                setFilterState(cleared);
                                router.get(route('loans.index'), cleared, { preserveScroll: true });
                            }}
                        >
                            <RotateCcw className="h-3.5 w-3.5" />
                        </Button>
                    </div>
                </form>
                {loans.data.length === 0 ? (
                    <div className="p-5">
                        <EmptyState
                            icon={BookOpen}
                            title="Tidak ada data peminjaman"
                            description="Ubah filter status untuk melihat catatan peminjaman lainnya."
                        />
                    </div>
                ) : (
                    <>
                        <div role="region" aria-label="Tabel peminjaman" tabIndex={0} className="w-full overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-4 py-2.5 font-bold">Siswa</th>
                                        <th className="px-3 py-2.5 font-bold">Buku</th>
                                        <th className="hidden px-3 py-2.5 font-bold xl:table-cell">Pinjam</th>
                                        <th className="px-3 py-2.5 font-bold">Jatuh Tempo</th>
                                        <th className="hidden px-3 py-2.5 font-bold xl:table-cell">Kembali</th>
                                        <th className="px-3 py-2.5 font-bold">Denda</th>
                                        <th className="px-3 py-2.5 font-bold">Status</th>
                                        <th className="px-4 py-2.5 text-right font-bold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {loans.data.map((loan) => (
                                        <tr
                                            key={loan.id}
                                            className={cn('hover:bg-slate-50/70', loan.is_overdue && 'bg-rose-50/40')}
                                        >
                                            <td className="px-4 py-2.5">
                                                <p className="truncate font-bold text-slate-800 max-w-[9rem]">{loan.student}</p>
                                                <p className="truncate text-xs text-slate-500 max-w-[13rem]">
                                                    {loan.classroom ?? 'Tidak tercatat'}
                                                </p>
                                            </td>
                                            <td className="px-3 py-2.5">
                                                <p className="truncate font-semibold text-slate-700 max-w-[13rem]">{loan.book}</p>
                                                <p className="truncate font-mono text-xs text-slate-500 max-w-[13rem]">
                                                    {loan.book_code}
                                                </p>
                                            </td>
                                            <td className="hidden px-3 py-2.5 text-slate-600 xl:table-cell">
                                                {loan.borrowed_at}
                                            </td>
                                            <td className="px-3 py-2.5 text-slate-600">
                                                <span className="whitespace-nowrap">{loan.due_at}</span>
                                                {loan.is_overdue && (
                                                    <span className="ml-1 whitespace-nowrap font-bold text-rose-600">
                                                        (+{loan.days_late})
                                                    </span>
                                                )}
                                            </td>
                                            <td className="hidden whitespace-nowrap px-3 py-2.5 text-slate-600 xl:table-cell">
                                                {loan.returned_at ?? '-'}
                                            </td>
                                            <td className="whitespace-nowrap px-3 py-2.5">
                                                {loan.fine > 0 ? (
                                                    <>
                                                        <span className="font-bold text-rose-700">
                                                            {loan.fine_label}
                                                        </span>
                                                        <p className="text-xs text-slate-500">
                                                            {loan.fine_paid ? (
                                                                <span className="text-emerald-600">Lunas</span>
                                                            ) : (
                                                                'Belum bayar'
                                                            )}
                                                        </p>
                                                    </>
                                                ) : (
                                                    <span className="text-slate-300">-</span>
                                                )}
                                            </td>
                                            <td className="px-3 py-2.5">
                                                {loan.status === 'dipinjam' ? (
                                                    loan.is_overdue ? (
                                                        <Badge tone="rose">
                                                            <AlertOctagon className="h-3 w-3" />
                                                            Terlambat
                                                        </Badge>
                                                    ) : (
                                                        <Badge tone="amber">
                                                            <BookMarked className="h-3 w-3" />
                                                            Dipinjam
                                                        </Badge>
                                                    )
                                                ) : loan.status === 'kembali' ? (
                                                    <Badge tone="emerald">
                                                        <CheckCircle2 className="h-3 w-3" />
                                                        Kembali
                                                    </Badge>
                                                ) : (
                                                    <Badge tone="slate">Hilang</Badge>
                                                )}
                                                {loan.notes && (
                                                    <p className="mt-1 max-w-[180px] text-xs italic text-slate-500">
                                                        {loan.notes}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-2.5">
                                                <div className="flex items-center justify-end gap-1">
                                                    <button
                                                        type="button"
                                                        onClick={() => window.open(route('loans.slip', loan.id), '_blank')}
                                                        className="rounded-md p-1.5 text-slate-500 transition hover:bg-sky-50 hover:text-sky-600"
                                                        title="Cetak surat peminjaman"
                                                    >
                                                        <Printer className="h-4 w-4" />
                                                    </button>
                                                    {loan.status !== 'dipinjam' && (
                                                        <button
                                                            type="button"
                                                            onClick={() => window.open(route('loans.slip-return', loan.id), '_blank')}
                                                            className="rounded-md p-1.5 text-slate-500 transition hover:bg-sky-50 hover:text-sky-600"
                                                            title="Cetak surat pengembalian"
                                                        >
                                                            <FileText className="h-4 w-4" />
                                                        </button>
                                                    )}
                                                    <CanManage>
                                                        {loan.status === 'dipinjam' && (
                                                            <>
                                                                <Button
                                                                    type="button"
                                                                    className="px-2.5 py-1.5"
                                                                    onClick={() => {
                                                                        setReturning(loan);
                                                                        returnForm.setData({
                                                                            returned_at: today,
                                                                            notes: '',
                                                                        });
                                                                    }}
                                                                >
                                                                    <CheckCircle2 className="h-3.5 w-3.5" />
                                                                    Kembalikan
                                                                </Button>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => markLost(loan)}
                                                                    className="rounded-md p-1.5 text-slate-500 transition hover:bg-amber-50 hover:text-amber-600"
                                                                    title="Tandai hilang"
                                                                >
                                                                    <AlertOctagon className="h-4 w-4" />
                                                                </button>
                                                            </>
                                                        )}
                                                        <button
                                                            type="button"
                                                            onClick={() => remove(loan)}
                                                            className="rounded-md p-1.5 text-slate-500 transition hover:bg-rose-50 hover:text-rose-600"
                                                            title="Hapus catatan"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    </CanManage>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <Pagination meta={loans.meta ?? loans} links={loans.links ?? []} />
                    </>
                )}

                <BorrowModal
                    show={borrowing}
                    onClose={() => setBorrowing(false)}
                    students={students}
                    loanDays={loanDays}
                    maxLoans={maxLoans}
                />
            </Card>
            <Modal show={Boolean(returning)} onClose={() => setReturning(null)} maxWidth="md">
                <form onSubmit={submitReturn}>
                    <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h3 className="text-sm font-extrabold text-slate-900">Catat Pengembalian</h3>
                        <button
                            type="button"
                            onClick={() => setReturning(null)}
                            className="rounded-md p-1.5 text-slate-500 hover:bg-slate-100"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>

                    <div className="space-y-4 px-5 py-5">
                        <div className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs">
                            <p className="font-bold text-slate-800">{returning?.book}</p>
                            <p className="text-slate-500">
                                Dipinjam oleh <strong>{returning?.student}</strong> ({returning?.classroom})
                            </p>
                            <p className="mt-1 flex items-center gap-1.5 text-slate-500">
                                <CalendarClock className="h-3.5 w-3.5" />
                                Jatuh tempo {returning?.due_at}
                                {returning?.is_overdue && (
                                    <span className="font-bold text-rose-600">
                                        — terlambat {returning?.days_late} hari
                                    </span>
                                )}
                            </p>
                            {returning?.is_overdue && (
                                <p className="mt-1 flex items-center gap-1.5 text-amber-700">
                                    <Banknote className="h-3.5 w-3.5" />
                                    Estimasi denda saat dikembalikan: {returning?.fine_label}
                                </p>
                            )}
                        </div>

                        <Field label="Tanggal Dikembalikan" error={returnForm.errors.returned_at}>
                            <Input
                                type="date"
                                value={returnForm.data.returned_at}
                                onChange={(event) => returnForm.setData('returned_at', event.target.value)}
                            />
                        </Field>

                        <Field label="Catatan Kondisi (opsional)" error={returnForm.errors.notes}>
                            <Input
                                value={returnForm.data.notes}
                                onChange={(event) => returnForm.setData('notes', event.target.value)}
                                placeholder="Misal: kondisi baik, sampul sedikit terlipat"
                            />
                        </Field>
                    </div>

                    <div className="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
                        <Button variant="secondary" type="button" onClick={() => setReturning(null)}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={returnForm.processing}>
                            <CheckCircle2 className="h-3.5 w-3.5" />
                            Simpan Pengembalian
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
