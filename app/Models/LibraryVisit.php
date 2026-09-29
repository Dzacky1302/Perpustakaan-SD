<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'classroom_id',
        'book_id',
        'visit_date',
        'arrival_time',
        'purpose',
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
            'visit_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Kelas pada saat kunjungan dicatat (snapshot).
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Kelas yang ditampilkan.
     *
     * Hanya memakai snapshot. Riwayat lama yang tidak punya snapshot
     * sengaja ditampilkan "tidak diketahui" supaya laporan historis
     * tidak memakai data kelas saat ini yang bisa menyesatkan.
     */
    public function effectiveClassroom(): ?Classroom
    {
        return $this->classroom;
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('visit_date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('visit_date', '<=', $to);
        }

        return $query;
    }
}
