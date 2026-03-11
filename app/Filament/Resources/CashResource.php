<?php
// app/Filament/Resources/CashResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\CashResource\Pages;
use App\Models\Cash;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\ActionGroup;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\IconEntry;
use Filament\Support\RawJs;
use Illuminate\Support\HtmlString;

class CashResource extends Resource
{
    protected static ?string $model = Cash::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = '🏦 بەشی دارایی';
    protected static ?string $modelLabel = 'قیاسەی دارایی';
    protected static ?string $pluralModelLabel = 'قیاسەی دارایی';
    protected static ?string $recordTitleAttribute = 'id';
    protected static ?string $slug = 'cash';
    protected static ?int $navigationSort = 1;

    /**
     * فۆرمی بینینی وردەکاری - دیزاینی کارتی دارایی
     */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // بەشی سەرەکی - کارتی ڕەوشتی دارایی
                Section::make('💳 کارتی ڕەوشتی دارایی')
                    ->description(new HtmlString('
                        <div class="text-sm text-gray-600">
                            ⏱️ دوایین نوێکردنەوە: ' . now()->format('Y/m/d H:i') . '
                        </div>
                    '))
                    ->icon('heroicon-o-presentation-chart-line')
                    ->schema([
                        // کارتی سەرەکی - وەک کارتی بانکی
                        Grid::make(1)
                            ->schema([
                                Group::make()
                                    ->schema([
                                        TextEntry::make('balance')
                                            ->label('')
                                            ->getStateUsing(function ($record) {
                                                return self::formatCardBalance($record->balance);
                                            })
                                            ->extraAttributes(['class' => 'text-center']),
                                    ])
                                    ->extraAttributes([
                                        'class' => 'bg-gradient-to-r from-blue-600 to-blue-800 text-white p-6 rounded-xl shadow-lg'
                                    ]),
                            ]),

                        // کارتی زانیاری ورد
                        Grid::make(3)
                            ->schema([
                                // کارتی سەرمایە
                                Group::make()
                                    ->schema([
                                        TextEntry::make('capital')
                                            ->label('💰 سەرمایەی سەرەتایی')
                                            ->getStateUsing(function ($record) {
                                                return self::formatMoneyWithTooltip($record->capital, 'سەرمایەی دانراو لە سەرەتای کار');
                                            })
                                            ->color('success')
                                            ->size(TextEntry\TextEntrySize::Large),

                                        TextEntry::make('capital_date')
                                            ->label('ڕێکەوتی دانان')
                                            ->getStateUsing(fn ($record) => $record->created_at->format('Y/m/d'))
                                            ->color('gray'),
                                    ])
                                    ->extraAttributes(['class' => 'border border-green-200 rounded-lg p-4 bg-green-50']),

                                // کارتی داهات
                                Group::make()
                                    ->schema([
                                        TextEntry::make('total_income')
                                            ->label('📈 کۆی داهات')
                                            ->getStateUsing(function ($record) {
                                                return self::formatMoneyWithTooltip($record->total_income, 'کۆی گشتی پارەی وەرگیراو لە فرۆشتن و سەرچاوەکانی تر');
                                            })
                                            ->color('success')
                                            ->size(TextEntry\TextEntrySize::Large),

                                        TextEntry::make('income_count')
                                            ->label('ژمارەی مامەڵە')
                                            ->getStateUsing(fn () => Transaction::whereIn('type', ['sale', 'income'])->count() . ' مامەڵە')
                                            ->color('gray'),
                                    ])
                                    ->extraAttributes(['class' => 'border border-blue-200 rounded-lg p-4 bg-blue-50']),

                                // کارتی خەرجی
                                Group::make()
                                    ->schema([
                                        TextEntry::make('total_expense')
                                            ->label('📉 کۆی خەرجی')
                                            ->getStateUsing(function ($record) {
                                                return self::formatMoneyWithTooltip($record->total_expense, 'کۆی گشتی پارەی بەکارهاتوو بۆ کڕین و خەرجییەکان');
                                            })
                                            ->color('danger')
                                            ->size(TextEntry\TextEntrySize::Large),

                                        TextEntry::make('expense_count')
                                            ->label('ژمارەی مامەڵە')
                                            ->getStateUsing(fn () => Transaction::whereIn('type', ['purchase', 'expense'])->count() . ' مامەڵە')
                                            ->color('gray'),
                                    ])
                                    ->extraAttributes(['class' => 'border border-red-200 rounded-lg p-4 bg-red-50']),
                            ]),

                        // کارتی قازانج
                        Grid::make(1)
                            ->schema([
                                Group::make()
                                    ->schema([
                                        TextEntry::make('profit')
                                            ->label('💹 قازانجی خاوێن')
                                            ->getStateUsing(function ($record) {
                                                $profit = $record->profit;
                                                $percentage = $record->capital > 0 ? round(($profit / $record->capital) * 100, 2) : 0;

                                                $icon = $profit >= 0 ? '📈' : '📉';
                                                $color = $profit >= 0 ? 'text-green-600' : 'text-red-600';

                                                return new HtmlString("
                                                    <div class='flex items-center justify-between'>
                                                        <span class='text-2xl font-bold {$color}'>
                                                            {$icon} " . number_format($profit) . " دینار
                                                        </span>
                                                        <span class='text-sm bg-gray-100 px-3 py-1 rounded-full'>
                                                            ڕێژەی قازانج: {$percentage}%
                                                        </span>
                                                    </div>
                                                ");
                                            })
                                            ->extraAttributes(['class' => 'w-full']),
                                    ])
                                    ->extraAttributes(['class' => 'border border-purple-200 rounded-lg p-6 bg-gradient-to-r from-purple-50 to-pink-50']),
                            ]),
                    ]),

                // بەشی دووەم - شیکردنەوەی دارایی
                Section::make('📊 شیکردنەوەی دۆخی دارایی')
                    ->description('ڕێژە و ڕادارە داراییەکان')
                    ->icon('heroicon-o-chart-pie')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                // ڕێژەی قازانج
                                Group::make()
                                    ->schema([
                                        TextEntry::make('profit_margin')
                                            ->label('ڕێژەی قازانج')
                                            ->getStateUsing(function ($record) {
                                                if ($record->total_income == 0) return '0%';
                                                $margin = ($record->profit / $record->total_income) * 100;
                                                return self::formatPercentage($margin);
                                            })
                                            ->extraAttributes(['class' => 'text-center']),

                                        TextEntry::make('profit_margin_desc')
                                            ->label('')
                                            ->getStateUsing(function ($record) {
                                                if ($record->total_income == 0) return 'هیچ فرۆشتنێک نییە';
                                                $margin = ($record->profit / $record->total_income) * 100;
                                                return self::getProfitDescription($margin);
                                            })
                                            ->color('gray')
                                            ->size(TextEntry\TextEntrySize::Small),
                                    ])
                                    ->extraAttributes(['class' => 'border rounded-lg p-3']),

                                // ڕێژەی خەرجی
                                Group::make()
                                    ->schema([
                                        TextEntry::make('expense_ratio')
                                            ->label('ڕێژەی خەرجی')
                                            ->getStateUsing(function ($record) {
                                                if ($record->total_income == 0) return '100%';
                                                $ratio = ($record->total_expense / $record->total_income) * 100;
                                                return self::formatPercentage($ratio);
                                            }),

                                        IconEntry::make('expense_status')
                                            ->label('')
                                            ->boolean()
                                            ->trueIcon('heroicon-o-check-circle')
                                            ->falseIcon('heroicon-o-x-circle')
                                            ->trueColor('success')
                                            ->falseColor('danger')
                                            ->getStateUsing(fn ($record) => $record->total_expense <= $record->total_income * 0.7),
                                    ])
                                    ->extraAttributes(['class' => 'border rounded-lg p-3']),

                                // گەڕانەوەی سەرمایە
                                Group::make()
                                    ->schema([
                                        TextEntry::make('roi')
                                            ->label('گەڕانەوەی سەرمایە')
                                            ->getStateUsing(function ($record) {
                                                if ($record->capital == 0) return '0%';
                                                $roi = (($record->balance - $record->capital) / $record->capital) * 100;
                                                return self::formatPercentage($roi);
                                            }),

                                        TextEntry::make('roi_desc')
                                            ->label('')
                                            ->getStateUsing(function ($record) {
                                                $profit = $record->balance - $record->capital;
                                                return $profit >= 0 ? 'قازانج' : 'زیان';
                                            })
                                            ->color(fn ($record) => ($record->balance - $record->capital) >= 0 ? 'success' : 'danger'),
                                    ])
                                    ->extraAttributes(['class' => 'border rounded-lg p-3']),

                                // ماوەی کارکردن
                                Group::make()
                                    ->schema([
                                        TextEntry::make('operating_days')
                                            ->label('ماوەی کارکردن')
                                            ->getStateUsing(function ($record) {
                                                $days = now()->diffInDays($record->created_at);
                                                return $days . ' ڕۆژ';
                                            }),

                                        TextEntry::make('avg_daily')
                                            ->label('تێکڕای ڕۆژانە')
                                            ->getStateUsing(function ($record) {
                                                $days = max(now()->diffInDays($record->created_at), 1);
                                                $avg = $record->profit / $days;
                                                return number_format($avg) . ' د.ع/ڕۆژ';
                                            }),
                                    ])
                                    ->extraAttributes(['class' => 'border rounded-lg p-3']),
                            ]),
                    ]),

                // بەشی سێیەم - ڕێنمایی و پێشنیار
                Section::make('💡 ڕێنمایی و پێشنیار')
                    ->description('چۆن دۆخی دارایی خۆت باشتر بکەیت')
                    ->icon('heroicon-o-light-bulb')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Group::make()
                                    ->schema([
                                        TextEntry::make('advice')
                                            ->label('')
                                            ->getStateUsing(function ($record) {
                                                return self::generateAdvice($record);
                                            })
                                            ->markdown()
                                            ->extraAttributes(['class' => 'bg-gray-50 p-6 rounded-lg leading-relaxed']),
                                    ]),

                                Group::make()
                                    ->schema([
                                        TextEntry::make('quick_stats')
                                            ->label('ئامارە خێراکان')
                                            ->getStateUsing(function ($record) {
                                                $totalTransactions = Transaction::count();
                                                $todayTransactions = Transaction::whereDate('created_at', today())->count();

                                                return new HtmlString("
                                                    <div class='space-y-2'>
                                                        <div class='flex justify-between border-b pb-2'>
                                                            <span>📊 کۆی مامەڵەکان:</span>
                                                            <span class='font-bold'>{$totalTransactions}</span>
                                                        </div>
                                                        <div class='flex justify-between border-b pb-2'>
                                                            <span>📅 مامەڵەی ئەمڕۆ:</span>
                                                            <span class='font-bold'>{$todayTransactions}</span>
                                                        </div>
                                                        <div class='flex justify-between border-b pb-2'>
                                                            <span>💰 ڕەوشتی قاسە:</span>
                                                            <span class='font-bold text-primary-600'>" . number_format($record->balance) . " د.ع</span>
                                                        </div>
                                                        <div class='flex justify-between'>
                                                            <span>📈 قازانج:</span>
                                                            <span class='font-bold " . ($record->profit >= 0 ? 'text-success-600' : 'text-danger-600') . "'>
                                                                " . number_format($record->profit) . " د.ع
                                                            </span>
                                                        </div>
                                                    </div>
                                                ");
                                            })
                                            ->extraAttributes(['class' => 'bg-blue-50 p-6 rounded-lg']),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    /**
     * فۆرمی دروستکردن و دەستکاری
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('📝 زانیاری گشتی')
                    ->description('پوختەی ڕەوشتی دارایی')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('balance')
                                    ->label('💰 ڕەوشتی قاسە')
                                    ->numeric()
                                    ->prefix('دینار')
                                    ->disabled()
                                    ->default(0)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->helperText('ئەم بڕە نوێ دەکرێتەوە کاتێک فرۆشتن یان خەرجی تۆمار دەکەیت')
                                    ->extraAttributes(['class' => 'text-primary-600 font-bold']),

                                Forms\Components\TextInput::make('capital')
                                    ->label('🏦 سەرمایە')
                                    ->numeric()
                                    ->prefix('دینار')
                                    ->disabled()
                                    ->default(0)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->helperText('سەرمایەی سەرەتایی کاتێک دەستت بە کار کردووە')
                                    ->extraAttributes(['class' => 'text-success-600 font-bold']),

                                Forms\Components\TextInput::make('profit')
                                    ->label('📊 قازانج')
                                    ->numeric()
                                    ->prefix('دینار')
                                    ->disabled()
                                    ->default(0)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->helperText('داهات - خەرجی = قازانجی خاوێن')
                                    ->extraAttributes(['class' => 'text-warning-600 font-bold']),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_income')
                                    ->label('📈 کۆی داهات')
                                    ->numeric()
                                    ->disabled()
                                    ->prefix('دینار')
                                    ->helperText('کۆی گشتی پارەی وەرگیراو لە فرۆشتنەکان')
                                    ->mask(RawJs::make('$money($input)')),

                                Forms\Components\TextInput::make('total_expense')
                                    ->label('📉 کۆی خەرجی')
                                    ->numeric()
                                    ->disabled()
                                    ->prefix('دینار')
                                    ->helperText('کۆی گشتی پارەی بەکارهاتوو بۆ کڕین و خەرجی')
                                    ->mask(RawJs::make('$money($input)')),
                            ]),

                        Forms\Components\DateTimePicker::make('last_update')
                            ->label('⏱️ دوایین نوێکردنەوە')
                            ->disabled()
                            ->helperText('کاتی دوایین گۆڕانکاری لە قاسە')
                            ->displayFormat('Y/m/d H:i'),
                    ]),
            ]);
    }

    /**
     * خشتەی نیشاندان - دیزاینی کارتی
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    // بەشی ڕاست - کارتی سەرەکی
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('card_view')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                return self::formatCardPreview($record);
                            })
                            ->html(),
                    ]),

                    // بەشی چەپ - زانیاری خێرا
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('quick_info')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                $profitColor = $record->profit >= 0 ? 'text-success-600' : 'text-danger-600';
                                $profitIcon = $record->profit >= 0 ? '↑' : '↓';

                                return new HtmlString("
                                    <div class='space-y-1 text-sm'>
                                        <div class='flex justify-between'>
                                            <span class='text-gray-600'>💰 قاسە:</span>
                                            <span class='font-bold text-primary-600'>" . number_format($record->balance) . "</span>
                                        </div>
                                        <div class='flex justify-between'>
                                            <span class='text-gray-600'>🏦 سەرمایە:</span>
                                            <span class='font-bold text-success-600'>" . number_format($record->capital) . "</span>
                                        </div>
                                        <div class='flex justify-between'>
                                            <span class='text-gray-600'>📊 قازانج:</span>
                                            <span class='font-bold {$profitColor}'>
                                                {$profitIcon} " . number_format(abs($record->profit)) . "
                                            </span>
                                        </div>
                                    </div>
                                ");
                            })
                            ->html(),
                    ]),
                ])->from('md'),
            ])
// لە بری ئەوەی هەڵەکەت هەیبوو، ئەم کۆدە دابنێ
->filters([
    Filter::make('balance_range')
        ->label('پێوەری بڕی پارە')
        ->form([
            Forms\Components\TextInput::make('min_balance')
                ->label('کەمترین')
                ->numeric()
                ->prefix('دینار')
                ->placeholder('١٠٠،٠٠٠')
                ->mask(RawJs::make('$money($input)')),
            Forms\Components\TextInput::make('max_balance')
                ->label('زۆرترین')
                ->numeric()
                ->prefix('دینار')
                ->placeholder('١،٠٠٠،٠٠٠')
                ->mask(RawJs::make('$money($input)')),
        ])
        ->query(function ($query, array $data) {
            return $query
                ->when($data['min_balance'] ?? null, fn ($q, $value) => $q->where('balance', '>=', $value))
                ->when($data['max_balance'] ?? null, fn ($q, $value) => $q->where('balance', '<=', $value));
        })
        ->indicateUsing(function (array $data) {
            $indicators = [];
            if ($data['min_balance'] ?? null) {
                $indicators[] = '≥ ' . number_format($data['min_balance']) . ' د.ع';
            }
            if ($data['max_balance'] ?? null) {
                $indicators[] = '≤ ' . number_format($data['max_balance']) . ' د.ع';
            }
            return $indicators ? 'بڕی پارە: ' . implode(' و ', $indicators) : null;
        }),

    // **ئەم بەشە چاککراوە** - بەکارهێنانی Forms\Components\Select
    Filter::make('profit_status')
        ->label('ڕەوشتی قازانج')
        ->form([
            Forms\Components\Select::make('value')
                ->label('ڕەوشت')
                ->options([
                    'profitable' => '💰 قازانج',
                    'loss' => '📉 زیان',
                    'break_even' => '⚖️ یەکسان',
                ])
                ->placeholder('هەموو')
        ])
        ->query(function ($query, array $data) {
            if (empty($data['value'])) return $query;

            return match($data['value']) {
                'profitable' => $query->where('profit', '>', 0),
                'loss' => $query->where('profit', '<', 0),
                'break_even' => $query->where('profit', 0),
                default => $query,
            };
        })
        ->indicateUsing(function (array $data) {
            if (empty($data['value'])) return null;

            $labels = [
                'profitable' => '💰 قازانج',
                'loss' => '📉 زیان',
                'break_even' => '⚖️ یەکسان',
            ];

            return 'ڕەوشتی قازانج: ' . ($labels[$data['value']] ?? $data['value']);
        }),

    Filter::make('date_range')
        ->label('مەودای کاتی')
        ->form([
            Forms\Components\DatePicker::make('from_date')
                ->label('لە')
                ->placeholder('YYYY-MM-DD'),
            Forms\Components\DatePicker::make('to_date')
                ->label('تا')
                ->placeholder('YYYY-MM-DD'),
        ])
        ->query(function ($query, array $data) {
            return $query
                ->when($data['from_date'] ?? null, fn ($q, $date) => $q->whereDate('last_update', '>=', $date))
                ->when($data['to_date'] ?? null, fn ($q, $date) => $q->whereDate('last_update', '<=', $date));
        })
        ->indicateUsing(function (array $data) {
            $indicators = [];
            if ($data['from_date'] ?? null) {
                $indicators[] = 'لە ' . \Carbon\Carbon::parse($data['from_date'])->format('Y/m/d');
            }
            if ($data['to_date'] ?? null) {
                $indicators[] = 'تا ' . \Carbon\Carbon::parse($data['to_date'])->format('Y/m/d');
            }
            return $indicators ? 'مەودای کاتی: ' . implode(' - ', $indicators) : null;
        }),
    ])


            ->filtersLayout(Tables\Enums\FiltersLayout::Modal)
            ->persistFiltersInSession()
            ->filtersTriggerAction(
                fn ($action) => $action
                    ->button()
                    ->label('فلتەر')
                    ->icon('heroicon-m-funnel')
                    ->color('gray')
            )

            ->headerActions([
                ActionGroup::make([
                    Action::make('add_capital')
                        ->label('➕ زیادکردنی سەرمایە')
                        ->icon('heroicon-o-banknotes')
                        ->color(Color::Green)
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label('بڕی سەرمایە')
                                ->numeric()
                                ->required()
                                ->prefix('دینار')
                                ->minValue(1000)
                                ->mask(RawJs::make('$money($input)'))
                                ->helperText('ئەم بڕە وەک سەرمایەی زیادە دادەنرێت'),

                            Forms\Components\Textarea::make('description')
                                ->label('تێبینی')
                                ->placeholder('نموونە: زیادکردنی سەرمایە بۆ فراوانکردنی کار')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\DatePicker::make('date')
                                ->label('ڕێکەوت')
                                ->default(now())
                                ->required(),
                        ])
                        ->action(function (array $data) {
                            $cash = Cash::first() ?? Cash::initialize(0, 0);
                            $cash->addCapital($data['amount'], $data['description'], $data['date']);

                            Notification::make()
                                ->title('✅ سەرمایە زیاد کرا')
                                ->body(self::formatSuccessMessage('capital_added', $data['amount']))
                                ->success()
                                ->send();
                        })
                        ->modalHeading('زیادکردنی سەرمایە')
                        ->modalDescription(new HtmlString('
                            <div class="text-sm text-gray-600">
                                <p>⚠️ <strong>تێبینی:</strong> ئەم کردارە ڕاستەوخۆ سەرمایەی کۆمپانیا زیاد دەکات.</p>
                                <p class="mt-1">💰 سەرمایەی ئێستا: ' . number_format(optional(Cash::first())->capital ?? 0) . ' دینار</p>
                            </div>
                        '))
                        ->modalIcon('heroicon-o-banknotes'),

                    Action::make('withdraw_capital')
                        ->label('➖ کەمکردنەوەی سەرمایە')
                        ->icon('heroicon-o-arrow-trending-down')
                        ->color(Color::Red)
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label('بڕی سەرمایە')
                                ->numeric()
                                ->required()
                                ->prefix('دینار')
                                ->minValue(1000)
                                ->mask(RawJs::make('$money($input)'))
                                ->helperText('سەرمایەی بوونی: ' . number_format(optional(Cash::first())->capital ?? 0) . ' دینار')
                                ->rule(fn () => 'max:' . (optional(Cash::first())->capital ?? 0)),

                            Forms\Components\Select::make('reason')
                                ->label('هۆکار')
                                ->options([
                                    'withdraw' => '💸 وەرگرتنی قازانج',
                                    'loss' => '📉 زیان',
                                    'investment' => '📈 وەبەرهێنانی تر',
                                    'personal' => '👤 پێویستی کەسی',
                                    'other' => '🔄 هۆکاری تر',
                                ])
                                ->required(),

                            Forms\Components\Textarea::make('description')
                                ->label('وەسف')
                                ->required()
                                ->placeholder('نموونە: وەرگرتنی قازانج بۆ خاوەن پشک')
                                ->maxLength(255),

                            Forms\Components\Toggle::make('confirm')
                                ->label('دڵنیام لە کەمکردنەوەی سەرمایە')
                                ->required()
                                ->helperText('ئەم کردارە سەرمایەی کۆمپانیا کەم دەکاتەوە'),
                        ])
                        ->action(function (array $data) {
                            $cash = Cash::first();

                            if (!$cash || $cash->capital < $data['amount']) {
                                Notification::make()
                                    ->title('❌ هەڵە!')
                                    ->body('سەرمایەی پێویست بوونی نییە')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $reasonText = match($data['reason']) {
                                'withdraw' => 'وەرگرتنی قازانج',
                                'loss' => 'زیان',
                                'investment' => 'وەبەرهێنانی تر',
                                'personal' => 'پێویستی کەسی',
                                default => 'هۆکاری تر',
                            };

                            $description = $reasonText . ' - ' . $data['description'];
                            $cash->withdrawCapital($data['amount'], $description, now());

                            Notification::make()
                                ->title('✅ سەرمایە کەم کرایەوە')
                                ->body(self::formatSuccessMessage('capital_withdrawn', $data['amount']))
                                ->success()
                                ->send();
                        })
                        ->modalHeading('کەمکردنەوەی سەرمایە')
                        ->modalDescription(new HtmlString('
                            <div class="text-sm text-red-600">
                                <p>⚠️ <strong>ئاگادار:</strong> کەمکردنەوەی سەرمایە کاریگەری لەسەر قازانج دەبێت!</p>
                            </div>
                        '))
                        ->visible(fn () => optional(Cash::first())->capital > 0),

                    Action::make('view_transactions')
                        ->label('📋 بینینی مامەڵەکان')
                        ->icon('heroicon-o-document-text')
                        ->color(Color::Blue)
                        ->url(fn (): string => route('filament.admin.resources.transactions.index'))
                        ->openUrlInNewTab(),

                    Action::make('export_report')
                        ->label('📥 هەناردەی ڕاپۆرت')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color(Color::Gray)
                        ->action(function () {
                            Notification::make()
                                ->title('ئەم تایبەتمەندییە لە بەردەستدایە')
                                ->warning()
                                ->send();
                        }),
                ])
                ->label('🔧 بەڕێوەبردنی دارایی')
                ->icon('heroicon-o-currency-dollar')
                ->color(Color::Purple)
                ->button(),

                Action::make('quick_add')
                    ->label('➕ زیادکردنی پارە')
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Orange)
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی پارە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->minValue(1000)
                            ->mask(RawJs::make('$money($input)')),

                        Forms\Components\Select::make('source')
                            ->label('سەرچاوە')
                            ->options([
                                'bank' => '🏦 وەرگرتن لە بانک',
                                'profit' => '💹 قازانج',
                                'other' => '🔄 سەرچاوەی تر',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label('تێبینی')
                            ->placeholder('نموونە: وەرگرتنی پارە لە بانک')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (array $data) {
                        $cash = Cash::first() ?? Cash::initialize(0, 0);

                        $sourceText = match($data['source']) {
                            'bank' => 'وەرگرتن لە بانک',
                            'profit' => 'قازانج',
                            default => 'سەرچاوەی تر',
                        };

                        $description = $sourceText . ' - ' . $data['description'];
                        $cash->addMoney($data['amount'], $description, now());

                        Notification::make()
                            ->title('✅ پارە زیاد کرا')
                            ->body(self::formatSuccessMessage('money_added', $data['amount']))
                            ->success()
                            ->send();
                    })
                    ->modalHeading('زیادکردنی پارە بۆ قاسە')
                    ->modalIcon('heroicon-o-plus-circle'),

                Action::make('quick_withdraw')
                    ->label('➖ کەمکردنەوەی پارە')
                    ->icon('heroicon-o-minus-circle')
                    ->color(Color::Red)
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی پارە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->minValue(1000)
                            ->mask(RawJs::make('$money($input)'))
                            ->rule(fn () => 'max:' . (optional(Cash::first())->balance ?? 0)),

                        Forms\Components\Select::make('reason')
                            ->label('هۆکار')
                            ->options([
                                'bank' => '🏦 گواستنەوە بۆ بانک',
                                'withdraw' => '💸 وەرگرتن',
                                'other' => '🔄 هۆکاری تر',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label('وەسف')
                            ->required()
                            ->placeholder('نموونە: گواستنەوە بۆ بانک')
                            ->maxLength(255),
                    ])
                    ->action(function (array $data) {
                        $cash = Cash::first();

                        if (!$cash) {
                            Notification::make()
                                ->title('❌ هەڵە!')
                                ->body('قیاسەی دارایی بوونی نییە')
                                ->danger()
                                ->send();
                            return;
                        }

                        $reasonText = match($data['reason']) {
                            'bank' => 'گواستنەوە بۆ بانک',
                            'withdraw' => 'وەرگرتن',
                            default => 'هۆکاری تر',
                        };

                        $description = $reasonText . ' - ' . $data['description'];
                        $cash->withdrawMoney($data['amount'], $description, now());

                        Notification::make()
                            ->title('✅ پارە کەم کرایەوە')
                            ->body(self::formatSuccessMessage('money_withdrawn', $data['amount']))
                            ->success()
                            ->send();
                    })
                    ->modalHeading('کەمکردنەوەی پارە لە قاسە')
                    ->modalIcon('heroicon-o-minus-circle')
                    ->visible(fn () => optional(Cash::first())->balance > 0),
            ])

            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('👁️ بینین')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Cash $record): string => static::getUrl('view', ['record' => $record])),

                    Action::make('quick_add_small')
                        ->label('➕ زیادکردن')
                        ->icon('heroicon-m-plus')
                        ->color(Color::Orange)
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label('بڕی پارە')
                                ->numeric()
                                ->required()
                                ->prefix('دینار')
                                ->mask(RawJs::make('$money($input)')),

                            Forms\Components\Textarea::make('description')
                                ->label('تێبینی')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(function (array $data, Cash $record) {
                            $record->addMoney($data['amount'], $data['description'], now());
                            Notification::make()->title('زیاد کرا')->success()->send();
                        }),

                    Action::make('quick_withdraw_small')
                        ->label('➖ کەمکردنەوە')
                        ->icon('heroicon-m-minus')
                        ->color(Color::Red)
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label('بڕی پارە')
                                ->numeric()
                                ->required()
                                ->prefix('دینار')
                                ->mask(RawJs::make('$money($input)')),

                            Forms\Components\Textarea::make('reason')
                                ->label('هۆکار')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(function (array $data, Cash $record) {
                            try {
                                $record->withdrawMoney($data['amount'], $data['reason'], now());
                                Notification::make()->title('کەم کرایەوە')->success()->send();
                            } catch (\Exception $e) {
                                Notification::make()->title('هەڵە')->body($e->getMessage())->danger()->send();
                            }
                        })
                        ->visible(fn (Cash $record): bool => $record->balance > 0),
                ])
                ->label('کردارەکان')
                ->icon('heroicon-o-ellipsis-vertical')
                ->color(Color::Gray)
                ->size('sm'),
            ])

            ->emptyStateIcon('heroicon-o-banknotes')
            ->emptyStateHeading('هیچ قیاسەیەکی دارایی نییە')
            ->emptyStateDescription('یەکەم قیاسەی دارایی دروست بکە بۆ دەستپێکردنی تۆمارکردن')
            ->emptyStateActions([
                Action::make('create')
                    ->label('دروستکردن')
                    ->icon('heroicon-m-plus')
                    ->url(fn (): string => static::getUrl('create')),
            ])

            ->poll('30s')
            ->paginated(false);
    }

    /**
     * فۆرمەتی کارتی دارایی
     */
    private static function formatCardBalance($amount): HtmlString
    {
        $balance = number_format($amount);
        $words = self::convertToWords($amount);

        return new HtmlString("
            <div class='space-y-2'>
                <div class='text-sm opacity-80'>ڕەوشتی قاسە</div>
                <div class='text-4xl font-bold tracking-tight'>{$balance} <span class='text-lg'>دینار</span></div>
                <div class='text-sm opacity-75 border-t border-white/20 pt-2 mt-2'>{$words}</div>
            </div>
        ");
    }

    /**
     * فۆرمەتی پێشبینینی کارتی دارایی
     */
    private static function formatCardPreview($record): HtmlString
    {
        $color = $record->profit >= 0 ? 'from-green-500 to-green-700' : 'from-red-500 to-red-700';
        $icon = $record->profit >= 0 ? '📈' : '📉';

        return new HtmlString("
            <div class='bg-gradient-to-r {$color} text-white p-4 rounded-lg shadow-lg w-full'>
                <div class='flex justify-between items-center'>
                    <div class='text-sm opacity-90'>قیاسەی دارایی</div>
                    <div class='text-2xl'>{$icon}</div>
                </div>
                <div class='text-2xl font-bold mt-2'>" . number_format($record->balance) . " د.ع</div>
                <div class='flex justify-between text-xs opacity-80 mt-2'>
                    <span>🏦 " . number_format($record->capital) . "</span>
                    <span>📊 " . number_format($record->profit) . "</span>
                </div>
            </div>
        ");
    }

    /**
     * فۆرمەتی پارە بە توولتیپ
     */
    private static function formatMoneyWithTooltip($amount, $tooltip): HtmlString
    {
        return new HtmlString("
            <span title='{$tooltip}' class='cursor-help border-b border-dotted'>
                " . number_format($amount) . " د.ع
            </span>
        ");
    }

    /**
     * فۆرمەتی ڕێژە
     */
    private static function formatPercentage($value): HtmlString
    {
        $color = $value >= 20 ? 'text-green-600' : ($value >= 10 ? 'text-yellow-600' : 'text-red-600');
        $icon = $value >= 20 ? '🟢' : ($value >= 10 ? '🟡' : '🔴');

        return new HtmlString("
            <span class='{$color} font-bold'>
                {$icon} " . number_format($value, 2) . "%
            </span>
        ");
    }

    /**
     * وەسفی ڕێژەی قازانج
     */
    private static function getProfitDescription($margin): string
    {
        if ($margin >= 30) return 'ممتاز - قازانجی زۆر باش';
        if ($margin >= 20) return 'باش - قازانجی پەسەندکراو';
        if ($margin >= 10) return 'مامناوەند - پێویستی بە باشترکردنە';
        if ($margin > 0) return 'کەم - پێویستە ڕێژە زیاد بکەیت';
        return 'زیان - پێویستە خەرجی کەم بکەیتەوە';
    }

    /**
     * دروستکردنی ڕێنمایی
     */
    private static function generateAdvice($record): string
    {
        $advice = [];

        // پشکنینی قازانج
        if ($record->profit < 0) {
            $advice[] = "🔴 **زیان**: کۆمپانیا لە زیاندا کاردەکات. پێویستە:";
            $advice[] = "- خەرجییەکان کەم بکەیتەوە";
            $advice[] = "- فرۆشتن زیاد بکەیت";
            $advice[] = "- نرخی فرۆشتن پێداچوونەوەی بکەیت";
        } elseif ($record->profit < $record->total_income * 0.1) {
            $advice[] = "🟡 **ڕێژەی قازانج کەمە**:";
            $advice[] = "- هەوڵبدە نرخی فرۆشتن زیاد بکەیت";
            $advice[] = "- خەرجییە ناپێویستەکان کەم بکەیتەوە";
            $advice[] = "- کڕینی بەنزین بە نرخی کەمتر";
        } else {
            $advice[] = "🟢 **دۆخی دارایی باشە**:";
            $advice[] = "- دەتوانیت سەرمایە زیاد بکەیت بۆ فراوانکردن";
            $advice[] = "- بەردەوام بە لەم ڕێگایە";
        }

        // پشکنینی خەرجی
        if ($record->total_expense > $record->total_income * 0.7) {
            $advice[] = "📊 **خەرجی زۆرە**: " . number_format(($record->total_expense / $record->total_income) * 100, 2) . "% ی داهات دەچێتە خەرجی";
        }

        // پشکنینی سەرمایە
        if ($record->capital == 0) {
            $advice[] = "💰 **سەرمایە دیاری نەکراوە**: باشترە سەرمایەی سەرەتایی دیاری بکەیت";
        } elseif ($record->balance < $record->capital) {
            $advice[] = "⚠️ **ئاگادار**: ڕەوشتی قاسە لە سەرمایە کەمترە";
        }

        return implode("\n", $advice);
    }

    /**
     * فۆرمەتی پەیامی سەرکەوتن
     */
    private static function formatSuccessMessage($type, $amount): string
    {
        return match($type) {
            'capital_added' => "💰 " . number_format($amount) . " دینار وەک سەرمایەی زیادە زیاد کرا",
            'capital_withdrawn' => "💸 " . number_format($amount) . " دینار لە سەرمایە کەم کرایەوە",
            'money_added' => "💵 " . number_format($amount) . " دینار زیاد کرا بۆ قاسە",
            'money_withdrawn' => "💸 " . number_format($amount) . " دینار لە قاسە کەم کرایەوە",
            default => "کردارەکە سەرکەوتوو بوو"
        };
    }

    /**
     * گۆڕینی ژمارە بە وشە
     */
    private static function convertToWords($amount): string
    {
        if ($amount >= 1000000) {
            $millions = floor($amount / 1000000);
            $remainder = $amount % 1000000;
            if ($remainder >= 1000) {
                $thousands = floor($remainder / 1000);
                return "{$millions} ملیۆن و {$thousands} هەزار دینار";
            }
            return "{$millions} ملیۆن دینار";
        } elseif ($amount >= 1000) {
            $thousands = floor($amount / 1000);
            $remainder = $amount % 1000;
            if ($remainder > 0) {
                return "{$thousands} هەزار و {$remainder} دینار";
            }
            return "{$thousands} هەزار دینار";
        }
        return $amount . " دینار";
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashes::route('/'),
            'create' => Pages\CreateCash::route('/create'),
            'view' => Pages\ViewCash::route('/{record}'),
            'edit' => Pages\EditCash::route('/{record}/edit'),
        ];
    }

    /**
     * بەج لە ناڤیگەیشن
     */
    public static function getNavigationBadge(): ?string
    {
        $cash = Cash::first();
        return $cash ? self::formatShortNumber($cash->balance) : '0';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $cash = Cash::first();
        if (!$cash) return 'gray';

        if ($cash->profit > 0) return 'success';
        if ($cash->profit < 0) return 'danger';
        return 'warning';
    }

    /**
     * فۆرمەتی کورتی ژمارە
     */
    private static function formatShortNumber($number): string
    {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        }
        if ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }
        return (string) $number;
    }
}
