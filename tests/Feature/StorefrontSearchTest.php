<?php

use App\Models\Product;
use App\Models\ProductVariant;
use Inertia\Testing\AssertableInertia as Assert;

function createActiveProduct(array $attributes = []): Product
{
    return Product::create(array_merge([
        'title' => 'Example Product',
        'description' => 'An example product description.',
        'unit_price' => 1000,
        'sale_price' => 800,
        'status' => 'active',
    ], $attributes));
}

it('finds a product by its own sku in the search overlay', function () {
    createActiveProduct(['title' => 'Lace Nightgown', 'sku' => 'SKU-12345']);

    $this->get('/api/search?q=SKU-12345')
        ->assertSuccessful()
        ->assertJsonCount(1)
        ->assertJsonFragment(['sku' => 'SKU-12345']);
});

it('finds a product by a variant sku in the search overlay', function () {
    $product = createActiveProduct(['title' => 'Lace Nightgown']);

    ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-99999',
        'unit_price' => 1000,
        'sale_price' => 800,
        'quantity' => 5,
    ]);

    $this->get('/api/search?q=VAR-99999')
        ->assertSuccessful()
        ->assertJsonCount(1)
        ->assertJsonFragment(['id' => $product->id])
        ->assertJsonFragment(['sku' => 'VAR-99999']);
});

it('does not return inactive products in the search overlay', function () {
    createActiveProduct(['title' => 'Active Product', 'sku' => 'SKU-ACTIVE']);
    createActiveProduct(['title' => 'Draft Product', 'sku' => 'SKU-DRAFT', 'status' => 'draft']);

    $this->get('/api/search?q=SKU-')
        ->assertSuccessful()
        ->assertJsonCount(1)
        ->assertJsonFragment(['sku' => 'SKU-ACTIVE'])
        ->assertJsonMissing(['sku' => 'SKU-DRAFT']);
});

it('returns an empty result when no product matches the sku', function () {
    createActiveProduct(['title' => 'Lace Nightgown', 'sku' => 'SKU-12345']);

    $this->get('/api/search?q=NOPE')
        ->assertSuccessful()
        ->assertJsonCount(0);
});

it('finds a product by a variant sku on the products index page', function () {
    $product = createActiveProduct(['title' => 'Lace Nightgown']);

    ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-55555',
        'unit_price' => 1000,
        'sale_price' => 800,
        'quantity' => 5,
    ]);

    $this->get('/products?q=VAR-55555')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StoreFront/Products/Index')
            ->has('products.data', 1));
});
