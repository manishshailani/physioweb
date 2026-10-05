<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Read-side query helpers over the audit_logs table. Rows are written by
 * App\Core\AuditLogger::log() (called from Controllers immediately after a
 * successful create/update/delete/login on a sensitive entity -- see that
 * class for the write-side contract and its swallow-and-log-only-on-failure
 * behavior, which guarantees audit logging itself can never break the
 * primary action it observes). This Model only ever reads; the admin
 * "Audit Logs" screen is intentionally read-only, matching the
 * append-only, tamper-evident nature an audit trail requires.
 */
final class AuditLog extends Model
{
    protected static string $table = 'audit_logs';

    /**
     * Logs joined with the acting admin's name. user_id/user_type are
     * polymorphic (an admin user id and a patient id are both plain
     * integers from unrelated tables), so the join to `users` is
     * deliberately qualified with `al.user_type = 'admin'` -- without that
     * guard a patient- or system-initiated row could spuriously match
     * whichever admin user happens to share that numeric id. Patient/system
     * rows simply get a NULL admin_name and the view falls back to
     * displaying user_type instead.
     */
    public static function listWithUser(string $whereSql = '1=1', array $params = [], string $orderBy = 'al.created_at DESC, al.id DESC', int $limit = 0): array
    {
        $sql = "SELECT al.*, u.name AS admin_name
                FROM audit_logs al
                LEFT JOIN users u ON u.id = al.user_id AND al.user_type = 'admin'
                WHERE {$whereSql}
                ORDER BY {$orderBy}";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }
        return Database::fetchAll($sql, $params);
    }

    public static function paginatedWithUser(int $page, int $perPage, string $whereSql = '1=1', array $params = [], string $orderBy = 'al.created_at DESC, al.id DESC'): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $total = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM audit_logs al WHERE {$whereSql}",
            $params
        );
        $rows = static::listWithUser($whereSql, $params, $orderBy . ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    /** Distinct action/entity-type values already logged, for the filter dropdowns. */
    public static function distinctActions(): array
    {
        return array_column(Database::fetchAll('SELECT DISTINCT action FROM audit_logs ORDER BY action'), 'action');
    }

    public static function distinctEntityTypes(): array
    {
        return array_column(Database::fetchAll('SELECT DISTINCT entity_type FROM audit_logs ORDER BY entity_type'), 'entity_type');
    }
}
