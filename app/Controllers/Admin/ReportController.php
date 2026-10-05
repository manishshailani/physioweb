<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\TreatmentPlan;
use App\Services\ExcelService;
use App\Services\PdfService;

/**
 * Four fixed report screens (patients, appointments, revenue, treatments),
 * each scoped to a [from, to] date range taken from the query string and
 * defaulting to the current calendar month, plus a shared export() action
 * that re-runs the same underlying Model query and streams it as .xlsx or
 * .pdf. Keeping the on-screen table and the export on the same Model
 * methods (App\Models\Patient::registeredBetween() etc.) guarantees the
 * downloaded file always matches what the admin is looking at -- there is
 * no separate "export query" that could silently drift from the report
 * query over time.
 */
final class ReportController extends Controller
{
    private const TYPES = ['patients', 'appointments', 'revenue', 'treatments'];
    private const FORMATS = ['xlsx', 'pdf'];

    public function patients(Request $request): void
    {
        $this->requireAdmin();
        [$from, $to] = $this->dateRange($request);

        $this->view('admin.reports.patients', [
            'page_title' => 'Patients Report',
            'from' => $from,
            'to' => $to,
            'newPatients' => Patient::registeredBetween($from, $to),
            'genderDistribution' => Patient::genderDistribution(),
            'ageGroupDistribution' => Patient::ageGroupDistribution(),
            'totalActive' => Patient::countActive(),
        ], 'layouts.admin');
    }

    public function appointments(Request $request): void
    {
        $this->requireAdmin();
        [$from, $to] = $this->dateRange($request);

        $this->view('admin.reports.appointments', [
            'page_title' => 'Appointments Report',
            'from' => $from,
            'to' => $to,
            'statusCounts' => Appointment::statusCountsBetween($from, $to),
            'byTherapist' => Appointment::countsByTherapistBetween($from, $to),
            'appointments' => Appointment::listBetween($from, $to),
        ], 'layouts.admin');
    }

    public function revenue(Request $request): void
    {
        $this->requireAdmin();
        [$from, $to] = $this->dateRange($request);

        $this->view('admin.reports.revenue', [
            'page_title' => 'Revenue Report',
            'from' => $from,
            'to' => $to,
            'totalRevenue' => Invoice::revenueBetween($from, $to),
            'modeBreakdown' => Invoice::paymentModeBreakdown($from, $to),
            'statusBreakdown' => Invoice::statusBreakdownBetween($from, $to),
            'monthlyTrend' => Invoice::monthlyRevenueBetween($from, $to),
            'payments' => Invoice::paymentsBetween($from, $to),
            'outstandingOverall' => Invoice::totalOutstanding(),
        ], 'layouts.admin');
    }

    public function treatments(Request $request): void
    {
        $this->requireAdmin();
        [$from, $to] = $this->dateRange($request);

        $this->view('admin.reports.treatments', [
            'page_title' => 'Treatments Report',
            'from' => $from,
            'to' => $to,
            'statusCounts' => TreatmentPlan::statusCountsBetween($from, $to),
            'plans' => TreatmentPlan::listBetween($from, $to),
        ], 'layouts.admin');
    }

    /** Streams the given report as an .xlsx or .pdf download, scoped to the same [from, to] range as the on-screen report. */
    public function export(array $params, Request $request): void
    {
        $this->requireAdmin();

        $type = (string) $params['type'];
        $format = (string) $params['format'];
        if (!in_array($type, self::TYPES, true) || !in_array($format, self::FORMATS, true)) {
            Response::notFound('Unknown report or export format.');
        }

        [$from, $to] = $this->dateRange($request);
        [$title, $headers, $rows] = $this->exportDataset($type, $from, $to);
        $filename = $type . '-report-' . $from . '-to-' . $to;

        if ($format === 'xlsx') {
            $this->streamExcel($title, $headers, $rows, $filename, $type, $from, $to);
        } else {
            $this->streamPdf($title, $headers, $rows, $filename, $type, $from, $to);
        }
    }

