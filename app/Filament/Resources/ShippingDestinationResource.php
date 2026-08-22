<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingDestinationResource\Pages;
use App\Filament\Resources\ShippingDestinationResource\RelationManagers;
use App\Models\ShippingDestination;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\Concerns\Translatable;

class ShippingDestinationResource extends Resource
{
    use Translatable;
    protected static ?string $model = ShippingDestination::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function getModelLabel(): string 
    {
        return __('filament-resources.resources.ShippingDestinationResource.label');
    }

    public static function getPluralModelLabel(): string 
    {
        return __('filament-resources.resources.ShippingDestinationResource.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.shipping');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('city')
                    ->label(__('filament-resources.resources.ShippingDestinationResource.city'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('rate_per_kg')
                    ->label(__('filament-resources.resources.ShippingDestinationResource.rate_per_kg'))
                    ->required()
                    ->suffix('EGP')
                    ->numeric(),
                TextInput::make('rate_per_volume_unit')
                    ->label(__('filament-resources.resources.ShippingDestinationResource.rate_per_volume_unit'))
                    ->required()
                    ->suffix('EGP')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('city')
                    ->label(__('filament-resources.resources.ShippingDestinationResource.city'))
                    ->searchable(),
                TextColumn::make('rate_per_kg')
                    ->label(__('filament-resources.resources.ShippingDestinationResource.rate_per_kg'))
                    ->suffix(' EGP')
                    ->searchable(),
                TextColumn::make('rate_per_volume_unit')
                    ->label(__('filament-resources.resources.ShippingDestinationResource.rate_per_volume_unit'))
                    ->suffix(' EGP')
                    ->searchable(),
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
            'index' => Pages\ListShippingDestinations::route('/'),
            'create' => Pages\CreateShippingDestination::route('/create'),
            'edit' => Pages\EditShippingDestination::route('/{record}/edit'),
        ];
    }
}
