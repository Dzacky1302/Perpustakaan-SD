<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Classroom;
use App\Models\PackageLoan;
use App\Models\Student;
use App\Services\BookStockService;
use App\Services\SpreadsheetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PackageLoanController extends Controller
{
    public function __construct(
        private readonly BookStockService $stock,
        private readonly SpreadsheetService $spreadsheet,
    ) {
    }

    /**
     * Matriks buku paket: daftar siswa x daftar buku paket kelas tersebut.
     */
    public function index(Request $request): Response
    {
        $classrooms = Classroom::forYear($request->query('academic_year'))
            ->withCount('students')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level', 'academic_year', 'homeroom_teacher']);

        $selectedClassroom = $classrooms->firstWhere('id', (int) $request->query('classroom_id'))
            ?? $classrooms->first();

        $academicYear = $request->query('academic_year') ?: $selectedClassroom?->academic_year;

        $books = $selectedClassroom
            ? Book::where('book_type', 'paket')
                ->where('grade_level', $selectedClassroom->grade_level)
                ->orderBy('title')
                ->get(['id', 'code', 'title'])
            : collect();

        $loans = $selectedClassroom
            ? PackageLoan::where('classroom_id', $selectedClassroom->id)
                ->where('academic_year', $academicYear)
                ->get()
                ->keyBy(fn (PackageLoan $loan) => $loan->student_id.'-'.$loan->book_id)
            : collect();

        $students = $selectedClassroom
            ? Student::where('classroom_id', $selectedClassroom->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'nisn', 'name'])
            : collect();

        return Inertia::render('Circulation/PackageLoans', [
            'classrooms' => $classrooms->map(fn (Classroom $classroom) => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'grade_level' => $classroom->grade_level,
                'academic_year' => $classroom->academic_year,
                'homeroom_teacher' => $classroom->homeroom_teacher,
                'students_count' => $classroom->students_count,
            ]),
            'selected' => $selectedClassroom ? [
                'id' => $selectedClassroom->id,
                'name' => $selectedClassroom->name,
                'grade_level' => $selectedClassroom->grade_level,
                'homeroom_teacher' => $selectedClassroom->homeroom_teacher,
                'students_count' => $selectedClassroom->students_count,
            ] : null,
            'academicYear' => $academicYear,
            'books' => $books->map(fn (Book $book) => [
                'id' => $book->id,
                'code' => $book->code,
                'title' => $book->title,
            ]),
            'matrix' => $students->map(function (Student $student) use ($books, $loans) {
                $cells = $books->map(function (Book $book) use ($student, $loans) {
                    $loan = $loans->get($student->id.'-'.$book->id);

                    return [
                        'book_id' => $book->id,
                        'loan_id' => $loan?->id,
                        'status' => $loan?->status,
                        'returned_at' => $loan?->returned_at?->toDateString(),
                        'return_condition' => $loan?->return_condition,
                    ];
                })->values()->all();

                $statuses = collect($cells)->pluck('status');

                return [
                    'student_id' => $student->id,
                    'nisn' => $student->nisn,
                    'name' => $student->name,
                    'cells' => $cells,
                    'active_count' => $statuses->filter(fn ($status) => $status === PackageLoan::STATUS_DIPINJAM)->count(),
                    'missing_count' => $statuses->filter(fn ($status) => $status === PackageLoan::STATUS_HILANG)->count(),
                    'is_complete' => $statuses->isNotEmpty()
                        && $statuses->every(fn ($status) => $status !== null && $status !== PackageLoan::STATUS_DIPINJAM),
                ];
            }),
            'summary' => [
                'students' => $students->count(),
                'books' => $books->count(),
                'distributed' => $loans->count(),
                'active' => $loans->where('status', PackageLoan::STATUS_DIPINJAM)->count(),
                'returned' => $loans->where('status', PackageLoan::STATUS_KEMBALI)->count(),
                'lost' => $loans->where('status', PackageLoan::STATUS_HILANG)->count(),
            ],
        ]);
    }

    /**
     * Distribusi massal: satu klik untuk seluruh siswa di satu kelas.
     */
    public function distribute(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'academic_year' => ['required', 'string', 'max:9'],
            'given_at' => ['nullable', 'date'],
            'book_ids' => ['nullable', 'array'],
            'book_ids.*' => ['integer', 'exists:books,id'],
        ]);

        $classroom = Classroom::findOrFail($validated['classroom_id']);
        $students = Student::where('classroom_id', $classroom->id)->where('is_active', true)->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'Belum ada siswa aktif di kelas ini.');
        }

        $books = Book::where('book_type', 'paket')
            ->where('grade_level', $classroom->grade_level)
            ->when(! empty($validated['book_ids']), fn ($query) => $query->whereIn('id', $validated['book_ids']))
            ->orderBy('title')
            ->get();

        if ($books->isEmpty()) {
            return back()->with('error', 'Belum ada buku paket terdaftar untuk tingkat kelas ini.');
        }

        $givenAt = isset($validated['given_at']) ? Carbon::parse($validated['given_at']) : Carbon::today();

        $created = 0;
        $skipped = 0;
        $outOfStock = 0;

        DB::transaction(function () use ($students, $books, $classroom, $validated, $givenAt, &$created, &$skipped, &$outOfStock) {
            foreach ($students as $student) {
                foreach ($books as $book) {
                    $exists = PackageLoan::where('student_id', $student->id)
                        ->where('book_id', $book->id)
                        ->where('academic_year', $validated['academic_year'])
                        ->exists();

                    if ($exists) {
                        $skipped++;

                        continue;
                    }

                    if (! $this->stock->isAvailable($book)) {
                        $outOfStock++;

                        continue;
                    }

                    PackageLoan::create([
                        'student_id' => $student->id,
                        'classroom_id' => $classroom->id,
                        'book_id' => $book->id,
                        'academic_year' => $validated['academic_year'],
                        'given_at' => $givenAt,
                        'status' => PackageLoan::STATUS_DIPINJAM,
                    ]);

                    $this->stock->decrease($book);
                    $created++;
                }
            }
        });

        $message = "Distribusi buku paket kelas {$classroom->name}: {$created} buku tercatat.";
        $message .= $skipped > 0 ? " {$skipped} data dilewati (sudah pernah dibagikan)." : '';
        $message .= $outOfStock > 0 ? " {$outOfStock} buku gagal karena stok habis." : '';

        return back()->with($outOfStock > 0 ? 'error' : 'success', $message);
    }

    /**
     * Pengembalian buku paket massal dari matriks checklist.
     */
    public function returnBatch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'loan_ids' => ['required', 'array'],
            'loan_ids.*' => ['integer', 'exists:package_loans,id'],
            'returned_at' => ['nullable', 'date'],
            'return_condition' => ['nullable', Rule::in(['baik', 'rusak ringan', 'rusak berat'])],
            'status' => ['nullable', Rule::in([PackageLoan::STATUS_KEMBALI, PackageLoan::STATUS_HILANG])],
        ]);

        $status = $validated['status'] ?? PackageLoan::STATUS_KEMBALI;

        $loans = PackageLoan::with('book')
            ->whereIn('id', $validated['loan_ids'])
            ->where('status', PackageLoan::STATUS_DIPINJAM)
            ->get();

        if ($loans->isEmpty()) {
            return back()->with('error', 'Tidak ada buku paket aktif pada pilihan tersebut.');
        }

        DB::transaction(function () use ($loans, $validated, $status) {
            foreach ($loans as $loan) {
                $loan->update([
                    'status' => $status,
                    'returned_at' => $status === PackageLoan::STATUS_KEMBALI
                        ? ($validated['returned_at'] ?? Carbon::today())
                        : null,
                    'return_condition' => $status === PackageLoan::STATUS_KEMBALI
                        ? ($validated['return_condition'] ?? 'baik')
                        : 'hilang',
                ]);

                if ($status === PackageLoan::STATUS_KEMBALI) {
                    $this->stock->increase($loan->book);
                }
            }
        });

        $label = $status === PackageLoan::STATUS_KEMBALI ? 'dikembalikan' : 'ditandai hilang';

        return back()->with('success', "{$loans->count()} buku paket berhasil {$label}.");
    }

    public function destroy(PackageLoan $packageLoan): RedirectResponse
    {
        DB::transaction(function () use ($packageLoan) {
            if ($packageLoan->status === PackageLoan::STATUS_DIPINJAM) {
                $this->stock->increase($packageLoan->book);
            }

            $packageLoan->delete();
        });

        return back()->with('success', 'Catatan buku paket dihapus.');
    }
}
