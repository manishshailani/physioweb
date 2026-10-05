<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\AuditLog;

/**
 * Read-only admin screen over the audit_logs table (see App\Models\AuditLog
 * for the join/query helpers and App\Core\AuditLogger for the write side).
 * Restricted to the 'admin' role -- audit trail visibility is itself a
 * sensitive capability that receptionist/therapist_staff accounts should
 * not have, matching the same restriction already applied to CMS User
 * management and Settings (see UserController::requireAdminRole calls).
 */
final class AuditLogController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request): void
    {
        $this->requireAdminRole('admin');

        $page = max(1, (int) $request->query('page', 1));
        $entityType = trim((string) $request->query('entity_type', ''));
        $action = trim((string) $request->query('action', ''));
        $userType = trim((string) $request->query('user_type', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        [$whereSql, $params] = $this->buildFilters($entityType, $action, $userType, $dateFrom, $dateTo);
        $result = AuditLog::paginatedWithUser($page, self::PER_PAGE, $whereSql, $params);

        $this->view('admin.audit-logs.index', [
            'page_title' => 'Audit Logs',
            'logs' => $result['data'],
            'pagination' => $result,
            'entityTypes' => AuditLog::distinctEntityTypes(),
            'actions' => AuditLog::distinctActions(),
            'filters' => [
                'entity_type' => $entityType,
                'action' => $action,
                'user_type' => $userType,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ], 'layouts.admin');
    }

    /** @return array{0: string, 1: array} [whereSql, params] */
    private function buildFilters(string $entityType, string $action, string $userType, string $dateFrom, string $dateTo): array
    {
        $conditions = ['1=1'];
        $params = [];

        if ($entityType !== '') {
            $conditions[] = 'al.entity_type = :entity_type';
            $params['entity_type'] = $entityType;
        }
        if ($action !== '') {
            $conditions[] = 'al.action = :action';
            $params['action'] = $action;
        }
        if (in_array($userType, ['admin', 'patient', 'system'], true)) {
            $conditions[] = 'al.user_type = :user_type';
            $params['user_type'] = $userType;
        }
        // Date filters compare against DATETIME strings using simple
        // lexical BETWEEN bounds (both drivers store 'Y-m-d H:i:s'), rather
        // than a SQL DATE()/strftime() truncation function, for the same
        // cross-driver-portability reason documented on
        // Database::concat() -- appending the time-of-day bound keeps the
        // comparison correct for rows that fall anywhere within the day.
        if ($dateFrom !== '' && $this->isValidDate($dateFrom)) {
            $conditions[] = 'al.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo !== '' && $this->isValidDate($dateTo)) {
            $conditions[] = 'al.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        return [implode(' AND ', $conditions), $params];
    }

    private function isValidDate(string $value): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return $d !== false && $d->format('Y-m-d') === $value;
    }
}
