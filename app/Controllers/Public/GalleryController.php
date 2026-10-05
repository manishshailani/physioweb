<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Gallery;
use App\Models\Setting;

final class GalleryController extends Controller
{
    public function index(array $params, Request $request): void
    {
        $category = $request->query('category', 'all');
        $this->view('public.gallery', [
            'items' => Gallery::byCategory($category === '' ? 'all' : $category),
            'activeCategory' => $category,
            'settings' => Setting::allAsMap(),
            'page_title' => 'Gallery',
        ], 'layouts.public');
    }
}
