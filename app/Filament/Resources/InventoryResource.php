<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryResource\Pages;
use App\Filament\Resources\InventoryResource\RelationManagers;
use App\Filament\Resources\InventoryResource\RelationManagers\InventoryProductsRelationManager;
use App\Models\Inventory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Concerns\Translatable;
use Filament\Tables\Columns\TextColumn;

class InventoryResource extends Resource
{
    use Translatable;

    protected static ?string $model = Inventory::class;

    protected static ?string $navigationIcon = 'icon-inventory';

    public static function getModelLabel(): string 
    {
        return __('filament-resources.resources.InventoryResource.label');
    }

    public static function getPluralModelLabel(): string 
    {
        return __('filament-resources.resources.InventoryResource.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.inventory_and_orders');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                TextInput::make('name')
                    ->label(__('filament-resources.common.name'))
                    ->required(),

                TextInput::make('maximum_quantity')
                    ->label(__('filament-resources.common.maximum_quantity'))
                    ->numeric()
                    ->required(),

                TextInput::make('location')
                    ->label(__('filament-resources.common.location'))
                    ->required(),

                Select::make('status')
                    ->label(__('filament-resources.common.status'))
                    ->native(false)
                    ->options([
                        'empty' => __('filament-resources.resources.InventoryResource.empty'),
                        'low-stock' => __('filament-resources.resources.InventoryResource.low-stock'),
                        'normal-stock' => __('filament-resources.resources.InventoryResource.normal-stock'),
                        'high-stock' => __('filament-resources.resources.InventoryResource.high-stock'),
                    ])
                    ->default('empty')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')
                ->label(__('filament-resources.common.name')),

                TextColumn::make('maximum_quantity')
                ->label(__('filament-resources.common.maximum_quantity')),

                TextColumn::make('location')
                ->label(__('filament-resources.common.location')),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament-resources.common.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'empty' => __('filament-resources.resources.InventoryResource.empty'),
                        'low-stock' => __('filament-resources.resources.InventoryResource.low-stock'),
                        'normal-stock' => __('filament-resources.resources.InventoryResource.normal-stock'),
                        'high-stock' => __('filament-resources.resources.InventoryResource.high-stock'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'empty', 'low-stock' => 'danger',
                        'normal-stock' => 'warning',
                        'high-stock' => 'success',
                        default => 'gray',
                    }),
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
            InventoryProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventories::route('/'),
            'create' => Pages\CreateInventory::route('/create'),
            'edit' => Pages\EditInventory::route('/{record}/edit'),
        ];
    }
}
