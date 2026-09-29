<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Kenaikan kelas: membuat set kelas untuk tahun ajaran baru lalu
 * memindahkan seluruh siswa. Siswa kelas 6 ditandai lulus.
 *
 * Semua riwayat (buku tamu, peminjaman, buku paket) tetap aman karena
 * tabel-tabel itu menunjuk ke student_id, bukan ke nama kelas.
 */
class ClassPromotionService
{
    public const MAX_GRADE = 6;

    /**
     * Ringkasan rencana kenaikan kelas tanpa mengubah apa pun.
     *
     * @return array<string, mixed>
     */
    public function preview(?string $targetYear = null): array
    {
        $sourceYear = Classroom::activeYear();
        $targetYear = $targetYear ?: Classroom::nextYear($sourceYear);

        if (! $targetYear) {
            throw new RuntimeException("Format tahun ajaran aktif tidak valid: {$sourceYear}");
        }

        $sourceClassrooms = Classroom::forYear($sourceYear)
            ->withCount('students')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        // Kelas tujuan adalah kelas dengan tingkat satu tingkat di atas.
        // Seluruh kelas tahun baru dibuat saat promote(), jadi kelas tujuan
        // selalu ada setelah proses dijalankan.
        $sourceByGrade = $sourceClassrooms->keyBy('grade_level');

        $rows = $sourceClassrooms->map(function (Classroom $classroom) use ($sourceByGrade) {
            $graduating = $classroom->grade_level >= self::MAX_GRADE;
            $next = $graduating ? null : $sourceByGrade->get($classroom->grade_level + 1);

            return [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'grade_level' => $classroom->grade_level,
                'homeroom_teacher' => $classroom->homeroom_teacher,
                'students' => $classroom->students_count,
                'action' => $graduating ? 'lulus' : 'naik',
                'target' => $graduating
                    ? 'Lulus / tidak naik kelas'
                    : ($next?->name ?? ('Kelas '.($classroom->grade_level + 1))),
                'target_exists' => ! $graduating,
            ];
        })->all();

        $moving = 0;
        $graduating = 0;

        foreach ($rows as $row) {
            if ($row['action'] === 'lulus') {
                $graduating += $row['students'];
            } else {
                $moving += $row['students'];
            }
        }

        return [
            'source_year' => $sourceYear,
            'target_year' => $targetYear,
            'rows' => $rows,
            'classrooms_to_create' => count($rows),
            'total_students' => array_sum(array_column($rows, 'students')),
            'moving' => $moving,
            'graduating' => $graduating,
            'unmapped' => 0,
            'already_done' => Classroom::forYear($targetYear)->exists(),
        ];
    }

    /**
     * Jalankan kenaikan kelas.
     *
     * @return array<string, mixed>
     */
    public function promote(?string $targetYear = null): array
    {
        $plan = $this->preview($targetYear);
        $targetYear = $plan['target_year'];

        if ($plan['already_done']) {
            throw new RuntimeException(
                "Kelas untuk tahun ajaran {$targetYear} sudah ada. Hapus dulu kelas tahun tersebut bila ingin mengulang."
            );
        }

        return DB::transaction(function () use ($plan, $targetYear) {
            $sourceClassrooms = Classroom::forYear($plan['source_year'])
                ->orderBy('grade_level')
                ->get();

            // 1. Buat semua kelas untuk tahun ajaran baru (menyalin wali kelas).
            $targetByGrade = [];

            foreach ($sourceClassrooms as $source) {
                $targetByGrade[$source->grade_level] = Classroom::create([
                    'name' => $source->name,
                    'grade_level' => $source->grade_level,
                    'academic_year' => $targetYear,
                    'homeroom_teacher' => $source->homeroom_teacher,
                ]);
            }

            $created = count($targetByGrade);
            $moved = 0;
            $graduated = 0;

            // 2. Pindahkan siswa naik satu tingkat.
            foreach ($sourceClassrooms as $source) {
                if ($source->grade_level >= self::MAX_GRADE) {
                    // Kelas 6: tandai lulus, tetap menunjuk ke kelas lamanya
                    // supaya riwayat lama tetap akurat.
                    $graduated += Student::where('classroom_id', $source->id)
                        ->whereNull('graduated_at')
                        ->update([
                            'is_active' => false,
                            'graduated_at' => Carbon::today(),
                        ]);

                    continue;
                }

                $target = $targetByGrade[$source->grade_level + 1] ?? null;

                if (! $target) {
                    continue;
                }

                $moved += Student::where('classroom_id', $source->id)
                    ->whereNull('graduated_at')
                    ->update(['classroom_id' => $target->id]);
            }

            return [
                'source_year' => $plan['source_year'],
                'target_year' => $targetYear,
                'classrooms_created' => $created,
                'students_moved' => $moved,
                'students_graduated' => $graduated,
            ];
        });
    }
}
