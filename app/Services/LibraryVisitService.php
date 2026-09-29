<?php

namespace App\Services;

use App\Models\Book;
use App\Models\DailyLoan;
use App\Models\LibraryVisit;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pencatatan mandiri oleh siswa: buku tamu digital + peminjaman buku koleksi.
 *
 * Dipakai oleh Portal Siswa (public) dan halaman Kios (operasional petugas),
 * sehingga aturan-kuotanya hanya ada di satu tempat.
 */
class LibraryVisitService
{
    public function __construct(
        private readonly BookStockService $stock,
        private readonly FineService $fine,
    ) {}

    public function loanLimit(): int
    {
        return (int) config('perpustakaan.max_loans_per_student', 2);
    }

    public function loanDays(): int
    {
        return (int) config('perpustakaan.loan_days', 7);
    }

    public function activeLoanCount(Student $student): int
    {
        return DailyLoan::where('student_id', $student->id)
            ->where('status', DailyLoan::STATUS_DIPINJAM)
            ->count();
    }

    public function isBorrowing(Student $student, Book $book): bool
    {
        return DailyLoan::where('student_id', $student->id)
            ->where('book_id', $book->id)
            ->where('status', DailyLoan::STATUS_DIPINJAM)
            ->exists();
    }

    public function hasVisitedToday(Student $student): bool
    {
        return LibraryVisit::where('student_id', $student->id)
            ->whereDate('visit_date', Carbon::today())
            ->exists();
    }

    /**
     * Catat satu kunjungan. Bila keperluan "pinjam", sekaligus buat
     * catatan peminjaman dan kurangi stok buku.
     *
     * @return array{visit: LibraryVisit, loan: ?DailyLoan, duplicate: bool}
     *
     * @throws RuntimeException bila aturan peminjaman dilanggar.
     */
    public function record(
        Student $student,
        string $purpose,
        ?Book $book,
        ?string $notes = null,
        ?int $loanDays = null
    ): array {
        $borrowing = $purpose === 'pinjam';

        if ($borrowing) {
            $this->assertCanBorrow($student, $book);
        }

        $duplicate = $this->hasVisitedToday($student);
        $limit = $this->loanLimit();
        $days = $loanDays ?: $this->loanDays();

        return DB::transaction(function () use ($student, $purpose, $book, $notes, $borrowing, $duplicate, $limit, $days) {
            $visit = LibraryVisit::create([
                'student_id' => $student->id,
                'classroom_id' => $student->classroom_id,
                'book_id' => $book?->id,
                'visit_date' => Carbon::today(),
                'arrival_time' => Carbon::now()->format('H:i:s'),
                'purpose' => $purpose,
                'notes' => $notes,
            ]);

            $loan = null;

            if ($borrowing) {
                $borrowedAt = Carbon::today();

                $loan = DailyLoan::create([
                    'student_id' => $student->id,
                    'classroom_id' => $student->classroom_id,
                    'book_id' => $book->id,
                    'borrowed_at' => $borrowedAt,
                    'due_at' => $borrowedAt->copy()->addDays($days),
                    'status' => DailyLoan::STATUS_DIPINJAM,
                    'notes' => $notes,
                ]);

                $this->stock->decrease($book);
            }

            return [
                'visit' => $visit,
                'loan' => $loan,
                'duplicate' => $duplicate,
                'limit' => $limit,
            ];
        });
    }

    /**
     * @throws RuntimeException
     */
    private function assertCanBorrow(Student $student, ?Book $book): void
    {
        if (! $book) {
            throw new RuntimeException('Pilih buku yang ingin dipinjam.');
        }

        // Denda yang belum dibayar menjadi alasan berhenti sebelum cek lain,
        // karena ini yang paling sering jadi pertanyaan siswa di loket.
        if ($message = $this->fine->blockMessage($student)) {
            throw new RuntimeException($message);
        }

        if ($book->book_type !== 'koleksi') {
            throw new RuntimeException('Buku paket tidak bisa dipinjam lewat halaman ini.');
        }

        if (! $this->stock->isAvailable($book)) {
            throw new RuntimeException("Stok \"{$book->title}\" sedang kosong, semua eksemplar dipinjam.");
        }

        if ($this->isBorrowing($student, $book)) {
            throw new RuntimeException("Buku \"{$book->title}\" sedang kamu pinjam.");
        }

        $limit = $this->loanLimit();
        $active = $this->activeLoanCount($student);

        if ($active >= $limit) {
            throw new RuntimeException(
                "Kuota peminjaman sudah penuh ({$active}/{$limit} buku). Kembalikan buku yang sedang dipinjam terlebih dahulu."
            );
        }
    }
}
