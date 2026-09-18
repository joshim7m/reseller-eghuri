<?php

use App\Models\InstagramImage;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function instagramImageAdminUser(): User
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

it('renders the instagram images index page as an ordered table', function () {
    $admin = instagramImageAdminUser();

    $first = InstagramImage::create(['title' => 'Latest Post', 'image' => 'images/instagram/latest.jpg']);
    InstagramImage::create(['title' => 'Older Post', 'image' => 'images/instagram/older.jpg']);

    $this->actingAs($admin)
        ->get(route('admin.instagram-images.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/InstagramImages/Index')
            ->has('images.data', 2)
            ->where('images.data.0.id', $first->id));
});
