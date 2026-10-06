import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import {
    Badge,
    Button,
    Card,
    EmptyState,
    Field,
    Input,
    PageHeader,
    Select,
    cn,
} from '@/Components/ui';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { CanManage } from '@/hooks/useCanManage';
import {
    BookOpen,
    Download,
    Library,
    Package,
    Pencil,
    Plus,
    RefreshCw,
    Search,
    Trash2,
    X,
} from 'lucide-react';

const EMPTY_FORM = {
    code: '',
    isbn: '',
    title: '',
    author: '',
    publisher: '',
    published_year: '',
    category_id: '',
    book_type: 'koleksi',
    grade_level: '',
    shelf_location: '',
    funding_source: '',
    total_copies: 1,
};

const TYPE_LABEL = { paket: 'Buku Paket', koleksi: 'Koleksi' };

export default function BooksIndex({ books, categories = [], filters = {}, summary, nextCode }) {
    const [filterState, setFilterState] = useState({
        search: filters.search ?? '',
        book_type: filters.book_type ?? '',
        category_id: filters.category_id ?? '',
    });
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);

    const form = useForm({ ...EMPTY_FORM });

    // (next) dipakai saat filter dropdown berubah: langsung terapkan tanpa
    // perlu tekan Enter. Tanpa argumen dipakai saat form disubmit (Enter).
    const applyFilters = (next) => {
        const params = next ? { ...filterState, ...next } : filterState;

        if (next) setFilterState(params);

        router.get(route('books.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const openCreate = () => {
        setEditing(null);
        form.setData({ ...EMPTY_FORM, code: nextCode ?? '', category_id: categories[0]?.id ?? '' });
        form.clearErrors();
        setShowModal(true);
    };

    const openEdit = (book) => {
        setEditing(book);
        form.setData({
            code: book.code ?? '',
            isbn: book.isbn ?? '',
            title: book.title ?? '',
            author: book.author ?? '',
            publisher: book.publisher ?? '',
            published_year: book.published_year ?? '',
            category_id: book.category_id ?? '',
            book_type: book.book_type ?? 'koleksi',
            grade_level: book.grade_level ?? '',
            shelf_location: book.shelf_location ?? '',
            funding_source: book.funding_source ?? '',
            total_copies: book.total_copies ?? 1,
        });
        form.clearErrors();
        setShowModal(true);
    };

    const closeModal = () => {
        setShowModal(false);
        setEditing(null);
        form.reset();
    };

    const submitForm = (event) => {
        event.preventDefault();

        if (editing) {
            form.put(route('books.update', editing.id), { onSuccess: closeModal, preserveScroll: true });
        } else {
            form.post(route('books.store'), { onSuccess: closeModal, preserveScroll: true });
        }
    };

    const deleteBook = (book) => {
        if (confirm(`Hapus buku "${book.title}" dari katalog?`)) {
            router.delete(route('books.destroy', book.id), { preserveScroll: true });
        }
    };

    const syncStock = () => {
        router.post(route('books.sync-stock'), {}, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Katalog Koleksi Buku"
                    subtitle="Kelola buku paket Kurikulum Merdeka dan koleksi bacaan siswa"
                >
                    <CanManage>
                        <Button variant="secondary" type="button" onClick={syncStock}>
                            <RefreshCw className="h-3.5 w-3.5" />
                            Sinkronkan Stok
                        </Button>
                    </CanManage>
                    <a
                        href={route('books.export')}
                        className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                    >
                        <Download className="h-3.5 w-3.5" />
                        Ekspor Excel
                    </a>
                    <CanManage>
                        <Button type="button" onClick={openCreate}>
                            <Plus className="h-3.5 w-3.5" />
                            Tambah Buku
                        </Button>
                    </CanManage>
                </PageHeader>
            }
        >
            <Head title="Katalog Buku" />

            <div className="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Total Judul</p>
                    <p className="mt-1 text-xl font-extrabold text-slate-900">{summary?.titles ?? 0}</p>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Total Eksemplar</p>
                    <p className="mt-1 text-xl font-extrabold text-emerald-700">{summary?.copies ?? 0}</p>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Judul Buku Paket</p>
                    <p className="mt-1 text-xl font-extrabold text-amber-600">
                        {summary?.package_titles ?? 0}
                    </p>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Judul Koleksi</p>
                    <p className="mt-1 text-xl font-extrabold text-sky-700">
                        {summary?.collection_titles ?? 0}
                    </p>
                </div>
            </div>

            <Card bodyClass="p-4" className="mb-4">
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        applyFilters();
                    }}
                    className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <Field label="Cari Judul / Kode / Pengarang">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                value={filterState.search}
                                onChange={(event) =>
                                    setFilterState({ ...filterState, search: event.target.value })
                                }
                                placeholder="Misal: Matematika atau BK-0001"
                                className="pl-9"
                            />
                        </div>
                    </Field>
                    <Field label="Jenis Buku">
                        <Select
                            value={filterState.book_type}
                            onChange={(event) => applyFilters({ book_type: event.target.value })}
                        >
                            <option value="">Semua jenis</option>
                            <option value="paket">Buku Paket</option>
                            <option value="koleksi">Koleksi Bacaan</option>
                        </Select>
                    </Field>
                    <Field label="Kategori">
                        <Select
                            value={filterState.category_id}
                            onChange={(event) => applyFilters({ category_id: event.target.value })}
                        >
                            <option value="">Semua kategori</option>
                            {categories.map((category) => (
                                <option key={category.id} value={category.id}>
                                    {category.code} · {category.name}
                                </option>
                            ))}
                        </Select>
                    </Field>
                    <div className="flex items-end gap-2">
                        <Button type="submit" className="flex-1">
                            <Search className="h-3.5 w-3.5" />
                            Cari
                        </Button>
                        <Button
                            variant="secondary"
                            type="button"
                            onClick={() => {
                                const cleared = { search: '', book_type: '', category_id: '' };
                                setFilterState(cleared);
                                router.get(route('books.index'), cleared, { preserveScroll: true });
                            }}
                        >
                            Reset
                        </Button>
                    </div>
                </form>
            </Card>
            <Card bodyClass="p-0">
                {books.data.length === 0 ? (
                    <div className="p-5">
                        <EmptyState
                            icon={BookOpen}
                            title="Belum ada buku yang cocok"
                            description="Tambahkan buku baru atau ubah kata kunci pencarian."
                            action={
                                <CanManage>
                                    <Button type="button" onClick={openCreate}>
                                        <Plus className="h-3.5 w-3.5" />
                                        Tambah Buku
                                    </Button>
                                </CanManage>
                            }
                        />
                    </div>
                ) : (
                    <>
                        <div className="w-full overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead className="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="hidden px-3 py-2.5 font-bold xl:table-cell">Kode</th>
                                        <th className="px-4 py-2.5 font-bold">Judul &amp; Penerbit</th>
                                        <th className="hidden px-3 py-2.5 font-bold lg:table-cell">Kategori</th>
                                        <th className="px-3 py-2.5 font-bold">Jenis</th>
                                        <th className="hidden px-3 py-2.5 font-bold xl:table-cell">Rak</th>
                                        <th className="px-3 py-2.5 font-bold">Stok</th>
                                        <th className="px-4 py-2.5 text-right font-bold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {books.data.map((book) => {
                                        const ratio =
                                            book.total_copies > 0
                                                ? (book.available_copies / book.total_copies) * 100
                                                : 0;

                                        return (
                                            <tr key={book.id} className="hover:bg-slate-50/70">
                                                <td className="hidden px-3 py-2.5 font-mono text-[11px] font-bold text-slate-500 xl:table-cell">
                                                    {book.code}
                                                </td>
                                                <td className="px-4 py-2.5">
                                                    <p className="truncate font-bold text-slate-800 max-w-[16rem]">{book.title}</p>
                                                    <p className="truncate text-[11px] text-slate-500 max-w-[16rem]">
                                                        {book.author ?? '-'}
                                                        {book.publisher ? ` · ${book.publisher}` : ''}
                                                    </p>
                                                    <p className="truncate font-mono text-[10px] text-slate-400 xl:hidden">
                                                        {book.code}
                                                    </p>
                                                </td>
                                                <td className="hidden px-3 py-2.5 lg:table-cell">
                                                    <Badge tone={book.category_color ?? 'slate'}>
                                                        {book.category ?? '-'}
                                                    </Badge>
                                                </td>
                                                <td className="px-3 py-2.5">
                                                    <Badge tone={book.book_type === 'paket' ? 'amber' : 'sky'}>
                                                        {book.book_type === 'paket' ? (
                                                            <Package className="h-3 w-3" />
                                                        ) : (
                                                            <Library className="h-3 w-3" />
                                                        )}
                                                        {TYPE_LABEL[book.book_type] ?? book.book_type}
                                                    </Badge>
                                                    {book.grade_level ? (
                                                        <p className="mt-1 text-[10px] text-slate-500">
                                                            Target Kelas {book.grade_level}
                                                        </p>
                                                    ) : null}
                                                </td>
                                                <td className="hidden whitespace-nowrap px-3 py-2.5 text-slate-500 xl:table-cell">
                                                    {book.shelf_location ?? '-'}
                                                </td>
                                                <td className="whitespace-nowrap px-3 py-2.5">
                                                    <p className="font-bold text-slate-700">
                                                        {book.available_copies}
                                                        <span className="font-normal text-slate-400">
                                                            /{book.total_copies}
                                                        </span>
                                                    </p>
                                                    <div className="mt-1 h-1.5 w-16 overflow-hidden rounded-full bg-slate-100">
                                                        <div
                                                            className={cn(
                                                                'h-full rounded-full',
                                                                ratio > 40
                                                                    ? 'bg-emerald-500'
                                                                    : ratio > 0
                                                                      ? 'bg-amber-500'
                                                                      : 'bg-rose-500',
                                                            )}
                                                            style={{ width: `${ratio}%` }}
                                                        />
                                                    </div>
                                                </td>
                                                <td className="px-4 py-2.5">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <CanManage>
                                                            <button
                                                                type="button"
                                                                onClick={() => openEdit(book)}
                                                                className="rounded-md p-1.5 text-slate-400 transition hover:bg-sky-50 hover:text-sky-600"
                                                                title="Ubah data buku"
                                                            >
                                                                <Pencil className="h-4 w-4" />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => deleteBook(book)}
                                                                className="rounded-md p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                                                title="Hapus buku"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </button>
                                                        </CanManage>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>

                        <Pagination meta={books.meta ?? books} links={books.links ?? []} />
                    </>
                )}
            </Card>
            <Modal show={showModal} onClose={closeModal} maxWidth="2xl">
                <form onSubmit={submitForm}>
                    <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div>
                            <h3 className="text-sm font-extrabold text-slate-900">
                                {editing ? 'Ubah Data Buku' : 'Tambah Buku Baru'}
                            </h3>
                            <p className="text-[11px] text-slate-500">
                                Kode buku otomatis berurutan, silakan ubah bila perlu.
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={closeModal}
                            className="rounded-md p-1.5 text-slate-400 hover:bg-slate-100"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>

                    <div className="grid max-h-[70vh] gap-4 overflow-y-auto px-5 py-5 sm:grid-cols-2">
                        <Field label="Kode Buku" error={form.errors.code}>
                            <Input
                                value={form.data.code}
                                onChange={(event) => form.setData('code', event.target.value)}
                                placeholder="BK-0001"
                            />
                        </Field>
                        <Field label="ISBN" error={form.errors.isbn}>
                            <Input
                                value={form.data.isbn}
                                onChange={(event) => form.setData('isbn', event.target.value)}
                                placeholder="979-123-456-7-9"
                            />
                        </Field>
                        <Field label="Judul Buku" error={form.errors.title}>
                            <Input
                                value={form.data.title}
                                onChange={(event) => form.setData('title', event.target.value)}
                                placeholder="Matematika Kelas 1"
                            />
                        </Field>
                        <Field label="Pengarang" error={form.errors.author}>
                            <Input
                                value={form.data.author}
                                onChange={(event) => form.setData('author', event.target.value)}
                            />
                        </Field>
                        <Field label="Penerbit" error={form.errors.publisher}>
                            <Input
                                value={form.data.publisher}
                                onChange={(event) => form.setData('publisher', event.target.value)}
                            />
                        </Field>
                        <Field label="Tahun Terbit" error={form.errors.published_year}>
                            <Input
                                type="number"
                                value={form.data.published_year}
                                onChange={(event) => form.setData('published_year', event.target.value)}
                                placeholder="2024"
                            />
                        </Field>
                        <Field label="Kategori" error={form.errors.category_id}>
                            <Select
                                value={form.data.category_id}
                                onChange={(event) => form.setData('category_id', event.target.value)}
                            >
                                <option value="">— Pilih kategori —</option>
                                {categories.map((category) => (
                                    <option key={category.id} value={category.id}>
                                        {category.code} · {category.name}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Jenis Buku" error={form.errors.book_type}>
                            <Select
                                value={form.data.book_type}
                                onChange={(event) => form.setData('book_type', event.target.value)}
                            >
                                <option value="koleksi">Koleksi Bacaan</option>
                                <option value="paket">Buku Paket (Kurikulum)</option>
                            </Select>
                        </Field>
                        <Field
                            label="Target Kelas"
                            hint="Wajib untuk buku paket (1-6), kosongkan untuk koleksi umum"
                            error={form.errors.grade_level}
                        >
                            <Select
                                value={form.data.grade_level}
                                onChange={(event) => form.setData('grade_level', event.target.value)}
                            >
                                <option value="">— Tidak spesifik —</option>
                                {[1, 2, 3, 4, 5, 6].map((grade) => (
                                    <option key={grade} value={grade}>
                                        Kelas {grade}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Lokasi Rak" error={form.errors.shelf_location}>
                            <Input
                                value={form.data.shelf_location}
                                onChange={(event) => form.setData('shelf_location', event.target.value)}
                                placeholder="Rak A-1"
                            />
                        </Field>
                        <Field label="Sumber Dana" error={form.errors.funding_source}>
                            <Input
                                value={form.data.funding_source}
                                onChange={(event) => form.setData('funding_source', event.target.value)}
                                placeholder="Dana BOS / Hibah"
                            />
                        </Field>
                        <Field
                            label="Jumlah Eksemplar"
                            hint="Stok tersedia otomatis mengikuti jumlah eksemplar"
                            error={form.errors.total_copies}
                        >
                            <Input
                                type="number"
                                min="1"
                                value={form.data.total_copies}
                                onChange={(event) => form.setData('total_copies', event.target.value)}
                            />
                        </Field>
                    </div>

                    <div className="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
                        <Button variant="secondary" type="button" onClick={closeModal}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {editing ? 'Simpan Perubahan' : 'Simpan Buku'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
