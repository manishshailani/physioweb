<?php

declare(strict_types=1);

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use RuntimeException;

/**
 * Exports tabular report data to .xlsx (via phpoffice/phpspreadsheet) or
 * .csv (built-in, no dependency needed).
 *
 * Dependency note: .xlsx export requires `composer install` to have
 * fetched phpoffice/phpspreadsheet. As with PdfService, this class is
 * written against PhpSpreadsheet's documented API (Spreadsheet,
 * getActiveSheet()->fromArray(), Xlsx writer) but has not been executed
 * in this sandbox (no Packagist network access here) — the .csv path has
 * no such dependency and IS exercised by tests/smoke_test_exports.php.
 *
 * @param array<int, array<string, scalar|null>> $rows
 */
final class ExcelExportService
{
    public function toCsv(array $headers, array $rows): string
    {
        $fh = fopen('php://temp', 'r+');
        if ($fh === false) {
            throw new RuntimeException('Could not open a temporary stream for CSV export.');
        }
        fputcsv($fh, $headers);
        foreach ($rows as $row) {
            fputcsv($fh, $row);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $csv === false ? '' : $csv;
    }

    public function toXlsx(array $headers, array $rows, string $sheetTitle = 'Report'): string
    {
        if (!class_exists(Spreadsheet::class)) {
            throw new RuntimeException(
                'Excel export requires phpoffice/phpspreadsheet. Run "composer install" (see docs/INSTALL.md).'
            );
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($sheetTitle, 0, 31));

        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tmpPath = tempnam(sys_get_temp_dir(), 'physioweb_xlsx_');
        if ($tmpPath === false) {
            throw new RuntimeException('Could not create a temporary file for the Excel export.');
        }

        try {
            $writer->save($tmpPath);
            $contents = file_get_contents($tmpPath);
            return $contents === false ? '' : $contents;
        } finally {
            @unlink($tmpPath);
        }
    }
}
