<?php

use App\Models\Category;
use App\Models\Module;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SeoSettingsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function seoProduct(string $slug, array $overrides = []): Product
{
    $category = Category::factory()->create(['name' => 'Nightwear']);

    return Product::create(array_merge([
        'category_id' => $category->id,
        'title' => 'Product '.$slug,
        'slug' => $slug,
        'description' => 'Beautiful nightwear imported from China.',
        'unit_price' => 500,
        'sale_price' => 500,
        'status' => 'active',
    ], $overrides));
}

function seoSettingsAdminUser(): User
{
    $module = Module::create(['name' => 'Settings']);
    $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
    $permission = Permission::create([
        'name' => 'Manage Settings',
        'module_id' => $module->id,
        'slug' => 'manage-settings',
    ]);
    $role->permissions()->attach($permission->id);

    return User::factory()->create([
        'role_id' => $role->id,
        'user_type' => 'admin',
        'status' => 1,
    ]);
}

it('renders the home page with a dynamic seo prop', function () {
    Setting::set('seo_home_title', 'Reseller Shop in Bangladesh');
    Setting::set('seo_home_description', 'Buy nightdress and sexy clothes at reseller price in Dhaka.');

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StoreFront/Home/Index')
            ->where('seo.title', 'Reseller Shop in Bangladesh')
            ->where('seo.description', 'Buy nightdress and sexy clothes at reseller price in Dhaka.')
            ->where('seo.canonical', route('home'))
            ->where('seo.og_type', 'website'));
});

it('falls back to company description for seo description', function () {
    Setting::set('company_description', 'Trusted online clothing store in Bangladesh.');

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StoreFront/Home/Index')
            ->where('seo.description', 'Trusted online clothing store in Bangladesh.'));
});

