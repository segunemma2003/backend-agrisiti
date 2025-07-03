<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRegistrationRequest;
use App\Http\Resources\StudentRegistrationResource;
use App\Models\StudentRegistration;
use App\Models\RegistrationAnalytics;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StudentRegistrationController extends Controller
{
    // Cache keys for consistent cache management
    private const CACHE_REGISTRATION_STATS = 'registration_stats';
    private const CACHE_REGISTRATION_ANALYTICS = 'registration_analytics';
    private const CACHE_TTL = 3600; // 1 hour

    public function store(StoreStudentRegistrationRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                // Optimized creation with minimal queries
                $registration = StudentRegistration::create([
                    ...$request->validated(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                // Clear only necessary caches
                $this->clearRegistrationCaches();

                // Async analytics update (non-blocking)
                $this->updateDailyAnalyticsAsync();

                Log::info('New student registration', [
                    'student_id' => $registration->id,
                    'email' => $registration->email,
                    'school' => $registration->school_name,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Registration successful! Our team will contact you within 24-48 hours.',
                    'data' => new StudentRegistrationResource($registration),
                ], 201);
            });

        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->except(['password']),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            // Start with optimized base query using forDashboard scope
            $query = StudentRegistration::forDashboard();

            // Apply filters efficiently using indexed columns
            $this->applyFilters($query, $request);

            // Optimized pagination with proper limits
            $perPage = min($request->get('per_page', 20), 100);

            // Use cursor pagination for better performance on large datasets
            if ($request->has('cursor')) {
                $registrations = $query->cursorPaginate($perPage);
            } else {
                $registrations = $query->paginate($perPage);
            }

            return response()->json([
                'success' => true,
                'data' => StudentRegistrationResource::collection($registrations->items()),
                'meta' => [
                    'current_page' => $registrations->currentPage(),
                    'last_page' => $registrations->lastPage(),
                    'per_page' => $registrations->perPage(),
                    'total' => $registrations->total(),
                    'has_more_pages' => $registrations->hasMorePages(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch registrations', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch registrations.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function show(StudentRegistration $registration): JsonResponse
    {
        // No additional queries needed, just return the resource
        return response()->json([
            'success' => true,
            'data' => new StudentRegistrationResource($registration),
        ]);
    }

    public function markAsContacted(StudentRegistration $registration): JsonResponse
    {
        try {
            $registration->markAsContacted();

            Log::info('Student marked as contacted', [
                'student_id' => $registration->id,
                'email' => $registration->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Student marked as contacted successfully.',
                'data' => new StudentRegistrationResource($registration->fresh()),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to mark student as contacted', [
                'student_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark student as contacted.',
            ], 500);
        }
    }

    public function markAsVerified(StudentRegistration $registration): JsonResponse
    {
        try {
            $registration->markAsVerified();

            Log::info('Student marked as verified', [
                'student_id' => $registration->id,
                'email' => $registration->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Student verified successfully.',
                'data' => new StudentRegistrationResource($registration->fresh()),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to verify student', [
                'student_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify student.',
            ], 500);
        }
    }

    public function analytics(): JsonResponse
    {
        try {
            // Use cached analytics with longer TTL for heavy computation
            $analytics = Cache::remember(
                self::CACHE_REGISTRATION_ANALYTICS,
                self::CACHE_TTL * 2, // 2 hours for analytics
                function () {
                    return $this->generateAnalytics();
                }
            );

            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate analytics', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate analytics.',
            ], 500);
        }
    }

    // Bulk operations for admin efficiency
    public function bulkMarkContacted(Request $request): JsonResponse
    {
        $request->validate([
            'student_ids' => 'required|array|max:100',
            'student_ids.*' => 'exists:student_registrations,id',
        ]);

        try {
            // Single bulk update query for performance
            $updated = StudentRegistration::whereIn('id', $request->student_ids)
                ->where('is_contacted', false)
                ->update(['is_contacted' => true, 'updated_at' => now()]);

            $this->clearRegistrationCaches();

            return response()->json([
                'success' => true,
                'message' => "{$updated} students marked as contacted.",
                'updated_count' => $updated,
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk contact update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update students.',
            ], 500);
        }
    }

    public function bulkMarkVerified(Request $request): JsonResponse
    {
        $request->validate([
            'student_ids' => 'required|array|max:100',
            'student_ids.*' => 'exists:student_registrations,id',
        ]);

        try {
            // Single bulk update query for performance
            $updated = StudentRegistration::whereIn('id', $request->student_ids)
                ->where('is_verified', false)
                ->update(['is_verified' => true, 'updated_at' => now()]);

            $this->clearRegistrationCaches();

            return response()->json([
                'success' => true,
                'message' => "{$updated} students marked as verified.",
                'updated_count' => $updated,
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk verification update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update students.',
            ], 500);
        }
    }

    // Optimized filter application
    private function applyFilters($query, Request $request): void
    {
        // Use when() for conditional queries with proper index usage
        $query->when($request->filled('experience'), function ($q) use ($request) {
            // Uses idx_active_experience composite index
            $q->where('experience_level', $request->experience);
        })
        ->when($request->filled('location'), function ($q) use ($request) {
            // Uses idx_active_location composite index
            $q->where('location', 'like', "%{$request->location}%");
        })
        ->when($request->filled('verified'), function ($q) use ($request) {
            // Uses idx_active_verified composite index
            $q->where('is_verified', filter_var($request->verified, FILTER_VALIDATE_BOOLEAN));
        })
        ->when($request->filled('contacted'), function ($q) use ($request) {
            // Uses idx_active_contacted composite index
            $q->where('is_contacted', filter_var($request->contacted, FILTER_VALIDATE_BOOLEAN));
        })
        ->when($request->filled('age_from'), function ($q) use ($request) {
            // Uses idx_age_active_created composite index
            $q->where('age', '>=', $request->age_from);
        })
        ->when($request->filled('age_to'), function ($q) use ($request) {
            $q->where('age', '<=', $request->age_to);
        })
        ->when($request->filled('school'), function ($q) use ($request) {
            // Uses idx_school_active composite index
            $q->where('school_name', 'like', "%{$request->school}%");
        })
        ->when($request->filled('search'), function ($q) use ($request) {
            // Uses optimized search scope with proper indexes
            $q->search($request->search);
        });
    }

    // Highly optimized analytics generation
    private function generateAnalytics(): array
    {
        $baseStats = StudentRegistration::getRegistrationStats();

        // All queries optimized with proper indexes and caching
        $analytics = [
            ...$baseStats,
            'experience_breakdown' => $this->getExperienceBreakdown(),
            'location_breakdown' => $this->getLocationBreakdown(),
            'school_breakdown' => $this->getSchoolBreakdown(),
            'interest_breakdown' => StudentRegistration::getInterestBreakdown(),
            'daily_registrations' => StudentRegistration::getDailyRegistrations(30),
            'age_distribution' => $this->getAgeDistribution(),
        ];

        return $analytics;
    }

    // Optimized breakdown methods using raw SQL for performance
    private function getExperienceBreakdown(): array
    {
        return Cache::remember('experience_breakdown', self::CACHE_TTL, function () {
            $results = DB::select("
                SELECT
                    experience_level,
                    COUNT(*) as count
                FROM student_registrations
                WHERE is_active = 1
                GROUP BY experience_level
                ORDER BY count DESC
            ");

            $breakdown = [];
            foreach ($results as $result) {
                $breakdown[StudentRegistration::getExperienceLevelLabel($result->experience_level)] = $result->count;
            }

            return $breakdown;
        });
    }

    private function getLocationBreakdown(): array
    {
        return Cache::remember('location_breakdown', self::CACHE_TTL, function () {
            $results = DB::select("
                SELECT
                    location,
                    COUNT(*) as count
                FROM student_registrations
                WHERE is_active = 1
                GROUP BY location
                ORDER BY count DESC
                LIMIT 15
            ");

            $breakdown = [];
            foreach ($results as $result) {
                $breakdown[$result->location] = $result->count;
            }

            return $breakdown;
        });
    }

    private function getSchoolBreakdown(): array
    {
        return Cache::remember('school_breakdown', self::CACHE_TTL, function () {
            $results = DB::select("
                SELECT
                    school_name,
                    COUNT(*) as count
                FROM student_registrations
                WHERE is_active = 1
                GROUP BY school_name
                ORDER BY count DESC
                LIMIT 15
            ");

            $breakdown = [];
            foreach ($results as $result) {
                $breakdown[$result->school_name] = $result->count;
            }

            return $breakdown;
        });
    }

    private function getAgeDistribution(): array
    {
        return Cache::remember('age_distribution', self::CACHE_TTL, function () {
            $results = DB::select("
                SELECT
                    age,
                    COUNT(*) as count
                FROM student_registrations
                WHERE is_active = 1
                GROUP BY age
                ORDER BY age ASC
            ");

            $distribution = [];
            foreach ($results as $result) {
                $distribution[$result->age] = $result->count;
            }

            return $distribution;
        });
    }

    // Efficient cache management
    private function clearRegistrationCaches(): void
    {
        $cacheKeys = [
            self::CACHE_REGISTRATION_STATS,
            self::CACHE_REGISTRATION_ANALYTICS,
            StudentRegistration::CACHE_STATS_KEY,
            StudentRegistration::CACHE_ANALYTICS_KEY,
            'experience_breakdown',
            'location_breakdown',
            'school_breakdown',
            'age_distribution',
            'daily_registrations_30',
            'interest_breakdown',
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    // Async analytics update for non-blocking performance
    private function updateDailyAnalyticsAsync(): void
    {
        // Use queue for heavy operations in production
        if (app()->environment('production')) {
            dispatch(function () {
                $this->updateDailyAnalytics();
            })->onQueue('analytics');
        } else {
            $this->updateDailyAnalytics();
        }
    }

    private function updateDailyAnalytics(): void
    {
        $today = Carbon::today();

        // Use upsert for better performance than updateOrCreate
        DB::table('registration_analytics')
            ->updateOrInsert(
                ['date' => $today],
                [
                    'total_registrations' => StudentRegistration::whereDate('created_at', $today)->count(),
                    'verified_registrations' => StudentRegistration::whereDate('created_at', $today)->verified()->count(),
                    'contacted_registrations' => StudentRegistration::whereDate('created_at', $today)->contacted()->count(),
                    'updated_at' => now(),
                ]
            );
    }
}
