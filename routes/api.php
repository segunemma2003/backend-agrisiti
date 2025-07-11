<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentRegistrationController;

// API v1 routes
Route::prefix('v1')->group(function () {
    // Public registration endpoint
    Route::post('/register', [StudentRegistrationController::class, 'store'])
        ->name('api.register');

    // Public analytics endpoint
    Route::get('/analytics', [StudentRegistrationController::class, 'analytics'])
        ->name('api.analytics');

    // Admin endpoints
    Route::prefix('admin')->group(function () {
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
Route::get('/health', function(Request $request) {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'laravel_version' => app()->version(),
        'cors' => 'enabled - Laravel 11',
        'origin' => $request->header('Origin'),
    ]);
})->name('api.health');

// CORS test endpoint (for debugging)
Route::get('/test-cors', function (Request $request) {
    return response()->json([
        'message' => 'CORS is working in Laravel 11!',
        'origin' => $request->header('Origin'),
        'method' => $request->method(),
        'timestamp' => now(),
        'headers' => $request->headers->all(),
    ]);
})->name('api.test-cors');
