<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryProductResource\Pages;
use App\Filament\Resources\InventoryProductResource\RelationManagers;
use App\Models\InventoryProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

class InventoryProductResource extends Resource
{
    protected static ?string $model = InventoryProduct::class;

    protected static ?string $navigationIcon = 'inventory_products';

    public static function getModelLabel(): string 
    {
        return __('filament-resources.resources.InventoryProductResource.label');
    }

    public static function getPluralModelLabel(): string 
    {
        return __('filament-resources.resources.InventoryProductResource.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.inventory_and_orders');
    }



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Create: repeater for multiple items
                Repeater::make('inventory_items')
                    ->label(__('filament-resources.resources.InventoryProductResource.inventory_items'))
                    ->addActionLabel(__('filament-resources.resources.InventoryProductResource.add_inventory_item'))
                    ->schema([
                        Select::make('product_id')
                            ->label(__('filament-resources.common.product'))
                            ->relationship('product', 'name')
                            ->reactive()
                            ->native(false)
                            ->required(),
                        Select::make('variant_id')
                            ->label(__('filament-resources.common.variant'))
                            ->native(false)
                            ->options(fn (callable $get) => $get('product_id')
                                ? \App\Models\ProductVariant::where('product_id', $get('product_id'))->pluck('variant_value','id')->toArray()
                                : [])
                            ->required(),
                        TextInput::make('quantity')
                            ->label(__('filament-resources.common.quantity'))
                            ->helperText('Number of pieces in stock.')
                            ->numeric()
                            ->required()
                            ->rules([
                                function (callable $get) {
                                    return function (string $attribute, $value, $fail) use ($get) {
                                        $inventoryId = $get('inventory_id');
                        
                                        if (! $inventoryId) {
                                            return;
                                        }
                        
                                        $currentSum = \App\Models\InventoryProduct::where('inventory_id', $inventoryId)->sum('quantity');
                                        $max = optional(\App\Models\Inventory::find($inventoryId))->maximum_quantity ?? 0;
                        
                                        if (($value + $currentSum) > $max) {
                                            $remaining = $max - $currentSum;
                                            if ($remaining <= 0) {
                                                $fail(__('filament-resources.resources.InventoryResource.the_inventory_is_full'));
                                            } else {
                                                $fail(__('filament-resources.resources.InventoryResource.only_:remaining_units_of_capacity_remaining', [
                                                    'remaining' => $remaining,
                                                ]));
                                            }
                                        }
                                    };
                                },
                            ]),
                        TextInput::make('show_alert_at_quantity')
                            ->label(__('filament-resources.resources.InventoryProductResource.show_alert_at_quantity'))
                            ->helperText('Alert threshold in pieces.')
                            ->numeric()
                            ->default(5)
                            ->required(),
                        
                        Select::make('inventory_id')
                            ->label(__('filament-resources.resources.InventoryResource.label'))
                            ->relationship('inventory', 'name')
                            ->native(false)
                            ->required(),
                    ])
                    ->minItems(1)
                    ->columns(4)
                    ->columnSpanFull()
                    ->mutateRelationshipDataBeforeCreateUsing(fn(array $data): array => [
                        'product_id' => $data['product_id'],
                        'variant_id' => $data['variant_id'],
                        'quantity'   => $data['quantity'],
                        'inventory_id'=> $data['inventory_id'],
                    ])
                    ->visibleOn('create'),

                // Edit: single record fields
                Select::make('product_id')
                    ->label(__('filament-resources.common.product'))
                    ->relationship('product', 'name')
                    ->required()
                    ->native(false)
                    ->visibleOn('edit'),

                Select::make('variant_id')
                    ->label(__('filament-resources.common.variant'))
                    ->options(fn (callable $get) => $get('product_id')
                        ? \App\Models\ProductVariant::where('product_id', $get('product_id'))->pluck('variant_value','id')->toArray()
                        : [])
                    ->required()
                    ->native(false)
                    ->visibleOn('edit'),

                TextInput::make('quantity')
                    ->label(__('filament-resources.common.quantity'))
                    ->helperText('Number of pieces in stock.')
                    ->numeric()
                    ->required()
                    ->rules([
                        function (callable $get) {
                            return function (string $attribute, $value, $fail) use ($get) {
                                $inventoryId = $get('inventory_id');
                                $recordId = $get('id'); // current record ID (only available on edit)
                
                                if (! $inventoryId) {
                                    return;
                                }
                
                                // ✅ Sum all quantities in the same inventory EXCEPT the current record
                                $currentSum = \App\Models\InventoryProduct::where('inventory_id', $inventoryId)
                                    ->when($recordId, fn ($query) => $query->where('id', '!=', $recordId))
                                    ->sum('quantity');
                
                                $max = optional(\App\Models\Inventory::find($inventoryId))->maximum_quantity ?? 0;
                
                                if (($value + $currentSum) > $max) {
                                    $remaining = $max - $currentSum;
                                    if ($remaining <= 0) {
                                        $fail(__('filament-resources.resources.InventoryResource.the_inventory_is_full'));
                                    } else {
                                        $fail(__('filament-resources.resources.InventoryResource.only_:remaining_units_of_capacity_remaining', [
                                            'remaining' => $remaining,
                                        ]));
                                    }
                                }
                            };
                        },
                    ])
                    ->visibleOn('edit'),

                TextInput::make('show_alert_at_quantity')
                    ->label(__('filament-resources.resources.InventoryProductResource.show_alert_at_quantity'))
                    ->helperText('Alert threshold in pieces.')
                    ->numeric()
                    ->default(5)
                    ->required()
                    ->visibleOn('edit'),

                Select::make('inventory_id')
                    ->label(__('filament-resources.resources.InventoryResource.label'))
                    ->relationship('inventory', 'name')
                    ->native(false)
                    ->required()
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('filament-resources.common.id')),
                TextColumn::make('product.name')
                    ->label(__('filament-resources.common.product'))
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $locale = app()->getLocale();
                        $term = mb_strtolower($search);
                        return $query->whereHas('product', function ($q) use ($term, $locale) {
                            $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"$locale\"'))) like ?", ["%{$term}%"])
                              ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"en\"'))) like ?", ["%{$term}%"])
                              ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"ar\"'))) like ?", ["%{$term}%"]);
                        });
                    }),
                TextColumn::make('variant.variant_value')
                    ->label(__('filament-resources.common.variant'))
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label(__('filament-resources.common.quantity'))
                    ->searchable(),
                TextColumn::make('inventory.name')
                    ->label(__('filament-resources.resources.InventoryResource.label'))
                    ->searchable(),
                TextColumn::make('show_alert_at_quantity')
                    ->label(__('filament-resources.resources.InventoryProductResource.show_alert_at_quantity'))
                    ->searchable(),
                TextColumn::make('last_sold_at')
                    ->label(__('filament-resources.resources.InventoryResource.last_sold_at'))
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
            'index' => Pages\ListInventoryProducts::route('/'),
            'create' => Pages\CreateInventoryProduct::route('/create'),
            'edit' => Pages\EditInventoryProduct::route('/{record}/edit'),
        ];
    }
}
