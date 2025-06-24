<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class StudentRegistration extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'student_registrations';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'location',
        'experience_level',
        'interests',
        'motivation',
        'ip_address',
        'user_agent',
        'is_active',
        'is_verified',
        'is_contacted',
    ];

    // Laravel 11 Casts
    protected function casts(): array
    {
        return [
            'interests' => 'array',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'is_contacted' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'ip_address',
        'user_agent',
    ];

    // Constants for experience levels
    const EXPERIENCE_LEVELS = [
        'beginner' => 'Beginner - New to farming',
        'intermediate' => 'Intermediate - Some farming experience',
        'advanced' => 'Advanced - Experienced farmer',
        'professional' => 'Professional - Agricultural professional',
    ];

    const VALID_INTERESTS = [
        'Crop Production',
        'Livestock Management',
        'Sustainable Farming',
        'Precision Agriculture',
        'Hydroponics',
        'Organic Farming',
        'Agricultural Technology',
        'Farm Business Management',
    ];

    // Laravel 11 Attribute Accessors
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}",
        );
    }

    protected function daysSinceRegistration(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at->diffInDays(now()),
        );
    }

    // Laravel 11 Attribute Mutators
    protected function firstName(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => ucfirst(strtolower(trim($value))),
        );
    }

    protected function lastName(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => ucfirst(strtolower(trim($value))),
        );
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower(trim($value)),
        );
    }

    // Query Scopes
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeVerified(Builder $query): void
    {
        $query->where('is_verified', true);
    }

    public function scopeContacted(Builder $query): void
    {
        $query->where('is_contacted', true);
    }

    public function scopeRecentRegistrations(Builder $query, int $days = 30): void
    {
        $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByExperience(Builder $query, string $experience): void
    {
        $query->where('experience_level', $experience);
    }

    public function scopeByLocation(Builder $query, string $location): void
    {
        $query->where('location', 'like', "%{$location}%");
    }

    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('location', 'like', "%{$search}%");
        });
    }

    // Helper Methods
    public function markAsContacted(): bool
    {
        return $this->update(['is_contacted' => true]);
    }

    public function markAsVerified(): bool
    {
        return $this->update(['is_verified' => true]);
    }

    public function hasInterest(string $interest): bool
    {
        return in_array($interest, $this->interests ?? []);
    }

    // Static Methods
    public static function getExperienceLevelLabel(string $level): string
    {
        return self::EXPERIENCE_LEVELS[$level] ?? $level;
    }

    public static function getRegistrationStats(): array
    {
        $total = self::active()->count();
        $verified = self::active()->verified()->count();
        $contacted = self::active()->contacted()->count();
        $recent = self::active()->recentRegistrations(30)->count();

        return [
            'total_registrations' => $total,
            'verified_registrations' => $verified,
            'contacted_registrations' => $contacted,
            'registrations_last_30_days' => $recent,
        ];
    }
}
