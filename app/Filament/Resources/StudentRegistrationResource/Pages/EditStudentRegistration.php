<?php

namespace App\Filament\Resources\StudentRegistrationResource\Pages;

use App\Filament\Resources\StudentRegistrationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentRegistration extends EditRecord
{
    protected static string $resource = StudentRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
