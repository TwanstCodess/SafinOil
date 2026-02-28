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
                    ->color('success'),
                Tables\Columns\TextColumn::make('total_income')
                    ->label('کۆی داهات')
                    ->money('IQD')
                    ->color('success'),
                Tables\Columns\TextColumn::make('total_expense')
                    ->label('کۆی خەرجی')
                    ->money('IQD')
                    ->color('danger'),
                Tables\Columns\TextColumn::make('last_update')
                    ->label('دوایین نوێکردنەوە')
                    ->date('Y/m/d')
                    ->since(),
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
                            ->autofocus(),
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
                            ->body(number_format($data['amount']) . ' دینار زیاد کرا بۆ قاسە')
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
                            ->placeholder('بڕی پارە داخڵ بکە'),
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
                            ->body(number_format($data['amount']) . ' دینار کەم کرایەوە لە قاسە')
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
                    ->icon('heroicon-m-plus'),
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
