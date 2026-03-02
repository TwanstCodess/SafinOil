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
use Illuminate\Support\HtmlString;

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
                        Forms\Components\DatePicker::make('sale_date')
                            ->label('ڕێکەوتی فرۆشتن')
                            ->required()
                            ->default(now())
                            ->unique(ignoreRecord: true)
                            ->displayFormat('Y/m/d'),

                        Forms\Components\Placeholder::make('categories_info')
                            ->label('کاتیگۆریەکان')
                            ->content(function () {
                                $categories = Category::with('type')->get();
                                $html = '<div class="grid grid-cols-3 gap-4">';

                                foreach ($categories as $category) {
                                    $color = match($category->type->key ?? '') {
                                        'fuel' => 'warning',
                                        'oil' => 'success',
                                        'gas' => 'info',
                                        default => 'gray',
                                    };

                                    $html .= "<div class='p-3 bg-{$color}-50 rounded-lg'>";
                                    $html .= "<span class='font-bold'>{$category->name}</span>";
                                    $html .= "<br><span class='text-sm'>نرخ: " . number_format($category->current_price) . " د.ع</span>";
                                    $html .= "<br><span class='text-sm'>کۆگا: " . number_format($category->stock_liters) . " لیتر</span>";
                                    $html .= "</div>";
                                }

                                $html .= '</div>';
                                return new HtmlString($html);
                            })
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('categories_data')
                            ->default(fn () => QuickSale::getCategoriesStructure()),

                        Forms\Components\Fieldset::make('خوێندنەوەی سەرەتایی')
                            ->schema(function () {
                                $fields = [];
                                $categories = Category::with('type')->get();

                                foreach ($categories as $category) {
                                    $fields[] = Forms\Components\TextInput::make("initial_readings.{$category->id}")
                                        ->label($category->name)
                                        ->numeric()
                                        ->default(0)
                                        ->suffix('لیتر')
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) use ($category) {
                                            // دوای هەر گۆڕانکاری، فرۆشراوەکان حساب بکە
                                            self::calculateSoldFields($set, $get);
                                        });
                                }

                                return $fields;
                            })
                            ->columns(3),

                        Forms\Components\Fieldset::make('خوێندنەوەی کۆتایی')
                            ->schema(function () {
                                $fields = [];
                                $categories = Category::with('type')->get();

                                foreach ($categories as $category) {
                                    $fields[] = Forms\Components\TextInput::make("final_readings.{$category->id}")
                                        ->label($category->name)
                                        ->numeric()
                                        ->default(0)
                                        ->suffix('لیتر')
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) use ($category) {
                                            self::calculateSoldFields($set, $get);
                                        });
                                }

                                return $fields;
                            })
                            ->columns(3),

                        Forms\Components\Fieldset::make('فرۆشراوەکان (حسابکراو)')
                            ->schema(function () {
                                $fields = [];
                                $categories = Category::with('type')->get();

                                foreach ($categories as $category) {
                                    $fields[] = Forms\Components\Placeholder::make("sold_display.{$category->id}")
                                        ->label($category->name)
                                        ->content(function (callable $get) use ($category) {
                                            $initial = $get("initial_readings.{$category->id}") ?? 0;
                                            $final = $get("final_readings.{$category->id}") ?? 0;
                                            $sold = $initial - $final;

                                            $color = $sold >= 0 ? 'text-success-600' : 'text-danger-600';
                                            return new HtmlString(
                                                "<span class='{$color} font-bold text-lg'>"
                                                . number_format($sold) . " لیتر</span>"
                                            );
                                        });
                                }

                                return $fields;
                            })
                            ->columns(3),

                        Forms\Components\Fieldset::make('جیاوازی')
                            ->schema(function () {
                                $fields = [];
                                $categories = Category::with('type')->get();

                                foreach ($categories as $category) {
                                    $fields[] = Forms\Components\Placeholder::make("difference_display.{$category->id}")
                                        ->label($category->name)
                                        ->content(function (callable $get) use ($category) {
                                            $initial = $get("initial_readings.{$category->id}") ?? 0;
                                            $final = $get("final_readings.{$category->id}") ?? 0;
                                            $reportedSold = $get("reported_sold.{$category->id}") ?? 0;
                                            $calculatedSold = $initial - $final;
                                            $difference = $reportedSold - $calculatedSold;

                                            $color = $difference == 0 ? 'text-gray-600' : ($difference > 0 ? 'text-success-600' : 'text-danger-600');
                                            $icon = $difference == 0 ? '✓' : ($difference > 0 ? '⬆' : '⬇');

                                            return new HtmlString(
                                                "<span class='{$color} font-bold'>"
                                                . $icon . ' ' . number_format(abs($difference)) . " لیتر</span>"
                                            );
                                        });
                                }

                                return $fields;
                            })
                            ->columns(3),

                        Forms\Components\Fieldset::make('فرۆشراوەکانی تۆ')
                            ->schema(function () {
                                $fields = [
                                    Forms\Components\Placeholder::make('info_text')
                                        ->content('ئەو بڕانە بنووسە کە خۆت فرۆشتوویت')
                                        ->columnSpanFull(),
                                ];

                                $categories = Category::with('type')->get();

                                foreach ($categories as $category) {
                                    $fields[] = Forms\Components\TextInput::make("reported_sold.{$category->id}")
                                        ->label($category->name)
                                        ->numeric()
                                        ->default(0)
                                        ->suffix('لیتر')
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) use ($category) {
                                            self::calculateSoldFields($set, $get);
                                        });
                                }

                                return $fields;
                            })
                            ->columns(3),

                        Forms\Components\TextInput::make('total_amount_display')
                            ->label('کۆی گشتی (دینار)')
                            ->disabled()
                            ->prefix('دینار')
                            ->formatStateUsing(function (callable $get) {
                                $total = 0;
                                $categories = Category::with('type')->get();

                                foreach ($categories as $category) {
                                    $initial = $get("initial_readings.{$category->id}") ?? 0;
                                    $final = $get("final_readings.{$category->id}") ?? 0;
                                    $sold = $initial - $final;
                                    $total += $sold * $category->current_price;
                                }

                                return number_format($total);
                            })
                            ->extraAttributes(['class' => 'text-primary-600 font-bold text-xl'])
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('total_amount'),
                    ]),
            ]);
    }

    private static function calculateSoldFields(callable $set, callable $get)
    {
        // ئەم میتۆدە دوای هەر گۆڕانکاریێک بانگ دەکرێت
        // بۆ نوێکردنەوەی total_amount
        $total = 0;
        $categories = Category::with('type')->get();

        foreach ($categories as $category) {
            $initial = $get("initial_readings.{$category->id}") ?? 0;
            $final = $get("final_readings.{$category->id}") ?? 0;
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
                    ->searchable(),

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
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('دروستکراو لەلایەن')
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
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('بینین'),

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

                Action::make('print_report')
                    ->label('چاپکردن')
                    ->icon('heroicon-m-printer')
                    ->color(Color::Blue)
                    ->url(fn ($record) => route('quick-sale.print', $record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('sale_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuickSales::route('/'),
            'create' => Pages\CreateQuickSale::route('/create'),
        ];
    }
}
