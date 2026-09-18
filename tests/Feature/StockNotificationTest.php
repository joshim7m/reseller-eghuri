<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Notifications\StockStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createReseller(): User
{
    $role = Role::firstOrCreate(['name' => 'reseller'], ['slug' => 'reseller']);

    return User::factory()->create([
        'user_type' => 'reseller',
        'role_id' => $role->id,
        'status' => true,
    ]);
}

function createProduct(int $quantity = 10): Product
{
    $category = Category::factory()->create();

    return Product::create([
        'category_id' => $category->id,
        'title' => fake()->words(3, true),
        'slug' => fake()->slug(),
        'unit_price' => 100,
        'sale_price' => 100,
        'quantity' => $quantity,
        'status' => 'active',
    ]);
}

it('creates in_stock notification when quantity goes from 0 to positive', function () {
    $user = createReseller();
    $product = createProduct(quantity: 0);

    $product->update(['quantity' => 5]);

    expect($user->fresh()->notifications)->toHaveCount(1);
    expect($user->fresh()->notifications->first()->type)->toBe(StockStatusChanged::class);
    expect($user->fresh()->notifications->first()->data['status'])->toBe('in_stock');
    expect($user->fresh()->notifications->first()->data['product_id'])->toBe($product->id);
});

it('creates out_of_stock notification when quantity goes from positive to 0', function () {
    $user = createReseller();
    $product = createProduct(quantity: 5);

    $product->update(['quantity' => 0]);

    expect($user->fresh()->notifications)->toHaveCount(1);
    expect($user->fresh()->notifications->first()->type)->toBe(StockStatusChanged::class);
    expect($user->fresh()->notifications->first()->data['status'])->toBe('out_of_stock');
    expect($user->fresh()->notifications->first()->data['product_id'])->toBe($product->id);
});

it('does not create notification when quantity stays the same', function () {
    $user = createReseller();
    $product = createProduct(quantity: 5);

    $product->update(['quantity' => 5]);

    expect($user->fresh()->notifications)->toHaveCount(0);
});

it('does not create notification when quantity stays at zero', function () {
    $user = createReseller();
    $product = createProduct(quantity: 0);

    $product->update(['quantity' => 0]);

    expect($user->fresh()->notifications)->toHaveCount(0);
});

it('does not create notification for non-reseller users', function () {
    $role = Role::firstOrCreate(['name' => 'admin'], ['slug' => 'admin']);
    $user = User::factory()->create([
        'user_type' => 'admin',
        'role_id' => $role->id,
        'status' => true,
    ]);
    $product = createProduct(quantity: 0);

    $product->update(['quantity' => 5]);

    expect($user->fresh()->notifications)->toHaveCount(0);
});

it('notifies all resellers', function () {
    $users = collect([
        createReseller(),
        createReseller(),
        createReseller(),
    ]);

    $product = createProduct(quantity: 0);
    $product->update(['quantity' => 5]);

    $users->each(function ($user) {
        expect($user->fresh()->notifications)->toHaveCount(1);
    });
});

it('notification includes correct product data', function () {
    $user = createReseller();
    $product = createProduct(quantity: 0);

    $product->update(['quantity' => 5]);

    $notification = $user->fresh()->notifications->first();
    $data = $notification->data;

    expect($data['product_id'])->toBe($product->id);
    expect($data['product_title'])->toBe($product->title);
    expect($data['product_slug'])->toBe($product->slug);
    expect($data['status'])->toBe('in_stock');
    expect($data['link'])->toContain($product->slug);
});

it('returns notifications index as json', function () {
    $user = createReseller();
    $product = createProduct(quantity: 0);
    $product->update(['quantity' => 5]);

    $this->actingAs($user)
        ->getJson(route('notifications.index'))
        ->assertOk()
        ->assertJsonStructure([
            'notifications',
            'unreadCount',
        ]);
});

it('returns unread count correctly', function () {
    $user = createReseller();
    $product = createProduct(quantity: 0);
    $product->update(['quantity' => 5]);

    $this->actingAs($user)
        ->getJson(route('notifications.index'))
        ->assertOk()
        ->assertJsonPath('unreadCount', 1);
});

it('marks all notifications as read', function () {
    $user = createReseller();
    $product = createProduct(quantity: 0);
    $product->update(['quantity' => 5]);

    expect($user->fresh()->unreadNotifications()->count())->toBe(1);

    $this->actingAs($user)
        ->postJson(route('notifications.mark-all-read'))
        ->assertOk();

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});
