<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'grade_level',
        'academic_year',
        'homeroom_teacher',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
        ];
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function packageLoans(): HasMany
    {
        return $this->hasMany(PackageLoan::class);
    }

    /**
     * Kunjungan pada kelas ini (snapshot kelas saat dicatat).
     */
    public function libraryVisits(): HasMany
    {
        return $this->hasMany(LibraryVisit::class);
    }

    /**
     * Tahun ajaran yang sedang berjalan, contoh: "2025/2026".
     */
    public static function activeYear(): string
    {
        return (string) config('perpustakaan.current_academic_year');
    }

    /**
     * Tahun ajaran berikutnya, contoh: "2025/2026" -> "2026/2027".
     */
    public static function nextYear(?string $year = null): ?string
    {
        $year ??= self::activeYear();

        if (! preg_match('/^(\d{4})\/(\d{4})$/', $year, $matches)) {
            return null;
        }

        $start = (int) $matches[1];
        $end = (int) $matches[2];

        return ($start + 1).'/'.($end + 1);
    }

    /**
     * Batasi query ke satu tahun ajaran.
     */
    public function scopeForYear(Builder $query, ?string $year = null): Builder
    {
        return $query->where('academic_year', $year ?: self::activeYear());
    }
}
