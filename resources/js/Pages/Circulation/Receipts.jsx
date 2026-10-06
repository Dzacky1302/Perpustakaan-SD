import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Pagination from '@/Components/Pagination';
import { CanManage } from '@/hooks/useCanManage';
import { Badge, Card, EmptyState, Field, Input, PageHeader, cn } from '@/Components/ui';
import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Printer, Receipt, Search, ShieldCheck } from 'lucide-react';

const TYPE_TABS = [
    { value: '', label: 'Semua' },
    { value: 'tagihan', label: 'Tagihan' },
    { value: 'pelunasan', label: 'Pelunasan' },
];

/**
 * Arsip kuitansi denda: jumlah cetak tiap dokumen, dan pintu ke
 * halaman pengecekan kode verifikasi.
 */
export default function ReceiptsPage({ receipts, filters = {}, summary = {} }) {
    const [filterState, setFilterState] = useState({
        type: filters.type ?? '',
        search: filters.search ?? '',
    });

    const applyFilters = (next = {}) => {
        const params = { ...filterState, ...next };

        setFilterState(params);
        router.get(route('receipts.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const rows = useMemo(() => receipts.data ?? receipts, [receipts]);

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Arsip Kuitansi Denda"
                    subtitle="Kuitansi tagihan & pelunasan yang pernah dicetak"
                >
                    <a
                        href={route('receipts.verify')}
                        className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                    >
                        <ShieldCheck className="h-3.5 w-3.5" />
                        Cek Kode Verifikasi
                    </a>
                </PageHeader>
            }
        >
            <Head title="Arsip Kuitansi Denda" />

            <div className="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Total Kuitansi</p>
                    <p className="mt-1 text-xl font-extrabold text-slate-900">{summary.total ?? 0}</p>
                    <p className="mt-1 text-[11px] text-slate-500">semua jenis</p>
                </div>
                <div className="rounded-2xl border border-amber-200 bg-amber-50/60 p-4">
                    <p className="text-[11px] font-bold uppercase text-amber-700">Tagihan</p>
                    <p className="mt-1 text-xl font-extrabold text-amber-700">{summary.tagihan ?? 0}</p>
                    <p className="mt-1 text-[11px] text-amber-600">belum dibayar</p>
                </div>
                <div className="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4">
                    <p className="text-[11px] font-bold uppercase text-emerald-700">Pelunasan</p>
                    <p className="mt-1 text-xl font-extrabold text-emerald-700">{summary.pelunasan ?? 0}</p>
                    <p className="mt-1 text-[11px] text-emerald-600">sudah lunas</p>
                </div>
                <div className="rounded-2xl border border-sky-200 bg-sky-50/60 p-4">
                    <p className="text-[11px] font-bold uppercase text-sky-700">Dicetak Ulang</p>
                    <p className="mt-1 text-xl font-extrabold text-sky-700">{summary.duplikat ?? 0}</p>
                    <p className="mt-1 text-[11px] text-sky-600">lebih dari 1 salinan</p>
                </div>
            </div>

            <Card bodyClass="p-0" className="mt-4">
                <div className="flex flex-wrap items-center gap-2 border-b border-slate-100 px-5 py-3.5">
                    {TYPE_TABS.map((tab) => (
                        <button
                            key={tab.value}
                            type="button"
                            onClick={() => applyFilters({ type: tab.value })}
                            className={cn(
                                'rounded-lg px-3 py-1.5 text-[11px] font-bold transition',
                                filterState.type === tab.value
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
                        applyFilters({ search: event.currentTarget.search.value });
                    }}
                    className="flex items-end gap-2 border-b border-slate-100 px-5 py-3"
                >
                    <Field label="Pencarian" className="mb-0 flex-1">
                        <Input
                            name="search"
                            defaultValue={filterState.search}
                            placeholder="Nomor kuitansi, kode verifikasi, atau nama siswa"
                        />
                    </Field>
                    <button
                        type="submit"
                        className="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3.5 py-2 text-xs font-bold text-white transition hover:bg-slate-700"
                    >
                        <Search className="h-3.5 w-3.5" />
                        Cari
                    </button>
                </form>

                {rows.length === 0 ? (
                    <EmptyState
                        icon={Receipt}
                        title="Belum ada kuitansi"
                        description="Kuitansi terbit otomatis saat buku terlambat dikembalikan atau denda dibayar."
                    />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead>
                                <tr className="border-b border-slate-100 text-[11px] uppercase text-slate-500">
                                    <th className="px-5 py-3 font-bold">No. Kuitansi</th>
                                    <th className="px-5 py-3 font-bold">Jenis</th>
                                    <th className="px-5 py-3 font-bold">Siswa / Buku</th>
                                    <th className="px-5 py-3 font-bold">Jumlah</th>
                                    <th className="px-5 py-3 font-bold">Kode</th>
                                    <th className="px-5 py-3 font-bold">Terbit</th>
                                    <th className="px-5 py-3 text-right font-bold">Cetak</th>
                                    <th className="px-5 py-3" />
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((row) => (
                                    <tr key={row.id} className="border-b border-slate-50 hover:bg-slate-50/60">
                                        <td className="px-5 py-3 font-semibold text-slate-900">{row.receipt_number}</td>
                                        <td className="px-5 py-3">
                                            <Badge tone={row.type === 'pelunasan' ? 'emerald' : 'amber'}>
                                                {row.type === 'pelunasan' ? 'Pelunasan' : 'Tagihan'}
                                            </Badge>
                                        </td>
                                        <td className="px-5 py-3">
                                            <p className="font-semibold text-slate-900">{row.student ?? '-'}</p>
                                            <p className="text-[11px] text-slate-500">{row.book ?? '-'}</p>
                                        </td>
                                        <td className="px-5 py-3 font-semibold text-slate-900">{row.amount_label}</td>
                                        <td className="px-5 py-3">
                                            <code className="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] font-bold tracking-widest text-slate-700">
                                                {row.verification_code}
                                            </code>
                                        </td>
                                        <td className="px-5 py-3 text-slate-600">{row.issued_at ?? '-'}</td>
                                        <td className="px-5 py-3 text-right">
                                            <span
                                                className={cn(
                                                    'inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-bold',
                                                    row.print_count > 1
                                                        ? 'bg-sky-100 text-sky-700'
                                                        : 'bg-slate-100 text-slate-600',
                                                )}
                                                title={
                                                    row.print_count > 1
                                                        ? 'Dicetak lebih dari satu kali, salinan ke atas ditandai SALINAN'
                                                        : 'Baru dicetak satu kali'
                                                }
                                            >
                                                <Printer className="h-3 w-3" />
                                                {row.print_count}x
                                            </span>
                                        </td>
                                        <td className="px-5 py-3 text-right">
                                            <CanManage>
                                                <button
                                                    type="button"
                                                    onClick={() => window.open(route('loans.slip-fine', row.loan_id), '_blank')}
                                                    className="rounded-md p-1.5 text-slate-400 transition hover:bg-sky-50 hover:text-sky-600"
                                                    title="Cetak ulang"
                                                >
                                                    <Printer className="h-4 w-4" />
                                                </button>
                                            </CanManage>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={receipts.links ?? []} />
            </Card>
        </AuthenticatedLayout>
    );
}
