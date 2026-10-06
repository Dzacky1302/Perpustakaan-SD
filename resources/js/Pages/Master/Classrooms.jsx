import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import { Button, Card, EmptyState, Field, Input, PageHeader, Select } from '@/Components/ui';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { CanManage } from '@/hooks/useCanManage';
import { GraduationCap, Layers, Pencil, Plus, Rocket, Trash2, UserRound, X } from 'lucide-react';

const EMPTY = { name: 'Kelas 1', grade_level: 1, academic_year: '', homeroom_teacher: '' };

export default function ClassroomsIndex({
    classrooms = [],
    academicYears = [],
    selectedYear = '',
    activeYear = '',
}) {
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);

    const form = useForm({ ...EMPTY });

    const openCreate = () => {
        setEditing(null);
        form.setData({
            ...EMPTY,
            academic_year: academicYears[0] ?? '2025/2026',
        });
        form.clearErrors();
        setShowModal(true);
    };

    const openEdit = (classroom) => {
        setEditing(classroom);
        form.setData({
            name: classroom.name,
            grade_level: classroom.grade_level,
            academic_year: classroom.academic_year,
            homeroom_teacher: classroom.homeroom_teacher ?? '',
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
            form.put(route('classrooms.update', editing.id), { onSuccess: closeModal, preserveScroll: true });
        } else {
            form.post(route('classrooms.store'), { onSuccess: closeModal, preserveScroll: true });
        }
    };

    const remove = (classroom) => {
        if (confirm(`Hapus kelas ${classroom.name} (${classroom.academic_year})?`)) {
            router.delete(route('classrooms.destroy', classroom.id), { preserveScroll: true });
        }
    };

    const totalStudents = classrooms.reduce((total, item) => total + item.students_count, 0);

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Data Kelas"
                    subtitle="Rombongan belajar, wali kelas, dan tahun ajaran aktif"
                >
                    <CanManage>
                        <Link
                            href={route('promotion.index')}
                            className="inline-flex items-center gap-1.5 rounded-lg border border-sky-300 bg-sky-50 px-3.5 py-2 text-xs font-bold text-sky-700 transition hover:bg-sky-100"
                        >
                            <Rocket className="h-3.5 w-3.5" />
                            Kenaikan Kelas
                        </Link>
                        <Button type="button" onClick={openCreate}>
                            <Plus className="h-3.5 w-3.5" />
                            Tambah Kelas
                        </Button>
                    </CanManage>
                </PageHeader>
            }
        >
            <Head title="Data Kelas" />

            {academicYears.length > 1 && (
                <div className="mb-4 flex flex-wrap items-center gap-2">
                    <span className="text-[11px] font-bold uppercase text-slate-500">
                        Tahun Ajaran
                    </span>
                    {academicYears.map((year) => (
                        <Link
                            key={year}
                            href={`?academic_year=${encodeURIComponent(year)}`}
                            preserveScroll
                            className={`rounded-lg px-3 py-1.5 text-xs font-bold transition ${
                                String(year) === String(selectedYear)
                                    ? 'bg-slate-900 text-white'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                            }`}
                        >
                            {year}
                            {String(year) === String(activeYear) && (
                                <span className="ml-1.5 text-[10px] font-normal opacity-80">
                                    (aktif)
                                </span>
                            )}
                        </Link>
                    ))}
                </div>
            )}

            {String(selectedYear) !== String(activeYear) && (
                <p className="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs font-semibold text-amber-800">
                    Kamu sedang melihat tahun ajaran {selectedYear}. Tahun ajaran aktif saat ini{' '}
                    {activeYear}. Data siswa dan buku tamu hanya memakai tahun ajaran aktif.
                </p>
            )}

            <div className="mb-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Jumlah Kelas</p>
                    <p className="mt-1 text-xl font-extrabold text-slate-900">{classrooms.length}</p>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Total Siswa</p>
                    <p className="mt-1 text-xl font-extrabold text-emerald-700">{totalStudents}</p>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Tahun Ajaran</p>
                    <p className="mt-1 text-xl font-extrabold text-sky-700">{academicYears.length} periode</p>
                </div>
            </div>
            <Card bodyClass="p-0">
                {classrooms.length === 0 ? (
                    <div className="p-5">
                        <EmptyState
                            icon={Layers}
                            title="Belum ada data kelas"
                            description="Tambahkan kelas sesuai rombongan belajar di sekolah."
                            action={
                                <CanManage>
                                    <Button type="button" onClick={openCreate}>
                                        <Plus className="h-3.5 w-3.5" />
                                        Tambah Kelas
                                    </Button>
                                </CanManage>
                            }
                        />
                    </div>
                ) : (
                    <div className="w-full overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th className="px-4 py-2.5 font-bold">Kelas</th>
                                    <th className="px-3 py-2.5 font-bold">Tahun Ajaran</th>
                                    <th className="px-3 py-2.5 font-bold">Wali Kelas</th>
                                    <th className="px-3 py-2.5 font-bold">Jumlah Siswa</th>
                                    <th className="px-4 py-2.5 text-right font-bold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {classrooms.map((classroom) => (
                                    <tr key={classroom.id} className="hover:bg-slate-50/70">
                                        <td className="px-4 py-2.5">
                                            <span className="flex items-center gap-2 font-extrabold text-slate-800">
                                                <GraduationCap className="h-4 w-4 shrink-0 text-emerald-600" />
                                                <span className="truncate">{classroom.name}</span>
                                            </span>
                                        </td>
                                        <td className="px-3 py-3 text-slate-600">{classroom.academic_year}</td>
                                        <td className="px-3 py-3">
                                            <span className="flex items-center gap-1.5 text-slate-600">
                                                <UserRound className="h-3.5 w-3.5 text-slate-400" />
                                                {classroom.homeroom_teacher ?? '-'}
                                            </span>
                                        </td>
                                        <td className="px-3 py-3 font-bold text-slate-700">
                                            {classroom.students_count} siswa
                                        </td>
                                        <td className="px-5 py-3">
                                            <div className="flex items-center justify-end gap-1.5">
                                                <CanManage>
                                                    <button
                                                        type="button"
                                                        onClick={() => openEdit(classroom)}
                                                        className="rounded-md p-1.5 text-slate-400 transition hover:bg-sky-50 hover:text-sky-600"
                                                        title="Ubah kelas"
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => remove(classroom)}
                                                        className="rounded-md p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                                        title="Hapus kelas"
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
                )}
            </Card>
            <Modal show={showModal} onClose={closeModal} maxWidth="lg">
                <form onSubmit={submit}>
                    <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h3 className="text-sm font-extrabold text-slate-900">
                            {editing ? `Ubah Kelas ${editing.name}` : 'Tambah Kelas Baru'}
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
                        <Field
                            label="Nama Kelas"
                            hint="Terisi otomatis dari tingkat kelas yang dipilih"
                            error={form.errors.name}
                        >
                            <Input
                                value={form.data.name}
                                readOnly
                                className="bg-slate-50 text-slate-600"
                                placeholder="Kelas 1"
                            />
                        </Field>
                        <Field label="Tingkat Kelas" error={form.errors.grade_level}>
                            <Select
                                value={form.data.grade_level}
                                onChange={(event) => {
                                    const grade = event.target.value;
                                    form.setData({
                                        grade_level: grade,
                                        name: `Kelas ${grade}`,
                                    });
                                }}
                            >
                                {[1, 2, 3, 4, 5, 6].map((grade) => (
                                    <option key={grade} value={grade}>
                                        Tingkat {grade}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <Field label="Tahun Ajaran" error={form.errors.academic_year}>
                            <Input
                                value={form.data.academic_year}
                                onChange={(event) => form.setData('academic_year', event.target.value)}
                                placeholder="2025/2026"
                            />
                        </Field>
                        <Field label="Wali Kelas" error={form.errors.homeroom_teacher}>
                            <Input
                                value={form.data.homeroom_teacher}
                                onChange={(event) => form.setData('homeroom_teacher', event.target.value)}
                                placeholder="Ibu Ratna Kusuma, S.Pd."
                            />
                        </Field>
                    </div>

                    <div className="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
                        <Button variant="secondary" type="button" onClick={closeModal}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {editing ? 'Simpan Perubahan' : 'Simpan Kelas'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
