<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Http\Services\UserRoleAssignmentService;
use App\Models\Role;
use App\Models\RoleUser;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return __('filament-resources.resources.RoleResource.label');
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            /** @var RoleUser $record */
            return app(UserRoleAssignmentService::class)->assign(
                auth()->user(),
                $record->user()->firstOrFail(),
                Role::query()->findOrFail($data['role_id']),
                $record,
            );
        } catch (ValidationException | AuthorizationException $exception) {
            Notification::make()
                ->title($this->resolveErrorMessage($exception))
                ->danger()
                ->send();

            throw $exception;
        }
    }

    protected function resolveErrorMessage(ValidationException | AuthorizationException $exception): string
    {
        if ($exception instanceof ValidationException) {
            return collect($exception->errors())
                ->flatten()
                ->filter()
                ->first() ?? $exception->getMessage();
        }

        return $exception->getMessage();
    }
}
