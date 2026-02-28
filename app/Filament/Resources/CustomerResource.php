<?php
// app/Filament/Resources/CustomerResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'بەشی کڕیاران';
    protected static ?string $modelLabel = 'کڕیار';
    protected static ?string $pluralModelLabel = 'کڕیاران';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری کەسی')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('ناوی تەواو')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('ژمارە مۆبایل')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('identity_number')
                            ->label('ژمارەی ناسنامە')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('vehicle_number')
                            ->label('ژمارەی ئۆتۆمۆبیل')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('address')
                            ->label('ناونیشان')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('زانیاری قەرز')
                    ->schema([
                        Forms\Components\TextInput::make('total_credit')
                            ->label('کۆی قەرز')
                            ->numeric()
                            ->disabled()
                            ->prefix('دینار'),
                        Forms\Components\TextInput::make('total_paid')
                            ->label('کۆی پارەدان')
                            ->numeric()
                            ->disabled()
                            ->prefix('دینار'),
                        Forms\Components\TextInput::make('current_debt')
                            ->label('قەرزی ماوە')
                            ->numeric()
                            ->disabled()
                            ->prefix('دینار'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('چالاکە')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('تێبینی')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('تێبینی')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('ناو')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('مۆبایل')
                    ->searchable(),
                Tables\Columns\TextColumn::make('current_debt')
                    ->label('قەرزی ماوە')
                    ->money('IQD')
                    ->badge()
                    ->color(fn ($record): string => $record->debt_color),
                Tables\Columns\TextColumn::make('total_credit')
                    ->label('کۆی قەرز')
                    ->money('IQD')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_paid')
                    ->label('کۆی پارەدان')
                    ->money('IQD')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('چالاک')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('چالاک'),
                Tables\Filters\Filter::make('has_debt')
                    ->label('قەرزدار')
                    ->query(fn ($query) => $query->where('current_debt', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('view_credits')
                    ->label('قەرزەکان')
                    ->icon('heroicon-o-credit-card')
                    ->color(Color::Orange)
                    ->url(fn (Customer $record): string => route('filament.admin.resources.credit-payments.index', ['customer_id' => $record->id])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
