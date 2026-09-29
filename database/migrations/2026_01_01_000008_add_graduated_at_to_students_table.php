<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membedakan "siswa tidak aktif" (pindah/keluar) dari "siswa lulus" (kelas 6).
 * dipakai saat proses kenaikan kelas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('graduated_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('graduated_at');
        });
    }
};
