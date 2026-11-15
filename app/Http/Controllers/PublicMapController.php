<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PublicMapController extends Controller
{
    /**
     * Display the fullscreen public outage map.
     */
    public function __invoke(): View
    {
        return view('map');
    }
}
