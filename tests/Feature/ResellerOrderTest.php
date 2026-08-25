<?php

use App\Models\Module;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ResellerOrder;
use App\Models\ResellerOrderItem;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Support\Facades\Hash;

function resellerTestUser(): User
{
    $role = Role::firstOrCreate(['slug' => 'reseller'], ['name' => 'Reseller']);

    return User::create([
        'name' => 'Test Reseller',
        'email' => 'reseller@test.dev',
        'password' => Hash::make('password'),
        'role_id' => $role->id,
        'user_type' => 'reseller',
        'status' => true,
        'email_verified_at' => now(),
    ]);
}

function adminTestUser(): User
{
    $module = Module::create(['name' => 'Orders']);
    $permission = Permission::create(['name' => 'Manage Orders', 'slug' => 'manage-orders', 'module_id' => $module->id]);
    $role = Role::create(['name' => 'Order Manager', 'slug' => 'order-manager']);
    $role->permissions()->sync([$permission->id]);

    return User::create([
        'name' => 'Test Admin',
        'email' => 'admin@test.dev',
        'password' => Hash::make('password'),
        'role_id' => $role->id,
        'user_type' => 'admin',
        'status' => true,
        'email_verified_at' => now(),
    ]);
}

function resellerOrderPayload(): array
{
    $product = Product::create([
        'title' => 'Test Product',
        'slug' => 'test-product-'.uniqid(),
        'unit_price' => 100,
        'sale_price' => 150,
        'quantity' => 50,
        'status' => 'active',
    ]);

    return [
        'items' => [
            [
                'product_id' => $product->id,
                'product_name' => 'Test Product',
                'quantity' => 2,
                'unit_price' => 100,
                'sale_price' => 150,
                'variant_id' => null,
                'size' => null,
                'color' => null,
            ],
        ],
        'customer_name' => 'John Doe',
        'mobile' => '01712345678',
        'delivery_charge' => 50,
        'shipping_address' => 'Dhaka, Bangladesh',
        'notes' => 'Please call before delivery',
    ];
}

it('redirects guests to login when visiting storefront reseller orders', function () {
    $this->get(route('reseller-orders.index'))->assertRedirect(route('login'));
});

it('forbids non-resellers from accessing reseller orders', function () {
    $role = Role::firstOrCreate(['slug' => 'customer'], ['name' => 'Customer']);
    $customer = User::factory()->create(['user_type' => 'customer', 'status' => true, 'role_id' => $role->id]);

    $this->actingAs($customer)
        ->get(route('reseller-orders.index'))
        ->assertForbidden();
});

it('shows the reseller their orders with wallet summary', function () {
    $reseller = resellerTestUser();
    Wallet::create(['user_id' => $reseller->id, 'credit' => 0, 'debit' => 0, 'balance' => 0]);

    ResellerOrder::factory()
        ->for($reseller)
        ->has(ResellerOrderItem::factory()->count(2), 'items')
        ->create();

    $this->actingAs($reseller)
        ->get(route('reseller-orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StoreFront/ResellerOrders/Index')
            ->has('orders.data', 1)
            ->has('wallet')
            ->where('pendingBalance', 0)
        );
});

it('stores a reseller order with items and a pending wallet transaction', function () {
    $reseller = resellerTestUser();

    $this->actingAs($reseller)
        ->post(route('reseller-orders.store'), resellerOrderPayload())
        ->assertRedirect(route('reseller-orders.index'))
        ->assertSessionHas('success');

    $order = ResellerOrder::first();

    expect($order->user_id)->toBe($reseller->id)
        ->and($order->order_number)->toStartWith('RSL-')
        ->and((float) $order->total_amount)->toBe(350.0)
        ->and($order->status)->toBe('pending')
        ->and($order->payment_status)->toBe('unpaid')
        ->and($order->items()->count())->toBe(1);

    $transaction = Transaction::where('reseller_order_id', $order->id)->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->type)->toBe('credit')
        ->and($transaction->status)->toBe('pending')
        ->and((float) $transaction->amount)->toBe(100.0);
});

