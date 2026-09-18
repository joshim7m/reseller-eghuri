<?php

use App\Models\Category;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function productIndexAdminUser(): User
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

function createIndexProduct(array $attributes = []): Product
{
    return Product::create(array_merge([
        'category_id' => Category::factory()->create()->id,
        'title' => fake()->unique()->words(3, true),
        'slug' => null,
        'unit_price' => 100,
        'sale_price' => 100,
        'status' => 'active',
    ], $attributes));
}

it('renders the product index page', function () {
    $admin = productIndexAdminUser();

    createIndexProduct();

    $this->actingAs($admin)
        ->get(route('admin.products.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Products/Index')
            ->has('products.data', 1));
});

it('filters products by status', function () {
    $admin = productIndexAdminUser();

    $active = createIndexProduct(['title' => 'Active Alpha', 'status' => 'active']);
    $draft = createIndexProduct(['title' => 'Draft Beta', 'status' => 'draft']);

    $this->actingAs($admin)
        ->get(route('admin.products.index', ['status' => 'active']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.id', $active->id));

    $this->actingAs($admin)
        ->get(route('admin.products.index', ['status' => 'draft']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.id', $draft->id));
});

it('filters products by featured flag', function () {
    $admin = productIndexAdminUser();

    $featured = createIndexProduct(['title' => 'Featured Gamma', 'featured' => true]);
    $regular = createIndexProduct(['title' => 'Regular Delta', 'featured' => false]);

    $this->actingAs($admin)
        ->get(route('admin.products.index', ['featured' => 'true']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.id', $featured->id));

    $this->actingAs($admin)
        ->get(route('admin.products.index', ['featured' => 'false']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.id', $regular->id));
});

it('filters products by a top category including its descendants', function () {
    $admin = productIndexAdminUser();

    $parent = Category::factory()->create(['name' => 'Parent Category', 'parent_id' => null]);
    $child = Category::factory()->create(['name' => 'Child Category', 'parent_id' => $parent->id]);
    $grandchild = Category::factory()->create(['name' => 'Grandchild Category', 'parent_id' => $child->id]);
    $other = Category::factory()->create(['name' => 'Other Category', 'parent_id' => null]);

    $parentProduct = createIndexProduct(['title' => 'Product Epsilon', 'category_id' => $parent->id, 'created_at' => now()]);
    $grandchildProduct = createIndexProduct(['title' => 'Product Zeta', 'category_id' => $grandchild->id, 'created_at' => now()->subSecond()]);
    $otherProduct = createIndexProduct(['title' => 'Product Eta', 'category_id' => $other->id, 'created_at' => now()->subSeconds(2)]);

    $this->actingAs($admin)
        ->get(route('admin.products.index', ['category_id' => $parent->id]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 2)
            ->where('products.data.0.id', $parentProduct->id)
            ->where('products.data.1.id', $grandchildProduct->id))
        ->assertDontSee($otherProduct->title);
});

it('filters products that belong to no category', function () {
    $admin = productIndexAdminUser();

    $orphan = createIndexProduct(['title' => 'Orphan Product', 'category_id' => null]);
    $pivotOnly = createIndexProduct(['title' => 'Pivot Only Product', 'category_id' => null]);
    $categorized = createIndexProduct(['title' => 'Categorized Product', 'category_id' => Category::factory()->create()->id]);

    $pivotOnly->categories()->attach(Category::factory()->create()->id);
    $categorized->categories()->attach(Category::factory()->create()->id);

    $this->actingAs($admin)
        ->get(route('admin.products.index', ['category_id' => 'none']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.id', $orphan->id));
});

it('searches products by title and sku', function () {
    $admin = productIndexAdminUser();

    $titleMatch = createIndexProduct(['title' => 'Wireless Headphones', 'sku' => null]);
    $skuMatch = createIndexProduct(['title' => 'Keyboard', 'sku' => 'HD-2026']);
    $noMatch = createIndexProduct(['title' => 'Mouse', 'sku' => 'MSE-01']);

    $this->actingAs($admin)
        ->get(route('admin.products.index', ['search' => 'headphones']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.id', $titleMatch->id));

    $this->actingAs($admin)
        ->get(route('admin.products.index', ['search' => 'HD-2026']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.id', $skuMatch->id));

    $this->actingAs($admin)
        ->get(route('admin.products.index', ['search' => 'nonexistent-thing']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->has('products.data', 0));

    expect($titleMatch->id)->not->toBe($noMatch->id);
});

it('searches products by variant sku', function () {
    $admin = productIndexAdminUser();

    $product = createIndexProduct(['title' => 'Sofa Set']);

    ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'SOFA-RED-L',
        'quantity' => 5,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.products.index', ['search' => 'SOFA-RED-L']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.id', $product->id));
});

it('updates a product status', function ($from, $to) {
    $admin = productIndexAdminUser();

    $product = createIndexProduct(['title' => 'Toggle Me', 'status' => $from]);

    $this->actingAs($admin)
        ->patch(route('admin.products.update-status', $product->id), ['status' => $to])
        ->assertRedirect();

    expect($product->refresh()->status)->toBe($to);
})->with([
    'active to inactive' => ['active', 'inactive'],
    'inactive to active' => ['inactive', 'active'],
    'draft to active' => ['draft', 'active'],
]);

it('rejects an invalid product status', function () {
    $admin = productIndexAdminUser();

    $product = createIndexProduct(['title' => 'Keep Me', 'status' => 'active']);

    $this->actingAs($admin)
        ->patch(route('admin.products.update-status', $product->id), ['status' => 'archived'])
        ->assertSessionHasErrors('status');

    expect($product->refresh()->status)->toBe('active');
});

it('updates the featured flag', function ($from, $to) {
    $admin = productIndexAdminUser();

    $product = createIndexProduct(['title' => 'Feature Me', 'featured' => $from]);

    $this->actingAs($admin)
        ->patch(route('admin.products.update-featured', $product->id), ['featured' => $to])
        ->assertRedirect();

    expect($product->refresh()->featured)->toBe($to);
})->with([
    'feature to unfeature' => [true, false],
    'unfeature to feature' => [false, true],
]);

it('forbids non-admin users from toggling product status', function () {
    $module = Module::create(['name' => 'Catalog']);
    $customerRole = Role::create(['name' => 'Customer', 'slug' => 'customer']);
    $customerPermission = Permission::create([
        'name' => 'View Catalog',
        'module_id' => $module->id,
        'slug' => 'view-catalog',
    ]);
    $customerRole->permissions()->attach($customerPermission->id);

    $user = User::factory()->create([
        'role_id' => $customerRole->id,
        'user_type' => 'customer',
        'status' => 1,
    ]);

    $product = createIndexProduct(['title' => 'Locked', 'status' => 'inactive']);

    $this->actingAs($user)
        ->patch(route('admin.products.update-status', $product->id), ['status' => 'active'])
        ->assertForbidden();
});
