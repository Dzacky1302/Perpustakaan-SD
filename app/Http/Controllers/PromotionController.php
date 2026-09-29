<?php

namespace App\Http\Controllers;

use App\Services\ClassPromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Halaman kenaikan kelas: sekali klik, seluruh siswa naik satu tingkat
 * dan set kelas baru dibuat untuk tahun ajaran berikutnya.
 */
class PromotionController extends Controller
{
    public function __construct(private readonly ClassPromotionService $promotion) {}

    public function index(Request $request): Response
    {
        $targetYear = $request->query('to');

        return Inertia::render('Promotion/Index', [
            'plan' => $this->promotion->preview($targetYear),
            'graduated' => \App\Models\Student::graduated()
                ->with('classroom:id,name,grade_level')
                ->orderByDesc('graduated_at')
                ->limit(50)
                ->get(['id', 'nisn', 'name', 'classroom_id', 'graduated_at'])
                ->map(fn ($student) => [
                    'id' => $student->id,
                    'nisn' => $student->nisn,
                    'name' => $student->name,
                    'classroom' => $student->classroom?->name,
                    'graduated_at' => $student->graduated_at?->toDateString(),
                ])
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'to' => ['nullable', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'confirm' => ['accepted'],
        ]);

        try {
            $result = $this->promotion->promote($validated['to'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with(
            'success',
            sprintf(
                'Kenaikan kelas %s → %s selesai. %d kelas dibuat, %d siswa naik kelas, %d siswa lulus.',
                $result['source_year'],
                $result['target_year'],
                $result['classrooms_created'],
                $result['students_moved'],
                $result['students_graduated'],
            )
        );
    }
}
