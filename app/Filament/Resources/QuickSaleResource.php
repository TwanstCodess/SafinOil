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
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;

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
                    ->description('تۆمارکردنی بڕی کۆگا بۆ هەر شەفتێک')
                    ->icon('heroicon-o-bolt')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('sale_date')
                                    ->label('ڕێکەوتی فرۆشتن')
                                    ->required()
                                    ->default(now())
                                    ->displayFormat('Y/m/d')
                                    ->native(false)
                                    ->closeOnDateSelection(),

                                Forms\Components\Select::make('shift')
                                    ->label('شەفت')
                                    ->options([
                                        'morning' => '🌅 شەفتی بەیانی',
                                        'evening' => '🌙 شەفتی ئێوارە',
                                    ])
                                    ->required()
                                    ->default('morning')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $date = $get('sale_date');
                                        if ($date) {
                                            $exists = QuickSale::whereDate('sale_date', $date)
                                                ->where('shift', $state)
                                                ->exists();

                                            if ($exists) {
                                                Notification::make()
                                                    ->warning()
                                                    ->title('ئاگادار!')
                                                    ->body('ئەم شەفتە پێشتر تۆمار کراوە')
                                                    ->send();
                                            }
                                        }
                                    }),

                                Forms\Components\Select::make('status')
                                    ->label('ڕەوشت')
                                    ->options([
                                        'open' => 'کراوە',
                                        'closed' => 'داخراو',
                                    ])
                                    ->default('open')
                                    ->disabled(fn (string $operation): bool => $operation === 'create')
                                    ->dehydrated(true),
                            ]),

                        Forms\Components\Hidden::make('created_by')
                            ->default(fn () => Auth::id()),

                        Forms\Components\Hidden::make('categories_data')
                            ->default(fn () => QuickSale::getCategoriesGroupedByType()),
                    ]),

                Forms\Components\Tabs::make('فرۆشی خێرا')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('خوێندنەوەی سەرەتایی')
                            ->icon('heroicon-m-sun')
                            ->schema([
                                Forms\Components\Placeholder::make('initial_info')
                                    ->content(fn (callable $get) => new HtmlString('
                                        <div class="' . ($get('shift') === 'morning' ? 'bg-yellow-50' : 'bg-indigo-50') . ' p-4 rounded-lg">
                                            <p class="' . ($get('shift') === 'morning' ? 'text-yellow-700' : 'text-indigo-700') . '">
                                                ' . ($get('shift') === 'morning' ? '🌅' : '🌙') . ' بڕی کۆگا لە <strong>سەرەتای ' . ($get('shift') === 'morning' ? 'شەفتی بەیانی' : 'شەفتی ئێوارە') . '</strong>دا داخیل بکە
                                            </p>
                                        </div>
                                    '))
                                    ->columnSpanFull(),
                                ...self::getInitialReadingsFields(),
                            ]),

                        Forms\Components\Tabs\Tab::make('خوێندنەوەی کۆتایی')
                            ->icon('heroicon-m-moon')
                            ->schema([
                                Forms\Components\Placeholder::make('final_info')
                                    ->content(fn (callable $get) => new HtmlString('
                                        <div class="' . ($get('shift') === 'morning' ? 'bg-yellow-50' : 'bg-indigo-50') . ' p-4 rounded-lg">
                                            <p class="' . ($get('shift') === 'morning' ? 'text-yellow-700' : 'text-indigo-700') . '">
                                                ' . ($get('shift') === 'morning' ? '🌅' : '🌙') . ' بڕی کۆگا لە <strong>کۆتایی ' . ($get('shift') === 'morning' ? 'شەفتی بەیانی' : 'شەفتی ئێوارە') . '</strong>دا داخیل بکە
                                            </p>
                                        </div>
                                    '))
                                    ->columnSpanFull(),
                                ...self::getFinalReadingsFields(),
                            ]),

                        Forms\Components\Tabs\Tab::make('فرۆشراوەکانی تۆ')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                Forms\Components\Placeholder::make('reported_info')
                                    ->content(fn (callable $get) => new HtmlString('
                                        <div class="bg-purple-50 p-4 rounded-lg">
                                            <p class="text-purple-700">📝 ئەو بڕانە بنووسە کە <strong>لە ' . ($get('shift') === 'morning' ? 'شەفتی بەیانی' : 'شەفتی ئێوارە') . '</strong>دا فرۆشتوویت</p>
                                        </div>
                                    '))
                                    ->columnSpanFull(),
                                ...self::getReportedSoldFields(),
                            ]),

                        Forms\Components\Tabs\Tab::make('پوختە و جیاوازی')
                            ->icon('heroicon-m-chart-bar')
                            ->schema([
                                Forms\Components\Placeholder::make('summary_info')
                                    ->content(fn (callable $get) => new HtmlString('
                                        <div class="bg-green-50 p-4 rounded-lg">
                                            <p class="text-green-700">📊 پوختەی فرۆشراوەکان و جیاوازیەکان بۆ ' . ($get('shift') === 'morning' ? 'شەفتی بەیانی' : 'شەفتی ئێوارە') . '</p>
                                        </div>
                                    '))
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
        $allCategories = QuickSale::getAllCategoriesList();

        $grouped = [];
        foreach ($allCategories as $catId => $category) {
            $typeKey = $category['type_key'];
            if (!isset($grouped[$typeKey])) {
                $grouped[$typeKey] = [
                    'name' => $category['type'],
                    'items' => []
                ];
            }
            $grouped[$typeKey]['items'][$catId] = $category;
        }

        foreach ($grouped as $typeKey => $group) {
            $color = match($typeKey) {
                'fuel' => 'warning',
                'oil' => 'success',
                'gas' => 'info',
                default => 'gray',
            };

            $fields[] = Forms\Components\Section::make($group['name'])
                ->description("خوێندنەوەی سەرەتایی")
                ->icon('heroicon-m-arrow-up-circle')
                ->collapsible()
                ->schema(function () use ($group, $color) {
                    $catFields = [];
                    foreach ($group['items'] as $catId => $category) {
                        $catFields[] = Forms\Components\TextInput::make("initial_readings.{$catId}")
                            ->label(new HtmlString("
                                <div class='flex flex-col'>
                                    <span class='font-bold'>{$category['name']}</span>
                                    <span class='text-xs text-gray-500'>نرخ: " . number_format($category['price']) . " د.ع</span>
                                </div>
                            "))
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
        $allCategories = QuickSale::getAllCategoriesList();

        $grouped = [];
        foreach ($allCategories as $catId => $category) {
            $typeKey = $category['type_key'];
            if (!isset($grouped[$typeKey])) {
                $grouped[$typeKey] = [
                    'name' => $category['type'],
                    'items' => []
                ];
            }
            $grouped[$typeKey]['items'][$catId] = $category;
        }

        foreach ($grouped as $typeKey => $group) {
            $color = match($typeKey) {
                'fuel' => 'warning',
                'oil' => 'success',
                'gas' => 'info',
                default => 'gray',
            };

            $fields[] = Forms\Components\Section::make($group['name'])
                ->description("خوێندنەوەی کۆتایی")
                ->icon('heroicon-m-arrow-down-circle')
                ->collapsible()
                ->schema(function () use ($group, $color) {
                    $catFields = [];
                    foreach ($group['items'] as $catId => $category) {
                        $catFields[] = Forms\Components\TextInput::make("final_readings.{$catId}")
                            ->label(new HtmlString("
                                <div class='flex flex-col'>
                                    <span class='font-bold'>{$category['name']}</span>
                                    <span class='text-xs text-gray-500'>نرخ: " . number_format($category['price']) . " د.ع</span>
                                </div>
                            "))
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
        $allCategories = QuickSale::getAllCategoriesList();

        $grouped = [];
        foreach ($allCategories as $catId => $category) {
            $typeKey = $category['type_key'];
            if (!isset($grouped[$typeKey])) {
                $grouped[$typeKey] = [
                    'name' => $category['type'],
                    'items' => []
                ];
            }
            $grouped[$typeKey]['items'][$catId] = $category;
        }

        foreach ($grouped as $typeKey => $group) {
            $color = match($typeKey) {
                'fuel' => 'warning',
                'oil' => 'success',
                'gas' => 'info',
                default => 'gray',
            };

            $fields[] = Forms\Components\Section::make($group['name'])
                ->description("فرۆشراوەکانی تۆ (بە لیتر)")
                ->icon('heroicon-m-document-check')
                ->collapsible()
                ->schema(function () use ($group, $color) {
                    $catFields = [];
                    foreach ($group['items'] as $catId => $category) {
                        $catFields[] = Forms\Components\TextInput::make("reported_sold.{$catId}")
                            ->label(new HtmlString("
                                <div class='flex flex-col'>
                                    <span class='font-bold'>{$category['name']}</span>
                                    <span class='text-xs text-gray-500'>نرخ: " . number_format($category['price']) . " د.ع</span>
                                </div>
                            "))
                            ->numeric()
                            ->default(function (callable $get) use ($catId) {
                                $initial = floatval($get("initial_readings.{$catId}") ?? 0);
                                $final = floatval($get("final_readings.{$catId}") ?? 0);
                                return $initial - $final;
                            })
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
        $allCategories = QuickSale::getAllCategoriesList();

        // دوگمەی کۆپی کردن
        $fields[] = Forms\Components\Actions::make([
            Forms\Components\Actions\Action::make('copyToReported')
                ->label('کۆپی کردنی فرۆشراوەکان بۆ فرۆشراوی تۆ')
                ->icon('heroicon-m-document-duplicate')
                ->color('success')
                ->action(function (callable $set, callable $get) use ($allCategories) {
                    foreach ($allCategories as $catId => $category) {
                        $initial = floatval($get("initial_readings.{$catId}") ?? 0);
                        $final = floatval($get("final_readings.{$catId}") ?? 0);
                        $sold = $initial - $final;
                        $set("reported_sold.{$catId}", $sold);
                    }

                    self::updateCalculations($set, $get);

                    Notification::make()
                        ->title('فرۆشراوەکان بە سەرکەوتوویی کۆپی کران')
                        ->success()
                        ->send();
                })
                ->extraAttributes(['class' => 'mb-4']),
        ])
        ->columnSpanFull();

        // سەرەتا ناونیشانی ستوونەکان
        $fields[] = Forms\Components\Grid::make(7)
            ->schema([
                Forms\Components\Placeholder::make('header_category')
                    ->label('')
                    ->content(new HtmlString('<span class="font-bold text-gray-700">کاتیگۆری</span>')),

                Forms\Components\Placeholder::make('header_initial')
                    ->label('')
                    ->content(new HtmlString('<span class="font-bold text-gray-700">سەرەتایی</span>')),

                Forms\Components\Placeholder::make('header_final')
                    ->label('')
                    ->content(new HtmlString('<span class="font-bold text-gray-700">کۆتایی</span>')),

                Forms\Components\Placeholder::make('header_sold')
                    ->label('')
                    ->content(new HtmlString('<span class="font-bold text-gray-700">فرۆشراو</span>')),

                Forms\Components\Placeholder::make('header_reported')
                    ->label('')
                    ->content(new HtmlString('<span class="font-bold text-gray-700">فرۆشراوی تۆ</span>')),

                Forms\Components\Placeholder::make('header_diff_liter')
                    ->label('')
                    ->content(new HtmlString('<span class="font-bold text-gray-700">جیاوازی (لیتر)</span>')),

                Forms\Components\Placeholder::make('header_diff_price')
                    ->label('')
                    ->content(new HtmlString('<span class="font-bold text-gray-700">جیاوازی (دینار)</span>')),
            ])
            ->columns(7)
            ->extraAttributes(['class' => 'bg-gray-100 p-2 rounded-t-lg mb-2']);

        // بۆ هەر کاتیگۆرییەک
        foreach ($allCategories as $catId => $category) {
            $typeKey = $category['type_key'];
            $bgColor = match($typeKey) {
                'fuel' => 'bg-warning-50',
                'oil' => 'bg-success-50',
                'gas' => 'bg-info-50',
                default => 'bg-gray-50',
            };

            $fields[] = Forms\Components\Grid::make(7)
                ->schema([
                    Forms\Components\Placeholder::make("cat_name.{$catId}")
                        ->label('')
                        ->content(new HtmlString(
                            "<div class='flex flex-col'>
                                <span class='font-bold'>{$category['name']}</span>
                                <span class='text-xs text-gray-500'>" . number_format($category['price']) . " د.ع</span>
                            </div>"
                        )),

                    Forms\Components\Placeholder::make("initial_display.{$catId}")
                        ->label('')
                        ->content(function (callable $get) use ($catId) {
                            $initial = floatval($get("initial_readings.{$catId}") ?? 0);
                            return new HtmlString(
                                "<span class='text-blue-600 font-medium'>" . number_format($initial) . " لیتر</span>"
                            );
                        }),

                    Forms\Components\Placeholder::make("final_display.{$catId}")
                        ->label('')
                        ->content(function (callable $get) use ($catId) {
                            $final = floatval($get("final_readings.{$catId}") ?? 0);
                            return new HtmlString(
                                "<span class='text-purple-600 font-medium'>" . number_format($final) . " لیتر</span>"
                            );
                        }),

                    Forms\Components\Placeholder::make("sold_display.{$catId}")
                        ->label('')
                        ->content(function (callable $get) use ($catId, $category) {
                            $initial = floatval($get("initial_readings.{$catId}") ?? 0);
                            $final = floatval($get("final_readings.{$catId}") ?? 0);
                            $sold = $initial - $final;
                            $totalPrice = $sold * $category['price'];

                            $color = $sold > 0 ? 'text-success-600' : 'text-gray-400';

                            return new HtmlString(
                                "<div class='flex flex-col'>
                                    <span class='{$color} font-bold'>" . number_format($sold) . " لیتر</span>
                                    <span class='text-xs text-gray-500'>" . number_format($totalPrice) . " د.ع</span>
                                </div>"
                            );
                        }),

                    Forms\Components\Placeholder::make("reported_display.{$catId}")
                        ->label('')
                        ->content(function (callable $get) use ($catId, $category) {
                            $initial = floatval($get("initial_readings.{$catId}") ?? 0);
                            $final = floatval($get("final_readings.{$catId}") ?? 0);
                            $sold = $initial - $final;
                            $reported = floatval($get("reported_sold.{$catId}") ?? $sold);
                            $totalPrice = $reported * $category['price'];

                            return new HtmlString(
                                "<div class='flex flex-col'>
                                    <span class='text-info-600 font-bold'>" . number_format($reported) . " لیتر</span>
                                    <span class='text-xs text-gray-500'>" . number_format($totalPrice) . " د.ع</span>
                                </div>"
                            );
                        }),

                    Forms\Components\Placeholder::make("diff_liter.{$catId}")
                        ->label('')
                        ->content(function (callable $get) use ($catId) {
                            $initial = floatval($get("initial_readings.{$catId}") ?? 0);
                            $final = floatval($get("final_readings.{$catId}") ?? 0);
                            $reported = floatval($get("reported_sold.{$catId}") ?? 0);
                            $calculated = $initial - $final;
                            $difference = $reported - $calculated;

                            $diffColor = $difference == 0 ? 'gray' : ($difference > 0 ? 'success' : 'danger');
                            $icon = $difference == 0 ? '✓' : ($difference > 0 ? '↑' : '↓');

                            return new HtmlString(
                                "<div class='flex items-center gap-1'>
                                    <span class='text-{$diffColor}-600 font-bold'>{$icon}</span>
                                    <span class='text-{$diffColor}-600 font-bold'>"
                                    . number_format(abs($difference)) . " لیتر</span>
                                </div>"
                            );
                        }),

                    Forms\Components\Placeholder::make("diff_price.{$catId}")
                        ->label('')
                        ->content(function (callable $get) use ($catId, $category) {
                            $initial = floatval($get("initial_readings.{$catId}") ?? 0);
                            $final = floatval($get("final_readings.{$catId}") ?? 0);
                            $reported = floatval($get("reported_sold.{$catId}") ?? 0);
                            $calculated = $initial - $final;
                            $difference = $reported - $calculated;
                            $priceDiff = $difference * $category['price'];

                            $diffColor = $priceDiff == 0 ? 'gray' : ($priceDiff > 0 ? 'success' : 'danger');

                            return new HtmlString(
                                "<span class='text-{$diffColor}-600 font-bold'>"
                                . number_format(abs($priceDiff)) . " د.ع</span>"
                            );
                        }),
                ])
                ->columns(7)
                ->extraAttributes(['class' => "{$bgColor} p-3 rounded-lg mb-2 border border-gray-200"]);
        }

        // کۆی گشتی
        $fields[] = Forms\Components\Section::make('کۆی گشتی')
            ->icon('heroicon-m-calculator')
            ->schema([
                Forms\Components\Grid::make(4)
                    ->schema([
                        Forms\Components\Placeholder::make('total_initial')
                            ->label('کۆی سەرەتایی')
                            ->content(function (callable $get) {
                                $total = 0;
                                $categories = Category::all();

                                foreach ($categories as $category) {
                                    $total += floatval($get("initial_readings.{$category->id}") ?? 0);
                                }

                                return new HtmlString(
                                    "<span class='text-blue-600 font-bold text-xl'>" .
                                    number_format($total) . " لیتر</span>"
                                );
                            }),

                        Forms\Components\Placeholder::make('total_final')
                            ->label('کۆی کۆتایی')
                            ->content(function (callable $get) {
                                $total = 0;
                                $categories = Category::all();

                                foreach ($categories as $category) {
                                    $total += floatval($get("final_readings.{$category->id}") ?? 0);
                                }

                                return new HtmlString(
                                    "<span class='text-purple-600 font-bold text-xl'>" .
                                    number_format($total) . " لیتر</span>"
                                );
                            }),

                        Forms\Components\Placeholder::make('total_sold')
                            ->label('کۆی فرۆشراو')
                            ->content(function (callable $get) {
                                $total = 0;
                                $categories = Category::all();

                                foreach ($categories as $category) {
                                    $initial = floatval($get("initial_readings.{$category->id}") ?? 0);
                                    $final = floatval($get("final_readings.{$category->id}") ?? 0);
                                    $total += ($initial - $final);
                                }

                                return new HtmlString(
                                    "<span class='text-success-600 font-bold text-xl'>" .
                                    number_format($total) . " لیتر</span>"
                                );
                            }),

                        Forms\Components\Placeholder::make('total_reported')
                            ->label('کۆی فرۆشراوی تۆ')
                            ->content(function (callable $get) {
                                $total = 0;
                                $categories = Category::all();

                                foreach ($categories as $category) {
                                    $total += floatval($get("reported_sold.{$category->id}") ?? 0);
                                }

                                return new HtmlString(
                                    "<span class='text-info-600 font-bold text-xl'>" .
                                    number_format($total) . " لیتر</span>"
                                );
                            }),
                    ])
                    ->columns(4),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Placeholder::make('total_difference_liter')
                            ->label('کۆی جیاوازی (لیتر)')
                            ->content(function (callable $get) {
                                $totalDiff = 0;
                                $categories = Category::all();

                                foreach ($categories as $category) {
                                    $catId = $category->id;
                                    $initial = floatval($get("initial_readings.{$catId}") ?? 0);
                                    $final = floatval($get("final_readings.{$catId}") ?? 0);
                                    $reported = floatval($get("reported_sold.{$catId}") ?? 0);
                                    $calculated = $initial - $final;
                                    $totalDiff += ($reported - $calculated);
                                }

                                $diffColor = $totalDiff == 0 ? 'gray' : ($totalDiff > 0 ? 'success' : 'danger');
                                $icon = $totalDiff == 0 ? '✓' : ($totalDiff > 0 ? '↑' : '↓');

                                return new HtmlString(
                                    "<div class='flex items-center gap-2'>
                                        <span class='text-{$diffColor}-600 font-bold text-2xl'>{$icon}</span>
                                        <span class='text-{$diffColor}-600 font-bold text-2xl'>"
                                        . number_format(abs($totalDiff)) . " لیتر</span>
                                    </div>"
                                );
                            }),

                        Forms\Components\Placeholder::make('total_difference_price')
                            ->label('کۆی جیاوازی (دینار)')
                            ->content(function (callable $get) {
                                $totalDiffPrice = 0;
                                $categories = Category::all();

                                foreach ($categories as $category) {
                                    $catId = $category->id;
                                    $initial = floatval($get("initial_readings.{$catId}") ?? 0);
                                    $final = floatval($get("final_readings.{$catId}") ?? 0);
                                    $reported = floatval($get("reported_sold.{$catId}") ?? 0);
                                    $calculated = $initial - $final;
                                    $difference = $reported - $calculated;
                                    $totalDiffPrice += $difference * $category->current_price;
                                }

                                $diffColor = $totalDiffPrice == 0 ? 'gray' : ($totalDiffPrice > 0 ? 'success' : 'danger');

                                return new HtmlString(
                                    "<span class='text-{$diffColor}-600 font-bold text-2xl'>"
                                    . number_format(abs($totalDiffPrice)) . " د.ع</span>"
                                );
                            }),
                    ])
                    ->columns(2),

                Forms\Components\Placeholder::make('total_amount_display')
                    ->label('کۆی گشتی فرۆشراو (دینار)')
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
                            "<span class='text-success-600 font-bold text-3xl'>" .
                            number_format($total) . " دینار</span>"
                        );
                    })
                    ->extraAttributes(['class' => 'text-center']),
            ])
            ->columnSpanFull();

        $fields[] = Forms\Components\Hidden::make('total_amount');

        return $fields;
    }

    private static function updateCalculations(callable $set, callable $get)
    {
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
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('shift')
                    ->label('شەفت')
                    ->badge()
                    ->color(fn ($record): string => $record->shift_color)
                    ->formatStateUsing(fn ($record): string => $record->shift_name)
                    ->icon(fn (string $state): string => match ($state) {
                        'morning' => 'heroicon-m-sun',
                        'evening' => 'heroicon-m-moon',
                    }),

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
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('کۆی گشتی')
                    ->money('IQD')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

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
                // فلتەری بەروار (پێشنیاری ئەمڕۆ)
                Filter::make('sale_date')
                    ->form([
                        DatePicker::make('date')
                            ->label('ڕێکەوت')
                            ->default(now())
                            ->displayFormat('Y/m/d')
                            ->native(false)
                            ->closeOnDateSelection(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['date'], fn ($q) => $q->whereDate('sale_date', $data['date']));
                    })
                    ->indicateUsing(function (array $data) {
                        if ($data['date'] ?? null) {
                            return 'ڕێکەوت: ' . \Carbon\Carbon::parse($data['date'])->format('Y/m/d');
                        }
                        return null;
                    })
                    ->columnSpan(1),

                // فلتەری شەفت
                Tables\Filters\SelectFilter::make('shift')
                    ->label('شەفت')
                    ->options([
                        'morning' => 'شەفتی بەیانی',
                        'evening' => 'شەفتی ئێوارە',
                    ])
                    ->columnSpan(1),

                // فلتەری ڕەوشت
                Tables\Filters\SelectFilter::make('status')
                    ->label('ڕەوشت')
                    ->options([
                        'open' => 'کراوە',
                        'closed' => 'داخراو',
                    ])
                    ->columnSpan(1),

                // فلتەری پێشکەوتوو (مەودای بەروار)
                Filter::make('date_range')
                    ->label('مەودای بەروار')
                    ->form([
                        DatePicker::make('from')
                            ->label('لە ڕێکەوتی')
                            ->displayFormat('Y/m/d')
                            ->native(false),
                        DatePicker::make('until')
                            ->label('تا ڕێکەوتی')
                            ->displayFormat('Y/m/d')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('sale_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('sale_date', '<=', $data['until']));
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'لە ' . \Carbon\Carbon::parse($data['from'])->format('Y/m/d');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'تا ' . \Carbon\Carbon::parse($data['until'])->format('Y/m/d');
                        }
                        return $indicators ? 'مەودای بەروار: ' . implode(' - ', $indicators) : null;
                    })
                    ->columns(2)
                    ->columnSpan(2),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->persistFiltersInSession()

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

                    Action::make('close')
                        ->label('داخستنی شەفت')
                        ->icon('heroicon-m-lock-closed')
                        ->color(Color::Red)
                        ->visible(fn ($record): bool => $record && $record->status === 'open')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update([
                                'status' => 'closed',
                                'closed_by' => auth()->id(),
                            ]);

                            Notification::make()
                                ->title('شەفت بە سەرکەوتوویی داخرا')
                                ->success()
                                ->send();
                        }),

                    Action::make('reopen')
                        ->label('کردنەوەی شەفت')
                        ->icon('heroicon-m-lock-open')
                        ->color(Color::Orange)
                        ->visible(fn ($record): bool => $record && $record->status === 'closed')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update([
                                'status' => 'open',
                                'closed_by' => null,
                            ]);

                            Notification::make()
                                ->title('شەفت بە سەرکەوتوویی کرایەوە')
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
            ->poll('30s')

            // کۆی گشتی لە ژێرەوەی خشتە
            ->footer([
                'total' => function ($records) {
                    $totalLiter = 0;
                    $totalPrice = 0;
                    $morningTotal = 0;
                    $eveningTotal = 0;

                    foreach ($records as $record) {
                        $soldData = $record->sold_data ?? [];
                        foreach ($soldData as $catId => $liters) {
                            $totalLiter += $liters;
                            $category = Category::find($catId);
                            if ($category) {
                                $totalPrice += $liters * $category->current_price;
                            }
                        }

                        if ($record->shift === 'morning') {
                            $morningTotal += $record->total_amount;
                        } else {
                            $eveningTotal += $record->total_amount;
                        }
                    }

                    return new HtmlString('
                        <div class="bg-gray-50 p-4 rounded-lg mt-4 border border-gray-200">
                            <div class="grid grid-cols-4 gap-4">
                                <div class="bg-blue-50 p-3 rounded-lg text-center">
                                    <span class="text-sm text-gray-600 block">کۆی گشتی فرۆشراو</span>
                                    <span class="text-2xl font-bold text-blue-600">' . number_format($totalLiter) . ' لیتر</span>
                                </div>
                                <div class="bg-green-50 p-3 rounded-lg text-center">
                                    <span class="text-sm text-gray-600 block">کۆی گشتی (دینار)</span>
                                    <span class="text-2xl font-bold text-green-600">' . number_format($totalPrice) . ' د.ع</span>
                                </div>
                                <div class="bg-yellow-50 p-3 rounded-lg text-center">
                                    <span class="text-sm text-gray-600 block">کۆی شەفتی بەیانی</span>
                                    <span class="text-2xl font-bold text-yellow-600">' . number_format($morningTotal) . ' د.ع</span>
                                </div>
                                <div class="bg-indigo-50 p-3 rounded-lg text-center">
                                    <span class="text-sm text-gray-600 block">کۆی شەفتی ئێوارە</span>
                                    <span class="text-2xl font-bold text-indigo-600">' . number_format($eveningTotal) . ' د.ع</span>
                                </div>
                            </div>
                        </div>
                    ');
                },
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuickSales::route('/'),
            'create' => Pages\CreateQuickSale::route('/create'),
            'edit' => Pages\EditQuickSale::route('/{record}/edit'),
            'view' => Pages\ViewQuickSale::route('/{record}'),
        ];
    }
}
