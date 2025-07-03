<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentRegistration extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'student_registrations';

    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone', 'age', 'date_of_birth',
        'school_name', 'parent_name', 'parent_phone', 'parent_email',
        'location', 'experience_level', 'interests', 'motivation',
        'ip_address', 'user_agent', 'is_active', 'is_verified', 'is_contacted',
    ];

    // Optimized casts for performance
    protected function casts(): array
    {
        return [
            'interests' => 'array',
            'date_of_birth' => 'date:Y-m-d', // Specific format for faster parsing
            'age' => 'integer',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'is_contacted' => 'boolean',
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    protected $hidden = ['ip_address', 'user_agent'];

    // Constants for better performance than database lookups
    const EXPERIENCE_LEVELS = [
        'beginner' => 'Beginner - New to farming',
        'intermediate' => 'Intermediate - Some farming experience',
        'advanced' => 'Advanced - Experienced farmer',
        'professional' => 'Professional - Agricultural professional',
    ];

    const VALID_INTERESTS = [
        'Crop Production', 'Livestock Management', 'Sustainable Farming',
        'Precision Agriculture', 'Hydroponics', 'Organic Farming',
        'Agricultural Technology', 'Farm Business Management',
        'Poultry Farming', 'Fish Farming', 'Beekeeping', 'Greenhouse Management',
    ];

    // Cache keys for performance
    const CACHE_STATS_KEY = 'student_registration_stats';
    const CACHE_ANALYTICS_KEY = 'student_registration_analytics';
    const CACHE_TTL = 3600; // 1 hour

    // Optimized Attribute Accessors with minimal computation
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->first_name . ' ' . $this->last_name,
        );
    }

    protected function daysSinceRegistration(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at->diffInDays(now()),
        );
    }

    // Efficient Query Scopes with proper index usage
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true); // Uses idx_is_active
    }

    public function scopeVerified(Builder $query): void
    {
        $query->where('is_verified', true); // Uses idx_is_verified
    }

    public function scopeContacted(Builder $query): void
    {
        $query->where('is_contacted', true); // Uses idx_is_contacted
    }

    public function scopeRecentRegistrations(Builder $query, int $days = 30): void
    {
        // Uses idx_created_active_sort for optimal performance
        $query->where('created_at', '>=', now()->subDays($days))
              ->where('is_active', true)
              ->orderBy('created_at', 'desc');
    }

    public function scopeByExperience(Builder $query, string $experience): void
    {
        // Uses idx_active_experience composite index
        $query->where('is_active', true)
              ->where('experience_level', $experience);
    }

    public function scopeByLocation(Builder $query, string $location): void
    {
        // Uses idx_active_location composite index
        $query->where('is_active', true)
              ->where('location', 'like', "%{$location}%");
    }

    public function scopeByAgeRange(Builder $query, int $minAge, int $maxAge = null): void
    {
        // Uses idx_age_active_created for optimal performance
        $query->where('is_active', true)
              ->where('age', '>=', $minAge);

        if ($maxAge) {
            $query->where('age', '<=', $maxAge);
        }
    }

    public function scopeBySchool(Builder $query, string $school): void
    {
        // Uses idx_school_active composite index
        $query->where('is_active', true)
              ->where('school_name', 'like', "%{$school}%");
    }

    // Optimized search with proper index usage
    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where('is_active', true)
              ->where(function ($q) use ($search) {
                  $q->where('first_name', 'like', "%{$search}%")      // Uses idx_first_name
                    ->orWhere('last_name', 'like', "%{$search}%")      // Uses idx_last_name
                    ->orWhere('email', 'like', "%{$search}%")          // Uses idx_email_unique
                    ->orWhere('parent_name', 'like', "%{$search}%")    // Uses idx_parent_name
                    ->orWhere('parent_email', 'like', "%{$search}%")   // Uses idx_parent_email
                    ->orWhere('school_name', 'like', "%{$search}%")    // Uses idx_school_name
                    ->orWhere('location', 'like', "%{$search}%");      // Uses idx_location
              });
    }

    // Highly optimized dashboard query for Filament
    public function scopeForDashboard(Builder $query): void
    {
        // Uses idx_active_created for optimal sorting performance
        $query->select([
                'id', 'first_name', 'last_name', 'age', 'school_name',
                'parent_name', 'location', 'experience_level',
                'is_verified', 'is_contacted', 'created_at'
            ])
            ->where('is_active', true)
            ->orderBy('created_at', 'desc');
    }

    // Performance-optimized methods
    public function markAsContacted(): bool
    {
        $result = $this->update(['is_contacted' => true]);

        // Clear relevant caches
        Cache::forget(self::CACHE_STATS_KEY);
        Cache::forget(self::CACHE_ANALYTICS_KEY);

        return $result;
    }

    public function markAsVerified(): bool
    {
        $result = $this->update(['is_verified' => true]);

        // Clear relevant caches
        Cache::forget(self::CACHE_STATS_KEY);
        Cache::forget(self::CACHE_ANALYTICS_KEY);

        return $result;
    }

    public function getAgeGroup(): string
    {
        // Cached computation for frequently accessed data
        return Cache::remember("age_group_{$this->age}", self::CACHE_TTL, function () {
            $age = $this->age;

            if ($age <= 12) return 'Child (≤12)';
            if ($age <= 17) return 'Teen (13-17)';
            if ($age <= 25) return 'Young Adult (18-25)';
            if ($age <= 35) return 'Adult (26-35)';

            return 'Mature Adult (36+)';
        });
    }

    // Highly optimized static methods with caching
    public static function getExperienceLevelLabel(string $level): string
    {
        return self::EXPERIENCE_LEVELS[$level] ?? $level;
    }

    public static function getRegistrationStats(): array
    {
        return Cache::remember(self::CACHE_STATS_KEY, self::CACHE_TTL, function () {
            // Single optimized query using indexes
            $baseQuery = self::where('is_active', true);

            // Use raw SQL with proper indexes for maximum performance
            $stats = DB::select("
                SELECT
                    COUNT(*) as total_registrations,
                    SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified_registrations,
                    SUM(CASE WHEN is_contacted = 1 THEN 1 ELSE 0 END) as contacted_registrations,
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as registrations_last_30_days,
                    AVG(age) as average_age,
                    COUNT(DISTINCT school_name) as unique_schools
                FROM student_registrations
                WHERE is_active = 1
            ", [now()->subDays(30)]);

            $stat = $stats[0];

            // Age groups with optimized query
            $ageGroups = DB::select("
                SELECT
                    CASE
                        WHEN age <= 12 THEN 'Child (≤12)'
                        WHEN age <= 17 THEN 'Teen (13-17)'
                        WHEN age <= 25 THEN 'Young Adult (18-25)'
                        WHEN age <= 35 THEN 'Adult (26-35)'
                        ELSE 'Mature Adult (36+)'
                    END as age_group,
                    COUNT(*) as count
                FROM student_registrations
                WHERE is_active = 1
                GROUP BY age_group
            ");

            $ageGroupsArray = [];
            foreach ($ageGroups as $group) {
                $ageGroupsArray[$group->age_group] = $group->count;
            }

            return [
                'total_registrations' => (int) $stat->total_registrations,
                'verified_registrations' => (int) $stat->verified_registrations,
                'contacted_registrations' => (int) $stat->contacted_registrations,
                'registrations_last_30_days' => (int) $stat->registrations_last_30_days,
                'average_age' => round($stat->average_age ?? 0, 1),
                'unique_schools' => (int) $stat->unique_schools,
                'age_groups' => $ageGroupsArray,
            ];
        });
    }

    // Optimized analytics with minimal queries
    public static function getDailyRegistrations(int $days = 30): array
    {
        $cacheKey = "daily_registrations_{$days}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($days) {
            // Single optimized query with date formatting
            $results = DB::select("
                SELECT
                    DATE(created_at) as date,
                    COUNT(*) as count
                FROM student_registrations
                WHERE is_active = 1
                  AND created_at >= ?
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ", [now()->subDays($days)]);

            $data = [];
            foreach ($results as $result) {
                $data[$result->date] = (int) $result->count;
            }

            // Fill missing dates with 0
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                if (!isset($data[$date])) {
                    $data[$date] = 0;
                }
            }

            return $data;
        });
    }

    // Optimized interest breakdown
    public static function getInterestBreakdown(): array
    {
        return Cache::remember('interest_breakdown', self::CACHE_TTL, function () {
            // Use JSON extraction for better performance
            return DB::select("
                SELECT
                    JSON_UNQUOTE(JSON_EXTRACT(interests, CONCAT('$[', numbers.n, ']'))) as interest,
                    COUNT(*) as count
                FROM student_registrations
                CROSS JOIN (
                    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3
                    UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7
                ) numbers
                WHERE is_active = 1
                  AND interests IS NOT NULL
                  AND JSON_EXTRACT(interests, CONCAT('$[', numbers.n, ']')) IS NOT NULL
                GROUP BY interest
                ORDER BY count DESC
                LIMIT 10
            ");
        });
    }

    // Clear all caches when new registration is created
    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            Cache::forget(self::CACHE_STATS_KEY);
            Cache::forget(self::CACHE_ANALYTICS_KEY);
            Cache::forget('daily_registrations_30');
            Cache::forget('interest_breakdown');
        });

        static::updated(function ($model) {
            if ($model->isDirty(['is_active', 'is_verified', 'is_contacted'])) {
                Cache::forget(self::CACHE_STATS_KEY);
                Cache::forget(self::CACHE_ANALYTICS_KEY);
            }
        });
    }
}
