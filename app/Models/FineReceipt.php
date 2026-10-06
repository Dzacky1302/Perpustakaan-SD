<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu kuitansi denda.
 *
 * Ada dua jenis:
 *   tagihan   terbit saat buku kembali, bukti bahwa siswa punya utang
 *   pelunasan terbit saat pembayaran dicatat, bukti sudah terbayar
 *
 * Nominal, jumlah hari terlambat, tanggal jatuh tempo, dan tanggal kembali
 * DISALIN dari daily_loans ketika kuitansi dibuat. Salinan inilah yang membuat
 * isi kuitansi tidak ikut berubah walaupun data lamanya diganti.
 */
class FineReceipt extends Model
{
    use HasFactory;

    public const TYPE_TAGIHAN = 'tagihan';

    public const TYPE_PELUNASAN = 'pelunasan';

    protected $fillable = [
        'daily_loan_id',
        'type',
        'receipt_number',
        'amount',
        'days_late',
        'due_at',
        'returned_at',
        'rate_per_day',
        'issued_by',
        'issued_at',
        'verification_code',
    ];

    protected $casts = [
        'amount' => 'integer',
        'days_late' => 'integer',
        'rate_per_day' => 'integer',
        'due_at' => 'date',
        'returned_at' => 'date',
        'issued_at' => 'datetime',
    ];

    public function dailyLoan(): BelongsTo
    {
        return $this->belongsTo(DailyLoan::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function prints(): HasMany
    {
        return $this->hasMany(FineReceiptPrint::class);
    }

    public function scopeType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('type', $type) : $query;
    }

    public function isDuplicate(): bool
    {
        return $this->copyNumber() > 1;
    }

    /**
     * Nomor salinan yang sedang dicetak (1 untuk cetakan pertama).
     */
    public function copyNumber(): int
    {
        return $this->prints()->max('copy_number') + 1;
    }

    public function printCount(): int
    {
        return $this->prints()->count();
    }
}