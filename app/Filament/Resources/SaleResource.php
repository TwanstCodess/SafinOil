<?php
// app/Filament/Resources/SaleResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\Category;
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
use Filament\Forms\Components\DatePicker;
use Filament\Support\RawJs;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'کڕین و فرۆشتن';
    protected static ?string $modelLabel = 'فرۆشتن';
    protected static ?string $pluralModelLabel = 'فرۆشتنەکان';
    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری فرۆشتن')
                    ->description('زانیاری سەرەکی فرۆشتن')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('payment_type')
                                    ->label('جۆری فرۆشتن')
                                    ->options([
                                        'cash' => 'پارەی ڕاستەوخۆ',
                                        'credit' => 'قەرز',
                                    ])
                                    ->required()
                                    ->default('cash')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state === 'cash') {
                                            $set('customer_id', null);
                                            $set('due_date', null);
                                        }
                                    })
                                    ->extraAttributes(['class' => 'font-bold']),

                                Forms\Components\Select::make('customer_id')
                                    ->label('کڕیار')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(fn (callable $get) => $get('payment_type') === 'credit')
                                    ->visible(fn (callable $get) => $get('payment_type') === 'credit')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('ناوی کڕیار')
                                            ->required(),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('ژمارە مۆبایل')
                                            ->tel(),
                                        Forms\Components\TextInput::make('identity_number')
                                            ->label('ژمارەی ناسنامە'),
                                        Forms\Components\TextInput::make('vehicle_number')
                                            ->label('ژمارەی ئۆتۆمۆبیل'),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        return Customer::create($data);
                                    }),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('category_id')
                                    ->label('کاتیگۆری')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $category = Category::find($state);
                                        if ($category) {
                                            $set('price_per_liter', $category->current_price);
                                            $liters = $get('liters') ?? 0;
                                            $set('total_price', $liters * $category->current_price);

                                            // پێشنیاری کۆگا
                                            $stock = $category->stock_liters ?? 0;
                                            if ($liters > $stock) {
                                                Notification::make()
                                                    ->warning()
                                                    ->title('ئاگادار!')
                                                    ->body("تەنها {$stock} لیتر لەم کاتیگۆرییە ماوە")
                                                    ->send();
                                            }
                                        }
                                    }),

                                Forms\Components\TextInput::make('liters')
                                    ->label('بڕ (لیتر)')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $pricePerLiter = $get('price_per_liter') ?? 0;
                                        $set('total_price', $state * $pricePerLiter);

                                        // پێشنیاری کۆگا
                                        $categoryId = $get('category_id');
                                        if ($categoryId) {
                                            $category = Category::find($categoryId);
                                            $stock = $category->stock_liters ?? 0;
                                            if ($state > $stock) {
                                                Notification::make()
                                                    ->warning()
                                                    ->title('ئاگادار!')
                                                    ->body("تەنها {$stock} لیتر لەم کاتیگۆرییە ماوە")
                                                    ->send();
                                            }
                                        }
                                    })
                                    ->suffix('لیتر'),

                                Forms\Components\TextInput::make('price_per_liter')
                                    ->label('نرخی لیترێک')
                                    ->numeric()
                                    ->required()
                                    ->prefix('دینار')
                                    ->reactive()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $liters = $get('liters') ?? 0;
                                        $set('total_price', $liters * $state);
                                    }),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_price')
                                    ->label('کۆی گشتی')
                                    ->numeric()
                                    ->required()
                                    ->prefix('دینار')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->extraAttributes(['class' => 'text-primary-600 font-bold text-lg']),

                                Forms\Components\DatePicker::make('sale_date')
                                    ->label('ڕێکەوتی فرۆشتن')
                                    ->required()
                                    ->default(now())
                                    ->displayFormat('Y/m/d'),
                            ]),

                        Forms\Components\Section::make('زانیاری قەرز')
                            ->description('ئەگەر فرۆشتن بە قەرزە')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                    Forms\Components\DatePicker::make('due_date')
    ->label('بەرواری وەستان')
    ->required(fn (callable $get) => $get('payment_type') === 'credit')
    ->visible(fn (callable $get) => $get('payment_type') === 'credit')
    // ->minDate(now())  // ئەم هێڵە کۆمێنت بکە یان بیسڕەوە
    ->displayFormat('Y/m/d'),


                                        Forms\Components\Placeholder::make('debt_info')
                                            ->label('')
                                            ->content(function (callable $get) {
                                                $customerId = $get('customer_id');
                                                if (!$customerId) return '';

                                                $customer = Customer::find($customerId);
                                                if ($customer && $customer->current_debt > 0) {
                                                    return "⚠️ قەرزی پێشووی ئەم کڕیارە: " . number_format($customer->current_debt) . " دینار";
                                                }
                                                return '';
                                            })
                                            ->visible(fn (callable $get) => $get('payment_type') === 'credit'),
                                    ]),
                            ])
                            ->visible(fn (callable $get) => $get('payment_type') === 'credit')
                            ->collapsible(),
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

                Tables\Columns\TextColumn::make('sale_date')
                    ->label('ڕێکەوت')
                    ->date('Y/m/d')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('payment_type')
                    ->label('جۆر')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'credit' => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'ڕاستەوخۆ',
                        'credit' => 'قەرز',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'cash' => 'heroicon-m-banknotes',
                        'credit' => 'heroicon-m-credit-card',
                    }),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('کڕیار')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->visible(fn ($livewire): bool => $livewire->tableFilterState['payment_type'] ?? null === 'credit'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('کاتیگۆری')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('liters')
                    ->label('بڕ')
                    ->suffix(' لیتر')
                    ->sortable()
                    ->toggleable()
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('price_per_liter')
                    ->label('نرخی لیتر')
                    ->money('IQD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('کۆی گشتی')
                    ->money('IQD')
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('status')
                    ->label('ڕەوشت')
                    ->badge()
                    ->color(fn ($record): string => $record?->status_color ?? 'gray')
                    ->formatStateUsing(fn ($record): string => $record?->status_label ?? '-')
                    ->icon(fn ($record): string => match ($record?->status) {
                        'paid' => 'heroicon-m-check-circle',
                        'partial' => 'heroicon-m-clock',
                        'pending' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->visible(fn ($record): bool => $record && $record->payment_type === 'credit'),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('بڕی ماوە')
                    ->money('IQD')
                    ->color('danger')
                    ->visible(fn ($record): bool => $record && $record->payment_type === 'credit' && $record->remaining_amount > 0),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('وەستان')
                    ->date('Y/m/d')
                    ->color(fn ($record): string => ($record && $record->due_date && $record->due_date->isPast()) ? 'danger' : 'gray')
                    ->visible(fn ($record): bool => $record && $record->payment_type === 'credit'),

                Tables\Columns\TextColumn::make('paid_date')
                    ->label('ڕێکەوتی پارەدان')
                    ->date('Y/m/d')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('payment_type')
                    ->label('جۆری فرۆشتن')
                    ->options([
                        'cash' => 'ڕاستەوخۆ',
                        'credit' => 'قەرز',
                    ]),

                SelectFilter::make('status')
                    ->label('ڕەوشت')
                    ->options([
                        'paid' => 'پارەدراوە',
                        'partial' => 'بەشێکی پارەدراوە',
                        'pending' => 'چاوەڕوانی پارەدان',
                    ])
                    ->visible(fn ($livewire): bool => $livewire->tableFilterState['payment_type'] ?? null === 'credit'),

                SelectFilter::make('customer_id')
                    ->label('کڕیار')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->visible(fn ($livewire): bool => $livewire->tableFilterState['payment_type'] ?? null === 'credit'),

                Filter::make('sale_date')
                    ->form([
                        DatePicker::make('from')
                            ->label('لە ڕێکەوتی'),
                        DatePicker::make('until')
                            ->label('تا ڕێکەوتی'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('sale_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('sale_date', '<=', $data['until']));
                    }),

                Filter::make('due_date_overdue')
                    ->label('قەرزە بەسەرچووەکان')
                    ->query(fn ($query) => $query->where('due_date', '<', now())->where('status', '!=', 'paid'))
                    ->toggle(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::Modal)
            ->persistFiltersInSession()

            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('بینین'),

                Tables\Actions\EditAction::make()
                    ->label('دەستکاری')
                    ->visible(fn ($record): bool => $record && ($record->payment_type === 'cash' || $record->status === 'pending')),

                Action::make('receive_payment')
                    ->label('وەرگرتنی پارە')
                    ->icon('heroicon-m-currency-dollar')
                    ->color(Color::Green)
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی پارە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->minValue(1000)
                            ->maxValue(fn ($record) => $record?->remaining_amount ?? 0)
                            ->mask(RawJs::make('$money($input)')),
                        Forms\Components\Select::make('payment_method')
                            ->label('شێوازی پارەدان')
                            ->options([
                                'cash' => 'پارەی ڕاستەوخۆ',
                                'bank' => 'بانک',
                                'cheque' => 'چێک',
                            ])
                            ->required(),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('ڕێکەوتی پارەدان')
                            ->default(now())
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('تێبینی')
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, $record): void {
                        try {
                            // دروستکردنی CreditPayment
                            $payment = $record->creditPayments()->create([
                                'customer_id' => $record->customer_id,
                                'amount' => $data['amount'],
                                'payment_date' => $data['payment_date'],
                                'payment_method' => $data['payment_method'],
                                'notes' => $data['notes'] ?? null,
                            ]);

                            // نوێکردنەوەی ڕەوشتی فرۆشتن
                            $record->paid_amount += $data['amount'];
                            $record->remaining_amount -= $data['amount'];

                            if ($record->remaining_amount <= 0) {
                                $record->status = 'paid';
                                $record->paid_date = $data['payment_date'];
                            } else {
                                $record->status = 'partial';
                            }
                            $record->save();

                            // نوێکردنەوەی قەرزی کڕیار
                            $record->customer->updateDebt();

                            Notification::make()
                                ->title('پارە بە سەرکەوتوویی وەرگیرا')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('هەڵە!')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn ($record): bool =>
                        $record &&
                        $record->payment_type === 'credit' &&
                        $record->remaining_amount > 0
                    )
                    ->modalHeading('وەرگرتنی پارەی قەرز')
                    ->modalIcon('heroicon-o-currency-dollar'),

                Action::make('view_payments')
                    ->label('مێژووی پارەدان')
                    ->icon('heroicon-m-clock')
                    ->color(Color::Blue)
                    ->url(fn ($record): string => $record ? route('filament.admin.resources.credit-payments.index', ['sale_id' => $record->id]) : '#')
                    ->visible(fn ($record): bool => $record && $record->payment_type === 'credit'),

                Tables\Actions\DeleteAction::make()
                    ->label('سڕینەوە')
                    ->modalHeading('سڕینەوەی فرۆشتن')
                    ->modalDescription('دڵنیای لە سڕینەوەی ئەم فرۆشتنە؟')
                    ->visible(fn ($record): bool => $record && ($record->payment_type === 'cash' || $record->status === 'pending')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('سڕینەوەی دیاریکراوەکان'),
                ]),
            ])
            ->defaultSort('sale_date', 'desc')
            ->poll('10s');
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
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $todaySales = static::getModel()::whereDate('sale_date', today())->count();
        return $todaySales > 0 ? (string) $todaySales : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
