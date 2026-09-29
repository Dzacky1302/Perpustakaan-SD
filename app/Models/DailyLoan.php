<?php

namespace App\Models;

use App\Services\FineService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLoan extends Model
{
    use HasFactory;

    public const STATUS_DIPINJAM = 'dipinjam';

    public const STATUS_KEMBALI = 'kembali';

    public const STATUS_HILANG = 'hilang';

    protected $fillable = [
        'student_id',
        'classroom_id',
        'book_id',
        'borrowed_at',
        'due_at',
        'returned_at',
        'status',
        'fine_amount',
        'fine_days_late',
        'fine_paid_at',
        'fine_received_by',
        'fine_notes',
        'slip_number',
        'return_slip_number',
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
            'borrowed_at' => 'date',
            'due_at' => 'date',
            'returned_at' => 'date',
            'fine_paid_at' => 'date',
            'fine_amount' => 'integer',
            'fine_days_late' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Kelas pada saat peminjaman dicatat (snapshot).
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Kelas yang ditampilkan.
     *
     * Hanya memakai snapshot. Peminjaman lama yang tidak punya snapshot
     * sengaja dianggap "tidak diketahui" supaya laporan historis tidak
     * memakai kelas siswa saat ini yang bisa sudah berubah.
     */
    public function effectiveClassroom(): ?Classroom
    {
        return $this->classroom;
    }

    /**
     * Petugas yang menerima pembayaran denda.
     */
    public function fineReceiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fine_received_by');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DIPINJAM);
    }

    /**
     * Peminjaman yang sudah melewati batas pengembalian.
     *
     * Memakai hitungan HARI SEKOLAH, jadi buku yang jatuh tempo Jumat
     * belum dianggap terlambat sampai hari sekolah berikutnya terlewat.
     */
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_DIPINJAM
            && $this->daysLate() > 0;
    }

    /**
     * Denda yang sudah dibayar siswa?
     */
    public function fineIsPaid(): bool
    {
        return $this->fine_paid_at !== null;
    }

    /**
     * Berapa hari sekolah buku ini terlambat.
     *
     * Semua tempat (layar, kuitansi, laporan) memakai angka yang sama,
     * supaya tidak pernah berbeda antar halaman.
     */
    public function daysLate(): int
    {
        return app(FineService::class)->daysLate($this);
    }

    /**
     * Denda berjalan saat buku belum dikembalikan.
     *
     * Dihitung ulang tiap render supaya di dashboard selalu tampil
     * jumlah hari keterlambatan terbaru. Setelah buku kembali, nominal
     * dikunci di kolom fine_amount.
     */
    public function liveFine(): int
    {
        return app(FineService::class)->calculate($this);
    }

    /**
     * Format rupiah untuk tampilan.
     */
    public function fineLabel(): string
    {
        return 'Rp'.number_format($this->liveFine(), 0, ',', '.');
    }
}
