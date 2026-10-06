import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge, Card, EmptyState, Field, Input, PageHeader, cn } from '@/Components/ui';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { CanManage } from '@/hooks/useCanManage';
import { Printer, ShieldCheck, ShieldX } from 'lucide-react';

/**
 * Pengecekan kode verifikasi kuitansi. Kode dicetak di kuitansi;
 * petugas mengetiknya di sini untuk memastikan kuitansi itu asli
 * dan melihat riwayat cetaknya.
 */
export default function VerifyReceiptPage({ code, notFound, receipt }) {
    const [value, setValue] = useState(code ?? '');

    const submit = (event) => {
        event.preventDefault();
        router.get(route('receipts.verify'), { code: value }, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Cek Kode Verifikasi"
                    subtitle="Masukkan kode 8 karakter yang tercetak di kuitansi denda"
                />
            }
        >
            <Head title="Cek Kode Verifikasi" />

            <Card className="mx-auto max-w-2xl">
                <form onSubmit={submit} className="flex items-end gap-2">
                    <Field
                        label="Kode Verifikasi"
                        hint="Contoh: KT7M2QD9 · huruf besar/kecil sama saja"
                        className="mb-0 flex-1"
                    >
                        <Input
                            value={value}
                            onChange={(event) => setValue(event.target.value.toUpperCase())}
                            placeholder="KT7M2QD9"
                            className="font-mono tracking-[0.3em]"
                            autoFocus
                        />
                    </Field>
                    <button
                        type="submit"
                        className="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-700"
                    >
                        <ShieldCheck className="h-3.5 w-3.5" />
                        Periksa
                    </button>
                </form>
            </Card>

            {notFound && (
                <Card className="mx-auto mt-4 max-w-2xl border-rose-200 bg-rose-50/60">
                    <div className="flex items-start gap-3">
                        <ShieldX className="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
                        <div>
                            <p className="text-sm font-bold text-rose-800">Kode tidak ditemukan</p>
                            <p className="mt-1 text-xs text-rose-700">
                                Pastikan kode ditulis benar tanpa spasi. Kode hanya terdiri dari 8 karakter.
                            </p>
                        </div>
                    </div>
                </Card>
            )}

            {receipt && (
                <Card className="mx-auto mt-4 max-w-2xl">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div>
                            <p className="text-[11px] font-bold uppercase text-slate-500">{receipt.type_label}</p>
                            <p className="mt-0.5 text-lg font-extrabold text-slate-900">{receipt.receipt_number}</p>
                        </div>
                        <Badge tone={receipt.type === 'pelunasan' ? 'emerald' : 'amber'}>
                            {receipt.type === 'pelunasan' ? 'Sudah Lunas' : 'Belum Dibayar'}
                        </Badge>
                    </div>

                    <dl className="grid grid-cols-1 gap-x-6 gap-y-3 py-4 text-xs sm:grid-cols-2">
                        <Detail label="Siswa" value={receipt.student} />
                        <Detail label="NISN" value={receipt.nisn} />
                        <Detail label="Buku" value={receipt.book} />
                        <Detail label="Jumlah Denda" value={receipt.amount_label} />
                        <Detail label="Terlambat" value={`${receipt.days_late} hari · ${receipt.rate_label}/hari`} />
                        <Detail label="Jatuh Tempo" value={receipt.due_at} />
                        <Detail label="Dikembalikan" value={receipt.returned_at} />
                        <Detail label="Diterbitkan" value={receipt.issued_at} />
                        <Detail label="Penerbit" value={receipt.issuer} />
                    </dl>

                    <div className="border-t border-slate-100 pt-4">
                        <p className="text-[11px] font-bold uppercase text-slate-500">Riwayat Cetak</p>
                        {receipt.prints.length === 0 ? (
                            <p className="mt-2 text-xs text-slate-500">Belum pernah dicetak.</p>
                        ) : (
                            <ul className="mt-2 space-y-1.5">
                                {receipt.prints.map((print) => (
                                    <li
                                        key={print.copy_number}
                                        className="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-xs"
                                    >
                                        <span className="font-semibold text-slate-700">
                                            Salinan ke-{print.copy_number}
                                        </span>
                                        <span className="text-slate-500">
                                            {print.printed_at} · {print.printed_by ?? '-'}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                        {receipt.prints.length > 1 && (
                            <p className="mt-2 text-[11px] text-sky-700">
                                Karena sudah dicetak lebih dari satu kali, salinan ke atas otomatis diberi
                                tanda <strong>SALINAN</strong>.
                            </p>
                        )}
                    </div>

                    <div className="mt-4 flex justify-end border-t border-slate-100 pt-4">
                        <CanManage>
                            <button
                                type="button"
                                onClick={() => window.open(route('loans.slip-fine', receipt.loan_id), '_blank')}
                                className="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3.5 py-2 text-xs font-bold text-white transition hover:bg-slate-700"
                            >
                                <Printer className="h-3.5 w-3.5" />
                                Cetak Ulang
                            </button>
                        </CanManage>
                    </div>
                </Card>
            )}

            {!notFound && !receipt && (
                <div className="mx-auto mt-6 max-w-2xl">
                    <EmptyState
                        icon={ShieldCheck}
                        title="Belum ada kode yang diperiksa"
                        description="Ketuk kolom di atas lalu tekan Periksa."
                    />
                </div>
            )}
        </AuthenticatedLayout>
    );
}

function Detail({ label, value }) {
    return (
        <div>
            <dt className="text-[11px] font-bold uppercase text-slate-500">{label}</dt>
            <dd className={cn('mt-0.5 font-semibold text-slate-900')}>{value ?? '-'}</dd>
        </div>
    );
}
