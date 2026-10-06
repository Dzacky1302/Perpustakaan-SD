<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses TULIS (tambah/ubah/hapus) hanya untuk pustakawan.
 *
 * Kepala sekolah tetap boleh membuka halaman & mengunduh laporan,
 * tetapi tidak dapat mengubah data.
 *
 * Satu pengecualian: halaman kios buku tamu adalah halaman input murni,
 * jadi GET-nya dijaga sendiri di KioskController (403 untuk kepsek).
 *
 * Diterapkan ke seluruh grup route petugas, jadi cukup satu tempat.
 */
class EnsureAdmin
{
    /**
     * Route yang tetap boleh diakses semua petugas, termasuk kepsek.
     */
    private const ALWAYS_ALLOWED = [
        'profile.update',
        'profile.destroy',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Membaca data (halaman & unduh laporan) terbuka untuk semua petugas.
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // Ubah profil & keluar tetap untuk semua pengguna.
        if (in_array((string) $request->route()?->getName(), self::ALWAYS_ALLOWED, true)) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && $user->isAdmin()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya pustakawan yang dapat mengubah data.',
            ], 403);
        }

        return back()->with('error', 'Akses ditolak. Hanya pustakawan yang dapat mengubah data.');
    }
}
