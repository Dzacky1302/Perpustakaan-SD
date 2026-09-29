<?php

namespace App\Services;

use App\Models\Book;
use App\Models\DailyLoan;
use App\Models\PackageLoan;

/**
 * Menjaga konsistensi kolom books.available_copies terhadap
 * peminjaman harian dan peminjaman buku paket yang masih berjalan.
 */
class BookStockService
{
    public function isAvailable(Book $book): bool
    {
        return $book->available_copies > 0;
    }

    public function decrease(Book $book): void
    {
        if ($book->available_copies > 0) {
            $book->decrement('available_copies');
            $book->refresh();
        }
    }

    public function increase(Book $book): void
    {
        if ($book->available_copies < $book->total_copies) {
            $book->increment('available_copies');
            $book->refresh();
        }
    }

    /**
     * Hitung ulang stok tersedia dari data peminjaman aktif.
     * Dipakai sebagai perbaikan data (sinkronisasi stok).
     */
    public function sync(Book $book): void
    {
        $activeDailyLoans = DailyLoan::where('book_id', $book->id)
            ->where('status', DailyLoan::STATUS_DIPINJAM)
            ->count();

        $activePackageLoans = PackageLoan::where('book_id', $book->id)
            ->where('status', PackageLoan::STATUS_DIPINJAM)
            ->count();

        $available = max(0, $book->total_copies - $activeDailyLoans - $activePackageLoans);

        $book->update(['available_copies' => $available]);
    }

    /**
     * Perbaiki stok seluruh koleksi (dipakai tombol "Sinkronkan Stok").
     */
    public function syncAll(): int
    {
        $books = Book::all();

        foreach ($books as $book) {
            $this->sync($book);
        }

        return $books->count();
    }
}
