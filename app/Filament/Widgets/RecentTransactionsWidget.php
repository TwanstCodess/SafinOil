<?php
// app/Filament/Widgets/RecentTransactionsWidget.php
namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTransactionsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 5;
    protected static ?string $heading = 'دوایین مامەڵەکان';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->latest('transaction_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('transaction_number')
                    ->label('ژ. مامەڵە')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('type')
                    ->label('جۆر')
                    ->badge()
                    ->color(fn ($record): string => $record->type_color)
                    ->formatStateUsing(fn ($record): string => $record->type_label),

                // ✅ فرۆشی خێرا → بڕ × 2
                Tables\Columns\TextColumn::make('amount')
                    ->label('بڕ')
                    ->formatStateUsing(function ($state, Transaction $record): string {
                        $amount = in_array($record->type, ['quick_sale', 'quick_sale_difference'])
                            ? floatval($state) * 2
                            : floatval($state);
                        return number_format(abs($amount)) . ' د.ع';
                    })
                    ->color(fn ($record): string => $record->is_income ? 'success' : 'danger')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('description')
                    ->label('وەسف')
                    ->limit(40)
                    ->tooltip(fn ($state): string => $state ?? ''),

                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('ڕێکەوت')
                    ->date('Y/m/d'),

                Tables\Columns\TextColumn::make('balance_after')
                    ->label('قاسە دوای')
                    ->formatStateUsing(fn ($state): string => number_format(abs(floatval($state))) . ' د.ع')
                    ->color('primary'),
            ])
            ->paginated(false);
    }
}
