<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BonusResource\Pages;
use App\Filament\Resources\BonusResource\RelationManagers;
use App\Models\Bonus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BonusResource extends Resource
{
    protected static ?string $model = Bonus::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    public static function getModelLabel(): string 
    {
        return __('filament-resources.resources.BonusResource.label');
    }

    public static function getPluralModelLabel(): string 
    {
        return __('filament-resources.resources.BonusResource.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.financials');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'full_name')
                    ->label(__('filament-resources.resources.BonusResource.agent'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label(__('filament-resources.resources.BonusResource.amount'))
                    ->default(0)
                    ->suffix('EGP')
                    ->numeric(),
                Forms\Components\DateTimePicker::make('paid_at')
                    ->label(__('filament-resources.resources.BonusResource.paid_at'))
                    ->native(false)
                    ->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label(__('filament-resources.resources.BonusResource.agent')),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('filament-resources.resources.BonusResource.amount'))
                    ->money('EGP'),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label(__('filament-resources.resources.BonusResource.paid_at'))
                    ->dateTime('Y-m-d'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListBonuses::route('/'),
            'create' => Pages\CreateBonus::route('/create'),
            'edit' => Pages\EditBonus::route('/{record}/edit'),
        ];
    }
}
