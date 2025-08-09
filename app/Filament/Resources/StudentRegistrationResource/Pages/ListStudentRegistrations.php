<?php

namespace App\Filament\Resources\StudentRegistrationResource\Pages;

use App\Filament\Resources\StudentRegistrationResource;
use App\Filament\Widgets\StatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListStudentRegistrations extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = StudentRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
        ];
    }
}
