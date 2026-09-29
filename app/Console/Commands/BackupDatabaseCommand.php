<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Cadangan otomatis database perpustakaan.
 *
 * Contoh:
 *   php artisan perpustakaan:backup
 *   php artisan perpustakaan:backup --keep=30
 *   php artisan perpustakaan:backup --prune-only
 */
class BackupDatabaseCommand extends Command
{
    protected $signature = 'perpustakaan:backup
                            {--keep= : Jumlah file backup yang dipertahankan (default dari config)}
                            {--prune-only : Hanya jalankan rotasi tanpa membuat backup baru}
                            {--list : Tampilkan daftar backup lalu keluar}';

    protected $description = 'Buat cadangan database perpustakaan (SQLite VACUUM INTO) dengan rotasi otomatis';

    public function handle(BackupService $backups): int
    {
        if ($option = $this->option('list')) {
            $this->displayList($backups);

            return self::SUCCESS;
        }

        if ($this->option('prune-only')) {
            $deleted = $backups->prune();

            if ($deleted === []) {
                $this->info('Tidak ada backup lama yang perlu dihapus.');
            } else {
                foreach ($deleted as $filename) {
                    $this->line("  - dihapus: {$filename}");
                }

                $this->info(count($deleted).' backup lama dihapus.');
            }

            return self::SUCCESS;
        }

        $this->info('Mencadangkan database perpustakaan...');

        try {
            $result = $backups->create();
        } catch (Throwable $exception) {
            $this->error('Backup gagal: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(
            ['Keterangan', 'Nilai'],
            [
                ['Berkas', $result['filename']],
                ['Lokasi', dirname($result['path'])],
                ['Ukuran', number_format($result['size'] / 1024, 1).' KB'],
                ['Total file tersimpan', $backups->count().' / '.$this->resolveKeep($backups)],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Tampilkan daftar backup yang ada.
     */
    private function displayList(BackupService $backups): void
    {
        $items = $backups->all();

        if ($items === []) {
            $this->warn('Belum ada backup tersimpan. Jalankan: php artisan perpustakaan:backup');

            return;
        }

        $this->table(
            ['#', 'Berkas', 'Dibuat', 'Usia', 'Ukuran'],
            array_map(fn (array $item, int $index) => [
                $index + 1,
                $item['filename'],
                $item['created_label'],
                $item['age_days'].' hari lalu',
                $item['size_label'],
            ], $items, array_keys($items))
        );

        $this->info('Total: '.$backups->count().' berkas, '.round($backups->totalSize() / 1024 / 1024, 2).' MB');
    }

    private function resolveKeep(BackupService $backups): int
    {
        $option = $this->option('keep');

        if ($option !== null) {
            return max(1, (int) $option);
        }

        return $backups->keepCount();
    }
}