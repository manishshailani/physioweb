<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Setting;
use App\Models\Therapist;

final class TeamController extends Controller
{
    public function index(): void
    {
        $this->view('public.team.index', [
            'therapists' => Therapist::allWithStaff(true),
            'settings' => Setting::allAsMap(),
            'page_title' => 'Our Physiotherapists',
        ], 'layouts.public');
    }

    public function show(array $params): void
    {
        $therapist = Therapist::findBySlugWithStaff($params['slug']);
        if ($therapist === null) {
            Response::notFound('Physiotherapist profile not found.');
        }
        $this->view('public.team.show', [
            'therapist' => $therapist,
            'settings' => Setting::allAsMap(),
            'page_title' => $therapist['first_name'] . ' ' . $therapist['last_name'],
        ], 'layouts.public');
    }
}
