<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kepala sekolah (kepsek) hanya boleh membaca & mengunduh laporan.
 * Pustakawan (admin) boleh menambah, mengubah, dan menghapus data.
 */
class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function kepsek(): User
    {
        return User::factory()->create(['role' => 'kepsek']);
    }

    public function test_kepsek_bisa_membuka_halaman_dan_laporan(): void
    {
        $kepsek = $this->kepsek();

        $this->actingAs($kepsek)->get('/books')->assertOk();
        $this->actingAs($kepsek)->get('/students')->assertOk();
        $this->actingAs($kepsek)->get('/classrooms')->assertOk();
        $this->actingAs($kepsek)->get('/reports')->assertOk();
        $this->actingAs($kepsek)->get('/dashboard')->assertOk();
    }

    public function test_kepsek_ditolak_saat_menulis_data(): void
    {
        $kepsek = $this->kepsek();

        // Tambah kategori
        $this->actingAs($kepsek)
            ->post('/categories', [
                'name' => 'Uji',
                'code' => 'UJI',
                'color' => 'slate',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_kepsek_ditolak_kenaikan_kelas(): void
    {
        $kepsek = $this->kepsek();

        $this->actingAs($kepsek)
            ->post('/kenaikan-kelas', ['confirm' => true])
            ->assertRedirect();

        $this->assertDatabaseCount('classrooms', 0);
    }

    public function test_admin_boleh_menulis_data(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/categories', [
                'name' => 'Fiksi',
                'code' => 'FIK',
                'color' => 'amber',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_kepsek_tetap_bisa_ubah_profil_sendiri(): void
    {
        $kepsek = $this->kepsek();

        $this->actingAs($kepsek)
            ->patch('/profile', [
                'name' => 'Nama Baru',
                'email' => $kepsek->email,
            ])
            ->assertRedirect();

        $this->assertSame('Nama Baru', $kepsek->fresh()->name);
    }

    public function test_halaman_publik_tenantuh_login(): void
    {
        $this->get('/peminjaman')->assertOk();
    }
}
