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
    public function store(StoreStudentRegistrationRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $registration = StudentRegistration::create([
                    ...$request->validated(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                // Clear cached statistics
                Cache::forget('registration_stats');
                Cache::forget('registration_analytics');

                // Update daily analytics
                $this->updateDailyAnalytics();

                Log::info('New student registration', [
                    'student_id' => $registration->id,
                    'email' => $registration->email,
                    'ip_address' => $registration->ip_address,
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
            $query = StudentRegistration::query()->active();

            // Apply filters using when() method
            $query->when($request->filled('experience'), fn($q) =>
                $q->byExperience($request->experience)
            )
            ->when($request->filled('location'), fn($q) =>
                $q->byLocation($request->location)
            )
            ->when($request->filled('verified'), fn($q) =>
                $q->where('is_verified', filter_var($request->verified, FILTER_VALIDATE_BOOLEAN))
            )
            ->when($request->filled('contacted'), fn($q) =>
                $q->where('is_contacted', filter_var($request->contacted, FILTER_VALIDATE_BOOLEAN))
            )
            ->when($request->filled('search'), fn($q) =>
                $q->search($request->search)
            );

            // Pagination
            $perPage = min($request->get('per_page', 20), 100);
            $registrations = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => StudentRegistrationResource::collection($registrations->items()),
                'meta' => [
                    'current_page' => $registrations->currentPage(),
                    'last_page' => $registrations->lastPage(),
                    'per_page' => $registrations->perPage(),
                    'total' => $registrations->total(),
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
            $analytics = Cache::remember('registration_analytics', 3600, function () {
                return $this->generateAnalytics();
            });

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

    private function generateAnalytics(): array
    {
        $baseStats = StudentRegistration::getRegistrationStats();

        // Experience breakdown
        $experienceBreakdown = StudentRegistration::active()
            ->select('experience_level', DB::raw('count(*) as count'))
            ->groupBy('experience_level')
            ->pluck('count', 'experience_level')
            ->mapWithKeys(fn($count, $level) =>
                [StudentRegistration::getExperienceLevelLabel($level) => $count]
            )
            ->toArray();

        // Location breakdown (top 10)
        $locationBreakdown = StudentRegistration::active()
            ->select('location', DB::raw('count(*) as count'))
            ->groupBy('location')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'location')
            ->toArray();

        // Interest breakdown
        $interestBreakdown = $this->getInterestBreakdown();

        // Daily registrations for last 30 days
        $dailyRegistrations = $this->getDailyRegistrations();

        return [
            ...$baseStats,
            'experience_breakdown' => $experienceBreakdown,
            'location_breakdown' => $locationBreakdown,
            'interest_breakdown' => $interestBreakdown,
            'daily_registrations' => $dailyRegistrations,
        ];
    }

    private function getInterestBreakdown(): array
    {
        return StudentRegistration::active()
            ->whereNotNull('interests')
            ->pluck('interests')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->toArray();
    }

    private function getDailyRegistrations(): array
    {
        return collect(range(29, 0))
            ->map(fn($daysAgo) => [
                'date' => Carbon::now()->subDays($daysAgo)->toDateString(),
                'count' => StudentRegistration::active()
                    ->whereDate('created_at', Carbon::now()->subDays($daysAgo))
                    ->count(),
            ])
            ->toArray();
    }

    private function updateDailyAnalytics(): void
    {
        $today = Carbon::today();

        RegistrationAnalytics::updateOrCreate(
            ['date' => $today],
            [
                'total_registrations' => StudentRegistration::whereDate('created_at', $today)->count(),
                'verified_registrations' => StudentRegistration::whereDate('created_at', $today)->verified()->count(),
                'contacted_registrations' => StudentRegistration::whereDate('created_at', $today)->contacted()->count(),
            ]
        );
    }
}
