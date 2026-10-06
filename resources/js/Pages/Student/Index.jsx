import StudentLayout from '@/Layouts/StudentLayout';
import { Badge, Button, Card, EmptyState, Input, cn } from '@/Components/ui';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import {
    BookOpen,
    CheckCircle2,
    GraduationCap,
    Home,
    RotateCcw,
    Search,
    Send,
    SmilePlus,
    UserRound,
} from 'lucide-react';

const STEPS = ['Kelas', 'Nama', 'Keperluan', 'Buku'];

const LOAN_OPTIONS = [3, 7, 14, 30];

const PURPOSES = [
    { value: 'membaca', label: 'Membaca di Tempat', description: 'Buku tetap di perpustakaan', icon: BookOpen },
    { value: 'pinjam', label: 'Meminjam Buku', description: 'Bawa pulang ke rumah', icon: SmilePlus },
];

export default function StudentPortalIndex({
    classrooms = [],
    books = [],
    loanLimit = 2,
    loanDays = 7,
}) {
    const { flash } = usePage().props;
    const [step, setStep] = useState(1);
    const [classroom, setClassroom] = useState(null);
    const [student, setStudent] = useState(null);
    const [students, setStudents] = useState([]);
    const [loadingStudents, setLoadingStudents] = useState(false);
    const [search, setSearch] = useState('');
    const [bookSearch, setBookSearch] = useState('');
    const searchRef = useRef(null);

    const { data, setData, post, processing, reset } = useForm({
        student_id: '',
        purpose: '',
        book_id: '',
        loan_days: String(loanDays),
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
            .get(route('peminjaman.students'), { params: { classroom_id: classroom.id } })
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

    const availableBooks = useMemo(() => {
        const keyword = bookSearch.trim().toLowerCase();

        return books.filter((book) => {
            if (book.available <= 0) return false;
            if (!keyword) return true;

            return (
                book.title.toLowerCase().includes(keyword) ||
                (book.author ?? '').toLowerCase().includes(keyword)
            );
        });
    }, [books, bookSearch]);

    const resetFlow = () => {
        setStep(1);
        setClassroom(null);
        setStudent(null);
        setSearch('');
        setBookSearch('');
        reset();
    };

    const submit = (event) => {
        event.preventDefault();

        post(route('peminjaman.store'), {
            preserveScroll: true,
            onSuccess: () => setStep(5),
        });
    };

    const selectedBook = books.find((book) => String(book.id) === String(data.book_id));
    const borrowQuota = student ? loanLimit - (student.active_loans ?? 0) : loanLimit;

    return (
        <StudentLayout steps={STEPS} step={step}>
            <Head title="Buku Tamu Digital" />

            {flash?.error && (
                <div className="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
                    {flash.error}
                </div>
            )}

            {step <= 4 && (
                <div className="mb-5 text-center">
                    <h1 className="text-2xl font-extrabold tracking-tight text-slate-900">
                        Buku Tamu Digital
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Pilih kelas, namamu, keperluan, dan buku yang kamu baca atau pinjam.
                    </p>
                </div>
            )}

            {step <= 4 && (
                <ol className="mb-5 flex items-center justify-center gap-1.5">
                    {STEPS.map((label, index) => {
                        const number = index + 1;
                        const active = step === number;
                        const finished = step > number;

                        return (
                            <li key={label} className="flex items-center gap-1.5">
                                <span
                                    className={cn(
                                        'flex h-8 w-8 items-center justify-center rounded-full text-xs font-extrabold',
                                        finished
                                            ? 'bg-emerald-500 text-white'
                                            : active
                                              ? 'bg-emerald-600 text-white'
                                              : 'bg-slate-200 text-slate-500',
                                    )}
                                >
                                    {finished ? <CheckCircle2 className="h-4 w-4" /> : number}
                                </span>
                                <span
                                    className={cn(
                                        'hidden text-[11px] font-bold sm:block',
                                        active ? 'text-slate-800' : 'text-slate-400',
                                    )}
                                >
                                    {label}
                                </span>
                                {index < STEPS.length - 1 && (
                                    <span
                                        className={cn(
                                            'mx-0.5 h-0.5 w-6 rounded-full sm:w-10',
                                            step > number ? 'bg-emerald-400' : 'bg-slate-200',
                                        )}
                                    />
                                )}
                            </li>
                        );
                    })}
                </ol>
            )}

            {step === 5 && (
                <Card className="mx-auto max-w-xl">
                    <div className="flex flex-col items-center py-6 text-center">
                        <span className="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50">
                            <CheckCircle2 className="h-9 w-9 text-emerald-600" />
                        </span>
                        <h2 className="mt-4 text-lg font-extrabold text-slate-900">
                            Berhasil dicatat!
                        </h2>
                        <p className="mt-1.5 text-sm text-slate-600">
                            {flash?.success ?? 'Kunjunganmu sudah tersimpan.'}
                        </p>

                        {student && (
                            <div className="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-5 py-3 text-left text-xs text-slate-600">
                                <p>
                                    <span className="font-bold text-slate-800">Nama:</span>{' '}
                                    {student.name}
                                </p>
                                <p>
                                    <span className="font-bold text-slate-800">Kelas:</span>{' '}
                                    {classroom?.name}
                                </p>
                                {selectedBook && (
                                    <p>
                                        <span className="font-bold text-slate-800">Buku:</span>{' '}
                                        {selectedBook.title}
                                    </p>
                                )}
                            </div>
                        )}

                        <Button type="button" className="mt-5 px-5 py-3 text-sm" onClick={resetFlow}>
                            <RotateCcw className="h-4 w-4" />
                            Selesai — Kembali ke Awal
                        </Button>
                    </div>
                </Card>
            )}

            {step === 1 && (
                <Card>
                    <p className="mb-3 flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500">
                        <GraduationCap className="h-4 w-4 text-emerald-600" />
                        Pilih Kelas
                    </p>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
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
                                className="rounded-2xl border border-slate-200 bg-white px-3 py-6 text-center transition hover:border-emerald-400 hover:bg-emerald-50"
                            >
                                <span className="block text-xl font-extrabold text-slate-800">
                                    {item.name}
                                </span>
                                <span className="mt-0.5 block text-[11px] text-slate-500">
                                    {item.students_count} siswa
                                </span>
                            </button>
                        ))}
                    </div>
                </Card>
            )}


            {step === 2 && (
                <Card>
                    <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <p className="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500">
                            <UserRound className="h-4 w-4 text-emerald-600" />
                            Pilih Nama — {classroom?.name}
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
                            placeholder="Ketik nama atau NISN..."
                            className="py-3 pl-9"
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
                            description="Coba kata kunci lain, atau pilih kelas yang berbeda."
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
                                    <span className="text-[10px] text-slate-500">
                                        NISN {item.nisn}
                                    </span>
                                    {item.active_loans > 0 && (
                                        <Badge tone="amber" className="mt-1.5">
                                            {item.active_loans}/{loanLimit} dipinjam
                                        </Badge>
                                    )}
                                </button>
                            ))}
                        </div>
                    )}
                </Card>
            )}


            {step === 3 && (
                <Card>
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <div>
                            <p className="text-[10px] font-bold uppercase tracking-wide text-emerald-700">
                                Siswa terpilih
                            </p>
                            <p className="text-sm font-extrabold text-slate-900">{student?.name}</p>
                            <p className="text-[11px] text-slate-600">
                                NISN {student?.nisn} • Kelas {classroom?.name} • sisa kuota{' '}
                                {borrowQuota} dari {loanLimit}
                            </p>
                        </div>
                        <Button variant="secondary" type="button" onClick={() => setStep(2)}>
                            Ganti Siswa
                        </Button>
                    </div>

                    <p className="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">
                        Mau Lakukan Apa?
                    </p>
                    <div className="grid gap-3 sm:grid-cols-2">
                        {PURPOSES.map((item) => {
                            const Icon = item.icon;

                            return (
                                <button
                                    key={item.value}
                                    type="button"
                                    onClick={() => {
                                        setData({ ...data, purpose: item.value, book_id: '' });
                                        setStep(4);
                                    }}
                                    className="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 text-left transition hover:border-emerald-400 hover:bg-emerald-50"
                                >
                                    <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50">
                                        <Icon className="h-6 w-6 text-emerald-600" />
                                    </span>
                                    <span>
                                        <span className="block text-base font-extrabold text-slate-800">
                                            {item.label}
                                        </span>
                                        <span className="block text-[11px] text-slate-500">
                                            {item.description}
                                        </span>
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </Card>
            )}


            {step === 4 && (
                <form onSubmit={submit}>
                    <Card>
                        <div className="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div>
                                <p className="text-[10px] font-bold uppercase tracking-wide text-slate-500">
                                    {student?.name} • {classroom?.name}
                                </p>
                                <p className="text-sm font-extrabold text-slate-900">
                                    {data.purpose === 'pinjam'
                                        ? 'Pilih buku yang mau dipinjam'
                                        : 'Pilih buku yang mau dibaca'}
                                </p>
                                <p className="text-[11px] text-slate-500">
                                    {data.purpose === 'pinjam'
                                        ? `Pinjaman ${data.loan_days || loanDays} hari. Sisa kuota ${borrowQuota} dari ${loanLimit}.`
                                        : 'Buku tetap di perpustakaan, boleh dikosongkan.'}
                                </p>
                            </div>
                            <Button variant="secondary" type="button" onClick={() => setStep(3)}>
                                Ganti Keperluan
                            </Button>
                        </div>

                        <div className="relative mb-3">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                value={bookSearch}
                                onChange={(event) => setBookSearch(event.target.value)}
                                placeholder="Ketik judul atau penulis..."
                                className="py-3 pl-9"
                            />
                        </div>



                        {availableBooks.length === 0 ? (
                            <EmptyState
                                icon={BookOpen}
                                title="Buku tidak ditemukan"
                                description="Coba kata kunci lain."
                            />
                        ) : (
                            <div className="grid max-h-[380px] gap-2.5 overflow-y-auto pr-1 sm:grid-cols-2">
                                {availableBooks.map((book) => {
                                    const picked = String(data.book_id) === String(book.id);

                                    return (
                                        <button
                                            key={book.id}
                                            type="button"
                                            onClick={() =>
                                                setData('book_id', picked ? '' : String(book.id))
                                            }
                                            className={cn(
                                                'rounded-xl border px-3 py-3 text-left transition',
                                                picked
                                                    ? 'border-emerald-500 bg-emerald-50'
                                                    : 'border-slate-200 bg-white hover:border-emerald-400 hover:bg-emerald-50',
                                            )}
                                        >
                                            <span className="block text-sm font-bold text-slate-800">
                                                {book.title}
                                            </span>
                                            <span className="block text-[11px] text-slate-500">
                                                {book.author}
                                            </span>
                                            <span className="mt-1.5 flex flex-wrap gap-1.5">
                                                <Badge tone={book.category_color ?? 'slate'}>
                                                    {book.category ?? '-'}
                                                </Badge>
                                                <Badge tone="slate">Sisa {book.available}</Badge>
                                                {book.shelf && <Badge tone="sky">{book.shelf}</Badge>}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                        )}

                        {data.purpose === 'membaca' && (
                            <button
                                type="button"
                                onClick={() => setData('book_id', '')}
                                className="mt-3 w-full rounded-xl border border-dashed border-slate-300 px-3 py-3 text-xs font-bold text-slate-500 transition hover:border-emerald-400 hover:text-emerald-700"
                            >
                                Tidak memilih buku tertentu
                            </button>
                        )}

                        <div className="mt-4 grid gap-3 sm:grid-cols-2">
                            {data.purpose === 'pinjam' && (
                                <div>
                                    <p className="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">
                                        Lama Pinjam
                                    </p>
                                    <div className="grid grid-cols-4 gap-2">
                                        {LOAN_OPTIONS.map((days) => (
                                            <button
                                                key={days}
                                                type="button"
                                                onClick={() =>
                                                    setData('loan_days', String(days))
                                                }
                                                className={cn(
                                                    'rounded-xl border px-2 py-3 text-center transition',
                                                    String(data.loan_days) === String(days)
                                                        ? 'border-emerald-500 bg-emerald-50'
                                                        : 'border-slate-200 bg-white hover:border-emerald-400 hover:bg-emerald-50',
                                                )}
                                            >
                                                <span className="block text-sm font-extrabold text-slate-800">
                                                    {days}
                                                </span>
                                                <span className="text-[10px] text-slate-500">hari</span>
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}

                            <div>
                                <p className="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">
                                    Catatan (opsional)
                                </p>
                                <Input
                                    value={data.notes}
                                    onChange={(event) => setData('notes', event.target.value)}
                                    placeholder="Misal: untuk tugas kelompok"
                                    maxLength={150}
                                />
                            </div>
                        </div>

                        {processing && (
                            <p className="mt-3 text-center text-xs text-slate-500">Menyimpan...</p>
                        )}

                        {borrowQuota <= 0 && (
                            <p className="mt-3 flex items-center justify-center gap-1.5 text-center text-xs font-semibold text-amber-700">
                                <Home className="h-3.5 w-3.5" />
                                Kuota peminjamanmu sudah penuh. Kembalikan buku dulu ya.
                            </p>
                        )}

                        <div className="mt-4 flex justify-end">
                            <Button
                                type="submit"
                                className="px-5 py-3 text-sm"
                                disabled={processing || (data.purpose === 'pinjam' && !data.book_id)}
                            >
                                <Send className="h-4 w-4" />
                                Simpan Kunjungan
                            </Button>
                        </div>
                    </Card>
                </form>
            )}
        </StudentLayout>
    );
}

