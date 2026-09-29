<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\DailyLoan;
use App\Models\LibraryVisit;
use App\Models\PackageLoan;
use App\Models\Student;
use App\Services\BookStockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LibraryActivitySeeder extends Seeder
{
    /**
     * Buku tamu, peminjaman harian, dan penyerahan buku paket tahun ajaran ini.
     */
    public function run(): void
    {
        $students = Student::with('classroom')->get();
        $collectionBooks = Book::where('book_type', 'koleksi')->get();

        $this->seedVisits($students, $collectionBooks);
        $this->seedDailyLoans($students, $collectionBooks);
        $this->seedPackageLoans($students);
    }

    /**
     * @param  Collection<int, Student>  $students
     * @param  Collection<int, Book>  $books
     */
    private function seedVisits($students, $books): void
    {
        $purposes = ['membaca', 'pinjam', 'tugas', 'lainnya'];
        $rows = [];

        foreach (range(0, 29) as $daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);

            if ($date->isWeekend()) {
                continue;
            }

            foreach ($students->random(random_int(4, 12)) as $student) {
                $rows[] = [
                    'student_id' => $student->id,
                    'book_id' => random_int(1, 3) === 1 ? $books->random()->id : null,
                    'visit_date' => $date->toDateString(),
                    'arrival_time' => Carbon::createFromTime(random_int(7, 13), random_int(0, 59))->format('H:i:s'),
                    'purpose' => $purposes[array_rand($purposes)],
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            LibraryVisit::insert($chunk);
        }
    }

    /**
     * Sebagian peminjaman sudah dikembalikan, sebagian masih berjalan,
     * dan beberapa sudah melewati jatuh tempo.
     *
     * @param  Collection<int, Student>  $students
     * @param  Collection<int, Book>  $books
     */
    private function seedDailyLoans($students, $books): void
    {
        $stock = app(BookStockService::class);
        $libraryOpened = Carbon::today()->subDays(120);

        foreach (range(1, 90) as $ignore) {
            $student = $students->random();
            $book = $books->random();
            $borrowedAt = Carbon::createFromTimestamp(
                random_int($libraryOpened->timestamp, Carbon::today()->timestamp)
            )->startOfDay();

            $dueAt = $borrowedAt->copy()->addDays(7);
            $status = DailyLoan::STATUS_DIPINJAM;

            if ($borrowedAt->diffInDays(Carbon::today()) > 10) {
                $status = random_int(1, 10) <= 8 ? DailyLoan::STATUS_KEMBALI : DailyLoan::STATUS_HILANG;
            } elseif (random_int(1, 100) <= 40) {
                $status = DailyLoan::STATUS_KEMBALI;
            }

            if ($status === DailyLoan::STATUS_DIPINJAM && ! $stock->isAvailable($book)) {
                continue;
            }

            DailyLoan::create([
                'student_id' => $student->id,
                'book_id' => $book->id,
                'borrowed_at' => $borrowedAt,
                'due_at' => $dueAt,
                'returned_at' => $status === DailyLoan::STATUS_KEMBALI
                    ? $borrowedAt->copy()->addDays(random_int(1, 10))
                    : null,
                'status' => $status,
                'notes' => $status === DailyLoan::STATUS_HILANG ? 'Buku tidak ditemukan saat pengembalian.' : null,
            ]);

            if ($status === DailyLoan::STATUS_DIPINJAM) {
                $stock->decrease($book);
            }
        }
    }

    /**
     * Menyerahkan seluruh buku paket sesuai tingkat kelas setiap siswa.
     *
     * @param  Collection<int, Student>  $students
     */
    private function seedPackageLoans($students): void
    {
        $stock = app(BookStockService::class);
        $booksByGrade = Book::where('book_type', 'paket')->get()->groupBy('grade_level');
        $givenAt = Carbon::today()->subDays(150);

        foreach ($students as $student) {
            foreach ($booksByGrade[$student->classroom->grade_level] ?? [] as $book) {
                if (! $stock->isAvailable($book)) {
                    continue;
                }

                PackageLoan::create([
                    'student_id' => $student->id,
                    'classroom_id' => $student->classroom_id,
                    'book_id' => $book->id,
                    'academic_year' => MasterDataSeeder::ACADEMIC_YEAR,
                    'given_at' => $givenAt,
                    'returned_at' => null,
                    'status' => PackageLoan::STATUS_DIPINJAM,
                    'return_condition' => null,
                    'notes' => null,
                ]);

                $stock->decrease($book);
            }
        }
    }
}
