<?php

namespace App\Filament\Widgets;

use App\Models\StudentRegistration;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentRegistrations extends BaseWidget
{
    protected static ?string $heading = 'Recent Student Registrations';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StudentRegistration::query()
                    ->active()
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Student Name')
                    ->searchable(['first_name', 'last_name'])
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('age')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('school_name')
                    ->limit(25)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 25 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('location')
                    ->limit(20)
                    ->icon('heroicon-o-map-pin'),

                Tables\Columns\TextColumn::make('experience_level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'beginner' => 'gray',
                        'intermediate' => 'warning',
                        'advanced' => 'success',
                        'professional' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'beginner' => 'Beginner',
                        'intermediate' => 'Intermediate',
                        'advanced' => 'Advanced',
                        'professional' => 'Professional',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->label('Verified'),

                Tables\Columns\IconColumn::make('is_contacted')
                    ->boolean()
                    ->label('Contacted'),

                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->label('Registered')
                    ->icon('heroicon-o-clock'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (StudentRegistration $record): string => route('filament.admin.resources.student-registrations.view', $record))
                    ->icon('heroicon-o-eye'),

                Tables\Actions\Action::make('mark_contacted')
                    ->icon('heroicon-o-phone')
                    ->color('success')
                    ->action(function (StudentRegistration $record) {
                        $record->markAsContacted();
                    })
                    ->visible(fn (StudentRegistration $record): bool => !$record->is_contacted)
                    ->tooltip('Mark as Contacted'),

                Tables\Actions\Action::make('mark_verified')
                    ->icon('heroicon-o-check-badge')
                    ->color('warning')
                    ->action(function (StudentRegistration $record) {
                        $record->markAsVerified();
                    })
                    ->visible(fn (StudentRegistration $record): bool => !$record->is_verified)
                    ->tooltip('Mark as Verified'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }
}