it('uses product meta title and description when provided', function () {
    $product = seoProduct('meta-driven', [
        'meta_title' => 'Secy Nightdress in Bangladesh - Reseller Price',
        'meta_description' => 'Buy sexy nightdress online in Bangladesh with COD.',
    ]);

    $this->get(route('product.show', $product->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StoreFront/Products/Show')
            ->where('seo.title', 'Secy Nightdress in Bangladesh - Reseller Price')
            ->where('seo.description', 'Buy sexy nightdress online in Bangladesh with COD.')
            ->where('seo.canonical', route('product.show', $product->slug))
            ->where('seo.og_type', 'product')
            ->where('seo.schema.type', 'product')
            ->where('seo.schema.product.name', $product->title)
            ->where('seo.schema.product.currency', 'BDT')
            ->where('seo.schema.product.availability', 'InStock'));
});

it('exposes the category name and slug for the product category link', function () {
    $product = seoProduct('category-link-product');

    $this->get(route('product.show', $product->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StoreFront/Products/Show')
            ->where('product.category.name', 'Nightwear')
            ->where('product.category.slug', $product->category->slug));
});

it('falls back to the product title pattern when no meta fields are set', function () {
    Setting::set('seo_product_title_pattern', '{title} - Best Price in Bangladesh');

    $product = seoProduct('pattern-fallback');

    $this->get(route('product.show', $product->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.title', 'Product pattern-fallback - Best Price in Bangladesh')
            ->where('seo.description', 'Beautiful nightwear imported from China.'));
});

it('uses category meta title when provided and includes category in canonical', function () {
    $category = Category::factory()->create([
        'name' => 'Chinese Nightdress',
        'slug' => 'chinese-nightdress',
        'meta_title' => 'Chinese Nightdress in Bangladesh - Reseller Price',
    ]);

    seoProduct('in-category', ['category_id' => $category->id]);

    $this->get(route('category.show', $category->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StoreFront/Categories/Show')
            ->where('seo.title', 'Chinese Nightdress in Bangladesh - Reseller Price')
            ->where('seo.canonical', route('category.show', $category->slug))
            ->where('seo.schema.type', 'item_list'));
});

it('uses the category title pattern for categories without meta fields', function () {
    Setting::set('seo_category_title_pattern', '{name} at Reseller Price');

    $category = Category::factory()->create(['name' => 'Bra Panty', 'slug' => 'bra-panty']);

    $this->get(route('category.show', $category->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.title', 'Bra Panty at Reseller Price'));
});

it('serves listings with an seo prop and item list schema', function () {
    seoProduct('listing-item');

    $this->get(route('products.new-arrivals'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StoreFront/NewArrivals')
            ->where('seo.canonical', route('products.new-arrivals'))
            ->where('seo.schema.type', 'item_list'));
});

it('renders the XML sitemap with active content urls', function () {
    $category = Category::factory()->create(['name' => 'Lingerie', 'slug' => 'lingerie']);
    $product = seoProduct('sitemap-product', ['category_id' => $category->id]);
    $page = Page::factory()->create(['status' => true]);

    $response = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', false)
        ->assertSee(route('product.show', $product->slug), false)
        ->assertSee(route('category.show', $category->slug), false)
        ->assertSee(route('pages.show', $page->slug), false)
        ->assertSee(route('home'), false);
});

it('returns 404 for sitemap when disabled', function () {
    Setting::set('seo_enable_sitemap', '0');

    $this->get('/sitemap.xml')->assertNotFound();
});

it('serves robots.txt pointing to the sitemap', function () {
    $response = $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('User-agent: *', false)
        ->assertSee('Sitemap: '.route('sitemap'), false);

    expect($response->headers->get('content-type'))->toContain('text/plain');
});

it('honours a custom robots.txt body', function () {
    Setting::set('seo_robots_custom', "User-agent: *\nDisallow: /cart\n");

    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Disallow: /cart', false)
        ->assertSee('Sitemap: '.route('sitemap'), false);
});

it('persists seo settings through the admin settings endpoint', function () {
    $admin = seoSettingsAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), [
            'seo_home_title' => 'Bangladesh Reseller Store',
            'seo_keywords' => 'nightdress, resell kori, chinese nightdress in bangladesh',
            'seo_enable_sitemap' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Setting::get('seo_home_title'))->toBe('Bangladesh Reseller Store');
    expect(Setting::get('seo_keywords'))->toBe('nightdress, resell kori, chinese nightdress in bangladesh');
    expect(Setting::get('seo_enable_sitemap'))->toBe('1');
});

it('persists checkbox toggles as 0/1 and falls back to defaults', function () {
    $admin = seoSettingsAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), [
            'seo_enable_sitemap' => '0',
            'seo_schema_org' => '0',
            'seo_schema_website' => '0',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Setting::get('seo_enable_sitemap'))->toBe('0');
    expect(Setting::get('seo_schema_org'))->toBe('0');
    expect(Setting::get('seo_schema_website'))->toBe('0');
});

it('seeds the default SEO settings', function () {
    $this->seed(SeoSettingsSeeder::class);

    expect(Setting::get('seo_title_suffix'))->toBe('| Eghuri');
    expect(Setting::get('seo_home_title'))->toBe('Eghuri - Online Shopping in Bangladesh for Women & Kids');
    expect(Setting::get('seo_product_title_pattern'))->toBe('{title} - Best Price in BD - Eghuri');
    expect(Setting::get('seo_enable_sitemap'))->toBe('1');
    expect(Setting::get('seo_schema_org'))->toBe('1');
    expect(Setting::get('seo_robots_custom'))->toBe('');
});

it('exposes the SEO settings admin page', function () {
    $admin = seoSettingsAdminUser();

    $this->actingAs($admin)
        ->get(route('admin.settings.seo'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Settings/Seo'));
});

it('persists product and category meta fields via admin controllers', function () {
    $admin = seoSettingsAdminUser();
    $category = Category::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.products.store'), [
        'category_id' => $category->id,
        'title' => 'Sexy Nightdress Set',
        'description' => 'A lovely imported nightdress.',
        'unit_price' => 800,
        'sale_price' => 800,
        'status' => 'active',
        'featured' => false,
        'meta_title' => 'Sexy Nightdress in Bangladesh',
        'meta_description' => 'Reseller price nightdress, COD in Dhaka.',
    ]);

    $response->assertRedirect(route('admin.products.index'));

    $product = Product::where('slug', 'sexy-nightdress-set')->first();
    expect($product)->not->toBeNull();
    expect($product->meta_title)->toBe('Sexy Nightdress in Bangladesh');
    expect($product->meta_description)->toBe('Reseller price nightdress, COD in Dhaka.');

    $this->actingAs($admin)->post(route('admin.categories.store'), [
        'name' => 'Nightwear',
        'description' => 'Nightwear collection.',
        'is_active' => true,
        'meta_title' => 'Nightwear in Bangladesh',
    ]);

    $savedCategory = Category::where('slug', 'nightwear')->first();
    expect($savedCategory->meta_title)->toBe('Nightwear in Bangladesh');
});
