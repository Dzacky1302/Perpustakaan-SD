<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Classroom;
use App\Models\Student;
use App\Services\LibraryVisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Portal siswa mandiri (public, tanpa login).
 *
 * Digitalisasi buku tamu: siswa memilih kelas, nama, keperluan, dan buku.
 * Tanggal serta jam tercatat otomatis oleh sistem.
 */
class StudentPortalController extends Controller
{
    public function __construct(private readonly LibraryVisitService $visits) {}

    public function index(): Response
    {
        $classrooms = Classroom::forYear()
            ->withCount('students')
            ->orderBy('grade_level')
            ->get(['id', 'name', 'grade_level']);

        $books = Book::with('category:id,code,name,color')
            ->where('book_type', 'koleksi')
            ->orderBy('title')
            ->get(['id', 'code', 'title', 'author', 'shelf_location', 'available_copies', 'category_id']);

        return Inertia::render('Student/Index', [
            'classrooms' => $classrooms->map(fn (Classroom $classroom) => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'grade_level' => $classroom->grade_level,
                'students_count' => $classroom->students_count,
            ]),
            'books' => $books->map(fn (Book $book) => [
                'id' => $book->id,
                'code' => $book->code,
                'title' => $book->title,
                'author' => $book->author,
                'shelf' => $book->shelf_location,
                'available' => $book->available_copies,
                'category' => $book->category?->name,
                'category_color' => $book->category?->color ?? 'slate',
            ]),
            'loanLimit' => $this->visits->loanLimit(),
            'loanDays' => $this->visits->loanDays(),
        ]);
    }

    /**
     * Daftar siswa aktif satu kelas (dipakai untuk pencarian di layar sentuh).
     *
     * NISN sengaja tidak ikut dikirim: nomor itu dipakai sebagai kunci
     * verifikasi di langkah berikutnya, jadi jangan sampai bisa dibaca
     * langsung dari daftar nama.
     */
    public function students(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'search' => ['nullable', 'string', 'max:60'],
        ]);

        $students = Student::query()
            ->where('classroom_id', $validated['classroom_id'])
            ->where('is_active', true)
            ->when($validated['search'] ?? null, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(60)
            ->get(['id', 'name']);

        $limit = $this->visits->loanLimit();

        return response()->json([
            'loan_limit' => $limit,
            'data' => $students->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => $student->name,
                'active_loans' => $this->visits->activeLoanCount($student),
            ]),
        ]);
    }

    /**
     * Periksa kecocokan nama siswa dengan NISN sebelum data dicatat.
     *
     * Dipanggil dari langkah verifikasi portal, supaya siswa yang salah
     * memilih nama tidak baru ketahuan setelah mengisi seluruh formulir.
     * Jawaban untuk NISN tidak dikenal dan nama tidak cocok sengaja dibuat
     * sama, agar dari luar tidak bisa dipakai menebak NISN yang terdaftar.
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_name' => ['required', 'string', 'max:80'],
            'nisn' => ['required', 'string', 'digits:10'],
        ]);

        $student = Student::query()
            ->where('nisn', $validated['nisn'])
            ->where('name', $validated['student_name'])
            ->first();

        if (! $student) {
            return response()->json([
                'valid' => false,
                'message' => 'NISN tidak cocok dengan nama yang dipilih. Periksa kembali.',
            ], 422);
        }

        if (! $student->is_active) {
            return response()->json([
                'valid' => false,
                'message' => 'Siswa ini tidak aktif.',
            ], 422);
        }

        return response()->json(['valid' => true]);
    }

    /**
     * Simpan kunjungan (sekaligus mencatat peminjaman bila gewählt "pinjam").
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_name' => ['required', 'string', 'max:80'],
            'nisn' => ['required', 'string', 'digits:10'],
            'purpose' => ['required', Rule::in(['membaca', 'pinjam'])],
            'book_id' => ['nullable', 'integer', 'exists:books,id'],
            'loan_days' => ['nullable', 'integer', 'between:1,30'],
            'notes' => ['nullable', 'string', 'max:150'],
        ]);

        // Nama dan NISN harus cocok. Pemeriksaan ini juga dijalankan di
        // langkah verifikasi portal, tetapi tetap diulang di sini karena
        // endpoint simpan bisa dipanggil langsung tanpa melewati langkah itu.
        $student = Student::where('nisn', $validated['nisn'])->first();

        if (! $student || $student->name !== $validated['student_name']) {
            throw ValidationException::withMessages([
                'nisn' => 'NISN tidak cocok dengan nama yang dipilih. Periksa kembali.',
            ]);
        }

        if (! $student->is_active) {
            return back()->with('error', 'Siswa ini tidak aktif.');
        }

        $book = isset($validated['book_id']) ? Book::find($validated['book_id']) : null;

        try {
            $result = $this->visits->record(
                $student,
                $validated['purpose'],
                $book,
                $validated['notes'] ?? null,
                isset($validated['loan_days']) ? (int) $validated['loan_days'] : null
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ($validated['purpose'] === 'pinjam') {
            $message = sprintf(
                'Buku "%s" berhasil dipinjam. Kembalikan paling lambat %s.',
                $book->title,
                $result['loan']->due_at->translatedFormat('d M Y')
            );
        } else {
            $message = 'Kunjungan tercatat. Selamat membaca!';
        }

        if ($result['duplicate']) {
            $message .= ' Catatan: nama ini sudah tercatat kunjungan hari ini.';
        }

        return back()->with('success', $message);
    }
}
