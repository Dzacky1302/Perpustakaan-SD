<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Support\Collection;

/**
 * Pencarian eksemplar untuk halaman peminjaman.
 *
 * Petugas tidak perlu tahu sedang mengetik apa. Satu kolom isian menerima:
 *
 *   - barcode stiker   -> eksemplar pasti, tidak ada tebakan
 *   - ISBN             -> judul ditemukan, eksemplar dipilih otomatis
 *   - kode internal    -> judul ditemukan
 *   - kata kunci judul -> judul ditemukan
 *
 * Semua jalur berakhir sama: menentukan judul dan, bila memungkinkan,
 * eksemplar yang benar-benar dipinjam.
 */
class BookCopyService
{
    public const MATCH_BARCODE = 'barcode';

    public const MATCH_ISBN = 'isbn';

    public const MATCH_CODE = 'kode';

    public const MATCH_TITLE = 'judul';

    /**
     * Bersihkan input: spasi dan tanda hubung ISBN tidak berpengaruh,
     * dan huruf scanner sering datang dalam huruf besar.
     */
    public function normalise(string $input): string
    {
        return mb_strtoupper(trim($input));
    }

    /**
     * Temukan buku berdasarkan satu masukan bebas.
     *
     * @return array{book: Book|null, copy: BookCopy|null, matched_by: string|null}
     */
    public function locate(string $input): array
    {
        $needle = $this->normalise($input);

        if ($needle === '') {
            return ['book' => null, 'copy' => null, 'matched_by' => null];
        }

        // 1. Barcode paling spesifik, dan satu-satunya yang tahu eksemplar.
        $copy = BookCopy::with('book')->where('barcode', $needle)->first();

        if ($copy?->book) {
            return ['book' => $copy->book, 'copy' => $copy, 'matched_by' => self::MATCH_BARCODE];
        }

        // 2. ISBN tercetak di sampul. Coba bentuk apa adanya (memakai indeks),
        // lalu bentuk tanpa tanda hubung, karena petugas bisa mengetik
        // "979-123-456-7-9" maupun "9791234567897".
        $book = Book::where('isbn', $needle)->first();

        if (! $book) {
            $digits = preg_replace('/[^0-9Xx]/', '', $needle);

            if (strlen($digits) === 10 || strlen($digits) === 13) {
                $book = Book::where('isbn', $digits)->first();
            }
        }

        if ($book) {
            return ['book' => $book, 'copy' => $this->allocate($book), 'matched_by' => self::MATCH_ISBN];
        }

        // 3. Kode internal perpustakaan.
        $book = Book::where('code', $needle)->first();

        if ($book) {
            return ['book' => $book, 'copy' => $this->allocate($book), 'matched_by' => self::MATCH_CODE];
        }

        // 4. Terakhir, pencarian bebas pada judul/penulis.
        $book = Book::search($input)->first();

        if ($book) {
            return ['book' => $book, 'copy' => $this->allocate($book), 'matched_by' => self::MATCH_TITLE];
        }

        return ['book' => null, 'copy' => null, 'matched_by' => null];
    }

    /**
     * Ambil eksemplar yang masih tersedia untuk sebuah judul.
     *
     * Memilih otomatis adalah keputusan sadar: petugas yang mengetik ISBN
     * hanya tahu judulnya, bukan nomor eksemplarnya. Eksemplar yang dipinjam
     * secara spesifik tetap bisa dipilih lewat barcode.
     */
    public function allocate(Book $book): ?BookCopy
    {
        return BookCopy::available()->where('book_id', $book->id)->orderBy('id')->first();
    }

    /**
     * Pastikan sebuah judul punya sejumlah eksemplar.
     *
     * Dipakai saat admin menambah buku (total_copies) dan saat menambah
     * eksemplar lewat katalog. Eksemplar yang melebihi total tidak dihapus
     * otomatis karena bisa sedang dipinjam.
     *
     * @return int jumlah eksemplar yang dibuat
     */
    public function ensureCopies(Book $book, int $target): int
    {
        $current = $book->copies()->count();
        $missing = $target - $current;

        if ($missing <= 0) {
            return 0;
        }

        // Nomor urut diambil SEKALI di luar loop. Kalau dipanggil di dalam
        // loop, setiap baris akan mendapat nomor yang sama karena belum ada
        // yang tersimpan untuk dibaca.
        $sequence = BookCopy::lastSequence();

        $rows = [];

        for ($i = 0; $i < $missing; $i++) {
            $sequence++;

            $rows[] = [
                'book_id' => $book->id,
                'accession_number' => sprintf('REG/%05d', $sequence),
                'barcode' => null,
                'condition' => BookCopy::CONDITION_BAIK,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        BookCopy::insert($rows);

        return $missing;
    }

    /**
     * Pencarian untuk dropdown cadangan saat barcode tidak terbaca.
     */
    public function search(string $term, int $limit = 10): Collection
    {
        return Book::query()
            ->with('category:id,name,color')
            ->search($term)
            ->orderBy('title')
            ->limit($limit)
            ->get();
    }
}