<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Appointment;
use App\Models\ContactInquiry;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\TreatmentPlan;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();

        $this->view('admin.dashboard', [
            'page_title' => 'Dashboard',
            'stats' => [
                'total_patients' => Patient::countActive(),
                'today_appointments' => Appointment::todayCount(),
                'pending_appointments' => Appointment::pendingCount(),
                'active_treatments' => TreatmentPlan::activeCount(),
                'revenue_today' => Invoice::revenueToday(),
                'revenue_month' => Invoice::revenueThisMonth(),
                'outstanding' => Invoice::totalOutstanding(),
                'new_inquiries' => ContactInquiry::newCount(),
            ],
            'recentAppointments' => Appointment::listWithDetails('1=1', [], 'a.created_at DESC', 5),
        ], 'layouts.admin');
    }

    /** JSON data feeds for the dashboard's Chart.js graphs (AJAX). */
    public function chartData(array $params): void
    {
        $this->requireAdmin();

        switch ($params['name']) {
            case 'appointments':
                $rows = Appointment::trendLastNDays(30);
                $this->json(['labels' => array_column($rows, 'scheduled_date'), 'values' => array_map('intval', array_column($rows, 'total'))]);
                // unreachable: json() has return type `never` and exits.

            case 'revenue':
                $rows = Invoice::revenueTrend(30);
                $this->json(['labels' => array_column($rows, 'payment_date'), 'values' => array_map('floatval', array_column($rows, 'total'))]);

            case 'treatment-categories':
                $rows = $this->treatmentCategoryDistribution(90);
                $this->json(['labels' => array_column($rows, 'category'), 'values' => array_map('intval', array_column($rows, 'total'))]);

            default:
                Response::notFound('Unknown chart.');
        }
    }

    /**
     * Appointment counts grouped by service category over the last
     * $days days. The date cutoff is computed in PHP (date('Y-m-d',
     * strtotime(...))) rather than with a SQL date function, specifically
     * so this query is portable between MySQL and SQLite without a
     * driver branch — see App\Core\Database::concat() for the same
     * principle applied to string concatenation elsewhere in this
     * codebase.
     */
    private function treatmentCategoryDistribution(int $days): array
    {
        $since = date('Y-m-d', strtotime("-{$days} days"));
        return \App\Core\Database::fetchAll(
            "SELECT COALESCE(sv.category, 'Uncategorized') AS category, COUNT(*) AS total
             FROM appointments a
             JOIN services sv ON sv.id = a.service_id
             WHERE a.scheduled_date >= :since
             GROUP BY sv.category
             ORDER BY total DESC",
            ['since' => $since]
        );
    }
}
