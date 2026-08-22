<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmartBundleResource\Pages;
use App\Filament\Resources\SmartBundleResource\RelationManagers;
use App\Models\SmartBundle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SmartBundleResource extends Resource
{
    use Translatable;

    protected static ?string $model = SmartBundle::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    public static function getModelLabel(): string 
    {
        return __('filament-resources.resources.SmartBundleResource.label');
    }

    public static function getPluralModelLabel(): string 
    {
        return __('filament-resources.resources.SmartBundleResource.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.financials');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('filament-resources.common.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('target_quantity')
                    ->label(__('filament-resources.resources.SmartBundleResource.target_quantity'))
                    ->required()
                    ->numeric()
                    ->minValue(1),
                Forms\Components\TextInput::make('discount_percentage')
                    ->label(__('filament-resources.resources.SmartBundleResource.discount_percentage'))
                    ->required()
                    ->numeric()
                    ->minValue(0.1)
                    ->maxValue(100.0)
                    ->suffix('%'),
                Forms\Components\Select::make('product_id')
                    ->label(__('filament-resources.resources.SmartBundleResource.product'))
                    ->relationship('product', 'name')
                    ->searchable()
                    ->nullable()
                    ->helperText('اتركه فارغاً لتعميم العرض (اختياري)'),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('filament-resources.resources.SmartBundleResource.is_active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-resources.common.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('target_quantity')
                    ->label(__('filament-resources.resources.SmartBundleResource.target_quantity')),
                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label(__('filament-resources.resources.SmartBundleResource.discount_percentage'))
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('filament-resources.resources.SmartBundleResource.product')),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label(__('filament-resources.resources.SmartBundleResource.is_active')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-resources.common.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListSmartBundles::route('/'),
            'create' => Pages\CreateSmartBundle::route('/create'),
            'edit' => Pages\EditSmartBundle::route('/{record}/edit'),
        ];
    }
}
