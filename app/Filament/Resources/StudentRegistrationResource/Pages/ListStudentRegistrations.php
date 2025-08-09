<?php

namespace App\Filament\Resources\StudentRegistrationResource\Pages;

use App\Filament\Resources\StudentRegistrationResource;
use App\Filament\Widgets\StatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

class ListStudentRegistrations extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = StudentRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('export_all_excel')
                ->label('Export All to Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\CheckboxList::make('selected_fields')
                        ->label('Select Fields to Export')
                        ->options([
                            'first_name' => 'First Name',
                            'last_name' => 'Last Name',
                            'full_name' => 'Full Name',
                            'email' => 'Email',
                            'phone' => 'Phone',
                            'age' => 'Age',
                            'date_of_birth' => 'Date of Birth',
                            'location' => 'Location',
                            'school_name' => 'School Name',
                            'parent_name' => 'Parent/Guardian Name',
                            'parent_phone' => 'Parent Phone',
                            'parent_email' => 'Parent Email',
                            'experience_level' => 'Experience Level',
                            'interests' => 'Areas of Interest',
                            'motivation' => 'Motivation',
                            'is_active' => 'Active Status',
                            'is_verified' => 'Verified Status',
                            'is_contacted' => 'Contacted Status',
                            'created_at' => 'Registration Date',
                        ])
                        ->default([
                            'first_name', 'last_name', 'email', 'phone', 'age',
                            'school_name', 'location', 'parent_name', 'experience_level'
                        ])
                        ->columns(2)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $records = \App\Models\StudentRegistration::where('is_active', true)->get();
                    return StudentRegistrationResource::exportToExcel($records, $data['selected_fields']);
                }),
            Actions\Action::make('export_all_pdf')
                ->label('Export All to CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->form([
                    \Filament\Forms\Components\CheckboxList::make('selected_fields')
                        ->label('Select Fields to Export')
                        ->options([
                            'first_name' => 'First Name',
                            'last_name' => 'Last Name',
                            'full_name' => 'Full Name',
                            'email' => 'Email',
                            'phone' => 'Phone',
                            'age' => 'Age',
                            'date_of_birth' => 'Date of Birth',
                            'location' => 'Location',
                            'school_name' => 'School Name',
                            'parent_name' => 'Parent/Guardian Name',
                            'parent_phone' => 'Parent Phone',
                            'parent_email' => 'Parent Email',
                            'experience_level' => 'Experience Level',
                            'interests' => 'Areas of Interest',
                            'motivation' => 'Motivation',
                            'is_active' => 'Active Status',
                            'is_verified' => 'Verified Status',
                            'is_contacted' => 'Contacted Status',
                            'created_at' => 'Registration Date',
                        ])
                        ->default([
                            'first_name', 'last_name', 'email', 'phone', 'age',
                            'school_name', 'location', 'parent_name', 'experience_level'
                        ])
                        ->columns(2)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $records = \App\Models\StudentRegistration::where('is_active', true)->get();
                    return StudentRegistrationResource::exportToPdf($records, $data['selected_fields']);
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('export_selected_excel')
                ->label('Export Selected to Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\CheckboxList::make('selected_fields')
                        ->label('Select Fields to Export')
                        ->options([
                            'first_name' => 'First Name',
                            'last_name' => 'Last Name',
                            'full_name' => 'Full Name',
                            'email' => 'Email',
                            'phone' => 'Phone',
                            'age' => 'Age',
                            'date_of_birth' => 'Date of Birth',
                            'location' => 'Location',
                            'school_name' => 'School Name',
                            'parent_name' => 'Parent/Guardian Name',
                            'parent_phone' => 'Parent Phone',
                            'parent_email' => 'Parent Email',
                            'experience_level' => 'Experience Level',
                            'interests' => 'Areas of Interest',
                            'motivation' => 'Motivation',
                            'is_active' => 'Active Status',
                            'is_verified' => 'Verified Status',
                            'is_contacted' => 'Contacted Status',
                            'created_at' => 'Registration Date',
                        ])
                        ->default([
                            'first_name', 'last_name', 'email', 'phone', 'age',
                            'school_name', 'location', 'parent_name', 'experience_level'
                        ])
                        ->columns(2)
                        ->required(),
                ])
                ->action(function (Collection $records, array $data) {
                    return StudentRegistrationResource::exportToExcel($records, $data['selected_fields']);
                })
                ->deselectRecordsAfterCompletion(),
            BulkAction::make('export_selected_pdf')
                ->label('Export Selected to CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->form([
                    \Filament\Forms\Components\CheckboxList::make('selected_fields')
                        ->label('Select Fields to Export')
                        ->options([
                            'first_name' => 'First Name',
                            'last_name' => 'Last Name',
                            'full_name' => 'Full Name',
                            'email' => 'Email',
                            'phone' => 'Phone',
                            'age' => 'Age',
                            'date_of_birth' => 'Date of Birth',
                            'location' => 'Location',
                            'school_name' => 'School Name',
                            'parent_name' => 'Parent/Guardian Name',
                            'parent_phone' => 'Parent Phone',
                            'parent_email' => 'Parent Email',
                            'experience_level' => 'Experience Level',
                            'interests' => 'Areas of Interest',
                            'motivation' => 'Motivation',
                            'is_active' => 'Active Status',
                            'is_verified' => 'Verified Status',
                            'is_contacted' => 'Contacted Status',
                            'created_at' => 'Registration Date',
                        ])
                        ->default([
                            'first_name', 'last_name', 'email', 'phone', 'age',
                            'school_name', 'location', 'parent_name', 'experience_level'
                        ])
                        ->columns(2)
                        ->required(),
                ])
                ->action(function (Collection $records, array $data) {
                    return StudentRegistrationResource::exportToPdf($records, $data['selected_fields']);
                })
                ->deselectRecordsAfterCompletion(),
        ];
    }
}
