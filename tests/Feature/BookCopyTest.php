<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Classroom;
use App\Models\DailyLoan;
use App\Models\Student;
use App\Models\User;
use App\Services\BookCodeService;
use App\Services\BookCopyService;
use App\Services\BookStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * ISBN, barcode, dan pelacakan eksemplar fisik.
 *
 * Fokus test: barcode harus menunjuk SATU eksemplar, bukan satu judul, dan
 * peminjaman buku lama yang belum punya eksemplar tetap harus jalan.
 */
class BookCopyTest extends TestCase
{
    use RefreshDatabase;

    private const NOW = '2026-09-16 10:00:00';

    private Book $book;

    private Student $student;

    private User $admin;

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
            'nisn' => '3333333333',
            'name' => 'Sari',
            'gender' => 'P',
            'is_active' => true,
        ]);

        $category = Category::create(['code' => 'FIK', 'name' => 'Fiksi', 'color' => 'amber']);

        $this->book = Book::create([
            'code' => 'BK-0001',
            'isbn' => '9791234567896',
            'title' => 'Dongeng',
            'category_id' => $category->id,
            'book_type' => 'koleksi',
            'total_copies' => 3,
            'available_copies' => 3,
        ]);

        app(BookCopyService::class)->ensureCopies($this->book, 3);

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function copies()
    {
        return $this->book->copies()->orderBy('id')->get();
    }

    private function newStudent(string $nisn, string $name): Student
    {
        return Student::create([
            'classroom_id' => $this->student->classroom_id,
            'nisn' => $nisn,
            'name' => $name,
            'gender' => 'L',
            'is_active' => true,
        ]);
    }

    // ------------------------------------------------------------------
    // Checksum ISBN dan barcode
    // ------------------------------------------------------------------

    public function test_isbn_dengan_checksum_benar_diterima(): void
    {
        $codes = app(BookCodeService::class);

        $this->assertTrue($codes->isValidIsbn('9791234567896'));
        $this->assertTrue($codes->isValidIsbn('979-1-234-56789-6'));
        $this->assertFalse($codes->isValidIsbn('9791234567897'));
        $this->assertTrue($codes->isValidIsbn('0-8044-2957-X'));
    }

    public function test_isbn_dengan_checksum_salah_ditolak(): void
    {
        $codes = app(BookCodeService::class);

        $this->assertFalse($codes->isValidIsbn('979-123-456-7-9'));
        $this->assertFalse($codes->isValidIsbn('9791234567897'));
        $this->assertFalse($codes->isValidIsbn('1234567890123'));
        $this->assertFalse($codes->isValidIsbn('12345'));
    }

    public function test_barcode_ean13_harus_lolos_checksum(): void
    {
        $codes = app(BookCodeService::class);
        $barcode = $codes->generateBarcode(1);

        $this->assertSame(13, strlen($barcode));
        $this->assertTrue($codes->isValidEan13($barcode));
        $this->assertFalse($codes->isValidEan13('1234567890123'));
    }

    public function test_isbn_tidak_valid_ditolak_saat_menambah_buku(): void
    {
        $this->actingAs($this->admin)
            ->post('/books', [
                'code' => 'BK-9999',
                'title' => 'Judul Uji',
                'isbn' => '979-123-456-7-9',
                'category_id' => $this->book->category_id,
                'book_type' => 'koleksi',
                'total_copies' => 1,
            ])
            ->assertSessionHasErrors('isbn');

        $this->assertDatabaseMissing('books', ['code' => 'BK-9999']);
    }

    public function test_isbn_kosong_diterima(): void
    {
        $this->actingAs($this->admin)
            ->post('/books', [
                'code' => 'BK-8888',
                'title' => 'Tanpa ISBN',
                'isbn' => '',
                'category_id' => $this->book->category_id,
                'book_type' => 'koleksi',
                'total_copies' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('books', ['code' => 'BK-8888']);
    }

    // ------------------------------------------------------------------
    // Eksemplar
    // ------------------------------------------------------------------

    public function test_menambah_buku_otomatis_membuat_eksemplar(): void
    {
        $this->actingAs($this->admin)
            ->post('/books', [
                'code' => 'BK-7777',
                'title' => 'Cerita Pendek',
                'isbn' => '9791234567896',
                'category_id' => $this->book->category_id,
                'book_type' => 'koleksi',
                'total_copies' => 4,
            ])
            ->assertSessionHasNoErrors();

        $book = Book::where('code', 'BK-7777')->first();

        $this->assertSame(4, $book->copies()->count());
    }

    public function test_eksemplar_punya_nomor_registrasi_unik(): void
    {
        $numbers = $this->copies()->pluck('accession_number');

        $this->assertCount(3, $numbers);
        $this->assertCount(3, $numbers->unique());
    }

    public function test_naikkan_total_copies_membuat_eksemplar_baru(): void
    {
        $this->actingAs($this->admin)->patch('/books/'.$this->book->id, [
            'code' => $this->book->code,
            'title' => $this->book->title,
            'category_id' => $this->book->category_id,
            'book_type' => 'koleksi',
            'total_copies' => 5,
        ])->assertSessionHasNoErrors();

        $this->assertSame(5, $this->book->copies()->count());
    }

    // ------------------------------------------------------------------
    // Pencarian terpadu
    // ------------------------------------------------------------------

    public function test_pencarian_mengenali_barcode_isbn_kode_dan_judul(): void
    {
        $service = app(BookCopyService::class);
        $this->copies()->first()->update(['barcode' => '8991234567890']);

        $byBarcode = $service->locate('8991234567890');
        $this->assertSame($this->book->id, $byBarcode['book']->id);
        $this->assertSame(BookCopyService::MATCH_BARCODE, $byBarcode['matched_by']);

        $this->assertSame(BookCopyService::MATCH_ISBN, $service->locate('9791234567896')['matched_by']);
        $this->assertSame(BookCopyService::MATCH_CODE, $service->locate('BK-0001')['matched_by']);
        $this->assertSame(BookCopyService::MATCH_TITLE, $service->locate('dongeng')['matched_by']);

        $this->assertNull($service->locate('tidak-ada-sama-sekali')['book']);
    }

    public function test_barcode_menunjuk_eksemplar_yang_pasth(): void
    {
        $service = app(BookCopyService::class);
        $second = $this->copies()->skip(1)->first();
        $second->update(['barcode' => '8991234567906']);

        $found = $service->locate('8991234567906');

        $this->assertSame($second->id, $found['copy']->id);
        $this->assertSame($this->book->id, $found['book']->id);
    }

    public function test_alamat_pencarian_terdaftar(): void
    {
        $this->actingAs($this->admin)
            ->get('/loans/cari-buku?q=dongeng')
            ->assertOk()
            ->assertJsonPath('books.0.title', 'Dongeng');
    }

    // ------------------------------------------------------------------
    // Peminjaman
    // ------------------------------------------------------------------

    public function test_peminjaman_lewat_barcode_mencatat_eksemplar(): void
    {
        $copy = $this->copies()->first();
        $copy->update(['barcode' => '8991234567890']);

        $this->actingAs($this->admin)->post('/loans', [
            'student_id' => $this->student->id,
            'book_query' => '8991234567890',
        ])->assertSessionHas('success');

        $loan = DailyLoan::first();

        $this->assertSame($copy->id, $loan->book_copy_id);
        $this->assertSame($this->book->id, $loan->book_id);
    }

    public function test_peminjaman_lewat_isbn_memilih_eksemplar_otomatis(): void
    {
        $this->actingAs($this->admin)->post('/loans', [
            'student_id' => $this->student->id,
            'book_query' => '9791234567896',
        ])->assertSessionHas('success');

        $loan = DailyLoan::first();

        $this->assertNotNull($loan->book_copy_id);
        $this->assertFalse($loan->bookCopy->isAvailable(), 'Eksemplar yang dipinjam tidak boleh tersedia lagi.');
    }

    public function test_eksemplar_yang_sedang_dipinjam_tidak_dipakai_lagi(): void
    {
        $first = $this->copies()->first();

        $this->actingAs($this->admin)->post('/loans', [
            'student_id' => $this->student->id,
            'book_id' => $this->book->id,
        ])->assertSessionHas('success');

        $this->assertSame($first->id, DailyLoan::first()->book_copy_id);

        $other = $this->newStudent('4444444444', 'Budi');

        $this->actingAs($this->admin)->post('/loans', [
            'student_id' => $other->id,
            'book_id' => $this->book->id,
        ])->assertSessionHas('success');

        $copies = DailyLoan::pluck('book_copy_id');

        $this->assertNotSame($copies[0], $copies[1]);
    }

    public function test_peminjaman_tanpa_input_buku_ditolak(): void
    {
        $this->actingAs($this->admin)->post('/loans', [
            'student_id' => $this->student->id,
        ])->assertSessionHas('error');

        $this->assertSame(0, DailyLoan::count());
    }

    public function test_buku_tanpa_eksemplar_tetap_bisa_dipinjam(): void
    {
        $legacy = Book::create([
            'code' => 'BK-0002',
            'title' => 'Buku Lama',
            'category_id' => $this->book->category_id,
            'book_type' => 'koleksi',
            'total_copies' => 2,
            'available_copies' => 2,
        ]);

        $this->assertSame(0, $legacy->copies()->count());

        $this->actingAs($this->admin)->post('/loans', [
            'student_id' => $this->student->id,
            'book_id' => $legacy->id,
        ])->assertSessionHas('success');

        $loan = DailyLoan::first();

        $this->assertSame($legacy->id, $loan->book_id);
        $this->assertNull($loan->book_copy_id, 'Buku tanpa eksemplar tetap boleh dipinjam.');
    }

    // ------------------------------------------------------------------
    // Stok
    // ------------------------------------------------------------------

    public function test_stok_dihitung_dari_eksemplar(): void
    {
        $stock = app(BookStockService::class);

        $this->book->update(['available_copies' => 99]);
        $stock->sync($this->book);
        $this->assertSame(3, $this->book->fresh()->available_copies);

        $this->copies()->first()->update(['condition' => BookCopy::CONDITION_RUSAK]);
        $stock->sync($this->book);
        $this->assertSame(2, $this->book->fresh()->available_copies);
    }

    public function test_eksemplar_rusak_tidak_dipinjamkan(): void
    {
        $copy = $this->copies()->first();
        $copy->update(['condition' => BookCopy::CONDITION_RUSAK, 'barcode' => '8991234567890']);

        $this->actingAs($this->admin)->post('/loans', [
            'student_id' => $this->student->id,
            'book_query' => '8991234567890',
        ])->assertSessionHas('success');

        $this->assertNotSame($copy->id, DailyLoan::first()->book_copy_id);
    }
}