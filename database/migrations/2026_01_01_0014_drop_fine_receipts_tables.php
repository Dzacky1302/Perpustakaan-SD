<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur kuitansi denda dihapus total: arsip, verifikasi kode, dan riwayat
 * cetaknya sudah tidak dipakai lagi, jadi tabelnya ikut dibuang supaya tidak
 * menyimpan data yang tidak pernah dibaca aplikasi.
 *
 * Migrasi ini tetap dibuat (bukan menghapus migrasi pembuatnya) karena
 * database lama sudah pernah menjalankan pembuatan tabelnya. Di database
 * baru urutannya jelas: dibuat, lalu dibuang.
 *
 * Kuitansi sudah berupa arsip kertas di sekolah, jadi data di tabel ini
 * bukan satu-satunya bukti. Sebelum dijalankan di produksi, jalankan backup
 * dulu (php artisan backup:run atau salin file SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('fine_receipt_prints');
        Schema::dropIfExists('fine_receipts');
    }

    public function down(): void
    {
        // Tidak bisa dikembalikan: struktur tabelnya ada di migrasi pembuat.
        // Jalankan ulang `migrate` dari commit sebelumnya bila benar-benar perlu.
    }
};
