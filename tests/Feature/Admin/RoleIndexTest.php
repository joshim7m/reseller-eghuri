<?php

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function roleIndexAdminUser(): User
{
    $module = Module::create(['name' => 'Catalog']);
    $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
    $permission = Permission::create([
        'name' => 'Manage Catalog',
        'module_id' => $module->id,
        'slug' => 'manage-catalog',
    ]);
    $role->permissions()->attach($permission->id);

    return User::factory()->create([
        'role_id' => $role->id,
        'user_type' => 'admin',
        'status' => 1,
    ]);
}

it('renders the roles index page', function () {
    $admin = roleIndexAdminUser();

    $this->actingAs($admin)
        ->get(route('admin.roles.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Roles/Index')
            ->has('roles'));
});

it('shows the permission count for each role', function () {
    $admin = roleIndexAdminUser();

    $module = Module::create(['name' => 'Orders']);
    $role = Role::create(['name' => 'Order Manager', 'slug' => 'order-manager']);
    $role->permissions()->attach(Permission::create([
        'name' => 'Manage Orders',
        'module_id' => $module->id,
        'slug' => 'manage-orders',
    ])->id);
    $role->permissions()->attach(Permission::create([
        'name' => 'View Orders',
        'module_id' => $module->id,
        'slug' => 'view-orders',
    ])->id);

    $this->actingAs($admin)
        ->get(route('admin.roles.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('roles.1.permissions_count', 2));
});
