<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Secure-ish file upload handling.
 *
 * Checks applied, in order:
 *  1. PHP-level upload error code (reject anything but UPLOAD_ERR_OK).
 *  2. Size limit (configurable, default from UPLOAD_MAX_SIZE_MB).
 *  3. Extension allow-list (never a deny-list).
 *  4. Real MIME type via finfo (fileinfo extension), not the client-supplied
 *     Content-Type, which is attacker-controlled.
 *  5. Destination filename is server-generated (random, never derived from
 *     the original filename) specifically to prevent path traversal and to
 *     avoid executing an uploaded file as a script if it were ever placed
 *     under a web-servable path with the wrong extension.
 *  6. Files are stored under storage/uploads/, which is outside public/
 *     (the document root) and therefore not directly web-accessible; they
 *     are served through a controller action that checks authorization
 *     first (see Admin\DocumentsController / Patient\DocumentsController).
 */
final class FileUpload
{
    private const ALLOWED = [
        'document' => [
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
            'mimes' => ['application/pdf', 'image/jpeg', 'image/png'],
        ],
        'image' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        ],
    ];

    public static function store(array $file, string $subdirectory, string $type, int $maxSizeMb = 10): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => self::uploadErrorMessage((int) $file['error'])];
        }

        if ($file['size'] > $maxSizeMb * 1024 * 1024) {
            return ['ok' => false, 'error' => "File exceeds the {$maxSizeMb}MB size limit."];
        }

        $rules = self::ALLOWED[$type] ?? self::ALLOWED['document'];

        $originalExt = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($originalExt, $rules['extensions'], true)) {
            return ['ok' => false, 'error' => 'File type not allowed. Allowed: ' . implode(', ', $rules['extensions'])];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file['tmp_name']);
        if ($realMime === false || !in_array($realMime, $rules['mimes'], true)) {
            return ['ok' => false, 'error' => 'The file content does not match an allowed file type.'];
        }

        $destinationDir = rtrim($subdirectory, '/');
        if (!is_dir($destinationDir)) {
            if (!@mkdir($destinationDir, 0775, true) && !is_dir($destinationDir)) {
                return ['ok' => false, 'error' => 'Could not create upload directory.'];
            }
        }

        $randomName = bin2hex(random_bytes(16)) . '.' . $originalExt;
        $destination = $destinationDir . '/' . $randomName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            Logger::error('move_uploaded_file failed', ['destination' => $destination]);
            return ['ok' => false, 'error' => 'Could not save the uploaded file.'];
        }

        return [
            'ok' => true,
            'stored_name' => $randomName,
            'original_name' => $file['name'],
            'path' => $destination,
            'mime' => $realMime,
            'size' => $file['size'],
        ];
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file is too large.',
            UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server misconfiguration: no temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not write the file to disk.',
            UPLOAD_ERR_EXTENSION => 'A server extension blocked the file upload.',
            default => 'File upload failed.',
        };
    }
}
