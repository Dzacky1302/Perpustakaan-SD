import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import { Badge, Button, Card, EmptyState, Field, Input, PageHeader, cn } from '@/Components/ui';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Palette, Pencil, Plus, Tag, Trash2, X } from 'lucide-react';
import { CanManage } from '@/hooks/useCanManage';

const EMPTY = { code: '', name: '', color: 'sky' };

export default function CategoriesIndex({ categories = [], colors = [], totalBooks = 0 }) {
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);

    const form = useForm({ ...EMPTY });

    const openCreate = () => {
        setEditing(null);
        form.setData({ code: '', name: '', color: colors[0] ?? 'sky' });
        form.clearErrors();
        setShowModal(true);
    };

    const openEdit = (category) => {
        setEditing(category);
        form.setData({
            code: category.code,
            name: category.name,
            color: category.color ?? 'sky',
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
            form.put(route('categories.update', editing.id), { onSuccess: closeModal, preserveScroll: true });
        } else {
            form.post(route('categories.store'), { onSuccess: closeModal, preserveScroll: true });
        }
    };

    const remove = (category) => {
        if (confirm(`Hapus kategori ${category.name}?`)) {
            router.delete(route('categories.destroy', category.id), { preserveScroll: true });
        }
    };

    const colorClass = {
        sky: 'bg-sky-500',
        emerald: 'bg-emerald-500',
        amber: 'bg-amber-500',
        rose: 'bg-rose-500',
        violet: 'bg-violet-500',
        teal: 'bg-teal-500',
        slate: 'bg-slate-500',
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Kategori Koleksi"
                    subtitle="Klasifikasi buku untuk mempermudah penelusuran koleksi"
                >
                    <CanManage>
                        <Button type="button" onClick={openCreate}>
                            <Plus className="h-3.5 w-3.5" />
                            Tambah Kategori
                        </Button>
                    </CanManage>
                </PageHeader>
            }
        >
            <Head title="Kategori" />

            <div className="mb-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Jumlah Kategori</p>
                    <p className="mt-1 text-xl font-extrabold text-slate-900">{categories.length}</p>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Judul Terdaftar</p>
                    <p className="mt-1 text-xl font-extrabold text-emerald-700">{totalBooks}</p>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-[11px] font-bold uppercase text-slate-500">Total Eksemplar</p>
                    <p className="mt-1 text-xl font-extrabold text-amber-600">
                        {categories.reduce((total, item) => total + item.copies_sum, 0)}
                    </p>
                </div>
            </div>
            <Card bodyClass="p-0">
                {categories.length === 0 ? (
                    <div className="p-5">
                        <EmptyState
                            icon={Tag}
                            title="Belum ada kategori"
                            description="Buat kategori seperti Buku Paket, Fiksi, atau Referensi."
                            action={
                                <CanManage>
                                    <Button type="button" onClick={openCreate}>
                                        <Plus className="h-3.5 w-3.5" />
                                        Tambah Kategori
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
                                    <th className="px-4 py-2.5 font-bold">Kode</th>
                                    <th className="px-3 py-2.5 font-bold">Nama Kategori</th>
                                    <th className="hidden px-3 py-2.5 font-bold lg:table-cell">Warna Label</th>
                                    <th className="px-3 py-2.5 font-bold">Jumlah Judul</th>
                                    <th className="px-3 py-2.5 font-bold">Eksemplar</th>
                                    <th className="px-4 py-2.5 text-right font-bold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {categories.map((category) => (
                                    <tr key={category.id} className="hover:bg-slate-50/70">
                                        <td className="px-4 py-2.5 font-mono text-[11px] font-bold text-slate-500">
                                            {category.code}
                                        </td>
                                        <td className="px-3 py-2.5">
                                            <Badge tone={category.color ?? 'slate'}>{category.name}</Badge>
                                        </td>
                                        <td className="hidden px-3 py-2.5 lg:table-cell">
                                            <span className="flex items-center gap-1.5 text-slate-500">
                                                <span
                                                    className={cn(
                                                        'h-3.5 w-3.5 rounded-full',
                                                        colorClass[category.color] ?? 'bg-slate-400',
                                                    )}
                                                />
                                                {category.color}
                                            </span>
                                        </td>
                                        <td className="px-3 py-3 font-semibold text-slate-700">
                                            {category.books_count} judul
                                        </td>
                                        <td className="px-3 py-3 text-slate-600">{category.copies_sum} buku</td>
                                        <td className="px-5 py-3">
                                            <div className="flex items-center justify-end gap-1.5">
                                                <CanManage>
                                                    <button
                                                        type="button"
                                                        onClick={() => openEdit(category)}
                                                        className="rounded-md p-1.5 text-slate-400 transition hover:bg-sky-50 hover:text-sky-600"
                                                        title="Ubah kategori"
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => remove(category)}
                                                        className="rounded-md p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                                        title="Hapus kategori"
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
            <Modal show={showModal} onClose={closeModal} maxWidth="md">
                <form onSubmit={submit}>
                    <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h3 className="text-sm font-extrabold text-slate-900">
                            {editing ? `Ubah Kategori ${editing.name}` : 'Tambah Kategori Baru'}
                        </h3>
                        <button
                            type="button"
                            onClick={closeModal}
                            className="rounded-md p-1.5 text-slate-400 hover:bg-slate-100"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>

                    <div className="space-y-4 px-5 py-5">
                        <Field
                            label="Kode Kategori"
                            hint="Singkatan unik, misal PAKET, FIKSI"
                            error={form.errors.code}
                        >
                            <Input
                                value={form.data.code}
                                onChange={(event) => form.setData('code', event.target.value)}
                                placeholder="FIKSI"
                            />
                        </Field>
                        <Field label="Nama Kategori" error={form.errors.name}>
                            <Input
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                placeholder="Buku Cerita Fiksi"
                            />
                        </Field>
                        <Field label="Warna Label" error={form.errors.color}>
                            <div className="mt-1 flex flex-wrap items-center gap-2">
                                <Palette className="h-4 w-4 text-slate-400" />
                                {colors.map((color) => (
                                    <button
                                        key={color}
                                        type="button"
                                        onClick={() => form.setData('color', color)}
                                        className={cn(
                                            'h-7 w-7 rounded-full border-2 transition',
                                            colorClass[color] ?? 'bg-slate-400',
                                            form.data.color === color
                                                ? 'scale-110 border-slate-900'
                                                : 'border-white',
                                        )}
                                        title={color}
                                    />
                                ))}
                            </div>
                        </Field>
                        <div className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p className="text-[11px] font-bold uppercase text-slate-500">Pratinjau Label</p>
                            <Badge tone={form.data.color} className="mt-2">
                                {form.data.name || 'Nama kategori'}
                            </Badge>
                        </div>
                    </div>

                    <div className="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
                        <Button variant="secondary" type="button" onClick={closeModal}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {editing ? 'Simpan Perubahan' : 'Simpan Kategori'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
