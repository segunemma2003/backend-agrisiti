<?php

namespace App\Filament\Widgets;

use App\Models\StudentRegistration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $stats = StudentRegistration::getRegistrationStats();

        $totalStudents = $stats['total_registrations'];
        $verifiedStudents = $stats['verified_registrations'];
        $contactedStudents = $stats['contacted_registrations'];
        $recentRegistrations = $stats['registrations_last_30_days'];
        $averageAge = $stats['average_age'];

        // Calculate growth percentage (comparing last 30 days vs previous 30 days)
        $previousPeriod = StudentRegistration::active()
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->count();

        $growthPercentage = $previousPeriod > 0
            ? round((($recentRegistrations - $previousPeriod) / $previousPeriod) * 100, 1)
            : 0;

        // Calculate verification rate
        $verificationRate = $totalStudents > 0
            ? round(($verifiedStudents / $totalStudents) * 100, 1)
            : 0;

        // Calculate contact rate
        $contactRate = $totalStudents > 0
            ? round(($contactedStudents / $totalStudents) * 100, 1)
            : 0;

        return [
            Stat::make('Total Students', $totalStudents)
                ->description('All registered students')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3, 6, 8, 10, 12]),

            Stat::make('New This Month', $recentRegistrations)
                ->description($growthPercentage >= 0 ? "+{$growthPercentage}% from last month" : "{$growthPercentage}% from last month")
                ->descriptionIcon($growthPercentage >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($growthPercentage >= 0 ? 'success' : 'danger')
                ->chart([3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, $recentRegistrations]),

            Stat::make('Verified Students', $verifiedStudents)
                ->description("{$verificationRate}% verification rate")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('warning')
                ->chart([2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, $verifiedStudents]),

            Stat::make('Contacted Students', $contactedStudents)
                ->description("{$contactRate}% contact rate")
                ->descriptionIcon('heroicon-m-phone')
                ->color('info')
                ->chart([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, $contactedStudents]),

            Stat::make('Average Age', $averageAge . ' years')
                ->description('Average student age')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('gray'),

            Stat::make('Unique Schools', StudentRegistration::active()->distinct('school_name')->count())
                ->description('Different schools represented')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),
        ];
    }
}
