<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\FileUpload;
use App\Core\Request;
use App\Models\Gallery;

final class GalleryController extends Controller
{
    private const CATEGORIES = ['clinic', 'treatments', 'events'];

    public function index(): void
    {
        $this->requireAdmin();
        $this->view('admin.gallery.index', [
            'page_title' => 'Gallery',
            'items' => Gallery::all('display_order, created_at DESC'),
            'categories' => self::CATEGORIES,
        ], 'layouts.admin');
    }

    public function store(Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);

        $file = $request->file('media');
        if ($file === null) {
            $this->flashError('Please choose an image to upload.');
            $this->redirect('/admin/gallery');
        }

        $uploaded = FileUpload::store($file, dirname(__DIR__, 3) . '/public/uploads/gallery', 'image', 10);
        if (!$uploaded['ok']) {
            $this->flashError($uploaded['error']);
            $this->redirect('/admin/gallery');
        }

        $title = trim((string) $request->input('title', ''));
        $category = (string) $request->input('category', 'clinic');
        if (!in_array($category, self::CATEGORIES, true)) {
            $category = 'clinic';
        }

        $id = Gallery::create([
            'title' => $title !== '' ? $title : null,
            'media_type' => 'image',
            'file_path' => '/uploads/gallery/' . $uploaded['stored_name'],
            'category' => $category,
            'display_order' => (int) $request->input('display_order', 0),
            'created_at' => Database::now(),
        ]);

        AuditLogger::log($this->currentAdminId(), 'admin', 'create', 'gallery', $id, 'Uploaded gallery image' . ($title !== '' ? ' "' . $title . '"' : ''));

        $this->flashSuccess('Image added to the gallery.');
        $this->redirect('/admin/gallery');
    }

    public function destroy(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $item = Gallery::find($id);
        if ($item !== null) {
            Gallery::deleteById($id);
            $fullPath = dirname(__DIR__, 3) . '/public' . $item['file_path'];
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
            AuditLogger::log($this->currentAdminId(), 'admin', 'delete', 'gallery', $id, 'Deleted gallery image');
        }
        $this->flashSuccess('Image removed.');
        $this->redirect('/admin/gallery');
    }
}
