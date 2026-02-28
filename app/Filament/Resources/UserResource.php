<?php
// app/Filament/Resources/UserResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'بەڕێوەبردنی سیستەم';
    protected static ?string $modelLabel = 'بەکارهێنەر';
    protected static ?string $pluralModelLabel = 'بەکارهێنەران';
    protected static ?string $navigationLabel = 'بەکارهێنەران';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('زانیاری بەکارهێنەر')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('ناوی تەواو')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-user'),
                        Forms\Components\TextInput::make('email')
                            ->label('ئیمەیڵ')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-envelope'),
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('ڕێکەوتی پشتڕاستکردنەوە')
                            ->default(now()),
                        Forms\Components\Toggle::make('is_admin')
                            ->label('بەڕێوەبەرە؟')
                            ->default(false)
                            ->helperText('بەکارهێنەرانی بەڕێوەبەر دەستڕاگەیشتنی تەواویان هەیە'),
                        Forms\Components\TextInput::make('password')
                            ->label('ووشەی نهێنی')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-lock-closed')
                            ->confirmed(),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('دووبارە ووشەی نهێنی')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(false)
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-lock-closed'),
                    ])->columns(2),
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
                Tables\Columns\TextColumn::make('name')
                    ->label('ناو')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->label('ئیمەیڵ')
                    ->searchable()
                    ->icon('heroicon-m-envelope'),
                Tables\Columns\IconColumn::make('is_admin')
                    ->label('بەڕێوەبەر')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('پشتڕاستکراوە')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('بەرواری دروستبوون')
                    ->dateTime('Y/m/d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_admin')
                    ->label('بەڕێوەبەر')
                    ->placeholder('هەموو')
                    ->trueLabel('بەڕێوەبەر')
                    ->falseLabel('بەکارهێنەر'),
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('پشتڕاستکراوە')
                    ->placeholder('هەموو')
                    ->trueLabel('پشتڕاستکراوە')
                    ->falseLabel('پشتڕاستنەکراوە')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('email_verified_at'),
                        false: fn ($query) => $query->whereNull('email_verified_at'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('بینین')
                    ->icon('heroicon-m-eye'),
                Tables\Actions\EditAction::make()
                    ->label('دەستکاری')
                    ->icon('heroicon-m-pencil'),
                Action::make('change_password')
                    ->label('گۆڕینی ووشەی نهێنی')
                    ->icon('heroicon-m-key')
                    ->color(Color::Orange)
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->label('ووشەی نهێنی نوێ')
                            ->password()
                            ->required()
                            ->confirmed(),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('دووبارە ووشەی نهێنی نوێ')
                            ->password()
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update([
                            'password' => Hash::make($data['password']),
                        ]);

                        Notification::make()
                            ->title('ووشەی نهێنی بە سەرکەوتوویی گۆڕدرا')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('گۆڕینی ووشەی نهێنی')
                    ->modalIcon('heroicon-o-key')
                    ->modalSubmitActionLabel('گۆڕین'),
                Action::make('verify_email')
                    ->label('پشتڕاستکردنەوە')
                    ->icon('heroicon-m-check-badge')
                    ->color(Color::Green)
                    ->visible(fn (User $record): bool => is_null($record->email_verified_at))
                    ->action(function (User $record): void {
                        $record->update([
                            'email_verified_at' => now(),
                        ]);

                        Notification::make()
                            ->title('ئیمەیڵ پشتڕاست کرایەوە')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('سڕینەوە')
                    ->icon('heroicon-m-trash')
                    ->modalHeading('سڕینەوەی بەکارهێنەر')
                    ->modalDescription('دڵنیای لە سڕینەوەی ئەم بەکارهێنەرە؟')
                    ->modalSubmitActionLabel('بەڵێ، بسڕەوە')
                    ->modalCancelActionLabel('نەخێر'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('سڕینەوەی دیاریکراوەکان')
                        ->modalHeading('سڕینەوەی بەکارهێنەرە دیاریکراوەکان')
                        ->modalDescription('دڵنیای لە سڕینەوەی ئەم بەکارهێنەرانە؟')
                        ->modalSubmitActionLabel('بەڵێ، بیانسڕەوە')
                        ->modalCancelActionLabel('نەخێر'),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('هیچ بەکارهێنەرێک نییە')
            ->emptyStateDescription('یەکەم بەکارهێنەر دروست بکە')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('دروستکردنی بەکارهێنەر'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
