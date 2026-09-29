<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'nisn',
        'name',
        'gender',
        'is_active',
        'graduated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'graduated_at' => 'date',
        ];
    }

    public function scopeGraduated(Builder $query): Builder
    {
        return $query->whereNotNull('graduated_at');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
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
     * Urutkan siswa sesuai tingkat kelas lalu nama.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->join('classrooms', 'classrooms.id', '=', 'students.classroom_id')
            ->orderBy('classrooms.grade_level')
            ->orderBy('classrooms.name')
            ->orderBy('students.name')
            ->select('students.*');
    }
}
