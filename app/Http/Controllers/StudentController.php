<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Student;
use App\Services\SpreadsheetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function __construct(private readonly SpreadsheetService $spreadsheet)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only('search', 'classroom_id');

        $students = Student::query()
            ->whereHas('classroom', fn ($query) => $query->forYear())
            ->with('classroom:id,name,grade_level')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%"));
            })
            ->when($filters['classroom_id'] ?? null, fn ($query, $classroomId) => $query->where('classroom_id', $classroomId))
            ->ordered()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Student $student) => [
                'id' => $student->id,
                'nisn' => $student->nisn,
                'name' => $student->name,
                'gender' => $student->gender,
                'is_active' => $student->is_active,
                'classroom_id' => $student->classroom_id,
                'classroom' => $student->classroom?->name,
                'active_loans' => $student->dailyLoans()->where('status', 'dipinjam')->count(),
                'package_loans' => $student->packageLoans()->where('status', 'dipinjam')->count(),
            ]);

        return Inertia::render('Master/Students', [
            'students' => $students,
            'classrooms' => Classroom::forYear()
                ->orderBy('grade_level')
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'classroom_id' => $filters['classroom_id'] ?? '',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Student::create($request->validate($this->rules()));

        return back()->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $student->update($request->validate($this->rules($student)));

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        if ($student->packageLoans()->where('status', 'dipinjam')->exists()) {
            return back()->with('error', 'Siswa masih memegang buku paket. Tarik buku paketnya terlebih dahulu.');
        }

        $student->delete();

        return back()->with('success', 'Data siswa berhasil dihapus.');
    }

    /**
     * Impor daftar siswa dari berkas Excel/CSV.
     */
    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,ods,txt', 'max:4096'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
        ]);

        $uploaded = $validated['file'];
        $rows = $this->spreadsheet->read($uploaded->getRealPath(), $uploaded->getClientOriginalExtension());
        $classrooms = Classroom::forYear()->pluck('id', 'name');

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $classrooms, $validated, &$imported, &$updated, &$skipped) {
            foreach ($rows as $index => $row) {
                if ($index === 0) {
                    continue; // baris judul
                }

                [$nisn, $name, $gender, $classroomName] = array_pad($row, 4, null);

                if (blank($nisn) && blank($name)) {
                    continue;
                }

                $classroomId = $validated['classroom_id'] ?? $classrooms[$classroomName] ?? null;

                if (! $classroomId) {
                    $skipped++;

                    continue;
                }

                $payload = [
                    'classroom_id' => $classroomId,
                    'name' => $name ?: 'Tanpa Nama',
                    'gender' => strtoupper((string) $gender) === 'P' ? 'P' : 'L',
                    'is_active' => true,
                ];

                $student = Student::where('nisn', (string) $nisn)->first();

                if ($student) {
                    $student->update($payload);
                    $updated++;
                } else {
                    Student::create($payload + ['nisn' => (string) $nisn]);
                    $imported++;
                }
            }
        });

        $message = "Impor selesai: {$imported} siswa baru, {$updated} siswa diperbarui.";

        if ($skipped > 0) {
            $message .= " {$skipped} baris dilewati karena nama kelas tidak dikenali.";
        }

        return back()->with($skipped === 0 ? 'success' : 'error', $message);
    }

    /**
     * Unduh template impor siswa (.xlsx).
     */
    public function template()
    {
        $path = $this->spreadsheet->build('template-import-siswa.xlsx', ['NISN', 'Nama Lengkap', 'L/P', 'Kelas'], [
            ['1234567890', 'Adi Pratama', 'L', '1A'],
            ['1234567891', 'Ayu Lestari', 'P', '1A'],
        ]);

        return response()->download($path, 'template-import-siswa.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?Student $student = null): array
    {
        return [
            'nisn' => [
                'required',
                'string',
                'max:20',
                Rule::unique('students', 'nisn')->ignore($student?->id),
            ],
            'name' => ['required', 'string', 'max:80'],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'is_active' => ['boolean'],
        ];
    }
}
