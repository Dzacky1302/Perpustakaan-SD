<?php

namespace App\Console\Commands;

use App\Services\FineService;
use Illuminate\Console\Command;

/**
 * Hitung ulang nominal denda pada peminjaman yang sudah selesai.
 *
 * Dipakai setelah aturan denda diubah di .env, supaya angka lama ikut
 * disesuaikan. Denda yang sudah dibayar tidak disentuh.
 */
class RecalculateFinesCommand extends Command
{
    protected $signature = 'perpustakaan:recalc-denda
                            {--dry-run : Tampilkan hasilnya tanpa menyimpan}';

    protected $description = 'Hitung ulang nominal denda keterlambatan pada peminjaman yang sudah selesai';

    public function handle(FineService $fines): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun
            ? 'Menghitung ulang denda (mode Dry Run, tidak ada data yang diubah)...'
            : 'Menghitung ulang nominal denda...');

        $updated = $dryRun ? 0 : $fines->recalculateAll();

        $this->newLine();
        $this->table(
            ['Keterangan', 'Nilai'],
            [
                ['Peminjaman diproses', $updated],
                ['Tarif per hari', $fines->format($fines->dailyRate())],
                ['Batas per buku', $fines->format($fines->maxPerBook())],
                ['Denda belum dibayar', $fines->format($fines->totalUnpaid())],
                ['Denda sudah diterima', $fines->format($fines->totalCollected())],
            ]
        );

        if ($dryRun) {
            $this->comment('Mode Dry Run: tidak ada data yang disimpan.');
        }

        return self::SUCCESS;
    }
}