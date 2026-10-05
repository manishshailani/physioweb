<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Setting;
use App\Models\Testimonial;

final class TestimonialController extends Controller
{
    public function index(): void
    {
        $this->view('public.testimonials', [
            'testimonials' => Testimonial::approved(),
            'settings' => Setting::allAsMap(),
            'page_title' => 'Patient Testimonials',
        ], 'layouts.public');
    }
}
