import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import { Badge, Button, Card, EmptyState, Field, Input, PageHeader, cn } from '@/Components/ui';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { CanManage } from '@/hooks/useCanManage';
import { Banknote, CheckCircle2, Download, Info, Receipt, RotateCcw, Search, TrendingUp, X } from 'lucide-react';

const STATUS_TABS = [
    { value: 'belum', label: 'Belum Dibayar' },
    { value: 'lunas', label: 'Sudah Lunas' },
    { value: 'semua', label: 'Semua' },
];

export default function FinesIndex({ fines, filters = {}, summary = {} }) {
    const [filterState, setFilterState] = useState({
        status: filters.status ?? 'belum',
        search: filters.search ?? '',
    });
    const [paying, setPaying] = useState(null);
    const [notes, setNotes] = useState('');

    const applyFilters = (next = {}) => {
        const params = { ...filterState, ...next };

        setFilterState(params);
        router.get(route('loans.fines'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const payFine = () => {
        router.patch(
            route('loans.fine.pay', paying.id),
            { fine_notes: notes },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setPaying(null);
                    setNotes('');
                },
            },
        );
    };

    const cancelFine = (fine) => {
        if (confirm('Batalkan pencatatan pembayaran denda ini?')) {
            router.delete(route('loans.fine.cancel', fine.id), {}, { preserveScroll: true });
        }
    };

    const exportUrl = `${route('loans.fines.excel')}?${new URLSearchParams(filterState).toString()}`;
    const rows = fines.data ?? fines;

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Denda Keterlambatan"
                    subtitle={`${summary.rate ?? 'Rp500'} per hari sekolah · maksimal ${summary.max ?? 'Rp10.000'} per buku`}
                >
                    <a
                        href={exportUrl}
                        className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                    >
                        <Download className="h-3.5 w-3.5" />
                        Ekspor Excel
                    </a>
                </PageHeader>
            }
        >
            <Head title="Denda Keterlambatan" />

            <div className="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div className="rounded-2xl border border-rose-200 bg-rose-50/60 p-4">
                    <p className="text-[11px] font-bold uppercase text-rose-700">Belum Dibayar</p>
                    <p className="mt-1 text-xl font-extrabold text-rose-700">{summary.unpaid_label ?? 'Rp0'}</p>
                    <p className="mt-1 text-[11px] text-rose-600">{summary.count_unpaid ?? 0} buku</p>
                </div>
                <div className="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4">
                    <p className="text-[11px] font-bold uppercase text-emerald-700">Sudah Diterima</p>
                    <p className="mt-1 text-xl font-extrabold text-emerald-700">
                        {summary.collected_label ?? 'Rp0'}
                    </p>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Tarif per Hari</p>
                    <p className="mt-1 text-xl font-extrabold text-slate-900">{summary.rate ?? 'Rp500'}</p>
                    <p className="mt-1 text-[11px] text-slate-500">per hari sekolah</p>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Batas per Buku</p>
                    <p className="mt-1 text-xl font-extrabold text-slate-900">{summary.max ?? 'Rp10.000'}</p>
                </div>
            </div>

            <Card bodyClass="p-0" className="mt-4">
                <div className="flex gap-2.5 border-b border-slate-100 px-5 py-3.5">
                    <Info className="mt-0.5 h-4 w-4 shrink-0 text-sky-600" />
                    <p className="text-[11px] leading-relaxed text-slate-600">
                        Denda dihitung <strong>per hari sekolah</strong> (Senin–Jumat). Sabtu, Minggu, dan hari
                        libur nasional <strong>tidak menambah denda</strong>. Karena itu buku yang jatuh tempo
                        Jumat boleh dikembalikan hari Senin berikutnya tanpa denda.
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-2 border-b border-slate-100 px-5 py-3.5">
                    {STATUS_TABS.map((tab) => (
                        <button
                            key={tab.value}
                            type="button"
                            onClick={() => applyFilters({ status: tab.value })}
                            className={cn(
                                'rounded-lg px-3 py-1.5 text-[11px] font-bold transition',
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
                    className="border-b border-slate-100 px-5 py-4"
                >
                    <Field label="Cari Siswa / Buku" className="max-w-md">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                value={filterState.search}
                                onChange={(event) =>
                                    setFilterState({ ...filterState, search: event.target.value })
                                }
                                placeholder="Nama siswa, NISN, atau judul buku"
                                className="pl-9"
                            />
                        </div>
                    </Field>
                </form>

                {rows?.length > 0 ? (
                    <>
                        <div className="w-full overflow-x-auto">
                            <table className="w-full">
                                <thead className="bg-slate-50 text-left text-[11px] uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-4 py-2.5">Siswa</th>
                                        <th className="px-3 py-2.5">Buku</th>
                                        <th className="px-3 py-2.5 text-center">Hari Telat</th>
                                        <th className="px-3 py-2.5 text-right">Denda</th>
                                        <th className="px-3 py-2.5 text-center">Status</th>
                                        <th className="px-4 py-2.5 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {rows.map((fine) => (
                                        <tr key={fine.id} className="text-xs text-slate-700">
                                            <td className="px-4 py-2.5">
                                                <p className="truncate font-semibold text-slate-800 max-w-[10rem]">{fine.student}</p>
                                                <p className="truncate text-[11px] text-slate-500 max-w-[14rem]">
                                                    {fine.classroom ?? 'Tidak tercatat'} · {fine.nisn}
                                                </p>
                                            </td>
                                            <td className="px-3 py-2.5">
                                                <p className="truncate text-slate-700 max-w-[14rem]">{fine.book}</p>
                                                <p className="truncate text-[11px] text-slate-500 max-w-[14rem]">
                                                    Kembali {fine.returned_at ?? '-'}
                                                </p>
                                            </td>
                                            <td className="px-3 py-2.5 text-center font-semibold">{fine.days_late}</td>
                                            <td className="whitespace-nowrap px-3 py-2.5 text-right font-bold text-rose-700">
                                                {fine.fine_label}
                                            </td>
                                            <td className="px-3 py-2.5 text-center">
                                                {fine.paid ? (
                                                    <Badge tone="emerald">
                                                        <CheckCircle2 className="h-3 w-3" />
                                                        Lunas
                                                    </Badge>
                                                ) : (
                                                    <Badge tone="rose">Belum</Badge>
                                                )}
                                            </td>
                                            <FineActions fine={fine} onPay={setPaying} onCancel={cancelFine} />
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <Pagination meta={fines.meta ?? fines} links={fines.links ?? []} />
                    </>
                ) : (
                    <div className="p-5">
                        <EmptyState
                            icon={TrendingUp}
                            title="Tidak ada data denda"
                            description="Belum ada denda keterlambatan yang tercatat pada filter ini."
                        />
                    </div>
                )}

                <Modal show={Boolean(paying)} onClose={() => setPaying(null)} maxWidth="md">
                    {paying && (
                        <div>
                            <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                                <h3 className="text-sm font-extrabold text-slate-900">Catat Pembayaran Denda</h3>
                                <button
                                    type="button"
                                    onClick={() => setPaying(null)}
                                    className="rounded-md p-1.5 text-slate-400 hover:bg-slate-100"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            </div>

                            <div className="space-y-4 px-5 py-5">
                                <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs">
                                    <p className="font-bold text-slate-800">{paying.student}</p>
                                    <p className="text-slate-500">{paying.book}</p>
                                    <p className="mt-1.5 text-sm font-extrabold text-rose-700">
                                        {paying.fine_label}
                                        <span className="ml-1.5 text-[11px] font-normal text-slate-600">
                                            ({paying.days_late} hari terlambat)
                                        </span>
                                    </p>
                                </div>

                                <Field label="Catatan (opsional)">
                                    <Input
                                        value={notes}
                                        onChange={(event) => setNotes(event.target.value)}
                                        placeholder="Misal: dibayar tunai oleh wali kelas"
                                    />
                                </Field>
                            </div>

                            <div className="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
                                <Button variant="secondary" type="button" onClick={() => setPaying(null)}>
                                    Batal
                                </Button>
                                <Button type="button" onClick={payFine}>
                                    <CheckCircle2 className="h-3.5 w-3.5" />
                                    Simpan Pembayaran
                                </Button>
                            </div>
                        </div>
                    )}
                </Modal>
            </Card>
        </AuthenticatedLayout>
    );
}

function FineActions({ fine, onPay, onCancel }) {
    return (
        <td className="px-5 py-3">
            <div className="flex items-center justify-end gap-1.5">
                {fine.paid ? (
                    <>
                        <button
                            type="button"
                            onClick={() => window.open(route('loans.slip-fine', fine.id), '_blank')}
                            className="rounded-md p-1.5 text-slate-400 transition hover:bg-sky-50 hover:text-sky-600"
                            title="Cetak kuitansi"
                        >
                            <Receipt className="h-4 w-4" />
                        </button>
                        <CanManage>
                            <button
                                type="button"
                                onClick={() => onCancel(fine)}
                                className="rounded-md p-1.5 text-slate-400 transition hover:bg-amber-50 hover:text-amber-600"
                                title="Batalkan pembayaran"
                            >
                                <RotateCcw className="h-4 w-4" />
                            </button>
                        </CanManage>
                    </>
                ) : (
                    <CanManage>
                        <Button type="button" className="px-2.5 py-1.5" onClick={() => onPay(fine)}>
                            <Banknote className="h-3.5 w-3.5" />
                            Bayar
                        </Button>
                    </CanManage>
                )}
            </div>
        </td>
    );
}