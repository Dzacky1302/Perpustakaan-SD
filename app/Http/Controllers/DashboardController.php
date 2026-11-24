<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Classroom;
use App\Models\DailyLoan;
use App\Models\LibraryVisit;
use App\Models\PackageLoan;
use App\Models\Student;
use App\Services\BackupService;
use App\Services\FineService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly FineService $fine,
        private readonly BackupService $backups,
    ) {}

    /**
     * Ringkasan aktivitas perpustakaan untuk pustakawan dan kepala sekolah.
     *
     * Semua angka uang diambil dari FineService yang sama dengan halaman
     * Denda dan Laporan, supaya layar pertama hari kerja tidak pernah
     * menampilkan nominal yang berbeda dengan sumbernya.
     */
    public function __invoke(Request $request): Response
    {
        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth();
        $backups = $this->backups->all();
        $lastBackup = $backups[0] ?? null;

        return Inertia::render('Dashboard', [
            'stats' => [
                'students' => Student::where('is_active', true)->count(),
                'books' => Book::sum('total_copies'),
                'titles' => Book::count(),
                'visits_today' => LibraryVisit::whereDate('visit_date', $today)->count(),
                'visits_month' => LibraryVisit::whereBetween('visit_date', [$startOfMonth, $today])->count(),
                'loans_active' => DailyLoan::active()->count(),
                'loans_overdue' => DailyLoan::overdue()->count(),
                'package_loans_active' => PackageLoan::active()->count(),
                'fine_unpaid' => $this->fine->totalUnpaid(),
                'fine_unpaid_label' => $this->fine->format($this->fine->totalUnpaid()),
                'students_blocked' => Student::whereIn(
                    'id',
                    DailyLoan::where('fine_amount', '>', 0)->whereNull('fine_paid_at')->distinct('student_id')->pluck('student_id')
                )->count(),
            ],
            'lastBackup' => $lastBackup ? [
                'created_label' => $lastBackup['created_label'],
                'filename' => $lastBackup['filename'],
                'count' => count($backups),
            ] : null,
            'visitsTrend' => $this->visitsTrend(),
            'topBooks' => $this->topBooks(),
            'visitsByClassroom' => $this->visitsByClassroom(),
            'overdueLoans' => $this->overdueLoans(),
            'recentLoans' => $this->recentLoans(),
            'todayVisits' => $this->todayVisits(),
            'todayVisitPurposes' => $this->todayVisitPurposes(),
        ]);
    }

    /**
     * Daftar kunjungan hari ini (isi buku tamu digital).
     *
     * @return array<int, array<string, mixed>>
     */
    private function todayVisits(): array
    {
        return LibraryVisit::with(['student:id,name,classroom_id', 'book:id,title'])
            ->whereDate('visit_date', Carbon::today())
            ->orderByDesc('arrival_time')
            ->limit(12)
            ->get()
            ->map(fn (LibraryVisit $visit) => [
                'id' => $visit->id,
                'time' => substr((string) $visit->arrival_time, 0, 5),
                'student' => $visit->student?->name,
                'classroom' => $visit->effectiveClassroom()?->name,
                'book' => $visit->book?->title,
                'purpose' => $visit->purpose,
                'notes' => $visit->notes,
            ])
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function todayVisitPurposes(): array
    {
        return LibraryVisit::whereDate('visit_date', Carbon::today())
            ->selectRaw('purpose, count(*) as total')
            ->groupBy('purpose')
            ->pluck('total', 'purpose')
            ->all();
    }

    /**
     * Kunjungan 14 hari terakhir.
     *
     * Tiap titik diberi penanda hari sekolah supaya grafik bisa menjelaskan
     * bahwa Sabtu/Minggu dan libur tidak ikut menambah denda keterlambatan.
     */
    private function visitsTrend(): array
    {
        $start = Carbon::today()->subDays(13);

        $rows = LibraryVisit::selectRaw('visit_date as date, count(*) as total')
            ->whereBetween('visit_date', [$start->toDateString(), Carbon::today()->toDateString()])
            ->groupBy('visit_date')
            ->pluck('total', 'date');

        $trend = [];

        for ($date = $start->copy(); $date->lte(Carbon::today()); $date->addDay()) {
            $key = $date->toDateString();

            $trend[] = [
                'date' => $key,
                'label' => $date->translatedFormat('d M'),
                'total' => (int) ($rows[$key] ?? 0),
                'school_day' => $this->fine->isSchoolDay($date),
            ];
        }

        return $trend;
    }

    private function topBooks(): array
    {
        return DailyLoan::select('book_id', DB::raw('count(*) as total'))
            ->with('book:id,title,code')
            ->whereNotNull('book_id')
            ->groupBy('book_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn (DailyLoan $loan) => [
                'title' => $loan->book?->title,
                'code' => $loan->book?->code,
                'total' => (int) $loan->total,
            ])
            ->all();
    }

    private function visitsByClassroom(): array
    {
        $from = Carbon::today()->startOfMonth()->toDateString();
        $to = Carbon::today()->toDateString();

        return Classroom::forYear()
            ->withCount('students')
            ->withCount([
                'libraryVisits as visits_month_count' => fn ($query) => $query
                    ->whereBetween('visit_date', [$from, $to]),
            ])
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get()
            ->map(fn (Classroom $classroom) => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'students' => $classroom->students_count,
                'visits' => (int) $classroom->visits_month_count,
            ])
            ->all();
    }

    private function overdueLoans(): array
    {
        return DailyLoan::with(['student.classroom', 'book'])
            ->overdue()
            ->orderBy('due_at')
            ->limit(10)
            ->get()
            ->map(fn (DailyLoan $loan) => [
                'id' => $loan->id,
                'student' => $loan->student?->name,
                'classroom' => $loan->student?->classroom?->name,
                'book' => $loan->book?->title,
                'due_at' => $loan->due_at?->translatedFormat('d M Y'),
                'days_late' => $loan->daysLate(),
                'fine_label' => $this->fine->format($loan->liveFine()),
            ])
            ->all();
    }

    private function recentLoans(): array
    {
        return DailyLoan::with(['student.classroom', 'book'])
            ->orderByDesc('borrowed_at')
            ->limit(8)
            ->get()
            ->map(fn (DailyLoan $loan) => [
                'id' => $loan->id,
                'student' => $loan->student?->name,
                'classroom' => $loan->student?->classroom?->name,
                'book' => $loan->book?->title,
                'borrowed_at' => $loan->borrowed_at?->translatedFormat('d M Y'),
                'due_at' => $loan->due_at?->translatedFormat('d M Y'),
                'status' => $loan->status,
                'is_overdue' => $loan->isOverdue(),
            ])
            ->all();
    }
}