    /** @return array{0: string, 1: string} [from, to] as 'Y-m-d' strings, defaulting to the current calendar month. */
    private function dateRange(Request $request): array
    {
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        if (!$this->isValidDate($from)) {
            $from = date('Y-m-01');
        }
        if (!$this->isValidDate($to)) {
            $to = date('Y-m-t');
        }
        // Swap rather than reject an inverted range, since a typo'd pair of
        // dates is far more likely than a deliberate reverse-range request,
        // and silently correcting it keeps the report usable.
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function isValidDate(string $value): bool
    {
        if ($value === '') {
            return false;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return $d !== false && $d->format('Y-m-d') === $value;
    }

    /** @return array{0: string, 1: array<int,string>, 2: array<int,array<int,scalar|null>>} [title, headers, rows] */
    private function exportDataset(string $type, string $from, string $to): array
    {
        return match ($type) {
            'patients' => [
                'Patients Report',
                ['Patient ID', 'Name', 'Gender', 'Age', 'Mobile', 'Email', 'City', 'Registered On'],
                array_map(static fn (array $p) => [
                    $p['patient_code'],
                    Patient::fullName($p),
                    ucfirst($p['gender']),
                    Patient::age($p) ?? '',
                    $p['mobile'],
                    $p['email'] ?? '',
                    $p['city'] ?? '',
                    $p['created_at'],
                ], Patient::registeredBetween($from, $to)),
            ],
            'appointments' => [
                'Appointments Report',
                ['Appt #', 'Patient', 'Therapist', 'Service', 'Date', 'Time', 'Status'],
                array_map(static fn (array $a) => [
                    $a['appointment_number'],
                    $a['patient_name'] ?? '',
                    $a['therapist_name'] ?? '',
                    $a['service_name'] ?? '',
                    $a['scheduled_date'],
                    $a['scheduled_time'],
                    ucfirst($a['status']),
                ], Appointment::listBetween($from, $to)),
            ],
            'revenue' => [
                'Revenue Report',
                ['Payment Date', 'Invoice #', 'Patient', 'Mode', 'Amount', 'Reference'],
                array_map(static fn (array $p) => [
                    $p['payment_date'],
                    $p['invoice_number'],
                    $p['patient_name'],
                    ucfirst(str_replace('_', ' ', $p['payment_mode'])),
                    (float) $p['amount'],
                    $p['reference_number'] ?? '',
                ], Invoice::paymentsBetween($from, $to)),
            ],
            'treatments' => [
                'Treatments Report',
                ['Plan #', 'Patient', 'Therapist', 'Diagnosis', 'Start Date', 'End Date', 'Status', 'Sessions'],
                array_map(static fn (array $p) => [
                    $p['plan_number'],
                    $p['patient_name'],
                    $p['therapist_name'] ?? 'Unassigned',
                    $p['diagnosis'],
                    $p['start_date'],
                    $p['end_date'] ?? '',
                    ucfirst($p['status']),
                    (int) $p['session_count'],
                ], TreatmentPlan::listBetween($from, $to)),
            ],
            default => throw new \InvalidArgumentException('Unknown report type: ' . $type),
        };
    }

    private function streamExcel(string $title, array $headers, array $rows, string $filename, string $type, string $from, string $to): void
    {
        try {
            $bytes = (new ExcelService())->buildWorkbook($title, $headers, $rows);
        } catch (\RuntimeException $e) {
            Logger::warning('Excel report export unavailable: ' . $e->getMessage());
            $this->flashError('Excel export is not available on this server yet (the phpoffice/phpspreadsheet library is not installed). See docs/INSTALL.md.');
            $this->redirect('/admin/reports/' . $type . '?from=' . $from . '&to=' . $to);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
        exit;
    }

    private function streamPdf(string $title, array $headers, array $rows, string $filename, string $type, string $from, string $to): void
    {
        $html = $this->renderReportHtml($title, $from, $to, $headers, $rows);

        try {
            $bytes = (new PdfService())->renderHtmlToPdf($html, 'A4', 'landscape');
        } catch (\RuntimeException $e) {
            Logger::warning('PDF report export unavailable: ' . $e->getMessage());
            $this->flashError('PDF export is not available on this server yet (the dompdf library is not installed). See docs/INSTALL.md.');
            $this->redirect('/admin/reports/' . $type . '?from=' . $from . '&to=' . $to);
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
        exit;
    }

    /** Builds a minimal, self-contained HTML document (inline CSS only, no external assets -- see PdfService::renderHtmlToPdf's isRemoteEnabled=false) for the PDF export. */
    private function renderReportHtml(string $title, string $from, string $to, array $headers, array $rows): string
    {
        $esc = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

        $headHtml = '<tr>' . implode('', array_map(static fn ($h) => '<th>' . $esc($h) . '</th>', $headers)) . '</tr>';
        $bodyHtml = '';
        foreach ($rows as $row) {
            $bodyHtml .= '<tr>' . implode('', array_map(static fn ($v) => '<td>' . $esc($v) . '</td>', $row)) . '</tr>';
        }
        if ($rows === []) {
            $bodyHtml = '<tr><td colspan="' . count($headers) . '" style="text-align:center;color:#888;">No data for this period.</td></tr>';
        }

        return '<!doctype html><html><head><meta charset="utf-8"><style>
            body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #222; }
            h1 { font-size: 16px; margin-bottom: 2px; }
            p.range { color: #666; margin-top: 0; margin-bottom: 14px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
            th { background: #f0f0f0; }
        </style></head><body>'
            . '<h1>' . $esc($title) . '</h1>'
            . '<p class="range">' . $esc($from) . ' to ' . $esc($to) . ' &mdash; Cortex &amp; Kinetic Physiotherapy and Rehabilitation Clinic</p>'
            . '<table><thead>' . $headHtml . '</thead><tbody>' . $bodyHtml . '</tbody></table>'
            . '</body></html>';
    }
}
