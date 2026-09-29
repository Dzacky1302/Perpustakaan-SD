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
 * Denda keterlambatan: Rp500 per HARI SEKOLAH, maksimal Rp10.000 per buku.
 * Hanya Senin-Jumat yang dihitung; akhir pekan & libur tidak menambah.
 * Denda yang belum lunas memblokir peminjaman berikutnya.
 */
class FineTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Waktu dibekukan agar hasil test tidak bergeser tiap hari
     * (mis. hari Rabu vs Jumat menghasilkan jumlah hari sekolah beda).
     * 16 September 2026 = Rabu, tanggal return default.
     */
    private const NOW = '2026-09-16 10:00:00';

    private Student $student;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(self::NOW);

        $classroom = Classroom::create([
            'name' => 'Kelas 3',
            'grade_level' => 3,
            'academic_year' => '2025/2026',
        ]);

        $this->student = Student::create([
            'classroom_id' => $classroom->id,
            'nisn' => '2222222222',
            'name' => 'Budi',
            'gender' => 'L',
            'is_active' => true,
        ]);

        $category = Category::create(['code' => 'FIK', 'name' => 'Fiksi', 'color' => 'amber']);

        $this->book = Book::create([
            'code' => 'BK-9001',
            'title' => 'Dongeng',
            'category_id' => $category->id,
            'book_type' => 'koleksi',
            'total_copies' => 5,
            'available_copies' => 5,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function loan(array $attributes = []): DailyLoan
    {
        return DailyLoan::create(array_merge([
            'student_id' => $this->student->id,
            'classroom_id' => $this->student->classroom_id,
            'book_id' => $this->book->id,
            'borrowed_at' => Carbon::today()->subDays(20),
            'due_at' => Carbon::today()->subDays(13),
            'status' => DailyLoan::STATUS_DIPINJAM,
        ], $attributes));
    }

    /**
     * helper: tanggal jatuh tempo pada hari sekolah tertentu.
     *
     * Memakai tanggal pasti (bukan "today - N") supaya test tidak
     * bergeser hasilnya saat tanggal eksekusi test berubah.
     */
    private function dueOn(string $date, int $schoolDaysLater = 0): DailyLoan
    {
        return $this->loan(['due_at' => Carbon::parse($date)->addDays($schoolDaysLater)]);
    }

    public function test_denda_dihitung_per_hari_sekolah_keterlambatan(): void
    {
        // Jatuh tempo Senin 7 Sep, kembali Jumat 11 Sep.
        // Hari sekolah yang terlewat: Sel, Rab, Kam, Jum = 4 hari.
        $loan = $this->dueOn('2026-09-07');
        $loan->update(['returned_at' => Carbon::parse('2026-09-11')]);

        $this->assertSame(4, app(FineService::class)->daysLate($loan));
        $this->assertSame(2000, app(FineService::class)->calculate($loan));
    }

    public function test_jatuh_tempo_jumat_kemudian_senin_tidak_berdenda(): void
    {
        // Tenggat efektif ikut bergeser ke Senin, jadi balik Senin = 0.
        $loan = $this->dueOn('2026-09-11');
        $loan->update(['returned_at' => Carbon::parse('2026-09-14')]);

        $this->assertSame(0, app(FineService::class)->daysLate($loan));
        $this->assertSame(0, app(FineService::class)->calculate($loan));
    }

    public function test_satu_hari_sekolah_terlewat_pada_senin_setelah_jumat(): void
    {
        // Kembali Selasa 15 Sep: hari Senin 14 Sep sudah terlewat.
        $loan = $this->dueOn('2026-09-11');
        $loan->update(['returned_at' => Carbon::parse('2026-09-15')]);

        $this->assertSame(1, app(FineService::class)->daysLate($loan));
        $this->assertSame(500, app(FineService::class)->calculate($loan));
    }

    public function test_libur_nasional_tidak_dihitung(): void
    {
        // Jatuh tempo Jumat 14 Agu 2026, kembali Rabu 19 Agu.
        // Blok Sat-Minggu + Senin 17 Agu (Kemerdekaan) diperlakukan bebas,
        // sehingga tenggat efektif bergeser ke Selasa 18 Agu.
        // Hanya Rabu 19 Agu yang terlewat -> 1 hari.
        $loan = $this->dueOn('2026-08-14');
        $loan->update(['returned_at' => Carbon::parse('2026-08-19')]);

        $this->assertSame(1, app(FineService::class)->daysLate($loan));
    }

    public function test_cuti_bersama_bisa_dimatikan(): void
    {
        // Jatuh tempo Jumat 13 Feb 2026, kembali Rabu 18 Feb.
        $loan = $this->dueOn('2026-02-13');
        $loan->update(['returned_at' => Carbon::parse('2026-02-18')]);

        // Cuti bersama + Imlek dihitung: tenggat geser ke Rabu 18, jadi 0.
        $this->assertSame(0, app(FineService::class)->daysLate($loan));

        // Cuti bersama tidak dihitung: Senin 16 Feb jadi hari sekolah,
        // tenggat berhenti di sana sehingga Selasa 17 terlewat = 1 hari.
        config()->set('perpustakaan.fine.count_joint_leave', false);
        $this->assertSame(1, app(FineService::class)->daysLate($loan));
    }

    public function test_denda_dibatasi_maksimal_sepuluh_ribu(): void
    {
        // Terlambat 39 hari kerja: 39 x 500 = Rp19.500, kena plafon.
        $loan = $this->dueOn('2026-06-01');
        $loan->update(['returned_at' => Carbon::parse('2026-07-27')]);

        $this->assertSame(39, app(FineService::class)->daysLate($loan));
        $this->assertSame(10000, app(FineService::class)->calculate($loan));
    }

    public function test_tidak_ada_denda_bila_tepat_waktu(): void
    {
        $loan = $this->loan(['due_at' => Carbon::parse('2026-09-07')]);
        $loan->update(['returned_at' => Carbon::parse('2026-09-07')]);

        $this->assertSame(0, app(FineService::class)->calculate($loan));
    }

    public function test_denda_dikunci_saat_buku_dikembalikan(): void
    {
        // Jatuh tempo Senin 7 Sep, dikembalikan Rabu 16 Sep (waktu dibekukan).
        // Hari sekolah terlewat: 8, 9, 10, 11, 14, 15, 16 = 7 hari.
        $loan = $this->dueOn('2026-09-07');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch("/loans/{$loan->id}/kembali")
            ->assertRedirect();

        $loan->refresh();

        $this->assertSame(7, $loan->fine_days_late);
        $this->assertSame(3500, $loan->fine_amount);
        $this->assertNotNull($loan->return_slip_number);
    }

    public function test_pembayaran_denda_berhasil_dicatat(): void
    {
        $loan = $this->dueOn('2026-09-07');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch("/loans/{$loan->id}/kembali");

        $this->actingAs($admin)
            ->patch("/loans/{$loan->id}/denda", ['fine_notes' => 'Dibayar tunai'])
            ->assertRedirect();

        $loan->refresh();

        $this->assertNotNull($loan->fine_paid_at);
        $this->assertSame($admin->id, $loan->fine_received_by);
    }

    public function test_siswa_berutang_denda_ditolak_saat_meminjam_lagi(): void
    {
        $loan = $this->dueOn('2026-09-07');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch("/loans/{$loan->id}/kembali");

        $this->assertTrue(app(FineService::class)->hasUnpaidFine($this->student));

        $response = $this->actingAs($admin)->post('/loans', [
            'student_id' => $this->student->id,
            'book_id' => $this->book->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertSame(1, DailyLoan::count());
    }

    public function test_setelah_denda_lunas_siswa_boleh_meminjam_lagi(): void
    {
        $loan = $this->dueOn('2026-09-07');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch("/loans/{$loan->id}/kembali");
        $this->actingAs($admin)->patch("/loans/{$loan->id}/denda");

        $this->assertFalse(app(FineService::class)->hasUnpaidFine($this->student));

        $this->actingAs($admin)
            ->post('/loans', ['student_id' => $this->student->id, 'book_id' => $this->book->id])
            ->assertSessionHas('success');

        $this->assertSame(2, DailyLoan::count());
    }

    public function test_peminjaman_baru_dapat_nomor_slip(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post('/loans', ['student_id' => $this->student->id, 'book_id' => $this->book->id])
            ->assertSessionHas('success');

        $loan = DailyLoan::latest('id')->first();

        $this->assertNotNull($loan->slip_number);
        $this->assertStringStartsWith('SP/', $loan->slip_number);
    }

    public function test_halaman_denda_dapat_diakses_kepsek(): void
    {
        $kepsek = User::factory()->create(['role' => 'kepsek']);

        $this->actingAs($kepsek)->get('/loans/denda')->assertOk();
    }

    public function test_kepsek_tidak_boleh_mencatat_pembayaran_denda(): void
    {
        $loan = $this->dueOn('2026-09-07');
        $admin = User::factory()->create(['role' => 'admin']);
        $kepsek = User::factory()->create(['role' => 'kepsek']);

        $this->actingAs($admin)->patch("/loans/{$loan->id}/kembali");

        $this->actingAs($kepsek)
            ->patch("/loans/{$loan->id}/denda")
            ->assertRedirect();

        $this->assertNull($loan->fresh()->fine_paid_at);
    }
}