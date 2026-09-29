import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge, Button, Card, EmptyState, Field, Input, Select, cn } from '@/Components/ui';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import {
    BookOpen,
    CheckCircle2,
    Clock,
    GraduationCap,
    Keyboard,
    RotateCcw,
    Search,
    SmilePlus,
    Trash2,
    UserRound,
} from 'lucide-react';

const PURPOSES = [
    { value: 'membaca', label: 'Membaca Buku', icon: BookOpen },
    { value: 'pinjam', label: 'Meminjam Buku', icon: SmilePlus },
    { value: 'tugas', label: 'Mengerjakan Tugas', icon: Keyboard },
    { value: 'lainnya', label: 'Keperluan Lain', icon: UserRound },
];

export default function KioskIndex({ classrooms = [], todayVisits = [], todaySummary, books = [] }) {
    const [step, setStep] = useState(1);
    const [classroom, setClassroom] = useState(null);
    const [students, setStudents] = useState([]);
    const [loadingStudents, setLoadingStudents] = useState(false);
    const [search, setSearch] = useState('');
    const [student, setStudent] = useState(null);
    const searchRef = useRef(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        student_id: '',
        purpose: 'membaca',
        book_id: '',
        notes: '',
    });

    useEffect(() => {
        if (!classroom) {
            setStudents([]);

            return;
        }

        let active = true;
        setLoadingStudents(true);

        axios
            .get(route('kiosk.students'), { params: { classroom_id: classroom.id } })
            .then((response) => {
                if (active) setStudents(response.data.data ?? []);
            })
            .catch(() => {
                if (active) setStudents([]);
            })
            .finally(() => {
                if (active) setLoadingStudents(false);
            });

        return () => {
            active = false;
        };
    }, [classroom]);

    useEffect(() => {
        if (step === 2) searchRef.current?.focus();
    }, [step]);

    const filteredStudents = useMemo(() => {
        const keyword = search.trim().toLowerCase();

        if (!keyword) return students;

        return students.filter(
            (item) =>
                item.name.toLowerCase().includes(keyword) ||
                (item.nisn ?? '').toLowerCase().includes(keyword),
        );
    }, [students, search]);

    const resetFlow = () => {
        setStep(1);
        setClassroom(null);
        setStudent(null);
        setSearch('');
        reset();
    };

    const submit = (event) => {
        event.preventDefault();

        post(route('kiosk.check-in'), {
            preserveScroll: true,
            onSuccess: () => resetFlow(),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="flex items-center gap-2 text-xl font-extrabold tracking-tight text-slate-900">
                            Buku Tamu Digital
                            <Badge tone="amber">Mode Kios</Badge>
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Sentuh nama kelas, pilih nama siswa, lalu tentukan keperluan kunjungan.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge tone="emerald">
                            <Clock className="h-3 w-3" />
                            {todaySummary?.total ?? 0} kunjungan hari ini
                        </Badge>
                        <Button variant="secondary" type="button" onClick={resetFlow}>
                            <RotateCcw className="h-3.5 w-3.5" />
                            Mulai Ulang
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Buku Tamu" />

            <div className="grid gap-4 xl:grid-cols-5">
                <div className="xl:col-span-3">
                    <Card
                        title="Formulir Kunjungan"
                        subtitle={`Langkah ${step} dari 3`}
                        action={
                            <div className="flex items-center gap-1.5">
                                {[1, 2, 3].map((item) => (
                                    <span
                                        key={item}
                                        className={cn(
                                            'h-1.5 w-8 rounded-full',
                                            step >= item ? 'bg-emerald-500' : 'bg-slate-200',
                                        )}
                                    />
                                ))}
                            </div>
                        }
                    >
                        {step === 1 && (
                            <div>
                                <p className="mb-3 flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500">
                                    <GraduationCap className="h-4 w-4 text-emerald-600" />
                                    Pilih Kelas
                                </p>
                                <div className="grid grid-cols-3 gap-2.5 sm:grid-cols-4 lg:grid-cols-6">
                                    {classrooms.map((item) => (
                                        <button
                                            key={item.id}
                                            type="button"
                                            onClick={() => {
                                                setClassroom(item);
                                                setStudent(null);
                                                setSearch('');
                                                setStep(2);
                                            }}
                                            className="rounded-xl border border-slate-200 bg-white px-3 py-4 text-center transition hover:border-emerald-400 hover:bg-emerald-50"
                                        >
                                            <span className="block text-lg font-extrabold text-slate-800">
                                                {item.name}
                                            </span>
                                            <span className="text-[10px] text-slate-500">
                                                {item.students_count} siswa
                                            </span>
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}

                        {step === 2 && (
                            <div>
                                <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                                    <p className="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500">
                                        <UserRound className="h-4 w-4 text-emerald-600" />
                                        Pilih Nama Siswa — Kelas {classroom?.name}
                                    </p>
                                    <Button variant="ghost" type="button" onClick={() => setStep(1)}>
                                        Ganti Kelas
                                    </Button>
                                </div>

                                <div className="relative mb-3">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                    <Input
                                        ref={searchRef}
                                        value={search}
                                        onChange={(event) => setSearch(event.target.value)}
                                        placeholder="Cari nama siswa atau NISN..."
                                        className="pl-9"
                                    />
                                </div>

                                {loadingStudents ? (
                                    <p className="py-6 text-center text-xs text-slate-500">
                                        Memuat daftar siswa...
                                    </p>
                                ) : filteredStudents.length === 0 ? (
                                    <EmptyState
                                        icon={UserRound}
                                        title="Siswa tidak ditemukan"
                                        description="Pastikan nama siswa sudah diimpor pada menu Data Siswa."
                                    />
                                ) : (
                                    <div className="grid max-h-[420px] grid-cols-2 gap-2.5 overflow-y-auto pr-1 sm:grid-cols-3">
                                        {filteredStudents.map((item) => (
                                            <button
                                                key={item.id}
                                                type="button"
                                                onClick={() => {
                                                    setStudent(item);
                                                    setData('student_id', item.id);
                                                    setStep(3);
                                                }}
                                                className="rounded-xl border border-slate-200 bg-white px-3 py-3 text-left transition hover:border-emerald-400 hover:bg-emerald-50"
                                            >
                                                <span className="block truncate text-sm font-bold text-slate-800">
                                                    {item.name}
                                                </span>
                                                <span className="text-[10px] text-slate-500">NISN {item.nisn}</span>
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}


                        {step === 3 && (
                            <form onSubmit={submit} className="space-y-4">
                                <div className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                                    <div>
                                        <p className="text-[10px] font-bold uppercase tracking-wide text-emerald-700">
                                            Siswa terpilih
                                        </p>
                                        <p className="text-sm font-extrabold text-slate-900">{student?.name}</p>
                                        <p className="text-[11px] text-slate-600">
                                            NISN {student?.nisn} • Kelas {classroom?.name}
                                        </p>
                                    </div>
                                    <Button variant="secondary" type="button" onClick={() => setStep(2)}>
                                        Ganti Siswa
                                    </Button>
                                </div>

                                <div>
                                    <p className="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">
                                        Keperluan Kunjungan
                                    </p>
                                    <div className="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                                        {PURPOSES.map((purpose) => {
                                            const Icon = purpose.icon;
                                            const active = data.purpose === purpose.value;

                                            return (
                                                <button
                                                    key={purpose.value}
                                                    type="button"
                                                    onClick={() => setData('purpose', purpose.value)}
                                                    className={cn(
                                                        'flex flex-col items-center gap-1.5 rounded-xl border px-3 py-3.5 text-[11px] font-bold transition',
                                                        active
                                                            ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                                                            : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-300',
                                                    )}
                                                >
                                                    <Icon className="h-4 w-4" />
                                                    {purpose.label}
                                                </button>
                                            );
                                        })}
                                    </div>
                                    {errors.purpose && (
                                        <p className="mt-1 text-[11px] font-semibold text-rose-600">
                                            {errors.purpose}
                                        </p>
                                    )}
                                </div>

                                <div className="grid gap-3 sm:grid-cols-2">
                                    <Field label="Buku yang Dibaca/Dipinjam (opsional)" error={errors.book_id}>
                                        <Select
                                            value={data.book_id}
                                            onChange={(event) => setData('book_id', event.target.value)}
                                        >
                                            <option value="">— Tidak memilih buku —</option>
                                            {books.map((book) => (
                                                <option key={book.id} value={book.id}>
                                                    {book.code} · {book.title}
                                                </option>
                                            ))}
                                        </Select>
                                    </Field>

                                    <Field label="Catatan (opsional)" error={errors.notes}>
                                        <Input
                                            value={data.notes}
                                            onChange={(event) => setData('notes', event.target.value)}
                                            placeholder="Misal: mencari buku cerita IPA"
                                        />
                                    </Field>
                                </div>

                                <div className="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                                    <Button type="submit" disabled={processing} className="px-5 py-2.5 text-sm">
                                        <CheckCircle2 className="h-4 w-4" />
                                        {processing ? 'Menyimpan...' : 'Catat Kunjungan'}
                                    </Button>
                                    <Button variant="secondary" type="button" onClick={resetFlow}>
                                        Batal
                                    </Button>
                                    <p className="text-[11px] text-slate-400">
                                        Waktu kedatangan dicatat otomatis oleh sistem.
                                    </p>
                                </div>
                            </form>
                        )}
                    </Card>
                </div>

                <div className="xl:col-span-2">
                    <Card
                        title="Daftar Hadir Hari Ini"
                        subtitle="Log kunjungan siswa terbaru"
                        bodyClass="p-0"
                        action={<Badge tone="sky">{todayVisits.length} baris</Badge>}
                    >
                        {todayVisits.length === 0 ? (
                            <div className="p-5">
                                <EmptyState
                                    icon={Clock}
                                    title="Belum ada kunjungan hari ini"
                                    description="Catatan akan muncul otomatis begitu siswa menyentuh namanya di kios."
                                />
                            </div>
                        ) : (
                            <ul className="max-h-[560px] divide-y divide-slate-100 overflow-y-auto">
                                {todayVisits.map((visit) => (
                                    <li key={visit.id} className="flex items-start gap-3 px-5 py-3">
                                        <span className="mt-0.5 flex h-9 w-9 shrink-0 flex-col items-center justify-center rounded-lg bg-emerald-50 text-[10px] font-bold text-emerald-700">
                                            {visit.arrival_time}
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-xs font-bold text-slate-800">
                                                {visit.student}
                                            </p>
                                            <p className="truncate text-[11px] text-slate-500">
                                                {visit.classroom}
                                                {visit.book ? ` • ${visit.book}` : ''}
                                            </p>
                                            <Badge tone="slate" className="mt-1">
                                                {visit.purpose}
                                            </Badge>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                if (confirm(`Batalkan kunjungan ${visit.student}?`)) {
                                                    router.delete(route('kiosk.destroy', visit.id), {
                                                        preserveScroll: true,
                                                    });
                                                }
                                            }}
                                            className="rounded-md p-1.5 text-slate-300 transition hover:bg-rose-50 hover:text-rose-600"
                                            title="Batalkan kunjungan"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>

                    <Card className="mt-4" title="Rekap Keperluan Hari Ini">
                        <ul className="space-y-2.5">
                            {PURPOSES.map((purpose) => {
                                const total = Number(todaySummary?.purpose?.[purpose.value] ?? 0);
                                const percentage =
                                    (purposeTotal(todaySummary) ?? 0) > 0
                                        ? (total / purposeTotal(todaySummary)) * 100
                                        : 0;

                                return (
                                    <li key={purpose.value}>
                                        <div className="flex items-center justify-between text-[11px] font-semibold text-slate-600">
                                            <span>{purpose.label}</span>
                                            <span>{total} siswa</span>
                                        </div>
                                        <div className="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                            <div
                                                className="h-full rounded-full bg-amber-400"
                                                style={{ width: `${percentage}%` }}
                                            />
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function purposeTotal(summary) {
    if (!summary?.purpose) return 0;

    return Object.values(summary.purpose).reduce((total, value) => total + Number(value), 0);
}

