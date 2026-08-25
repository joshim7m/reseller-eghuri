<?php

use App\Jobs\ExportCatalogJob;
use App\Jobs\ImportCatalogJob;
use App\Models\CatalogExport;
use App\Models\CatalogImport;
use App\Models\Category;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function catalogAdminUser(): User
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

function tinyPng(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
}

it('renders the catalog settings page', function () {
    $admin = catalogAdminUser();

    $this->actingAs($admin)
        ->get(route('admin.settings.catalog'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Settings/Catalog'));
});

it('starts a catalog export job', function () {
    Queue::fake();

    $admin = catalogAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.settings.catalog.export'))
        ->assertRedirect();

    Queue::assertPushed(ExportCatalogJob::class);
    $this->assertDatabaseHas('catalog_exports', ['status' => 'queued']);
});

it('requires a file when starting an import', function () {
    $admin = catalogAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.settings.catalog.import'))
        ->assertSessionHasErrors('file');
});

it('rejects an import file larger than 200 MB', function () {
    $admin = catalogAdminUser();
    $oversized = UploadedFile::fake()->create('products.csv', 204801);

    $this->actingAs($admin)
        ->post(route('admin.settings.catalog.import'), ['file' => $oversized])
        ->assertSessionHasErrors('file');
});

it('returns a friendly error when the upload exceeds the server limit', function () {
    $admin = catalogAdminUser();

    $this->actingAs($admin)
        ->withServerVariables(['CONTENT_LENGTH' => '999999999'])
        ->postJson(route('admin.settings.catalog.import'))
        ->assertStatus(413)
        ->assertJsonPath('message', 'The uploaded file is too large.')
        ->assertJsonPath('errors.file', 'The uploaded file exceeds the server upload limit.');
});

it('starts a catalog import job and stores the upload', function () {
    Queue::fake();
    Storage::fake('local');

    $admin = catalogAdminUser();
    $file = UploadedFile::fake()->create('products.csv', 100, 'text/csv');

    $this->actingAs($admin)
        ->post(route('admin.settings.catalog.import'), [
            'file' => $file,
        ])
        ->assertRedirect();

    Queue::assertPushed(ImportCatalogJob::class);
    $this->assertDatabaseHas('catalog_imports', ['original_name' => 'products.csv']);

    $stored = CatalogImport::first()->file_path;

    expect($stored)->not->toBeNull();
    Storage::disk('local')->assertExists($stored);
});

it('exports the catalog as a single products.csv ZIP with embedded categories', function () {
    $admin = catalogAdminUser();

    $productImage = public_path('images/test-catalog/product.png');

    if (! is_dir(dirname($productImage))) {
        mkdir(dirname($productImage), 0755, true);
    }

    file_put_contents($productImage, tinyPng());

    $category = Category::create(['name' => 'Clothing']);

    $product = Product::create([
        'category_id' => $category->id,
        'title' => 'Export Product',
        'sku' => 'EXP-1',
        'unit_price' => 500,
        'sale_price' => 450,
        'quantity' => 3,
        'status' => 'active',
        'featured' => true,
    ]);
    $product->categories()->attach($category->id);
    ProductImage::create([
        'product_id' => $product->id,
        'image_path' => 'images/test-catalog/product.png',
        'sort_order' => 0,
    ]);

    try {
        Storage::fake('local');

        $export = CatalogExport::factory()->create(['user_id' => $admin->id]);

        (new ExportCatalogJob($export->id))->handle();

        $export->refresh();

        expect($export->status)->toBe('completed')
            ->and($export->total)->toBe(1)
            ->and($export->processed)->toBe(1);

        $zip = new ZipArchive;
        expect($zip->open(Storage::disk('local')->path($export->file_path)))->toBeTrue();

        $csv = $zip->getFromName('products.csv');

        expect($csv)->toContain('Title')
            ->and($csv)->toContain('Export Product')
            ->and($csv)->toContain('Clothing');

        expect($zip->getFromName('categories.csv'))->toBeFalse()
            ->and($zip->getFromName("images/{$product->id}-0-product.png"))->not->toBeFalse();

        $zip->close();
    } finally {
        @unlink($productImage);
    }
});

it('imports products from a CSV, creates categories, and skips existing SKUs', function () {
    $admin = catalogAdminUser();

    Product::create([
        'title' => 'Existing Product',
        'sku' => 'EXISTING-1',
        'unit_price' => 100,
        'sale_price' => 100,
        'status' => 'active',
    ]);

    $csv = "Title,Slug,SKU,Description,Specification,Unit Price,Sale Price,Quantity,Status,Featured,Category,Categories,Images\n"
        ."Imported T-Shirt,,TSHIRT-001,Nice shirt,,500,450,10,active,Yes,Clothing,Summer,\n"
        ."Existing Product,,EXISTING-1,,,100,100,1,active,No,,\n"
        ."Duplicate Shirt,,TSHIRT-001,,,200,180,1,active,No,,\n"
        .",,,,,\n";

    Storage::fake('local');
    Storage::disk('local')->put('catalog/imports/original/products.csv', $csv);

    $import = CatalogImport::factory()->create([
        'user_id' => $admin->id,
        'original_name' => 'products.csv',
        'file_path' => 'catalog/imports/original/products.csv',
    ]);

    (new ImportCatalogJob($import->id))->handle();

    $import->refresh();

    expect($import->status)->toBe('completed')
        ->and($import->total_rows)->toBe(4)
        ->and($import->imported)->toBe(1)
        ->and($import->skipped)->toBe(3)
        ->and($import->errors)->toHaveCount(1)
        ->and($import->errors[0]['row'])->toBe(5);

    $this->assertDatabaseHas('products', [
        'sku' => 'TSHIRT-001',
        'title' => 'Imported T-Shirt',
        'unit_price' => 500,
        'sale_price' => 450,
        'quantity' => 10,
        'status' => 'active',
        'featured' => 1,
    ]);

    expect(Category::where('name', 'Clothing')->exists())->toBeTrue()
        ->and(Category::where('name', 'Summer')->exists())->toBeTrue();

    $product = Product::where('sku', 'TSHIRT-001')->first();
    expect($product->categories->pluck('name'))->toContain('Summer');
    expect(Product::where('sku', 'TSHIRT-001')->count())->toBe(1);
});

it('imports a ZIP containing products.csv and auto-creates referenced categories', function () {
    $admin = catalogAdminUser();

    Storage::fake('local');
    $disk = Storage::disk('local');
    $workDir = 'catalog/imports/original';

    $disk->makeDirectory($workDir.'/images');
    $disk->put($workDir.'/products.csv', "Title,SKU,Unit Price,Sale Price,Quantity,Status,Category,Images\nZip Product,ZIP-1,300,250,5,active,Zip Category,zip.png\n");
    $disk->put($workDir.'/images/zip.png', tinyPng());

    $zipPath = $disk->path($workDir.'/catalog.zip');
    $zip = new ZipArchive;
    expect($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE))->toBeTrue();
    $zip->addFile($disk->path($workDir.'/products.csv'), 'products.csv');
    $zip->addFile($disk->path($workDir.'/images/zip.png'), 'images/zip.png');
    $zip->close();

    $import = CatalogImport::factory()->create([
        'user_id' => $admin->id,
        'original_name' => 'catalog.zip',
        'file_path' => $workDir.'/catalog.zip',
    ]);

    (new ImportCatalogJob($import->id))->handle();

    $import->refresh();

    expect($import->status)->toBe('completed')
        ->and($import->total_rows)->toBe(1)
        ->and($import->imported)->toBe(1)
        ->and($import->skipped)->toBe(0);

    $product = Product::where('sku', 'ZIP-1')->first();

    expect($product)->not->toBeNull()
        ->and($product->images)->toHaveCount(1)
        ->and(Category::where('name', 'Zip Category')->exists())->toBeTrue();

    $imagePath = $product->images->first()->image_path;

    expect(public_path($imagePath))->toBeFile();

    @unlink(public_path($imagePath));
});

it('imports categories and skips existing slugs', function () {
    $admin = catalogAdminUser();

    Category::create(['name' => 'Clothing', 'slug' => 'clothing']);

    $csv = "Name,Slug,Parent,Description,Is Active,Image\n"
        ."Men,men,Clothing,Men category,Yes,\n"
        ."Clothing,clothing,,,Yes,\n";

    Storage::fake('local');
    Storage::disk('local')->put('catalog/imports/original/categories.csv', $csv);

    $import = CatalogImport::factory()->create([
        'user_id' => $admin->id,
        'original_name' => 'categories.csv',
        'file_path' => 'catalog/imports/original/categories.csv',
    ]);

    (new ImportCatalogJob($import->id))->handle();

    $import->refresh();

    expect($import->status)->toBe('completed')
        ->and($import->imported)->toBe(1)
        ->and($import->skipped)->toBe(1);

    $men = Category::where('slug', 'men')->first();

    expect($men)->not->toBeNull()
        ->and($men->parent->name)->toBe('Clothing');
});

it('round-trips an exported product back into an import without duplicating SKUs', function () {
    $admin = catalogAdminUser();

    $category = Category::create(['name' => 'Round Trip']);
    Product::create([
        'category_id' => $category->id,
        'title' => 'Round Trip Shirt',
        'sku' => 'ROUND-1',
        'unit_price' => 400,
        'sale_price' => 350,
        'quantity' => 7,
        'status' => 'active',
    ]);

    Storage::fake('local');

    $export = CatalogExport::factory()->create(['user_id' => $admin->id]);
    (new ExportCatalogJob($export->id))->handle();
    $export->refresh();

    $zip = new ZipArchive;
    $zip->open(Storage::disk('local')->path($export->file_path));
    $csv = $zip->getFromName('products.csv');
    $zip->close();

    $import = CatalogImport::factory()->create([
        'user_id' => $admin->id,
        'original_name' => 'products.csv',
        'file_path' => 'catalog/imports/original/products.csv',
    ]);
    Storage::disk('local')->put($import->file_path, $csv);

    (new ImportCatalogJob($import->id))->handle();

    $import->refresh();

    expect($import->status)->toBe('completed')
        ->and($import->skipped)->toBe(1)
        ->and($import->imported)->toBe(0)
        ->and(Product::where('sku', 'ROUND-1')->count())->toBe(1);
});

it('returns job status as JSON', function () {
    $admin = catalogAdminUser();

    CatalogExport::factory()->create(['user_id' => $admin->id]);
    CatalogImport::factory()->create(['user_id' => $admin->id]);

    $this->actingAs($admin)
        ->get(route('admin.settings.catalog.status'))
        ->assertOk()
        ->assertJsonStructure(['exports' => [], 'imports' => []])
        ->assertJsonCount(1, 'exports')
        ->assertJsonCount(1, 'imports');
});

it('downloads a completed export', function () {
    Storage::fake('local');
    Storage::disk('local')->put('catalog/exports/catalog-exports-99.zip', 'zip-bytes');

    $admin = catalogAdminUser();
    $export = CatalogExport::factory()->create([
        'user_id' => $admin->id,
        'status' => 'completed',
        'file_path' => 'catalog/exports/catalog-exports-99.zip',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.settings.catalog.download', $export))
        ->assertOk()
        ->assertDownload('catalog-exports-'.$export->id.'.zip');
});

it('does not download an incomplete export', function () {
    $admin = catalogAdminUser();
    $export = CatalogExport::factory()->create(['user_id' => $admin->id, 'status' => 'queued']);

    $this->actingAs($admin)
        ->get(route('admin.settings.catalog.download', $export))
        ->assertNotFound();
});
