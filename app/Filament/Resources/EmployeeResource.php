<?php
// app/Filament/Resources/EmployeeResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Colors\Color;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'بەڕێوەبردنی کارمەندان';
    protected static ?string $modelLabel = 'کارمەند';
    protected static ?string $pluralModelLabel = 'کارمەندان';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری کارمەند')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('ناو')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('position')
                            ->label('پلە')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('ژمارە مۆبایل')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('salary')
                            ->label('مووچە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار'),
                        Forms\Components\DatePicker::make('hire_date')
                            ->label('ڕێکەوتی دەستبەکاربوون')
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('چالاکە')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('ناو')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium),

                Tables\Columns\TextColumn::make('position')
                    ->label('پلە')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('مۆبایل')
                    ->searchable()
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('salary')
                    ->label('مووچە')
                    ->money('IQD')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('hire_date')
                    ->label('ڕێکەوتی دەستبەکاربوون')
                    ->date('Y/m/d')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('چالاک')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('years_of_service')
                    ->label('ماوەی کارکردن')
                    ->getStateUsing(function ($record) {
                        $years = now()->diffInYears($record->hire_date);
                        $months = now()->diffInMonths($record->hire_date) % 12;

                        if ($years > 0) {
                            return $years . ' ساڵ و ' . $months . ' مانگ';
                        }
                        return $months . ' مانگ';
                    })
                    ->badge()
                    ->color(fn ($state) =>
                        str_contains($state, 'ساڵ') ? 'success' : 'warning'
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('بەرواری تۆمارکردن')
                    ->date('Y/m/d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            // **فلتەرە پڕۆفیشناڵەکان**
            ->filters([
                // فلتەری دۆخی چالاکی
                SelectFilter::make('is_active')
                    ->label('دۆخی چالاکی')
                    ->options([
                        '1' => 'چالاک',
                        '0' => 'ناچالاک',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        return $query->where('is_active', $data['value']);
                    })
                    ->indicator('چالاکی')
                    ->placeholder('هەموو')
                    ->columnSpan(1),

                // فلتەری پلە (position)
                SelectFilter::make('position')
                    ->label('پلەی کار')
                    ->options(function () {
                        return Employee::distinct()
                            ->pluck('position', 'position')
                            ->toArray();
                    })
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->indicator('پلە')
                    ->placeholder('هەموو پلەکان')
                    ->columnSpan(2),

                // فلتەری مەودای مووچە
                Filter::make('salary_range')
                    ->label('مەودای مووچە')
                    ->form([
                        TextInput::make('min_salary')
                            ->label('کەمترین مووچە')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('٢٥٠٠٠٠'),
                        TextInput::make('max_salary')
                            ->label('زۆرترین مووچە')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١٠٠٠٠٠٠'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['min_salary'],
                                fn ($q) => $q->where('salary', '>=', $data['min_salary'])
                            )
                            ->when(
                                $data['max_salary'],
                                fn ($q) => $q->where('salary', '<=', $data['max_salary'])
                            );
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['min_salary'] ?? null) {
                            $indicators[] = 'کەمتر نییە لە ' . number_format($data['min_salary']) . ' د.ع';
                        }
                        if ($data['max_salary'] ?? null) {
                            $indicators[] = 'زیاتر نییە لە ' . number_format($data['max_salary']) . ' د.ع';
                        }
                        return $indicators ? 'مەودای مووچە: ' . implode(' و ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری ئاستی مووچە
                SelectFilter::make('salary_level')
                    ->label('ئاستی مووچە')
                    ->options([
                        'high' => 'مووچەی بەرز (> ١٠٠٠٠٠٠)',
                        'medium' => 'مووچەی مامناوەند (٥٠٠٠٠٠ - ١٠٠٠٠٠٠)',
                        'low' => 'مووچەی کەم (< ٥٠٠٠٠٠)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match($data['value']) {
                            'high' => $query->where('salary', '>', 1000000),
                            'medium' => $query->whereBetween('salary', [500000, 1000000]),
                            'low' => $query->where('salary', '<', 500000),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data) {
                        return match($data['value'] ?? null) {
                            'high' => 'مووچەی بەرز',
                            'medium' => 'مووچەی مامناوەند',
                            'low' => 'مووچەی کەم',
                            default => null,
                        };
                    })
                    ->columnSpan(1),

                // فلتەری مەودای بەرواری دەستبەکاربوون
                Filter::make('hire_date_range')
                    ->label('مەودای دەستبەکاربوون')
                    ->form([
                        DatePicker::make('from')
                            ->label('لە')
                            ->placeholder('YYYY-MM-DD'),
                        DatePicker::make('until')
                            ->label('تا')
                            ->placeholder('YYYY-MM-DD'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn ($q) => $q->whereDate('hire_date', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn ($q) => $q->whereDate('hire_date', '<=', $data['until'])
                            );
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'لە ' . \Carbon\Carbon::parse($data['from'])->format('Y/m/d');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'تا ' . \Carbon\Carbon::parse($data['until'])->format('Y/m/d');
                        }
                        return $indicators ? 'بەرواری دەستبەکاربوون: ' . implode(' - ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری ماوەی کارکردن
                SelectFilter::make('service_length')
                    ->label('ماوەی کارکردن')
                    ->options([
                        'new' => 'کەمتر لە ١ ساڵ',
                        'one_to_three' => '١ - ٣ ساڵ',
                        'three_to_five' => '٣ - ٥ ساڵ',
                        'five_plus' => 'زیاتر لە ٥ ساڵ',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        $now = now();
                        return match($data['value']) {
                            'new' => $query->whereRaw("strftime('%Y', 'now') - strftime('%Y', hire_date) < 1"),
                            'one_to_three' => $query->whereRaw("strftime('%Y', 'now') - strftime('%Y', hire_date) BETWEEN 1 AND 3"),
                            'three_to_five' => $query->whereRaw("strftime('%Y', 'now') - strftime('%Y', hire_date) BETWEEN 3 AND 5"),
                            'five_plus' => $query->whereRaw("strftime('%Y', 'now') - strftime('%Y', hire_date) > 5"),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data) {
                        return match($data['value'] ?? null) {
                            'new' => 'کارمەندی نوێ',
                            'one_to_three' => '١ - ٣ ساڵ',
                            'three_to_five' => '٣ - ٥ ساڵ',
                            'five_plus' => 'زیاتر لە ٥ ساڵ',
                            default => null,
                        };
                    })
                    ->columnSpan(1),

                // فلتەری پێشگری مۆبایل
                Filter::make('phone_prefix')
                    ->label('پێشگری مۆبایل')
                    ->form([
                        TextInput::make('prefix')
                            ->label('پێشگر')
                            ->placeholder('٠٧٥٠، ٠٧٧٠، ...')
                            ->maxLength(4),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['prefix'],
                                fn ($q) => $q->where('phone', 'LIKE', $data['prefix'] . '%')
                            );
                    })
                    ->indicateUsing(function (array $data) {
                        if ($data['prefix'] ?? null) {
                            return 'پێشگری مۆبایل: ' . $data['prefix'];
                        }
                        return null;
                    }),

                // فلتەری کارمەندانی ئەمڕۆ
                Filter::make('today_employees')
                    ->label('کارمەندانی ئەمڕۆ (تۆمارکراو)')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('created_at', today()))
                    ->indicator('ئەمڕۆ تۆمارکراوە'),

                // فلتەری کارمەندانی ئەم مانگە
                Filter::make('this_month')
                    ->label('کارمەندانی ئەم مانگە (تۆمارکراو)')
                    ->toggle()
                    ->query(fn ($query) => $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year))
                    ->indicator('ئەم مانگە تۆمارکراوە'),

                // فلتەری دەستبەکاربوونی ئەمساڵ
                Filter::make('hired_this_year')
                    ->label('دەستبەکاربوونی ئەمساڵ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereYear('hire_date', now()->year))
                    ->indicator('ئەمساڵ دەستبەکاربوون'),
            ])

            // ڕێکخستنی فلتەرەکان
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->filtersFormWidth('lg')
            ->persistFiltersInSession()

            // دوگمەی فلتەر
            ->filtersTriggerAction(
                fn ($action) => $action
                    ->button()
                    ->label('فلتەری پێشکەوتوو')
                    ->icon('heroicon-m-funnel')
                    ->color('gray')
                    ->size('sm')
            )

            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('بینین')
                        ->icon('heroicon-m-eye')
                        ->color('info'),

                    Tables\Actions\EditAction::make()
                        ->label('دەستکاری')
                        ->icon('heroicon-m-pencil')
                        ->color('warning'),

                    Tables\Actions\DeleteAction::make()
                        ->label('سڕینەوە')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->modalHeading('سڕینەوەی کارمەند')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم کارمەندە؟')
                        ->modalSubmitActionLabel('بەڵێ، بسڕەوە')
                        ->modalCancelActionLabel('نەخێر'),
                ])
                ->label('کردارەکان')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->size('sm'),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('سڕینەوەی دیاریکراوەکان')
                        ->modalHeading('سڕینەوەی کارمەندە دیاریکراوەکان')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم کارمەندانە؟')
                        ->modalSubmitActionLabel('بەڵێ، بیانسڕەوە')
                        ->modalCancelActionLabel('نەخێر'),

                    Tables\Actions\BulkAction::make('mark_active')
                        ->label('کردن بە چالاک')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('mark_inactive')
                        ->label('کردن بە ناچالاک')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])

            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('هیچ کارمەندێک نییە')
            ->emptyStateDescription('یەکەم کارمەند دروست بکە بۆ دەستپێکردن')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('دروستکردنی کارمەند')
                    ->icon('heroicon-m-plus'),
            ])

            ->defaultSort('name')
            ->striped()
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('is_active', true)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
