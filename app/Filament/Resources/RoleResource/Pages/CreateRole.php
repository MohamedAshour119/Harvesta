<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Http\Services\UserRoleAssignmentService;
use App\Models\Role;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return __('filament-resources.resources.RoleResource.label');
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(UserRoleAssignmentService::class)->assign(
                auth()->user(),
                User::query()->findOrFail($data['user_id']),
                Role::query()->findOrFail($data['role_id']),
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
