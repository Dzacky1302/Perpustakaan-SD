<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Level akses petugas perpustakaan.
 *
 * admin : pustakawan, boleh menambah/mengubah/menghapus data.
 * kepsek : kepala sekolah, hanya boleh melihat & mengunduh laporan.
 */
/**
 * Level akses petugas perpustakaan.
 *
 * admin : pustakawan, boleh menambah/mengubah/menghapus data.
 * kepsek : kepala sekolah, hanya boleh melihat & mengunduh laporan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('admin')->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
