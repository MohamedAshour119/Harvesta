<?php

namespace App\Filament\Resources\AgentMonthlyTargetResource\Pages;

use App\Filament\Resources\AgentMonthlyTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAgentMonthlyTarget extends EditRecord
{
    protected static string $resource = AgentMonthlyTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
