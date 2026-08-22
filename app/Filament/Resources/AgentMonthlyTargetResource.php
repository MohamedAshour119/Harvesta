<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentMonthlyTargetResource\Pages;
use App\Filament\Resources\AgentMonthlyTargetResource\RelationManagers;
use App\Models\AgentMonthlyTarget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AgentMonthlyTargetResource extends Resource
{
    protected static ?string $model = AgentMonthlyTarget::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    public static function getModelLabel(): string 
    {
        return __('filament-resources.resources.AgentMonthlyTargetResource.label');
    }

    public static function getPluralModelLabel(): string 
    {
        return __('filament-resources.resources.AgentMonthlyTargetResource.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.financials');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('agent_id')
                    ->relationship('agent', 'full_name')
                    ->label(__('filament-resources.resources.AgentMonthlyTargetResource.agent'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('achieved_amount')
                    ->label(__('filament-resources.resources.AgentMonthlyTargetResource.achieved_amount'))
                    ->default(0)
                    ->suffix('EGP')
                    ->numeric(),
                Forms\Components\TextInput::make('target_amount')
                    ->label(__('filament-resources.resources.AgentMonthlyTargetResource.target_amount'))
                    ->suffix('EGP')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('bonus_amount')
                    ->label(__('filament-resources.resources.AgentMonthlyTargetResource.bonus_amount'))
                    ->suffix('EGP')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('agent.full_name')
                    ->label(__('filament-resources.resources.AgentMonthlyTargetResource.agent')),
                Tables\Columns\TextColumn::make('achieved_amount')
                    ->label(__('filament-resources.resources.AgentMonthlyTargetResource.achieved_amount'))
                    ->money('EGP'),
                Tables\Columns\TextColumn::make('target_amount')
                    ->label(__('filament-resources.resources.AgentMonthlyTargetResource.target_amount'))
                    ->money('EGP'),
                Tables\Columns\TextColumn::make('bonus_amount')
                    ->label(__('filament-resources.resources.AgentMonthlyTargetResource.bonus_amount'))
                    ->money('EGP'),
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
            'index' => Pages\ListAgentMonthlyTargets::route('/'),
            'create' => Pages\CreateAgentMonthlyTarget::route('/create'),
            'edit' => Pages\EditAgentMonthlyTarget::route('/{record}/edit'),
        ];
    }
}
