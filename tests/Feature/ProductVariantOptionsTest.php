<?php

use App\Models\Module;
use App\Models\OrderItem;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ResellerOrderItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function variantOptionsAdminUser(): User
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

function variantOptionsCustomerUser(): User
{
    $role = Role::firstOrCreate(['slug' => 'customer'], ['name' => 'Customer']);

    return User::create([
        'name' => 'Options Customer',
        'email' => 'options-customer@test.dev',
        'password' => Hash::make('password'),
        'role_id' => $role->id,
        'user_type' => 'customer',
        'status' => true,
        'email_verified_at' => now(),
    ]);
}

it('persists user-defined variant options on product store', function () {
    $admin = variantOptionsAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.products.store'), [
            'title' => 'Cotton Saree',
            'description' => 'Beautiful hand-woven cotton.',
            'unit_price' => 800,
            'sale_price' => 900,
            'status' => 'active',
            'variants' => [
                [
                    'options' => [
                        ['name' => 'weight', 'value' => '250g'],
                        ['name' => 'material', 'value' => 'Cotton'],
                        ['name' => 'color', 'value' => 'Red'],
                    ],
                    'quantity' => 5,
                ],
                [
                    'options' => [
                        ['name' => 'weight', 'value' => '500g'],
                        ['name' => 'material', 'value' => 'Silk'],
                    ],
                    'quantity' => 3,
                ],
            ],
        ])
        ->assertRedirect(route('admin.products.index'));

    $product = Product::first();

    expect($product->variants)->toHaveCount(2);

    $first = $product->variants->first();
    expect($first->options)->toBe([
        ['name' => 'weight', 'value' => '250g'],
        ['name' => 'material', 'value' => 'Cotton'],
        ['name' => 'color', 'value' => 'Red'],
    ]);

    $second = $product->variants->last();
    expect($second->options)->toBe([
        ['name' => 'weight', 'value' => '500g'],
        ['name' => 'material', 'value' => 'Silk'],
    ]);
});

it('updates variant options on product update', function () {
    $admin = variantOptionsAdminUser();
    $product = Product::create([
        'title' => 'Original',
        'slug' => 'original-'.uniqid(),
        'description' => 'Description.',
        'unit_price' => 100,
        'sale_price' => 120,
        'status' => 'active',
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'options' => [['name' => 'size', 'value' => 'L'], ['name' => 'color', 'value' => 'Red']],
        'quantity' => 3,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), [
            'title' => 'Updated',
            'description' => 'Updated description.',
            'unit_price' => 150,
            'sale_price' => 180,
            'status' => 'active',
            'variants' => [
                [
                    'id' => $variant->id,
                    'options' => [
                        ['name' => 'box', 'value' => '1kg box'],
                        ['name' => 'color', 'value' => 'Green'],
                    ],
                    'quantity' => 9,
                ],
            ],
        ])
        ->assertRedirect(route('admin.products.index'));

    $variant->refresh();

    expect($variant->options)->toBe([
        ['name' => 'box', 'value' => '1kg box'],
        ['name' => 'color', 'value' => 'Green'],
    ])->and($variant->quantity)->toBe(9);
});

it('persists options on reseller order items', function () {
    $role = Role::firstOrCreate(['slug' => 'reseller'], ['name' => 'Reseller']);
    $reseller = User::create([
        'name' => 'Options Reseller',
        'email' => 'options-reseller@test.dev',
        'password' => Hash::make('password'),
        'role_id' => $role->id,
        'user_type' => 'reseller',
        'status' => true,
        'email_verified_at' => now(),
    ]);
    $product = Product::create([
        'title' => 'Perfume',
        'slug' => 'perfume-'.uniqid(),
        'unit_price' => 300,
        'sale_price' => 450,
        'quantity' => 10,
        'status' => 'active',
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'options' => [['name' => 'size', 'value' => '50ml'], ['name' => 'shape', 'value' => 'Bottle']],
        'quantity' => 4,
    ]);

    $this->actingAs($reseller)
        ->post(route('reseller-orders.store'), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => 'Perfume',
                    'quantity' => 1,
                    'unit_price' => 300,
                    'sale_price' => 450,
                    'variant_id' => $variant->id,
                    'options' => [
                        ['name' => 'size', 'value' => '50ml'],
                        ['name' => 'shape', 'value' => 'Bottle'],
                    ],
                ],
            ],
            'customer_name' => 'Jane Doe',
            'mobile' => '01712345678',
            'delivery_charge' => 50,
            'shipping_address' => 'Gulshan, Dhaka',
        ])
        ->assertRedirect(route('reseller-orders.index'));

    $item = ResellerOrderItem::first();

    expect($item->options)->toBe([
        ['name' => 'size', 'value' => '50ml'],
        ['name' => 'shape', 'value' => 'Bottle'],
    ])->and($item->product_variant_id)->toBe($variant->id);
});

