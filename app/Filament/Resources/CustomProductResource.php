<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomProductResource\Pages;
use App\Filament\Resources\CustomProductResource\RelationManagers;
use App\Models\CustomProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;

class CustomProductResource extends Resource
{
    protected static ?string $model = CustomProduct::class;

    protected static ?string $navigationIcon = 'icon-products';

    public static function getModelLabel(): string 
    {
        return __('filament-resources.resources.CustomProductResource.label');
    }

    public static function getPluralModelLabel(): string 
    {
        return __('filament-resources.resources.CustomProductResource.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.inventory_and_orders');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('full_name')
                    ->label(__('filament-resources.resources.CustomProductResource.full_name'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('filament-resources.resources.CustomProductResource.email'))
                    ->required(),
                TextInput::make('phone')
                    ->label(__('filament-resources.resources.CustomProductResource.phone'))
                    ->required(),
                TextInput::make('product_name')
                    ->label(__('filament-resources.resources.CustomProductResource.product_name'))
                    ->required(),
                TextInput::make('estimated_quantity')
                    ->label(__('filament-resources.resources.CustomProductResource.estimated_quantity'))
                    ->required(),
                TextInput::make('budget')
                    ->label(__('filament-resources.resources.CustomProductResource.budget'))
                    ->numeric()
                    ->suffix('EGP')
                    ->required(),
                Textarea::make('product_description')
                    ->label(__('filament-resources.resources.CustomProductResource.product_description'))
                    ->required(),
                DatePicker::make('deadline')
                    ->native(false)
                    ->label(__('filament-resources.resources.CustomProductResource.deadline'))
                    ->required(),
                Select::make('status')
                    ->options([
                        'pending' => __('filament-resources.select_options.status.pending'),
                        'approved' => __('filament-resources.select_options.status.approved'),
                        'rejected' => __('filament-resources.select_options.status.rejected'),
                    ])
                    ->native(false)
                    ->label(__('filament-resources.common.status'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('filament-resources.common.id'))
                    ->searchable(),
                SpatieMediaLibraryImageColumn::make('custom_product_images')
                    ->label(__('filament-resources.resources.CustomProductResource.images'))
                    ->height(120)
                    ->collection('custom_product_images'),
                TextColumn::make('full_name')
                    ->label(__('filament-resources.resources.CustomProductResource.full_name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('filament-resources.resources.CustomProductResource.email'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(__('filament-resources.resources.CustomProductResource.phone'))
                    ->searchable(),
                TextColumn::make('product_name')
                    ->label(__('filament-resources.resources.CustomProductResource.product_name'))
                    ->searchable(),
                TextColumn::make('estimated_quantity')
                    ->label(__('filament-resources.resources.CustomProductResource.estimated_quantity'))
                    ->searchable(),
                TextColumn::make('budget')
                    ->label(__('filament-resources.resources.CustomProductResource.budget'))
                    ->money('EGP')
                    ->searchable(),
                TextColumn::make('product_description')
                    ->label(__('filament-resources.resources.CustomProductResource.product_description'))
                    ->searchable(),
                TextColumn::make('deadline')
                    ->label(__('filament-resources.resources.CustomProductResource.deadline'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('filament-resources.common.status'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('filament-resources.common.created_at'))
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('filament-resources.common.status'))
                    ->options([
                        'pending' => __('filament-resources.select_options.status.pending'),
                        'approved' => __('filament-resources.select_options.status.approved'),
                        'rejected' => __('filament-resources.select_options.status.rejected'),
                    ])
                    ->native(false),
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
            'index' => Pages\ListCustomProducts::route('/'),
            'create' => Pages\CreateCustomProduct::route('/create'),
            'edit' => Pages\EditCustomProduct::route('/{record}/edit'),
        ];
    }
}
