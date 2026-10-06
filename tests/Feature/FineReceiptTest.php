<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Classroom;
use App\Models\DailyLoan;
use App\Models\FineReceipt;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Kuitansi denda: tagihan (KT/...) terbit saat buku kembali, pelunasan
 * (KP/...) terbit saat pembayaran dicatat.
 *
 * Tiga hal yang dijaga di sini:
 *   1. Kuitansi terbit otomatis, petugas tidak perlu mengingat nomor apa pun.
 *   2. Tiap pencetakan tercatat sebagai salinan; salinan ke atas wajib
 *      bertanda SALINAN supaya tidak bisa disamakan dengan asli.
 *   3. Kode verifikasi yang tercetak di kertas bisa dicocokkan dari layar.
 */
class FineReceiptTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Waktu dibekukan supaya jumlah hari sekolah & nomor kuitansi tidak
     * bergeser antar eksekusi. 16 September 2026 = Rabu.
     */
    private const NOW = '2026-09-16 10:00:00';

    private DailyLoan $loan;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(self::NOW);

        $classroom = Classroom::create([
            'name' => 'Kelas 5A',
            'grade_level' => 5,
            'academic_year' => '2025/2026',
        ]);

        $student = Student::create([
            'classroom_id' => $classroom->id,
            'nisn' => '5555555555',
            'name' => 'Dimas',
            'gender' => 'L',
            'is_active' => true,
        ]);

        $category = Category::create(['code' => 'SIS', 'name' => 'Siswa', 'color' => 'sky']);

        $book = Book::create([
            'code' => 'BK-9300',
            'title' => 'Buku Cerita',
            'category_id' => $category->id,
            'book_type' => 'koleksi',
            'total_copies' => 4,
            'available_copies' => 4,
        ]);

        // Jatuh tempo Jumat 11 Sep 2026, hari ini Rabu 16 Sep 2026,
        // jadi pasti terlambat dan dendanya terkunci saat dikembalikan.
        $this->loan = DailyLoan::create([
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'book_id' => $book->id,
            'borrowed_at' => Carbon::parse('2026-09-04'),
            'due_at' => Carbon::parse('2026-09-11'),
            'status' => DailyLoan::STATUS_DIPINJAM,
        ]);

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function assertIsPdf($response): void
    {
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /**
     * Kembalikan buku (dan kunci dendanya) lalu ambil kuitansinya.
     */
    private function returnBook(): FineReceipt
    {
        $this->actingAs($this->admin)
            ->patch("/loans/{$this->loan->id}/kembali")
            ->assertRedirect();

        $this->assertGreaterThan(0, (int) $this->loan->fresh()->fine_amount);

        return FineReceipt::sole();
    }

    public function test_kuitansi_tagihan_terbit_otomatis_saat_buku_dikembalikan(): void
    {
        $receipt = $this->returnBook();

        $this->assertSame(FineReceipt::TYPE_TAGIHAN, $receipt->type);
        $this->assertStringStartsWith('KT/', $receipt->receipt_number);
        $this->assertSame((int) $this->loan->fresh()->fine_amount, $receipt->amount);
        $this->assertGreaterThan(0, $receipt->amount);

        // Tagihan belum dibayar, jadi tidak ada penerbitnya.
        $this->assertNull($receipt->issued_by);
        $this->assertNull($this->loan->fresh()->fine_paid_at);
    }

    public function test_kuitansi_pelunasan_terbit_otomatis_saat_denda_dibayar(): void
    {
        $this->returnBook();

        $this->actingAs($this->admin)
            ->patch("/loans/{$this->loan->id}/denda", ['fine_notes' => 'Bayar tunai'])
            ->assertRedirect();

        $this->assertSame(2, FineReceipt::count());

        $pelunasan = FineReceipt::where('type', FineReceipt::TYPE_PELUNASAN)->sole();

        $this->assertStringStartsWith('KP/', $pelunasan->receipt_number);
        $this->assertSame($this->admin->id, $pelunasan->issued_by);
        $this->assertSame($this->loan->fresh()->fine_amount, $pelunasan->amount);
    }

    public function test_kuitansi_tidak_terbit_saat_tidak_ada_denda(): void
    {
        $this->loan->update([
            'due_at' => Carbon::parse('2026-09-16'),
            'borrowed_at' => Carbon::parse('2026-09-15'),
        ]);

        $this->actingAs($this->admin)->patch("/loans/{$this->loan->id}/kembali");

        $this->assertSame(0, (int) $this->loan->fresh()->fine_amount);
        $this->assertSame(0, FineReceipt::count());
    }

    public function test_kuitansi_tidak_bisa_dicetak_selama_buku_masih_dipinjam(): void
    {
        // Buku masih di tangan siswa, dendanya pun belum dikunci.
        // Kalau dipaksa, nominal di kertas bisa berbeda dengan tagihan final.
        $this->actingAs($this->admin)
            ->get("/loans/{$this->loan->id}/slip-denda")
            ->assertRedirect();

        $this->assertSame(0, FineReceipt::count());
    }

    public function test_kuitansi_ditolak_saat_peminjaman_tidak_berdenda(): void
    {
        $this->loan->update(['due_at' => Carbon::parse('2026-09-16')]);
        $this->actingAs($this->admin)->patch("/loans/{$this->loan->id}/kembali");

        $this->actingAs($this->admin)
            ->get("/loans/{$this->loan->id}/slip-denda")
            ->assertRedirect();

        $this->assertSame(0, FineReceipt::count());
    }

    public function test_setiap_pencetakan_tercatat_dan_nomor_salinan_berurutan(): void
    {
        $receipt = $this->returnBook();

        $this->assertIsPdf($this->actingAs($this->admin)->get("/loans/{$this->loan->id}/slip-denda"));
        $this->assertIsPdf($this->actingAs($this->admin)->get("/loans/{$this->loan->id}/slip-denda"));
        $this->assertIsPdf($this->actingAs($this->admin)->get("/loans/{$this->loan->id}/slip-denda"));

        $copyNumbers = $receipt->prints()->pluck('copy_number')->sort()->values()->all();

        $this->assertSame([1, 2, 3], $copyNumbers);
        $this->assertSame(3, $receipt->fresh()->printCount());
        $this->assertTrue($receipt->fresh()->isDuplicate());

        // Pencetaknya ikut tercatat, bukan cuma jumlahnya.
        $this->assertSame(
            [$this->admin->id, $this->admin->id, $this->admin->id],
            $receipt->prints()->pluck('printed_by')->all()
        );
    }

    public function test_mencetak_kuitansi_menerbitkan_nomor_bila_belum_ada(): void
    {
        // Peminjaman lama yang belum punya kuitansi tidak perlu diapa-apakan
        // oleh petugas: cukup tekan cetak, kuitansinya menyusul terbit.
        $this->loan->update([
            'status' => DailyLoan::STATUS_KEMBALI,
            'returned_at' => Carbon::parse('2026-09-14'),
            'fine_amount' => 1500,
            'fine_days_late' => 3,
        ]);

        $this->assertSame(0, FineReceipt::count());

        $this->assertIsPdf($this->actingAs($this->admin)->get("/loans/{$this->loan->id}/slip-denda"));

        $receipt = FineReceipt::sole();
        $this->assertSame(FineReceipt::TYPE_TAGIHAN, $receipt->type);
        $this->assertSame(1, $receipt->printCount());
    }

    public function test_cetakan_ulang_ke_atas_bertanda_salinan(): void
    {
        $receipt = $this->returnBook();

        $this->actingAs($this->admin)->get("/loans/{$this->loan->id}/slip-denda");

        // Salinan pertama tidak boleh tertulis "SALINAN".
        $asli = $this->renderReceipt($receipt, salinan: null, printCount: 1);
        $this->assertStringNotContainsString('SALINAN KE-', $asli);
        $this->assertStringNotContainsString('>SALINAN<', $asli);

        $this->actingAs($this->admin)->get("/loans/{$this->loan->id}/slip-denda");

        // Salinan kedua wajib bertanda, jadi tidak bisa disamakan dengan asli.
        $salinan = $this->renderReceipt($receipt->fresh(), salinan: 2, printCount: 2);
        $this->assertStringContainsString('SALINAN KE-2', $salinan);
        $this->assertStringContainsString('bukan salinan asli', $salinan);
    }

    public function test_kode_verifikasi_tercetak_dan_bisa_dicek_petugas(): void
    {
        $receipt = $this->returnBook();
        $this->assertIsPdf($this->actingAs($this->admin)->get("/loans/{$this->loan->id}/slip-denda"));

        $this->assertSame(8, strlen($receipt->verification_code));
        $this->assertMatchesRegularExpression('/^[ACDEFGHJKLMNPQRTUVWXY34679]{8}$/', $receipt->verification_code);

        // Kode ditulis tangan di kertas: spasi & huruf kecil tetap diterima.
        $typed = substr($receipt->verification_code, 0, 4).' '.strtolower(substr($receipt->verification_code, 4));

        $this->actingAs($this->admin)
            ->get('/kuitansi-denda/cek?code='.urlencode($typed))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Circulation/VerifyReceipt')
                ->where('notFound', false)
                ->where('receipt.receipt_number', $receipt->receipt_number)
                ->where('receipt.prints.0.copy_number', 1)
            );
    }

    public function test_kode_verifikasi_yang_salah_dilaporkan_tidak_ditemukan(): void
    {
        $this->actingAs($this->admin)
            ->get('/kuitansi-denda/cek?code=QQQQQQQQ')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Circulation/VerifyReceipt')
                ->where('notFound', true)
                ->where('receipt', null)
            );
    }

    public function test_kepsek_tidak_bisa_mencetak_kuitansi(): void
    {
        $kepsek = User::factory()->create(['role' => User::ROLE_KEPSEK]);
        $receipt = $this->returnBook();

        $this->actingAs($kepsek)
            ->get("/loans/{$this->loan->id}/slip-denda")
            ->assertForbidden();

        // Kepsek hanya membaca, jadi tidak ada salinan baru yang tercatat.
        $this->assertSame(0, $receipt->fresh()->printCount());
    }

    public function test_arsip_kuitansi_menampilkan_jumlah_cetak(): void
    {
        $receipt = $this->returnBook();
        $this->actingAs($this->admin)->get("/loans/{$this->loan->id}/slip-denda");
        $this->actingAs($this->admin)->get("/loans/{$this->loan->id}/slip-denda");

        $this->actingAs($this->admin)
            ->get('/kuitansi-denda')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Circulation/Receipts')
                ->where('summary.total', 1)
                ->where('summary.tagihan', 1)
                ->where('summary.pelunasan', 0)
                ->where('summary.duplikat', 1)
                ->where('receipts.data.0.receipt_number', $receipt->receipt_number)
                ->where('receipts.data.0.print_count', 2)
                ->where('receipts.data.0.verification_code', $receipt->verification_code)
            );
    }

    /**
     * Render kuitansi jadi HTML, supaya tanda SALINAN bisa diperiksa
     * langsung tanpa tergantung cara PDF mengompres isinya.
     */
    private function renderReceipt(FineReceipt $receipt, ?int $salinan, int $printCount): string
    {
        return view('slips.kuitansi-denda', [
            'receipt' => $receipt->load(['dailyLoan.student', 'dailyLoan.classroom', 'dailyLoan.book', 'issuer']),
            'isPaid' => $receipt->type === FineReceipt::TYPE_PELUNASAN,
            'slipNumber' => $receipt->receipt_number,
            'verificationCode' => $receipt->verification_code,
            'fineLabel' => 'Rp'.number_format($receipt->amount, 0, ',', '.'),
            'salinan' => $salinan,
            'printCount' => $printCount,
        ])->render();
    }

    public function test_nomor_kuitansi_unik_antar_peminjaman(): void
    {
        $second = DailyLoan::create([
            'student_id' => $this->loan->student_id,
            'classroom_id' => $this->loan->classroom_id,
            'book_id' => $this->loan->book_id,
            'borrowed_at' => Carbon::parse('2026-09-04'),
            'due_at' => Carbon::parse('2026-09-11'),
            'status' => DailyLoan::STATUS_DIPINJAM,
        ]);

        $this->returnBook();
        $this->actingAs($this->admin)->patch("/loans/{$second->id}/kembali");

        $numbers = FineReceipt::pluck('receipt_number');

        $this->assertSame(2, $numbers->count());
        $this->assertSame(2, $numbers->unique()->count());
        $this->assertSame(
            ['KT/'.now()->format('Y/m').'/0001', 'KT/'.now()->format('Y/m').'/0002'],
            $numbers->sort()->values()->all()
        );
    }
}
