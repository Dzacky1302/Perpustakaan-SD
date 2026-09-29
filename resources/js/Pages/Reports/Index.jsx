import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge, Card, Field, Input, PageHeader, Select } from '@/Components/ui';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    Banknote,
    BookMarked,
    CalendarRange,
    FileSpreadsheet,
    FileText,
    LibraryBig,
    Users,
} from 'lucide-react';

const REPORTS = [
    {
        key: 'visits',
        title: 'Rekap Buku Tamu',
        description:
            'Daftar kunjungan siswa ke perpustakaan lengkap dengan waktu datang dan keperluan, disertai ringkasan rata-rata kunjungan harian.',
        icon: Users,
        tone: 'emerald',
        pdf: 'reports.visits.pdf',
        excel: 'reports.visits.excel',
    },
    {
        key: 'package-loans',
        title: 'Rekap Buku Paket',
        description:
            'Laporan penerimaan dan pengembalian buku paket per kelas: jumlah dibagikan, sudah kembali, masih dipegang, dan hilang.',
        icon: LibraryBig,
        tone: 'amber',
        pdf: 'reports.package-loans.pdf',
        excel: 'reports.package-loans.excel',
    },
    {
        key: 'loans',
        title: 'Rekap Peminjaman Buku Koleksi',
        description:
            'Daftar peminjaman buku koleksi harian beserta status keterlambatan untuk keperluan penagihan dan arsip pustakawan.',
        icon: BookMarked,
        tone: 'sky',
        pdf: 'reports.loans.pdf',
        excel: null,
    },
    {
        key: 'fines',
        title: 'Rekap Denda Keterlambatan',
        description:
            'Rekap denda keterlambatan pengembalian buku. Nominal dikunci saat buku dikembalikan, jadi angka historis tidak berubah.',
        icon: Banknote,
        tone: 'rose',
        pdf: 'reports.fines.pdf',
        excel: 'reports.fines.excel',
    },
];

