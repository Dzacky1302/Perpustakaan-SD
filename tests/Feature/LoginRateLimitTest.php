<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Pembatasan percobaan login.
 *
 * Dua lapis yang diuji terpisah:
 *   1. middleware throttle di route — menahan request sebelum controller jalan
 *   2. RateLimiter di LoginRequest — per email+IP dan per IP
 *
 * Lapis kedua penting karena kunci per email saja bisa dilewati dengan
 * mengganti email pada setiap percobaan.
 */
class LoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'admin@perpus-sd.test';

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('login-ip:127.0.0.1');
        RateLimiter::clear('login:'.self::EMAIL.'|127.0.0.1');

        User::factory()->create([
            'role'  => 'admin',
            'email' => self::EMAIL,
        ]);
    }

    private function attempt(string $email, string $password = 'salah')
    {
        return $this->post('/login', [
            'email'    => $email,
            'password' => $password,
        ]);
    }

    private function errorMessage(): string
    {
        return (string) session('errors')->first('email');
    }

    public function test_percobaan_gagal_ditolak_setelah_mencapai_batas(): void
    {
        $max = (int) config('perpustakaan.login.max_attempts');

        for ($i = 1; $i <= $max; $i++) {
            $this->attempt(self::EMAIL)->assertSessionHasErrors('email');

            $this->assertStringNotContainsString(
                'detik',
                $this->errorMessage(),
                "Percobaan {$i} masih di bawah batas, jadi belum boleh dikunci.",
            );
        }

        $this->attempt(self::EMAIL)->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'detik',
            $this->errorMessage(),
            'Percobaan berikutnya harus diminta menunggu, bukan sekadar "kredensial salah".',
        );
    }

    public function test_ganti_email_tiap_percobaan_tidak_meloloskan_batas_per_ip(): void
    {
        config()->set('perpustakaan.login.max_attempts', 100);
        config()->set('perpustakaan.login.max_attempts_per_ip', 3);

        for ($i = 1; $i <= 3; $i++) {
            $this->attempt("lain-{$i}@contoh.test")->assertSessionHasErrors('email');

            $this->assertStringNotContainsString('detik', $this->errorMessage());
        }

        $this->attempt('tetap-sama@contoh.test');

        $this->assertStringContainsString(
            'detik',
            $this->errorMessage(),
            'Batas per IP harus tetap berlaku walau email tiap percobaan diganti.',
        );
    }

    public function test_login_sah_berhasil_dan_menghapus_hitungan(): void
    {
        $this->attempt(self::EMAIL)->assertSessionHasErrors('email');

        $this->post('/login', [
            'email'    => self::EMAIL,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();

        $this->assertSame(
            0,
            RateLimiter::attempts('login:'.self::EMAIL.'|127.0.0.1'),
            'Login berhasil harus menghapus hitungan agar akun sah tidak terkunci.',
        );
    }

    public function test_batas_per_akun_terpisah_antar_email(): void
    {
        config()->set('perpustakaan.login.max_attempts', 3);
        config()->set('perpustakaan.login.max_attempts_per_ip', 100);

        User::factory()->create(['role' => 'kepsek', 'email' => 'kepsek@perpus-sd.test']);

        for ($i = 1; $i <= 3; $i++) {
            $this->attempt(self::EMAIL);
        }

        $this->attempt(self::EMAIL);

        $this->assertStringContainsString(
            'detik',
            $this->errorMessage(),
            'Akun yang sudah melewati batas harus terkunci.',
        );

        // Akun lain dari IP yang sama belum boleh ikut terkunci.
        $this->post('/login', [
            'email'    => 'kepsek@perpus-sd.test',
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_route_login_punya_middleware_throttle(): void
    {
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn ($r) => $r->uri() === 'login' && in_array('POST', $r->methods(), true));

        $this->assertNotNull($route, 'Route POST /login tidak ditemukan.');

        // gatherMiddleware() mengembalikan middleware apa adanya (alias ikut
        // membawa parameternya), mis. "throttle:20,1".
        $middleware = $route->gatherMiddleware();

        $this->assertTrue(
            (bool) preg_grep('#^throttle#', $middleware),
            'Route POST /login harus memakai middleware throttle. Yang terdeteksi: '.implode(', ', $middleware),
        );
    }
}