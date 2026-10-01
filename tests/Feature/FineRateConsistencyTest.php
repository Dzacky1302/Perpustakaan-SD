<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Penjaga konsistensi tarif denda antar layar.
 *
 * Latar belakang: tarif pernah tertulis menduplikasi di beberapa tempat —
 * fallback di controller, teks pada komponen React, bahkan komentar. Saat
 * aturan diubah dari Rp1.000 ke Rp500, salinan itu tertinggal sehingga
 * dashboard dan laporan bisa menampilkan nominal berbeda.
 *
 * Test di bawah sengaja mengubah konfigurasi lalu memastikan semua layar
 * ikut berubah. Kalau suatu saat ada angka tarif yang ditulis ulang secara
 * harfian di kode, test ini gagal — bukan Silent salah tampil.
 */
class FineRateConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_default_konfigurasi_sekolah(): void
    {
        config()->set('perpustakaan.fine.daily_rate', 500);
        config()->set('perpustakaan.fine.max_per_book', 10000);

        $this->assertSame(500, app(FineService::class)->dailyRate());
        $this->assertSame(10000, app(FineService::class)->maxPerBook());
    }

    public function test_halaman_laporan_ikut_konfigurasi(): void
    {
        config()->set('perpustakaan.fine.daily_rate', 750);
        config()->set('perpustakaan.fine.max_per_book', 20000);

        $this->actingAs($this->admin())
            ->get('/reports')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('fines.rate_label', 'Rp750')
                ->where('fines.max_label', 'Rp20.000')
            );
    }

    public function test_halaman_denda_ikut_konfigurasi(): void
    {
        config()->set('perpustakaan.fine.daily_rate', 750);
        config()->set('perpustakaan.fine.max_per_book', 20000);

        $this->actingAs($this->admin())
            ->get('/loans/denda')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('summary.rate', 'Rp750')
                ->where('summary.max', 'Rp20.000')
            );
    }

    /**
     * Angka Rp1.000 pernah tertinggal sebagai fallback di beberapa berkas.
     * Memindai sumber kode memastikan tidak ada tarif yang ditulis harfian
     * di luar config, sehingga test ini gagal kalau ada yang ditambahkan lagi.
     */
    public function test_tidak_ada_tarif_harfian_tersisa_di_kode(): void
    {
        $files = [
            app_path('Http/Controllers/ReportController.php'),
            app_path('Http/Controllers/DailyLoanController.php'),
            app_path('Services/FineService.php'),
            base_path('resources/js/Pages/Circulation/Fines.jsx'),
            base_path('resources/js/Pages/Reports/Index.jsx'),
        ];

        // Sengaja memakai assertSame + preg_match, bukan
        // assertDoesNotMatchRegularExpression: yang latter ikut mencetak seluruh
        // isi file ke pesan error, jadi output-nya jadi jauh lebih ribet.
        $pattern = '/Rp\s?1\.000|daily_rate[\'"]?\s*,\s*1000|dailyRate\(\)[^;]*1000/';

        foreach ($files as $file) {
            $source = file_get_contents($file);

            // Buang komentar: angka di dalam docblock bukan kode yang dijalankan.
            $code = preg_replace('#/\*.*?\*/#s', '', $source);
            $code = preg_replace('#//[^\n]*#', '', $code);

            $this->assertSame(
                0,
                preg_match($pattern, $code),
                "Tarif Rp1.000 masih tertanam di {$file} — ambil dari FineService, jangan tulis ulang.",
            );
        }
    }
}