<?php
// app/Filament/Resources/CreditPaymentResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\CreditPaymentResource\Pages;
use App\Models\CreditPayment;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;

class CreditPaymentResource extends Resource
{
    protected static ?string $model = CreditPayment::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'بەشی کڕیاران';
    protected static ?string $modelLabel = 'دانەوەی قەرز';
    protected static ?string $pluralModelLabel = 'دانەوەی قەرز';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری دانەوە')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('کڕیار')
                            ->relationship('customer', 'name')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $customer = Customer::find($state);
                                if ($customer) {
                                    $set('max_amount', $customer->current_debt);
                                }
                            })
                            ->searchable(),
                        Forms\Components\Select::make('sale_id')
                            ->label('فرۆشتن')
                            ->options(function (callable $get) {
                                $customerId = $get('customer_id');
                                if (!$customerId) return [];

                                return \App\Models\Sale::where('customer_id', $customerId)
                                    ->whereIn('status', ['pending', 'partial'])
                                    ->get()
                                    ->mapWithKeys(fn ($sale) => [
                                        $sale->id => "#{$sale->id} - {$sale->liters} لیتر - " . number_format($sale->remaining_amount) . ' دینار'
                                    ]);
                            })
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی پارەدان')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->minValue(1000)
                            ->rule(function (callable $get) {
                                $saleId = $get('sale_id');
                                if (!$saleId) return '';

                                $sale = \App\Models\Sale::find($saleId);
                                return 'max:' . ($sale->remaining_amount ?? 0);
                            }),
                        Forms\Components\Select::make('payment_method')
                            ->label('شێوازی پارەدان')
                            ->options([
                                'cash' => 'پارەی ڕاستەوخۆ',
                                'bank' => 'بانک',
                                'cheque' => 'چێک',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('reference_number')
                            ->label('ژمارەی سەرچاوە')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('ڕێکەوتی پارەدان')
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
                Tables\Columns\TextColumn::make('transaction_number')
                    ->label('ژ. مامەڵە')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('کڕیار')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_id')
                    ->label('ژ. فرۆشتن')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('بڕی پارە')
                    ->money('IQD')
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('شێواز')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'cash' => 'success',
                        'bank' => 'info',
                        'cheque' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('ڕێکەوت')
                    ->date('Y/m/d')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable(),
                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('payment_date', 'desc');
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
}
