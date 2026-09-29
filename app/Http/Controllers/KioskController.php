<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Classroom;
use App\Models\LibraryVisit;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KioskController extends Controller
{
    /**
     * Mode kios layar sentuh: siswa memilih kelas, memilih nama,
     * lalu buku tamu terisi otomatis.
     */
    public function index(): Response
    {
        return Inertia::render('Kiosk/Index', [
            'classrooms' => Classroom::forYear()
                ->withCount('students')
                ->orderBy('grade_level')
                ->orderBy('name')
                ->get(['id', 'name', 'grade_level']),
            'todayVisits' => $this->todayVisits(),
            'todaySummary' => [
                'total' => LibraryVisit::whereDate('visit_date', Carbon::today())->count(),
                'purpose' => LibraryVisit::whereDate('visit_date', Carbon::today())
                    ->selectRaw('purpose, count(*) as total')
                    ->groupBy('purpose')
                    ->pluck('total', 'purpose'),
            ],
            'books' => Book::orderBy('title')->get(['id', 'title', 'code', 'book_type']),
        ]);
    }

    /**
     * Daftar siswa untuk pemilih nama di kios (JSON).
     */
    public function students(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'search' => ['nullable', 'string', 'max:60'],
        ]);

        $students = Student::query()
            ->with('classroom:id,name')
            ->where('is_active', true)
            ->when($validated['classroom_id'] ?? null, fn ($query, $classroomId) => $query->where('classroom_id', $classroomId))
            ->when($validated['search'] ?? null, function ($query, $search) {
                $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->limit(40)
            ->get(['id', 'name', 'nisn', 'classroom_id']);

        return response()->json([
            'data' => $students->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => $student->name,
                'nisn' => $student->nisn,
                'classroom' => $student->classroom?->name,
            ]),
        ]);
    }

    /**
     * Simpan satu kunjungan dari kios.
     */
    public function checkIn(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'book_id' => ['nullable', 'integer', 'exists:books,id'],
            'purpose' => ['required', Rule::in(['membaca', 'pinjam', 'tugas', 'lainnya'])],
            'notes' => ['nullable', 'string', 'max:150'],
        ]);

        $student = Student::with('classroom')->findOrFail($validated['student_id']);

        $alreadyCheckedIn = LibraryVisit::where('student_id', $student->id)
            ->whereDate('visit_date', Carbon::today())
            ->exists();

        $visit = LibraryVisit::create([
            'student_id' => $student->id,
            'classroom_id' => $student->classroom_id,
            'book_id' => $validated['book_id'] ?? null,
            'visit_date' => Carbon::today(),
            'arrival_time' => Carbon::now()->format('H:i:s'),
            'purpose' => $validated['purpose'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', sprintf(
            '%s (%s) tercatat hadir di perpustakaan.',
            $student->name,
            $student->classroom?->name
        ))->with('lastVisit', [
            'id' => $visit->id,
            'student' => $student->name,
            'classroom' => $student->classroom?->name,
            'time' => $visit->arrival_time,
            'purpose' => $visit->purpose,
            'duplicate' => $alreadyCheckedIn,
        ]);
    }

    /**
     * Batalkan kunjungan hari ini (salah pilih nama).
     */
    public function destroy(LibraryVisit $visit): RedirectResponse
    {
        if (! $visit->visit_date->isToday()) {
            return back()->with('error', 'Hanya kunjungan hari ini yang bisa dibatalkan dari kios.');
        }

        $visit->delete();

        return back()->with('success', 'Catatan kunjungan dibatalkan.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function todayVisits(): array
    {
        return LibraryVisit::with(['student:id,classroom_id', 'book:id,title'])
            ->whereDate('visit_date', Carbon::today())
            ->orderByDesc('arrival_time')
            ->limit(50)
            ->get()
            ->map(fn (LibraryVisit $visit) => [
                'id' => $visit->id,
                'student' => $visit->student?->name,
                'classroom' => $visit->effectiveClassroom()?->name,
                'book' => $visit->book?->title,
                'arrival_time' => substr((string) $visit->arrival_time, 0, 5),
                'purpose' => $visit->purpose,
            ])
            ->all();
    }
}
