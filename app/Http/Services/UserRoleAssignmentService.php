<?php

namespace App\Http\Services;

use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserRoleAssignmentService
{
    public function assign(?User $actor, User $targetUser, Role $role, ?RoleUser $preferredAssignment = null): RoleUser
    {
        $actor = $this->resolveAuthorizedActor($actor);
        $this->ensureTargetUserCanBeUpdated($actor, $targetUser, $role);

        return DB::transaction(function () use ($targetUser, $role, $preferredAssignment): RoleUser {
            $assignments = RoleUser::query()
                ->where('user_id', $targetUser->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $assignment = null;

            if ($preferredAssignment?->user_id === $targetUser->id) {
                $assignment = $assignments->firstWhere('id', $preferredAssignment->id);
                $assignments = $assignments
                    ->reject(fn (RoleUser $candidate) => $candidate->id === $preferredAssignment->id)
                    ->values();
            }

            $assignment ??= $assignments->shift();

            if ($assignment) {
                $assignment->forceFill([
                    'role_id' => $role->id,
                ])->save();
            } else {
                $assignment = RoleUser::query()->create([
                    'user_id' => $targetUser->id,
                    'role_id' => $role->id,
                ]);
            }

            if ($assignments->isNotEmpty()) {
                RoleUser::query()
                    ->whereIn('id', $assignments->pluck('id'))
                    ->delete();
            }

            return $assignment->fresh(['role', 'user']);
        });
    }

    protected function resolveAuthorizedActor(?User $actor): User
    {
        if (! $actor instanceof User || ! $actor->hasRole('Super Admin')) {
            throw new AuthorizationException(__('filament-resources.resources.RoleResource.messages.only_super_admins_can_assign_roles'));
        }

        return $actor;
    }

    protected function ensureTargetUserCanBeUpdated(User $actor, User $targetUser, Role $role): void
    {
        if ($actor->is($targetUser)) {
            throw ValidationException::withMessages([
                'user_id' => __('filament-resources.resources.RoleResource.messages.cannot_change_own_role'),
            ]);
        }

        $targetHasSuperAdminRole = $targetUser->roles()
            ->where('name', 'Super Admin')
            ->exists();

        if ($targetHasSuperAdminRole && $role->name !== 'Super Admin') {
            throw ValidationException::withMessages([
                'user_id' => __('filament-resources.resources.RoleResource.messages.existing_super_admin_cannot_be_changed'),
            ]);
        }

        if ($role->name === 'Super Admin' && $this->anotherSuperAdminAlreadyExists($targetUser)) {
            throw ValidationException::withMessages([
                'role_id' => __('filament-resources.resources.RoleResource.messages.only_one_super_admin_allowed'),
            ]);
        }
    }

    protected function anotherSuperAdminAlreadyExists(User $targetUser): bool
    {
        return RoleUser::query()
            ->where('user_id', '!=', $targetUser->id)
            ->whereHas('role', fn ($query) => $query->where('name', 'Super Admin'))
            ->exists();
    }
}
