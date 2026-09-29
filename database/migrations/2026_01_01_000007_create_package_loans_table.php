<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Peminjaman buku paket (BOS) per siswa per tahun ajaran.
     */
    public function up(): void
    {
        Schema::create('package_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->restrictOnDelete();
            $table->string('academic_year');
            $table->date('given_at');
            $table->date('returned_at')->nullable();
            $table->enum('status', ['dipinjam', 'kembali', 'hilang'])->default('dipinjam');
            $table->string('return_condition')->nullable(); // baik, rusak ringan, rusak berat
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'book_id', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_loans');
    }
};
