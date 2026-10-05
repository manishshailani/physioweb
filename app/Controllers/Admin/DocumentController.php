<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\Response;
use App\Models\PatientDocument;

/**
 * Serves a previously-uploaded patient document (see
 * PatientController::uploadDocument()) to an authenticated admin/staff
 * user. Documents are stored under storage/uploads/ -- outside the public/
 * web root -- specifically so they are never directly reachable by URL;
 * this controller is the single, authorization-checked gateway to them
 * (see App\Core\FileUpload's class-level doc comment for the full
 * rationale). The stored file_path is never taken from client input --
 * only from the patient_documents row looked up by its own primary key --
 * so there is no path-traversal surface here to validate against.
 */
final class DocumentController extends Controller
{
    public function download(array $params): void
    {
        $this->requireAdmin();

        $id = (int) $params['id'];
        $document = PatientDocument::find($id);
        if ($document === null) {
            Response::notFound('Document not found.');
        }

        $path = (string) $document['file_path'];
        if (!is_file($path)) {
            Logger::error('Patient document file missing on disk', ['document_id' => $id, 'path' => $path]);
            Response::notFound('This document is no longer available on the server.');
        }

        AuditLogger::log(
            $this->currentAdminId(),
            'admin',
            'view',
            'patient_document',
            $id,
            'Downloaded document "' . $document['original_name'] . '" (patient_id=' . $document['patient_id'] . ')'
        );

        header('Content-Type: ' . $document['mime_type']);
        header('Content-Disposition: inline; filename="' . basename((string) $document['original_name']) . '"');
        header('Content-Length: ' . (string) filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}
