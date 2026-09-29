<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Cadangan otomatis database perpustakaan
|--------------------------------------------------------------------------
| Disadwalkan otomatis oleh Laravel (routes/console.php). Backup memakai
| perintah `perpustakaan:backup` yang memakai VACUUM INTO, jadi aman
| dijalankan walau sedang ada yang memakai aplikasi.
|
| Jadwal & lokasi bisa diubah di .env:
|   PERPUS_BACKUP_JADWAL=daily|hourly|weekly  (default daily)
|   PERPUS_BACKUP_WAKTU=23:00
|   PERPUS_BACKUP_KEEP=14   (jumlah file backup yang disimpan)
*/
$backupSchedule = env('PERPUS_BACKUP_JADWAL', 'daily');
$backupTime = env('PERPUS_BACKUP_WAKTU', '23:00');

match ($backupSchedule) {
    'hourly' => Schedule::command('perpustakaan:backup')->hourly()->withoutOverlapping(),
    'weekly' => Schedule::command('perpustakaan:backup')->weeklyOn(1, $backupTime)->withoutOverlapping(),
    'always' => Schedule::command('perpustakaan:backup')->everyMinute()->withoutOverlapping(),
    default => Schedule::command('perpustakaan:backup')->dailyAt($backupTime)->withoutOverlapping(),
};

