<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'isbn',
        'title',
        'author',
        'publisher',
        'published_year',
        'category_id',
        'book_type',
        'grade_level',
        'shelf_location',
        'funding_source',
        'total_copies',
        'available_copies',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_year' => 'integer',
            'grade_level' => 'integer',
            'total_copies' => 'integer',
            'available_copies' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(LibraryVisit::class);
    }

    public function dailyLoans(): HasMany
    {
        return $this->hasMany(DailyLoan::class);
    }

    public function packageLoans(): HasMany
    {
        return $this->hasMany(PackageLoan::class);
    }

    /**
     * Eksemplar fisik milik judul ini.
     */
    public function copies(): HasMany
    {
        return $this->hasMany(BookCopy::class);
    }

    /**
     * Pencarian memuat ISBN dan barcode eksemplar, bukan hanya kolom buku.
     *
     * Petugas bisa mengetik apa pun yang ada di tangannya: kode internal
     * (BK-0001), ISBN tercetak di sampul, barcode stiker, atau judul.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term) {
            $query->where('title', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('isbn', 'like', "%{$term}%")
                ->orWhere('author', 'like', "%{$term}%")
                ->orWhere('publisher', 'like', "%{$term}%")
                ->orWhereHas('copies', fn (Builder $copy) => $copy->where('barcode', $term));
        });
    }

    public function scopeType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('book_type', $type) : $query;
    }
}
