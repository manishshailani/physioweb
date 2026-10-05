<?php

declare(strict_types=1);

namespace App\Controllers\Patient;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\Response;
use App\Models\PatientDocument;

/**
 * Serves one of the logged-in patient's own uploaded documents. See
 * Admin\DocumentController for the general rationale (documents live
 * outside the public/ web root and are only ever reachable through an
 * authorization-checked controller); the one difference here is the
 * ownership check against $this->currentPatientId(), which is what stops
 * one patient from downloading another patient's document by
 * guessing/incrementing the id in the URL.
 */
final class DocumentController extends Controller
{
    public function download(array $params): void
    {
        $this->requirePatient();

        $id = (int) $params['id'];
        $document = PatientDocument::find($id);
        if ($document === null || (int) $document['patient_id'] !== $this->currentPatientId()) {
            Response::notFound('Document not found.');
        }

        $path = (string) $document['file_path'];
        if (!is_file($path)) {
            Logger::error('Patient document file missing on disk', ['document_id' => $id, 'path' => $path]);
            Response::notFound('This document is no longer available on the server.');
        }

        AuditLogger::log(
            $this->currentPatientId(),
            'patient',
            'view',
            'patient_document',
            $id,
            'Patient downloaded own document "' . $document['original_name'] . '"'
        );

        header('Content-Type: ' . $document['mime_type']);
        header('Content-Disposition: inline; filename="' . basename((string) $document['original_name']) . '"');
        header('Content-Length: ' . (string) filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}