it('persists options on checkout order items', function () {
    $customer = variantOptionsCustomerUser();
    $product = Product::create([
        'title' => 'Tea',
        'slug' => 'tea-'.uniqid(),
        'unit_price' => 200,
        'sale_price' => 250,
        'quantity' => 10,
        'status' => 'active',
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'options' => [['name' => 'weight', 'value' => '100g'], ['name' => 'type', 'value' => 'Green Tea']],
        'quantity' => 6,
    ]);

    $this->actingAs($customer)
        ->post(route('checkout.store'), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'options' => [
                        ['name' => 'weight', 'value' => '100g'],
                        ['name' => 'type', 'value' => 'Green Tea'],
                    ],
                    'item_name' => 'Tea',
                    'quantity' => 1,
                    'price' => 250,
                ],
            ],
            'payment_method' => 'cod',
            'shipping_address' => 'Dhanmondi, Dhaka 1205',
            'name' => 'Jane Doe',
            'mobile' => '01712345678',
            'delivery_charge' => 50,
        ])
        ->assertRedirect(route('checkout.thank-you', OrderItem::first()->order_id));

    $item = OrderItem::first();

    expect($item->options)->toBe([
        ['name' => 'weight', 'value' => '100g'],
        ['name' => 'type', 'value' => 'Green Tea'],
    ])->and($item->product_variant_id)->toBe($variant->id);
});

it('exposes generic dimensions as filter options on the storefront', function () {
    $first = Product::create([
        'title' => 'Shirt A',
        'slug' => 'shirt-a-'.uniqid(),
        'unit_price' => 500,
        'sale_price' => 600,
        'quantity' => 5,
        'status' => 'active',
    ]);
    ProductVariant::create([
        'product_id' => $first->id,
        'options' => [['name' => 'size', 'value' => 'M'], ['name' => 'color', 'value' => 'Red']],
        'quantity' => 3,
    ]);
    ProductVariant::create([
        'product_id' => $first->id,
        'options' => [['name' => 'size', 'value' => 'L'], ['name' => 'color', 'value' => 'Blue']],
        'quantity' => 2,
    ]);

    $this->get(route('products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StoreFront/Products/Index')
            ->where('filters.dimensions', [
                ['name' => 'size', 'values' => ['L', 'M']],
                ['name' => 'color', 'values' => ['Blue', 'Red']],
            ])
        );
});

it('filters storefront products by generic dimension options', function () {
    $matching = Product::create([
        'title' => 'Matching Shirt',
        'slug' => 'matching-shirt-'.uniqid(),
        'unit_price' => 500,
        'sale_price' => 600,
        'quantity' => 5,
        'status' => 'active',
    ]);
    ProductVariant::create([
        'product_id' => $matching->id,
        'options' => [['name' => 'size', 'value' => 'M'], ['name' => 'color', 'value' => 'Red']],
        'quantity' => 3,
    ]);

    $other = Product::create([
        'title' => 'Other Trousers',
        'slug' => 'other-trousers-'.uniqid(),
        'unit_price' => 700,
        'sale_price' => 800,
        'quantity' => 5,
        'status' => 'active',
    ]);
    ProductVariant::create([
        'product_id' => $other->id,
        'options' => [['name' => 'size', 'value' => 'L'], ['name' => 'color', 'value' => 'Blue']],
        'quantity' => 4,
    ]);

    $this->get(route('products.index', ['options' => ['color' => ['Red']]]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StoreFront/Products/Index')
            ->has('products.data', 1)
            ->where('products.data.0.id', $matching->id)
        );
});

it('collapses inconsistent dimension names into one unique filter group', function () {
    $product = Product::create([
        'title' => 'Nighty',
        'slug' => 'nighty-'.uniqid(),
        'unit_price' => 500,
        'sale_price' => 600,
        'quantity' => 5,
        'status' => 'active',
    ]);
    ProductVariant::create([
        'product_id' => $product->id,
        'options' => [['name' => 'Ccolor', 'value' => 'Red'], ['name' => 'Size', 'value' => 'M']],
        'quantity' => 3,
    ]);
    ProductVariant::create([
        'product_id' => $product->id,
        'options' => [['name' => 'color', 'value' => 'Pink'], ['name' => 'size', 'value' => 'L']],
        'quantity' => 2,
    ]);

    $this->get(route('products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StoreFront/Products/Index')
            ->where('filters.dimensions', [
                ['name' => 'color', 'values' => ['Pink', 'Red']],
                ['name' => 'size', 'values' => ['L', 'M']],
            ])
        );
});

it('filters with normalized dimension names despite messy stored names', function () {
    $matching = Product::create([
        'title' => 'Matching Nighty',
        'slug' => 'matching-nighty-'.uniqid(),
        'unit_price' => 500,
        'sale_price' => 600,
        'quantity' => 5,
        'status' => 'active',
    ]);
    ProductVariant::create([
        'product_id' => $matching->id,
        'options' => [['name' => 'Ccolor', 'value' => 'Red'], ['name' => 'Size', 'value' => 'M']],
        'quantity' => 2,
    ]);

    $other = Product::create([
        'title' => 'Other Lingerie',
        'slug' => 'other-lingerie-'.uniqid(),
        'unit_price' => 700,
        'sale_price' => 800,
        'quantity' => 5,
        'status' => 'active',
    ]);
    ProductVariant::create([
        'product_id' => $other->id,
        'options' => [['name' => 'color', 'value' => 'Blue'], ['name' => 'size', 'value' => 'L']],
        'quantity' => 3,
    ]);

    $this->get(route('products.index', ['options' => ['size' => ['M']]]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StoreFront/Products/Index')
            ->has('products.data', 1)
            ->where('products.data.0.id', $matching->id)
        );
});
