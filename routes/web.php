<?php

use App\Http\Controllers\Api\V1\OutageApiController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicMapController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('home');
Route::get('/map', PublicMapController::class)->name('map');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::prefix('api/v1')->group(function () {
    Route::get('/outages', [OutageApiController::class, 'index'])
        ->middleware('throttle:outages-api')
        ->name('api.outages.index');

    Route::middleware(['auth', 'throttle:create-outage'])->group(function () {
        Route::post('/outages', [OutageApiController::class, 'store'])
            ->name('api.outages.store');
        Route::match(['post', 'patch'], '/outages/{outage}/close', [OutageApiController::class, 'close'])
            ->name('api.outages.close');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
