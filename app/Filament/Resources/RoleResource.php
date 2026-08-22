<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class RoleResource extends Resource
{
    protected static ?string $model = RoleUser::class;
    protected static ?string $navigationIcon = 'icon-roles';
    protected static ?string $label = "Roles";

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.manage_roles');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['role', 'user']);
    }

    public static function canViewAny(): bool
    {
        return static::canManageRoles();
    }

    public static function canAccess(): bool
    {
        return static::canManageRoles();
    }

    public static function canCreate(): bool
    {
        return static::canManageRoles();
    }

    public static function canEdit(EloquentModel $record): bool
    {
        return static::canManageRoles() && ! static::isProtectedAssignment($record);
    }

    public static function canDelete(EloquentModel $record): bool
    {
        return static::canManageRoles() && ! static::isProtectedAssignment($record);
    }

    public static function canDeleteAny(): bool
    {
        return static::canManageRoles();
    }

    public static function getModelLabel(): string 
    {
        return __('filament-resources.resources.RoleResource.label');
    }

    public static function getPluralModelLabel(): string 
    {
        return __('filament-resources.resources.RoleResource.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('role_id')
                    ->label(__('filament-resources.resources.RoleResource.role'))
                    ->options(fn () => Role::query()->orderBy('name')->pluck('name', 'id'))
                    ->disabled(fn (?RoleUser $record) => static::isProtectedAssignment($record))
                    ->native(false)
                    ->required(),

                Select::make('user_id')
                    ->label(__('filament-resources.resources.RoleResource.assign_users'))
                    ->options(fn () => User::query()->orderBy('email')->pluck('email', 'id'))
                    ->disabled(fn (?RoleUser $record) => filled($record?->id) || static::isProtectedAssignment($record))
                    ->searchable()
                    ->native(false)
                    ->required(fn (?RoleUser $record) => blank($record?->id))
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('role.name') // Display role name
                    ->label(__('filament-resources.resources.RoleResource.role_name'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Admin' => 'success',
                        'Super Admin' => 'info',
                        'Customer' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email') // Display user name
                    ->label(__('filament-resources.resources.RoleResource.user'))
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->role->name === 'Super Admin' || $record->user_id === auth()->id()),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    protected static function canManageRoles(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasRole('Super Admin');
    }

    protected static function isProtectedAssignment(?EloquentModel $record): bool
    {
        if (! $record instanceof RoleUser) {
            return false;
        }

        return $record->role?->name === 'Super Admin'
            || $record->user_id === auth()->id();
    }
}
