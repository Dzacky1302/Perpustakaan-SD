<?php

namespace App\Services;

use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ODS\Reader as OdsReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use RuntimeException;

/**
 * Baca dan tulis file Excel (.xlsx/.ods/.csv) memakai OpenSpout
 * agar laporan bisa langsung dibuka di Microsoft Excel / LibreOffice.
 */
class SpreadsheetService
{
    /**
     * Baca seluruh baris dari file spreadsheet yang diunggah.
     *
     * @return array<int, array<int, string>>
     */
    public function read(string $path, ?string $extension = null): array
    {
        $extension = strtolower($extension ?: Str::lower(pathinfo($path, PATHINFO_EXTENSION)));

        $reader = match ($extension) {
            'xlsx' => new XlsxReader(),
            'ods' => new OdsReader(),
            'csv', 'txt' => new CsvReader(),
            default => throw new RuntimeException("Format berkas .{$extension} belum didukung."),
        };

        if ($reader instanceof CsvReader) {
            $reader->setFieldDelimiter($this->detectDelimiter($path));
        }

        $rows = [];

        $reader->open($path);

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = array_map(
                    fn ($cell) => trim((string) $cell),
                    $row->toArray()
                );
            }

            break; // hanya sheet pertama
        }

        $reader->close();

        return $rows;
    }

    /**
     * Buat file .xlsx sementara lalu kembalikan path-nya untuk diunduh.
     *
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, string|int|float|null>>  $rows
     */
    public function build(string $filename, array $headings, iterable $rows): string
    {
        $directory = storage_path('app/temp');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $path = $directory.DIRECTORY_SEPARATOR.$filename;

        $writer = new XlsxWriter();
        $writer->setCreator('PustakaSD');
        $writer->openToFile($path);

        $writer->addRow(Row::fromValues($headings));

        foreach ($rows as $row) {
            $values = array_map(
                fn ($value) => $value ?? '',
                array_values($row)
            );

            $writer->addRow(Row::fromValues($values));
        }

        $writer->close();

        return $path;
    }

    /**
     * Tebak pemisah kolom CSV (Excel Indonesia sering memakai titik koma).
     */
    private function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return ',';
        }

        $firstLine = (string) fgets($handle);
        fclose($handle);

        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }
}
