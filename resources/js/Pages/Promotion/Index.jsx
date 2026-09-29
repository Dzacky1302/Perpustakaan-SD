import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import { Badge, Button, Card, EmptyState, Field, Input, PageHeader, StatCard } from '@/Components/ui';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowRight, GraduationCap, Info, Rocket, TriangleAlert, Users, X } from 'lucide-react';
import { CanManage } from '@/hooks/useCanManage';

export default function PromotionIndex({ plan, graduated = [] }) {
    const [showModal, setShowModal] = useState(false);

    const form = useForm({ to: plan.target_year, confirm: false });

    const closeModal = () => {
        setShowModal(false);
        form.reset();
        form.clearErrors();
    };

    const submit = (event) => {
        event.preventDefault();

        form.post(route('promotion.store'), {
            preserveScroll: true,
            onSuccess: closeModal,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Kenaikan Kelas"
                    subtitle={`Pindahkan seluruh siswa dari T.A. ${plan.source_year} ke ${plan.target_year}`}
                >
                    <CanManage>
                        <Button
                            type="button"
                            disabled={plan.already_done}
                            onClick={() => {
                                form.setData({ to: plan.target_year, confirm: false });
                                setShowModal(true);
                            }}
                        >
                            <Rocket className="h-3.5 w-3.5" />
                            Jalankan Kenaikan Kelas
                        </Button>
                    </CanManage>
                </PageHeader>
            }
        >
            <Head title="Kenaikan Kelas" />

            {plan.already_done && (
                <div className="mb-4 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                    <TriangleAlert className="mt-0.5 h-4 w-4 shrink-0" />
                    <span>
                        Kelas untuk T.A. {plan.target_year} sudah ada. Kenaikan kelas untuk tahun tersebut sudah
                        dilakukan — hapus dulu kelas tahun itu bila ingin mengulang.
                    </span>
                </div>
            )}

            <div className="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard
                    label="Total Siswa"
                    value={plan.total_students}
                    hint={`T.A. ${plan.source_year}`}
                    icon={Users}
                    tone="slate"
                />
                <StatCard
                    label="Siswa Naik Kelas"
                    value={plan.moving}
                    hint="Pindah ke kelas berikutnya"
                    icon={ArrowRight}
                    tone="emerald"
                />
                <StatCard
                    label="Siswa Lulus"
                    value={plan.graduating}
                    hint="Kelas 6 — selesai sekolah"
                    icon={GraduationCap}
                    tone="amber"
                />
                <StatCard
                    label="Kelas Dibuat"
                    value={plan.classrooms_to_create}
                    hint={`Untuk T.A. ${plan.target_year}`}
                    icon={Info}
                    tone="sky"
                />
            </div>


            <Card
                className="mb-4"
                bodyClass="p-0"
                title="Rencana Perpindahan Siswa"
                subtitle="Cek dulu sebelum dijalankan"
            >
                <div className="w-full overflow-x-auto">
                    <table className="w-full text-left text-xs">
                        <thead className="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="px-5 py-3 font-bold">Kelas</th>
                                <th className="px-3 py-3 font-bold">Wali Kelas</th>
                                <th className="px-3 py-3 font-bold">Jumlah Siswa</th>
                                <th className="px-3 py-3 font-bold">Aksi</th>
                                <th className="px-5 py-3 font-bold">Berpindah Ke</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {plan.rows.map((row) => (
                                <tr key={row.id} className="hover:bg-slate-50/70">
                                    <td className="px-5 py-3 font-extrabold text-slate-800">
                                        {row.name}
                                    </td>
                                    <td className="px-3 py-3 text-slate-600">
                                        {row.homeroom_teacher ?? '-'}
                                    </td>
                                    <td className="px-3 py-3 font-bold text-slate-700">
                                        {row.students} siswa
                                    </td>
                                    <td className="px-3 py-3">
                                        {row.action === 'lulus' ? (
                                            <Badge tone="amber">Lulus</Badge>
                                        ) : (
                                            <Badge tone="emerald">Naik kelas</Badge>
                                        )}
                                    </td>
                                    <td className="px-5 py-3 text-slate-600">
                                        {row.action === 'lulus' ? (
                                            <span className="text-slate-400">
                                                {row.target}
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center gap-1.5 font-semibold text-emerald-700">
                                                <ArrowRight className="h-3.5 w-3.5" />
                                                {row.target}
                                                <span className="text-[10px] font-normal text-slate-400">
                                                    (T.A. baru)
                                                </span>
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>

            <Card
                title="Siswa Lulus"
                subtitle="Kelas 6 yang sudah selesai sekolah"
                bodyClass="p-0"
                action={<Badge tone="slate">{graduated.length} siswa</Badge>}
            >
                {graduated.length === 0 ? (
                    <div className="p-5">
                        <EmptyState
                            icon={GraduationCap}
                            title="Belum ada siswa lulus"
                            description="Siswa akan muncul di sini setelah kenaikan kelas dijalankan."
                        />
                    </div>
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {graduated.map((student) => (
                            <li
                                key={student.id}
                                className="flex items-center justify-between gap-3 px-5 py-3"
                            >
                                <div className="min-w-0">
                                    <p className="truncate text-xs font-bold text-slate-800">
                                        {student.name}
                                    </p>
                                    <p className="truncate text-[11px] text-slate-500">
                                        NISN {student.nisn} • {student.classroom}
                                    </p>
                                </div>
                                <Badge tone="amber">Lulus {student.graduated_at}</Badge>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>

            <Modal show={showModal} onClose={closeModal} maxWidth="lg">
                <form onSubmit={submit}>
                    <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h3 className="text-sm font-extrabold text-slate-900">
                            Konfirmasi Kenaikan Kelas
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
                        <div className="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                            <span className="text-sm font-extrabold text-slate-700">
                                {plan.source_year}
                            </span>
                            <ArrowRight className="h-4 w-4 text-emerald-600" />
                            <span className="text-sm font-extrabold text-slate-700">
                                {form.data.to || plan.target_year}
                            </span>
                        </div>

                        <Field label="Tahun Ajaran Tujuan" error={form.errors.to}>
                            <Input
                                value={form.data.to}
                                onChange={(event) => form.setData('to', event.target.value)}
                                placeholder="2026/2027"
                            />
                        </Field>

                        <ul className="space-y-1.5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                            <li>• {plan.classrooms_to_create} kelas baru akan dibuat</li>
                            <li>• {plan.moving} siswa naik satu tingkat</li>
                            <li>• {plan.graduating} siswa kelas 6 ditandai lulus</li>
                            <li>• Riwayat buku tamu & peminjaman tetap aman</li>
                        </ul>

                        <label className="flex items-start gap-2 text-xs font-semibold text-slate-700">
                            <input
                                type="checkbox"
                                checked={form.data.confirm}
                                onChange={(event) => form.setData('confirm', event.target.checked)}
                                className="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                            />
                            Saya sudah cek daftar di atas dan yakin ingin menjalankan kenaikan kelas.
                        </label>
                    </div>

                    <div className="flex justify-end gap-2 border-t border-slate-100 px-5 py-4">
                        <Button type="button" variant="secondary" onClick={closeModal}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing || !form.data.confirm}>
                            <Rocket className="h-4 w-4" />
                            {form.processing ? 'Memproses...' : 'Ya, Naikkan Kelas'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

