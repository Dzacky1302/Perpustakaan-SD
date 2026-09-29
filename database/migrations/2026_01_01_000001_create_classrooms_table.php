<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rombel / kelas di sekolah dasar, contoh: 1A, 4B.
     */
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // 1A, 4B
            $table->unsignedTinyInteger('grade_level');   // 1 - 6
            $table->string('academic_year');              // 2025/2026
            $table->string('homeroom_teacher')->nullable();
            $table->timestamps();

            $table->unique(['name', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
