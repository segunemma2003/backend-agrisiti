<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentRegistrationController;

// Laravel 11 Route API group
Route::group(['prefix' => 'v1'], function () {
    // Public registration endpoint
    Route::post('/register', [StudentRegistrationController::class, 'store'])
        ->name('api.register');

    // Protected admin endpoints
    Route::group(['prefix' => 'admin'], function () {
        Route::get('/registrations', [StudentRegistrationController::class, 'index'])
            ->name('api.admin.registrations.index');

        Route::get('/registrations/{registration}', [StudentRegistrationController::class, 'show'])
            ->name('api.admin.registrations.show');

        Route::patch('/registrations/{registration}/contacted', [StudentRegistrationController::class, 'markAsContacted'])
            ->name('api.admin.registrations.contacted');

        Route::patch('/registrations/{registration}/verified', [StudentRegistrationController::class, 'markAsVerified'])
            ->name('api.admin.registrations.verified');

        Route::get('/analytics', [StudentRegistrationController::class, 'analytics'])
            ->name('api.admin.analytics');
    });
});

// Health check endpoint
Route::get('/health', fn() => response()->json([
    'status' => 'ok',
    'timestamp' => now()->toISOString(),
    'laravel_version' => app()->version(),
]))->name('api.health');
