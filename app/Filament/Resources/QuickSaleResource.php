<?php
// app/Filament/Resources/QuickSaleResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\QuickSaleResource\Pages;
use App\Models\QuickSale;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Support\RawJs;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;

class QuickSaleResource extends Resource
{
    protected static ?string $model = QuickSale::class;
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationGroup = 'فرۆشی خێرا';
    protected static ?string $modelLabel = 'فرۆشی خێرا';
    protected static ?string $pluralModelLabel = 'فرۆشی خێرا';
    protected static ?string $recordTitleAttribute = 'sale_date';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری فرۆشی خێرا')
                    ->description('تۆمارکردنی بڕی کۆگا لە سەرەتا و کۆتایی ڕۆژدا')
                    ->icon('heroicon-o-bolt')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('sale_date')
                                    ->label('ڕێکەوتی فرۆشتن')
                                    ->required()
                                    ->default(now())
                                    ->unique(ignoreRecord: true)
                                    ->displayFormat('Y/m/d')
                                    ->native(false)
                                    ->closeOnDateSelection(),

                                Forms\Components\Select::make('status')
                                    ->label('ڕەوشت')
                                    ->options([
                                        'open' => 'کراوە',
                                        'closed' => 'داخراو',
                                    ])
                                    ->default('open')
                                    ->disabled()
                                    ->dehydrated(true),
                            ]),

                        Forms\Components\Hidden::make('created_by')
                            ->default(fn () => Auth::id()),
                    ]),

                Forms\Components\Tabs::make('فرۆشی خێرا')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('خوێندنەوەی سەرەتایی')
                            ->icon('heroicon-m-sun')
                            ->schema([
                                Forms\Components\Placeholder::make('initial_info')
                                    ->content('بڕی کۆگا لە سەرەتای ڕۆژدا داخیل بکە')
                                    ->columnSpanFull(),
                                ...self::getInitialReadingsFields(),
                            ]),

                        Forms\Components\Tabs\Tab::make('خوێندنەوەی کۆتایی')
                            ->icon('heroicon-m-moon')
                            ->schema([
                                Forms\Components\Placeholder::make('final_info')
                                    ->content('بڕی کۆگا لە کۆتایی ڕۆژدا داخیل بکە')
                                    ->columnSpanFull(),
                                ...self::getFinalReadingsFields(),
                            ]),

                        Forms\Components\Tabs\Tab::make('فرۆشراوەکانی تۆ')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                Forms\Components\Placeholder::make('reported_info')
                                    ->content('ئەو بڕانە بنووسە کە خۆت فرۆشتوویت')
                                    ->columnSpanFull(),
                                ...self::getReportedSoldFields(),
                            ]),

                        Forms\Components\Tabs\Tab::make('پوختە و جیاوازی')
                            ->icon('heroicon-m-chart-bar')
                            ->schema([
                                Forms\Components\Placeholder::make('summary_info')
                                    ->content('پوختەی فرۆشراوەکان و جیاوازیەکان')
                                    ->columnSpanFull(),
                                ...self::getSummaryFields(),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    private static function getInitialReadingsFields(): array
    {
        $fields = [];
        $groupedCategories = QuickSale::getCategoriesGroupedByType();

        foreach ($groupedCategories as $typeKey => $typeData) {
            $color = $typeData['color'] ?? 'gray';

            $fields[] = Forms\Components\Section::make($typeData['name'])
                ->description("خوێندنەوەی سەرەتایی بۆ {$typeData['name']}")
                ->icon('heroicon-m-arrow-up-circle')
                ->collapsible()
                ->schema(function () use ($typeData, $color) {
                    $catFields = [];
                    foreach ($typeData['categories'] as $catId => $category) {
                        $catFields[] = Forms\Components\TextInput::make("initial_readings.{$catId}")
                            ->label(new HtmlString(
                                "<span class='font-bold'>{$category['name']}</span>" .
                                "<br><span class='text-xs text-gray-500'>نرخ: " . number_format($category['price']) . " د.ع</span>"
                            ))
                            ->numeric()
                            ->default(0)
                            ->suffix('لیتر')
                            ->mask(RawJs::make('$money($input)'))
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                self::updateCalculations($set, $get);
                            })
                            ->extraAttributes([
                                'class' => 'border-' . $color . '-300 focus:ring-' . $color . '-500',
                            ]);
                    }
                    return $catFields;
                })
                ->columns(2);
        }

        return $fields;
    }

    private static function getFinalReadingsFields(): array
    {
        $fields = [];
        $groupedCategories = QuickSale::getCategoriesGroupedByType();

        foreach ($groupedCategories as $typeKey => $typeData) {
            $color = $typeData['color'] ?? 'gray';

            $fields[] = Forms\Components\Section::make($typeData['name'])
                ->description("خوێندنەوەی کۆتایی بۆ {$typeData['name']}")
                ->icon('heroicon-m-arrow-down-circle')
                ->collapsible()
                ->schema(function () use ($typeData, $color) {
                    $catFields = [];
                    foreach ($typeData['categories'] as $catId => $category) {
                        $catFields[] = Forms\Components\TextInput::make("final_readings.{$catId}")
                            ->label(new HtmlString(
                                "<span class='font-bold'>{$category['name']}</span>" .
                                "<br><span class='text-xs text-gray-500'>نرخ: " . number_format($category['price']) . " د.ع</span>"
                            ))
                            ->numeric()
                            ->default(0)
                            ->suffix('لیتر')
                            ->mask(RawJs::make('$money($input)'))
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                self::updateCalculations($set, $get);
                            })
                            ->extraAttributes([
                                'class' => 'border-' . $color . '-300 focus:ring-' . $color . '-500',
                            ]);
                    }
                    return $catFields;
                })
                ->columns(2);
        }

        return $fields;
    }

    private static function getReportedSoldFields(): array
    {
        $fields = [];
        $groupedCategories = QuickSale::getCategoriesGroupedByType();

        foreach ($groupedCategories as $typeKey => $typeData) {
            $color = $typeData['color'] ?? 'gray';

            $fields[] = Forms\Components\Section::make($typeData['name'])
                ->description("فرۆشراوەکانی تۆ بۆ {$typeData['name']}")
                ->icon('heroicon-m-document-check')
                ->collapsible()
                ->schema(function () use ($typeData, $color) {
                    $catFields = [];
                    foreach ($typeData['categories'] as $catId => $category) {
                        $catFields[] = Forms\Components\TextInput::make("reported_sold.{$catId}")
                            ->label(new HtmlString(
                                "<span class='font-bold'>{$category['name']}</span>" .
                                "<br><span class='text-xs text-gray-500'>نرخ: " . number_format($category['price']) . " د.ع</span>"
                            ))
                            ->numeric()
                            ->default(0)
                            ->suffix('لیتر')
                            ->mask(RawJs::make('$money($input)'))
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                self::updateCalculations($set, $get);
                            })
                            ->extraAttributes([
                                'class' => 'border-' . $color . '-300 focus:ring-' . $color . '-500',
                            ]);
                    }
                    return $catFields;
                })
                ->columns(2);
        }

        return $fields;
    }

    private static function getSummaryFields(): array
    {
        $fields = [];
        $groupedCategories = QuickSale::getCategoriesGroupedByType();

        foreach ($groupedCategories as $typeKey => $typeData) {
            $color = $typeData['color'] ?? 'gray';

            $fields[] = Forms\Components\Section::make($typeData['name'])
                ->description("پوختە و جیاوازی بۆ {$typeData['name']}")
                ->icon('heroicon-m-scale')
                ->schema(function () use ($typeData, $color) {
                    $catFields = [];
                    foreach ($typeData['categories'] as $catId => $category) {
                        $catFields[] = Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make("sold_display.{$catId}")
                                    ->label('فرۆشراو (حسابکراو)')
                                    ->content(function (callable $get) use ($catId) {
                                        $initial = floatval($get("initial_readings.{$catId}") ?? 0);
                                        $final = floatval($get("final_readings.{$catId}") ?? 0);
                                        $sold = $initial - $final;

                                        return new HtmlString(
                                            "<span class='text-primary-600 font-bold text-lg'>" .
                                            number_format($sold) . " لیتر</span>"
                                        );
                                    }),

                                Forms\Components\Placeholder::make("reported_display.{$catId}")
                                    ->label('فرۆشراوی تۆ')
                                    ->content(function (callable $get) use ($catId) {
                                        $reported = floatval($get("reported_sold.{$catId}") ?? 0);

                                        return new HtmlString(
                                            "<span class='text-info-600 font-bold text-lg'>" .
                                            number_format($reported) . " لیتر</span>"
                                        );
                                    }),

                                Forms\Components\Placeholder::make("difference_display.{$catId}")
                                    ->label('جیاوازی')
                                    ->content(function (callable $get) use ($catId, $color) {
                                        $initial = floatval($get("initial_readings.{$catId}") ?? 0);
                                        $final = floatval($get("final_readings.{$catId}") ?? 0);
                                        $reported = floatval($get("reported_sold.{$catId}") ?? 0);
                                        $calculated = $initial - $final;
                                        $difference = $reported - $calculated;

                                        $diffColor = $difference == 0 ? 'gray' : ($difference > 0 ? 'success' : 'danger');
                                        $icon = $difference == 0 ? '✓' : ($difference > 0 ? '↑' : '↓');

                                        return new HtmlString(
                                            "<span class='text-{$diffColor}-600 font-bold text-lg'>" .
                                            $icon . ' ' . number_format(abs($difference)) . " لیتر</span>"
                                        );
                                    }),
                            ]);
                    }
                    return $catFields;
                })
                ->columns(1);
        }

        // کۆی گشتی
        $fields[] = Forms\Components\Section::make('کۆی گشتی')
            ->icon('heroicon-m-calculator')
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Placeholder::make('total_sold')
                            ->label('کۆی گشتی فرۆشراو (لیتر)')
                            ->content(function (callable $get) {
                                $total = 0;
                                $categories = Category::all();

                                foreach ($categories as $category) {
                                    $initial = floatval($get("initial_readings.{$category->id}") ?? 0);
                                    $final = floatval($get("final_readings.{$category->id}") ?? 0);
                                    $total += ($initial - $final);
                                }

                                return new HtmlString(
                                    "<span class='text-primary-600 font-bold text-2xl'>" .
                                    number_format($total) . " لیتر</span>"
                                );
                            }),

                        Forms\Components\Placeholder::make('total_amount')
                            ->label('کۆی گشتی (دینار)')
                            ->content(function (callable $get) {
                                $total = 0;
                                $categories = Category::all();

                                foreach ($categories as $category) {
                                    $initial = floatval($get("initial_readings.{$category->id}") ?? 0);
                                    $final = floatval($get("final_readings.{$category->id}") ?? 0);
                                    $sold = $initial - $final;
                                    $total += $sold * $category->current_price;
                                }

                                return new HtmlString(
                                    "<span class='text-success-600 font-bold text-2xl'>" .
                                    number_format($total) . " دینار</span>"
                                );
                            }),
                    ]),
            ])
            ->columnSpanFull();

        $fields[] = Forms\Components\Hidden::make('total_amount');
        $fields[] = Forms\Components\Hidden::make('categories_data')
            ->default(fn () => QuickSale::getCategoriesGroupedByType());

        return $fields;
    }

    private static function updateCalculations(callable $set, callable $get)
    {
        // نوێکردنەوەی total_amount
        $total = 0;
        $categories = Category::all();

        foreach ($categories as $category) {
            $initial = floatval($get("initial_readings.{$category->id}") ?? 0);
            $final = floatval($get("final_readings.{$category->id}") ?? 0);
            $sold = $initial - $final;
            $total += $sold * $category->current_price;
        }

        $set('total_amount', $total);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sale_date')
                    ->label('ڕێکەوت')
                    ->date('Y/m/d')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium),

                Tables\Columns\TextColumn::make('status')
                    ->label('ڕەوشت')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success',
                        'closed' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'کراوە',
                        'closed' => 'داخراو',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'open' => 'heroicon-m-lock-open',
                        'closed' => 'heroicon-m-lock-closed',
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('کۆی گشتی')
                    ->money('IQD')
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Medium),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('تۆمارکراو لەلایەن')
                    ->icon('heroicon-m-user')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('کاتی تۆمارکردن')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('ڕەوشت')
                    ->options([
                        'open' => 'کراوە',
                        'closed' => 'داخراو',
                    ]),

                Tables\Filters\Filter::make('sale_date')
                    ->label('مەودای بەروار')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('لە ڕێکەوتی'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('تا ڕێکەوتی'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('sale_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('sale_date', '<=', $data['until']));
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::Modal)
            ->persistFiltersInSession()

            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('بینین')
                        ->icon('heroicon-m-eye')
                        ->color('info'),

                    Action::make('close')
                        ->label('داخستنی ڕۆژ')
                        ->icon('heroicon-m-lock-closed')
                        ->color(Color::Red)
                        ->visible(fn ($record): bool => $record->status === 'open')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update([
                                'status' => 'closed',
                                'closed_by' => auth()->id(),
                            ]);

                            Notification::make()
                                ->title('ڕۆژ بە سەرکەوتوویی داخرا')
                                ->success()
                                ->send();
                        }),

                    Action::make('reopen')
                        ->label('کردنەوەی ڕۆژ')
                        ->icon('heroicon-m-lock-open')
                        ->color(Color::Orange)
                        ->visible(fn ($record): bool => $record->status === 'closed')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update([
                                'status' => 'open',
                                'closed_by' => null,
                            ]);

                            Notification::make()
                                ->title('ڕۆژ بە سەرکەوتوویی کرایەوە')
                                ->success()
                                ->send();
                        }),
                ])
                ->label('کردارەکان')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->size('sm'),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('سڕینەوەی دیاریکراوەکان'),
                ]),
            ])

            ->emptyStateIcon('heroicon-o-bolt')
            ->emptyStateHeading('هیچ فرۆشی خێرایەک نییە')
            ->emptyStateDescription('یەکەم تۆماری فرۆشی خێرا دروست بکە')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('دروستکردنی فرۆشی خێرا')
                    ->icon('heroicon-m-plus'),
            ])

            ->defaultSort('sale_date', 'desc')
            ->striped()
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuickSales::route('/'),
            'create' => Pages\CreateQuickSale::route('/create'),
            'edit' => Pages\CreateQuickSale::route('/{record}/edit')
        ];
    }
}
