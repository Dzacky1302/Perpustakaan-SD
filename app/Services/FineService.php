<?php

namespace App\Services;

use App\Models\DailyLoan;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Denda keterlambatan pengembalian buku koleksi.
 *
 * Seluruh angka & kalender diambil dari config/perpustakaan.php (key `fine`),
 * yang bisa dioverride lewat .env. Tidak ada nilai nominal yang ditulis di sini
 * supaya tidak melenceng saat aturan diubah.
 *
 * Nominal disimpan permanen di kolom fine_amount saat buku dikembalikan,
 * sehingga laporan tahun lalu tidak ikut berubah seiring waktu berjalan.
 */
class FineService
{
    public function dailyRate(): int
    {
        return (int) config('perpustakaan.fine.daily_rate', 500);
    }

    public function maxPerBook(): int
    {
        return (int) config('perpustakaan.fine.max_per_book', 10000);
    }

    public function blocksBorrowing(): bool
    {
        return (bool) config('perpustakaan.fine.block_borrowing', true);
    }

    public function skipWeekends(): bool
    {
        return (bool) config('perpustakaan.fine.skip_weekends', true);
    }

    public function countsJointLeave(): bool
    {
        return (bool) config('perpustakaan.fine.count_joint_leave', true);
    }

    /**
     * Semua tanggal yang bukan hari sekolah untuk sebuah tahun:
     * hari libur nasional + cuti bersama (bila dihitung).
     *
     * @return array<int, string> daftar tanggal YYYY-MM-DD
     */
    public function nonSchoolDays(?int $year = null): array
    {
        $year ??= (int) date('Y');

        $holidays = (array) config('perpustakaan.fine.holidays', []);

        $days = (array) ($holidays[$year] ?? []);

        if ($this->countsJointLeave()) {
            $jointLeaves = (array) config('perpustakaan.fine.joint_leaves', []);

            $days = array_merge($days, (array) ($jointLeaves[$year] ?? []));
        }

        return array_values(array_unique(array_map('strval', $days)));
    }

    /**
     * Apakah tanggal ini sekolah buka?
     *
     * False untuk Sabtu/Minggu dan hari libur nasional.
     */
    public function isSchoolDay(Carbon $date): bool
    {
        if ($this->skipWeekends() && $date->isWeekend()) {
            return false;
        }

        return ! in_array($date->toDateString(), $this->nonSchoolDays((int) $date->year), true);
    }

    /**
     * Berapa HARI SEKOLAH yang lewat di antara dua tanggal.
     *
     * Tanggal $from tidak dihitung sendiri, $to ikut dihitung. Jadi
     * jatuh tempo Jumat -> kembali Senin = 0 hari (tidak ada hari sekolah
     * yang terlewat), sedangkan kembali Selasa = 1 hari.
     */
    public function schoolDaysBetween(Carbon $from, Carbon $to): int
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        if ($end->lessThanOrEqualTo($start)) {
            return 0;
        }

        $days = 0;
        $cursor = $start->copy()->addDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            if ($this->isSchoolDay($cursor)) {
                $days++;
            }

