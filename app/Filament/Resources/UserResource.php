<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Resources\Concerns\Translatable;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    use Translatable;
    
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'icon-users';

    public static function getModelLabel(): string 
    {
        return __('filament-resources.resources.UserResource.label');
    }

    public static function getPluralModelLabel(): string 
    {
        return __('filament-resources.resources.UserResource.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.people_and_access');
    }
    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('full_name')
                    ->label(__('filament-resources.common.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('filament-resources.common.email'))
                    ->email()
                    ->required()
                    ->maxLength(255),
                // TextInput::make('code')
                //     ->label(__('filament-resources.common.agent_code'))
                //     ->helperText(__('filament-resources.common.agent_code_help'))
                //     ->reactive()
                //     ->required(fn (Get $get) => $get('is_agent'))
                //     ->maxLength(255),
                // TextInput::make('commission')
                //     ->label(__('filament-resources.common.commission_rate'))
                //     ->numeric()
                //     ->reactive()
                //     ->required(fn (Get $get) => $get('is_agent'))
                //     ->maxLength(255),
                // TextInput::make('password')
                //     ->label(__('filament-resources.common.password'))
                //     ->password()
                //     ->required()
                //     ->maxLength(255),
                Toggle::make('is_agent')
                    ->label(__('filament-resources.common.is_agent'))
                    ->reactive()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label(__('filament-resources.common.name')),
                TextColumn::make('email')
                    ->label(__('filament-resources.common.email')),
                // IconColumn::make('is_agent')
                //     ->label(__('filament-resources.common.is_agent'))
                //     ->boolean(),
                // TextColumn::make('code')
                //     ->label(__('filament-resources.common.agent_code')),
                // TextColumn::make('commission')
                //     ->formatStateUsing(fn($state) => "{$state}%")
                //     ->label(__('filament-resources.common.commission_rate')),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
