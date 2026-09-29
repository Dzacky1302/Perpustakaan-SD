<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();              // BK-0001
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('publisher')->nullable();
            $table->unsignedSmallInteger('published_year')->nullable();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->enum('book_type', ['paket', 'koleksi'])->default('koleksi');
            $table->unsignedTinyInteger('grade_level')->nullable(); // target kelas untuk buku paket
            $table->string('shelf_location')->nullable();           // Rak A-1
            $table->string('funding_source')->nullable();           // BOS Reguler, Hibah
            $table->unsignedInteger('total_copies')->default(1);
            $table->unsignedInteger('available_copies')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
