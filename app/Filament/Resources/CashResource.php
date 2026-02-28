<?php
// app/Filament/Resources/CashResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\CashResource\Pages;
use App\Models\Cash;
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

class CashResource extends Resource
{
    protected static ?string $model = Cash::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'بەشی دارایی';
    protected static ?string $modelLabel = 'قاسە';
    protected static ?string $pluralModelLabel = 'قاسە';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری قاسە')
                    ->schema([
                        Forms\Components\TextInput::make('balance')
                            ->label('ڕەوشتی قاسە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->disabled()
                            ->default(0),
                        Forms\Components\TextInput::make('total_income')
                            ->label('کۆی داهات')
                            ->numeric()
                            ->disabled()
                            ->prefix('دینار'),
                        Forms\Components\TextInput::make('total_expense')
                            ->label('کۆی خەرجی')
                            ->numeric()
                            ->disabled()
                            ->prefix('دینار'),
                        Forms\Components\DatePicker::make('last_update')
                            ->label('دوایین نوێکردنەوە')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('balance')
                    ->label('ڕەوشتی قاسە')
                    ->money('IQD')
                    ->sortable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                    ->weight('bold')
                    ->color('success')
                    ->formatStateUsing(function ($state) {
                        // نیشاندانی ژمارە بە هەزاران و ملیۆنان
                        if ($state >= 1000000) {
                            return number_format($state / 1000000, 2) . ' ملیۆن دینار';
                        } elseif ($state >= 1000) {
                            return number_format($state / 1000, 2) . ' هەزار دینار';
                        }
                        return number_format($state) . ' دینار';
                    }),

                Tables\Columns\TextColumn::make('total_income')
                    ->label('کۆی داهات')
                    ->money('IQD')
                    ->color('success')
                    ->formatStateUsing(function ($state) {
                        if ($state >= 1000000) {
                            return number_format($state / 1000000, 2) . ' ملیۆن دینار';
                        } elseif ($state >= 1000) {
                            return number_format($state / 1000, 2) . ' هەزار دینار';
                        }
                        return number_format($state) . ' دینار';
                    }),

                Tables\Columns\TextColumn::make('total_expense')
                    ->label('کۆی خەرجی')
                    ->money('IQD')
                    ->color('danger')
                    ->formatStateUsing(function ($state) {
                        if ($state >= 1000000) {
                            return number_format($state / 1000000, 2) . ' ملیۆن دینار';
                        } elseif ($state >= 1000) {
                            return number_format($state / 1000, 2) . ' هەزار دینار';
                        }
                        return number_format($state) . ' دینار';
                    }),

                Tables\Columns\TextColumn::make('last_update')
                    ->label('دوایین نوێکردنەوە')
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return 'هەرگیز نوێ نەکراوەتەوە';
                        }

                        $now = now();
                        $diffInDays = $now->diffInDays($state);
                        $diffInHours = $now->diffInHours($state);

                        if ($diffInDays > 365) {
                            $years = floor($diffInDays / 365);
                            return $years . ' ساڵ پێش ئێستا';
                        } elseif ($diffInDays > 30) {
                            $months = floor($diffInDays / 30);
                            return $months . ' مانگ پێش ئێستا';
                        } elseif ($diffInDays > 7) {
                            $weeks = floor($diffInDays / 7);
                            return $weeks . ' هەفتە پێش ئێستا';
                        } elseif ($diffInDays >= 1) {
                            return $diffInDays . ' ڕۆژ پێش ئێستا (' . $state->format('Y/m/d') . ')';
                        } elseif ($diffInHours >= 1) {
                            return $diffInHours . ' کاتژمێر پێش ئێستا';
                        } else {
                            return 'ئێستا';
                        }
                    }),
            ])

            // **فلتەرەکان لێرە زیاد کراون**
            ->filters([
                // فلتەری بەپێی بڕی پارە
                Filter::make('balance_range')
                    ->form([
                        Forms\Components\TextInput::make('min_balance')
                            ->label('کەمترین بڕ')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('بۆ نموونە: 100000'),
                        Forms\Components\TextInput::make('max_balance')
                            ->label('زۆرترین بڕ')
                            ->numeric()
                            ->prefix('دینار')
                            ->placeholder('بۆ نموونە: 1000000'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['min_balance'], fn ($q) => $q->where('balance', '>=', $data['min_balance']))
                            ->when($data['max_balance'], fn ($q) => $q->where('balance', '<=', $data['max_balance']));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $indicators = [];
                        if ($data['min_balance'] ?? null) {
                            $indicators[] = 'کەمترین: ' . number_format($data['min_balance']) . ' دینار';
                        }
                        if ($data['max_balance'] ?? null) {
                            $indicators[] = 'زۆرترین: ' . number_format($data['max_balance']) . ' دینار';
                        }
                        return $indicators ? 'بڕی پارە: ' . implode(', ', $indicators) : null;
                    }),

                // فلتەری بەپێی داهات
                Filter::make('income_range')
                    ->form([
                        Forms\Components\TextInput::make('min_income')
                            ->label('کەمترین داهات')
                            ->numeric()
                            ->prefix('دینار'),
                        Forms\Components\TextInput::make('max_income')
                            ->label('زۆرترین داهات')
                            ->numeric()
                            ->prefix('دینار'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['min_income'], fn ($q) => $q->where('total_income', '>=', $data['min_income']))
                            ->when($data['max_income'], fn ($q) => $q->where('total_income', '<=', $data['max_income']));
                    })
                    ->columnSpan(2),

                // فلتەری بەپێی خەرجی
                Filter::make('expense_range')
                    ->form([
                        Forms\Components\TextInput::make('min_expense')
                            ->label('کەمترین خەرجی')
                            ->numeric()
                            ->prefix('دینار'),
                        Forms\Components\TextInput::make('max_expense')
                            ->label('زۆرترین خەرجی')
                            ->numeric()
                            ->prefix('دینار'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['min_expense'], fn ($q) => $q->where('total_expense', '>=', $data['min_expense']))
                            ->when($data['max_expense'], fn ($q) => $q->where('total_expense', '<=', $data['max_expense']));
                    }),

                // فلتەری بەپێی ڕێکەوتی دوایین نوێکردنەوە
                Filter::make('last_update_filter')
                    ->form([
                        DatePicker::make('from_date')
                            ->label('لە ڕێکەوتی')
                            ->placeholder('YYYY-MM-DD'),
                        DatePicker::make('to_date')
                            ->label('تا ڕێکەوتی')
                            ->placeholder('YYYY-MM-DD'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from_date'], fn ($q) => $q->whereDate('last_update', '>=', $data['from_date']))
                            ->when($data['to_date'], fn ($q) => $q->whereDate('last_update', '<=', $data['to_date']));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['from_date'] && !$data['to_date']) {
                            return null;
                        }

                        $indicator = 'ڕێکەوتی نوێکردنەوە: ';
                        if ($data['from_date']) {
                            $indicator .= 'لە ' . $data['from_date'];
                        }
                        if ($data['to_date']) {
                            $indicator .= ($data['from_date'] ? ' تا ' : 'تا ') . $data['to_date'];
                        }
                        return $indicator;
                    }),

                // فلتەری بەپێی ئاستی قاسە
                SelectFilter::make('cash_level')
                    ->label('ئاستی قاسە')
                    ->options([
                        'low' => 'کەم (کەمتر لە ١٠٠ هەزار)',
                        'medium' => 'مامناوەند (١٠٠ هەزار - ١ ملیۆن)',
                        'high' => 'زۆر (زیاتر لە ١ ملیۆن)',
                        'very_high' => 'زۆر زۆر (زیاتر لە ١٠ ملیۆن)',
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match($data['value']) {
                            'low' => $query->where('balance', '<', 100000),
                            'medium' => $query->whereBetween('balance', [100000, 1000000]),
                            'high' => $query->whereBetween('balance', [1000000, 10000000]),
                            'very_high' => $query->where('balance', '>', 10000000),
                            default => $query,
                        };
                    }),

                // فلتەری پێشنیارکراو (ئەگەر داهات زیاترە یان خەرجی)
                SelectFilter::make('status')
                    ->label('ڕەوشتی قاسە')
                    ->options([
                        'profit' => 'قازانج (داهات > خەرجی)',
                        'loss' => 'زیان (داهات < خەرجی)',
                        'equal' => 'یەکسان (داهات = خەرجی)',
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match($data['value']) {
                            'profit' => $query->whereColumn('total_income', '>', 'total_expense'),
                            'loss' => $query->whereColumn('total_income', '<', 'total_expense'),
                            'equal' => $query->whereColumn('total_income', '=', 'total_expense'),
                            default => $query,
                        };
                    }),
            ])

            ->headerActions([
                // ئەکشنی زیادکردنی پارە بۆ قاسە
                Action::make('add_money')
                    ->label('زیادکردنی پارە بۆ قاسە')
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Orange)
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی پارە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->minValue(1000)
                            ->placeholder('بڕی پارە داخڵ بکە')
                            ->autofocus()
                            ->formatStateUsing(fn ($state) => number_format($state)),
                        Forms\Components\Textarea::make('description')
                            ->label('شوێنەوار (تێبینی)')
                            ->placeholder('نموونە: زیادکردنی پارە لە بانک، قازانج، ...')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('date')
                            ->label('ڕێکەوت')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire): void {
                        // وەرگرتنی یەکەم تۆماری قاسە (یان دروستکردنی ئەگەر بوونی نەبێت)
                        $cash = Cash::first();

                        if (!$cash) {
                            $cash = Cash::create([
                                'balance' => 0,
                                'total_income' => 0,
                                'total_expense' => 0,
                                'last_update' => now(),
                            ]);
                        }

                        // زیادکردنی پارە بۆ قاسە
                        $cash->balance += $data['amount'];
                        $cash->total_income += $data['amount'];
                        $cash->last_update = now();
                        $cash->save();

                        // نیشاندانی پەیامی سەرکەوتن
                        Notification::make()
                            ->title('پارە بە سەرکەوتوویی زیاد کرا')
                            ->body($data['amount'] >= 1000000
                                ? number_format($data['amount'] / 1000000, 2) . ' ملیۆن دینار زیاد کرا بۆ قاسە'
                                : (number_format($data['amount'] / 1000, 2) . ' هەزار دینار زیاد کرا بۆ قاسە'))
                            ->success()
                            ->send();
                    })
                    ->modalHeading('زیادکردنی پارە بۆ قاسە')
                    ->modalDescription('بڕی پارە دیاری بکە کە دەتەوێت زیاد بکەیت بۆ قاسە')
                    ->modalSubmitActionLabel('زیادکردن')
                    ->modalCancelActionLabel('پاشگەزبوونەوە'),

                // ئەکشنی کەمکردنەوەی پارە لە قاسە
                Action::make('withdraw_money')
                    ->label('کەمکردنەوەی پارە لە قاسە')
                    ->icon('heroicon-o-minus-circle')
                    ->color(Color::Red)
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی پارە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->minValue(1000)
                            ->placeholder('بڕی پارە داخڵ بکە')
                            ->formatStateUsing(fn ($state) => number_format($state)),
                        Forms\Components\Textarea::make('description')
                            ->label('هۆکار')
                            ->required()
                            ->placeholder('نموونە: گواستنەوە بۆ بانک، ...')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('date')
                            ->label('ڕێکەوت')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire): void {
                        $cash = Cash::first();

                        if (!$cash) {
                            Notification::make()
                                ->title('هەڵە!')
                                ->body('قاسە بوونی نییە')
                                ->danger()
                                ->send();
                            return;
                        }

                        // دڵنیابوون لەوەی پارە بەشی کەمکردنەوە هەیە
                        if ($cash->balance < $data['amount']) {
                            Notification::make()
                                ->title('هەڵە!')
                                ->body('پارەی پێویست لە قاسەدا نییە')
                                ->danger()
                                ->send();
                            return;
                        }

                        // کەمکردنەوەی پارە لە قاسە
                        $cash->balance -= $data['amount'];
                        $cash->total_expense += $data['amount'];
                        $cash->last_update = now();
                        $cash->save();

                        Notification::make()
                            ->title('پارە بە سەرکەوتوویی کەم کرایەوە')
                            ->body($data['amount'] >= 1000000
                                ? number_format($data['amount'] / 1000000, 2) . ' ملیۆن دینار کەم کرایەوە لە قاسە'
                                : (number_format($data['amount'] / 1000, 2) . ' هەزار دینار کەم کرایەوە لە قاسە'))
                            ->warning()
                            ->send();
                    })
                    ->modalHeading('کەمکردنەوەی پارە لە قاسە')
                    ->modalSubmitActionLabel('کەمکردنەوە')
                    ->modalCancelActionLabel('پاشگەزبوونەوە'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // ئەکشنی خێرا بۆ زیادکردنی پارە لەسەر هەر ڕیزێک
                Action::make('quick_add')
                    ->label('')
                    ->icon('heroicon-m-plus')
                    ->tooltip('زیادکردنی پارە')
                    ->color(Color::Orange)
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی پارە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار'),
                        Forms\Components\Textarea::make('description')
                            ->label('تێبینی')
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, Cash $record): void {
                        $record->balance += $data['amount'];
                        $record->total_income += $data['amount'];
                        $record->last_update = now();
                        $record->save();

                        Notification::make()
                            ->title('زیاد کرا')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('زیادکردنی پارە'),

                // ئەکشنی خێرا بۆ کەمکردنەوەی پارە لەسەر هەر ڕیزێک
                Action::make('quick_withdraw')
                    ->label('')
                    ->icon('heroicon-m-minus')
                    ->tooltip('کەمکردنەوەی پارە')
                    ->color(Color::Red)
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی پارە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار'),
                        Forms\Components\Textarea::make('description')
                            ->label('هۆکار')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, Cash $record): void {
                        if ($record->balance < $data['amount']) {
                            Notification::make()
                                ->title('هەڵە!')
                                ->body('پارەی پێویست لە قاسەدا نییە')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->balance -= $data['amount'];
                        $record->total_expense += $data['amount'];
                        $record->last_update = now();
                        $record->save();

                        Notification::make()
                            ->title('کەم کرایەوە')
                            ->warning()
                            ->send();
                    })
                    ->modalHeading('کەمکردنەوەی پارە')
                    ->visible(fn (Cash $record): bool => $record->balance > 0),
            ])
            ->emptyStateActions([
                Action::make('create')
                    ->label('دروستکردنی قاسە')
                    ->icon('heroicon-m-plus')
                    ->url(fn (): string => static::getUrl('create')),
            ])
            ->paginated(false); // چونکە تەنها یەک ڕیز هەیە بۆ قاسە
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
            'index' => Pages\ListCashes::route('/'),
            'create' => Pages\CreateCash::route('/create'),
            'edit' => Pages\EditCash::route('/{record}/edit'),
        ];
    }
}
