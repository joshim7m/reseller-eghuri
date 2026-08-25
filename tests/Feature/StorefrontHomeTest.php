<?php

use App\Models\Category;
use App\Models\Product;
use Inertia\Testing\AssertableInertia as Assert;

function createHomeProduct(string $slug): Product
{
    $category = Category::factory()->create();

    return Product::create([
        'category_id' => $category->id,
        'title' => 'Product '.$slug,
        'slug' => $slug,
        'unit_price' => 100,
        'sale_price' => 100,
        'status' => 'active',
    ]);
}

it('limits new arrivals and top selling products to 20 each', function () {
    foreach (range(1, 25) as $i) {
        createHomeProduct('home-product-'.$i);
    }

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->has('newArrivals', 20)
            ->has('products', 20));
});

it('shows all categories (parents and children) on the home page', function () {
    $parent = Category::factory()->create(['name' => 'Lingerie & Nightwear']);
    Category::factory()->child()->create([
        'name' => 'Bras',
        'parent_id' => $parent->id,
    ]);
    Category::factory()->create(['name' => 'Fashion & Clothing']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->has('categories', 3)
            ->where('categories.0.name', 'Lingerie & Nightwear')
            ->where('categories.1.name', 'Bras')
            ->where('categories.2.name', 'Fashion & Clothing'));
});

it('sums child product counts into the parent category count', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->child()->create(['parent_id' => $parent->id]);

    $parentProduct = Product::create([
        'category_id' => $parent->id,
        'title' => 'Parent Product',
        'slug' => 'parent-product',
        'unit_price' => 100,
        'sale_price' => 100,
        'status' => 'active',
    ]);

    $childProduct = Product::create([
        'category_id' => $child->id,
        'title' => 'Child Product',
        'slug' => 'child-product',
        'unit_price' => 100,
        'sale_price' => 100,
        'status' => 'active',
    ]);

    DB::table('category_product')->insert([
        ['category_id' => $child->id, 'product_id' => $childProduct->id],
        ['category_id' => $parent->id, 'product_id' => $parentProduct->id],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->has('categories', 2)
            ->where('categories.0.id', $parent->id)
            ->where('categories.0.products_count', 2));
});
