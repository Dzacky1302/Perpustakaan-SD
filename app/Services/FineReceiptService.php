<?php

namespace App\Services;

use App\Models\DailyLoan;
use App\Models\FineReceipt;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Membuat dan mencatat kuitansi denda.
 *
 * Dua hal yang dijaga di sini:
 *
 *   1. Kuitansi terbit otomatis. Saat buku kembali, tagihan langsung dibuat.
 *      Saat pembayaran dicatat, kuitansi pelunasan langsung dibuat. Petugas
 *      tidak perlu Remember nomor apa pun.
 *
 *   2. Setiap pencetakan dicatat. Kalau kuitansi dicetak ulang, barisnya
 *      diberi nomor salinan dan dicetak dengan watermark SALINAN, jadi tidak
 *      bisa disamakan dengan salinan asli.
 */
class FineReceiptService
{
    // Huruf dan digit berikut sengaja tidak dipakai: O/0, I/1, S/5, B/8, Z/2.
    // Sisanya dipakai supaya kode enak dibaca dan diucapkan dari kertas.
    private const ALPHABET = 'ACDEFGHJKLMNPQRTUVWXY34679';

    /**
     * Buat kuitansi tagihan (bukti utang) untuk satu peminjaman.
     */
    public function issueForFine(DailyLoan $loan, ?User $issuedBy = null, ?Carbon $at = null): FineReceipt
    {
        return $this->issue($loan, FineReceipt::TYPE_TAGIHAN, $issuedBy, $at);
    }

    /**
     * Buat kuitansi pelunasan (bukti sudah bayar).
     */
    public function issueForPayment(DailyLoan $loan, User $issuedBy, ?Carbon $at = null): FineReceipt
    {
        return $this->issue($loan, FineReceipt::TYPE_PELUNASAN, $issuedBy, $at);
    }

    /**
     * Cari kuitansi yang sudah terbit untuk satu peminjaman dan jenis tertentu.
     *
     * Dipakai supaya kuitansi tidak dibuat dua kali ketika satu proses
     * dijalankan berulang, dan supaya peminjaman lama yang belum punya
     * kuitansi tetap bisa dilayani.
     */
    public function find(DailyLoan $loan, string $type): ?FineReceipt
    {
        return FineReceipt::where('daily_loan_id', $loan->id)
            ->where('type', $type)
            ->latest('id')
            ->first();
    }

    /**
     * Kuitansi yang boleh ditampilkan untuk satu peminjaman.
     *
     * Kalau dendanya sudah lunas, yang tampil adalah kuitansi pelunasan;
     * kalau belum, kuitansi tagihan. Inilah tombol "cetak kuitansi" tunggal
     * yang dipakai di antarmuka.
     */
    public function printableFor(DailyLoan $loan): ?FineReceipt
    {
        if ($loan->fineIsPaid()) {
            return $this->find($loan, FineReceipt::TYPE_PELUNASAN)
                ?? $this->find($loan, FineReceipt::TYPE_TAGIHAN);
        }

        return $this->find($loan, FineReceipt::TYPE_TAGIHAN);
    }

    /**
     * Catat bahwa sebuah kuitansi dicetak, dan kembalikan nomor salinannya.
     */
    public function recordPrint(FineReceipt $receipt, ?User $printedBy = null): int
    {
        $copyNumber = $receipt->copyNumber();

        $receipt->prints()->create([
            'printed_by' => $printedBy?->id,
            'printed_at' => Carbon::now(),
            'copy_number' => $copyNumber,
        ]);

        return $copyNumber;
    }

    /**
     * Cari kuitansi dari kode verifikasi yang ditulis di kertas.
     */
    public function findByCode(string $code): ?FineReceipt
    {
        // Besarkan dulu, baru buang spasi/tanda baca. Kalau urutannya dibalik,
        // huruf kecil ikut terhapus dan kode yang ditulis tangan jadi terpotong.
        $needle = preg_replace('/[^A-Z0-9]/', '', strtoupper($code)) ?? '';

        if ($needle === '') {
            return null;
        }

        return FineReceipt::with(['dailyLoan.student', 'dailyLoan.book', 'issuer', 'prints.printer'])
            ->where('verification_code', $needle)
            ->first();
    }

    // ------------------------------------------------------------------

    private function issue(DailyLoan $loan, string $type, ?User $issuedBy, ?Carbon $at): FineReceipt
    {
        $existing = $this->find($loan, $type);

        if ($existing) {
            return $existing;
        }

        $at ??= Carbon::now();
        $prefix = $type === FineReceipt::TYPE_PELUNASAN
            ? (string) config('perpustakaan.slip.paid_receipt_prefix', 'KP')
            : (string) config('perpustakaan.slip.bill_receipt_prefix', 'KT');

        return DB::transaction(function () use ($loan, $type, $issuedBy, $at, $prefix) {
            return FineReceipt::create([
                'daily_loan_id' => $loan->id,
                'type' => $type,
                'receipt_number' => $this->nextNumber($prefix, $at),
                'amount' => (int) $loan->fine_amount,
                'days_late' => (int) $loan->fine_days_late,
                'due_at' => $loan->due_at?->toDateString(),
                'returned_at' => ($loan->returned_at ?? Carbon::today())->toDateString(),
                'rate_per_day' => app(FineService::class)->dailyRate(),
                'issued_by' => $issuedBy?->id,
                'issued_at' => $at,
                'verification_code' => $this->newVerificationCode(),
            ]);
        });
    }

    /**
     * Nomor kuitansi berikutnya, contoh KT/2026/10/0007.
     */
    private function nextNumber(string $prefix, Carbon $date): string
    {
        $base = sprintf('%s/%s', $prefix, $date->format('Y/m'));

        $used = FineReceipt::where('receipt_number', 'like', "{$base}/%")
            ->pluck('receipt_number')
            ->map(fn (string $value) => (int) Str::afterLast($value, '/'))
            ->filter()
            ->max() ?? 0;

        return sprintf('%s/%04d', $base, $used + 1);
    }

    /**
     * Kode verifikasi 8 karakter, misalnya K7M4QX9P.
     *
     * Huruf dan digit yang sulit tertukar dipakai agar petugas bisa
     * membacanya dari kertas tanpa salah dengar.
     */
    private function newVerificationCode(): string
    {
        do {
            $code = '';

            for ($i = 0; $i < 8; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (FineReceipt::where('verification_code', $code)->exists());

        return $code;
    }
}