<?php

namespace Tests\Services\Feature\Users;

use App\Filament\Resources\RoleResource;
use App\Http\Services\UserRoleAssignmentService;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserRoleAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_assignment_updates_existing_user_role_instead_of_creating_a_duplicate(): void
    {
        [$superAdminRole, $adminRole, $customerRole] = $this->seedRoles();

        $actor = $this->createUserWithRole($superAdminRole);
        $target = User::factory()->create();

        RoleUser::query()->create([
            'user_id' => $target->id,
            'role_id' => $adminRole->id,
        ]);

        $duplicateAssignment = RoleUser::query()->create([
            'user_id' => $target->id,
            'role_id' => $customerRole->id,
        ]);

        $assignment = app(UserRoleAssignmentService::class)->assign(
            $actor,
            $target,
            $adminRole,
            $duplicateAssignment,
        );

        $this->assertSame($duplicateAssignment->id, $assignment->id);
        $this->assertSame(
            [$duplicateAssignment->id],
            RoleUser::query()
                ->where('user_id', $target->id)
                ->pluck('id')
                ->all(),
        );
        $this->assertDatabaseCount('role_user', 2);
        $this->assertDatabaseHas('role_user', [
            'id' => $duplicateAssignment->id,
            'user_id' => $target->id,
            'role_id' => $adminRole->id,
        ]);
    }

    public function test_admin_users_cannot_assign_roles(): void
    {
        [, $adminRole, $customerRole] = $this->seedRoles();

        $actor = $this->createUserWithRole($adminRole);
        $target = User::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(UserRoleAssignmentService::class)->assign($actor, $target, $customerRole);
    }

    public function test_role_resource_is_only_accessible_to_super_admins(): void
    {
        [$superAdminRole, $adminRole] = $this->seedRoles();

        $admin = $this->createUserWithRole($adminRole);
        $this->actingAs($admin);

        $this->assertFalse(RoleResource::canAccess());
        $this->assertFalse(RoleResource::canCreate());

        $superAdmin = $this->createUserWithRole($superAdminRole);
        $this->actingAs($superAdmin);

        $this->assertTrue(RoleResource::canAccess());
        $this->assertTrue(RoleResource::canCreate());
    }

    public function test_existing_super_admin_roles_cannot_be_downgraded_from_this_flow(): void
    {
        [$superAdminRole, $adminRole] = $this->seedRoles();

        $actor = $this->createUserWithRole($superAdminRole);
        $target = $this->createUserWithRole($superAdminRole);

        $this->expectException(ValidationException::class);

        app(UserRoleAssignmentService::class)->assign($actor, $target, $adminRole);
    }

    public function test_cannot_assign_super_admin_role_to_a_second_user(): void
    {
        [$superAdminRole, $adminRole] = $this->seedRoles();

        $actor = $this->createUserWithRole($superAdminRole);
        $target = $this->createUserWithRole($adminRole);
        $this->createUserWithRole($superAdminRole);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Only one super admin is allowed in the system.');

        app(UserRoleAssignmentService::class)->assign($actor, $target, $superAdminRole);
    }

    /**
     * @return array{0: Role, 1: Role, 2: Role}
     */
    private function seedRoles(): array
    {
        return [
            Role::query()->create(['name' => 'Super Admin']),
            Role::query()->create(['name' => 'Admin']),
            Role::query()->create(['name' => 'Customer']),
        ];
    }

    private function createUserWithRole(Role $role): User
    {
        $user = User::factory()->create();

        RoleUser::query()->create([
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);

        return $user;
    }
}
