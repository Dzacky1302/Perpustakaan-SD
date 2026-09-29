<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori koleksi perpustakaan (Dewey sederhana + penanda warna rak).
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();        // 000, 300, 800, FIK
            $table->string('name');                      // Karya Umum, Ilmu Sosial, Fiksi Anak
            $table->string('color', 20)->default('sky'); // warna label rak
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
