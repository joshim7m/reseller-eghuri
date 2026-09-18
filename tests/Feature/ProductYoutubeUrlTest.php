<?php

use App\Models\Category;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

function youtubeAdminUser(): User
{
    $module = Module::create(['name' => 'Catalog']);
    $permission = Permission::create(['name' => 'Manage Catalog', 'slug' => 'manage-catalog', 'module_id' => $module->id]);
    $role = Role::create(['name' => 'Catalog Manager', 'slug' => 'catalog-manager']);
    $role->permissions()->sync([$permission->id]);

    return User::create([
        'name' => 'Catalog Admin',
        'email' => 'catalog-admin@test.dev',
        'password' => Hash::make('password'),
        'role_id' => $role->id,
        'user_type' => 'admin',
        'status' => true,
        'email_verified_at' => now(),
    ]);
}

it('persists the youtube url on product store', function () {
    $admin = youtubeAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.products.store'), [
            'title' => 'Hand Mixer',
            'description' => 'Powerful hand mixer.',
            'unit_price' => 800,
            'sale_price' => 900,
            'status' => 'active',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ])
        ->assertRedirect(route('admin.products.index'));

    $product = Product::first();

    expect($product->youtube_url)->toBe('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
});

it('updates the youtube url on product update', function () {
    $admin = youtubeAdminUser();
    $product = Product::create([
        'title' => 'Original',
        'slug' => 'original-'.uniqid(),
        'description' => 'Description.',
        'unit_price' => 100,
        'sale_price' => 120,
        'status' => 'active',
        'youtube_url' => 'https://www.youtube.com/watch?v=oldId12345',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), [
            'title' => 'Updated',
            'description' => 'Updated description.',
            'unit_price' => 150,
            'sale_price' => 180,
            'status' => 'active',
            'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($product->refresh()->youtube_url)->toBe('https://youtu.be/dQw4w9WgXcQ');
});

it('exposes the youtube url on the product page', function () {
    $category = Category::factory()->create(['name' => 'Appliances']);
    $product = Product::create([
        'category_id' => $category->id,
        'title' => 'Hand Mixer',
        'slug' => 'hand-mixer-'.uniqid(),
        'description' => 'Powerful hand mixer.',
        'unit_price' => 800,
        'sale_price' => 900,
        'status' => 'active',
        'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    ]);

    $this->get(route('product.show', $product->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StoreFront/Products/Show')
            ->where('product.youtube_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
});

it('allows a product without a youtube url', function () {
    $admin = youtubeAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.products.store'), [
            'title' => 'Plain Product',
            'description' => 'No video here.',
            'unit_price' => 300,
            'sale_price' => 350,
            'status' => 'active',
        ])
        ->assertRedirect(route('admin.products.index'));

    expect(Product::first()->youtube_url)->toBeNull();
});
