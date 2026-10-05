<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Service;
use App\Models\Setting;

final class ServiceController extends Controller
{
    public function index(): void
    {
        $this->view('public.services.index', [
            'services' => Service::activeOrdered(),
            'settings' => Setting::allAsMap(),
            'page_title' => 'Our Services',
        ], 'layouts.public');
    }

    public function show(array $params): void
    {
        $service = Service::findBySlug($params['slug']);
        if ($service === null) {
            Response::notFound('Service not found.');
        }
        $this->view('public.services.show', [
            'service' => $service,
            'settings' => Setting::allAsMap(),
            'page_title' => $service['name'],
        ], 'layouts.public');
    }
}
