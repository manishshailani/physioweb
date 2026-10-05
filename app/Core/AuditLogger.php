<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Writes an immutable trail of who-did-what-when into audit_logs.
 * Called from Controllers after a successful create/update/delete/login
 * on sensitive entities (patients, invoices, payments, users). Never
 * records request bodies verbatim (to avoid capturing passwords or full
 * patient medical notes into a second table) — only entity type, entity
 * id, action, and a short human-readable summary.
 */
final class AuditLogger
{
    public static function log(?int $userId, string $userType, string $action, string $entityType, ?int $entityId, string $description = ''): void
    {
        try {
            Database::insert('audit_logs', [
                'user_id' => $userId,
                'user_type' => $userType,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'description' => mb_substr($description, 0, 500),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'created_at' => Database::now(),
            ]);
        } catch (\Throwable $e) {
            // Audit logging must never break the primary action it is
            // observing; log the failure and continue.
            Logger::error('Audit log write failed: ' . $e->getMessage());
        }
    }
}