            $cursor->addDay();
        }

        return $days;
    }

    /**
     * Tenggat efektif: tanggal jatuh tempo yang sudah digeser melewati
     * blok hari libur / akhir pekan yang langsung menyusulnya.
     *
     * Inilah yang membuat "jatuh tempo Jumat, kembali Senin" berdenda nol:
     * batasnya jadi hari Senin, bukan Jumat. Buku baru dianggap terlambat
     * setelah hari sekolah berikutnya benar-benar terlewat.
     */
    public function effectiveDeadline(Carbon $dueAt): Carbon
    {
        $deadline = $dueAt->copy()->startOfDay();
        $next = $deadline->copy()->addDay();

        // Kalau besoknya bukan hari sekolah, geser terus sampai ketemu
        // hari sekolah berikutnya. Batas 60 hari sebagai pengaman bila
        // daftar libur salah konfigurasi.
        if (! $this->isSchoolDay($next)) {
            for ($i = 0; $i < 60; $i++) {
                if ($this->isSchoolDay($next)) {
                    return $next->copy();
                }

                $next->addDay();
            }
        }

        return $deadline;
    }

    /**
     * Berapa HARI SEKOLAH buku ini terlambat, dihitung terhadap tanggal kembali.
     *
     * Hanya hari sekolah (Senin–Jumat, bukan libur) yang dihitung, dan
     * akhir pekan yang menempel pada tanggal jatuh tempo diperlakukan
     * sebagai hari bebas — jadi jatuh tempo Jumat lalu dikembalikan Senin
     * tidak incur denda sama sekali.
     *
     * Buku yang masih dipinjam memakai hari ini sebagai pembanding,
     * sehingga angka di layar ikut bertambah setiap hari sekolah.
     */
    public function daysLate(DailyLoan $loan, ?Carbon $returnedAt = null): int
    {
        if (! $loan->due_at) {
            return 0;
        }

        $reference = $returnedAt
            ?? ($loan->returned_at ?? ($loan->status === DailyLoan::STATUS_DIPINJAM ? Carbon::today() : null));

        if (! $reference) {
            return 0;
        }

        return $this->schoolDaysBetween($this->effectiveDeadline($loan->due_at), $reference);
    }

    /**
     * Denda untuk satu peminjaman.
     */
    public function calculate(DailyLoan $loan, ?Carbon $returnedAt = null): int
    {
        $days = $this->daysLate($loan, $returnedAt);

        if ($days === 0) {
            return 0;
        }

        return min($days * $this->dailyRate(), $this->maxPerBook());
    }

    /**
     * Simpan sisa denda di menit terakhir sebelum buku dinyatakan kembali.
     *
     * Dipanggil tepat saat pengembalian dicatat supaya angka yang
     * tersimpan = angka yang diterima dari siswa.
     */
    public function settle(DailyLoan $loan, ?Carbon $returnedAt = null): DailyLoan
    {
        $reference = $returnedAt ?? $loan->returned_at ?? Carbon::today();

        $loan->forceFill([
            'fine_amount' => $this->calculate($loan, $reference),
            'fine_days_late' => $this->daysLate($loan, $reference),
        ])->save();

        // Kuitansi tagihan terbit otomatis begitu utang dendanya terbentuk.
        // Siswa sering belum punya uang hari itu juga, jadi kuitansi inilah
        // yang dibawa pulang untuk ditagih ke orang tua.
        if ((int) $loan->fine_amount > 0) {
            app(FineReceiptService::class)->issueForFine($loan, null, $reference);
        }

        return $loan;
    }

    public function format(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }

    /**
     * Denda milik seorang siswa yang belum dibayar.
     */
    public function unpaidFor(Student $student): int
    {
        return (int) DailyLoan::where('student_id', $student->id)
            ->where('fine_amount', '>', 0)
            ->whereNull('fine_paid_at')
            ->sum('fine_amount');
    }

    public function hasUnpaidFine(Student $student): bool
    {
        if (! $this->blocksBorrowing()) {
            return false;
        }

        return $this->unpaidFor($student) > 0;
    }

    /**
     * Pesan yang tampil di portal siswa / kios saat borrowing diblokir.
     */
    public function blockMessage(Student $student): ?string
    {
        if (! $this->hasUnpaidFine($student)) {
            return null;
        }

        $amount = $this->unpaidFor($student);

        return "Ada denda yang belum dibayar sebesar {$this->format($amount)}. "
            .'Silakan melunasi di perpustakaan sebelum meminjaman buku lagi.';
    }

    /**
     * Tandai denda lunas.
     */
    public function markPaid(DailyLoan $loan, User $receiver, ?string $notes = null, ?Carbon $paidAt = null): DailyLoan
    {
        $paidOn = $paidAt ?? Carbon::today();

        $loan->forceFill([
            'fine_paid_at' => $paidOn,
            'fine_received_by' => $receiver->id,
            'fine_notes' => $notes,
        ])->save();

        // Kuitansi pelunasan terbit otomatis, sebagai bukti resmi bahwa
        // denda untuk peminjaman ini sudah dibayar.
        if ((int) $loan->fine_amount > 0) {
            app(FineReceiptService::class)->issueForPayment($loan, $receiver, $paidOn);
        }

        return $loan;
    }

    public function isPaid(DailyLoan $loan): bool
    {
        return $loan->fine_paid_at !== null;
    }

    /**
     * Denda yang sudah dibayar (untuk riwayat & laporan keuangan).
     *
     * @return Builder<DailyLoan>
     */
    public function paidQuery(): Builder
    {
        return DailyLoan::whereNotNull('fine_paid_at')
            ->where('fine_amount', '>', 0);
    }

    /**
     * Total seluruh denda yang belum dibayar di perpustakaan.
     */
    public function totalUnpaid(): int
    {
        return (int) DailyLoan::where('fine_amount', '>', 0)
            ->whereNull('fine_paid_at')
            ->sum('fine_amount');
    }

    /**
     * Total denda yang sudah masuk (diterima pustakawan).
     */
    public function totalCollected(): int
    {
        return (int) $this->paidQuery()->sum('fine_amount');
    }

    /**
     * Hitung ulang denda untuk semua buku yang sudah kembali.
     *
     * Dipakai ketika aturan diubah lewat .env supaya angka lama ikut
     * disesuaikan. Buku yang dendanya sudah dibayar tidak disentuh.
     */
    public function recalculateAll(): int
    {
        $updated = 0;

        DailyLoan::whereIn('status', [DailyLoan::STATUS_KEMBALI, DailyLoan::STATUS_HILANG])
            ->where('fine_paid_at', null)
            ->chunkById(200, function ($loans) use (&$updated) {
                foreach ($loans as $loan) {
                    $this->settle($loan);
                    $updated++;
                }
            });

        return $updated;
    }
}
