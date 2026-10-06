<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu peristiwa pencetakan kuitansi.
 *
 * Baris ini yang membuat "kuitansi ini benar-benar pernah keluar dari
 * perpustakaan" bisa dibuktikan, lengkap dengan petugas dan waktunya.
 */
class FineReceiptPrint extends Model
{
    protected $fillable = [
        'fine_receipt_id',
        'printed_by',
        'printed_at',
        'copy_number',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
        'copy_number' => 'integer',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(FineReceipt::class, 'fine_receipt_id');
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }
}