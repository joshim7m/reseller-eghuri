<?php

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Slider;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function sliderAdminUser(): User
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

it('renders the hero slider index page as an ordered table', function () {
    $admin = sliderAdminUser();

    $first = Slider::create([
        'title' => 'New Season Styles',
        'sub_title' => 'Fresh looks for the new season.',
        'button_text' => 'Shop Now',
        'button_link' => '/products',
        'image' => 'images/instagram/placeholder-0.jpg',
    ]);
    Slider::create([
        'title' => 'Older Banner',
        'sub_title' => null,
        'button_text' => null,
        'button_link' => null,
        'image' => 'images/instagram/placeholder-1.jpg',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.sliders.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Sliders/Index')
            ->has('sliders.data', 2)
            ->where('sliders.data.0.id', $first->id));
});
