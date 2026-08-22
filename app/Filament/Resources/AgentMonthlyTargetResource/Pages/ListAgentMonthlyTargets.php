<?php

namespace App\Filament\Resources\AgentMonthlyTargetResource\Pages;

use App\Filament\Resources\AgentMonthlyTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgentMonthlyTargets extends ListRecords
{
    protected static string $resource = AgentMonthlyTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
