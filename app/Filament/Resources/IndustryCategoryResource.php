<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IndustryCategoryResource\Pages;
use App\Filament\Resources\IndustryCategoryResource\RelationManagers;
use App\Models\IndustryCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
class IndustryCategoryResource extends Resource
{
    use Translatable;
    protected static ?string $model = IndustryCategory::class;

    protected static ?string $navigationIcon = 'icon-categories';
    
    public static function getTranslatableLocales(): array
    {
        return [app()->getLocale(), app()->getLocale() === 'ar' ? 'en' : 'ar'];
    }
    public static function getModelLabel(): string 
    {
        return __('filament-resources.resources.IndustryCategoryResource.label');
    }

    public static function getPluralModelLabel(): string 
    {
        return __('filament-resources.resources.IndustryCategoryResource.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.categories_and_marketing');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('filament-resources.common.name'))
                    ->required(),
                SpatieMediaLibraryFileUpload::make('industry_category_image')
                    ->label(__('filament-resources.common.main_image'))
                    ->collection('industry_category_images')
                    ->required()
                    ->image()
                    ->imageResizeMode('cover')
                    ->imageEditor(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-resources.common.name'))
                    ->searchable(),
                SpatieMediaLibraryImageColumn::make('industry_category_image')
                    ->label(__('filament-resources.common.image'))
                    ->collection('industry_category_images')
                    ->width(100)
                    ->height(100),
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
            'index' => Pages\ListIndustryCategories::route('/'),
            'create' => Pages\CreateIndustryCategory::route('/create'),
            'edit' => Pages\EditIndustryCategory::route('/{record}/edit'),
        ];
    }
}
