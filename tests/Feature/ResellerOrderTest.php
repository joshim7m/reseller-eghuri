<?php

use App\Models\Module;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ResellerOrder;
use App\Models\ResellerOrderItem;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Database\Seeders\ResellerOrderSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

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

it('shows order details with the variant image and without the wallet transaction', function () {
    $reseller = resellerTestUser();
    $product = Product::create([
        'title' => 'Variant Product',
        'slug' => 'variant-product-'.uniqid(),
        'unit_price' => 100,
        'sale_price' => 150,
        'quantity' => 50,
        'status' => 'active',
    ]);
    $image = ProductImage::create([
        'product_id' => $product->id,
        'image_path' => '/storage/images/variant.jpg',
        'sort_order' => 1,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'product_image_id' => $image->id,
        'sku' => 'VAR-001',
        'unit_price' => 100,
        'sale_price' => 150,
        'quantity' => 10,
        'options' => [['name' => 'Color', 'value' => 'Red']],
    ]);

    $order = ResellerOrder::factory()
        ->for($reseller)
        ->has(ResellerOrderItem::factory([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'unit_price' => 100,
            'sale_price' => 150,
        ]), 'items')
        ->create();

    app(WalletService::class)->createPendingTransaction($order);

    $this->actingAs($reseller)
        ->get(route('reseller-orders.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StoreFront/ResellerOrders/Show')
            ->has('resellerOrder.items', 1)
            ->where('resellerOrder.items.0.variant.image.image_path', '/storage/images/variant.jpg')
            ->where('resellerOrder.items.0.variant.image.image_url', 'http://localhost/storage/images/variant.jpg')
            ->missing('resellerOrder.transaction')
        );
});

