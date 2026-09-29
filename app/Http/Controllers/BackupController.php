<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Halaman pengelolaan cadangan database.
 *
 * Hanya pustakawan (admin) yang boleh menjalankan / menghapus / memulihkan
 * backup, karena ketiganya menyentuh berkas database. Kepala sekolah
 * tetap bisa membuka halaman & mengunduh cadangan (GET).
 */
class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backups) {}

    public function index(): Response
    {
        return Inertia::render('System/Backups', [
            'backups' => $this->backups->all(),
            'settings' => [
                'keep' => $this->backups->keepCount(),
                'schedule' => config('perpustakaan.backup.schedule'),
                'time' => config('perpustakaan.backup.time'),
                'path' => $this->backups->directory(),
            ],
            'summary' => [
                'count' => $this->backups->count(),
                'total_size' => round($this->backups->totalSize() / 1024 / 1024, 2),
                'will_prune' => $this->backups->prunePreview(),
            ],
        ]);
    }

    /**
     * Buat backup baru sekarang juga.
     */
    public function store(): RedirectResponse
    {
        try {
            $result = $this->backups->create();
        } catch (Throwable $exception) {
            return back()->with('error', 'Backup gagal: '.$exception->getMessage());
        }

        return back()->with(
            'success',
            "Backup berhasil dibuat: {$result['filename']} (".round($result['size'] / 1024, 1).' KB).'
        );
    }

    /**
     * Unduh file backup.
     */
    public function download(string $file)
    {
        $path = $this->backups->safePath($file);

        if (! is_file($path)) {
            abort(404, 'Berkas backup tidak ditemukan.');
        }

        return response()->download($path, basename($path));
    }

    /**
     * Hapus satu file backup.
     */
    public function destroy(string $file): RedirectResponse
    {
        $filename = basename($file);

        if (! $this->backups->delete($filename)) {
            return back()->with('error', "Berkas backup {$filename} tidak ditemukan.");
        }

        return back()->with('success', "Backup {$filename} berhasil dihapus.");
    }

    /**
     * Jalankan rotasi sekarang (hapus yang melebihi batas).
     */
    public function prune(): RedirectResponse
    {
        $deleted = $this->backups->prune();

        if ($deleted === []) {
            return back()->with('info', 'Semua backup masih di bawah batas jumlah, tidak ada yang dihapus.');
        }

        return back()->with('success', count($deleted).' backup lama berhasil dihapus.');
    }

    /**
     * Pulihkan database dari file backup.
     *
     * File database yang sekarang dicadangkan dulu ke folder
     * "sebelum-pulihkan" supaya mistakes masih bisa dikembalikan.
     */
    public function restore(Request $request, string $file): RedirectResponse
    {
        $validated = $request->validate([
            'confirm' => ['required', 'accepted'],
        ], [
            'confirm.required' => 'Centang konfirmasi dulu sebelum memulihkan database.',
            'confirm.accepted' => 'Centang konfirmasi dulu sebelum memulihkan database.',
        ]);

        $path = $this->backups->safePath($file);

        if (! is_file($path)) {
            return back()->with('error', 'Berkas backup tidak ditemukan.');
        }

        try {
            Artisan::call('perpustakaan:backup');
        } catch (Throwable $exception) {
            // Backup pra-pulihkan gagal: batalkan, jangan sentuh data asli.
            return back()->with('error', 'Pemulihan dibatalkan karena backup pengaman gagal: '.$exception->getMessage());
        }

        $database = config('database.connections.sqlite.database');

        try {
            $copied = @copy($path, $database);

            if (! $copied) {
                return back()->with('error', 'Pemulihan gagal: database tidak dapat ditulis. Pastikan aplikasi sedang tidak dipakai.');
            }
        } catch (Throwable $exception) {
            return back()->with('error', 'Pemulihan gagal: '.$exception->getMessage());
        }

        return back()->with('success', "Database berhasil dipulihkan dari {$file}. Segera lakukan login ulang.");
    }
}