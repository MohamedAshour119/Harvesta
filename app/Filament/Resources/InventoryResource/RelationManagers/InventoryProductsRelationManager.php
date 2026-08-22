<?php

namespace App\Filament\Resources\InventoryResource\RelationManagers;

use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventoryProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'inventoryProducts';

    protected static ?string $recordTitleAttribute = 'product.name';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('product_id')
                ->label(__('filament-resources.common.product'))
                ->options(fn () => Product::query()
                    ->get()
                    ->mapWithKeys(function (Product $product) {
                        $locale = app()->getLocale();
                        $name = $product->getTranslation('name', $locale, false)
                            ?: $product->getTranslation('name', 'en', false)
                            ?: $product->getTranslation('name', 'ar', false)
                            ?: 'Product #'.$product->id;

                        return [$product->id => $name];
                    })
                    ->all())
                ->searchable()
                ->preload()
                ->reactive()
                ->required()
                ->afterStateUpdated(function (callable $set, $state): void {
                    $set('variant_id', null);

                    if (! $state) {
                        return;
                    }

                    $variantIds = ProductVariant::query()
                        ->where('product_id', $state)
                        ->pluck('id');

                    if ($variantIds->count() === 1) {
                        $set('variant_id', $variantIds->first());
                    }
                }),
            Forms\Components\Select::make('variant_id')
                ->label(__('filament-resources.common.variant'))
                ->options(fn (callable $get) => $get('product_id')
                    ? ProductVariant::query()
                        ->where('product_id', $get('product_id'))
                        ->pluck('variant_value', 'id')
                        ->all()
                    : [])
                ->searchable()
                ->preload()
                ->reactive()
                ->native(false)
                ->required(),
            Forms\Components\TextInput::make('quantity')
                ->label(__('filament-resources.common.quantity'))
                ->helperText('Number of pieces in this inventory.')
                ->integer()
                ->minValue(1)
                ->required(),
            Forms\Components\TextInput::make('show_alert_at_quantity')
                ->label(__('filament-resources.resources.InventoryProductResource.show_alert_at_quantity'))
                ->helperText('Alert threshold in pieces.')
                ->integer()
                ->minValue(0)
                ->default(5)
                ->required(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
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
                Tables\Columns\TextColumn::make('variant.variant_value')
                    ->label(__('filament-resources.common.variant')),
                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('filament-resources.common.quantity')),
                Tables\Columns\TextColumn::make('show_alert_at_quantity')
                    ->label(__('filament-resources.resources.InventoryProductResource.show_alert_at_quantity')),
                Tables\Columns\TextColumn::make('last_sold_at')
                    ->label(__('filament-resources.resources.InventoryResource.last_sold_at'))
                    ->dateTime('d M Y, h:i A'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data, string $model): Model {
                        $variant = ProductVariant::query()->findOrFail($data['variant_id']);
                        $record = $model::query()
                            ->where('inventory_id', $this->ownerRecord->id)
                            ->where('variant_id', $variant->id)
                            ->first();

                        $proposedQuantity = ($record?->quantity ?? 0) + (int) $data['quantity'];
                        $this->assertCapacity(
                            proposedQuantity: $proposedQuantity,
                            currentRecordId: $record?->id
                        );

                        if ($record) {
                            $record->update([
                                'product_id' => $variant->product_id,
                                'quantity' => $proposedQuantity,
                                'show_alert_at_quantity' => $data['show_alert_at_quantity'],
                            ]);

                            return $record;
                        }

                        return $model::query()->create([
                            'inventory_id' => $this->ownerRecord->id,
                            'product_id' => $variant->product_id,
                            'variant_id' => $variant->id,
                            'quantity' => (int) $data['quantity'],
                            'show_alert_at_quantity' => $data['show_alert_at_quantity'],
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(function (array $data): array {
                        $data['product_id'] = $data['product_id'];

                        return $data;
                    })
                    ->using(function (Model $record, array $data): Model {
                        $variant = ProductVariant::query()->findOrFail($data['variant_id']);

                        $this->assertCapacity(
                            proposedQuantity: (int) $data['quantity'],
                            currentRecordId: $record->id
                        );

                        $record->update([
                            'product_id' => $variant->product_id,
                            'variant_id' => $variant->id,
                            'quantity' => (int) $data['quantity'],
                            'show_alert_at_quantity' => $data['show_alert_at_quantity'],
                        ]);

                        return $record;
                    }),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    private function assertCapacity(int $proposedQuantity, ?int $currentRecordId = null): void
    {
        $currentSum = $this->ownerRecord->inventoryProducts()
            ->when($currentRecordId, fn (Builder $query) => $query->where('id', '!=', $currentRecordId))
            ->sum('quantity');

        $max = (int) ($this->ownerRecord->maximum_quantity ?? 0);

        if (($currentSum + $proposedQuantity) <= $max) {
            return;
        }

        $remaining = $max - $currentSum;

        if ($remaining <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'quantity' => __('filament-resources.resources.InventoryResource.the_inventory_is_full'),
            ]);
        }

        throw \Illuminate\Validation\ValidationException::withMessages([
            'quantity' => __('filament-resources.resources.InventoryResource.only_:remaining_units_of_capacity_remaining', [
                'remaining' => $remaining,
            ]),
        ]);
    }
}
