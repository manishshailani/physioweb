<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

/**
 * Thin wrapper around dompdf/dompdf for rendering HTML (produced by our
 * own View templates) to a PDF byte string.
 *
 * Dependency note: requires `composer install` to have fetched
 * dompdf/dompdf (declared in composer.json). This project's development
 * sandbox has no network access to Packagist, so this class has been
 * statically reviewed against dompdf's documented public API
 * (Dompdf::loadHtml / setPaper / render / output) but NOT executed end to
 * end in this environment. Verify on first real deployment with:
 *   composer install
 *   php -r "require 'vendor/autoload.php'; $d=new Dompdf\Dompdf(); $d->loadHtml('<h1>Test</h1>'); $d->render(); file_put_contents('test.pdf', $d->output());"
 */
final class PdfService
{
    public function renderHtmlToPdf(string $html, string $paper = 'A4', string $orientation = 'portrait'): string
    {
        if (!class_exists(Dompdf::class)) {
            throw new RuntimeException(
                'PDF generation requires dompdf/dompdf. Run "composer install" (see docs/INSTALL.md).'
            );
        }

        $options = new Options();
        $options->set('isRemoteEnabled', false); // never fetch remote resources into generated PDFs
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        return $dompdf->output();
    }
}
