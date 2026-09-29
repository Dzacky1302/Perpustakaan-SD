<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Denda keterlambatan pengembalian buku koleksi.
 *
 * Aturan sederhana dan tetap: Rp1.000 per hari keterlambatan,
 * maksimal Rp10.000 per buku. Nominal disimpan permanen pada saat
 * buku dikembalikan supaya angka historis tidak ikut berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_loans', function (Blueprint $table) {
            $table->unsignedInteger('fine_amount')->default(0)->after('status');
            $table->unsignedSmallInteger('fine_days_late')->default(0)->after('fine_amount');
            $table->date('fine_paid_at')->nullable()->after('fine_days_late');
            $table->foreignId('fine_received_by')->nullable()->after('fine_paid_at')
                ->constrained('users')->nullOnDelete();
            $table->text('fine_notes')->nullable()->after('fine_received_by');
            $table->string('slip_number', 30)->nullable()->unique()->after('fine_notes');
            $table->string('return_slip_number', 30)->nullable()->unique()->after('slip_number');

            $table->index(['fine_paid_at', 'fine_amount'], 'daily_loans_fine_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('daily_loans', function (Blueprint $table) {
            // Index dilepas lebih dulu: SQLite menolak menghapus kolom
            // whilst masih ada index yang menempel pada kolom tersebut.
            $table->dropUnique('daily_loans_slip_number_unique');
            $table->dropUnique('daily_loans_return_slip_number_unique');
            $table->dropIndex('daily_loans_fine_lookup');
            $table->dropConstrainedForeignId('fine_received_by');

            $table->dropColumn([
                'fine_amount',
                'fine_days_late',
                'fine_paid_at',
                'fine_notes',
                'slip_number',
                'return_slip_number',
            ]);
        });
    }
};