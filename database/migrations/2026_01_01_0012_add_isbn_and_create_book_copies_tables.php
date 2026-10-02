<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ISBN, eksemplar fisik, dan pelacakan eksemplar pada peminjaman.
 *
 * books disimpan pada level JUDUL, sehingga satu baris bisa mewakili banyak
 * eksemplar (total_copies > 1). Barcode justru melekat pada fisiknya, bukan
 * pada judulnya: tiga salinan "Dongeng" harus punya tiga barcode berbeda,
 * kalau tidak sistem tidak bisa mencatat eksemplar mana yang sedang dipinjam
 * atau hilang.
 *
 * Kolom books.total_copies / available_copies tetap ada dan tetap dipakai
 * laporan, tetapi kini diperlakukan sebagai cache yang dihitung ulang dari
 * book_copies (lihat BookStockService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('isbn', 20)->nullable()->after('code');
            $table->index('isbn');
        });

        Schema::create('book_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            // Nomor registrasi: identitas untuk laporan inventaris aset BOS.
            $table->string('accession_number', 30)->unique();
            // Stiker barcode perpustakaan. Kosong untuk eksemplar yang belum
            // dilabeli petugas; peminjaman tetap bisa lewat pemilihan judul.
            $table->string('barcode', 20)->nullable()->unique();
            $table->enum('condition', ['baik', 'rusak', 'hilang'])->default('baik');
            $table->date('acquired_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['book_id', 'condition']);
        });

        Schema::table('daily_loans', function (Blueprint $table) {
            // Nullable: peminjaman lama tidak knows eksemplarnya, dan buku yang
            // barcode-nya belum diisi juga tidak punya kepastian eksemplar.
            $table->foreignId('book_copy_id')->nullable()->after('book_id')->constrained()->nullOnDelete();
        });

        $this->seedCopiesFromExistingData();
    }

    /**
     * Buat eksemplar untuk data yang sudah ada.
     *
     * Barcode sengaja TIDAK diisi di sini: nomor barcodefisik tidak bisa
     * ditebak, dan mengarang angka akan lebih berbahaya daripada membiarkan
     * kolom kosong sampai petugas melabelinya sungguhan.
     */
    protected function seedCopiesFromExistingData(): void
    {
        $now = now();
        $sequence = 0;

        foreach (DB::table('books')->orderBy('id')->get() as $book) {
            $copies = max(0, (int) $book->total_copies);

            for ($i = 1; $i <= $copies; $i++) {
                $sequence++;

                DB::table('book_copies')->insert([
                    'book_id' => $book->id,
                    'accession_number' => sprintf('REG/%05d', $sequence),
                    'barcode' => null,
                    'condition' => 'baik',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Urutan dibalik: kolom -> tabel -> kolom.
        Schema::table('daily_loans', function (Blueprint $table) {
            $table->dropForeign(['book_copy_id']);
            $table->dropColumn('book_copy_id');
        });

        Schema::dropIfExists('book_copies');

        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['isbn']);
            $table->dropColumn('isbn');
        });
    }
};