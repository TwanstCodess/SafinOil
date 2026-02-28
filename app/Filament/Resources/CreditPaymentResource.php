<?php
// app/Filament/Resources/CreditPaymentResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\CreditPaymentResource\Pages;
use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\Sale;
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
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class CreditPaymentResource extends Resource
{
    protected static ?string $model = CreditPayment::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'بەشی کڕیاران';
    protected static ?string $modelLabel = 'دانەوەی قەرز';
    protected static ?string $pluralModelLabel = 'دانەوەی قەرز';
    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری دانەوەی قەرز')
                    ->description('زانیاری پارەدان بۆ قەرزەکان')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        // ڕیز یەکەم: کڕیار و قەرزی ماوە
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('customer_id')
                                    ->label('کڕیار (قەرزدار)')
                                    ->relationship(
                                        name: 'customer',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query->where('current_debt', '>', 0)
                                    )
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $customer = Customer::find($state);
                                        if ($customer) {
                                            $set('customer_debt', $customer->current_debt);
                                            $set('reference_number', self::generateReferenceNumber($customer));
                                            $set('sale_id', null);
                                            $set('remaining_amount', 0);

                                            if ($customer->current_debt > 0) {
                                                Notification::make()
                                                    ->info()
                                                    ->title('زانیاری قەرز')
                                                    ->body("قەرزی ماوە: " . number_format($customer->current_debt) . " دینار")
                                                    ->send();
                                            }
                                        }
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('ناوی کڕیار')
                                            ->required(),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('ژمارە مۆبایل')
                                            ->tel(),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $customer = Customer::create($data);
                                        Notification::make()
                                            ->success()
                                            ->title('کڕیار دروستکرا')
                                            ->body("کڕیار {$customer->name} بە سەرکەوتوویی دروستکرا")
                                            ->send();
                                        return $customer;
                                    })
                                    ->extraAttributes(['class' => 'font-bold']),

                                Forms\Components\Placeholder::make('customer_debt_placeholder')
                                    ->label('قەرزی ماوە')
                                    ->content(function (callable $get) {
                                        $debt = $get('customer_debt') ?? 0;
                                        $color = $debt > 0 ? 'text-danger-600' : 'text-success-600';
                                        return new HtmlString(
                                            "<span class='{$color} font-bold text-lg'>" . number_format($debt) . " دینار</span>"
                                        );
                                    }),

                                Forms\Components\Hidden::make('customer_debt')
                                    ->default(0),
                            ]),

                        // ڕیز دووەم: فرۆشتن و بڕی ماوە
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('sale_id')
                                    ->label('فرۆشتنە قەرزەکان')
                                    ->options(function (callable $get) {
                                        $customerId = $get('customer_id');
                                        if (!$customerId) return [];

                                        return Sale::where('customer_id', $customerId)
                                            ->whereIn('status', ['pending', 'partial'])
                                            ->where('remaining_amount', '>', 0)
                                            ->get()
                                            ->mapWithKeys(fn ($sale) => [
                                                $sale->id => "فرۆشتن #{$sale->id} - " . number_format($sale->remaining_amount) . ' دینار'
                                            ]);
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state) {
                                            $sale = Sale::find($state);
                                            if ($sale) {
                                                $set('remaining_amount', $sale->remaining_amount);

                                                $amount = $get('amount') ?? 0;
                                                if ($amount > $sale->remaining_amount && $sale->remaining_amount > 0) {
                                                    Notification::make()
                                                        ->warning()
                                                        ->title('ئاگادار!')
                                                        ->body("بڕی پارەدان زیاترە لە قەرزی ئەم فرۆشتنە")
                                                        ->send();
                                                }
                                            }
                                        } else {
                                            $set('remaining_amount', 0);
                                        }
                                    })
                                    ->searchable()
                                    ->placeholder('هەڵبژاردنی فرۆشتن (ئارادی)')
                                    ->helperText('ئەگەر فرۆشتنێک دیاری نەکەیت، پارەکە بە شێوەی گشتی دادەنرێت'),

                                Forms\Components\Placeholder::make('remaining_amount_placeholder')
                                    ->label('بڕی ماوە')
                                    ->content(function (callable $get) {
                                        $remaining = $get('remaining_amount') ?? 0;
                                        $color = $remaining > 0 ? 'text-warning-600' : 'text-gray-400';
                                        return new HtmlString(
                                            "<span class='{$color} font-bold'>" . number_format($remaining) . " دینار</span>"
                                        );
                                    }),

                                Forms\Components\Hidden::make('remaining_amount')
                                    ->default(0),
                            ]),

                        // ڕیز سێیەم: بڕی پارەدان، شێواز و ژمارەی سەرچاوە
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('بڕی پارەدان')
                                    ->numeric()
                                    ->required()
                                    ->prefix('دینار')
                                    ->minValue(1)
                                    ->maxValue(function (callable $get) {
                                        return $get('customer_debt') ?? 0;
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $customerDebt = $get('customer_debt') ?? 0;

                                        if ($state > $customerDebt) {
                                            Notification::make()
                                                ->danger()
                                                ->title('هەڵە!')
                                                ->body("بڕی پارەدان نابێت زیاتر بێت لە " . number_format($customerDebt) . " دینار")
                                                ->send();

                                            $set('amount', $customerDebt);
                                        }
                                    })
                                    ->helperText(function (callable $get) {
                                        $debt = $get('customer_debt') ?? 0;
                                        return "کۆی قەرز: " . number_format($debt) . " دینار";
                                    }),

                                Forms\Components\Select::make('payment_method')
                                    ->label('شێوازی پارەدان')
                                    ->options([
                                        'cash' => 'پارەی ڕاستەوخۆ',
                                        'bank' => 'بانک',
                                        'cheque' => 'چێک',
                                    ])
                                    ->required()
                                    ->default('cash'),

                                Forms\Components\TextInput::make('reference_number')
                                    ->label('ژمارەی سەرچاوە')
                                    ->maxLength(255)
                                    ->placeholder('ئۆتۆماتیکی دروستدەبێت')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->default(function (callable $get) {
                                        $customerId = $get('customer_id');
                                        if ($customerId) {
                                            $customer = Customer::find($customerId);
                                            return $customer ? self::generateReferenceNumber($customer) : self::generateReferenceNumber();
                                        }
                                        return self::generateReferenceNumber();
                                    }),
                            ]),

                        // ڕیز چوارەم: ڕێکەوت و ژمارەی مامەڵە
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('payment_date')
                                    ->label('ڕێکەوتی پارەدان')
                                    ->required()
                                    ->default(now())
                                    ->displayFormat('Y/m/d')
                                    ->native(false),

                                Forms\Components\TextInput::make('transaction_number')
                                    ->label('ژمارەی مامەڵە')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visibleOn('edit'),
                            ]),

                        // تێبینی
                        Forms\Components\Textarea::make('notes')
                            ->label('تێبینی')
                            ->placeholder('تێبینی زیاتر لەبارەی ئەم پارەدانە')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ژمارە')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('ڕێکەوت')
                    ->date('Y/m/d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('کڕیار')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('sale_id')
                    ->label('ژ. فرۆشتن')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => $state ? "#{$state}" : '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('بڕی پارە')
                    ->money('IQD')
                    ->weight('bold')
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('شێواز')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'cash' => 'success',
                        'bank' => 'info',
                        'cheque' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'cash' => 'پارەی ڕاستەوخۆ',
                        'bank' => 'بانک',
                        'cheque' => 'چێک',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('reference_number')
                    ->label('ژ. سەرچاوە')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تۆمارکرا لە')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // فلتەری کڕیار
                SelectFilter::make('customer_id')
                    ->label('فلتەری کڕیار')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->placeholder('هەموو کڕیاران')
                    ->indicator('کڕیار'),

                // فلتەری شێوازی پارەدان
                SelectFilter::make('payment_method')
                    ->label('شێوازی پارەدان')
                    ->options([
                        'cash' => 'پارەی ڕاستەوخۆ',
                        'bank' => 'بانک',
                        'cheque' => 'چێک',
                    ])
                    ->multiple()
                    ->placeholder('هەموو شێوازەکان')
                    ->indicator('شێوازی پارەدان'),

                // فلتەری مەودای بەروار
                Filter::make('payment_date')
                    ->label('مەودای بەروار')
                    ->form([
                        DatePicker::make('from')
                            ->label('لە بەرواری')
                            ->placeholder('YYYY-MM-DD')
                            ->displayFormat('Y/m/d')
                            ->native(false)
                            ->closeOnDateSelection(),
                        DatePicker::make('until')
                            ->label('تا بەرواری')
                            ->placeholder('YYYY-MM-DD')
                            ->displayFormat('Y/m/d')
                            ->native(false)
                            ->closeOnDateSelection(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn ($q) => $q->whereDate('payment_date', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn ($q) => $q->whereDate('payment_date', '<=', $data['until'])
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

                        if (count($indicators) > 0) {
                            return 'بەروار: ' . implode(' - ', $indicators);
                        }

                        return null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری مەودای بڕی پارە
                Filter::make('amount_range')
                    ->label('مەودای بڕی پارە')
                    ->form([
                        TextInput::make('min_amount')
                            ->label('کەمترین بڕ')
                            ->numeric()
                            ->prefix('دینار')
                            ->minValue(0)
                            ->placeholder('١٠٠٠'),
                        TextInput::make('max_amount')
                            ->label('زۆرترین بڕ')
                            ->numeric()
                            ->prefix('دینار')
                            ->minValue(0)
                            ->placeholder('١٠٠٠٠٠'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn ($q) => $q->where('amount', '>=', $data['min_amount'])
                            )
                            ->when(
                                $data['max_amount'],
                                fn ($q) => $q->where('amount', '<=', $data['max_amount'])
                            );
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];

                        if ($data['min_amount'] ?? null) {
                            $indicators[] = 'کەمتر نییە لە ' . number_format($data['min_amount']) . ' د.ع';
                        }

                        if ($data['max_amount'] ?? null) {
                            $indicators[] = 'زیاتر نییە لە ' . number_format($data['max_amount']) . ' د.ع';
                        }

                        if (count($indicators) > 0) {
                            return 'بڕی پارە: ' . implode(' و ', $indicators);
                        }

                        return null;
                    })
                    ->columns(2)
                    ->columnSpan(2),

                // فلتەری پەیوەندی بە فرۆشتن
                TernaryFilter::make('has_sale')
                    ->label('پەیوەندی بە فرۆشتن')
                    ->placeholder('هەموو')
                    ->trueLabel('بە فرۆشتنەوە')
                    ->falseLabel('بێ فرۆشتن (پارەدانی گشتی)')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('sale_id'),
                        false: fn ($query) => $query->whereNull('sale_id'),
                    )
                    ->indicator('فرۆشتن'),

                // فلتەری ئەمڕۆ
                Filter::make('today')
                    ->label('پارەدانی ئەمڕۆ')
                    ->toggle()
                    ->query(fn ($query) => $query->whereDate('payment_date', today()))
                    ->indicator('ئەمڕۆ'),

                // فلتەری ئەم هەفتەیە
                Filter::make('this_week')
                    ->label('پارەدانی ئەم هەفتەیە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereBetween('payment_date', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->indicator('ئەم هەفتەیە'),

                // فلتەری ئەم مانگە
                Filter::make('this_month')
                    ->label('پارەدانی ئەم مانگە')
                    ->toggle()
                    ->query(fn ($query) => $query->whereMonth('payment_date', now()->month)
                        ->whereYear('payment_date', now()->year))
                    ->indicator('ئەم مانگە'),

                // فلتەری ژمارەی سەرچاوە
                Filter::make('reference_number')
                    ->label('ژمارەی سەرچاوە')
                    ->form([
                        TextInput::make('reference')
                            ->label('ژمارەی سەرچاوە')
                            ->placeholder('REF-20250228-...')
                            ->maxLength(255),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['reference'],
                                fn ($q) => $q->where('reference_number', 'LIKE', '%' . $data['reference'] . '%')
                            );
                    })
                    ->indicateUsing(function (array $data) {
                        if ($data['reference'] ?? null) {
                            return 'ژ. سەرچاوە: ' . $data['reference'];
                        }
                        return null;
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->persistFiltersInSession()
            ->filtersTriggerAction(
                fn ($action) => $action
                    ->button()
                    ->label('فلتەری پێشکەوتوو')
                    ->icon('heroicon-m-funnel')
                    ->color('gray')
            )
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('بینین'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('سڕینەوەی دیاریکراوەکان')
                        ->modalHeading('سڕینەوەی پارەدانەکان')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم پارەدانانە؟')
                        ->modalSubmitActionLabel('بەڵێ، بیانسڕەوە'),
                ]),
            ])
            ->defaultSort('payment_date', 'desc')
            ->poll('10s');
    }

    /**
     * ژمارەی سەرچاوە دروستبکە
     */
    public static function generateReferenceNumber($customer = null): string
    {
        $prefix = 'REF';
        $year = now()->format('Y');
        $month = now()->format('m');
        $day = now()->format('d');

        // بەشێک لە ناوی کڕیار (ئەگەر هەبێت)
        $customerCode = '';
        if ($customer && $customer->name) {
            $words = explode(' ', $customer->name);
            foreach ($words as $word) {
                if (!empty($word)) {
                    $customerCode .= Str::substr($word, 0, 1);
                }
            }
        }

        // بەشی هەڕەمەکی (تەنها پیت)
        $random = '';
        for ($i = 0; $i < 4; $i++) {
            $random .= chr(rand(65, 90));
        }

        // ژمارەی یەکتا
        $count = CreditPayment::whereDate('created_at', today())->count() + 1;
        $countStr = str_pad($count, 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}{$month}{$day}-{$customerCode}{$random}-{$countStr}";
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
            'index' => Pages\ListCreditPayments::route('/'),
            'create' => Pages\CreateCreditPayment::route('/create'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereDate('payment_date', today())->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
