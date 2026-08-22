<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductVariantResource\Pages;
use App\Filament\Resources\ProductVariantResource\RelationManagers;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class ProductVariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static ?string $navigationIcon = 'icon-product_variants';
    
    public static function getModelLabel(): string 
    {
        return __('filament-resources.resources.ProductVariantResource.label');
    }

    public static function getPluralModelLabel(): string 
    {
        return __('filament-resources.resources.ProductVariantResource.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.product_management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label(__('filament-resources.common.product'))
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),
                Forms\Components\Select::make('variant_type_id')
                    ->label(__('filament-resources.common.variant'))
                    ->relationship('variantType', 'name')
                    ->required()
                    ->native(false),
                Forms\Components\TextInput::make('variant_value')
                    ->label(__('filament-resources.common.variant_value'))
                    ->required(),
                Forms\Components\Select::make('availability')
                    ->label(__('filament-resources.common.stock_availability'))
                    ->options([
                        'in-stock' => __('filament-resources.common.in_stock'),
                        'out-of-stock' => __('filament-resources.common.out_of_stock'),
                    ])
                    ->native(false)
                    ->required(),
                Forms\Components\Select::make('suppliers')
                    ->label(__('filament-resources.common.supplier'))
                    ->relationship('suppliers', 'name')
                    ->multiple()
                    ->required()
                    ->preload(),
                Forms\Components\TextInput::make('unit_price_usd')
                    ->label(__('filament-resources.common.price'))
                    ->numeric()
                    ->suffix('EGP')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $discountPercentage = $get('discount_percentage');
                        if (is_numeric($state) && is_numeric($discountPercentage)) {
                            $calculatedPrice = $state - ($state * ($discountPercentage / 100));
                            $set('price_after_discount', number_format($calculatedPrice, 2, '.', ''));
                        } else {
                            $set('price_after_discount', null);
                        }

                        if ($get('agent_price') === null || $get('agent_price') === '') {
                            $set('agent_price', is_numeric($state) ? number_format((float) $state, 2, '.', '') : null);
                        }
                    }),
                Forms\Components\TextInput::make('discount_percentage')
                    ->label(__('filament-resources.common.discount_percentage'))
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->suffix('%')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $unitPrice = $get('unit_price_usd');
                        if (is_numeric($state) && is_numeric($unitPrice)) {
                            $calculatedPrice = $unitPrice - ($unitPrice * ($state / 100));
                            $set('price_after_discount', number_format($calculatedPrice, 2, '.', ''));
                        } else {
                            $set('price_after_discount', null);
                        }
                    }),
                Forms\Components\TextInput::make('price_after_discount')
                    ->label(__('filament-resources.common.price_after_discount'))
                    ->numeric()
                    ->suffix('EGP')
                    ->disabled()
                    ->dehydrated(true),
                Forms\Components\TextInput::make('agent_price')
                    ->label(__('filament-resources.common.agent_price'))
                    ->numeric()
                    ->suffix('EGP')
                    ->required()
                    ->dehydrateStateUsing(fn ($state, callable $get) => ($state === null || $state === '')
                        ? $get('unit_price_usd')
                        : $state)
                    ->afterStateHydrated(function ($state, callable $set, callable $get) {
                        if (($state === null || $state === '') && is_numeric($get('unit_price_usd'))) {
                            $set('agent_price', number_format((float) $get('unit_price_usd'), 2, '.', ''));
                        }
                    }),
                Forms\Components\TextInput::make('weight')
                    ->label(__('filament-resources.resources.ProductVariantResource.weight'))
                    ->suffix('KG')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('volume')
                    ->label(__('filament-resources.resources.ProductVariantResource.volume'))
                    ->suffix('M³')
                    ->numeric()
                    ->default(0)
                    ->required(),
                SpatieMediaLibraryFileUpload::make('variants_images')
                    ->label(__('filament-resources.common.image'))
                    ->collection('variants_images')
                    ->required()
                    // ->helperText(__('filament-resources.common.main_image_help'))
                    ->image()
                    ->imageEditor(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('filament-resources.common.id'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_id')
                    ->label(__('filament-resources.common.product'))
                    ->sortable(),
                SpatieMediaLibraryImageColumn::make('product_thumbnail')
                    ->label(__('filament-resources.common.image'))
                    ->collection('variants_images')
                    ->width(100)
                    ->height(100),
                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('filament-resources.common.product')),
                Tables\Columns\TextColumn::make('variantType.name')
                    ->label(__('filament-resources.common.variant')),
                Tables\Columns\TextColumn::make('variant_value')
                    ->label(__('filament-resources.common.variant_value')),
                Tables\Columns\TextColumn::make('unit_price_usd')
                    ->label(__('filament-resources.common.price'))
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('availability')
                    ->label(__('filament-resources.common.stock_availability'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label(__('filament-resources.common.discount_percentage'))
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_after_discount')
                    ->label(__('filament-resources.common.price_after_discount'))
                    ->money('EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('agent_price')
                    ->label(__('filament-resources.common.agent_price'))
                    ->money('EGP')
                    ->sortable(),
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
            'index' => Pages\ListProductVariants::route('/'),
            'create' => Pages\CreateProductVariant::route('/create'),
            'edit' => Pages\EditProductVariant::route('/{record}/edit'),
        ];
    }
}
