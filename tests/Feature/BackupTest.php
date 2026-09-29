<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Classroom;
use App\Models\DailyLoan;
use App\Models\Student;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Cadangan database: pembuatan berkas, rotasi, dan pembatasan akses.
 *
 * Sengaja memakai DatabaseMigrations, bukan RefreshDatabase: SQLite
 * tidak bisa menjalankan VACUUM di dalam transaction, sedangkan
 * RefreshDatabase membungkus setiap test dengan satu transaction.
 */
class BackupTest extends TestCase
{
    use DatabaseMigrations;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('app/testing-backups');

        File::deleteDirectory($this->directory);
        File::makeDirectory($this->directory, 0755, true);

        config()->set('perpustakaan.backup.path', $this->directory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_perintah_backup_membuat_berkas_sqlite(): void
    {
        $this->artisan('perpustakaan:backup')->assertSuccessful();

        $files = File::files($this->directory);

        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.sqlite', $files[0]->getFilename());
        $this->assertGreaterThan(0, $files[0]->getSize());
    }

    public function test_perintah_backup_menampilkan_daftar(): void
    {
        $this->artisan('perpustakaan:backup')->assertSuccessful();

        $this->artisan('perpustakaan:backup --list')
            ->expectsOutputToContain('Total: 1 berkas')
            ->assertSuccessful();
    }

    public function test_rotasi_menyisakan_terbaru(): void
    {
        config()->set('perpustakaan.backup.keep', 3);

        $service = app(BackupService::class);

        // Buat 5 berkas dengan nama berurutan.
        foreach (range(1, 5) as $index) {
            File::put($this->directory.DIRECTORY_SEPARATOR."perpustakaan-2026010$index-000000.sqlite", 'x');
        }

        $deleted = $service->prune();

        $this->assertCount(2, $deleted);
        $this->assertCount(3, $service->all());
    }

    public function test_kepsek_tidak_boleh_membuat_backup(): void
    {
        $kepsek = User::factory()->create(['role' => 'kepsek']);

        $this->actingAs($kepsek)
            ->post('/backups')
            ->assertRedirect();

        $this->assertCount(0, File::files($this->directory));
    }

    public function test_kepsek_tidak_boleh_menghapus_backup(): void
    {
        File::put($this->directory.DIRECTORY_SEPARATOR.'perpustakaan-20260101-000000.sqlite', 'x');

        $kepsek = User::factory()->create(['role' => 'kepsek']);

        $this->actingAs($kepsek)
            ->delete('/backups/perpustakaan-20260101-000000.sqlite')
            ->assertRedirect();

        $this->assertFileExists($this->directory.DIRECTORY_SEPARATOR.'perpustakaan-20260101-000000.sqlite');
    }

    public function test_halaman_backup_bisa_dibuka_kepsek(): void
    {
        $kepsek = User::factory()->create(['role' => 'kepsek']);

        $this->actingAs($kepsek)->get('/backups')->assertOk();
    }

    public function test_path_traversal_ditolak(): void
    {
        $service = app(BackupService::class);

        // basename() membuang semua segmen folder, jadi berkas tetap
        // berada di dalam folder backup dan tidak bisa keluar dari sana.
        $this->assertSame(
            $this->directory.DIRECTORY_SEPARATOR.'database.sqlite',
            $service->safePath('../../database/database.sqlite'),
        );
    }

    public function test_berkas_bukan_sqlite_ditolak(): void
    {
        $this->expectException(\RuntimeException::class);

        app(BackupService::class)->safePath('sewa.php');
    }
}