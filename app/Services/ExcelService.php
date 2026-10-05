<?php

declare(strict_types=1);

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

/**
 * Thin wrapper around phpoffice/phpspreadsheet for building a single-sheet
 * .xlsx export from a flat array of associative rows (exactly the shape
 * every report Model method in this codebase already returns).
 *
 * Dependency note: requires `composer install` to have fetched
 * phpoffice/phpspreadsheet (declared in composer.json). This project's
 * development sandbox has no network access to Packagist, so this class
 * has been statically reviewed against PhpSpreadsheet's documented public
 * API (Spreadsheet::getActiveSheet / fromArray / Writer\Xlsx::save) but NOT
 * executed end to end in this environment -- mirrors the same documented
 * limitation already recorded on PdfService for dompdf. Verify on first
 * real deployment with:
 *   composer install
 *   php -r "require 'vendor/autoload.php'; $s=new PhpOffice\PhpSpreadsheet\Spreadsheet(); $s->getActiveSheet()->fromArray([['a','b'],[1,2]]); (new PhpOffice\PhpSpreadsheet\Writer\Xlsx($s))->save('test.xlsx');"
 */
final class ExcelService
{
    /**
     * @param string $title Sheet title (max 31 chars per the xlsx format; truncated if longer).
     * @param array<int, string> $headers Column headers, written as row 1.
     * @param array<int, array<int, scalar|null>> $rows Each inner array must be in the same column order as $headers.
     * @return string Raw .xlsx file bytes, ready to be sent as a download or email attachment.
     */
    public function buildWorkbook(string $title, array $headers, array $rows): string
    {
        if (!class_exists(Spreadsheet::class)) {
            throw new RuntimeException(
                'Excel export requires phpoffice/phpspreadsheet. Run "composer install" (see docs/INSTALL.md).'
            );
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($title, 0, 31));

        $sheet->fromArray($headers, null, 'A1');
        if ($rows !== []) {
            $sheet->fromArray($rows, null, 'A2');
        }

        // Bold the header row and auto-size columns so the exported file is
        // readable without the recipient manually reformatting it first.
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);
        foreach (range('A', $lastColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        $tmpPath = tempnam(sys_get_temp_dir(), 'xlsx_');
        if ($tmpPath === false) {
            throw new RuntimeException('Unable to allocate a temporary file for the Excel export.');
        }

        try {
            $writer->save($tmpPath);
            $bytes = file_get_contents($tmpPath);
            if ($bytes === false) {
                throw new RuntimeException('Failed to read back the generated Excel file.');
            }
            return $bytes;
        } finally {
            @unlink($tmpPath);
            $spreadsheet->disconnectWorksheets();
        }
    }
}
