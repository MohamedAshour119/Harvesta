<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VariantTypeResource\Pages;
use App\Filament\Resources\VariantTypeResource\RelationManagers;
use App\Models\VariantType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\Concerns\Translatable;

class VariantTypeResource extends Resource
{
    use Translatable;
    protected static ?string $model = VariantType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    
    public static function getTranslatableLocales(): array
    {
        return [app()->getLocale(), app()->getLocale() === 'ar' ? 'en' : 'ar'];
    }

    public static function getModelLabel(): string 
    {
        return __('filament-resources.resources.VariantTypeResource.label');
    }

    public static function getPluralModelLabel(): string 
    {
        return __('filament-resources.resources.VariantTypeResource.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.product_management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('filament-resources.common.name'))
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-resources.common.name'))
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
            'index' => Pages\ListVariantTypes::route('/'),
            'create' => Pages\CreateVariantType::route('/create'),
            'edit' => Pages\EditVariantType::route('/{record}/edit'),
        ];
    }
}