export default function ReportsIndex({ filters = {}, classrooms = [], academicYears = [], preview = {}, fines = {} }) {
    const { school } = usePage().props;

    const [state, setState] = useState({
        from: filters.from ?? '',
        to: filters.to ?? '',
        classroom_id: filters.classroom_id ?? '',
        academic_year: filters.academic_year ?? '',
    });

    // (next) dipakai saat filter berubah: langsung terapkan tanpa perlu
    // tekan Enter. Tanpa argumen dipakai saat form disubmit (Enter).
    const applyFilters = (next) => {
        const params = next ? { ...state, ...next } : state;

        if (next) setState(params);

        router.get(route('reports.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const query = new URLSearchParams({
        from: state.from,
        to: state.to,
        classroom_id: state.classroom_id,
        academic_year: state.academic_year,
    }).toString();

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Pusat Laporan & Cetak Dokumen"
                    subtitle="Unduh rekap resmi ber-kop sekolah dalam format PDF atau Excel"
                >
                    <Badge tone="emerald">
                        <CalendarRange className="h-3 w-3" />
                        {school?.library ?? 'Perpustakaan Sekolah'}
                    </Badge>
                </PageHeader>
            }
        >
            <Head title="Laporan" />
            <div className="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div className="rounded-2xl border border-rose-200 bg-rose-50/60 p-4">
                    <p className="text-[11px] font-bold uppercase text-rose-700">Denda Belum Dibayar</p>
                    <p className="mt-1 text-xl font-extrabold text-rose-700">{fines.unpaid_label ?? 'Rp0'}</p>
                    <p className="mt-1 text-[11px] text-rose-600">{fines.count_unpaid ?? 0} buku</p>
                </div>
                <div className="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4">
                    <p className="text-[11px] font-bold uppercase text-emerald-700">Denda Diterima</p>
                    <p className="mt-1 text-xl font-extrabold text-emerald-700">{fines.collected_label ?? 'Rp0'}</p>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Tarif Denda</p>
                    <p className="mt-1 text-xl font-extrabold text-slate-900">{fines.rate_label ?? 'Rp1.000'}</p>
                    <p className="mt-1 text-[11px] text-slate-500">per hari</p>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Batas per Buku</p>
                    <p className="mt-1 text-xl font-extrabold text-slate-900">{fines.max_label ?? 'Rp10.000'}</p>
                </div>
            </div>
            <Card
                className="mb-4"
                title="Filter Periode Laporan"
                subtitle="Periode ini dipakai untuk rekap buku tamu dan peminjaman"
            >
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        applyFilters();
                    }}
                    className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <Field label="Dari Tanggal">
                        <Input
                            type="date"
                            value={state.from}
                            onChange={(event) => applyFilters({ from: event.target.value })}
                        />
                    </Field>
                    <Field label="Sampai Tanggal">
                        <Input
                            type="date"
                            value={state.to}
                            onChange={(event) => applyFilters({ to: event.target.value })}
                        />
                    </Field>
                    <Field label="Kelas (untuk rekap buku paket)">
                        <Select
                            value={state.classroom_id}
                            onChange={(event) => {
                                const classroomId = event.target.value;
                                const classroom = classrooms.find(
                                    (item) => String(item.id) === String(classroomId),
                                );

                                applyFilters({
                                    classroom_id: classroomId,
                                    academic_year: classroom?.academic_year ?? state.academic_year,
                                });
                            }}
                        >
                            <option value="">Semua kelas</option>
                            {classrooms.map((classroom) => (
                                <option key={classroom.id} value={classroom.id}>
                                    Kelas {classroom.name} ({classroom.students_count} siswa)
                                </option>
                            ))}
                        </Select>
                    </Field>
                    <Field label="Tahun Ajaran">
                        <Input
                            value={state.academic_year}
                            onChange={(event) => setState({ ...state, academic_year: event.target.value })}
                            placeholder={academicYears[0] ?? '2025/2026'}
                        />
                    </Field>
                </form>
            </Card>

            <div className="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Kunjungan Periode Ini</p>
                    <p className="mt-1 text-xl font-extrabold text-emerald-700">{preview.visits ?? 0}</p>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Peminjaman Aktif</p>
                    <p className="mt-1 text-xl font-extrabold text-amber-600">{preview.active_loans ?? 0}</p>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Buku Paket Beredar</p>
                    <p className="mt-1 text-xl font-extrabold text-violet-700">{preview.package_loans ?? 0}</p>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Anggota Aktif</p>
                    <p className="mt-1 text-xl font-extrabold text-slate-900">{preview.students ?? 0}</p>
                </div>
            </div>
            <div className="grid gap-4 lg:grid-cols-3">
                {REPORTS.map((report) => {
                    const Icon = report.icon;
                    const params =
                        report.key === 'package-loans'
                            ? new URLSearchParams({
                                  classroom_id: state.classroom_id,
                                  academic_year: state.academic_year,
                              }).toString()
                            : query;

                    return (
                        <div
                            key={report.key}
                            className="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <div className="mb-3 flex items-center gap-3">
                                <span className="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600">
                                    <Icon className="h-5 w-5" />
                                </span>
                                <h3 className="text-sm font-extrabold text-slate-900">{report.title}</h3>
                            </div>
                            <p className="flex-1 text-xs leading-relaxed text-slate-500">
                                {report.description}
                            </p>
                            <div className="mt-4 flex flex-wrap gap-2">
                                <a
                                    href={`${route(report.pdf)}?${params}`}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white transition hover:bg-emerald-700"
                                >
                                    <FileText className="h-3.5 w-3.5" />
                                    Unduh PDF
                                </a>
                                {report.excel && (
                                    <a
                                        href={`${route(report.excel)}?${params}`}
                                        className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                                    >
                                        <FileSpreadsheet className="h-3.5 w-3.5" />
                                        Unduh Excel
                                    </a>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
            <Card className="mt-4" title="Surat Keterangan Bebas Pustaka">
                <p className="text-xs leading-relaxed text-slate-600">
                    Surat keterangan bebas tanggungan peminjaman dicetak per siswa untuk keperluan mutasi, kelulusan,
                    atau pengambilan ijazah. Buka menu <strong>Data Siswa</strong>, lalu tekan ikon{' '}
                    <FileText className="inline h-3.5 w-3.5 text-amber-600" /> pada baris siswa yang dituju. Sistem
                    akan mencetak surat resmi sekaligus menampilkan daftar buku yang masih menjadi tanggungan.
                </p>
                <div className="mt-4 flex flex-wrap gap-2">
                    <a
                        href={route('students.index')}
                        className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                    >
                        <Users className="h-3.5 w-3.5" />
                        Buka Data Siswa
                    </a>
                    <a
                        href={route('books.export')}
                        className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                    >
                        <FileSpreadsheet className="h-3.5 w-3.5" />
                        Ekspor Katalog Buku
                    </a>
                    <a
                        href={route('students.template')}
                        className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                    >
                        <FileSpreadsheet className="h-3.5 w-3.5" />
                        Template Impor Siswa
                    </a>
                </div>
            </Card>
        </AuthenticatedLayout>
    );
}