it('forbids a reseller from viewing another reseller order', function () {
    $owner = resellerTestUser();
    $other = User::create([
        'name' => 'Other Reseller',
        'email' => 'other@test.dev',
        'password' => Hash::make('password'),
        'role_id' => $owner->role_id,
        'user_type' => 'reseller',
        'status' => true,
        'email_verified_at' => now(),
    ]);

    $order = ResellerOrder::factory()
        ->for($owner)
        ->has(ResellerOrderItem::factory(), 'items')
        ->create();

    $this->actingAs($other)
        ->get(route('reseller-orders.show', $order))
        ->assertForbidden();
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

it('accepts sale prices below the unit price', function () {
    $reseller = resellerTestUser();
    $payload = resellerOrderPayload();
    $payload['items'][0]['sale_price'] = 99;

    $this->actingAs($reseller)
        ->post(route('reseller-orders.store'), $payload)
        ->assertRedirect(route('reseller-orders.index'))
        ->assertSessionHas('success');

    $order = ResellerOrder::first();

    expect($order)->not->toBeNull()
        ->and((float) $order->items()->first()->sale_price)->toBe(99.0)
        ->and((float) $order->items()->first()->unit_price)->toBe(100.0);
});

it('saves delivery areas as json from the admin site config', function () {
    $admin = adminTestUser();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), [
            'delivery_areas' => [
                ['name' => 'Inside Dhaka', 'charge' => 60],
                ['name' => 'Outside Dhaka', 'charge' => 120],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $areas = json_decode(Setting::get('delivery_areas'), true);

    expect($areas)->toBe([
        ['name' => 'Inside Dhaka', 'charge' => 60],
        ['name' => 'Outside Dhaka', 'charge' => 120],
    ]);
});

it('accepts a delivery charge from the configured delivery areas', function () {
    $reseller = resellerTestUser();

    Setting::set('delivery_areas', json_encode([
        ['name' => 'Inside Dhaka', 'charge' => 60],
        ['name' => 'Outside Dhaka', 'charge' => 150],
    ]));

    $payload = resellerOrderPayload();
    $payload['delivery_charge'] = 60;

    $this->actingAs($reseller)
        ->post(route('reseller-orders.store'), $payload)
        ->assertRedirect(route('reseller-orders.index'))
        ->assertSessionHas('success');

    expect((float) ResellerOrder::first()->delivery_charge)->toBe(60.0);
});

it('rejects a delivery charge that is not configured', function () {
    $reseller = resellerTestUser();

    Setting::set('delivery_areas', json_encode([
        ['name' => 'Inside Dhaka', 'charge' => 60],
    ]));

    $this->actingAs($reseller)
        ->post(route('reseller-orders.store'), resellerOrderPayload())
        ->assertSessionHasErrors('delivery_charge');

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

it('completes the wallet transaction when the order status is set to completed', function () {
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

    $order->refresh();
    $wallet = Wallet::where('user_id', $reseller->id)->first();

    expect($order->payment_status)->toBe('paid')
        ->and(Transaction::where('reseller_order_id', $order->id)->value('status'))->toBe('completed')
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
    ProductVariant::create(['product_id' => $product->id, 'unit_price' => 750, 'sale_price' => 1190, 'options' => [['name' => 'size', 'value' => 'L'], ['name' => 'color', 'value' => 'White']], 'quantity' => 5]);

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
        ->and($response->json('0.variants.0.options'))->toBe([
            ['name' => 'size', 'value' => 'L'],
            ['name' => 'color', 'value' => 'White'],
        ])
        ->and($response->json('0.variants.0.quantity'))->toBe(5)
        ->and($response->json('0.variants.0.unit_price'))->toBe(750)
        ->and($response->json('0.variants.0.sale_price'))->toBe(1190);
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

it('matches products by sku at the product level', function () {
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

    $response = $this->actingAs($reseller)
        ->getJson(route('reseller-orders.search-products', ['q' => 'XYZ']))
        ->assertOk()
        ->assertJsonCount(1);

    expect($response->json('0.title'))->toBe('Winter Jacket')
        ->and($response->json('0.sku'))->toBe('XYZ-999');
});

it('matches products by variant sku', function () {
    $reseller = resellerTestUser();

    $product = Product::create([
        'title' => 'Denim Shirt',
        'slug' => 'denim-shirt-'.uniqid(),
        'unit_price' => 400,
        'sale_price' => 700,
        'quantity' => 10,
        'status' => 'active',
    ]);
    ProductVariant::create(['product_id' => $product->id, 'sku' => 'SHRT-111', 'options' => [['name' => 'size', 'value' => 'M']], 'quantity' => 4]);

    $response = $this->actingAs($reseller)
        ->getJson(route('reseller-orders.search-products', ['q' => 'SHRT']))
        ->assertOk()
        ->assertJsonCount(1);

    expect($response->json('0.title'))->toBe('Denim Shirt')
        ->and($response->json('0.variants.0.sku'))->toBe('SHRT-111');
});

it('renders the reseller sales report scoped to the date range', function () {
    $admin = adminTestUser();
    $reseller = resellerTestUser();

    ResellerOrder::factory()
        ->for($reseller)
        ->has(ResellerOrderItem::factory(['unit_price' => 100, 'sale_price' => 150, 'quantity' => 2]), 'items')
        ->create(['created_at' => now()->subDays(2)]);

    ResellerOrder::factory()
        ->for($reseller)
        ->has(ResellerOrderItem::factory(), 'items')
        ->create(['created_at' => now()->subDays(60)]);

    $this->actingAs($admin)
        ->get(route('admin.reseller-orders.report', [
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/ResellerOrders/Report')
            ->has('orders', 1)
            ->where('orders.0.status', 'pending')
            ->where('orders.0.quantity', 2)
            ->where('orders.0.items.0.quantity', 2)
        );
});

it('downloads an xlsx courier report with text-formatted phones and skipped statuses', function () {
    $admin = adminTestUser();
    $reseller = resellerTestUser();

    Setting::set('company_name', 'Acme Fashion');
    Setting::set('company_mobile', '01715009988');

    ResellerOrder::factory()
        ->for($reseller)
        ->has(ResellerOrderItem::factory(['product_name' => 'Padded Bra', 'unit_price' => 100, 'sale_price' => 150, 'quantity' => 2, 'options' => [['name' => 'color', 'value' => 'White']]]), 'items')
        ->create([
            'order_number' => 'RSL-000001',
            'customer_name' => 'Nasrin Akter',
            'mobile' => '01945090085',
            'shipping_address' => '7/d, Sector-7 Uttara Dhaka',
            'delivery_charge' => 50,
            'created_at' => now()->subDays(2),
        ]);

    ResellerOrder::factory()
        ->cancelled()
        ->for($reseller)
        ->has(ResellerOrderItem::factory(), 'items')
        ->create(['created_at' => now()->subDays(1)]);

    ResellerOrder::factory()
        ->for($reseller)
        ->has(ResellerOrderItem::factory(), 'items')
        ->create(['order_number' => 'RSL-000002', 'created_at' => now()->subDays(60)]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reseller-orders.export-report', [
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertDownload('courier-report-'.now()->format('Y-m-d').'.xlsx');

    $file = tempnam(sys_get_temp_dir(), 'reseller_report');

    file_put_contents($file, $response->streamedContent());

    try {
        $sheet = (new Xlsx)->load($file)->getActiveSheet();

        expect($sheet->getCell('A1')->getValue())->toBe('Invoice')
            ->and($sheet->getCell('I1')->getValue())->toBe('Contact Number')
            ->and($sheet->getCell('A2')->getValue())->toBe('Padded Bra White')
            ->and($sheet->getCell('B2')->getValue())->toBe('Nasrin Akter')
            ->and($sheet->getCell('C2')->getValue())->toBe('7/d, Sector-7 Uttara Dhaka')
            ->and($sheet->getCell('D2')->getValue())->toBe('01945090085')
            ->and($sheet->getCell('D2')->getDataType())->toBe(DataType::TYPE_STRING)
            ->and($sheet->getCell('E2')->getValue())->toBe(350.0)
            ->and($sheet->getCell('F2')->getValue())->toBe('null')
            ->and($sheet->getCell('G2')->getValue())->toBeNull()
            ->and($sheet->getCell('H2')->getValue())->toBe('Acme Fashion')
            ->and($sheet->getCell('I2')->getValue())->toBe('01715009988')
            ->and($sheet->getHighestRow())->toBe(2);
    } finally {
        @unlink($file);
    }
});

it('exports only the selected order ids', function () {
    $admin = adminTestUser();
    $reseller = resellerTestUser();

    $keep = ResellerOrder::factory()
        ->for($reseller)
        ->has(ResellerOrderItem::factory(), 'items')
        ->create(['created_at' => now()->subDays(2)]);

    ResellerOrder::factory()
        ->for($reseller)
        ->has(ResellerOrderItem::factory(), 'items')
        ->create(['created_at' => now()->subDays(1)]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reseller-orders.export-report', [
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
            'ids' => [$keep->id],
        ]))
        ->assertOk();

    $file = tempnam(sys_get_temp_dir(), 'reseller_report');

    file_put_contents($file, $response->streamedContent());

    try {
        $sheet = (new Xlsx)->load($file)->getActiveSheet();

        expect($sheet->getHighestRow())->toBe(2)
            ->and($sheet->getCell('B2')->getValue())->toBe($keep->customer_name);
    } finally {
        @unlink($file);
    }
});

it('seeds orders with the cumulative date distribution', function () {
    $role = Role::firstOrCreate(['slug' => 'reseller'], ['name' => 'Reseller']);
    User::create([
        'name' => 'Seeder Reseller',
        'email' => 'seeder@test.dev',
        'password' => Hash::make('password'),
        'role_id' => $role->id,
        'user_type' => 'reseller',
        'status' => true,
        'email_verified_at' => now(),
    ]);

    $this->seed(ResellerOrderSeeder::class);

    $now = now();

    expect(ResellerOrder::count())->toBe(22);
    expect(ResellerOrder::where('created_at', '>=', $now->copy()->startOfDay())->count())->toBe(5);
    expect(ResellerOrder::where('created_at', '>=', $now->copy()->startOfWeek(Carbon::SUNDAY))->count())->toBe(12);
    expect(ResellerOrder::where('created_at', '>=', $now->copy()->startOfMonth())->count())->toBe(22);
});
