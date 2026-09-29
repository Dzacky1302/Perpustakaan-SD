<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Cadangan (backup) database SQLite.
 *
 * Memakai perintah VACUUM INTO bawaan SQLite: hasilnya file .sqlite yang
 * sudah rapi & konsisten, aman dijalankan walau aplikasi sedang dipakai.
 * Tidak perlu menyalin file database yang sedang aktif secara manual.
 *
 * Rotasi: hanya N file terbaru (default 14) yang disimpan, sisanya dihapus.
 */
class BackupService
{
    public function directory(): string
    {
        $path = (string) config('perpustakaan.backup.path', storage_path('app/backups'));

        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        return $path;
    }

    public function keepCount(): int
    {
        return max(1, (int) config('perpustakaan.backup.keep', 14));
    }

    /**
     * Buat satu file backup baru.
     *
     * @return array{path: string, filename: string, size: int}
     */
    public function create(): array
    {
        $directory = $this->directory();
        $filename = 'perpustakaan-'.Carbon::now()->format('Ymd-His').'.sqlite';
        $target = $directory.DIRECTORY_SEPARATOR.$filename;

        if (File::exists($target)) {
            File::delete($target);
        }

        $source = $this->sourcePath();

        // SQLite tidak bisa menjalankan VACUUM di dalam transaction.
        // Ini muncul saat dipanggil dari test (RefreshDatabase) atau bila
        // ada proses lain yang sedang memakai transaction.
        if (DB::connection()->transactionLevel() > 0) {
            throw new RuntimeException(
                'Backup tidak bisa dijalankan di dalam transaction database. '
                .'Jalankan di luar transaction (misalnya lewat artisan atau cron).'
            );
        }

        // VACUUM INTO membuat salinan ringkas & konsisten tanpa mengunci
        // database sumber, jadi aman saat aplikasi sedang diakses.
        DB::statement('VACUUM INTO ?', [$target]);

        if (! File::exists($target)) {
            throw new RuntimeException('Backup gagal: berkas tidak terbentuk.');
        }

        $size = (int) File::size($target);

        $this->prune();

        return ['path' => $target, 'filename' => $filename, 'size' => $size];
    }

    /**
     * Daftar file backup terbaru (terbaru dulu).
     *
     * @return array<int, array{filename: string, path: string, size: int, size_label: string, created_at: string, age_days: int}>
     */
    public function all(): array
    {
        $files = File::files($this->directory());

        $items = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'sqlite') {
                continue;
            }

            $createdAt = Carbon::createFromTimestamp($file->getMTime());
            $size = (int) $file->getSize();

            $items[] = [
                'filename' => $file->getFilename(),
                'path' => $file->getPathname(),
                'size' => $size,
                'size_label' => $this->humanSize($size),
                'created_at' => $createdAt->toDateTimeString(),
                'created_label' => $createdAt->translatedFormat('d M Y H:i'),
                'age_days' => (int) $createdAt->diffInDays(Carbon::now()),
            ];
        }

        usort($items, fn ($a, $b) => strcmp($b['filename'], $a['filename']));

        return $items;
    }

    /**
     * Hapus file backup tertentu (hanya nama file yang aman).
     */
    public function delete(string $filename): bool
    {
        $path = $this->safePath($filename);

        if (! File::exists($path)) {
            return false;
        }

        return File::delete($path);
    }

    /**
     * Kembalikan daftar file yang akan dihapus saat rotasi berikutnya.
     *
     * @return array<int, string>
     */
    public function prunePreview(): array
    {
        $files = array_column($this->all(), 'filename');

        return array_slice($files, $this->keepCount());
    }

    /**
     * Jalankan rotasi: sisakan N file terbaru.
     *
     * @return array<int, string> nama file yang dihapus
     */
    public function prune(): array
    {
        $deleted = [];

        foreach ($this->prunePreview() as $filename) {
            if ($this->delete($filename)) {
                $deleted[] = $filename;
            }
        }

        return $deleted;
    }

    /**
     * Pastikan nama file aman & berada di dalam folder backup.
     * Mencegah path traversal dari parameter URL.
     */
    public function safePath(string $filename): string
    {
        $filename = basename($filename);

        if (! str_ends_with(strtolower($filename), '.sqlite')) {
            throw new RuntimeException('Berkas backup harus berakhiran .sqlite.');
        }

        return $this->directory().DIRECTORY_SEPARATOR.$filename;
    }

    /**
     * Jumlah file backup saat ini.
     */
    public function count(): int
    {
        return count($this->all());
    }

    public function totalSize(): int
    {
        return (int) array_sum(array_column($this->all(), 'size'));
    }

    private function humanSize(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024) {
                return round($bytes, 0).' '.$unit;
            }

            $bytes /= 1024;
        }

        return round($bytes, 1).' TB';
    }

    /**
     * Lokasi file database SQLite yang aktif.
     *
     * Saat pengujian memakai database in-memory (:memory:) tidak ada
     * file yang bisa disalin, tapi VACUUM INTO tetap bisa menulis ke
     * disk, jadi bernilai dikembalikan apa adanya.
     */
    private function sourcePath(): string
    {
        $database = config('database.connections.sqlite.database');

        if (! is_string($database) || $database === '') {
            throw new RuntimeException('Koneksi database SQLite belum dikonfigurasi.');
        }

        if ($database !== ':memory:' && ! File::exists($database)) {
            throw new RuntimeException('Database SQLite tidak ditemukan di: '.$database);
        }

        return $database;
    }
}