it('rejects sale prices below the unit price', function () {
    $reseller = resellerTestUser();
    $payload = resellerOrderPayload();
    $payload['items'][0]['sale_price'] = 99;

    $this->actingAs($reseller)
        ->post(route('reseller-orders.store'), $payload)
        ->assertSessionHasErrors('items.0.sale_price');

    expect(ResellerOrder::count())->toBe(0);
});

it('groups the admin index by date and user', function () {
    $admin = adminTestUser();
    $reseller = resellerTestUser();

    ResellerOrder::factory()
        ->for($reseller)
        ->count(3)
        ->has(ResellerOrderItem::factory(), 'items')
        ->create();

    $this->actingAs($admin)
        ->get(route('admin.reseller-orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/ResellerOrders/Index')
            ->has('orders', 1)
            ->has('orders.0.groups', 1)
            ->where('orders.0.groups.0.count', 3)
        );
});

it('shows the per-user day view for admins and 404s when empty', function () {
    $admin = adminTestUser();
    $reseller = resellerTestUser();

    $order = ResellerOrder::factory()
        ->for($reseller)
        ->has(ResellerOrderItem::factory(), 'items')
        ->create();

    $date = $order->created_at->format('Y-m-d');

    $this->actingAs($admin)
        ->get(route('admin.reseller-orders.by-user', ['user' => $reseller->id, 'date' => $date]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/ResellerOrders/ByUser')
            ->has('orders', 1)
            ->where('user.id', $reseller->id)
        );

    $this->actingAs($admin)
        ->get(route('admin.reseller-orders.by-user', ['user' => $reseller->id, 'date' => '2001-01-01']))
        ->assertNotFound();
});

it('completes the wallet transaction when the order becomes completed and paid', function () {
    $admin = adminTestUser();
    $reseller = resellerTestUser();

    $order = ResellerOrder::factory()
        ->for($reseller)
        ->has(ResellerOrderItem::factory(['unit_price' => 100, 'sale_price' => 150, 'quantity' => 2]), 'items')
        ->create();

    app(WalletService::class)->createPendingTransaction($order);

    $this->actingAs($admin)
        ->patch(route('admin.reseller-orders.update-status', $order), ['status' => 'completed'])
        ->assertRedirect();

    expect(Transaction::where('reseller_order_id', $order->id)->value('status'))->toBe('pending');

    $this->actingAs($admin)
        ->patch(route('admin.reseller-orders.update-payment-status', $order), ['payment_status' => 'paid'])
        ->assertRedirect();

    $wallet = Wallet::where('user_id', $reseller->id)->first();

    expect(Transaction::where('reseller_order_id', $order->id)->value('status'))->toBe('completed')
        ->and($wallet)->not->toBeNull()
        ->and((float) $wallet->balance)->toBe(100.0)
        ->and((float) $wallet->credit)->toBe(100.0);
});

it('cancels the pending transaction without touching the wallet on cancellation', function () {
    $admin = adminTestUser();
    $reseller = resellerTestUser();

    $order = ResellerOrder::factory()
        ->for($reseller)
        ->has(ResellerOrderItem::factory(['unit_price' => 100, 'sale_price' => 150, 'quantity' => 1]), 'items')
        ->create();

    app(WalletService::class)->createPendingTransaction($order);

    $this->actingAs($admin)
        ->patch(route('admin.reseller-orders.update-status', $order), ['status' => 'cancelled'])
        ->assertRedirect();

    expect(Transaction::where('reseller_order_id', $order->id)->value('status'))->toBe('cancelled')
        ->and(Wallet::where('user_id', $reseller->id)->exists())->toBeFalse();
});

it('reverses a completed wallet credit when cancelling after completion', function () {
    $admin = adminTestUser();
    $reseller = resellerTestUser();

    $order = ResellerOrder::factory()
        ->completedPaid()
        ->for($reseller)
        ->has(ResellerOrderItem::factory(['unit_price' => 100, 'sale_price' => 150, 'quantity' => 2]), 'items')
        ->create();

    app(WalletService::class)->createPendingTransaction($order);
    app(WalletService::class)->completeTransaction($order);

    expect((float) Wallet::where('user_id', $reseller->id)->value('balance'))->toBe(100.0);

    $this->actingAs($admin)
        ->patch(route('admin.reseller-orders.update-status', $order), ['status' => 'cancelled'])
        ->assertRedirect();

    $wallet = Wallet::where('user_id', $reseller->id)->first();

    expect(Transaction::where('reseller_order_id', $order->id)->value('status'))->toBe('cancelled')
        ->and((float) $wallet->balance)->toBe(0.0)
        ->and((float) $wallet->credit)->toBe(0.0);
});

it('updates the order details from the edit form', function () {
    $admin = adminTestUser();
    $reseller = resellerTestUser();

    $order = ResellerOrder::factory()
        ->for($reseller)
        ->has(ResellerOrderItem::factory(), 'items')
        ->create();

    $this->actingAs($admin)
        ->put(route('admin.reseller-orders.update', $order), [
            'status' => 'processing',
            'payment_status' => 'paid',
            'total_amount' => 999,
            'delivery_charge' => 120,
            'shipping_address' => 'Updated address',
        ])
        ->assertRedirect(route('admin.reseller-orders.index'))
        ->assertSessionHas('success');

    $order->refresh();

    expect($order->status)->toBe('processing')
        ->and($order->payment_status)->toBe('paid')
        ->and((float) $order->total_amount)->toBe(999.0)
        ->and((float) $order->delivery_charge)->toBe(120.0)
        ->and($order->shipping_address)->toBe('Updated address');
});

it('requires authentication for the product search endpoint', function () {
    $this->get(route('reseller-orders.search-products', ['q' => 'Test']))
        ->assertRedirect(route('login'));
});

it('returns matching active products with purchase price and variants', function () {
    $reseller = resellerTestUser();

    $product = Product::create([
        'title' => 'Cotton Panjabi',
        'slug' => 'cotton-panjabi-'.uniqid(),
        'unit_price' => 800,
        'sale_price' => 1200,
        'quantity' => 10,
        'status' => 'active',
    ]);
    ProductVariant::create(['product_id' => $product->id, 'size' => 'L', 'color' => 'White', 'quantity' => 5]);

    Product::create([
        'title' => 'Cotton Panjabi Old',
        'slug' => 'cotton-panjabi-old-'.uniqid(),
        'unit_price' => 700,
        'sale_price' => 1000,
        'quantity' => 5,
        'status' => 'inactive',
    ]);

    $response = $this->actingAs($reseller)
        ->getJson(route('reseller-orders.search-products', ['q' => 'Panjabi']))
        ->assertOk();

    expect($response->json())->toHaveCount(1)
        ->and($response->json('0.title'))->toBe('Cotton Panjabi')
        ->and((float) $response->json('0.purchase_price'))->toBe(800.0)
        ->and((float) $response->json('0.sale_price'))->toBe(1200.0)
        ->and($response->json('0.variants'))->toHaveCount(1)
        ->and($response->json('0.variants.0.size'))->toBe('L')
        ->and($response->json('0.variants.0.color'))->toBe('White')
        ->and($response->json('0.variants.0.quantity'))->toBe(5);
});

it('returns an empty array for short search queries', function () {
    $reseller = resellerTestUser();

    Product::create([
        'title' => 'Test Product',
        'slug' => 'test-product-'.uniqid(),
        'unit_price' => 100,
        'sale_price' => 150,
        'quantity' => 50,
        'status' => 'active',
    ]);

    $this->actingAs($reseller)
        ->getJson(route('reseller-orders.search-products'))
        ->assertOk()
        ->assertJson([]);

    $this->actingAs($reseller)
        ->getJson(route('reseller-orders.search-products', ['q' => 'T']))
        ->assertOk()
        ->assertJson([]);
});

it('matches product titles only, not skus', function () {
    $reseller = resellerTestUser();

    Product::create([
        'title' => 'Winter Jacket',
        'slug' => 'winter-jacket-'.uniqid(),
        'sku' => 'XYZ-999',
        'unit_price' => 500,
        'sale_price' => 900,
        'quantity' => 20,
        'status' => 'active',
    ]);

    $this->actingAs($reseller)
        ->getJson(route('reseller-orders.search-products', ['q' => 'XYZ']))
        ->assertOk()
        ->assertJson([]);
});
