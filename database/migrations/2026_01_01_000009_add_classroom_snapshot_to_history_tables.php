<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot kelas pada riwayat kunjungan & peminjaman.
 *
 * Dulu tabel ini hanya menunjuk ke student_id, sehingga laporan lintas
 * tahun akan mengelompokkan siswa ke kelas SEKARANG, bukan kelas waktu
 * dia datang. Sekarang kelas ikut tersimpan saat pencatatan.
 *
 * Data lama sengaja TIDAK diisi ulang: lebih baik "tidak diketahui"
 * daripada salah mengelompokkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_visits', function (Blueprint $table) {
            $table->foreignId('classroom_id')
                ->nullable()
                ->after('student_id')
                ->constrained()
                ->nullOnDelete();
            $table->index('classroom_id');
        });

        Schema::table('daily_loans', function (Blueprint $table) {
            $table->foreignId('classroom_id')
                ->nullable()
                ->after('student_id')
                ->constrained()
                ->nullOnDelete();
            $table->index('classroom_id');
        });
    }

    public function down(): void
    {
        Schema::table('library_visits', function (Blueprint $table) {
            $table->dropForeign(['classroom_id']);
            $table->dropIndex(['classroom_id']);
            $table->dropColumn('classroom_id');
        });

        Schema::table('daily_loans', function (Blueprint $table) {
            $table->dropForeign(['classroom_id']);
            $table->dropIndex(['classroom_id']);
            $table->dropColumn('classroom_id');
        });
    }
};
