<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu eksemplar fisik dari sebuah judul buku.
 *
 * books menyimpan data pada level JUDUL, sedangkan tabel ini menyimpan satu
 * baris untuk tiap buku fisik yang benar-benar ada di rak. Pemisahan ini
 * penting karena barcode melekat pada fisiknya: tiga salinan "Dongeng" punya
 * judul dan ISBN yang sama, tetapi harus punya barcode berbeda agar sistem
 * tahu eksemplar mana yang sedang dipinjam atau hilang.
 */
class BookCopy extends Model
{
    use HasFactory;

    public const CONDITION_BAIK = 'baik';

    public const CONDITION_RUSAK = 'rusak';

    public const CONDITION_HILANG = 'hilang';

    protected $fillable = [
        'book_id',
        'accession_number',
        'barcode',
        'condition',
        'acquired_at',
        'note',
    ];

    protected $casts = [
        'acquired_at' => 'date',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Eksemplar yang masih layak dipinjam: kondisi baik dan tidak sedang
     * dipinjam lewat peminjaman koleksi harian.
     *
     * Buku paket sengaja tidak ikut dihitung: paket dan koleksi memakai
     * book_type yang berbeda, sehingga satu eksemplar tidak pernah muncul
     * di kedua alur sekaligus.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        $onLoan = DailyLoan::query()
            ->select('daily_loans.book_copy_id')
            ->whereColumn('daily_loans.book_copy_id', 'book_copies.id')
            ->where('daily_loans.status', DailyLoan::STATUS_DIPINJAM);

        return $query
            ->where('condition', self::CONDITION_BAIK)
            ->whereNotIn('id', $onLoan);
    }

    public function scopeWithBarcode(Builder $query): Builder
    {
        return $query->whereNotNull('barcode')->where('barcode', '!=', '');
    }

    public function isAvailable(): bool
    {
        return $this->condition === self::CONDITION_BAIK
            && ! $this->dailyLoans()->where('status', DailyLoan::STATUS_DIPINJAM)->exists();
    }

    public function dailyLoans(): HasMany
    {
        return $this->hasMany(DailyLoan::class);
    }

    /**
     * Nomor urut terakhir yang sudah terpakai, contoh 42.
     * Dipakai BookCopyService supaya penomoran tidak bentrok.
     */
    public static function lastSequence(): int
    {
        $last = static::query()
            ->where('accession_number', 'like', 'REG/%')
            ->orderByDesc('accession_number')
            ->value('accession_number');

        return $last ? (int) substr($last, 4) : 0;
    }

    /**
     * Nomor registrasi berikutnya, dipakai saat menambah eksemplar baru.
     * Format: REG/00042
     */
    public static function nextAccessionNumber(): string
    {
        return sprintf('REG/%05d', static::lastSequence() + 1);
    }
}