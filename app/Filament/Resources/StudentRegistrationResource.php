<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentRegistrationResource\Pages;
use App\Models\StudentRegistration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Model;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class StudentRegistrationResource extends Resource
{
    protected static ?string $model = StudentRegistration::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Student Registrations';
    protected static ?string $modelLabel = 'Student Registration';
    protected static ?string $pluralModelLabel = 'Student Registrations';
    protected static ?string $navigationGroup = 'Student Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Student Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('first_name')
                                    ->required()
                                    ->maxLength(100)
                                    ->autocomplete('given-name'),
                                Forms\Components\TextInput::make('last_name')
                                    ->required()
                                    ->maxLength(100)
                                    ->autocomplete('family-name'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->autocomplete('email'),
                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->required()
                                    ->maxLength(20)
                                    ->autocomplete('tel'),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('age')
                                    ->numeric()
                                    ->required()
                                    ->minValue(5)
                                    ->maxValue(100),
                                Forms\Components\DatePicker::make('date_of_birth')
                                    ->required()
                                    ->native(false)
                                    ->maxDate(now()->subYears(5)),
                                Forms\Components\TextInput::make('location')
                                    ->required()
                                    ->maxLength(200),
                            ]),
                        Forms\Components\TextInput::make('school_name')
                            ->required()
                            ->maxLength(200)
                            ->label('School Name'),
                    ]),

                Forms\Components\Section::make('Parent Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('parent_name')
                                    ->required()
                                    ->maxLength(200)
                                    ->label('Parent/Guardian Name'),
                                Forms\Components\TextInput::make('parent_phone')
                                    ->tel()
                                    ->required()
                                    ->maxLength(20)
                                    ->label('Parent Phone'),
                            ]),
                        Forms\Components\TextInput::make('parent_email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->label('Parent Email'),
                    ]),

                Forms\Components\Section::make('Agricultural Information')
                    ->schema([
                        Forms\Components\Select::make('experience_level')
                            ->required()
                            ->options([
                                'beginner' => 'Beginner - New to farming',
                                'intermediate' => 'Intermediate - Some farming experience',
                                'advanced' => 'Advanced - Experienced farmer',
                                'professional' => 'Professional - Agricultural professional',
                            ])
                            ->label('Experience Level')
                            ->placeholder('Select experience level')
                            ->native(false)
                            ->searchable(false),

                        Forms\Components\CheckboxList::make('interests')
                            ->options([
                                'Crop Production' => 'Crop Production',
                                'Livestock Management' => 'Livestock Management',
                                'Sustainable Farming' => 'Sustainable Farming',
                                'Precision Agriculture' => 'Precision Agriculture',
                                'Hydroponics' => 'Hydroponics',
                                'Organic Farming' => 'Organic Farming',
                                'Agricultural Technology' => 'Agricultural Technology',
                                'Farm Business Management' => 'Farm Business Management',
                                'Poultry Farming' => 'Poultry Farming',
                                'Fish Farming' => 'Fish Farming',
                                'Beekeeping' => 'Beekeeping',
                                'Greenhouse Management' => 'Greenhouse Management',
                            ])
                            ->columns(2)
                            ->label('Areas of Interest')
                            ->searchable(),

                        Forms\Components\Textarea::make('motivation')
                            ->maxLength(2000)
                            ->rows(4)
                            ->label('Motivation/Why interested in agriculture?'),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->default(true)
                                    ->label('Active'),
                                Forms\Components\Toggle::make('is_verified')
                                    ->default(false)
                                    ->label('Verified'),
                                Forms\Components\Toggle::make('is_contacted')
                                    ->default(false)
                                    ->label('Contacted'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                StudentRegistration::query()->where('is_active', true)
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Student Name')
                    ->getStateUsing(fn ($record) => $record->first_name . ' ' . $record->last_name)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name'])
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('age')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('school_name')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state ?? '') > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('parent_name')
                    ->searchable()
                    ->sortable()
                    ->limit(25)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->icon('heroicon-o-map-pin'),

                Tables\Columns\TextColumn::make('experience_level')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'beginner' => 'gray',
                        'intermediate' => 'warning',
                        'advanced' => 'success',
                        'professional' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => match($state) {
                        'beginner' => 'Beginner',
                        'intermediate' => 'Intermediate',
                        'advanced' => 'Advanced',
                        'professional' => 'Professional',
                        default => $state ?? 'Unknown',
                    }),

                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->label('Verified')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_contacted')
                    ->boolean()
                    ->label('Contacted')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->since()
                    ->label('Registered')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('experience_level')
                    ->options([
                        'beginner' => 'Beginner - New to farming',
                        'intermediate' => 'Intermediate - Some farming experience',
                        'advanced' => 'Advanced - Experienced farmer',
                        'professional' => 'Professional - Agricultural professional',
                    ])
                    ->label('Experience Level')
                    ->placeholder('All Experience Levels')
                    ->multiple()
                    ->preload(),

                SelectFilter::make('is_verified')
                    ->options([
                        1 => 'Verified',
                        0 => 'Not Verified',
                    ])
                    ->label('Verification Status')
                    ->placeholder('All Verification Status'),

                SelectFilter::make('is_contacted')
                    ->options([
                        1 => 'Contacted',
                        0 => 'Not Contacted',
                    ])
                    ->label('Contact Status')
                    ->placeholder('All Contact Status'),

                Filter::make('age_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('age_from')
                                    ->numeric()
                                    ->label('Age From')
                                    ->placeholder('Min age'),
                                Forms\Components\TextInput::make('age_to')
                                    ->numeric()
                                    ->label('Age To')
                                    ->placeholder('Max age'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['age_from'],
                                fn (Builder $query, $age): Builder => $query->where('age', '>=', $age),
                            )
                            ->when(
                                $data['age_to'],
                                fn (Builder $query, $age): Builder => $query->where('age', '<=', $age),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['age_from'] ?? null) {
                            $indicators['age_from'] = 'Age from: ' . $data['age_from'];
                        }

                        if ($data['age_to'] ?? null) {
                            $indicators['age_to'] = 'Age to: ' . $data['age_to'];
                        }

                        return $indicators;
                    }),

                Filter::make('recent_registrations')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30)))
                    ->toggle()
                    ->label('Recent Registrations (30 days)'),

                Filter::make('school_filter')
                    ->form([
                        Forms\Components\Select::make('school_name')
                            ->label('School')
                            ->options(function () {
                                return Cache::remember('school_options', 3600, function () {
                                    return StudentRegistration::query()
                                        ->where('is_active', true)
                                        ->whereNotNull('school_name')
                                        ->where('school_name', '!=', '')
                                        ->distinct()
                                        ->pluck('school_name', 'school_name')
                                        ->filter() // Remove empty values
                                        ->sort();
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a school'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['school_name'],
                            fn (Builder $query, $school): Builder => $query->where('school_name', $school),
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver(),
                Tables\Actions\EditAction::make()
                    ->slideOver(),
                Tables\Actions\Action::make('mark_contacted')
                    ->icon('heroicon-o-phone')
                    ->color('success')
                    ->action(function (StudentRegistration $record) {
                        $record->markAsContacted();
                    })
                    ->visible(fn (StudentRegistration $record): bool => !$record->is_contacted)
                    ->label('Mark Contacted')
                    ->tooltip('Mark as Contacted'),

                Tables\Actions\Action::make('mark_verified')
                    ->icon('heroicon-o-check-badge')
                    ->color('warning')
                    ->action(function (StudentRegistration $record) {
                        $record->markAsVerified();
                    })
                    ->visible(fn (StudentRegistration $record): bool => !$record->is_verified)
                    ->label('Mark Verified')
                    ->tooltip('Mark as Verified'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('mark_contacted')
                        ->label('Mark as Contacted')
                        ->icon('heroicon-o-phone')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $count = $records->where('is_contacted', false)->count();
                            $records->where('is_contacted', false)->each->markAsContacted();

                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title("Marked {$count} students as contacted")
                                    ->success()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('mark_verified')
                        ->label('Mark as Verified')
                        ->icon('heroicon-o-check-badge')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $count = $records->where('is_verified', false)->count();
                            $records->where('is_verified', false)->each->markAsVerified();

                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title("Verified {$count} students")
                                    ->success()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('export_excel')
                        ->label('Export to Excel')
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
                            return $this->exportToExcel($records, $data['selected_fields']);
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('export_pdf')
                        ->label('Export to PDF')
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
                            return $this->exportToPdf($records, $data['selected_fields']);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordTitleAttribute('full_name')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Student Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('full_name')
                                    ->label('Full Name')
                                    ->getStateUsing(fn ($record) => $record->first_name . ' ' . $record->last_name)
                                    ->weight(FontWeight::Bold)
                                    ->copyable(),
                                TextEntry::make('age')
                                    ->badge()
                                    ->color('success'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('email')
                                    ->copyable()
                                    ->icon('heroicon-o-envelope'),
                                TextEntry::make('phone')
                                    ->copyable()
                                    ->icon('heroicon-o-phone'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('date_of_birth')
                                    ->date('M d, Y')
                                    ->icon('heroicon-o-calendar'),
                                TextEntry::make('location')
                                    ->icon('heroicon-o-map-pin')
                                    ->copyable(),
                            ]),
                        TextEntry::make('school_name')
                            ->icon('heroicon-o-academic-cap')
                            ->copyable(),
                    ]),

                Section::make('Parent Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('parent_name')
                                    ->label('Parent/Guardian Name')
                                    ->weight(FontWeight::Bold)
                                    ->copyable(),
                                TextEntry::make('parent_phone')
                                    ->copyable()
                                    ->icon('heroicon-o-phone'),
                            ]),
                        TextEntry::make('parent_email')
                            ->copyable()
                            ->icon('heroicon-o-envelope'),
                    ]),

                Section::make('Agricultural Information')
                    ->schema([
                        TextEntry::make('experience_level')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'beginner' => 'gray',
                                'intermediate' => 'warning',
                                'advanced' => 'success',
                                'professional' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state) => match($state) {
                                'beginner' => 'Beginner - New to farming',
                                'intermediate' => 'Intermediate - Some farming experience',
                                'advanced' => 'Advanced - Experienced farmer',
                                'professional' => 'Professional - Agricultural professional',
                                default => $state ?? 'Unknown',
                            }),

                        TextEntry::make('interests')
                            ->badge()
                            ->separator(',')
                            ->color('info')
                            ->columnSpanFull()
                            ->formatStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return $state;
                                }
                                if (is_string($state)) {
                                    return json_decode($state, true) ?? [$state];
                                }
                                return [];
                            }),

                        TextEntry::make('motivation')
                            ->columnSpanFull()
                            ->prose()
                            ->markdown()
                            ->placeholder('No motivation provided'),
                    ]),

                Section::make('Status & Tracking')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('is_active')
                                    ->label('Active')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),

                                TextEntry::make('is_verified')
                                    ->label('Verified')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'warning')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),

                                TextEntry::make('is_contacted')
                                    ->label('Contacted')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Registration Date')
                                    ->dateTime('M d, Y - H:i')
                                    ->since()
                                    ->icon('heroicon-o-calendar'),

                                TextEntry::make('days_since_registration')
                                    ->label('Days Since Registration')
                                    ->getStateUsing(fn ($record) => $record->created_at->diffInDays(now()))
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentRegistrations::route('/'),
            'create' => Pages\CreateStudentRegistration::route('/create'),
            'view' => Pages\ViewStudentRegistration::route('/{record}'),
            'edit' => Pages\EditStudentRegistration::route('/{record}/edit'),
        ];
    }

    // Optimized navigation badge with caching
    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('student_nav_badge', 300, function () {
            return static::getModel()::where('is_active', true)->count();
        });
    }

    // Optimized global search
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->select(['id', 'first_name', 'last_name', 'email', 'school_name', 'location', 'age', 'parent_name', 'created_at'])
            ->where('is_active', true);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email', 'parent_name', 'school_name', 'location'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'School' => $record->school_name ?? 'No school',
            'Location' => $record->location ?? 'No location',
            'Age' => ($record->age ?? 0) . ' years',
            'Parent' => $record->parent_name ?? 'No parent name',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return ($record->first_name ?? '') . ' ' . ($record->last_name ?? '');
    }

    // Export methods
    private function exportToExcel(Collection $records, array $selectedFields = [])
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Define all possible fields and their headers
        $fieldMappings = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'full_name' => 'Full Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'age' => 'Age',
            'date_of_birth' => 'Date of Birth',
            'location' => 'Location',
            'school_name' => 'School Name',
            'parent_name' => 'Parent Name',
            'parent_phone' => 'Parent Phone',
            'parent_email' => 'Parent Email',
            'experience_level' => 'Experience Level',
            'interests' => 'Interests',
            'motivation' => 'Motivation',
            'is_active' => 'Active',
            'is_verified' => 'Verified',
            'is_contacted' => 'Contacted',
            'created_at' => 'Registration Date'
        ];

        // Use selected fields or default to all fields
        $fieldsToExport = !empty($selectedFields) ? $selectedFields : array_keys($fieldMappings);
        $headers = array_map(fn($field) => $fieldMappings[$field], $fieldsToExport);

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Data
        $row = 2;
        foreach ($records as $record) {
            $col = 'A';
            foreach ($fieldsToExport as $field) {
                $value = $this->getFieldValue($record, $field);
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        $lastCol = chr(ord('A') + count($fieldsToExport) - 1);
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'student-registrations-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
        $path = storage_path('app/public/exports/' . $filename);

        // Ensure directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer->save($path);

        return response()->download($path)->deleteFileAfterSend();
    }

    private function exportToPdf(Collection $records, array $selectedFields = [])
    {
        // Define all possible fields and their headers
        $fieldMappings = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'full_name' => 'Full Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'age' => 'Age',
            'date_of_birth' => 'Date of Birth',
            'location' => 'Location',
            'school_name' => 'School Name',
            'parent_name' => 'Parent Name',
            'parent_phone' => 'Parent Phone',
            'parent_email' => 'Parent Email',
            'experience_level' => 'Experience Level',
            'interests' => 'Interests',
            'motivation' => 'Motivation',
            'is_active' => 'Active',
            'is_verified' => 'Verified',
            'is_contacted' => 'Contacted',
            'created_at' => 'Registration Date'
        ];

        // Use selected fields or default to all fields
        $fieldsToExport = !empty($selectedFields) ? $selectedFields : array_keys($fieldMappings);

        $data = [];
        foreach ($records as $record) {
            $rowData = [];
            foreach ($fieldsToExport as $field) {
                $rowData[$field] = $this->getFieldValue($record, $field);
            }
            $data[] = $rowData;
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.student-registrations', [
            'records' => $data,
            'fields' => $fieldsToExport,
            'headers' => array_map(fn($field) => $fieldMappings[$field], $fieldsToExport),
            'exportDate' => now()->format('Y-m-d H:i:s'),
            'totalRecords' => $records->count()
        ]);

        $filename = 'student-registrations-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    private function formatExperienceLevel(?string $level): string
    {
        return match($level) {
            'beginner' => 'Beginner - New to farming',
            'intermediate' => 'Intermediate - Some farming experience',
            'advanced' => 'Advanced - Experienced farmer',
            'professional' => 'Professional - Agricultural professional',
            default => $level ?? 'Unknown',
        };
    }

    private function formatInterests($interests): string
    {
        if (is_array($interests)) {
            return implode(', ', $interests);
        }
        if (is_string($interests)) {
            $decoded = json_decode($interests, true);
            return is_array($decoded) ? implode(', ', $decoded) : $interests;
        }
        return '';
    }

    private function getFieldValue($record, string $field): string
    {
        return match($field) {
            'first_name' => $record->first_name ?? '',
            'last_name' => $record->last_name ?? '',
            'full_name' => ($record->first_name ?? '') . ' ' . ($record->last_name ?? ''),
            'email' => $record->email ?? '',
            'phone' => $record->phone ?? '',
            'age' => (string) ($record->age ?? ''),
            'date_of_birth' => $record->date_of_birth ? $record->date_of_birth->format('Y-m-d') : '',
            'location' => $record->location ?? '',
            'school_name' => $record->school_name ?? '',
            'parent_name' => $record->parent_name ?? '',
            'parent_phone' => $record->parent_phone ?? '',
            'parent_email' => $record->parent_email ?? '',
            'experience_level' => $this->formatExperienceLevel($record->experience_level),
            'interests' => $this->formatInterests($record->interests),
            'motivation' => strip_tags($record->motivation ?? ''),
            'is_active' => $record->is_active ? 'Yes' : 'No',
            'is_verified' => $record->is_verified ? 'Yes' : 'No',
            'is_contacted' => $record->is_contacted ? 'Yes' : 'No',
            'created_at' => $record->created_at ? $record->created_at->format('Y-m-d H:i:s') : '',
            default => '',
        };
    }
}
