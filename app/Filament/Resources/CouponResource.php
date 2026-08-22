<?php

namespace App\Filament\Resources;

use App\Enums\CouponStatusEnum;
use App\Enums\CouponTypeEnum;
use App\Filament\Resources\CouponResource\Pages;
use App\Filament\Resources\CouponResource\RelationManagers;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'icon-coupons';

    public static function getModelLabel(): string 
    {
        return __('filament-resources.resources.CouponResource.label');
    }

    public static function getPluralModelLabel(): string 
    {
        return __('filament-resources.resources.CouponResource.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.categories_and_marketing');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->maxLength(60)
                    ->label(__('filament-resources.common.name'))
                    ->required(),
                TextInput::make('value')
                    ->numeric()
                    ->label(__('filament-resources.resources.CouponResource.value'))
                    ->required(),
                Select::make('status')
                    ->options(CouponStatusEnum::labels())
                    ->label(__('filament-resources.common.status'))
                    ->native(false)
                    ->required(),
                TextInput::make('usage_limit')
                    ->minValue(1)
                    ->numeric()
                    ->label(__('filament-resources.resources.CouponResource.usage_limit'))
                    ->required(),
                TextInput::make('used_count')
                    ->numeric()
                    ->default(0)
                    ->dehydrateStateUsing(fn ($state) => blank($state) ? 0 : $state)
                    ->disabled()
                    ->label(__('filament-resources.resources.CouponResource.used_count')),
                DatePicker::make('expires_at')
                    ->label(__('filament-resources.resources.CouponResource.expiration_date'))
                    ->native(false)
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-resources.common.name'))
                    ->copyable()
                    ->copyMessage(__('filament-resources.resources.CouponResource.coupon_copied'))
                    ->copyMessageDuration(1500),
                TextColumn::make('value')
                    ->label(__('filament-resources.resources.CouponResource.value')),
                TextColumn::make('status')
                    ->label(__('filament-resources.common.status'))
                    ->formatStateUsing(fn (?string $state): string => CouponStatusEnum::labels()[$state] ?? (string) $state),
                TextColumn::make('usage_limit')
                    ->label(__('filament-resources.resources.CouponResource.usage_limit')),
                TextColumn::make('used_count')
                    ->label(__('filament-resources.resources.CouponResource.used_count')),
                TextColumn::make('expires_at')
                    ->label(__('filament-resources.resources.CouponResource.expiration_date')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
