<?php
// app/Filament/Resources/SalaryResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\SalaryResource\Pages;
use App\Models\Salary;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SalaryResource extends Resource
{
    protected static ?string $model = Salary::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'بەڕێوەبردنی کارمەندان';
    protected static ?string $modelLabel = 'مووچە';
    protected static ?string $pluralModelLabel = 'مووچەکان';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری مووچە')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('کارمەند')
                            ->relationship('employee', 'name')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) =>
                                $set('amount', Employee::find($state)?->salary ?? 0)
                            ),
                        Forms\Components\TextInput::make('amount')
                            ->label('بڕی مووچە')
                            ->numeric()
                            ->required()
                            ->prefix('دینار'),
                        Forms\Components\TextInput::make('deductions')
                            ->label('بڕی سزا')
                            ->numeric()
                            ->default(0)
                            ->prefix('دینار')
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                                $set('net_amount', $get('amount') - $state)
                            ),
                        Forms\Components\TextInput::make('net_amount')
                            ->label('مووچەی پاک')
                            ->numeric()
                            ->required()
                            ->prefix('دینار')
                            ->disabled(),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('ڕێکەوتی پێدان')
                            ->required()
                            ->default(now()),
                        Forms\Components\TextInput::make('month')
                            ->label('مانگ')
                            ->required()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('year')
                            ->label('ساڵ')
                            ->required(),
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
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('کارمەند')
                    ->sortable(),
                Tables\Columns\TextColumn::make('month')
                    ->label('مانگ'),
                Tables\Columns\TextColumn::make('year')
                    ->label('ساڵ'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('مووچە')
                    ->money('IQD'),
                Tables\Columns\TextColumn::make('deductions')
                    ->label('سزا')
                    ->money('IQD'),
                Tables\Columns\TextColumn::make('net_amount')
                    ->label('مووچەی پاک')
                    ->money('IQD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('ڕێکەوتی پێدان')
                    ->date(),
            ])
            ->defaultSort('payment_date', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalaries::route('/'),
            'create' => Pages\CreateSalary::route('/create'),
            'edit' => Pages\EditSalary::route('/{record}/edit'),
        ];
    }
}
