<?php
// app/Filament/Resources/FuelPurchaseResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\FuelPurchaseResource\Pages;
use App\Models\FuelPurchase;
use App\Models\Category;
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
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;

class FuelPurchaseResource extends Resource
{
    protected static ?string $model = FuelPurchase::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'کڕین و فرۆشتن';
    protected static ?string $modelLabel = 'کڕینی بەنزین';
    protected static ?string $pluralModelLabel = 'کڕینی بەنزین';
    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری کڕین')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('کاتیگۆری')
                            ->relationship('category', 'name')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $category = Category::find($state);
                                if ($category) {
                                    $set('price_per_liter', $category->purchase_price);
                                    $liters = $get('liters') ?? 0;
                                    $set('total_price', $liters * $category->purchase_price);

                                    // پێشنیاری کۆگا
                                    Notification::make()
                                        ->info()
                                        ->title('زانیاری کۆگا')
                                        ->body("کۆگای ئێستا: " . number_format($category->stock_liters) . " لیتر")
                                        ->send();
                                }
                            }),
                        Forms\Components\TextInput::make('liters')
                            ->label('بڕ (لیتر)')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $pricePerLiter = $get('price_per_liter') ?? 0;
                                $totalPrice = $state * $pricePerLiter;
                                $set('total_price', $totalPrice);
                            }),
                        Forms\Components\TextInput::make('price_per_liter')
                            ->label('نرخی لیترێک')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $liters = $get('liters') ?? 0;
                                $totalPrice = $liters * $state;
                                $set('total_price', $totalPrice);
                            }),
                        Forms\Components\TextInput::make('total_price')
                            ->label('کۆی گشتی')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->disabled()
                            ->dehydrated(true)
                            ->default(0),
                        Forms\Components\DatePicker::make('purchase_date')
                            ->label('ڕێکەوتی کڕین')
                            ->required()
                            ->default(now()),
                        Forms\Components\Textarea::make('notes')
                            ->label('تێبینی')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label('کاتیگۆری')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium),

                Tables\Columns\TextColumn::make('liters')
                    ->label('بڕ')
                    ->suffix(' لیتر')
                    ->sortable()
                    ->weight('bold')
                    ->color('warning')
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('price_per_liter')
                    ->label('نرخی لیترێک')
                    ->money('IQD')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('کۆی گشتی')
                    ->money('IQD')
                    ->sortable()
                    ->weight('bold')
                    ->color('danger'),

                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('ڕێکەوت')
                    ->date('Y/m/d')
                    ->sortable()
                    ->icon('heroicon-m-calendar'),

                Tables\Columns\TextColumn::make('notes')
                    ->label('تێبینی')
                    ->limit(30)
                    ->tooltip(fn ($state): string => $state ?? '')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('category.stock_liters')
                    ->label('کۆگای دوای کڕین')
                    ->getStateUsing(function ($record) {
                        return $record->category?->stock_liters ?? 0;
                    })
                    ->suffix(' لیتر')
                    ->badge()
                    ->color(fn ($state): string =>
                        $state > 1000 ? 'success' : ($state > 500 ? 'warning' : 'danger')
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('بەرواری تۆمارکردن')
                    ->date('Y/m/d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            // **فلتەرە پڕۆفیشناڵەکان**
            ->filters([
                // فلتەری کاتیگۆری
                SelectFilter::make('category_id')
                    ->label('کاتیگۆری')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->indicator('کاتیگۆری')
                    ->placeholder('هەموو کاتیگۆریەکان')
                    ->columnSpan(2),

                // فلتەری مەودای بەروار
                Filter::make('purchase_date')
                    ->label('مەودای بەروار')
                    ->form([
                        DatePicker::make('from')
                            ->label('لە ڕێکەوتی')
                            ->placeholder('YYYY-MM-DD'),
                        DatePicker::make('until')
                            ->label('تا ڕێکەوتی')
                            ->placeholder('YYYY-MM-DD'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn ($q) => $q->whereDate('purchase_date', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn ($q) => $q->whereDate('purchase_date', '<=', $data['until'])
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
                        return $indicators ? 'بەروار: ' . implode(' - ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری مەودای بڕ (لیتر)
                Filter::make('liters_range')
                    ->label('مەودای بڕ (لیتر)')
                    ->form([
                        TextInput::make('min_liters')
                            ->label('کەمترین بڕ')
                            ->numeric()
                            ->suffix('لیتر')
                            ->placeholder('١٠٠'),
                        TextInput::make('max_liters')
                            ->label('زۆرترین بڕ')
                            ->numeric()
                            ->suffix('لیتر')
                            ->placeholder('١٠٠٠'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['min_liters'],
                                fn ($q) => $q->where('liters', '>=', $data['min_liters'])
                            )
                            ->when(
                                $data['max_liters'],
                                fn ($q) => $q->where('liters', '<=', $data['max_liters'])
                            );
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['min_liters'] ?? null) {
                            $indicators[] = 'کەمتر نییە لە ' . number_format($data['min_liters']) . ' لیتر';
                        }
                        if ($data['max_liters'] ?? null) {
                            $indicators[] = 'زیاتر نییە لە ' . number_format($data['max_liters']) . ' لیتر';
                        }
                        return $indicators ? 'بڕ: ' . implode(' و ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری مەودای نرخ
                Filter::make('price_range')
                    ->label('مەودای نرخ')
                    ->form([
                        TextInput::make('min_price')
                            ->label('کەمترین نرخ')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('٥٠٠'),
                        TextInput::make('max_price')
                            ->label('زۆرترین نرخ')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١٢٠٠'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['min_price'],
                                fn ($q) => $q->where('price_per_liter', '>=', $data['min_price'])
                            )
                            ->when(
                                $data['max_price'],
                                fn ($q) => $q->where('price_per_liter', '<=', $data['max_price'])
                            );
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['min_price'] ?? null) {
                            $indicators[] = 'کەمتر نییە لە ' . number_format($data['min_price']) . ' د.ع';
                        }
                        if ($data['max_price'] ?? null) {
                            $indicators[] = 'زیاتر نییە لە ' . number_format($data['max_price']) . ' د.ع';
                        }
                        return $indicators ? 'نرخ: ' . implode(' و ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری مەودای کۆی گشتی
                Filter::make('total_price_range')
                    ->label('مەودای کۆی گشتی')
                    ->form([
                        TextInput::make('min_total')
                            ->label('کەمترین کۆ')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١٠٠٠٠٠'),
                        TextInput::make('max_total')
                            ->label('زۆرترین کۆ')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('١٠٠٠٠٠٠'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['min_total'],
                                fn ($q) => $q->where('total_price', '>=', $data['min_total'])
                            )
                            ->when(
                                $data['max_total'],
                                fn ($q) => $q->where('total_price', '<=', $data['max_total'])
                            );
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['min_total'] ?? null) {
                            $indicators[] = 'کەمتر نییە لە ' . number_format($data['min_total']) . ' د.ع';
                        }
                        if ($data['max_total'] ?? null) {
                            $indicators[] = 'زیاتر نییە لە ' . number_format($data['max_total']) . ' د.ع';
                        }
                        return $indicators ? 'کۆی گشتی: ' . implode(' و ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری ئاستی بڕ
                SelectFilter::make('liters_level')
                    ->label('ئاستی بڕ')
                    ->options([
                        'very_large' => 'زۆر گەورە (> ١٠٠٠٠ لیتر)',
                        'large' => 'گەورە (٥٠٠٠ - ١٠٠٠٠ لیتر)',
                        'medium' => 'مامناوەند (١٠٠٠ - ٥٠٠٠ لیتر)',
                        'small' => 'بچوک (٥٠٠ - ١٠٠٠ لیتر)',
                        'very_small' => 'زۆر بچوک (< ٥٠٠ لیتر)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match($data['value']) {
                            'very_large' => $query->where('liters', '>', 10000),
                            'large' => $query->whereBetween('liters', [5000, 10000]),
                            'medium' => $query->whereBetween('liters', [1000, 5000]),
                            'small' => $query->whereBetween('liters', [500, 1000]),
                            'very_small' => $query->where('liters', '<', 500),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data) {
                        return match($data['value'] ?? null) {
                            'very_large' => 'بڕی زۆر گەورە',
                            'large' => 'بڕی گەورە',
                            'medium' => 'بڕی مامناوەند',
                            'small' => 'بڕی بچوک',
                            'very_small' => 'بڕی زۆر بچوک',
                            default => null,
                        };
                    })
                    ->columnSpan(1),

                // فلتەری کڕینی ئەمڕۆ
                Filter::make('today')
                    ->label('کڕینی ئەمڕۆ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('purchase_date', today()))
                    ->indicator('ئەمڕۆ'),

                // فلتەری کڕینی دوێنێ
                Filter::make('yesterday')
                    ->label('کڕینی دوێنێ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('purchase_date', today()->subDay()))
                    ->indicator('دوێنێ'),

                // فلتەری کڕینی ئەم هەفتەیە
                Filter::make('this_week')
                    ->label('کڕینی ئەم هەفتەیە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereBetween('purchase_date', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->indicator('ئەم هەفتەیە'),

                // فلتەری کڕینی ئەم مانگە
                Filter::make('this_month')
                    ->label('کڕینی ئەم مانگە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereMonth('purchase_date', now()->month)
                        ->whereYear('purchase_date', now()->year))
                    ->indicator('ئەم مانگە'),

                // فلتەری کڕینی ئەمساڵ
                Filter::make('this_year')
                    ->label('کڕینی ئەمساڵ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereYear('purchase_date', now()->year))
                    ->indicator('ئەمساڵ'),

                // فلتەری تێبینی
                TernaryFilter::make('has_notes')
                    ->label('تێبینی')
                    ->placeholder('هەموو')
                    ->trueLabel('تێبینی هەیە')
                    ->falseLabel('تێبینی نییە')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('notes'),
                        false: fn ($query) => $query->whereNull('notes'),
                    )
                    ->indicator('تێبینی'),
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

                   Tables\Actions\Action::make('view_category')
                        ->label('بینینی کۆگا')
                        ->icon('heroicon-o-beaker')
                        ->color(Color::Orange)
                        ->url(fn ($record): string => route('filament.admin.resources.categories.edit', $record->category_id))
                        ->openUrlInNewTab(),

                    Tables\Actions\DeleteAction::make()
                        ->label('سڕینەوە')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->modalHeading('سڕینەوەی کڕین')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم کڕینە؟')
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
                        ->modalHeading('سڕینەوەی کڕینە دیاریکراوەکان')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم کڕینانە؟')
                        ->modalSubmitActionLabel('بەڵێ، بیانسڕەوە')
                        ->modalCancelActionLabel('نەخێر'),
                ]),
            ])

            ->emptyStateIcon('heroicon-o-truck')
            ->emptyStateHeading('هیچ کڕینێک نییە')
            ->emptyStateDescription('یەکەم کڕین تۆمار بکە بۆ دەستپێکردن')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('تۆمارکردنی کڕین')
                    ->icon('heroicon-m-plus'),
            ])

            ->defaultSort('purchase_date', 'desc')
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
            'index' => Pages\ListFuelPurchases::route('/'),
            'create' => Pages\CreateFuelPurchase::route('/create'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $todayTotal = static::getModel()::whereDate('purchase_date', today())->count();
        return $todayTotal > 0 ? (string) $todayTotal : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
