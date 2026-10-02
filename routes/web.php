<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DailyLoanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\PackageLoanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'appName' => config('app.name'),
    ]);
})->name('home');

/*
|--------------------------------------------------------------------------
| Portal siswa mandiri (public, tanpa login)
|--------------------------------------------------------------------------
| Digitalisasi buku tamu: pilih kelas -> pilih nama -> pilih keperluan ->
| pilih buku. Tanggal dan jam dicatat otomatis oleh sistem.
*/
Route::get('/peminjaman', [StudentPortalController::class, 'index'])->name('peminjaman.index');
Route::get('/peminjaman/siswa', [StudentPortalController::class, 'students'])->name('peminjaman.students');
Route::post('/peminjaman', [StudentPortalController::class, 'store'])->name('peminjaman.store');

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Buku tamu digital (mode kios)
    |--------------------------------------------------------------------------
    */
    Route::get('/kios', [KioskController::class, 'index'])->name('kiosk.index');
    Route::get('/kios/siswa', [KioskController::class, 'students'])->name('kiosk.students');
    Route::post('/kios/kunjungan', [KioskController::class, 'checkIn'])->name('kiosk.check-in');
    Route::delete('/kios/kunjungan/{visit}', [KioskController::class, 'destroy'])->name('kiosk.destroy');

    /*
    |--------------------------------------------------------------------------
    | Data master: kelas, kategori, siswa, koleksi buku
    |--------------------------------------------------------------------------
    */
    Route::resource('classrooms', ClassroomController::class)->except(['create', 'show', 'edit']);

    /*
    |--------------------------------------------------------------------------
    | Kenaikan kelas (sekali klik, seluruh siswa naik satu tingkat)
    |--------------------------------------------------------------------------
    */
    Route::get('/kenaikan-kelas', [PromotionController::class, 'index'])->name('promotion.index');
    Route::post('/kenaikan-kelas', [PromotionController::class, 'store'])->name('promotion.store');
    Route::resource('categories', CategoryController::class)->except(['create', 'show', 'edit']);

    Route::get('/students/template', [StudentController::class, 'template'])->name('students.template');
    Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');
    Route::get('/students/{student}/bebas-pustaka', [ReportController::class, 'freeCertificate'])
        ->name('students.free-certificate');
    Route::resource('students', StudentController::class)->except(['create', 'show', 'edit']);

    Route::get('/books/export', [BookController::class, 'export'])->name('books.export');
    Route::post('/books/sync-stock', [BookController::class, 'syncStock'])->name('books.sync-stock');
    Route::resource('books', BookController::class)->except(['create', 'show', 'edit']);

    /*
    |--------------------------------------------------------------------------
    | Sirkulasi: peminjaman buku koleksi harian
    |--------------------------------------------------------------------------
    */
    Route::get('/loans/export', [DailyLoanController::class, 'export'])->name('loans.export');

    // Pencarian buku untuk kolom isian: barcode, ISBN, kode internal, atau judul.
    Route::get('/loans/cari-buku', [DailyLoanController::class, 'searchBooks'])->name('loans.search-books');

    Route::get('/loans/denda', [DailyLoanController::class, 'fines'])->name('loans.fines');
    Route::get('/loans/denda/excel', [DailyLoanController::class, 'finesExcel'])->name('loans.fines.excel');
    Route::patch('/loans/{dailyLoan}/denda', [DailyLoanController::class, 'payFine'])->name('loans.fine.pay');
    Route::delete('/loans/{dailyLoan}/denda', [DailyLoanController::class, 'cancelFine'])->name('loans.fine.cancel');
    Route::patch('/loans/{dailyLoan}/kembali', [DailyLoanController::class, 'returnBook'])->name('loans.return');
    Route::patch('/loans/{dailyLoan}/hilang', [DailyLoanController::class, 'lost'])->name('loans.lost');
    Route::get('/loans/{dailyLoan}/slip-pinjaman', [DailyLoanController::class, 'printSlip'])->name('loans.slip');
    Route::get('/loans/{dailyLoan}/slip-kembali', [DailyLoanController::class, 'printReturnSlip'])->name('loans.slip-return');
    Route::get('/loans/{dailyLoan}/slip-denda', [DailyLoanController::class, 'printFineSlip'])->name('loans.slip-fine');
    Route::resource('loans', DailyLoanController::class)
        ->except(['create', 'show', 'edit'])
        ->parameters(['loans' => 'dailyLoan']);

    /*
    |--------------------------------------------------------------------------
    | Buku paket: distribusi massal & checklist pengembalian
    |--------------------------------------------------------------------------
    */
    Route::post('/package-loans/distribusi', [PackageLoanController::class, 'distribute'])
        ->name('package-loans.distribute');
    Route::post('/package-loans/kembali', [PackageLoanController::class, 'returnBatch'])
        ->name('package-loans.return');
    Route::resource('package-loans', PackageLoanController::class)
        ->only(['index', 'destroy'])
        ->parameters(['package-loans' => 'packageLoan']);

    /*
    |--------------------------------------------------------------------------
    | Laporan PDF & Excel
    |--------------------------------------------------------------------------
    */
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/buku-tamu/pdf', [ReportController::class, 'visitsPdf'])->name('reports.visits.pdf');
    Route::get('/reports/buku-tamu/excel', [ReportController::class, 'visitsExcel'])->name('reports.visits.excel');
    Route::get('/reports/buku-paket/pdf', [ReportController::class, 'packageLoansPdf'])->name('reports.package-loans.pdf');
    Route::get('/reports/buku-paket/excel', [ReportController::class, 'packageLoansExcel'])->name('reports.package-loans.excel');
    Route::get('/reports/peminjaman/pdf', [ReportController::class, 'loansPdf'])->name('reports.loans.pdf');
    Route::get('/reports/denda/pdf', [ReportController::class, 'finesPdf'])->name('reports.fines.pdf');
    Route::get('/reports/denda/excel', [ReportController::class, 'finesExcel'])->name('reports.fines.excel');

    /*
    |--------------------------------------------------------------------------
    | Profil pengguna
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Cadangan database (backup)
    |--------------------------------------------------------------------------
    | Halaman & unduh: semua petugas (GET).
    | Buat / hapus / pulihkan: hanya pustakawan (dicek middleware admin).
    */
    Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
    Route::post('/backups/rotasi', [BackupController::class, 'prune'])->name('backups.prune');
    Route::get('/backups/{file}/unduh', [BackupController::class, 'download'])->name('backups.download');
    Route::post('/backups/{file}/pulihkan', [BackupController::class, 'restore'])->name('backups.restore');
    Route::delete('/backups/{file}', [BackupController::class, 'destroy'])->name('backups.destroy');
});

require __DIR__.'/auth.php';
