<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Classroom;
use App\Models\DailyLoan;
use App\Models\Student;
use App\Models\User;
use App\Services\FineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Konsistensi hitungan keterlambatan antar layar.
 *
 * Sumber kebenaran tetap FineService: buku baru disebut terlambat setelah
 * ada HARI SEKOLAH yang terlewat, bukan sekadar tanggal kalender lewat.
 * Dashboard, ringkasan, dan filter "terlambat" harus memakai angka yang sama.
 */
class OverdueConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    private Book $book;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $classroom = Classroom::create([
            'name' => 'Kelas 3',
            'grade_level' => 3,
            'academic_year' => '2025/2026',
        ]);

        $this->student = Student::create([
            'classroom_id' => $classroom->id,
            'nisn' => '5555555555',
            'name' => 'Sari',
            'gender' => 'P',
            'is_active' => true,
        ]);

        $category = Category::create(['code' => 'FIK', 'name' => 'Fiksi', 'color' => 'amber']);

        $this->book = Book::create([
            'code' => 'BK-9100',
            'title' => 'Dongeng',
            'category_id' => $category->id,
            'book_type' => 'koleksi',
            'total_copies' => 3,
            'available_copies' => 3,
        ]);

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function freeze(string $date): void
    {
        Carbon::setTestNow(Carbon::parse($date.' 10:00:00'));
    }

    private function loan(string $dueDate): DailyLoan
    {
        $due = Carbon::parse($dueDate);

        return DailyLoan::create([
            'student_id' => $this->student->id,
            'classroom_id' => $this->student->classroom_id,
            'book_id' => $this->book->id,
            'borrowed_at' => $due->copy()->subDays(7),
            'due_at' => $due,
            'status' => DailyLoan::STATUS_DIPINJAM,
        ]);
    }

    public function test_sabtu_setelah_jatuh_tempo_jumat_belum_terlambat(): void
    {
        $loan = $this->loan('2026-09-11');

        $this->freeze('2026-09-12'); // Sabtu

        $this->assertFalse($loan->fresh()->isOverdue());
        $this->assertSame(0, $loan->fresh()->daysLate());
        $this->assertSame(0, DailyLoan::overdue()->count());
    }

    public function test_senin_setelah_jatuh_tempo_jumat_masih_nol_hari(): void
    {
        $loan = $this->loan('2026-09-11');

        $this->freeze('2026-09-14'); // Senin

        $this->assertFalse($loan->fresh()->isOverdue());
        $this->assertSame(0, DailyLoan::overdue()->count());

        $this->actingAs($this->admin)
            ->get('/loans?status=terlambat')
            ->assertInertia(fn ($page) => $page
                ->where('summary.overdue', 0)
                ->has('loans.data', 0)
            );
    }

    public function test_selasa_setelah_jatuh_tempo_jumat_terlambat_satu_hari_sekolah(): void
    {
        $loan = $this->loan('2026-09-11');

        $this->freeze('2026-09-15'); // Selasa

        $this->assertTrue($loan->fresh()->isOverdue());
        $this->assertSame(1, $loan->fresh()->daysLate(), 'Bukan 4 hari kalender.');
        $this->assertSame(1, DailyLoan::overdue()->count());

        $this->actingAs($this->admin)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('stats.loans_overdue', 1)
                ->has('overdueLoans', 1)
                ->where('overdueLoans.0.days_late', 1)
            );

        $this->actingAs($this->admin)
            ->get('/loans?status=terlambat')
            ->assertInertia(fn ($page) => $page
                ->where('summary.overdue', 1)
                ->has('loans.data', 1)
            );
    }

    public function test_libur_nasional_tidak_menambah_hari_terlambat(): void
    {
        $loan = $this->loan('2026-08-14'); // Jumat

        // Senin 17 Agustus 2026 libur, Selasa 18 Agustus hari sekolah
        // pertama sesudah blok libur.
        $this->freeze('2026-08-18');

        $this->assertFalse($loan->fresh()->isOverdue());
        $this->assertSame(0, DailyLoan::overdue()->count());

        $this->freeze('2026-08-19');

        $this->assertTrue($loan->fresh()->isOverdue());
        $this->assertSame(1, DailyLoan::overdue()->count());
    }

    public function test_jatuh_tempo_hari_minggu_digeser_ke_senin(): void
    {
        $loan = $this->loan('2026-09-13'); // Minggu

        $this->freeze('2026-09-14'); // Senin

        $this->assertSame(0, app(FineService::class)->daysLate($loan));
        $this->assertSame(0, DailyLoan::overdue()->count());

        $this->freeze('2026-09-15'); // Selasa

        $this->assertSame(1, DailyLoan::overdue()->count());
    }

    public function test_tenggat_efektif_hari_libur_dan_akhir_pekan(): void
    {
        $fine = app(FineService::class);

        // Minggu -> Senin.
        $this->assertSame(
            '2026-09-14',
            $fine->effectiveDeadline(Carbon::parse('2026-09-13'))->toDateString()
        );

        // 1 Juni 2026 libur Hari Lahir Pancasila -> Selasa 2 Juni.
        $this->assertSame(
            '2026-06-02',
            $fine->effectiveDeadline(Carbon::parse('2026-06-01'))->toDateString()
        );

        // Jumat -> blok akhir pekan + libur 17 Agustus -> Selasa 18 Agustus.
        $this->assertSame(
            '2026-08-18',
            $fine->effectiveDeadline(Carbon::parse('2026-08-14'))->toDateString()
        );
    }
}
