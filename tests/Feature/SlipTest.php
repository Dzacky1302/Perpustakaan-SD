<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Classroom;
use App\Models\DailyLoan;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Slip cetak: surat peminjaman, surat pengembalian, dan kuitansi denda.
 *
 * Semua slip hanya boleh dicetak pada kondisi yang benar, supaya arsip
 * kertas tidak pernah berisi data yang belum final.
 */
class SlipTest extends TestCase
{
    use RefreshDatabase;

    private DailyLoan $loan;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $classroom = Classroom::create([
            'name' => 'Kelas 4A',
            'grade_level' => 4,
            'academic_year' => '2025/2026',
        ]);

        $student = Student::create([
            'classroom_id' => $classroom->id,
            'nisn' => '3333333333',
            'name' => 'Sari',
            'gender' => 'P',
            'is_active' => true,
        ]);

        $category = Category::create(['code' => 'SAI', 'name' => 'Sains', 'color' => 'teal']);

        $book = Book::create([
            'code' => 'BK-9100',
            'title' => 'Buku Sains',
            'category_id' => $category->id,
            'book_type' => 'koleksi',
            'total_copies' => 3,
            'available_copies' => 3,
        ]);

        // Terlambat 3 hari, supaya sekaligus ada dendanya.
        $this->loan = DailyLoan::create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'book_id' => $book->id,
            'borrowed_at' => Carbon::today()->subDays(12),
            'due_at' => Carbon::today()->subDays(3),
            'status' => DailyLoan::STATUS_DIPINJAM,
        ]);

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function assertIsPdf($response): void
    {
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));

        $body = $response->getContent();

        $this->assertStringStartsWith('%PDF', $body);
    }

    public function test_surat_peminjaman_dicetak_dan_diberi_nomor(): void
    {
        $this->assertIsPdf($this->actingAs($this->admin)->get("/loans/{$this->loan->id}/slip-pinjaman"));

        $this->assertNotNull($this->loan->fresh()->slip_number);
    }

    public function test_nomor_slip_tetap_sama_saat_dicetak_ulang(): void
    {
        $this->actingAs($this->admin)->get("/loans/{$this->loan->id}/slip-pinjaman");
        $first = $this->loan->fresh()->slip_number;

        $this->actingAs($this->admin)->get("/loans/{$this->loan->id}/slip-pinjaman");

        $this->assertSame($first, $this->loan->fresh()->slip_number);
    }

    public function test_surat_pengembalian_ditolak_saat_buku_masih_dipinjam(): void
    {
        $this->actingAs($this->admin)
            ->get("/loans/{$this->loan->id}/slip-kembali")
            ->assertRedirect();
    }

    public function test_surat_pengembalian_ada_setelah_buku_dikembalikan(): void
    {
        $this->actingAs($this->admin)->patch("/loans/{$this->loan->id}/kembali");

        $this->assertIsPdf($this->actingAs($this->admin)->get("/loans/{$this->loan->id}/slip-kembali"));
        $this->assertNotNull($this->loan->fresh()->return_slip_number);
    }

    public function test_kuitansi_denda_ditolak_saat_belum_lunas(): void
    {
        $this->actingAs($this->admin)->patch("/loans/{$this->loan->id}/kembali");

        $this->actingAs($this->admin)
            ->get("/loans/{$this->loan->id}/slip-denda")
            ->assertRedirect();
    }

    public function test_kuitansi_denda_ada_setelah_dibayar(): void
    {
        $this->actingAs($this->admin)->patch("/loans/{$this->loan->id}/kembali");
        $this->actingAs($this->admin)->patch("/loans/{$this->loan->id}/denda");

        $this->assertIsPdf($this->actingAs($this->admin)->get("/loans/{$this->loan->id}/slip-denda"));
    }

    public function test_kepsek_boleh_mencetak_slip(): void
    {
        $kepsek = User::factory()->create(['role' => 'kepsek']);

        $this->assertIsPdf($this->actingAs($kepsek)->get("/loans/{$this->loan->id}/slip-pinjaman"));
    }

    public function test_laporan_denda_pdf_dan_excel(): void
    {
        $this->actingAs($this->admin)->patch("/loans/{$this->loan->id}/kembali");

        $this->actingAs($this->admin)
            ->get('/reports/denda/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($this->admin)
            ->get('/reports/denda/excel')
            ->assertOk();
    }
}