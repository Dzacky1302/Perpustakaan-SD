import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import { Badge, Button, Card, EmptyState, Field, Input, PageHeader, Select, cn } from '@/Components/ui';
import { Head, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { CanManage } from '@/hooks/useCanManage';
import {
    CheckCircle2,
    ClipboardCheck,
    Download,
    FileText,
    Layers,
    PackageCheck,
    Send,
    TriangleAlert,
    Users,
    X,
} from 'lucide-react';

const today = new Date().toISOString().slice(0, 10);

export default function PackageLoansIndex({
    classrooms = [],
    selected,
    academicYear,
    books = [],
    matrix = [],
    summary,
}) {
    const [selectedLoans, setSelectedLoans] = useState([]);
    const [showDistribute, setShowDistribute] = useState(false);
    const [showReturn, setShowReturn] = useState(false);

    const distributeForm = useForm({
        classroom_id: selected?.id ?? '',
        academic_year: academicYear ?? '',
        given_at: today,
        book_ids: books.map((book) => book.id),
    });

    const returnForm = useForm({
        loan_ids: [],
        returned_at: today,
        return_condition: 'baik',
        status: 'kembali',
    });

    const activeLoanIds = useMemo(
        () =>
            matrix.flatMap((row) =>
                row.cells
                    .filter((cell) => cell.status === 'dipinjam' && cell.loan_id)
                    .map((cell) => cell.loan_id),
            ),
        [matrix],
    );

    const activeCells = useMemo(
        () =>
            matrix.flatMap((row) =>
                row.cells
                    .filter((cell) => cell.status === 'dipinjam' && cell.loan_id)
                    .map((cell) => ({ ...cell, student: row.name, nisn: row.nisn })),
            ),
        [matrix],
    );

    const changeClassroom = (classroomId) => {
        router.get(
            route('package-loans.index'),
            { classroom_id: classroomId, academic_year: academicYear },
            { preserveScroll: true },
        );
    };

    const changeYear = (year) => {
        router.get(
            route('package-loans.index'),
            { classroom_id: selected?.id, academic_year: year },
            { preserveScroll: true },
        );
    };

    const toggleLoan = (loanId) => {
        setSelectedLoans((current) =>
            current.includes(loanId) ? current.filter((id) => id !== loanId) : [...current, loanId],
        );
    };

    const openReturn = () => {
        returnForm.setData({
            loan_ids: selectedLoans,
            returned_at: today,
            return_condition: 'baik',
            status: 'kembali',
        });
        setShowReturn(true);
    };

    const submitReturn = (event) => {
        event.preventDefault();

        returnForm.post(route('package-loans.return'), {
            preserveScroll: true,
            onSuccess: () => {
                setShowReturn(false);
                setSelectedLoans([]);
            },
        });
    };

    const submitDistribute = (event) => {
        event.preventDefault();

        distributeForm.post(route('package-loans.distribute'), {
            preserveScroll: true,
            onSuccess: () => setShowDistribute(false),
        });
    };

    const reportParams = new URLSearchParams({
        classroom_id: selected?.id ?? '',
        academic_year: academicYear ?? '',
    }).toString();

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Distribusi Buku Paket"
                    subtitle="Matriks pembagian buku kurikulum per siswa dan checklist pengembalian"
                >
                    <a
                        href={`${route('reports.package-loans.pdf')}?${reportParams}`}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                    >
                        <FileText className="h-3.5 w-3.5" />
                        Rekap PDF
                    </a>
                    <a
                        href={`${route('reports.package-loans.excel')}?${reportParams}`}
                        className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                    >
                        <Download className="h-3.5 w-3.5" />
                        Rekap Excel
                    </a>
                    <CanManage>
                        <Button
                            type="button"
                            disabled={!selected || books.length === 0}
                            onClick={() => {
                                distributeForm.setData({
                                    classroom_id: selected?.id ?? '',
                                    academic_year: academicYear ?? '',
                                    given_at: today,
                                    book_ids: books.map((book) => book.id),
                                });
                                setShowDistribute(true);
                            }}
                        >
                            <Send className="h-3.5 w-3.5" />
                            Distribusi Satu Klik
                        </Button>
                    </CanManage>
                </PageHeader>
            }
        >
            <Head title="Buku Paket" />
            <Card bodyClass="p-4" className="mb-4">
                <div className="grid gap-3 lg:grid-cols-12">
                    <Field label="Pilih Kelas" className="lg:col-span-5">
                        <Select value={selected?.id ?? ''} onChange={(event) => changeClassroom(event.target.value)}>
                            {classrooms.map((classroom) => (
                                <option key={classroom.id} value={classroom.id}>
                                    Kelas {classroom.name} • {classroom.students_count} siswa •{' '}
                                    {classroom.academic_year}
                                </option>
                            ))}
                        </Select>
                    </Field>
                    <Field label="Tahun Ajaran" className="lg:col-span-3">
                        <Input
                            value={academicYear ?? ''}
                            onChange={(event) => changeYear(event.target.value)}
                            placeholder="2025/2026"
                        />
                    </Field>
                    <div className="flex items-end gap-2 lg:col-span-4">
                        <div className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-[11px]">
                            <p className="font-bold text-slate-600">Wali Kelas</p>
                            <p className="text-slate-500">{selected?.homeroom_teacher ?? '-'}</p>
                        </div>
                        {activeLoanIds.length > 0 && (
                            <Button
                                variant="secondary"
                                type="button"
                                onClick={() =>
                                    setSelectedLoans(
                                        selectedLoans.length === activeLoanIds.length ? [] : activeLoanIds,
                                    )
                                }
                            >
                                <ClipboardCheck className="h-3.5 w-3.5" />
                                {selectedLoans.length === activeLoanIds.length ? 'Batal Pilih' : 'Pilih Semua Aktif'}
                            </Button>
                        )}
                    </div>
                </div>
            </Card>

            <div className="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-6">
                {[
                    { label: 'Siswa', value: summary?.students ?? 0, tone: 'text-slate-900' },
                    { label: 'Judul Paket', value: summary?.books ?? 0, tone: 'text-slate-900' },
                    { label: 'Sudah Dibagikan', value: summary?.distributed ?? 0, tone: 'text-sky-700' },
                    { label: 'Masih Dipegang', value: summary?.active ?? 0, tone: 'text-amber-600' },
                    { label: 'Sudah Kembali', value: summary?.returned ?? 0, tone: 'text-emerald-700' },
                    { label: 'Hilang', value: summary?.lost ?? 0, tone: 'text-rose-700' },
                ].map((item) => (
                    <div key={item.label} className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-[10px] font-bold uppercase text-slate-500">{item.label}</p>
                        <p className={cn('mt-1 text-xl font-extrabold', item.tone)}>{item.value}</p>
                    </div>
                ))}
            </div>

            {selectedLoans.length > 0 && (
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3.5">
                    <p className="flex items-center gap-2 text-xs font-bold text-emerald-800">
                        <PackageCheck className="h-4 w-4" />
                        {selectedLoans.length} buku paket dipilih untuk diproses
                    </p>
                    <div className="flex items-center gap-2">
                        <Button variant="secondary" type="button" onClick={() => setSelectedLoans([])}>
                            Bersihkan Pilihan
                        </Button>
                        <Button type="button" onClick={openReturn}>
                            <CheckCircle2 className="h-3.5 w-3.5" />
                            Proses Pengembalian
                        </Button>
                    </div>
                </div>
            )}
            <Card
                title={`Matriks Buku Paket — Kelas ${selected?.name ?? '-'}`}
                subtitle="Klik kotak status untuk memilih buku yang akan diproses pengembaliannya"
                bodyClass="p-0"
                action={
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge tone="amber">Dipinjam</Badge>
                        <Badge tone="emerald">Kembali</Badge>
                        <Badge tone="rose">Hilang</Badge>
                        <Badge tone="slate">Belum dibagikan</Badge>
                    </div>
                }
            >
                {matrix.length === 0 || books.length === 0 ? (
                    <div className="p-5">
                        <EmptyState
                            icon={Layers}
                            title="Data matriks belum tersedia"
                            description="Pastikan kelas memiliki siswa aktif dan buku paket untuk tingkat kelas tersebut sudah terdaftar pada katalog."
                        />
                    </div>
                ) : (
                    <div className="max-h-[70vh] overflow-auto">
                        <table className="min-w-full border-separate border-spacing-0 text-left text-xs">
                            <thead>
                                <tr>
                                    <th className="sticky left-0 top-0 z-20 min-w-[220px] border-b border-slate-200 bg-slate-50 px-5 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">
                                        Nama Siswa
                                    </th>
                                    {books.map((book) => (
                                        <th
                                            key={book.id}
                                            className="sticky top-0 z-10 min-w-[130px] border-b border-l border-slate-200 bg-slate-50 px-3 py-3 text-center text-[10px] font-bold leading-tight text-slate-500"
                                            title={book.title}
                                        >
                                            <span className="block truncate">{book.title}</span>
                                            <span className="font-mono text-[9px] text-slate-400">
                                                {book.code}
                                            </span>
                                        </th>
                                    ))}
                                    <th className="sticky right-0 top-0 z-20 min-w-[110px] border-b border-l border-slate-200 bg-slate-50 px-3 py-3 text-center text-[10px] font-bold uppercase tracking-wide text-slate-500">
                                        Ringkasan
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {matrix.map((row) => (
                                    <tr key={row.student_id} className="hover:bg-slate-50/60">
                                        <td className="sticky left-0 z-10 border-b border-slate-100 bg-white px-5 py-3">
                                            <p className="font-bold text-slate-800">{row.name}</p>
                                            <p className="font-mono text-[10px] text-slate-400">{row.nisn}</p>
                                        </td>
                                        {row.cells.map((cell) => {
                                            const selectedCell =
                                                cell.loan_id && selectedLoans.includes(cell.loan_id);
                                            const clickable = cell.status === 'dipinjam' && cell.loan_id;

                                            return (
                                                <td
                                                    key={cell.book_id}
                                                    className="border-b border-l border-slate-100 px-2 py-2 text-center"
                                                >
                                                    <button
                                                        type="button"
                                                        disabled={!clickable}
                                                        onClick={() => clickable && toggleLoan(cell.loan_id)}
                                                        title={
                                                            cell.status === 'dipinjam'
                                                                ? 'Klik untuk memilih buku ini'
                                                                : (cell.status ?? 'Belum dibagikan')
                                                        }
                                                        className={cn(
                                                            'mx-auto flex h-9 w-full items-center justify-center rounded-lg border text-[10px] font-bold transition',
                                                            cell.status === 'dipinjam' &&
                                                                (selectedCell
                                                                    ? 'border-emerald-500 bg-emerald-500 text-white'
                                                                    : 'border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100'),
                                                            cell.status === 'kembali' &&
                                                                'border-emerald-200 bg-emerald-50 text-emerald-700',
                                                            cell.status === 'hilang' &&
                                                                'border-rose-200 bg-rose-50 text-rose-700',
                                                            !cell.status &&
                                                                'cursor-default border-dashed border-slate-200 bg-slate-50 text-slate-300',
                                                        )}
                                                    >
                                                        {cell.status === 'dipinjam' &&
                                                            (selectedCell ? '✓ Dipilih' : 'Dipinjam')}
                                                        {cell.status === 'kembali' &&
                                                            (cell.return_condition ?? 'Kembali')}
                                                        {cell.status === 'hilang' && 'Hilang'}
                                                        {!cell.status && '—'}
                                                    </button>
                                                    {cell.status === 'kembali' && cell.returned_at && (
                                                        <p className="mt-1 text-[9px] text-slate-400">
                                                            {cell.returned_at}
                                                        </p>
                                                    )}
                                                </td>
                                            );
                                        })}
                                        <td className="sticky right-0 z-10 border-b border-l border-slate-100 bg-white px-3 py-2 text-center">
                                            {row.is_complete ? (
                                                <Badge tone="emerald">
                                                    <CheckCircle2 className="h-3 w-3" />
                                                    Lengkap
                                                </Badge>
                                            ) : (
                                                <div className="flex flex-col items-center gap-1">
                                                    <Badge tone="amber">{row.active_count} dipegang</Badge>
                                                    {row.missing_count > 0 && (
                                                        <Badge tone="rose">
                                                            <TriangleAlert className="h-3 w-3" />
                                                            {row.missing_count} hilang
                                                        </Badge>
                                                    )}
                                                </div>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>
            <Modal show={showDistribute} onClose={() => setShowDistribute(false)} maxWidth="2xl">
                <form onSubmit={submitDistribute}>
                    <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div>
                            <h3 className="text-sm font-extrabold text-slate-900">
                                Distribusi Buku Paket Kelas {selected?.name}
                            </h3>
                            <p className="text-[11px] text-slate-500">
                                Sistem membagikan buku ke seluruh siswa aktif kelas ini sekaligus.
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setShowDistribute(false)}
                            className="rounded-md p-1.5 text-slate-400 hover:bg-slate-100"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>

                    <div className="max-h-[65vh] space-y-4 overflow-y-auto px-5 py-5">
                        <div className="grid gap-3 sm:grid-cols-2">
                            <Field label="Tanggal Pembagian" error={distributeForm.errors.given_at}>
                                <Input
                                    type="date"
                                    value={distributeForm.data.given_at}
                                    onChange={(event) =>
                                        distributeForm.setData('given_at', event.target.value)
                                    }
                                />
                            </Field>
                            <Field label="Tahun Ajaran" error={distributeForm.errors.academic_year}>
                                <Input
                                    value={distributeForm.data.academic_year}
                                    onChange={(event) =>
                                        distributeForm.setData('academic_year', event.target.value)
                                    }
                                />
                            </Field>
                        </div>

                        <div>
                            <p className="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                Buku yang dibagikan ({distributeForm.data.book_ids.length} dari {books.length}{' '}
                                judul)
                            </p>
                            <div className="grid gap-2 sm:grid-cols-2">
                                {books.map((book) => {
                                    const checked = distributeForm.data.book_ids.includes(book.id);

                                    return (
                                        <label
                                            key={book.id}
                                            className="flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs hover:bg-slate-50"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={checked}
                                                onChange={(event) =>
                                                    distributeForm.setData(
                                                        'book_ids',
                                                        event.target.checked
                                                            ? [...distributeForm.data.book_ids, book.id]
                                                            : distributeForm.data.book_ids.filter(
                                                                  (id) => id !== book.id,
                                                              ),
                                                    )
                                                }
                                                className="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                            />
                                            <span className="min-w-0">
                                                <span className="block truncate font-bold text-slate-700">
                                                    {book.title}
                                                </span>
                                                <span className="font-mono text-[10px] text-slate-400">
                                                    {book.code}
                                                </span>
                                            </span>
                                        </label>
                                    );
                                })}
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
                        <Button variant="secondary" type="button" onClick={() => setShowDistribute(false)}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={distributeForm.processing}>
                            <Send className="h-3.5 w-3.5" />
                            {distributeForm.processing ? 'Memproses...' : 'Distribusikan Sekarang'}
                        </Button>
                    </div>
                </form>
            </Modal>
            <Modal show={showReturn} onClose={() => setShowReturn(false)} maxWidth="lg">
                <form onSubmit={submitReturn}>
                    <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div>
                            <h3 className="text-sm font-extrabold text-slate-900">
                                Checklist Pengembalian Buku Paket
                            </h3>
                            <p className="text-[11px] text-slate-500">
                                {returnForm.data.loan_ids.length} buku siap diproses
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setShowReturn(false)}
                            className="rounded-md p-1.5 text-slate-400 hover:bg-slate-100"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>

                    <div className="max-h-[60vh] space-y-4 overflow-y-auto px-5 py-5">
                        <ul className="divide-y divide-slate-100 rounded-xl border border-slate-200">
                            {activeCells
                                .filter((cell) => returnForm.data.loan_ids.includes(cell.loan_id))
                                .map((cell) => (
                                    <li
                                        key={cell.loan_id}
                                        className="flex items-center justify-between gap-3 px-4 py-2.5 text-[11px]"
                                    >
                                        <span className="min-w-0">
                                            <span className="block font-bold text-slate-700">
                                                {cell.student}
                                            </span>
                                            <span className="text-slate-400">NISN {cell.nisn}</span>
                                        </span>
                                        <span className="shrink-0 text-slate-500">
                                            ID Paket #{cell.loan_id}
                                        </span>
                                    </li>
                                ))}
                        </ul>

                        <div className="grid gap-3 sm:grid-cols-3">
                            <Field label="Tanggal Kembali" error={returnForm.errors.returned_at}>
                                <Input
                                    type="date"
                                    value={returnForm.data.returned_at}
                                    onChange={(event) =>
                                        returnForm.setData('returned_at', event.target.value)
                                    }
                                />
                            </Field>
                            <Field label="Kondisi Buku" error={returnForm.errors.return_condition}>
                                <Select
                                    value={returnForm.data.return_condition}
                                    onChange={(event) =>
                                        returnForm.setData('return_condition', event.target.value)
                                    }
                                    disabled={returnForm.data.status === 'hilang'}
                                >
                                    <option value="baik">Baik</option>
                                    <option value="rusak ringan">Rusak Ringan</option>
                                    <option value="rusak berat">Rusak Berat</option>
                                </Select>
                            </Field>
                            <Field label="Status Akhir" error={returnForm.errors.status}>
                                <Select
                                    value={returnForm.data.status}
                                    onChange={(event) => returnForm.setData('status', event.target.value)}
                                >
                                    <option value="kembali">Dikembalikan</option>
                                    <option value="hilang">Ditandai Hilang</option>
                                </Select>
                            </Field>
                        </div>

                        <p className="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-[11px] text-sky-900">
                            Buku yang dikembalikan akan menambah stok tersedia otomatis. Buku yang ditandai hilang
                            tidak mengembalikan stok dan tercatat sebagai temuan inventaris.
                        </p>
                    </div>

                    <div className="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
                        <Button variant="secondary" type="button" onClick={() => setShowReturn(false)}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={returnForm.processing}>
                            <CheckCircle2 className="h-3.5 w-3.5" />
                            {returnForm.processing ? 'Menyimpan...' : 'Simpan Pengembalian'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
