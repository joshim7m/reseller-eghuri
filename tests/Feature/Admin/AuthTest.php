<?php

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function createAdminWithPermission(string $permissionSlug): User
{
    $module = Module::firstOrCreate(['name' => 'Core'], ['name' => 'Core']);
    $permission = Permission::firstOrCreate(
        ['slug' => $permissionSlug],
        ['name' => ucfirst(str_replace('-', ' ', $permissionSlug)), 'module_id' => $module->id]
    );
    $role = Role::firstOrCreate(['slug' => 'admin-for-auth'], ['name' => 'Admin For Auth']);
    $role->permissions()->syncWithoutDetaching([$permission->id]);

    return User::create([
        'name' => 'Test Admin',
        'email' => 'test-admin-auth@example.com',
        'password' => Hash::make('password'),
        'role_id' => $role->id,
        'user_type' => 'admin',
        'status' => true,
        'email_verified_at' => now(),
    ]);
}

function createNonAdminUser(): User
{
    $role = Role::firstOrCreate(['slug' => 'wholeseller'], ['name' => 'Wholeseller']);

    return User::create([
        'name' => 'Test Wholeseller',
        'email' => 'test-wholeseller-auth@example.com',
        'password' => Hash::make('password'),
        'role_id' => $role->id,
        'user_type' => 'wholeseller',
        'status' => true,
        'email_verified_at' => now(),
    ]);
}

test('admin login page renders', function () {
    $this->get(route('admin.login'))->assertOk();
});

test('admin with manage-users permission can log in and is redirected to admin dashboard', function () {
    $user = createAdminWithPermission('manage-users');

    $response = $this->post(route('admin.login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('admin login sets remember cookie when remember is true', function () {
    $user = createAdminWithPermission('manage-users');

    $response = $this->post(route('admin.login'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => true,
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($user);

    $rememberCookie = collect($response->headers->getCookies())
        ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web'));

    expect($rememberCookie)->not->toBeNull();
});

test('remember cookie restores admin session after session expires', function () {
    $user = createAdminWithPermission('manage-users');

    $response = $this->post(route('admin.login'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => true,
    ]);

    $rememberCookie = collect($response->headers->getCookies())
        ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web'));

    expect($rememberCookie)->not->toBeNull();

    $this->app['auth']->forgetGuards();

    $fresh = $this->withCookie($rememberCookie->getName(), $rememberCookie->getValue());
    $fresh->get(route('admin.dashboard'))->assertOk();
});

test('non-admin user is redirected to home after admin login', function () {
    $user = createNonAdminUser();

    $response = $this->post(route('admin.login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
});

test('invalid credentials show validation error', function () {
    $response = $this->post(route('admin.login'), [
        'email' => 'nobody@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('admin can log out from admin panel', function () {
    $user = createAdminWithPermission('manage-users');

    $response = $this->actingAs($user)->post(route('admin.logout'));

    $response->assertRedirect(route('admin.login'));
    $this->assertGuest();
});

test('already authenticated admin opening admin login is redirected to dashboard', function () {
    $user = createAdminWithPermission('manage-users');

    $this->actingAs($user)
        ->get(route('admin.login'))
        ->assertRedirect(route('admin.dashboard'));
});

test('authenticated non-admin opening login page is redirected to home', function () {
    $user = createNonAdminUser();

    $this->actingAs($user)
        ->get(route('admin.login'))
        ->assertRedirect(route('home'));
});
