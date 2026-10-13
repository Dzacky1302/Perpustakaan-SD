import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge, Card, EmptyState, PageHeader, StatCard, TableWrapper } from '@/Components/ui';
import { CanManage } from '@/hooks/useCanManage';
import { Head, Link } from '@inertiajs/react';
import {
    BookMarked,
    BookOpen,
    CalendarCheck,
    Clock,
    Library,
    LibraryBig,
    TrendingUp,
    UserRound,
} from 'lucide-react';
import {
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

const PURPOSE_META = {
    membaca: { label: 'Membaca', tone: 'emerald' },
    pinjam: { label: 'Meminjam', tone: 'sky' },
    tugas: { label: 'Tugas', tone: 'violet' },
    lainnya: { label: 'Lainnya', tone: 'slate' },
};

function formatTrendDelta(value) {
    if (value > 0) return `+${value} vs kemarin`;
    if (value < 0) return `${value} vs kemarin`;
    return 'Sama kayak kemarin';
}

function VisitsTooltip({ active, payload, label }) {
    if (!active || !payload?.length) return null;
    const day = payload[0]?.payload;

    return (
        <div className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs shadow-pop">
            <p className="font-bold text-slate-800">{day?.fullDate ?? label}</p>
            <p className="mt-0.5 font-semibold tabular-nums text-emerald-700">
                {payload[0].value} kunjungan
            </p>
        </div>
    );
}

export default function Dashboard({
    stats,
    visitsTrend = [],
    topBooks = [],
    visitsByClassroom = [],
    overdueLoans = [],
    recentLoans = [],
    todayVisits = [],
    todayVisitPurposes = {},
}) {
    const today = new Date().toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    const maxTopBook = Math.max(1, ...topBooks.map((book) => book.total));
    const maxClassroomVisit = Math.max(1, ...visitsByClassroom.map((row) => row.visits));

    const chartData = visitsTrend.map((day) => ({
        ...day,
        fullDate: new Date(`${day.date}T00:00:00`).toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
        }),
    }));

    const avgVisits = Math.round(
        visitsTrend.reduce((total, day) => total + day.total, 0) / Math.max(1, visitsTrend.length),
    );

    // Delta kunjungan hari ini vs kemarin (2 titik terakhir tren).
    const lastDay = visitsTrend[visitsTrend.length - 1]?.total ?? 0;
    const prevDay = visitsTrend[visitsTrend.length - 2]?.total ?? 0;
    const visitsDelta = lastDay - prevDay;

    const statusTone = (status) =>
        status === 'dipinjam' ? 'amber' : status === 'kembali' ? 'emerald' : 'rose';

    return (
        <AuthenticatedLayout
            header={
                <PageHeader
                    title="Dashboard Perpustakaan"
                    subtitle={`Rekapitulasi aktivitas perpustakaan — ${today}`}
                >
                    {/* Tombol "Buka Buku Tamu" sengaja tidak ditampilkan karena
                        menu Buku Tamu sudah dihapus dari navigasi. */}
                    <CanManage>
                        <Link
                            href={route('peminjaman.index')}
                            target="_blank"
                            rel="noopener"
                            className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                        >
                            <BookMarked className="h-4 w-4" />
                            Lihat Formulir Siswa
                        </Link>
                    </CanManage>
                </PageHeader>
            }
        >
            <Head title="Dashboard" />

            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard
                    label="Kunjungan Hari Ini"
                    value={stats.visits_today}
                    hint={`${stats.visits_month} kunjungan bulan ini`}
                    trend={formatTrendDelta(visitsDelta)}
                    icon={CalendarCheck}
                    tone="emerald"
                />
                <StatCard
                    label="Peminjaman Aktif"
                    value={stats.loans_active}
                    hint={`${stats.loans_overdue} terlambat dikembalikan`}
                    trend={stats.loans_overdue > 0 ? `${stats.loans_overdue} telat` : 'Aman'}
                    icon={BookMarked}
                    tone={stats.loans_overdue > 0 ? 'rose' : 'sky'}
                />
                <StatCard
                    label="Buku Paket Beredar"
                    value={stats.package_loans_active}
                    hint="Eksemplar yang ada di tangan siswa"
                    icon={LibraryBig}
                    tone="amber"
                />
                <StatCard
                    label="Koleksi Perpustakaan"
                    value={stats.books}
                    hint={`${stats.titles} judul buku terdaftar`}
                    icon={Library}
                    tone="violet"
                />
            </div>


            <div className="mt-4 grid gap-4 lg:grid-cols-3">
                <Card
                    className="lg:col-span-2"
                    title="Tren Kunjungan 14 Hari Terakhir"
                    subtitle="Jumlah siswa yang tercatat hadir di perpustakaan"
                    action={
                        <Badge tone="emerald">
                            <TrendingUp className="h-3 w-3" />
                            Rata-rata {avgVisits} siswa/hari
                        </Badge>
                    }
                >
                    {chartData.length === 0 ? (
                        <EmptyState
                            icon={TrendingUp}
                            title="Belum ada data kunjungan"
                            description="Grafik muncul otomatis setelah ada kunjungan tercatat."
                            compact
                        />
                    ) : (
                        <div className="h-48 w-full">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={chartData} margin={{ top: 4, right: 4, bottom: 0, left: -18 }}>
                                    <defs>
                                        <linearGradient id="visitsFill" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stopColor="#059669" stopOpacity={0.35} />
                                            <stop offset="100%" stopColor="#059669" stopOpacity={0.02} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" vertical={false} />
                                    <XAxis
                                        dataKey="label"
                                        tick={{ fontSize: 9, fill: '#64748b' }}
                                        tickLine={false}
                                        axisLine={{ stroke: '#e2e8f0' }}
                                        interval="preserveStartEnd"
                                        minTickGap={16}
                                    />
                                    <YAxis
                                        allowDecimals={false}
                                        tick={{ fontSize: 9, fill: '#64748b' }}
                                        tickLine={false}
                                        axisLine={false}
                                        width={36}
                                    />
                                    <Tooltip content={<VisitsTooltip />} cursor={{ stroke: '#059669', strokeDasharray: '4 4' }} />
                                    <Area
                                        type="monotone"
                                        dataKey="total"
                                        name="Kunjungan"
                                        stroke="#059669"
                                        strokeWidth={2.5}
                                        fill="url(#visitsFill)"
                                        dot={false}
                                        activeDot={{ r: 4, fill: '#059669', stroke: '#fff', strokeWidth: 2 }}
                                    />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    )}
                </Card>

                <Card title="Buku Paling Sering Dipinjam" subtitle="5 judul teratas">
                    {topBooks.length === 0 ? (
                        <EmptyState icon={BookOpen} title="Belum ada data peminjaman" />
                    ) : (
                        <ul className="space-y-3.5">
                            {topBooks.map((book, index) => (
                                <li key={book.code ?? index}>
                                    <div className="flex items-center justify-between gap-2 text-xs">
                                        <span className="flex min-w-0 items-center gap-2">
                                            <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-slate-100 text-[10px] font-bold text-slate-600">
                                                {index + 1}
                                            </span>
                                            <span className="truncate font-semibold text-slate-700">
                                                {book.title}
                                            </span>
                                        </span>
                                        <span className="shrink-0 font-bold text-emerald-700">{book.total}x</span>
                                    </div>
                                    <div className="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                        <div
                                            className="h-full rounded-full bg-emerald-500"
                                            style={{ width: `${(book.total / maxTopBook) * 100}%` }}
                                        />
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-5">
                <Card
                    className="lg:col-span-3"
                    title="Kunjungan Hari Ini"
                    subtitle="Buku tamu digital — diisi siswa sendiri lewat formulir"
                    bodyClass="p-0"
                    action={
                        <Badge tone="emerald">
                            <Clock className="h-3 w-3" />
                            {stats.visits_today} siswa
                        </Badge>
                    }
                >
                    {todayVisits.length === 0 ? (
                        <div className="p-5">
                            <EmptyState
                                icon={UserRound}
                                title="Belum ada kunjungan hari ini"
                                description="Data muncul begitu siswa mengisi formulir di halaman Buku Tamu Digital."
                            />
                        </div>
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {todayVisits.map((visit) => (
                                <li key={visit.id} className="flex items-start gap-3 px-5 py-3">
                                    <span className="mt-0.5 flex h-10 w-11 shrink-0 flex-col items-center justify-center rounded-lg bg-emerald-50 text-[11px] font-extrabold tabular-nums text-emerald-700">
                                        {visit.time}
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-xs font-bold text-slate-800">
                                            {visit.student ?? '-'}
                                        </p>
                                        <p className="truncate text-[11px] text-slate-500">
                                            {visit.classroom ?? '-'}
                                            {visit.book ? ` • ${visit.book}` : ''}
                                        </p>
                                        {visit.notes && (
                                            <p className="truncate text-[11px] italic text-slate-400">
                                                “{visit.notes}”
                                            </p>
                                        )}
                                    </div>
                                    <Badge tone={PURPOSE_META[visit.purpose]?.tone ?? 'slate'}>
                                        {PURPOSE_META[visit.purpose]?.label ?? visit.purpose}
                                    </Badge>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>

                <Card title="Rekap Keperluan Hari Ini" subtitle="Berdasarkan pilihan siswa">
                    {Object.keys(todayVisitPurposes).length === 0 ? (
                        <EmptyState icon={UserRound} title="Belum ada data" />
                    ) : (
                        <ul className="space-y-3">
                            {Object.entries(todayVisitPurposes).map(([purpose, total]) => {
                                const share = (Number(total) / Math.max(1, stats.visits_today)) * 100;
                                const meta = PURPOSE_META[purpose] ?? {
                                    label: purpose,
                                    tone: 'slate',
                                };

                                return (
                                    <li key={purpose}>
                                        <div className="flex items-center justify-between text-[11px] font-semibold text-slate-600">
                                            <span>{meta.label}</span>
                                            <span>{total} siswa</span>
                                        </div>
                                        <div className="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                            <div
                                                className="h-full rounded-full bg-emerald-500"
                                                style={{ width: `${share}%` }}
                                            />
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </Card>
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-5">
                <Card
                    className="lg:col-span-2"
                    title="Perlu Ditagih Hari Ini"
                    subtitle="Peminjaman yang melewati jatuh tempo"
                    bodyClass="p-0"
                    action={<Badge tone={overdueLoans.length > 0 ? 'rose' : 'emerald'}>{overdueLoans.length} catatan</Badge>}
                >
                    {overdueLoans.length === 0 ? (
                        <div className="p-5">
                            <EmptyState
                                icon={CalendarCheck}
                                title="Tidak ada keterlambatan"
                                description="Semua buku koleksi dikembalikan sesuai jadwal."
                            />
                        </div>
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {overdueLoans.map((loan) => (
                                <li key={loan.id} className="flex items-start justify-between gap-3 px-5 py-3">
                                    <div className="min-w-0">
                                        <p className="truncate text-xs font-bold text-slate-800">{loan.student}</p>
                                        <p className="truncate text-[11px] text-slate-500">
                                            {loan.classroom} • {loan.book}
                                        </p>
                                        <p className="mt-0.5 text-[11px] text-slate-400">
                                            Jatuh tempo {loan.due_at}
                                        </p>
                                    </div>
                                    <Badge tone="rose">Terlambat {loan.days_late} hari</Badge>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>

                <Card
                    className="lg:col-span-3"
                    title="Peminjaman Terbaru"
                    subtitle="8 transaksi terakhir dicatat"
                    bodyClass="p-0"
                >
                    {recentLoans.length === 0 ? (
                        <div className="p-5">
                            <EmptyState
                                icon={BookMarked}
                                title="Belum ada peminjaman"
                                description="Transaksi peminjaman terbaru akan muncul di sini."
                            />
                        </div>
                    ) : (
                        <TableWrapper>
                            <table className="w-full min-w-[620px] text-left text-xs">
                                <thead className="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-2.5 font-bold">Siswa</th>
                                        <th className="px-3 py-2.5 font-bold">Buku</th>
                                        <th className="px-3 py-2.5 font-bold">Pinjam</th>
                                        <th className="px-5 py-2.5 font-bold">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {recentLoans.map((loan) => (
                                        <tr key={loan.id} className="hover:bg-slate-50/70">
                                            <td className="px-5 py-2.5">
                                                <p className="font-semibold text-slate-800">{loan.student}</p>
                                                <p className="text-[10px] text-slate-500">{loan.classroom}</p>
                                            </td>
                                            <td className="max-w-[220px] truncate px-3 py-2.5 text-slate-600">
                                                {loan.book}
                                            </td>
                                            <td className="whitespace-nowrap px-3 py-2.5 text-slate-500">{loan.borrowed_at}</td>
                                            <td className="whitespace-nowrap px-5 py-2.5">
                                                {loan.is_overdue ? (
                                                    <Badge tone="rose" dot>
                                                        Terlambat
                                                    </Badge>
                                                ) : (
                                                    <Badge tone={statusTone(loan.status)} dot>
                                                        {loan.status === 'dipinjam'
                                                            ? 'Dipinjam'
                                                            : loan.status === 'kembali'
                                                              ? 'Kembali'
                                                              : 'Hilang'}
                                                    </Badge>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </TableWrapper>
                    )}
                </Card>
            </div>


            <Card
                    className="mt-4"
                    title="Kunjungan Bulan Ini per Kelas"
                    subtitle="Perbandingan aktivitas kunjungan tiap rombongan belajar"
                    bodyClass="p-0"
                >
                    {visitsByClassroom.length === 0 ? (
                        <div className="p-5">
                            <EmptyState
                                icon={UserRound}
                                title="Belum ada kunjungan bulan ini"
                                description="Data muncul otomatis setelah siswa mengisi formulir kunjungan."
                            />
                        </div>
                    ) : (
                        <TableWrapper>
                            <table className="w-full min-w-[560px] text-left text-xs">
                                <thead className="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-2.5 font-bold">Kelas</th>
                                        <th className="px-3 py-2.5 font-bold">Jumlah Siswa</th>
                                        <th className="px-3 py-2.5 font-bold">Kunjungan</th>
                                        <th className="px-5 py-2.5 font-bold">Proporsi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {visitsByClassroom.map((row) => (
                                        <tr key={row.id} className="hover:bg-slate-50/70">
                                            <td className="px-5 py-2.5 font-bold text-slate-800">{row.name}</td>
                                            <td className="whitespace-nowrap px-3 py-2.5 text-slate-600">{row.students} siswa</td>
                                            <td className="whitespace-nowrap px-3 py-2.5 font-semibold text-emerald-700">{row.visits}x</td>
                                            <td className="px-5 py-2.5">
                                                <div className="h-1.5 w-full min-w-[120px] overflow-hidden rounded-full bg-slate-100">
                                                    <div
                                                        className="h-full rounded-full bg-teal-500"
                                                        style={{ width: `${(row.visits / maxClassroomVisit) * 100}%` }}
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </TableWrapper>
                    )}
                </Card>

            <p className="mt-4 text-center text-[11px] text-slate-400">
                Data diperbarui otomatis setiap kali halaman ini dimuat.
            </p>
        </AuthenticatedLayout>
    );
}

