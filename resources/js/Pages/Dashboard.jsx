import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    Badge,
    Button,
    Card,
    EmptyState,
    PageHeader,
    StatCard,
    StatSkeleton,
    TableWrapper,
} from '@/Components/ui';
import { PURPOSE_META } from '@/constants/visits';
import { CanManage } from '@/hooks/useCanManage';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
    Banknote,
    BookMarked,
    BookOpen,
    CalendarCheck,
    Clock,
    DatabaseBackup,
    Library,
    LibraryBig,
    Printer,
    RefreshCw,
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

function timeNow() {
    return new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

/**
 * Angka hari ini masih bertambah sampai pulang sekolah, jadi perbandingan
 * dengan kemarin diberi label "seharian" — bukan kesan bahwa kunjungan
 * sedang turun setiap pagi.
 */
function formatTrendDelta(value) {
    if (value === 0) return 'Sama kayak kemarin';
    return `${value > 0 ? '+' : ''}${value} vs kemarin (seharian)`;
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
            {day?.school_day === false && (
                <p className="mt-0.5 text-amber-700">Hari libur — tidak menambah denda</p>
            )}
        </div>
    );
}

/** Tick grafik yang menandai hari non-sekolah tanpa bergantung pada warna saja. */
function VisitsXAxisTick({ x, y, payload, schoolDays }) {
    const schoolDay = schoolDays[payload.value] ?? true;

    return (
        <g transform={`translate(${x},${y})`}>
            <text
                x={0}
                y={0}
                dy={14}
                textAnchor="middle"
                fontSize={11}
                fontWeight={schoolDay ? 400 : 700}
                fill={schoolDay ? '#475569' : '#b45309'}
            >
                {payload.value}
            </text>
        </g>
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
    lastBackup = null,
}) {
    const [loading, setLoading] = useState(false);
    const [loadedAt, setLoadedAt] = useState(timeNow);

    useEffect(() => {
        const offStart = router.on('start', () => setLoading(true));
        const offFinish = router.on('finish', () => {
            setLoading(false);
            setLoadedAt(timeNow());
        });

        return () => {
            offStart();
            offFinish();
        };
    }, []);

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
    const schoolDays = Object.fromEntries(chartData.map((day) => [day.label, day.school_day]));

    const avgVisits = Math.round(
        visitsTrend.reduce((total, day) => total + day.total, 0) / Math.max(1, visitsTrend.length),
    );

    // Dua titik terakhir tren = hari ini dan kemarin.
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
                    subtitle={`Ringkasan kerja hari ini — ${today}`}
                >
                    <CanManage>
                        <Button href={route('loans.index', { action: 'catat' })} size="sm">
                            <BookMarked className="h-3.5 w-3.5" />
                            Catat Peminjaman
                        </Button>
                        <Button
                            href={route('loans.index', { status: 'aktif' })}
                            variant="secondary"
                            size="sm"
                        >
                            <BookOpen className="h-3.5 w-3.5" />
                            Catat Pengembalian
                        </Button>
                    </CanManage>
                    <Button href={route('reports.index')} variant="secondary" size="sm">
                        <Printer className="h-3.5 w-3.5" />
                        Cetak Laporan
                    </Button>
                    <Button
                        href={route('peminjaman.index')}
                        variant="ghost"
                        size="sm"
                        target="_blank"
                        rel="noopener"
                    >
                        Formulir Siswa
                    </Button>
                </PageHeader>
            }
        >
            <Head title="Dashboard" />

            <div className="mb-4 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                <Link
                    href={route('backups.index')}
                    className="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 font-semibold text-slate-700 transition hover:border-emerald-300 hover:text-emerald-800"
                >
                    <DatabaseBackup className="h-3.5 w-3.5 text-emerald-700" aria-hidden="true" />
                    {lastBackup
                        ? `Cadangan terakhir: ${lastBackup.created_label} · ${lastBackup.count} berkas`
                        : 'Belum ada cadangan — buat sekarang'}
                </Link>
                <span>Denda dihitung per hari sekolah (Senin–Jumat).</span>
            </div>

            {loading ? (
                <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-5">
                    {Array.from({ length: 5 }).map((_, index) => (
                        <StatSkeleton key={index} />
                    ))}
                </div>
            ) : (
                <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-5">
                    <Link
                        href={route('reports.index')}
                        className="block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                    >
                        <StatCard
                            label="Kunjungan Hari Ini"
                            value={stats.visits_today}
                            hint={`${stats.visits_month} kunjungan bulan ini`}
                            trend={formatTrendDelta(visitsDelta)}
                            icon={CalendarCheck}
                            tone="emerald"
                        />
                    </Link>
                    <Link
                        href={route('loans.index', { status: 'aktif' })}
                        className="block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                    >
                        <StatCard
                            label="Peminjaman Aktif"
                            value={stats.loans_active}
                            hint={`${stats.loans_overdue} terlambat dikembalikan`}
                            trend={stats.loans_overdue > 0 ? `${stats.loans_overdue} telat` : 'Semua aman'}
                            icon={BookMarked}
                            tone={stats.loans_overdue > 0 ? 'rose' : 'sky'}
                        />
                    </Link>
                    <Link
                        href={route('package-loans.index')}
                        className="block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                    >
                        <StatCard
                            label="Buku Paket Beredar"
                            value={stats.package_loans_active}
                            hint="Eksemplar yang ada di tangan siswa"
                            icon={LibraryBig}
                            tone="amber"
                        />
                    </Link>
                    <Link
                        href={route('books.index')}
                        className="block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                    >
                        <StatCard
                            label="Koleksi Perpustakaan"
                            value={stats.books}
                            hint={`${stats.titles} judul buku terdaftar`}
                            icon={Library}
                            tone="violet"
                        />
                    </Link>
                    <Link
                        href={route('loans.fines', { status: 'belum' })}
                        className="block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                    >
                        <StatCard
                            label="Denda Belum Dibayar"
                            value={stats.fine_unpaid_label ?? 'Rp0'}
                            valueSize="text-lg sm:text-xl"
                            hint={`${stats.students_blocked ?? 0} siswa diblokir dari pinjam`}
                            icon={Banknote}
                            tone="rose"
                            valueTone="rose"
                        />
                    </Link>
                </div>
            )}

            <div className="mt-4 grid gap-4 lg:grid-cols-3">
                <Card
                    className="lg:col-span-2"
                    title="Tren Kunjungan 14 Hari Terakhir"
                    subtitle="Kunjungan tercatat per hari · hari libur tidak menambah denda"
                    action={
                        <Badge tone="emerald">
                            <TrendingUp className="h-3 w-3" />
                            Rata-rata {avgVisits} kunjungan/hari
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
                        <>
                            <div
                                className="h-48 w-full"
                                role="img"
                                aria-label="Grafik jumlah kunjungan perpustakaan 14 hari terakhir"
                            >
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
                                            tick={(props) => <VisitsXAxisTick {...props} schoolDays={schoolDays} />}
                                            tickLine={false}
                                            axisLine={{ stroke: '#e2e8f0' }}
                                            interval="preserveStartEnd"
                                            minTickGap={16}
                                        />
                                        <YAxis
                                            allowDecimals={false}
                                            tick={{ fontSize: 11, fill: '#475569' }}
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
                            <p className="mt-2 text-xs text-slate-600">
                                Tanggal berwarna coklat adalah hari libur (tidak menambah denda).
                            </p>
                            <table className="sr-only">
                                <caption>Kunjungan perpustakaan per hari, 14 hari terakhir</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Tanggal</th>
                                        <th scope="col">Jenis hari</th>
                                        <th scope="col">Jumlah kunjungan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {chartData.map((day) => (
                                        <tr key={day.date}>
                                            <th scope="row">{day.fullDate}</th>
                                            <td>{day.school_day ? 'Hari sekolah' : 'Hari libur'}</td>
                                            <td>{day.total} kunjungan</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </>
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
                                            <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-slate-100 text-xs font-bold text-slate-700">
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
                    title="Kunjungan Hari Ini — Daftar"
                    subtitle="Buku tamu digital — diisi siswa sendiri lewat formulir"
                    bodyClass="p-0"
                    action={
                        <Badge tone="emerald">
                            <Clock className="h-3 w-3" />
                            {stats.visits_today} kunjungan
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
                                    <span className="mt-0.5 flex h-10 w-11 shrink-0 flex-col items-center justify-center rounded-lg bg-emerald-50 text-xs font-extrabold tabular-nums text-emerald-700">
                                        {visit.time}
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-xs font-bold text-slate-800">
                                            {visit.student ?? '-'}
                                        </p>
                                        <p className="truncate text-xs text-slate-600">
                                            {visit.classroom ?? '-'}
                                            {visit.book ? ` • ${visit.book}` : ''}
                                        </p>
                                        {visit.notes && (
                                            <p className="truncate text-xs italic text-slate-600">
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
                                        <div className="flex items-center justify-between text-xs font-semibold text-slate-600">
                                            <span>{meta.label}</span>
                                            <span>{total} kunjungan</span>
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
                    subtitle="Denda dihitung per hari sekolah (Senin–Jumat)"
                    bodyClass="p-0"
                    action={
                        <>
                            <Badge tone={overdueLoans.length > 0 ? 'rose' : 'emerald'} dot>
                                {overdueLoans.length} catatan
                            </Badge>
                            {overdueLoans.length > 0 && (
                                <Button
                                    href={route('loans.fines', { status: 'belum' })}
                                    variant="secondary"
                                    size="sm"
                                >
                                    <Banknote className="h-3.5 w-3.5" />
                                    Buka Denda
                                </Button>
                            )}
                        </>
                    }
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
                                <li key={loan.id}>
                                    <Link
                                        href={route('loans.fines', { status: 'belum', search: loan.student })}
                                        className="flex items-start justify-between gap-3 px-5 py-3 transition hover:bg-rose-50/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-rose-500"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-xs font-bold text-slate-800">{loan.student}</p>
                                            <p className="truncate text-xs text-slate-600">
                                                {loan.classroom} • {loan.book}
                                            </p>
                                            <p className="mt-0.5 text-xs text-slate-600">
                                                Jatuh tempo {loan.due_at}
                                            </p>
                                            <p className="mt-1 text-xs font-extrabold tabular-nums text-rose-700">
                                                {loan.fine_label}
                                            </p>
                                        </div>
                                        <Badge tone="rose" dot>
                                            Terlambat {loan.days_late} hari
                                        </Badge>
                                    </Link>
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
                        <TableWrapper label="Peminjaman terbaru">
                            <table className="w-full min-w-[620px] text-left text-xs">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
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
                                                <Link
                                                    href={route('loans.index', { search: loan.student })}
                                                    className="font-semibold text-slate-800 underline-offset-2 hover:text-emerald-700 hover:underline"
                                                >
                                                    {loan.student}
                                                </Link>
                                                <p className="text-xs text-slate-600">{loan.classroom}</p>
                                            </td>
                                            <td className="max-w-[220px] truncate px-3 py-2.5 text-slate-600">
                                                {loan.book}
                                            </td>
                                            <td className="whitespace-nowrap px-3 py-2.5 text-slate-600">
                                                {loan.borrowed_at}
                                            </td>
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
                subtitle="Perbandingan jumlah kunjungan tiap kelas"
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
                    <TableWrapper label="Kunjungan per kelas">
                        <table className="w-full min-w-[560px] text-left text-xs">
                            <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                                <tr>
                                    <th className="px-5 py-2.5 font-bold">Kelas</th>
                                    <th className="px-3 py-2.5 font-bold">Jumlah Siswa</th>
                                    <th className="px-3 py-2.5 font-bold">Kunjungan</th>
                                    <th className="px-5 py-2.5 font-bold">Proporsi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {visitsByClassroom.map((row) => {
                                    const percent = Math.round((row.visits / maxClassroomVisit) * 100);

                                    return (
                                        <tr key={row.id} className="hover:bg-slate-50/70">
                                            <td className="px-5 py-2.5 font-bold text-slate-800">{row.name}</td>
                                            <td className="whitespace-nowrap px-3 py-2.5 text-slate-600">{row.students} siswa</td>
                                            <td className="whitespace-nowrap px-3 py-2.5 font-semibold text-emerald-700">{row.visits}x</td>
                                            <td className="px-5 py-2.5">
                                                <div className="flex items-center gap-2">
                                                    <div className="h-1.5 w-full min-w-[120px] overflow-hidden rounded-full bg-slate-100">
                                                        <div
                                                            className="h-full rounded-full bg-emerald-500"
                                                            style={{ width: `${percent}%` }}
                                                        />
                                                    </div>
                                                    <span className="w-9 shrink-0 text-right font-semibold tabular-nums text-slate-600">
                                                        {percent}%
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </TableWrapper>
                )}
            </Card>

            <div className="mt-4 flex flex-wrap items-center justify-center gap-3 text-center text-xs text-slate-600">
                <span>Terakhir dimuat pukul {loadedAt} · segarkan untuk angka terbaru.</span>
                <Button
                    variant="ghost"
                    size="xs"
                    onClick={() => router.reload({ preserveScroll: true })}
                >
                    <RefreshCw className="h-3.5 w-3.5" />
                    Segarkan
                </Button>
            </div>
        </AuthenticatedLayout>
    );
}
