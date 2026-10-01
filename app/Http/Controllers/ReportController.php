<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Classroom;
use App\Models\DailyLoan;
use App\Models\LibraryVisit;
use App\Models\PackageLoan;
use App\Models\Student;
use App\Services\FineService;
use App\Services\SpreadsheetService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly SpreadsheetService $spreadsheet,
        private readonly FineService $fines,
    ) {
    }

    /**
     * Halaman pusat laporan: pilih periode, kelas, dan unduh PDF/Excel.
     */
    public function index(Request $request): Response
    {
        $from = $request->query('from') ?: Carbon::today()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: Carbon::today()->toDateString();

        $classrooms = Classroom::forYear($request->query('academic_year'))
            ->withCount('students')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $classroomId = $request->query('classroom_id') ?: optional($classrooms->first())->id;
        $academicYear = $request->query('academic_year') ?: optional($classrooms->first())->academic_year;

        $visitsCount = LibraryVisit::query()
            ->when($classroomId, fn ($query, $id) => $query->where('classroom_id', $id))
            ->whereBetween('visit_date', [$from, $to])
            ->count();

        return Inertia::render('Reports/Index', [
            'filters' => [
                'from' => $from,
                'to' => $to,
                'classroom_id' => $classroomId,
                'academic_year' => $academicYear,
            ],
            'classrooms' => $classrooms->map(fn (Classroom $classroom) => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'grade_level' => $classroom->grade_level,
                'academic_year' => $classroom->academic_year,
                'students_count' => $classroom->students_count,
            ]),
            'academicYears' => Classroom::query()->distinct()->orderByDesc('academic_year')->pluck('academic_year'),
            'preview' => [
                'visits' => $visitsCount,
                'active_loans' => DailyLoan::active()->count(),
                'package_loans' => PackageLoan::active()->count(),
                'students' => Student::where('is_active', true)->count(),
            ],
            'fines' => $this->fineSummary(),
        ]);
    }

    /**
     * Rekap denda untuk pratinjau di halaman laporan.
     *
     * @return array<string, mixed>
     */
    private function fineSummary(): array
    {
        $unpaid = (int) DailyLoan::where('fine_amount', '>', 0)->whereNull('fine_paid_at')->sum('fine_amount');
        $collected = (int) DailyLoan::where('fine_amount', '>', 0)
            ->whereNotNull('fine_paid_at')->sum('fine_amount');

        $money = fn (int $value) => 'Rp'.number_format($value, 0, ',', '.');

        return [
            'unpaid' => $unpaid,
            'unpaid_label' => $money($unpaid),
            'collected' => $collected,
            'collected_label' => $money($collected),
            'count_unpaid' => DailyLoan::where('fine_amount', '>', 0)->whereNull('fine_paid_at')->count(),
            'rate_label' => $money($this->fines->dailyRate()),
            'max_label' => $money($this->fines->maxPerBook()),
        ];
    }

    /**
     * Rekap buku tamu (buku tamu digital) dalam format PDF.
     */
    public function visitsPdf(Request $request)
    {
        $filters = $this->visitFilters($request);

        $visits = $this->visitQuery($filters)->limit(500)->get();

        return Pdf::loadView('reports.visits', [
            'title' => 'Rekap Buku Tamu Perpustakaan',
            'periode' => 'Periode '.Carbon::parse($filters['from'])->translatedFormat('d F Y')
                .' s.d. '.Carbon::parse($filters['to'])->translatedFormat('d F Y')
                .($filters['classroom'] ? ' - Kelas '.$filters['classroom']->name : ''),
            'visits' => $visits,
            'summary' => $this->visitSummary($filters),
        ])->setPaper('a4', 'portrait')->download('rekap-buku-tamu-'.$filters['from'].'_'.$filters['to'].'.pdf');
    }

    /**
     * Rekap buku tamu dalam format Excel.
     */
    public function visitsExcel(Request $request)
    {
        $filters = $this->visitFilters($request);

        $rows = $this->visitQuery($filters)
            ->get()
            ->map(fn (LibraryVisit $visit, int $index) => [
                $index + 1,
                $visit->visit_date?->format('d/m/Y'),
                $visit->student?->nisn,
                $visit->student?->name,
                $visit->effectiveClassroom()?->name ?? 'Tidak tercatat',
                substr((string) $visit->arrival_time, 0, 5),
                ucfirst($visit->purpose),
                $visit->book?->title ?? '-',
                $visit->notes,
            ]);

        $filename = 'rekap-buku-tamu-'.$filters['from'].'_'.$filters['to'].'.xlsx';

        $path = $this->spreadsheet->build($filename, [
            'No', 'Tanggal', 'NISN', 'Nama Siswa', 'Kelas', 'Jam Datang', 'Keperluan', 'Buku', 'Catatan',
        ], $rows);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Rekap denda keterlambatan dalam format PDF.
     */
    public function finesPdf(Request $request)
    {
        $status = $request->query('status') ?: 'belum';

        $loans = $this->fineLoansQuery($status)->limit(500)->get();

        $from = $request->query('from') ?: Carbon::today()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: Carbon::today()->toDateString();

        $money = fn (int $value) => 'Rp'.number_format($value, 0, ',', '.');

        return Pdf::loadView('reports.fines', [
            'title' => 'Rekap Denda Keterlambatan Pengembalian',
            'periode' => 'Periode '.Carbon::parse($from)->translatedFormat('d F Y')
                .' s.d. '.Carbon::parse($to)->translatedFormat('d F Y')
                .' - '.($status === 'lunas' ? 'Denda yang sudah dibayar' : 'Denda yang belum dibayar'),
            'loans' => $loans,
            'summary' => [
                'count' => $loans->count(),
                'total' => (int) $loans->sum('fine_amount'),
                'unpaid' => (int) DailyLoan::where('fine_amount', '>', 0)->whereNull('fine_paid_at')->sum('fine_amount'),
                'collected' => (int) DailyLoan::where('fine_amount', '>', 0)->whereNotNull('fine_paid_at')->sum('fine_amount'),
                'rate' => $money($this->fines->dailyRate()),
                'max' => $money($this->fines->maxPerBook()),
            ],
        ])->setPaper('a4', 'portrait')->download('rekap-denda-'.Carbon::today()->format('Ymd').'.pdf');
    }

    /**
     * Rekap denda keterlambatan dalam format Excel.
     */
    public function finesExcel(Request $request)
    {
        $status = $request->query('status') ?: 'belum';

        $rows = $this->fineLoansQuery($status)
            ->get()
            ->map(fn (DailyLoan $loan, int $index) => [
                $index + 1,
                $loan->student?->nisn,
                $loan->student?->name,
                $loan->effectiveClassroom()?->name ?? 'Tidak tercatat',
                $loan->book?->title,
                $loan->due_at?->format('d/m/Y'),
                $loan->returned_at?->format('d/m/Y') ?? '-',
                (int) $loan->fine_days_late,
                (int) $loan->fine_amount,
                $loan->fine_paid_at?->format('d/m/Y') ?? 'Belum dibayar',
                $loan->fineReceiver?->name ?? '-',
            ]);

        $filename = 'rekap-denda-'.$status.'-'.Carbon::today()->format('Ymd').'.xlsx';

        $path = $this->spreadsheet->build($filename, [
            'No', 'NISN', 'Nama Siswa', 'Kelas', 'Judul Buku', 'Jatuh Tempo',
            'Tanggal Kembali', 'Hari Telat', 'Denda', 'Status Bayar', 'Diterima Oleh',
        ], $rows);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Query rekap denda.
     *
     * @return \Illuminate\Database\Eloquent\Builder<DailyLoan>
     */
    private function fineLoansQuery(string $status)
    {
        return DailyLoan::query()
            ->with(['student:id,name,nisn,classroom_id', 'classroom:id,name', 'book:id,code,title', 'fineReceiver:id,name'])
            ->where('fine_amount', '>', 0)
            ->when($status === 'belum', fn ($query) => $query->whereNull('fine_paid_at'))
            ->when($status === 'lunas', fn ($query) => $query->whereNotNull('fine_paid_at'))
            ->orderByRaw('case when fine_paid_at is null then 0 else 1 end')
            ->orderByDesc('fine_amount');
    }

    /**
     * Rekap penelusuran buku paket per kelas dalam format PDF.
     */
    public function packageLoansPdf(Request $request)
    {
        $data = $this->packageLoanData($request);

        return Pdf::loadView('reports.package-loans', [
            'title' => 'Rekap Penelusuran Buku Paket',
            'periode' => 'Kelas '.$data['classroom']->name.' - Tahun Ajaran '.$data['academicYear'],
            'classroom' => $data['classroom'],
            'academicYear' => $data['academicYear'],
            'students' => $data['students'],
            'books' => $data['books'],
            'summary' => $data['summary'],
        ])->setPaper('a4', 'portrait')->download('rekap-buku-paket-'.$data['classroom']->name.'.pdf');
    }

    /**
     * Rekap penelusuran buku paket per kelas dalam format Excel.
     */
    public function packageLoansExcel(Request $request)
    {
        $data = $this->packageLoanData($request);

        $rows = $data['students']->map(fn ($student, int $index) => [
            $index + 1,
            $student->nisn,
            $student->name,
            $student->total_diterima,
            $student->total_kembali,
            $student->total_hilang,
            $student->total_aktif,
            $student->total_aktif === 0 && $student->total_diterima > 0 ? 'Lengkap' : ($student->total_diterima === 0 ? 'Belum Terima' : 'Belum Lengkap'),
        ]);

        $filename = 'rekap-buku-paket-'.$data['classroom']->name.'-'.$data['academicYear'].'.xlsx';

        $path = $this->spreadsheet->build($filename, [
            'No', 'NISN', 'Nama Siswa', 'Buku Diterima', 'Sudah Kembali', 'Hilang', 'Masih Dipinjam', 'Keterangan',
        ], $rows);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Rekap peminjaman harian (bulanan) dalam format PDF.
     */
    public function loansPdf(Request $request)
    {
        $from = $request->query('from') ?: Carbon::today()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: Carbon::today()->toDateString();

        $loans = DailyLoan::with(['student.classroom:id,name', 'book:id,code,title'])
            ->whereBetween('borrowed_at', [$from, $to])
            ->orderBy('borrowed_at')
            ->limit(500)
            ->get();

        return Pdf::loadView('reports.daily-loans', [
            'title' => 'Rekap Peminjaman Buku Koleksi',
            'periode' => 'Periode '.Carbon::parse($from)->translatedFormat('d F Y')
                .' s.d. '.Carbon::parse($to)->translatedFormat('d F Y'),
            'loans' => $loans,
            'summary' => [
                'total' => $loans->count(),
                'active' => $loans->where('status', DailyLoan::STATUS_DIPINJAM)->count(),
                'returned' => $loans->where('status', DailyLoan::STATUS_KEMBALI)->count(),
                'lost' => $loans->where('status', DailyLoan::STATUS_HILANG)->count(),
                'overdue' => $loans->filter(fn (DailyLoan $loan) => $loan->isOverdue())->count(),
            ],
        ])->setPaper('a4', 'portrait')->download('rekap-peminjaman-'.$from.'_'.$to.'.pdf');
    }

    /**
     * Surat keterangan bebas pustaka / daftar tanggungan untuk satu siswa.
     */
    public function freeCertificate(Student $student)
    {
        $student->load('classroom');

        $activePackageLoans = PackageLoan::with('book:id,title')
            ->where('student_id', $student->id)
            ->where('status', PackageLoan::STATUS_DIPINJAM)
            ->orderBy('given_at')
            ->get();

        $activeDailyLoans = DailyLoan::with('book:id,title')
            ->where('student_id', $student->id)
            ->where('status', DailyLoan::STATUS_DIPINJAM)
            ->orderBy('borrowed_at')
            ->get();

        $history = collect()
            ->merge(PackageLoan::with('book:id,title')
                ->where('student_id', $student->id)
                ->whereIn('status', [PackageLoan::STATUS_KEMBALI, PackageLoan::STATUS_HILANG])
                ->get()
                ->map(fn (PackageLoan $loan) => (object) [
                    'book' => $loan->book,
                    'type' => 'Buku Paket',
                    'started_at' => $loan->given_at,
                    'finished_at' => $loan->returned_at ?? $loan->updated_at,
                    'condition' => $loan->status === PackageLoan::STATUS_HILANG ? 'Hilang' : ($loan->return_condition ?? 'Baik'),
                ]))
            ->merge(DailyLoan::with('book:id,title')
                ->where('student_id', $student->id)
                ->whereIn('status', [DailyLoan::STATUS_KEMBALI, DailyLoan::STATUS_HILANG])
                ->get()
                ->map(fn (DailyLoan $loan) => (object) [
                    'book' => $loan->book,
                    'type' => 'Koleksi',
                    'started_at' => $loan->borrowed_at,
                    'finished_at' => $loan->returned_at ?? $loan->updated_at,
                    'condition' => $loan->status === DailyLoan::STATUS_HILANG ? 'Hilang' : 'Baik',
                ]))
            ->sortByDesc('finished_at')
            ->values();

        return Pdf::loadView('reports.free-certificate', [
            'title' => 'Surat Keterangan Bebas Pustaka',
            'periode' => 'Tahun Ajaran '.$student->classroom?->academic_year,
            'student' => $student,
            'isClear' => $activePackageLoans->isEmpty() && $activeDailyLoans->isEmpty(),
            'activePackageLoans' => $activePackageLoans,
            'activeDailyLoans' => $activeDailyLoans,
            'history' => $history,
        ])->setPaper('a4', 'portrait')->download('bebas-pustaka-'.$student->nisn.'.pdf');
    }

    /**
     * @return array{from: string, to: string, classroom: Classroom|null}
     */
    private function visitFilters(Request $request): array
    {
        return [
            'from' => $request->query('from') ?: Carbon::today()->startOfMonth()->toDateString(),
            'to' => $request->query('to') ?: Carbon::today()->toDateString(),
            'classroom' => $request->query('classroom_id')
                ? Classroom::find($request->query('classroom_id'))
                : null,
        ];
    }

    /**
     * @param  array{from: string, to: string, classroom: Classroom|null}  $filters
     * @return \Illuminate\Database\Eloquent\Builder<LibraryVisit>
     */
    private function visitQuery(array $filters)
    {
        return LibraryVisit::query()
            ->with(['classroom:id,name,grade_level', 'student.classroom:id,name', 'book:id,title'])
            ->whereBetween('visit_date', [$filters['from'], $filters['to']])
            ->when($filters['classroom'], fn ($query, $classroom) => $query
                ->where('classroom_id', $classroom->id))
            ->orderBy('visit_date')
            ->orderBy('arrival_time');
    }

    /**
     * @param  array{from: string, to: string, classroom: Classroom|null}  $filters
     * @return array<string, int|float>
     */
    private function visitSummary(array $filters): array
    {
        $base = LibraryVisit::query()
            ->whereBetween('visit_date', [$filters['from'], $filters['to']])
            ->when($filters['classroom'], fn ($query, $classroom) => $query
                ->where('classroom_id', $classroom->id));

        $total = (clone $base)->count();
        $schoolDays = (clone $base)->distinct()->count('visit_date');
        $withBook = (clone $base)->whereNotNull('book_id')->count();
        $uniqueStudents = (clone $base)->distinct()->count('student_id');

        return [
            'total' => $total,
            'with_book' => $withBook,
            'unique_students' => $uniqueStudents,
            'daily_average' => $schoolDays > 0 ? round($total / $schoolDays, 1) : 0,
        ];
    }

    /**
     * Data rekap buku paket per kelas untuk laporan PDF/Excel.
     *
     * @return array<string, mixed>
     */
    private function packageLoanData(Request $request): array
    {
        $classroom = Classroom::findOrFail($request->query('classroom_id') ?? Classroom::orderBy('grade_level')->value('id'));
        $academicYear = $request->query('academic_year') ?: $classroom->academic_year;

        $loans = PackageLoan::with('book:id,title,code')
            ->where('classroom_id', $classroom->id)
            ->where('academic_year', $academicYear)
            ->get();

        $students = Student::where('classroom_id', $classroom->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Student $student) use ($loans) {
                $owned = $loans->where('student_id', $student->id);

                $student->total_diterima = $owned->count();
                $student->total_kembali = $owned->where('status', PackageLoan::STATUS_KEMBALI)->count();
                $student->total_hilang = $owned->where('status', PackageLoan::STATUS_HILANG)->count();
                $student->total_aktif = $owned->where('status', PackageLoan::STATUS_DIPINJAM)->count();

                return $student;
            });

        $books = Book::where('book_type', 'paket')
            ->where('grade_level', $classroom->grade_level)
            ->orderBy('title')
            ->get()
            ->map(function (Book $book) use ($loans) {
                $owned = $loans->where('book_id', $book->id);

                $book->penerima = $owned->count();
                $book->kembali = $owned->where('status', PackageLoan::STATUS_KEMBALI)->count();
                $book->hilang = $owned->where('status', PackageLoan::STATUS_HILANG)->count();
                $book->masih = $owned->where('status', PackageLoan::STATUS_DIPINJAM)->count();

                return $book;
            });

        return [
            'classroom' => $classroom,
            'academicYear' => $academicYear,
            'students' => $students,
            'books' => $books,
            'summary' => [
                'students' => $students->count(),
                'books' => $books->count(),
                'distributed' => $loans->count(),
                'returned' => $loans->where('status', PackageLoan::STATUS_KEMBALI)->count(),
                'lost' => $loans->where('status', PackageLoan::STATUS_HILANG)->count(),
                'active' => $loans->where('status', PackageLoan::STATUS_DIPINJAM)->count(),
            ],
        ];
    }
}
