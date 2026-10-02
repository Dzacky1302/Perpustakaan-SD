import Modal from '@/Components/Modal';
import { Badge, Button, Field, Input, cn } from '@/Components/ui';
import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { CheckCircle2, Loader2, ScanLine, X } from 'lucide-react';

/**
 * Form "Catat Peminjaman".
 *
 * Kolom buku sengaja hanya satu: petugas mengetik apa pun yang ada di
 * tangannya, lalu sistem menentukan sendiri apakah itu barcode, ISBN, kode
 * internal, atau judul. Hasil pembacaan ditampilkan terbuka supaya petugas
 * tahu persis apa yang dipinjam, termasuk nomor registrasi eksemplarnya.
 */
export default function BorrowModal({ show, onClose, students = [], loanDays = 7, maxLoans = 2 }) {
    const today = new Date().toISOString().slice(0, 10);

    const form = useForm({
        student_id: '',
        book_id: '',
        book_copy_id: '',
        book_query: '',
        borrowed_at: today,
        loan_days: loanDays,
        notes: '',
    });

    const [bookResults, setBookResults] = useState([]);
    const [searching, setSearching] = useState(false);
    const [studentQuery, setStudentQuery] = useState('');
    const debounce = useRef(null);

    const selected = bookResults.find((book) => String(book.id) === String(form.data.book_id));
    const student = students.find((item) => String(item.id) === String(form.data.student_id));
    const quotaLeft = student ? maxLoans - (student.active_loans ?? 0) : maxLoans;

    const filteredStudents = students
        .filter((item) => {
            if (!studentQuery) return true;
            const needle = studentQuery.toLowerCase();

            return item.name.toLowerCase().includes(needle) || String(item.nisn ?? '').includes(needle);
        })
        .slice(0, 40);

    useEffect(() => {
        if (!show) return;

        form.reset();
        form.setData({ borrowed_at: today, loan_days: loanDays });
        setBookResults([]);
        setStudentQuery('');
    }, [show]);

    // Pencarian ditunda supaya tidak memanggil server tiap ketikan.
    const searchBooks = (term) => {
        form.setData('book_query', term);
        clearTimeout(debounce.current);

        if (!term.trim()) {
            setBookResults([]);

            return;
        }

        setSearching(true);
        debounce.current = setTimeout(() => {
            router.get(
                route('loans.search-books'),
                { q: term },
                {
                    preserveScroll: true,
                    onSuccess: (page) => {
                        setBookResults(page.props.books ?? []);
                        setSearching(false);
                    },
                    onError: () => setSearching(false),
                },
            );
        }, 250);
    };

    const pick = (book) => {
        form.setData({
            book_id: book.id,
            book_copy_id: book.book_copy_id ?? '',
            book_query: book.title,
        });
        setBookResults([]);
    };

    const submit = (event) => {
        event.preventDefault();

        form.post(route('loans.store'), {
            preserveScroll: true,
            onSuccess: () => onClose?.(),
        });
    };

    const MATCH_LABEL = {
        barcode: 'dari barcode',
        isbn: 'dari ISBN',
        kode: 'dari kode buku',
        judul: 'dari pencarian judul',
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="2xl">
            <form onSubmit={submit} className="space-y-4">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h2 className="text-base font-bold text-slate-900">Catat Peminjaman</h2>
                        <p className="mt-0.5 text-xs text-slate-500">
                            Scan barcode, atau ketik ISBN, kode buku, atau judul.
                        </p>
                    </div>
                    <button type="button" onClick={onClose} className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                        <X className="h-4 w-4" />
                    </button>
                </div>
                <Field label="Buku" error={form.errors.book_id || form.errors.book_query}>
                    <div className="relative">
                        <ScanLine className="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                        <Input
                            value={form.data.book_query}
                            onChange={(event) => searchBooks(event.target.value)}
                            placeholder="8991234567890 / 979-123-456-7-9 / BK-0001 / judul"
                            className="pl-9"
                            autoComplete="off"
                        />
                        {searching && <Loader2 className="absolute right-3 top-2.5 h-4 w-4 animate-spin text-slate-400" />}
                    </div>

                    {bookResults.length > 0 && (
                        <div className="mt-1.5 max-h-56 overflow-y-auto rounded-xl border border-slate-200 bg-white">
                            {bookResults.map((book) => (
                                <button
                                    key={`${book.id}-${book.book_copy_id ?? 'x'}`}
                                    type="button"
                                    onClick={() => pick(book)}
                                    className="flex w-full items-start justify-between gap-3 border-b border-slate-100 px-3 py-2 text-left last:border-0 hover:bg-slate-50"
                                >
                                    <span className="min-w-0">
                                        <span className="block truncate text-sm font-semibold text-slate-800">{book.title}</span>
                                        <span className="block truncate text-xs text-slate-500">
                                            {book.code}
                                            {book.isbn ? ` · ${book.isbn}` : ''}
                                            {book.author ? ` · ${book.author}` : ''}
                                        </span>
                                    </span>
                                    <span className="shrink-0 text-right">
                                        <span
                                            className={cn(
                                                'block text-xs font-bold',
                                                book.available_copies > 0 ? 'text-emerald-600' : 'text-rose-600',
                                            )}
                                        >
                                            {book.available_copies > 0 ? `Tersedia ${book.available_copies}` : 'Kosong'}
                                        </span>
                                        {book.accession_number && (
                                            <span className="block text-[10px] text-slate-400">{book.accession_number}</span>
                                        )}
                                    </span>
                                </button>
                            ))}
                        </div>
                    )}
                </Field>

                {selected && (
                    <div className="flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                        <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                        <div className="min-w-0 text-xs text-emerald-900">
                            <p className="font-bold">{selected.title}</p>
                            <p className="mt-0.5">
                                {selected.matched_by ? MATCH_LABEL[selected.matched_by] : 'dipilih dari daftar'}
                                {selected.accession_number ? ` · eksemplar ${selected.accession_number}` : ''}
                                {selected.barcode ? ` · barcode ${selected.barcode}` : ''}
                            </p>
                        </div>
                    </div>
                )}
                <Field label="Siswa" error={form.errors.student_id}>
                    <Input
                        value={studentQuery}
                        onChange={(event) => setStudentQuery(event.target.value)}
                        placeholder="Cari nama atau NISN…"
                        autoComplete="off"
                    />
                    <div className="mt-1.5 max-h-44 overflow-y-auto rounded-xl border border-slate-200">
                        {filteredStudents.map((item) => (
                            <button
                                key={item.id}
                                type="button"
                                onClick={() => {
                                    form.setData('student_id', item.id);
                                    setStudentQuery('');
                                }}
                                className={cn(
                                    'flex w-full items-center justify-between gap-3 border-b border-slate-100 px-3 py-2 text-left last:border-0',
                                    String(form.data.student_id) === String(item.id) ? 'bg-emerald-50' : 'hover:bg-slate-50',
                                )}
                            >
                                <span className="min-w-0">
                                    <span className="block truncate text-sm font-medium text-slate-800">{item.name}</span>
                                    <span className="block truncate text-xs text-slate-500">
                                        {item.classroom ?? '-'} · {item.nisn}
                                    </span>
                                </span>
                                <Badge tone={item.active_loans >= maxLoans ? 'rose' : 'slate'}>
                                    {item.active_loans}/{maxLoans}
                                </Badge>
                            </button>
                        ))}
                        {filteredStudents.length === 0 && (
                            <p className="px-3 py-4 text-center text-xs text-slate-500">Tidak ada siswa yang cocok.</p>
                        )}
                    </div>
                </Field>

                {student && quotaLeft <= 0 && (
                    <p className="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">
                        {student.name} sudah punya {student.active_loans} buku dipinjam. Kembalikan dulu sebelum meminjam lagi.
                    </p>
                )}

                <div className="grid gap-3 sm:grid-cols-2">
                    <Field label="Tanggal Pinjam" error={form.errors.borrowed_at}>
                        <Input
                            type="date"
                            value={form.data.borrowed_at}
                            onChange={(event) => form.setData('borrowed_at', event.target.value)}
                        />
                    </Field>
                    <Field label="Lama Pinjam (hari)" error={form.errors.loan_days}>
                        <Input
                            type="number"
                            min="1"
                            max="30"
                            value={form.data.loan_days}
                            onChange={(event) => form.setData('loan_days', event.target.value)}
                        />
                    </Field>
                </div>

                <Field label="Catatan (opsional)" error={form.errors.notes}>
                    <Input
                        value={form.data.notes}
                        onChange={(event) => form.setData('notes', event.target.value)}
                        placeholder="Mis. buku dalam kondisi baik"
                    />
                </Field>

                <div className="flex justify-end gap-2 pt-1">
                    <Button type="button" onClick={onClose} variant="secondary">
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        disabled={form.processing || !form.data.student_id || !form.data.book_id || quotaLeft <= 0}
                    >
                        {form.processing ? 'Menyimpan…' : 'Catat Peminjaman'}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}