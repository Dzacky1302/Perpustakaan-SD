<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kuitansi denda dalam dua bentuk, beserta log pencetakannya.
 *
 * fine_receipts menyimpan dokumennya, fine_receipt_prints menyimpan riwayat
 * setiap kali dokumen dicetak.
 *
 * Kenapa perlu log: di sekolah, orang tua kadang tidak percaya bahwa denda
 * itu ada, karena siswanya hilang atau merobek kuitansinya. Dengan setiap
 * pencetakan dicatat, petugas bisa menunjukkan bahwa dokumen tersebut benar
 * pernah keluar dari perpustakaan, lengkap dengan nama petugas dan waktunya.
 *
 * Nominal pada fine_receipts sengaja tidak bisa diubah setelah terbit: saat
 * kuitansi dibuat, nilainya disalin dari daily_loans.fine_amount yang memang
 * sudah dikunci pada saat buku dikembalikan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fine_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_loan_id')->constrained()->cascadeOnDelete();

            // tagihan = bukti utang (dibawa pulang), pelunasan = bukti bayar.
            $table->enum('type', ['tagihan', 'pelunasan']);

            // Diawali tahun/bulan agar urut dan mudah dicari di arsip kertas.
            $table->string('receipt_number', 30)->unique();

            $table->unsignedInteger('amount');

            // Disalin dari daily_loans saat kuitansi terbit, supaya isi kuitansi
            // tetap benar walau data lama berubah.
            $table->unsignedSmallInteger('days_late');
            $table->date('due_at')->nullable();
            $table->date('returned_at')->nullable();
            $table->unsignedInteger('rate_per_day');

            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('issued_at');

            // Kode dibaca petugas kepada orang tua untuk mengecek keaslian.
            // Hanya huruf dan angka tanpa karakter yang mudah tertukar
            // (0/O, 1/I) supaya enak dibaca dari kertas.
            $table->string('verification_code', 12)->unique();

            $table->timestamps();

            $table->index(['type', 'issued_at']);
            $table->index('daily_loan_id');
        });

        Schema::create('fine_receipt_prints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fine_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('printed_at');

            // 1 = salinan asli, 2 ke atas = cetak ulang. Cetakan kedua dan
            // seterusnya diberi watermark SALINAN.
            $table->unsignedSmallInteger('copy_number')->default(1);

            $table->timestamps();

            $table->index('fine_receipt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fine_receipt_prints');
        Schema::dropIfExists('fine_receipts');
    }
};