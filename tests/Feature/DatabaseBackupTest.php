<?php

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('exposes the database backup admin page', function () {
    $module = Module::create(['name' => 'Settings']);
    $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
    $permission = Permission::create([
        'name' => 'Manage Settings',
        'module_id' => $module->id,
        'slug' => 'manage-settings',
    ]);
    $role->permissions()->attach($permission->id);

    $admin = User::factory()->create([
        'role_id' => $role->id,
        'user_type' => 'admin',
        'status' => 1,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.settings.backup'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Settings/DatabaseBackup'));
});
