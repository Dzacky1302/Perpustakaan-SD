import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import { Badge, Button, Card, EmptyState, Field, Input, PageHeader, Select } from '@/Components/ui';
import { Head, router, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { CanManage } from '@/hooks/useCanManage';
import {
    Download,
    FileText,
    Pencil,
    Plus,
    Search,
    Trash2,
    Upload,
    UserRound,
    X,
} from 'lucide-react';

const EMPTY = { nisn: '', name: '', gender: 'L', classroom_id: '', is_active: true };

export default function StudentsIndex({ students, classrooms = [], filters = {} }) {
    const [filterState, setFilterState] = useState({
        search: filters.search ?? '',
        classroom_id: filters.classroom_id ?? '',
    });
    const [showModal, setShowModal] = useState(false);
    const [showImport, setShowImport] = useState(false);
    const [editing, setEditing] = useState(null);
    const fileRef = useRef(null);

    const form = useForm({ ...EMPTY });
    const importForm = useForm({ file: null, classroom_id: '' });

    // (next) dipakai saat filter dropdown berubah: langsung terapkan tanpa
    // perlu tekan Enter. Tanpa argumen dipakai saat form disubmit (Enter).
    const applyFilters = (next) => {
        const params = next ? { ...filterState, ...next } : filterState;

        if (next) setFilterState(params);

        router.get(route('students.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const openCreate = () => {
        setEditing(null);
        form.setData({ ...EMPTY, classroom_id: classrooms[0]?.id ?? '' });
        form.clearErrors();
        setShowModal(true);
    };

    const openEdit = (student) => {
        setEditing(student);
        form.setData({
            nisn: student.nisn ?? '',
            name: student.name ?? '',
            gender: student.gender ?? 'L',
            classroom_id: student.classroom_id ?? '',
            is_active: Boolean(student.is_active),
        });
        form.clearErrors();
        setShowModal(true);
    };

    const closeModal = () => {
        setShowModal(false);
        setEditing(null);
        form.reset();
    };

    const submit = (event) => {
        event.preventDefault();

        if (editing) {
            form.put(route('students.update', editing.id), { onSuccess: closeModal, preserveScroll: true });
        } else {
            form.post(route('students.store'), { onSuccess: closeModal, preserveScroll: true });
        }
    };

    const submitImport = (event) => {
        event.preventDefault();

        importForm.post(route('students.import'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setShowImport(false);
                importForm.reset();
                if (fileRef.current) fileRef.current.value = '';
            },
        });
    };

    const remove = (student) => {
        if (confirm(`Hapus data siswa ${student.name}?`)) {
            router.delete(route('students.destroy', student.id), { preserveScroll: true });
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Data Siswa"
                    subtitle="Daftar anggota perpustakaan beserta tanggungan peminjaman"
                >
                    <CanManage>
                        <Button variant="secondary" type="button" onClick={() => setShowImport(true)}>
                            <Upload className="h-3.5 w-3.5" />
                            Impor Excel
                        </Button>
                        <Button type="button" onClick={openCreate}>
                            <Plus className="h-3.5 w-3.5" />
                            Tambah Siswa
                        </Button>
                    </CanManage>
                </PageHeader>
            }
        >
            <Head title="Data Siswa" />
            <Card bodyClass="p-4" className="mb-4">
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        applyFilters();
                    }}
                    className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <Field label="Cari Nama / NISN" className="lg:col-span-2">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                value={filterState.search}
                                onChange={(event) =>
                                    setFilterState({ ...filterState, search: event.target.value })
                                }
                                placeholder="Ketik nama siswa atau NISN"
                                className="pl-9"
                            />
                        </div>
                    </Field>
                    <Field label="Kelas">
                        <Select
                            value={filterState.classroom_id}
                            onChange={(event) => applyFilters({ classroom_id: event.target.value })}
                        >
                            <option value="">Semua kelas</option>
                            {classrooms.map((classroom) => (
                                <option key={classroom.id} value={classroom.id}>
                                    {classroom.name}
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
                                const cleared = { search: '', classroom_id: '' };
                                setFilterState(cleared);
                                router.get(route('students.index'), cleared, { preserveScroll: true });
                            }}
                        >
                            Reset
                        </Button>
                    </div>
                </form>
            </Card>

            <Card bodyClass="p-0">
                {students.data.length === 0 ? (
                    <div className="p-5">
                        <EmptyState
                            icon={UserRound}
                            title="Belum ada data siswa"
                            description="Tambahkan satu per satu atau impor sekaligus dari berkas Excel."
                            action={
                                <div className="flex flex-wrap items-center justify-center gap-2">
                                    <Button type="button" onClick={openCreate}>
                                        <Plus className="h-3.5 w-3.5" />
                                        Tambah Siswa
                                    </Button>
                                    <Button variant="secondary" type="button" onClick={() => setShowImport(true)}>
                                        <Upload className="h-3.5 w-3.5" />
                                        Impor Excel
                                    </Button>
                                </div>
                            }
                        />
                    </div>
                ) : (
                    <>
                        <div className="w-full overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead className="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="hidden px-3 py-2.5 font-bold xl:table-cell">NISN</th>
                                        <th className="px-4 py-2.5 font-bold">Nama Siswa</th>
                                        <th className="hidden px-3 py-2.5 font-bold lg:table-cell">L/P</th>
                                        <th className="px-3 py-2.5 font-bold">Kelas</th>
                                        <th className="px-3 py-2.5 font-bold">Tanggungan</th>
                                        <th className="px-3 py-2.5 font-bold">Status</th>
                                        <th className="px-5 py-3 text-right font-bold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {students.data.map((student) => (
                                        <tr key={student.id} className="hover:bg-slate-50/70">
                                            <td className="hidden px-3 py-2.5 font-mono text-[11px] font-bold text-slate-500 xl:table-cell">
                                                {student.nisn}
                                            </td>
                                            <td className="px-4 py-2.5">
                                                <p className="truncate font-bold text-slate-800 max-w-[11rem]">{student.name}</p>
                                                <p className="truncate font-mono text-[10px] text-slate-400 xl:hidden">
                                                    {student.nisn}
                                                </p>
                                            </td>
                                            <td className="hidden px-3 py-2.5 lg:table-cell">
                                                <Badge tone={student.gender === 'P' ? 'rose' : 'sky'}>
                                                    {student.gender === 'P' ? 'Perempuan' : 'Laki-laki'}
                                                </Badge>
                                            </td>
                                            <td className="whitespace-nowrap px-3 py-2.5 font-semibold text-slate-600">
                                                {student.classroom ?? '-'}
                                            </td>
                                            <td className="px-3 py-2.5">
                                                <div className="flex flex-wrap gap-1">
                                                    <Badge tone={student.active_loans > 0 ? 'amber' : 'slate'}>
                                                        {student.active_loans} koleksi
                                                    </Badge>
                                                    <Badge tone={student.package_loans > 0 ? 'violet' : 'slate'}>
                                                        {student.package_loans} paket
                                                    </Badge>
                                                </div>
                                            </td>
                                            <td className="px-3 py-3">
                                                <Badge tone={student.is_active ? 'emerald' : 'rose'}>
                                                    {student.is_active ? 'Aktif' : 'Nonaktif'}
                                                </Badge>
                                            </td>
                                            <td className="px-5 py-3">
                                                <div className="flex items-center justify-end gap-1.5">
                                                    <a
                                                        href={route('students.free-certificate', student.id)}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="rounded-md p-1.5 text-slate-400 transition hover:bg-amber-50 hover:text-amber-600"
                                                        title="Cetak Surat Bebas Pustaka"
                                                    >
                                                        <FileText className="h-4 w-4" />
                                                    </a>
                                                    <CanManage>
                                                        <button
                                                            type="button"
                                                            onClick={() => openEdit(student)}
                                                            className="rounded-md p-1.5 text-slate-400 transition hover:bg-sky-50 hover:text-sky-600"
                                                            title="Ubah data siswa"
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => remove(student)}
                                                            className="rounded-md p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                                            title="Hapus data siswa"
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

                        <Pagination meta={students.meta ?? students} links={students.links ?? []} />
                    </>
                )}
            </Card>
            <Modal show={showModal} onClose={closeModal} maxWidth="lg">
                <form onSubmit={submit}>
                    <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h3 className="text-sm font-extrabold text-slate-900">
                            {editing ? `Ubah Data ${editing.name}` : 'Tambah Siswa Baru'}
                        </h3>
                        <button
                            type="button"
                            onClick={closeModal}
                            className="rounded-md p-1.5 text-slate-400 hover:bg-slate-100"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>

                    <div className="grid gap-4 px-5 py-5 sm:grid-cols-2">
                        <Field label="NISN" error={form.errors.nisn}>
                            <Input
                                value={form.data.nisn}
                                onChange={(event) => form.setData('nisn', event.target.value)}
                                placeholder="0012345678"
                            />
                        </Field>
                        <Field label="Nama Lengkap" error={form.errors.name}>
                            <Input
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                placeholder="Adi Pratama"
                            />
                        </Field>
                        <Field label="Jenis Kelamin" error={form.errors.gender}>
                            <Select
                                value={form.data.gender}
                                onChange={(event) => form.setData('gender', event.target.value)}
                            >
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </Select>
                        </Field>
                        <Field label="Kelas" error={form.errors.classroom_id}>
                            <Select
                                value={form.data.classroom_id}
                                onChange={(event) => form.setData('classroom_id', event.target.value)}
                            >
                                <option value="">— Pilih kelas —</option>
                                {classrooms.map((classroom) => (
                                    <option key={classroom.id} value={classroom.id}>
                                        {classroom.name}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Status Keanggotaan" className="sm:col-span-2">
                            <Select
                                value={form.data.is_active ? '1' : '0'}
                                onChange={(event) => form.setData('is_active', event.target.value === '1')}
                            >
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif (mutasi / lulus)</option>
                            </Select>
                        </Field>
                    </div>

                    <div className="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
                        <Button variant="secondary" type="button" onClick={closeModal}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {editing ? 'Simpan Perubahan' : 'Simpan Siswa'}
                        </Button>
                    </div>
                </form>
            </Modal>
            <Modal show={showImport} onClose={() => setShowImport(false)} maxWidth="lg">
                <form onSubmit={submitImport}>
                    <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h3 className="text-sm font-extrabold text-slate-900">Impor Daftar Siswa</h3>
                        <button
                            type="button"
                            onClick={() => setShowImport(false)}
                            className="rounded-md p-1.5 text-slate-400 hover:bg-slate-100"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>

                    <div className="space-y-4 px-5 py-5">
                        <ol className="list-decimal space-y-1 rounded-xl border border-sky-200 bg-sky-50 py-3 pl-9 pr-5 text-[11px] text-sky-900">
                            <li>
                                Unduh template, lalu isi kolom <strong>NISN</strong>,{' '}
                                <strong>Nama Lengkap</strong>, <strong>L/P</strong>, dan <strong>Kelas</strong>{' '}
                                (contoh: 1A).
                            </li>
                            <li>Simpan berkas dalam format .xlsx atau .csv.</li>
                            <li>
                                Unggah berkas di bawah ini. Data dengan NISN yang sama akan diperbarui
                                otomatis.
                            </li>
                        </ol>

                        <a
                            href={route('students.template')}
                            className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                        >
                            <Download className="h-3.5 w-3.5" />
                            Unduh Template Excel
                        </a>

                        <Field label="Berkas Excel / CSV" error={importForm.errors.file}>
                            <input
                                ref={fileRef}
                                type="file"
                                accept=".xlsx,.xls,.csv,.ods,.txt"
                                onChange={(event) => importForm.setData('file', event.target.files[0] ?? null)}
                                className="block w-full rounded-lg border border-slate-300 text-xs file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-bold file:text-slate-700 hover:file:bg-slate-200"
                            />
                        </Field>

                        <Field
                            label="Paksa Semua ke Kelas (opsional)"
                            hint="Biarkan kosong agar kolom Kelas pada berkas yang dipakai"
                            error={importForm.errors.classroom_id}
                        >
                            <Select
                                value={importForm.data.classroom_id}
                                onChange={(event) => importForm.setData('classroom_id', event.target.value)}
                            >
                                <option value="">— Ikuti kolom Kelas pada berkas —</option>
                                {classrooms.map((classroom) => (
                                    <option key={classroom.id} value={classroom.id}>
                                        {classroom.name}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                    </div>

                    <div className="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
                        <Button variant="secondary" type="button" onClick={() => setShowImport(false)}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={importForm.processing}>
                            <Upload className="h-3.5 w-3.5" />
                            {importForm.processing ? 'Mengimpor...' : 'Mulai Impor'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
