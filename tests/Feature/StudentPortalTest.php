<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\DailyLoan;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifikasi portal siswa: daftar nama tidak boleh membocorkan NISN,
 * dan nama yang dipilih harus cocok dengan NISN yang diketik.
 */
class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    private Classroom $classroom;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classroom = Classroom::create([
            'name' => 'Kelas 3',
            'grade_level' => 3,
            'academic_year' => '2025/2026',
        ]);

        $this->student = Student::create([
            'classroom_id' => $this->classroom->id,
            'nisn' => '3333333333',
            'name' => 'Sari',
            'gender' => 'P',
            'is_active' => true,
        ]);
    }

    public function test_daftar_siswa_tidak_membocorkan_nisn(): void
    {
        $response = $this->getJson('/peminjaman/siswa?classroom_id='.$this->classroom->id);

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Sari')
            ->assertJsonMissingPath('data.0.nisn');

        $this->assertArrayNotHasKey('nisn', $response->json('data.0'));
    }

    public function test_verifikasi_nisn_cocok_diterima(): void
    {
        $this->postJson('/peminjaman/verifikasi', [
            'student_name' => 'Sari',
            'nisn' => '3333333333',
        ])
            ->assertOk()
            ->assertJson(['valid' => true]);
    }

    public function test_verifikasi_nisn_tidak_cocok_ditolak(): void
    {
        $this->postJson('/peminjaman/verifikasi', [
            'student_name' => 'Sari',
            'nisn' => '9999999999',
        ])
            ->assertStatus(422)
            ->assertJson(['valid' => false]);
    }

    public function test_verifikasi_nisn_milik_siswa_lain_ditolak(): void
    {
        $other = Student::create([
            'classroom_id' => $this->classroom->id,
            'nisn' => '4444444444',
            'name' => 'Budi',
            'gender' => 'L',
            'is_active' => true,
        ]);

        // Nama Sari dengan NISN milik Budi tidak boleh lolos.
        $this->postJson('/peminjaman/verifikasi', [
            'student_name' => $this->student->name,
            'nisn' => $other->nisn,
        ])
            ->assertStatus(422)
            ->assertJson(['valid' => false]);
    }

    public function test_simpan_ditolak_saat_nama_dan_nisn_tidak_cocok(): void
    {
        $this->post('/peminjaman', [
            'student_name' => 'Sari',
            'nisn' => '9999999999',
            'purpose' => 'membaca',
        ])->assertSessionHasErrors('nisn');

        $this->assertDatabaseCount('library_visits', 0);
        $this->assertSame(0, DailyLoan::count());
    }

    public function test_verifikasi_ditolak_untuk_siswa_nonaktif(): void
    {
        $this->student->update(['is_active' => false]);

        $this->postJson('/peminjaman/verifikasi', [
            'student_name' => 'Sari',
            'nisn' => '3333333333',
        ])
            ->assertStatus(422)
            ->assertJson(['valid' => false]);
    }

    public function test_simpan_ditolak_untuk_siswa_nonaktif(): void
    {
        $this->student->update(['is_active' => false]);

        $this->post('/peminjaman', [
            'student_name' => 'Sari',
            'nisn' => '3333333333',
            'purpose' => 'membaca',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('library_visits', 0);
    }

    public function test_verifikasi_dibatasi_laju_per_ip(): void
    {
        // Batas di route: 60 per menit. Percobaan ke-61 harus sudah ditolak,
        // supaya NISN tidak bisa ditebak satu per satu.
        $payload = ['student_name' => 'Sari', 'nisn' => '3333333333'];

        for ($i = 0; $i < 60; $i++) {
            $this->postJson('/peminjaman/verifikasi', $payload);
        }

        $this->postJson('/peminjaman/verifikasi', $payload)->assertStatus(429);
    }
}
