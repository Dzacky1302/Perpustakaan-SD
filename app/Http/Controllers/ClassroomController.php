<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    public function index(Request $request): Response
    {
        $selectedYear = $request->query('academic_year') ?: Classroom::activeYear();

        return Inertia::render('Master/Classrooms', [
            'classrooms' => Classroom::forYear($selectedYear)
                ->withCount('students')
                ->orderBy('grade_level')
                ->orderBy('name')
                ->get()
                ->map(fn (Classroom $classroom) => [
                    'id' => $classroom->id,
                    'name' => $classroom->name,
                    'grade_level' => $classroom->grade_level,
                    'academic_year' => $classroom->academic_year,
                    'homeroom_teacher' => $classroom->homeroom_teacher,
                    'students_count' => $classroom->students_count,
                ]),
            'academicYears' => Classroom::query()
                ->distinct()
                ->orderByDesc('academic_year')
                ->pluck('academic_year'),
            'selectedYear' => $selectedYear,
            'activeYear' => Classroom::activeYear(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Classroom::create($request->validate($this->rules()));

        return back()->with('success', 'Kelas baru berhasil ditambahkan.');
    }

    public function update(Request $request, Classroom $classroom): RedirectResponse
    {
        $classroom->update($request->validate($this->rules($classroom)));

        return back()->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(Classroom $classroom): RedirectResponse
    {
        if ($classroom->students()->exists()) {
            return back()->with('error', 'Kelas masih memiliki siswa. Pindahkan siswanya terlebih dahulu.');
        }

        $classroom->delete();

        return back()->with('success', 'Kelas berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?Classroom $classroom = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:10',
                Rule::unique('classrooms', 'name')
                    ->where(fn ($query) => $query->where('academic_year', request('academic_year')))
                    ->ignore($classroom?->id),
            ],
            'grade_level' => ['required', 'integer', 'between:1,6'],
            'academic_year' => ['required', 'string', 'max:9', 'regex:/^\d{4}\/\d{4}$/'],
            'homeroom_teacher' => ['nullable', 'string', 'max:100'],
        ];
    }
}
