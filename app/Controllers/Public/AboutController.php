<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Setting;
use App\Models\Therapist;

final class AboutController extends Controller
{
    public function index(): void
    {
        $this->view('public.about', [
            'settings' => Setting::allAsMap(),
            'therapists' => Therapist::allWithStaff(true),
            'page_title' => 'About Us',
        ], 'layouts.public');
    }
}
