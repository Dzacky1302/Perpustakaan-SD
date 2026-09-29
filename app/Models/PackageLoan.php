<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageLoan extends Model
{
    use HasFactory;

    public const STATUS_DIPINJAM = 'dipinjam';

    public const STATUS_KEMBALI = 'kembali';

    public const STATUS_HILANG = 'hilang';

    protected $fillable = [
        'student_id',
        'classroom_id',
        'book_id',
        'academic_year',
        'given_at',
        'returned_at',
        'status',
        'return_condition',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'given_at' => 'date',
            'returned_at' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DIPINJAM);
    }
}
