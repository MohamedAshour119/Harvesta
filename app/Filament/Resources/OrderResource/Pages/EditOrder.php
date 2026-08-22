<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Http\Services\ManualOrderApprovalService;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve_manual_sale')
                ->label(__('filament-resources.resources.OrderResource.approve_manual_sale'))
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->isManualSale() && $this->record->manual_sale_status === 'submitted')
                ->form([
                    Textarea::make('review_notes')
                        ->label(__('filament-resources.resources.OrderResource.approval_note'))
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    try {
                        app(ManualOrderApprovalService::class)->approve($this->record, auth()->user(), $data['review_notes'] ?? null);
                        Notification::make()->title(__('filament-resources.resources.OrderResource.manual_sale_approved'))->success()->send();
                        $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                    } catch (ValidationException $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    } catch (\RuntimeException $exception) {
                        Notification::make()
                            ->title(__('filament-resources.resources.OrderResource.insufficient_stock_title', ['default' => 'Insufficient Stock']))
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('reject_manual_sale')
                ->label(__('filament-resources.resources.OrderResource.reject_manual_sale'))
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->isManualSale() && $this->record->manual_sale_status === 'submitted')
                ->form([
                    Textarea::make('review_notes')
                        ->label(__('filament-resources.resources.OrderResource.rejection_reason'))
                        ->rows(3)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        app(ManualOrderApprovalService::class)->reject($this->record, auth()->user(), $data['review_notes'] ?? null);
                        Notification::make()->title(__('filament-resources.resources.OrderResource.manual_sale_rejected'))->success()->send();
                        $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                    } catch (ValidationException $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
