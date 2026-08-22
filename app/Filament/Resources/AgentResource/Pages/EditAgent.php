<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Filament\Resources\AgentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;

class EditAgent extends EditRecord
{
    // use EditRecord\Concerns\Translatable;

    protected static string $resource = AgentResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return Gate::allows('superAdminAccess');
    }
    protected function getHeaderActions(): array
    {
        return [
            // Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
