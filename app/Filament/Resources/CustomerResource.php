<?php
// app/Filament/Resources/CustomerResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Indicator;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'بەشی کڕیاران';
    protected static ?string $modelLabel = 'کڕیار';
    protected static ?string $pluralModelLabel = 'کڕیاران';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری کەسی')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('ناوی تەواو')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('ژمارە مۆبایل')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('identity_number')
                            ->label('ژمارەی ناسنامە')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('vehicle_number')
                            ->label('ژمارەی ئۆتۆمۆبیل')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('address')
                            ->label('ناونیشان')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('زانیاری قەرز')
                    ->schema([
                        Forms\Components\TextInput::make('total_credit')
                            ->label('کۆی قەرز')
                            ->numeric()
                            ->disabled()
                            ->prefix('دینار'),
                        Forms\Components\TextInput::make('total_paid')
                            ->label('کۆی پارەدان')
                            ->numeric()
                            ->disabled()
                            ->prefix('دینار'),
                        Forms\Components\TextInput::make('current_debt')
                            ->label('قەرزی ماوە')
                            ->numeric()
                            ->disabled()
                            ->prefix('دینار'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('چالاکە')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('تێبینی')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('تێبینی')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
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

                Tables\Columns\TextColumn::make('phone')
                    ->label('مۆبایل')
                    ->searchable()
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('current_debt')
                    ->label('قەرزی ماوە')
                    ->money('IQD')
                    ->badge()
                    ->color(fn ($record): string => $record->debt_color)
                    ->weight('bold')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium),

                Tables\Columns\TextColumn::make('total_credit')
                    ->label('کۆی قەرز')
                    ->money('IQD')
                    ->toggleable()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('total_paid')
                    ->label('کۆی پارەدان')
                    ->money('IQD')
                    ->toggleable()
                    ->color('success'),

                Tables\Columns\TextColumn::make('debt_ratio')
                    ->label('ڕێژەی قەرز')
                    ->getStateUsing(function ($record) {
                        if ($record->total_credit == 0) return '٠٪';
                        $paid = ($record->total_paid / $record->total_credit) * 100;
                        return number_format($paid, 1) . '٪';
                    })
                    ->badge()
                    ->color(fn ($state): string =>
                        floatval($state) > 70 ? 'success' : (floatval($state) > 30 ? 'warning' : 'danger')
                    )
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('چالاک')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

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

                // فلتەری قەرزدار
                SelectFilter::make('debt_status')
                    ->label('دۆخی قەرز')
                    ->options([
                        'has_debt' => 'قەرزدار',
                        'no_debt' => 'بێ قەرز',
                        'high_debt' => 'قەرزی زۆر (> ١ ملیۆن)',
                        'medium_debt' => 'قەرزی مامناوەند (١٠٠ هەزار - ١ ملیۆن)',
                        'low_debt' => 'قەرزی کەم (< ١٠٠ هەزار)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match($data['value']) {
                            'has_debt' => $query->where('current_debt', '>', 0),
                            'no_debt' => $query->where('current_debt', '<=', 0),
                            'high_debt' => $query->where('current_debt', '>', 1000000),
                            'medium_debt' => $query->whereBetween('current_debt', [100000, 1000000]),
                            'low_debt' => $query->where('current_debt', '<', 100000)->where('current_debt', '>', 0),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data) {
                        return match($data['value'] ?? null) {
                            'has_debt' => 'قەرزدار',
                            'no_debt' => 'بێ قەرز',
                            'high_debt' => 'قەرزی زۆر',
                            'medium_debt' => 'قەرزی مامناوەند',
                            'low_debt' => 'قەرزی کەم',
                            default => null,
                        };
                    })
                    ->columnSpan(1),

                // فلتەری مەودای قەرز
                Filter::make('debt_range')
                    ->label('مەودای قەرز')
                    ->form([
                        TextInput::make('min_debt')
                            ->label('کەمترین قەرز')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١٠٠٠٠٠'),
                        TextInput::make('max_debt')
                            ->label('زۆرترین قەرز')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١٠٠٠٠٠٠'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['min_debt'],
                                fn ($q) => $q->where('current_debt', '>=', $data['min_debt'])
                            )
                            ->when(
                                $data['max_debt'],
                                fn ($q) => $q->where('current_debt', '<=', $data['max_debt'])
                            );
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['min_debt'] ?? null) {
                            $indicators[] = 'کەمتر نییە لە ' . number_format($data['min_debt']) . ' د.ع';
                        }
                        if ($data['max_debt'] ?? null) {
                            $indicators[] = 'زیاتر نییە لە ' . number_format($data['max_debt']) . ' د.ع';
                        }
                        return $indicators ? 'مەودای قەرز: ' . implode(' و ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری ڕێژەی پارەدان
                SelectFilter::make('payment_ratio')
                    ->label('ڕێژەی پارەدان')
                    ->options([
                        'high' => 'پارەدانی زۆر (> ٧٠٪)',
                        'medium' => 'پارەدانی مامناوەند (٣٠٪ - ٧٠٪)',
                        'low' => 'پارەدانی کەم (< ٣٠٪)',
                        'none' => 'هیچ پارەدانێک نەکراوە',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match($data['value']) {
                            'high' => $query->whereRaw('(total_paid / total_credit) > 0.7'),
                            'medium' => $query->whereRaw('(total_paid / total_credit) BETWEEN 0.3 AND 0.7'),
                            'low' => $query->whereRaw('(total_paid / total_credit) < 0.3')
                                ->where('total_paid', '>', 0),
                            'none' => $query->where('total_paid', '=', 0)
                                ->where('total_credit', '>', 0),
                            default => $query,
                        };
                    })
                    ->columnSpan(1),

                // فلتەری ژمارەی مۆبایل
                Filter::make('phone_filter')
                    ->label('پاڕاستەری مۆبایل')
                    ->form([
                        TextInput::make('phone_prefix')
                            ->label('پێشگر')
                            ->placeholder('٠٧٥٠، ٠٧٧٠، ...')
                            ->maxLength(4),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['phone_prefix'],
                                fn ($q) => $q->where('phone', 'LIKE', $data['phone_prefix'] . '%')
                            );
                    })
                    ->indicateUsing(function (array $data) {
                        if ($data['phone_prefix'] ?? null) {
                            return 'پێشگری مۆبایل: ' . $data['phone_prefix'];
                        }
                        return null;
                    }),

                // فلتەری بەرواری تۆمارکردن
                Filter::make('created_at')
                    ->label('بەرواری تۆمارکردن')
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
                                fn ($q) => $q->whereDate('created_at', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn ($q) => $q->whereDate('created_at', '<=', $data['until'])
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
                        return $indicators ? 'بەرواری تۆمارکردن: ' . implode(' - ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری کڕیارانی ئەمڕۆ
                Filter::make('today_customers')
                    ->label('کڕیارانی ئەمڕۆ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('created_at', today()))
                    ->indicator('ئەمڕۆ'),

                // فلتەری کڕیارانی ئەم هەفتەیە
                Filter::make('this_week')
                    ->label('کڕیارانی ئەم هەفتەیە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->indicator('ئەم هەفتەیە'),

                // فلتەری کڕیارانی ئەم مانگە
                Filter::make('this_month')
                    ->label('کڕیارانی ئەم مانگە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year))
                    ->indicator('ئەم مانگە'),
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

                    Action::make('view_credits')
                        ->label('قەرزەکان')
                        ->icon('heroicon-o-credit-card')
                        ->color(Color::Orange)
                        ->url(fn (Customer $record): string => route('filament.admin.resources.credit-payments.index', ['customer_id' => $record->id]))
                        ->openUrlInNewTab(),

                    Action::make('add_payment')
                        ->label('تۆمارکردنی پارەدان')
                        ->icon('heroicon-o-currency-dollar')
                        ->color(Color::Green)
                        ->url(fn (Customer $record): string => route('filament.admin.resources.credit-payments.create', ['customer_id' => $record->id]))
                        ->visible(fn ($record): bool => $record->current_debt > 0),

                    Tables\Actions\DeleteAction::make()
                        ->label('سڕینەوە')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->modalHeading('سڕینەوەی کڕیار')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم کڕیارە؟')
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
                        ->modalHeading('سڕینەوەی کڕیارە دیاریکراوەکان')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم کڕیارانە؟')
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
            ->emptyStateHeading('هیچ کڕیارێک نییە')
            ->emptyStateDescription('یەکەم کڕیار دروست بکە بۆ دەستپێکردن')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('دروستکردنی کڕیار')
                    ->icon('heroicon-m-plus'),
            ])

            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('current_debt', '>', 0)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
