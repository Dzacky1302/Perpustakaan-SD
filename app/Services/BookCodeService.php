<?php

namespace App\Services;

/**
 * Validasi kode identitas buku.
 *
 * Dua kode yang sering tertukar:
 *
 *   ISBN      dicetak penerbit di sampul. Satu per JUDUL, jadi tiga salinan
 *             buku yang sama memiliki ISBN yang sama.
 *   Barcode   stiker milik perpustakaan. Satu per EKSEMPLAR fisik.
 *
 * Keduanya punya digit checksum, jadi salah ketik atau salah baca scanner
 * bisa dikenali lebih dulu sebelum disimpan.
 */
class BookCodeService
{
    /**
     * ISBN-10: 10 digit, digit terakhir boleh "X".
     * Checksum = jumlah (bobot 10..1) habis dibagi 11.
     */
    public function isValidIsbn10(string $isbn): bool
    {
        $digits = strtoupper(preg_replace('/[^0-9Xx]/', '', $isbn) ?? '');

        if (strlen($digits) !== 10) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 10; $i++) {
            $value = $digits[$i] === 'X' ? 10 : (int) $digits[$i];
            $sum += $value * (10 - $i);
        }

        return $sum % 11 === 0;
    }

    /**
     * ISBN-13 dan barcode EAN-13 berbagi rumus yang sama.
     * Checksum = jumlah (bobot 1,3,1,3,...) habis dibagi 10.
     */
    public function isValidEan13(string $code): bool
    {
        $digits = preg_replace('/\D/', '', $code) ?? '';

        if (strlen($digits) !== 13) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 13; $i++) {
            $sum += ((int) $digits[$i]) * ($i % 2 === 0 ? 1 : 3);
        }

        return $sum % 10 === 0;
    }

    /**
     * ISBN diterima dalam bentuk 10 maupun 13 digit.
     */
    public function isValidIsbn(string $isbn): bool
    {
        return $this->isValidIsbn10($isbn) || $this->isValidEan13($isbn);
    }

    /**
     * Digit checksum untuk EAN-13 dari 12 digit pertama.
     */
    public function ean13CheckDigit(string $twelve): string
    {
        $digits = preg_replace('/\D/', '', $twelve) ?? '';
        $sum = 0;

        for ($i = 0; $i < 12; $i++) {
            $sum += ((int) $digits[$i]) * ($i % 2 === 0 ? 1 : 3);
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }

    /**
     * Bangun ISBN-13 yang sah untuk data contoh.
     *
     * Prefiks 978 (ISBN) dipakai, bukan 899 (barcode produk), supaya jelas
     * bedanya: ISBN menunjuk judul, barcode menunjuk eksemplar.
     */
    public function generateIsbn(int $sequence): string
    {
        $body = '978'.str_pad((string) ($sequence % 10000000000), 9, '0', STR_PAD_LEFT);

        return substr($body, 0, 12).$this->ean13CheckDigit($body);
    }

    /**
     * Bangun barcode perpustakaan yang sah dari sebuah urutan.
     *
     * Prefiks 899 (prefiks GS1 untuk Indonesia) dipakai supaya barcode ini
     * jelas bukan EAN produk komersial. Hanya untuk data contoh; nomor barcode
     * sungguhan tetap milik sekolah.
     */
    public function generateBarcode(int $sequence): string
    {
        $body = '899'.str_pad((string) ($sequence % 10000000000), 9, '0', STR_PAD_LEFT);

        return substr($body, 0, 12).$this->ean13CheckDigit($body);
    }

    /**
     * Bentuk kanonik untuk disimpan: hanya digit, huruf X ikut jadi X.
     *
     * Disimpan tanpa tanda hubung supaya pencarian tidak terganggu oleh format.
     * Tanda hubung hanya dipakai saat ditampilkan.
     */
    public function normaliseIsbn(string $isbn): string
    {
        return strtoupper(preg_replace('/[^0-9Xx]/', '', $isbn) ?? '');
    }

    /**
     * Rapikan penulisan ISBN agar seragam di katalog.
     */
    public function formatIsbn(string $isbn): string
    {
        $digits = strtoupper(preg_replace('/[^0-9Xx]/', '', $isbn) ?? '');

        if (strlen($digits) === 13 && $this->isValidEan13($digits)) {
            return implode('-', [
                substr($digits, 0, 3),
                substr($digits, 3, 1),
                substr($digits, 4, 3),
                substr($digits, 7, 5),
                substr($digits, 12, 1),
            ]);
        }

        if (strlen($digits) === 10) {
            return implode('-', [
                substr($digits, 0, 1),
                substr($digits, 1, 4),
                substr($digits, 5, 4),
                substr($digits, 9, 1),
            ]);
        }

        return $digits;
    }
}