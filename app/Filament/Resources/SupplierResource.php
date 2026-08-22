<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\Concerns\Translatable;

class SupplierResource extends Resource
{
    use Translatable;
    
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'icon-supplier';
    
    public static function getModelLabel(): string 
    {
        return __('filament-resources.resources.SupplierResource.label');
    }

    public static function getPluralModelLabel(): string 
    {
        return __('filament-resources.resources.SupplierResource.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.people_and_access');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('filament-resources.common.name'))
                    ->required(),
                Forms\Components\TextInput::make('website')
                    ->label('Website')
                    ->url(),
                Forms\Components\TextInput::make('email')
                    ->label(__('filament-resources.common.email'))
                    ->email(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-resources.common.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('website')
                    ->label(__('filament-resources.common.website')),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('filament-resources.common.email')),
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
