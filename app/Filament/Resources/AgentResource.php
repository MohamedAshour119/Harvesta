<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentResource\Pages;
use App\Filament\Resources\AgentResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class AgentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'icon-agent';
    public static function getModelLabel(): string 
    {
        return __('filament-resources.resources.AgentResource.label');
    }

    public static function getPluralModelLabel(): string 
    {
        return __('filament-resources.resources.AgentResource.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.people_and_access');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_agent', true);
    }

    public static function canCreate(): bool
    {
        return Gate::allows('superAdminAccess');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('full_name')
                ->label(__('filament-resources.common.name'))
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label(__('filament-resources.common.email'))
                ->email()
                ->required()
                ->maxLength(255),

            TextInput::make('code')
                ->label(__('filament-resources.common.agent_code'))
                ->helperText(__('filament-resources.common.agent_code_help'))
                ->unique(ignoreRecord: true)
                ->rule('uuid')
                ->reactive()
                ->nullable()
                ->maxLength(36),

            TextInput::make('commission')
                ->label(__('filament-resources.common.commission_rate'))
                ->numeric()
                ->required()
                ->minValue(0)
                ->reactive()
                ->required(fn (Get $get) => $get('is_agent'))
                ->step(0.01),
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
                TextColumn::make('id')
                    ->label(__('filament-resources.common.id'))
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label(__('filament-resources.common.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('filament-resources.common.email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label(__('filament-resources.common.agent_code'))
                    ->sortable(),
                TextColumn::make('commission')
                    ->label(__('filament-resources.common.commission_rate'))
                    ->formatStateUsing(fn($state) => "{$state}%")
                    ->sortable(),
                TextColumn::make('created_at')->label(__('filament-resources.common.created_at'))->dateTime('Y-m-d'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => Gate::allows('superAdminAccess')),
                Action::make('view_as_agent')
                    ->label(__('filament-resources.resources.AgentResource.view_as_agent'))
                    ->icon('heroicon-o-user')
                    ->color('success')
                    ->visible(fn () => Gate::allows('superAdminAccess'))
                    ->action(function ($record, Component $livewire) {
                        $response = Http::withToken(Auth::user()->createToken('filament-impersonate', [], now()->addSeconds(15))->plainTextToken)
                            ->post(route('api.agents.impersonate', ['user' => $record->id]));

                        if ($response->successful()) {
                            $payload = $response->json('payload');
                            // Add impersonate=true flag to the URL
                            $frontendUrl = rtrim(config('app.app_url'), '/') . '/dashboard?payload=' . urlencode($payload) . '&impersonate=true';
                            $livewire->dispatch('open-new-tab', ['url' => $frontendUrl]);
                        } else {
                            Notification::make()
                                ->title('Impersonation Failed')
                                ->body('Could not generate impersonation payload. Check logs for details.')
                                ->danger()
                                ->send();
                        }
                    })
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => Gate::allows('superAdminAccess')),
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
            'index' => Pages\ListAgents::route('/'),
            'create' => Pages\CreateAgent::route('/create'),
            'edit' => Pages\EditAgent::route('/{record}/edit'),
        ];
    }

}
