import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import { Badge, Button, Card, EmptyState, PageHeader, StatCard } from '@/Components/ui';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { CanManage } from '@/hooks/useCanManage';
import {
    AlertTriangle,
    CalendarClock,
    DatabaseBackup,
    Download,
    HardDrive,
    RotateCcw,
    Save,
    Trash2,
    Upload,
    X,
} from 'lucide-react';

const SCHEDULE_LABEL = {
    daily: 'Setiap hari',
    hourly: 'Setiap jam',
    weekly: 'Setiap minggu',
    always: 'Setiap menit (khusus uji coba)',
};

export default function BackupsIndex({ backups = [], settings = {}, summary = {} }) {
    const [restoring, setRestoring] = useState(null);
    const [creating, setCreating] = useState(false);
    const [pruning, setPruning] = useState(false);

    const restoreForm = useForm({ confirm: false });

    const createBackup = () => {
        setCreating(true);
        router.post(route('backups.store'), {}, { preserveScroll: true, onFinish: () => setCreating(false) });
    };

    const runPrune = () => {
        setPruning(true);
        router.post(route('backups.prune'), {}, { preserveScroll: true, onFinish: () => setPruning(false) });
    };

    const confirmRestore = () => {
        router.post(
            route('backups.restore', restoring.filename),
            { confirm: true },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setRestoring(null);
                    restoreForm.reset();
                },
            },
        );
    };

    const removeBackup = (backup) => {
        if (confirm(`Hapus backup "${backup.filename}"?`)) {
            router.delete(route('backups.destroy', backup.filename), {}, { preserveScroll: true });
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader title="Cadangan Database" subtitle="Salinan otomatis harian memakai SQLite VACUUM INTO">
                    <CanManage>
                        <Button type="button" variant="secondary" onClick={runPrune} disabled={pruning}>
                            <RotateCcw className="h-3.5 w-3.5" />
                            Jalankan Rotasi
                        </Button>
                        <Button type="button" onClick={createBackup} disabled={creating}>
                            <Save className="h-3.5 w-3.5" />
                            {creating ? 'Mencadangkan...' : 'Cadangkan Sekarang'}
                        </Button>
                    </CanManage>
                </PageHeader>
            }
        >
            <Head title="Cadangan Database" />

            <div className="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard
                    label="Backup Tersimpan"
                    value={summary.count ?? 0}
                    hint={`Batas ${settings.keep ?? 14} berkas`}
                    icon={DatabaseBackup}
                    tone="emerald"
                />
                <StatCard
                    label="Total Ukuran"
                    value={`${summary.total_size ?? 0} MB`}
                    hint="Semua berkas cadangan"
                    icon={HardDrive}
                    tone="sky"
                />
                <StatCard
                    label="Jadwal Otomatis"
                    value={settings.time ?? '23:00'}
                    hint={SCHEDULE_LABEL[settings.schedule] ?? 'Setiap hari'}
                    icon={CalendarClock}
                    tone="violet"
                />
                <StatCard
                    label="Akan Dihapus"
                    value={(summary.will_prune ?? []).length}
                    hint="Backup melewati batas"
                    icon={AlertTriangle}
                    tone="amber"
                />
            </div>

            <Card
                title="Berkas Cadangan"
                subtitle="Unduh untuk disimpan di flashdisk, atau pulihkan bila data bermasalah."
                bodyClass="p-0"
                className="mb-4"
            >
                {backups.length > 0 ? (
                    <div className="w-full overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-slate-50 text-left text-[11px] uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th className="px-5 py-3">Berkas</th>
                                    <th className="px-5 py-3">Dibuat</th>
                                    <th className="px-5 py-3 text-right">Ukuran</th>
                                    <th className="px-5 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {backups.map((backup, index) => (
                                    <tr key={backup.filename} className="text-xs text-slate-700">
                                        <td className="px-5 py-3">
                                            <p className="font-semibold text-slate-800">{backup.filename}</p>
                                            {index === 0 && (
                                                <Badge tone="emerald" className="mt-1">
                                                    Terbaru
                                                </Badge>
                                            )}
                                        </td>
                                        <td className="px-5 py-3">
                                            <p>{backup.created_label}</p>
                                            <p className="text-[11px] text-slate-500">
                                                {backup.age_days === 0
                                                    ? 'Hari ini'
                                                    : `${backup.age_days} hari lalu`}
                                            </p>
                                        </td>
                                        <td className="px-5 py-3 text-right font-semibold">
                                            {backup.size_label}
                                        </td>
                                        <td className="px-5 py-3">
                                            <div className="flex items-center justify-end gap-1.5">
                                                <a
                                                    href={route('backups.download', backup.filename)}
                                                    className="rounded-md p-1.5 text-slate-400 transition hover:bg-sky-50 hover:text-sky-600"
                                                    title="Unduh backup"
                                                >
                                                    <Download className="h-4 w-4" />
                                                </a>
                                                <CanManage>
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            setRestoring(backup);
                                                            restoreForm.setData({ confirm: false });
                                                        }}
                                                        className="rounded-md p-1.5 text-slate-400 transition hover:bg-emerald-50 hover:text-emerald-600"
                                                        title="Pulihkan database"
                                                    >
                                                        <Upload className="h-4 w-4" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => removeBackup(backup)}
                                                        className="rounded-md p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                                        title="Hapus backup"
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
                ) : (
                    <div className="p-5">
                        <EmptyState
                            icon={DatabaseBackup}
                            title="Belum ada backup"
                            description="Klik 'Cadangkan Sekarang' untuk membuat cadangan pertama, atau tunggu jadwal otomatis."
                        />
                    </div>
                )}
            </Card>

            <Card title="Cara Kerja Cadangan" bodyClass="p-5">
                <ul className="space-y-2 text-xs leading-relaxed text-slate-600">
                    <li>
                        <strong className="text-slate-800">Otomatis:</strong> sistem menjalankan backup sendiri
                        setiap {SCHEDULE_LABEL[settings.schedule] ?? 'hari'} pukul {settings.time ?? '23:00'},
                        dijalankan oleh cron server (
                        <code className="rounded bg-slate-100 px-1">php artisan schedule:work</code>).
                    </li>
                    <li>
                        <strong className="text-slate-800">Akan dipakai online:</strong> backup memakai perintah
                        SQLite <code className="rounded bg-slate-100 px-1">VACUUM INTO</code>, jadi tidak mengunci
                        database walau sedang ada siswa meminjaman buku.
                    </li>
                    <li>
                        <strong className="text-slate-800">Rotasi:</strong> hanya {settings.keep ?? 14} berkas
                        terbaru yang disimpan. Sisanya dihapus otomatis supaya penyimpanan tidak penuh.
                    </li>
                    <li>
                        <strong className="text-slate-800">Lokasi berkas:</strong>{' '}
                        <code className="rounded bg-slate-100 px-1">{settings.path}</code>. Salin berkasnya
                        secara berkala ke flashdisk atau penyimpanan lain, karena file di komputer sekolah
                        bisa hilang bila perangkat rusak.
                    </li>
                    <li>
                        <strong className="text-slate-800">Pemulihan:</strong> sebelum memulihkan, sistem
                        membuat backup pengaman dulu, jadi data saat ini tetap bisa dikembalikan bila
                        ternyata backup yang dipilih kurang tepat.
                    </li>
                </ul>
            </Card>

            <Modal show={Boolean(restoring)} onClose={() => setRestoring(null)} maxWidth="lg">
                {restoring && (
                    <div>
                        <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                            <h3 className="text-sm font-extrabold text-slate-900">Pulihkan Database</h3>
                            <button
                                type="button"
                                onClick={() => setRestoring(null)}
                                className="rounded-md p-1.5 text-slate-400 hover:bg-slate-100"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        </div>

                        <div className="space-y-4 px-5 py-5">
                            <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                                <div className="flex gap-2.5">
                                    <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />
                                    <p className="text-xs leading-relaxed text-amber-900">
                                        Seluruh data yang ada SEKARANG akan diganti dengan isi backup
                                        <strong> {restoring.filename}</strong> (dibuat {restoring.created_label}).
                                        Data siswa, buku, dan riwayat peminjaman akan kembali ke kondisi saat
                                        backup itu dibuat.
                                    </p>
                                </div>
                            </div>

                            <label className="flex items-start gap-2.5 rounded-lg border border-slate-200 p-3">
                                <input
                                    type="checkbox"
                                    checked={restoreForm.data.confirm}
                                    onChange={(event) => restoreForm.setData('confirm', event.target.checked)}
                                    className="mt-0.5 rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                                />
                                <span className="text-xs text-slate-700">
                                    Saya mengerti dampaknya dan ingin melanjutkan pemulihan database.
                                    <span className="mt-0.5 block text-[11px] text-slate-500">
                                        Sistem akan membuat backup pengaman lebih dulu sebelum menimpa.
                                    </span>
                                </span>
                            </label>
                        </div>

                        <div className="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
                            <Button variant="secondary" type="button" onClick={() => setRestoring(null)}>
                                Batal
                            </Button>
                            <Button
                                type="button"
                                variant="danger"
                                disabled={!restoreForm.data.confirm || restoreForm.processing}
                                onClick={confirmRestore}
                            >
                                <Upload className="h-3.5 w-3.5" />
                                Pulihkan Sekarang
                            </Button>
                        </div>
                    </div>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}