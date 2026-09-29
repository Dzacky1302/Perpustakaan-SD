<?php

namespace App\Services;

use App\Models\DailyLoan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Nomor slip cetak untuk surat peminjaman, surat pengembalian,
 * dan kuitansi pembayaran denda.
 *
 * Format: SP/2026/03/0007  (prefix/tahun/bulan/urut hari ini)
 * Nomor dihitung dari jumlah slip yang sudah keluar pada hari yang
 * sama, jadi tetap urut dan searchable di arsip kertas.
 */
class SlipService
{
    /**
     * Nomor slip peminjaman, contoh SP/2026/03/0007.
     */
    public function loanNumber(?Carbon $date = null): string
    {
        return $this->next(
            (string) config('perpustakaan.slip.loan_prefix', 'SP'),
            'slip_number',
            $date
        );
    }

    /**
     * Nomor slip pengembalian, contoh SK/2026/03/0002.
     */
    public function returnNumber(?Carbon $date = null): string
    {
        return $this->next(
            (string) config('perpustakaan.slip.return_prefix', 'SK'),
            'return_slip_number',
            $date
        );
    }

    /**
     * Pastikan satu peminjaman punya nomor slip. Dipanggil sekali
     * saat baris peminjaman dibuat supaya slip bisa dicetak ulang
     * kapan saja tanpa nomor berubah.
     */
    public function ensureLoanNumber(DailyLoan $loan): DailyLoan
    {
        if (! $loan->slip_number) {
            $loan->forceFill([
                'slip_number' => $this->loanNumber($loan->borrowed_at),
            ])->save();
        }

        return $loan;
    }

    /**
     * Pastikan satu pengembalian punya nomor slip.
     */
    public function ensureReturnNumber(DailyLoan $loan, ?Carbon $returnedAt = null): DailyLoan
    {
        if (! $loan->return_slip_number) {
            $loan->forceFill([
                'return_slip_number' => $this->returnNumber($returnedAt ?? $loan->returned_at),
            ])->save();
        }

        return $loan;
    }

    /**
     * Nomor urut berikutnya untuk prefix + kolom tertentu.
     */
    private function next(string $prefix, string $column, ?Carbon $date = null): string
    {
        $date ??= Carbon::today();

        $base = sprintf('%s/%s', $prefix, $date->format('Y/m'));

        $used = DailyLoan::where($column, 'like', "{$base}/%")
            ->pluck($column)
            ->map(fn (string $value) => (int) Str::afterLast($value, '/'))
            ->filter()
            ->max() ?? 0;

        return sprintf('%s/%04d', $base, $used + 1);
    }
}