<?php

namespace App\Filament\Widgets;

use App\Models\StudentRegistration;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class RegistrationChart extends ChartWidget
{
    protected static ?string $heading = 'Student Registrations Trend';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        // Get daily registrations for the last 30 days
        $dailyRegistrations = collect(range(29, 0))
            ->map(function ($daysAgo) {
                $date = Carbon::now()->subDays($daysAgo);
                return [
                    'date' => $date->format('M d'),
                    'count' => StudentRegistration::active()
                        ->whereDate('created_at', $date)
                        ->count(),
                ];
            });

        // Get experience level breakdown
        $experienceBreakdown = StudentRegistration::active()
            ->selectRaw('experience_level, COUNT(*) as count')
            ->groupBy('experience_level')
            ->pluck('count', 'experience_level')
            ->map(fn($count, $level) => [
                'label' => StudentRegistration::getExperienceLevelLabel($level),
                'count' => $count
            ]);

        // Get age group breakdown
        $ageBreakdown = StudentRegistration::active()
            ->selectRaw('
                CASE
                    WHEN age <= 12 THEN "Child (≤12)"
                    WHEN age <= 17 THEN "Teen (13-17)"
                    WHEN age <= 25 THEN "Young Adult (18-25)"
                    WHEN age <= 35 THEN "Adult (26-35)"
                    ELSE "Mature Adult (36+)"
                END as age_group,
                COUNT(*) as count
            ')
            ->groupBy('age_group')
            ->pluck('count', 'age_group');

        return [
            'datasets' => [
                [
                    'label' => 'Daily Registrations',
                    'data' => $dailyRegistrations->pluck('count')->toArray(),
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $dailyRegistrations->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }
}
