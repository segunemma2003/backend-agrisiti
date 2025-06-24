<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationAnalytics extends Model
{
    use HasFactory;

    protected $table = 'registration_analytics';

    protected $fillable = [
        'date',
        'total_registrations',
        'verified_registrations',
        'contacted_registrations',
        'top_interests',
        'top_locations',
        'experience_breakdown',
    ];

    // Laravel 11 Casts
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'top_interests' => 'array',
            'top_locations' => 'array',
            'experience_breakdown' => 'array',
        ];
    }
}
