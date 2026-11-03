<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ganti cascadeOnDelete ke nullOnDelete
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('classroom_id')->nullOnDelete()->change();
        });

        // Tambah soft deletes
        Schema::table('students', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Kembalikan cascadeOnDelete
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('classroom_id')->cascadeOnDelete()->change();
        });
    }
};