<?php

namespace App\Filament\Resources\StudentRegistrationResource\Pages;

use App\Filament\Resources\StudentRegistrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentRegistration extends ViewRecord
{
    protected static string $resource = StudentRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('mark_contacted')
                ->icon('heroicon-o-phone')
                ->color('success')
                ->action(function () {
                    $this->record->markAsContacted();
                    $this->refreshFormData(['is_contacted']);
                })
                ->visible(fn (): bool => !$this->record->is_contacted)
                ->label('Mark as Contacted'),

            Actions\Action::make('mark_verified')
                ->icon('heroicon-o-check-badge')
                ->color('warning')
                ->action(function () {
                    $this->record->markAsVerified();
                    $this->refreshFormData(['is_verified']);
                })
                ->visible(fn (): bool => !$this->record->is_verified)
                ->label('Mark as Verified'),
        ];
    }
}
