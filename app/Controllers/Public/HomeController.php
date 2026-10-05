<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\Therapist;

final class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('public.home', [
            'settings' => Setting::allAsMap(),
            'services' => array_slice(Service::activeOrdered(), 0, 6),
            'therapists' => array_slice(Therapist::allWithStaff(true), 0, 3),
            'testimonials' => array_slice(Testimonial::approved(), 0, 6),
            'page_title' => 'Home',
        ], 'layouts.public');
    }
}
