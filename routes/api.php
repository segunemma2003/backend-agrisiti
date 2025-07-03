<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentRegistrationController;

// Handle all OPTIONS requests for CORS preflight
Route::options('/{any}', function (Request $request) {
    return response()->json([], 200);
})->where('any', '.*');

// Laravel 11 Route API group
Route::group(['prefix' => 'v1'], function () {
    // Public registration endpoint - completely open
    Route::post('/register', [StudentRegistrationController::class, 'store'])
        ->name('api.register');

    // Public analytics endpoint - if you want it open
    Route::get('/analytics', [StudentRegistrationController::class, 'analytics'])
        ->name('api.analytics');

    // Admin endpoints - you might want to protect these later
    Route::group(['prefix' => 'admin'], function () {
        Route::get('/registrations', [StudentRegistrationController::class, 'index'])
            ->name('api.admin.registrations.index');

        Route::get('/registrations/{registration}', [StudentRegistrationController::class, 'show'])
            ->name('api.admin.registrations.show');

        Route::patch('/registrations/{registration}/contacted', [StudentRegistrationController::class, 'markAsContacted'])
            ->name('api.admin.registrations.contacted');

        Route::patch('/registrations/{registration}/verified', [StudentRegistrationController::class, 'markAsVerified'])
            ->name('api.admin.registrations.verified');

        Route::post('/registrations/bulk-contacted', [StudentRegistrationController::class, 'bulkMarkContacted'])
            ->name('api.admin.registrations.bulk-contacted');

        Route::post('/registrations/bulk-verified', [StudentRegistrationController::class, 'bulkMarkVerified'])
            ->name('api.admin.registrations.bulk-verified');
    });
});

// Health check endpoint
Route::get('/health', fn() => response()->json([
    'status' => 'ok',
    'timestamp' => now()->toISOString(),
    'laravel_version' => app()->version(),
    'cors' => 'enabled - all origins allowed',
]))->name('api.health');
