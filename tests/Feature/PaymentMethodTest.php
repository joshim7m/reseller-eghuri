<?php

use App\Models\Module;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;

function paymentMethodAdmin(): User
{
    $module = Module::firstOrCreate(['name' => 'Orders']);
    $permission = Permission::firstOrCreate(
        ['slug' => 'manage-orders'],
        ['name' => 'Manage Orders', 'module_id' => $module->id]
    );
    $role = Role::firstOrCreate(['slug' => 'payment-manager'], ['name' => 'Payment Manager']);
    $role->permissions()->sync([$permission->id]);

    return User::create([
        'name' => 'Payment Admin',
        'email' => uniqid().'payment-admin@test.dev',
        'password' => bcrypt('password'),
        'role_id' => $role->id,
        'user_type' => 'admin',
        'status' => true,
        'email_verified_at' => now(),
    ]);
}

function paymentMethodPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'bKash',
        'provider' => 'bKash',
        'type' => 'withdrawal',
        'account_number' => '01700000099',
        'status' => 'active',
    ], $overrides);
}

it('lists payment methods in the admin panel', function () {
    $method = PaymentMethod::create(paymentMethodPayload());

    $this->actingAs(paymentMethodAdmin())
        ->get(route('admin.payment-methods.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/PaymentMethods/Index')
            ->has('paymentMethods.data', 1)
            ->where('paymentMethods.data.0.name', $method->name)
        );
});

it('creates a payment method with a logo', function () {
    Storage::fake('public');

    $this->actingAs(paymentMethodAdmin())
        ->post(route('admin.payment-methods.store'), [
            ...paymentMethodPayload(),
            'image' => UploadedFile::fake()->image('bkash.png'),
        ])
        ->assertRedirect(route('admin.payment-methods.index'))
        ->assertSessionHas('success');

    expect(PaymentMethod::where('account_number', '01700000099')->exists())->toBeTrue();
});

it('validates required fields when creating a payment method', function () {
    $this->actingAs(paymentMethodAdmin())
        ->post(route('admin.payment-methods.store'), [])
        ->assertInvalid(['name', 'account_number', 'status']);
});

it('requires a unique account number when creating a payment method', function () {
    PaymentMethod::create(paymentMethodPayload());

    $this->actingAs(paymentMethodAdmin())
        ->post(route('admin.payment-methods.store'), paymentMethodPayload())
        ->assertInvalid('account_number');
});

it('allows the same account number with a different type', function () {
    PaymentMethod::create(paymentMethodPayload());

    $this->actingAs(paymentMethodAdmin())
        ->post(route('admin.payment-methods.store'), paymentMethodPayload(['type' => 'ecommerce']))
        ->assertRedirect(route('admin.payment-methods.index'))
        ->assertSessionHas('success');

    expect(PaymentMethod::where('account_number', '01700000099')->count())->toBe(2);
});

it('updates a payment method', function () {
    $method = PaymentMethod::create(paymentMethodPayload());

    $this->actingAs(paymentMethodAdmin())
        ->put(route('admin.payment-methods.update', $method), [
            ...paymentMethodPayload(['name' => 'Nagad', 'status' => 'inactive']),
        ])
        ->assertRedirect(route('admin.payment-methods.index'))
        ->assertSessionHas('success');

    $method->refresh();

    expect($method->name)->toBe('Nagad')
        ->and($method->status)->toBe('inactive');
});

it('updates a payment method with multipart form data and a new logo', function () {
    Storage::fake('public');

    $method = PaymentMethod::create(paymentMethodPayload());
    $originalImage = $this->app->basePath();

    $response = $this->actingAs(paymentMethodAdmin())
        ->put(route('admin.payment-methods.update', $method), [
            ...paymentMethodPayload(['name' => 'bKash Updated']),
            'image' => UploadedFile::fake()->image('bkash-new.png'),
        ]);

    $response->assertRedirect(route('admin.payment-methods.index'))
        ->assertSessionHas('success');

    $method->refresh();

    expect($method->name)->toBe('bKash Updated');
});

it('toggles the payment method status', function () {
    $method = PaymentMethod::create(paymentMethodPayload());

    $this->actingAs(paymentMethodAdmin())
        ->patch(route('admin.payment-methods.toggle-status', $method))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($method->refresh()->status)->toBe('inactive');

    $this->actingAs(paymentMethodAdmin())
        ->patch(route('admin.payment-methods.toggle-status', $method));

    expect($method->refresh()->status)->toBe('active');
});

it('deletes a payment method', function () {
    $method = PaymentMethod::create(paymentMethodPayload());

    $this->actingAs(paymentMethodAdmin())
        ->delete(route('admin.payment-methods.destroy', $method))
        ->assertRedirect(route('admin.payment-methods.index'));

    expect(PaymentMethod::find($method->id))->toBeNull();
});
