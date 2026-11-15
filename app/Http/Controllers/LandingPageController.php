<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LandingPageController extends Controller
{
    /**
     * Display the public landing page with embedded outage map.
     */
    public function __invoke(): View
    {
        return view('landing');
    }
}
