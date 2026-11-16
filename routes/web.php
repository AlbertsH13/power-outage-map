<?php

use App\Http\Controllers\Api\V1\OutageApiController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicMapController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('home');
Route::get('/map', PublicMapController::class)->name('map');

Route::get('/dashboard', function () {
    return redirect()->route('reports.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])
        ->middleware('throttle:create-outage')
        ->name('reports.store');
    Route::get('/my-reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{outage}/edit', [ReportController::class, 'edit'])->name('reports.edit');
    Route::match(['put', 'patch'], '/reports/{outage}', [ReportController::class, 'update'])->name('reports.update');
    Route::match(['post', 'patch'], '/reports/{outage}/close', [ReportController::class, 'close'])->name('reports.close');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('api/v1')->group(function () {
    Route::get('/outages', [OutageApiController::class, 'index'])
        ->middleware('throttle:outages-api')
        ->name('api.outages.index');

    Route::middleware(['auth', 'throttle:create-outage'])->group(function () {
        Route::post('/outages', [OutageApiController::class, 'store'])
            ->name('api.outages.store');
        Route::match(['put', 'patch'], '/outages/{outage}', [OutageApiController::class, 'update'])
            ->name('api.outages.update');
        Route::match(['post', 'patch'], '/outages/{outage}/close', [OutageApiController::class, 'close'])
            ->name('api.outages.close');
    });
});

require __DIR__.'/auth.php';
