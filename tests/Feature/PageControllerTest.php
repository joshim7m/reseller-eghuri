<?php

use App\Models\Module;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function adminUser(): User
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

it('renders the pages index page', function () {
    $admin = adminUser();

    Page::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.pages.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Pages/Index')
            ->has('pages', 3));
});

it('renders the page create form', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->get(route('admin.pages.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Pages/Create'));
});

it('creates a page', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('admin.pages.store'), [
            'title' => 'Shipping Policy',
            'content' => '<p>Our shipping policy.</p>',
            'status' => 1,
        ])
        ->assertRedirect(route('admin.pages.index'));

    $this->assertDatabaseHas('pages', [
        'title' => 'Shipping Policy',
        'slug' => 'shipping-policy',
        'content' => '<p>Our shipping policy.</p>',
        'status' => 1,
    ]);
});

it('uses the submitted slug when creating a page', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('admin.pages.store'), [
            'title' => 'Shipping Policy',
            'slug' => 'shipping-rules',
            'content' => '<p>Our shipping policy.</p>',
        ])
        ->assertRedirect(route('admin.pages.index'));

    $this->assertDatabaseHas('pages', [
        'title' => 'Shipping Policy',
        'slug' => 'shipping-rules',
    ]);
});

it('validates required fields when creating a page', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('admin.pages.store'), [])
        ->assertSessionHasErrors(['title', 'content']);
});

it('rejects a duplicate slug when creating a page', function () {
    $admin = adminUser();

    Page::factory()->create(['slug' => 'shipping-policy']);

    $this->actingAs($admin)
        ->post(route('admin.pages.store'), [
            'title' => 'Shipping Policy',
            'slug' => 'shipping-policy',
            'content' => '<p>Our shipping policy.</p>',
        ])
        ->assertSessionHasErrors(['slug']);
});

it('renders the page edit form', function () {
    $admin = adminUser();

    $page = Page::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.pages.edit', $page))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('Admin/Pages/Edit')
            ->where('page.id', $page->id));
});

it('updates a page', function () {
    $admin = adminUser();

    $page = Page::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.pages.update', $page), [
            'title' => 'Return Policy',
            'content' => '<p>Our return policy.</p>',
            'status' => 0,
        ])
        ->assertRedirect(route('admin.pages.index'));

    $this->assertDatabaseHas('pages', [
        'id' => $page->id,
        'title' => 'Return Policy',
        'slug' => 'return-policy',
        'content' => '<p>Our return policy.</p>',
        'status' => 0,
    ]);
});

it('rejects a duplicate slug when updating a page', function () {
    $admin = adminUser();

    $page = Page::factory()->create();
    $other = Page::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.pages.update', $page), [
            'title' => 'Shipping Policy',
            'slug' => $other->slug,
            'content' => '<p>Our shipping policy.</p>',
        ])
        ->assertSessionHasErrors(['slug']);
});

it('deletes a page', function () {
    $admin = adminUser();

    $page = Page::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.pages.destroy', $page))
        ->assertRedirect(route('admin.pages.index'));

    $this->assertDatabaseMissing('pages', ['id' => $page->id]);
});

it('shows an active page on the storefront', function () {
    $page = Page::factory()->create([
        'title' => 'Terms and Conditions',
        'slug' => 'terms-and-conditions',
    ]);

    $this->get(route('pages.show', $page->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('StoreFront/Pages/Show')
            ->where('page.slug', 'terms-and-conditions'));
});

it('does not show an inactive page on the storefront', function () {
    $page = Page::factory()->inactive()->create();

    $this->get(route('pages.show', $page->slug))->assertNotFound();
});

it('only shares active pages with the storefront layout', function () {
    Page::factory()->create(['title' => 'About Us', 'slug' => 'about-us']);
    Page::factory()->inactive()->create(['title' => 'Draft', 'slug' => 'draft']);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->has('pages', 1)
            ->where('pages.0.slug', 'about-us'));
});
