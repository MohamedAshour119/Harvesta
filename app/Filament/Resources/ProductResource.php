<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Concerns\Translatable;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;

class ProductResource extends Resource
{
    use Translatable;
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'icon-products';
    public static function getTranslatableLocales(): array
    {
        return [app()->getLocale(), app()->getLocale() === 'ar' ? 'en' : 'ar'];
    }

    // Use translation function instead of hardcoded values
    public static function getModelLabel(): string 
    {
        return __('filament-resources.resources.ProductResource.label');
    }

    public static function getPluralModelLabel(): string 
    {
        return __('filament-resources.resources.ProductResource.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.product_management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament-resources.common.product_information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('filament-resources.common.name'))
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label(__('filament-resources.common.description')),
                        Forms\Components\Select::make('industry_category_id')
                            ->label(__('filament-resources.resources.IndustryCategoryResource.label'))
                            ->relationship('industrialCategory', 'name')
                            ->native(false)
                            ->required()
                            ->preload(),
                        Forms\Components\TextInput::make('min_order_value')
                            ->label(__('filament-resources.resources.ProductResource.min_order_value'))
                            ->numeric()
                            ->default(50)
                            ->reactive(),
                        Forms\Components\TextInput::make('agent_min_order_value')
                            ->label(__('filament-resources.resources.ProductResource.agent_min_order_value'))
                            ->numeric()
                            ->default(fn (callable $get) => $get('min_order_value') ?? 50)
                            ->dehydrateStateUsing(fn ($state, callable $get) => ($state === null || $state === '')
                                ? $get('min_order_value')
                                : $state)
                            ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                if (($state === null || $state === '') && $get('min_order_value') !== null) {
                                    $set('agent_min_order_value', $get('min_order_value'));
                                }
                            }),
                        Forms\Components\TextInput::make('minimum_order_quantity')
                            ->label(__('filament-resources.resources.ProductResource.minimum_order_quantity'))
                            ->numeric()
                            ->default(1),
                        Forms\Components\Toggle::make('is_approved')
                            ->label(__('filament-resources.common.is_approved'))
                            ->default(false),
                        Forms\Components\Select::make('status')
                            ->label(__('filament-resources.common.status'))
                            ->options(__('filament-resources.select_options.status'))
                            ->native(false)
                            ->default('pending')
                            ->required(),
                        Forms\Components\Toggle::make('oem')
                            ->label(__('filament-resources.resources.ProductResource.oem'))
                            ->default(false),

                    ]),
                Forms\Components\Section::make(__('filament-resources.common.product_images'))
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('products_main_image')
                            ->label(__('filament-resources.common.main_image'))
                            ->collection('main_images')
                            ->helperText(__('filament-resources.common.main_image_help'))
                            ->image()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->imageEditor(),

                        SpatieMediaLibraryFileUpload::make('products_other_images')
                            ->label(__('filament-resources.common.additional_images'))
                            ->collection('products_other_images')
                            ->multiple()
                            ->maxFiles(5)
                            ->helperText(__('filament-resources.common.max_5_images'))
                            ->image()
                            ->imageResizeMode('cover')
                            ->imageEditor(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('filament-resources.common.id'))
                    ->sortable(),
                SpatieMediaLibraryImageColumn::make('product_thumbnail')
                    ->label(__('filament-resources.common.image'))
                    ->collection('main_images')
                    ->width(100)
                    ->height(100),
                Tables\Columns\TextColumn::make('industrialCategory.name')
                    ->label(__('filament-resources.common.category'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-resources.common.name'))
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_approved')
                    ->label(__('filament-resources.common.is_approved'))
                    ->boolean(),
                Tables\Columns\IconColumn::make('oem')
                    ->label(__('filament-resources.resources.ProductResource.oem'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('min_order_value')
                    ->label(__('filament-resources.resources.ProductResource.min_order_value')),
                Tables\Columns\TextColumn::make('agent_min_order_value')
                    ->label(__('filament-resources.resources.ProductResource.agent_min_order_value')),
                Tables\Columns\TextColumn::make('minimum_order_quantity')
                    ->label(__('filament-resources.resources.ProductResource.minimum_order_quantity')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('industrialCategory')
                    ->label(__('filament-resources.common.category'))
                    ->relationship('industrialCategory', 'name')
                    ->preload()
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
