<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentRegistrationController;

// Global OPTIONS handler for all routes
Route::options('/{any}', function (Request $request) {
    $origin = $request->header('Origin');

    // List of allowed origins
    $allowedOrigins = [
        'http://localhost:3000',
        'http://localhost:5173',
        'http://localhost:8080',
        'http://127.0.0.1:5173',
        'https://agrisiti.com',
        'https://www.agrisiti.com',
        'https://e-register.agrisiti.com',
        'https://backend.agrisiti.com',
    ];

    // Check if origin is allowed
    $allowOrigin = '*'; // Default to allow all
    if (in_array($origin, $allowedOrigins)) {
        $allowOrigin = $origin;
    }

    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', $allowOrigin)
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN')
        ->header('Access-Control-Allow-Credentials', 'true')
        ->header('Access-Control-Max-Age', '86400');
})->where('any', '.*');

// Laravel 11 Route API group
Route::group(['prefix' => 'v1'], function () {
    // Specific OPTIONS handlers
    Route::options('/register', [StudentRegistrationController::class, 'handlePreflight']);
    Route::options('/analytics', [StudentRegistrationController::class, 'handlePreflight']);
    Route::options('/admin/{any}', [StudentRegistrationController::class, 'handlePreflight'])->where('any', '.*');

    // Public registration endpoint
    Route::post('/register', [StudentRegistrationController::class, 'store'])
        ->name('api.register');

    // Public analytics endpoint
    Route::get('/analytics', [StudentRegistrationController::class, 'analytics'])
        ->name('api.analytics');

    // Admin endpoints
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

// Health check endpoint with CORS
Route::get('/health', function(Request $request) {
    $origin = $request->header('Origin');

    $response = response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'laravel_version' => app()->version(),
        'cors' => 'enabled - production ready',
        'origin' => $origin,
    ]);

    return $response
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN');
})->name('api.health');
