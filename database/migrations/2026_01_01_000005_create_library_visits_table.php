<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buku tamu digital perpustakaan (pengganti buku tulis tangan).
     */
    public function up(): void
    {
        Schema::create('library_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->nullable()->constrained()->nullOnDelete();
            $table->date('visit_date');
            $table->time('arrival_time');
            $table->enum('purpose', ['membaca', 'pinjam', 'tugas', 'lainnya'])->default('membaca');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['visit_date', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_visits');
    }
};
