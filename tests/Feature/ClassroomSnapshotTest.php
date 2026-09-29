<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Classroom;
use App\Models\LibraryVisit;
use App\Models\Student;
use App\Services\LibraryVisitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kelas harus ikut tersimpan (snapshot) saat kunjungan/peminjaman dicatat,
 * supaya laporan lintas tahun tidak mengelompokkan siswa ke kelas sekarang.
 */
class ClassroomSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private function seedData(): array
    {
        $classroomLama = Classroom::create([
            'name' => 'Kelas 1',
            'grade_level' => 1,
            'academic_year' => '2025/2026',
        ]);

        $classroomBaru = Classroom::create([
            'name' => 'Kelas 1',
            'grade_level' => 1,
            'academic_year' => '2026/2027',
        ]);

        $student = Student::create([
            'classroom_id' => $classroomLama->id,
            'nisn' => '1111111111',
            'name' => 'Ani',
            'gender' => 'P',
            'is_active' => true,
        ]);

        $category = Category::create(['code' => 'FIK', 'name' => 'Fiksi', 'color' => 'amber']);

        $book = Book::create([
            'code' => 'BK-0001',
            'title' => 'Cerita Pendek',
            'category_id' => $category->id,
            'book_type' => 'koleksi',
            'total_copies' => 5,
            'available_copies' => 5,
        ]);

        return [$classroomLama, $classroomBaru, $student, $book];
    }

    public function test_kunjungan_menyimpan_kelas_saat_dicatat(): void
    {
        [, , $student] = $this->seedData();

        $result = app(LibraryVisitService::class)->record($student, 'membaca', null);

        $visit = LibraryVisit::find($result['visit']->id);

        $this->assertSame($student->classroom_id, $visit->classroom_id);
        $this->assertSame('Kelas 1', $visit->classroom->name);
        $this->assertSame('Kelas 1', $visit->effectiveClassroom()->name);
    }

    public function test_kelas_tidak_ikut_saat_siswa_naik_kelas(): void
    {
        [$lama, $baru, $student, $book] = $this->seedData();

        $result = app(LibraryVisitService::class)->record($student, 'membaca', null);
        $visit = LibraryVisit::find($result['visit']->id);

        // Siswa naik kelas ke tahun ajaran berikutnya.
        $student->update(['classroom_id' => $baru->id]);

        $visit->refresh();

        // Laporan harus tetap memakai kelas saat kunjungan dicatat.
        $this->assertSame($lama->id, $visit->classroom_id);
        $this->assertSame('Kelas 1', $visit->effectiveClassroom()->name);

        $loan = app(LibraryVisitService::class)->record($student, 'pinjam', $book);
        $this->assertSame($baru->id, $loan['loan']->classroom_id);
    }

    public function test_peminjaman_menyimpan_kelas_saat_dicatat(): void
    {
        [, , $student, $book] = $this->seedData();

        $result = app(LibraryVisitService::class)->record($student, 'pinjam', $book);

        $this->assertSame($student->classroom_id, $result['loan']->classroom_id);
    }
}
