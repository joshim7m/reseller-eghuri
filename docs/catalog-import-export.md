# Catalog Import / Export — Portable Implementation Guide

A queued, chunked import/export system for a product catalog, built on Laravel's queue + PhpSpreadsheet. Shopify-style: **one** catalog file (`products.csv`) carries every product **and** its categories (as name columns), delivered as a **ZIP archive containing the single CSV + real image files** (never image URLs).

This guide is written so you can re-implement the feature in *any* Laravel app. Where the code touches your own schema, adapt the field names listed in §2.2. The reference implementation lives in this repository (`app/Jobs`, `app/Models/CatalogExport`, `app/Models/CatalogImport`, `app/Http/Controllers/Admin/CatalogController.php`, `resources/js/Pages/Admin/Settings/Catalog.vue`, `tests/Feature/CatalogTransferTest.php`).

---

## 1. Overview

| Capability | Detail |
|---|---|
| **What it exports/imports** | Products, with their categories embedded in each product row |
| **File format** | **One** CSV — `products.csv` — one row per product; categories are `Category`/`Categories` name columns (Shopify-style), never a second file |
| **Export deliverable** | `catalog-exports-{id}.zip` = `products.csv` + `images/` folder |
| **Import input** | ZIP (single `products.csv` + images), or a plain CSV / XLSX (products *or* categories, auto-detected from columns) |
| **Processing** | Queued background jobs, chunked in batches of **100 rows** |
| **Concurrency safety** | Each export/import is tracked in its own DB record with live progress |
| **Duplicate policy** | Rows with an existing SKU (products) or slug (categories) are **skipped and reported** |

### Why queued + chunked?

- Large catalogs are processed in background jobs so admin requests never time out.
- Rows are processed in chunks of 100, keeping memory usage flat regardless of catalog size.
- The admin sees live progress (processed / total) and downloads the finished ZIP when the job completes.

### Why ZIP instead of a single spreadsheet?

CSV cannot embed binary image files. A ZIP archive carries the CSV **and** a folder of image files, with the CSV referencing images by filename. PhpSpreadsheet cannot append to an existing XLSX file, so CSV + ZIP is the only format that supports genuinely chunked, incremental writing.

---

## 2. Requirements & Assumed Schema

### 2.1 Packages

- `phpoffice/phpspreadsheet` (^5.9) — XLSX reading (installed via composer).
- PHP `zip` extension — `ZipArchive`.
- `queue` driver — use `database` (`QUEUE_CONNECTION=database`) so progress records survive restarts; add a worker (`php artisan queue:work`).

### 2.2 Assumed table columns (adapt to your project)

| Table | Columns used |
|---|---|
| `products` | `id`, `category_id` (nullable FK), `title`, `slug`, `sku` (nullable), `description`, `specification`, `unit_price`, `sale_price`, `quantity`, `status`, `featured` (bool), `timestamps` |
| `categories` | `id`, `parent_id` (nullable FK), `name`, `slug`, `description`, `is_active` (bool), `image_path` (nullable), `timestamps` |
| `product_images` | `id`, `product_id` FK, `image_path`, `sort_order`, `timestamps` |
| `category_product` | `category_id` FK, `product_id` FK (pivot for many-to-many extra categories) |

Model relations used:
- `Product::category()` (BelongsTo), `Product::categories()` (BelongsToMany via `category_product`), `Product::images()` (HasMany, ordered by `sort_order`).
- `Category::parent()` / children via `parent_id` if you import standalone category files.

Prices are stored as **integers** (whole currency units). If yours are decimal, change `priceOf()` and the export `fputcsv` values accordingly.

---

## 3. Database Schema (migrations)

Two tracking tables. Status values: `queued | processing | completed | failed`.

```php
// create_catalog_exports_table.php
Schema::create('catalog_exports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('status')->default('queued')->index();
    $table->string('file_path')->nullable();
    $table->unsignedInteger('total')->default(0);
    $table->unsignedInteger('processed')->default(0);
    $table->text('error')->nullable();
    $table->timestamps();
});

// create_catalog_imports_table.php
Schema::create('catalog_imports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('status')->default('queued')->index();
    $table->string('original_name');
    $table->string('file_path');
    $table->unsignedInteger('total_rows')->default(0);
    $table->unsignedInteger('processed')->default(0);
    $table->unsignedInteger('imported')->default(0);
    $table->unsignedInteger('skipped')->default(0);
    $table->json('errors')->nullable();
    $table->text('error')->nullable();
    $table->timestamps();
});
```

### Status lifecycle

```
queued → processing → completed
                  ↘ → failed
```

---

## 4. Models + Factories

```php
namespace App\Models;

use Database\Factories\CatalogExportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogExport extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /** @use HasFactory<CatalogExportFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'status', 'file_path', 'total', 'processed', 'error',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

```php
namespace App\Models;

use Database\Factories\CatalogImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogImport extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /** @use HasFactory<CatalogImportFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'status', 'original_name', 'file_path', 'total_rows',
        'processed', 'imported', 'skipped', 'errors', 'error',
    ];

    protected function casts(): array
    {
        return ['errors' => 'array'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

Factories:

```php
// CatalogExportFactory
return [
    'status' => CatalogExport::STATUS_QUEUED,
    'total' => 0,
    'processed' => 0,
];

// CatalogImportFactory
return [
    'status' => CatalogImport::STATUS_QUEUED,
    'original_name' => 'catalog.csv',
    'file_path' => 'catalog/imports/original/catalog.csv',
    'total_rows' => 0,
    'processed' => 0,
    'imported' => 0,
    'skipped' => 0,
];
```

---

## 5. Routes

Inside your authenticated admin group (protect with your own admin middleware — the reference uses `auth`, `verified`, `check-status`, `check-role:manage-catalog`; swap `check-role` for your RBAC):

```php
Route::get('settings/catalog', [CatalogController::class, 'index'])->name('settings.catalog');
Route::post('settings/catalog/export', [CatalogController::class, 'export'])->name('settings.catalog.export');
Route::post('settings/catalog/import', [CatalogController::class, 'import'])->name('settings.catalog.import');
Route::get('settings/catalog/status', [CatalogController::class, 'status'])->name('settings.catalog.status');
Route::get('settings/catalog/exports/{export}/download', [CatalogController::class, 'download'])->name('settings.catalog.download');
```

| Method | URI | Purpose |
|---|---|---|
| GET | `/settings/catalog` | Renders the page with latest jobs |
| POST | `/settings/catalog/export` | No body → creates `CatalogExport`, dispatches `ExportCatalogJob` |
| POST | `/settings/catalog/import` | Body `{ file }` → stores file, creates `CatalogImport`, dispatches `ImportCatalogJob` |
| GET | `/settings/catalog/status` | JSON: latest exports + imports (polled by the UI every 2.5 s) |
| GET | `/settings/catalog/exports/{export}/download` | Streams the completed ZIP (route-model binding) |

---

## 6. Controller

```php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ExportCatalogJob;
use App\Jobs\ImportCatalogJob;
use App\Models\CatalogExport;
use App\Models\CatalogImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatalogController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Catalog', [
            'exports' => CatalogExport::with('user')->latest()->limit(10)->get(),
            'imports' => CatalogImport::with('user')->latest()->limit(10)->get(),
        ]);
    }

    public function export(Request $request): RedirectResponse
    {
        $export = CatalogExport::create(['user_id' => $request->user()->id]);

        ExportCatalogJob::dispatch($export->id);

        return back()->with('success', 'Catalog export started.');
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:zip,csv,xlsx,xls|max:204800', // 200 MB
        ]);

        $file = $request->file('file');
        $storedPath = $file->store('catalog/imports/original', 'local');

        $import = CatalogImport::create([
            'user_id' => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
        ]);

        ImportCatalogJob::dispatch($import->id);

        return back()->with('success', 'Catalog import started.');
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'exports' => CatalogExport::latest()->limit(10)->get(),
            'imports' => CatalogImport::latest()->limit(10)->get(),
        ]);
    }

    public function download(CatalogExport $export): StreamedResponse
    {
        abort_unless($export->status === CatalogExport::STATUS_COMPLETED && $export->file_path, 404);

        return Storage::disk('local')->download($export->file_path, 'catalog-exports-'.$export->id.'.zip');
    }
}
```

> If you're not using Inertia, replace the `index()` render and the `back()` responses with your Blade/API equivalents — the jobs and format are framework-agnostic.

---

## 7. Export Job — `ExportCatalogJob`

Key points:
- Chunked with `chunkById(100)` so memory stays flat.
- CSV is opened in append-write mode with a UTF-8 BOM header row (`\xEF\xBB\xBF`).
- Images are copied/downloaded into `images/` as `{productId}-{sortOrder}-{basename}`.
- Work dir is deleted after the ZIP is built; failures mark the record `failed` and rethrow.

```php
<?php

namespace App\Jobs;

use App\Models\CatalogExport;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class ExportCatalogJob implements ShouldQueue
{
    use Queueable;

    private const CHUNK_SIZE = 100;

    public function __construct(public int $exportId) {}

    public function handle(): void
    {
        $export = CatalogExport::findOrFail($this->exportId);

        if (in_array($export->status, [CatalogExport::STATUS_PROCESSING, CatalogExport::STATUS_COMPLETED], true)) {
            return;
        }

        $disk = Storage::disk('local');
        $workDir = "catalog/exports/{$export->id}";
        $imageDir = "{$workDir}/images";
        $csvPath = "{$workDir}/products.csv";

        $disk->deleteDirectory($workDir);
        $disk->makeDirectory($imageDir);

        $export->update([
            'status' => CatalogExport::STATUS_PROCESSING,
            'total' => Product::count(),
        ]);

        try {
            $this->exportProducts($export, $disk->path($csvPath), $imageDir);

            $zipPath = "catalog/exports/catalog-exports-{$export->id}.zip";
            $this->buildZip($disk->path($workDir), $disk->path($zipPath));

            $disk->deleteDirectory($workDir);

            $export->update([
                'status' => CatalogExport::STATUS_COMPLETED,
                'file_path' => $zipPath,
            ]);
        } catch (\Throwable $exception) {
            $disk->deleteDirectory($workDir);

            $export->update([
                'status' => CatalogExport::STATUS_FAILED,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function exportProducts(CatalogExport $export, string $csvPath, string $imageDir): void
    {
        $handle = $this->openCsv($csvPath, [
            'Title', 'Slug', 'SKU', 'Description', 'Specification',
            'Unit Price', 'Sale Price', 'Quantity', 'Status', 'Featured',
            'Category', 'Categories', 'Images',
        ]);

        $processed = $export->processed;

        Product::query()
            ->with(['category', 'categories', 'images'])
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($products) use ($export, $handle, $imageDir, &$processed) {
                foreach ($products as $product) {
                    $imageNames = [];

                    foreach ($product->images->sortBy('sort_order')->values() as $index => $image) {
                        $name = $this->exportImage($imageDir, $product->id, $index, $image->image_path);

                        if ($name) {
                            $imageNames[] = $name;
                        }
                    }

                    fputcsv($handle, [
                        $product->title,
                        $product->slug,
                        $product->sku,
                        $product->description,
                        $product->specification,
                        $product->unit_price,
                        $product->sale_price,
                        $product->quantity,
                        $product->status,
                        $product->featured ? 'Yes' : 'No',
                        $product->category?->name,
                        $product->categories->pluck('name')->implode(', '),
                        implode(',', $imageNames),
                    ]);
                }

                $processed += $products->count();
                $export->update(['processed' => $processed]);
            });

        fclose($handle);
    }

    private function openCsv(string $csvPath, array $headers): mixed
    {
        $handle = fopen($csvPath, 'w');

        if ($handle === false) {
            throw new RuntimeException("Could not open CSV file [{$csvPath}].");
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);

        return $handle;
    }

    private function exportImage(string $imageDir, int $modelId, int $sortOrder, ?string $imagePath): ?string
    {
        if (! $imagePath) {
            return null;
        }

        $filename = $modelId.'-'.$sortOrder.'-'.$this->sanitizeFilename($imagePath);

        $contents = null;

        if (str_starts_with($imagePath, 'http')) {
            try {
                $contents = @file_get_contents($imagePath);
            } catch (\Throwable) {
                $contents = false;
            }
        } else {
            $local = public_path($imagePath);

            if (is_file($local)) {
                $contents = file_get_contents($local);
            } else {
                try {
                    $contents = @file_get_contents(asset($imagePath));
                } catch (\Throwable) {
                    $contents = false;
                }
            }
        }

        if ($contents === false) {
            return null;
        }

        Storage::disk('local')->put("{$imageDir}/{$filename}", $contents);

        return $filename;
    }

    private function sanitizeFilename(string $path): string
    {
        $basename = pathinfo($path, PATHINFO_BASENAME);
        $sanitized = preg_replace('/[^A-Za-z0-9._-]/', '_', $basename);

        return trim((string) $sanitized, '._-') ?: 'image';
    }

    private function buildZip(string $directory, string $zipPath): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not create ZIP archive [{$zipPath}].");
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $relative = substr($file->getPathname(), strlen($directory) + 1);
            $zip->addFile($file->getPathname(), $relative);
        }

        $zip->close();
    }
}
```

### ZIP structure

```
catalog-exports-{id}.zip
├── products.csv             one row per product (categories + images as columns)
└── images/
    ├── 12-0-white-shirt.jpg      {modelId}-{sortOrder}-{basename}
    ├── 12-1-white-shirt-back.jpg
    └── ...
```

There is **no** `categories.csv` — like Shopify, everything lives in one file.

### `products.csv` columns

Headers, in order, prefixed with a UTF-8 BOM (`\xEF\xBB\xBF`) so Excel renders non-ASCII text correctly:

```
Title, Slug, SKU, Description, Specification, Unit Price, Sale Price, Quantity, Status, Featured, Category, Categories, Images
```

| Column | Notes |
|---|---|
| `Title` | Product title |
| `Slug` | Auto-generated on import if blank |
| `SKU` | Dedupe key on import |
| `Description` / `Specification` | Plain text |
| `Unit Price` / `Sale Price` | Integers (adapt if your prices are decimal) |
| `Quantity` | Stock quantity |
| `Status` | `active` \| `inactive` \| `draft` |
| `Featured` | `Yes` \| `No` |
| `Category` | Primary category **name** (`category_id`) — like Shopify's *Type* |
| `Categories` | Extra categories (pivot), comma-separated — like Shopify's *Tags* |
| `Images` | Comma-separated image filenames as written under `images/` |

> Categories are represented **by name only** (Shopify-faithful). On import they are auto-created via `firstOrCreate(['name' => ...])`; category metadata (description, parent, is_active, category image) is not part of this format.

### Image handling on export

- Images are written ordered by `sort_order`.
- **Local paths** (stored under `public/images/...`) are copied directly into `images/`.
- **Remote URLs** are downloaded at export time via `file_get_contents`; failed downloads are skipped silently.
- Filename scheme `{modelId}-{sortOrder}-{basename}` guarantees uniqueness and restores ordering on import.

---

## 8. Import Job — `ImportCatalogJob`

Key points:
- ZIP → guarded extraction → requires `products.csv` at root.
- Standalone CSV/XLSX → type auto-detected from headers (`Title`/`SKU` → products; `Name` → categories).
- Dedupes against existing SKUs/slugs **and** within the file (per-run `seen` collections).
- CSV is streamed with `fgetcsv`; XLSX uses a `ChunkReadFilter` + per-chunk `load()` so memory stays flat.
- Images resolve from the extracted `images/`, a local `public/` path, or a remote URL; missing images are row errors, never fatal.
- Per-chunk counters update `processed` / `imported` / `skipped` / `errors` so the UI shows live progress.

```php
<?php

namespace App\Jobs;

use App\Models\CatalogImport;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use ZipArchive;

class ImportCatalogJob implements ShouldQueue
{
    use Queueable;

    private const CHUNK_SIZE = 100;

    public function __construct(public int $importId) {}

    public function handle(): void
    {
        $import = CatalogImport::findOrFail($this->importId);

        if (in_array($import->status, [CatalogImport::STATUS_PROCESSING, CatalogImport::STATUS_COMPLETED], true)) {
            return;
        }

        $disk = Storage::disk('local');
        $workDir = "catalog/imports/{$import->id}";
        $extractedDir = "{$workDir}/extracted";
        $extension = strtolower((string) pathinfo($import->original_name, PATHINFO_EXTENSION));

        $import->update(['status' => CatalogImport::STATUS_PROCESSING]);

        try {
            $filePath = $disk->path($import->file_path);

            if ($extension === 'zip') {
                $disk->makeDirectory($extractedDir);
                $this->extractZip($filePath, $disk->path($extractedDir));

                $productFile = $disk->path("{$extractedDir}/products.csv");

                if (! is_file($productFile)) {
                    throw new RuntimeException('products.csv not found in the uploaded archive.');
                }

                $import->update(['total_rows' => $this->countRows($productFile, 'csv')]);

                $this->importProducts($import, $productFile, 'csv', $disk, $extractedDir);
            } else {
                $fileType = $this->detectFileType($filePath, $extension);

                $import->update(['total_rows' => $this->countRows($filePath, $extension)]);

                if ($fileType === 'categories') {
                    $this->importCategories($import, $filePath, $extension, $disk, '');
                } else {
                    $this->importProducts($import, $filePath, $extension, $disk, '');
                }
            }

            if ($extension === 'zip') {
                $disk->deleteDirectory($extractedDir);
            }

            $import->update(['status' => CatalogImport::STATUS_COMPLETED]);
        } catch (\Throwable $exception) {
            $import->update([
                'status' => CatalogImport::STATUS_FAILED,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function detectFileType(string $filePath, string $extension): string
    {
        if (in_array($extension, ['xlsx', 'xls'], true)) {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $header = $this->normalizeXlsxHeader($reader->load($filePath)->getActiveSheet());
        } else {
            $handle = fopen($filePath, 'r');

            if ($handle === false) {
                throw new RuntimeException("Could not open CSV file [{$filePath}].");
            }

            $headerRow = null;

            try {
                $headerRow = fgetcsv($handle);
            } finally {
                fclose($handle);
            }

            $header = [];

            if (is_array($headerRow)) {
                foreach ($headerRow as $name) {
                    $key = $this->normalizeKey((string) $name);

                    if ($key !== '') {
                        $header[$key] = true;
                    }
                }
            }
        }

        if (isset($header['title']) || isset($header['sku'])) {
            return 'products';
        }

        if (isset($header['name'])) {
            return 'categories';
        }

        throw new RuntimeException('Could not detect whether the file contains products or categories. Expected a "Title"/"SKU" header for products or a "Name" header for categories.');
    }

    private function importProducts(CatalogImport $import, string $filePath, string $extension, FilesystemAdapter $disk, string $extractedDir): void
    {
        $seenSkus = Product::query()->whereNotNull('sku')->pluck('sku')
            ->map(fn ($sku) => trim((string) $sku))
            ->filter()
            ->flip();

        $seenSlugs = Product::query()->pluck('slug')->flip();
        /** @var array<int, array{row: int, message: string}> $errors */
        $errors = $import->errors ?? [];

        foreach ($this->rowBatches($filePath, $extension) as $batch) {
            $batchImported = 0;
            $batchSkipped = 0;

            foreach ($batch as $item) {
                $row = $item['row'];
                $rowNumber = $item['row_number'];

                $sku = $this->cleanValue($row['sku'] ?? null);

                if ($sku !== null && $seenSkus->has($sku)) {
                    $batchSkipped++;

                    continue;
                }

                $title = $this->cleanValue($row['title'] ?? null);
                $unitPrice = $this->priceOf($row['unit_price'] ?? null);
                $salePrice = $this->priceOf($row['sale_price'] ?? null);

                if ($title === null) {
                    $errors[] = ['row' => $rowNumber, 'message' => 'Title is required.'];
                    $batchSkipped++;

                    continue;
                }

                if ($unitPrice === null) {
                    $errors[] = ['row' => $rowNumber, 'message' => 'Unit price is required.'];
                    $batchSkipped++;

                    continue;
                }

                if ($salePrice === null) {
                    $errors[] = ['row' => $rowNumber, 'message' => 'Sale price is required.'];
                    $batchSkipped++;

                    continue;
                }

                $status = strtolower($this->cleanValue($row['status'] ?? null) ?? 'draft');

                if (! in_array($status, ['active', 'inactive', 'draft'], true)) {
                    $errors[] = ['row' => $rowNumber, 'message' => "Invalid status \"{$status}\"."];
                    $batchSkipped++;

                    continue;
                }

                $primary = $this->resolveCategory($row['category'] ?? null);
                $categoryIds = [];

                foreach ($this->splitList($row['categories'] ?? null) as $name) {
                    $categoryIds[] = $this->resolveCategory($name)->id;
                }

                $slug = $this->uniqueSlug(Str::slug($title), $seenSlugs);

                $product = Product::create([
                    'category_id' => $primary?->id,
                    'title' => $title,
                    'slug' => $slug,
                    'description' => $this->cleanValue($row['description'] ?? null) ?? '',
                    'specification' => $this->cleanValue($row['specification'] ?? null),
                    'sku' => $sku,
                    'quantity' => max(0, (int) ($this->cleanValue($row['quantity'] ?? null) ?? 0)),
                    'unit_price' => $unitPrice,
                    'sale_price' => $salePrice,
                    'status' => $status,
                    'featured' => $this->booleanOf($row['featured'] ?? null),
                ]);

                if ($sku !== null) {
                    $seenSkus->offsetSet($sku, 1);
                }

                $seenSlugs->put($slug, 1);

                if ($categoryIds) {
                    $product->categories()->sync($categoryIds);
                }

                $this->importProductImages($product, $disk, $extractedDir, $row['images'] ?? null, $rowNumber, $errors);

                $batchImported++;
            }

            $import->increment('processed', count($batch));
            $import->increment('imported', $batchImported);
            $import->increment('skipped', $batchSkipped);
            $import->update(['errors' => $errors]);
        }
    }

    private function importCategories(CatalogImport $import, string $filePath, string $extension, FilesystemAdapter $disk, string $extractedDir): void
    {
        $seenSlugs = Category::query()->pluck('slug')->flip();
        /** @var array<int, array{row: int, message: string}> $errors */
        $errors = $import->errors ?? [];

        foreach ($this->rowBatches($filePath, $extension) as $batch) {
            $batchImported = 0;
            $batchSkipped = 0;

            foreach ($batch as $item) {
                $row = $item['row'];
                $rowNumber = $item['row_number'];

                $name = $this->cleanValue($row['name'] ?? null);

                if ($name === null) {
                    $errors[] = ['row' => $rowNumber, 'message' => 'Name is required.'];
                    $batchSkipped++;

                    continue;
                }

                $slug = $this->cleanValue($row['slug'] ?? null) ?? Str::slug($name);

                if ($seenSlugs->has($slug)) {
                    $batchSkipped++;

                    continue;
                }

                $seenSlugs->put($slug, 1);

                $parent = $this->resolveCategory($row['parent'] ?? null);
                $isActiveValue = $this->cleanValue($row['is_active'] ?? null);

                $category = Category::create([
                    'parent_id' => $parent?->id,
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $this->cleanValue($row['description'] ?? null),
                    'is_active' => $isActiveValue === null ? true : $this->booleanOf($isActiveValue),
                ]);

                $imageName = $this->cleanValue($row['image'] ?? null);

                if ($imageName !== null) {
                    $source = $this->locateImage($disk, $extractedDir, $imageName);

                    if ($source !== null) {
                        $target = 'images/categories/'.$category->id.'_'.$this->sanitizeFilename($imageName);
                        copy($source, public_path($target));
                        $category->update(['image_path' => $target]);
                    } else {
                        $errors[] = ['row' => $rowNumber, 'message' => "Image \"{$imageName}\" not found."];
                    }
                }

                $batchImported++;
            }

            $import->increment('processed', count($batch));
            $import->increment('imported', $batchImported);
            $import->increment('skipped', $batchSkipped);
            $import->update(['errors' => $errors]);
        }
    }

    private function importProductImages(Product $product, FilesystemAdapter $disk, string $extractedDir, mixed $imagesColumn, int $rowNumber, array &$errors): void
    {
        foreach ($this->splitList($imagesColumn) as $index => $name) {
            $source = $this->locateImage($disk, $extractedDir, $name);

            if ($source === null) {
                $errors[] = ['row' => $rowNumber, 'message' => "Image \"{$name}\" not found."];

                continue;
            }

            $target = 'images/products/'.$product->id.'_'.$index.'_'.$this->sanitizeFilename($name);
            copy($source, public_path($target));

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $target,
                'sort_order' => $index,
            ]);
        }
    }

    private function locateImage(FilesystemAdapter $disk, string $extractedDir, string $name): ?string
    {
        $filename = basename($name);
        $inArchive = $disk->path("{$extractedDir}/images/{$filename}");

        if ($extractedDir !== '' && is_file($inArchive)) {
            return $inArchive;
        }

        $public = public_path($name);

        if (is_file($public)) {
            return $public;
        }

        if (str_starts_with($name, 'http')) {
            $contents = @file_get_contents($name);

            if ($contents !== false) {
                $temporary = tempnam(sys_get_temp_dir(), 'catalog-img');
                file_put_contents($temporary, $contents);

                return $temporary;
            }
        }

        return null;
    }

    private function resolveCategory(mixed $name): ?Category
    {
        $name = $this->cleanValue($name);

        if ($name === null) {
            return null;
        }

        return Category::firstOrCreate(['name' => $name]);
    }

    private function uniqueSlug(string $slug, EloquentCollection|Collection $seen): string
    {
        $base = $slug;
        $suffix = 2;

        while ($seen->has($slug)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function priceOf(mixed $value): ?int
    {
        $value = $this->cleanValue($value);

        if ($value === null) {
            return null;
        }

        $cleaned = preg_replace('/[^\d.-]/', '', $value);

        if ($cleaned === null || $cleaned === '' || $cleaned === '-' || $cleaned === '.') {
            return null;
        }

        return (int) round((float) $cleaned);
    }

    private function booleanOf(mixed $value): bool
    {
        return in_array(strtolower($this->cleanValue($value) ?? ''), ['1', 'true', 'yes', 'y', 'on'], true);
    }

    private function cleanValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function splitList(mixed $value): array
    {
        $value = $this->cleanValue($value);

        if ($value === null) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('trim', preg_split('/[,;|]/', $value) ?: []))));
    }

    private function sanitizeFilename(string $path): string
    {
        $basename = pathinfo($path, PATHINFO_BASENAME);
        $sanitized = preg_replace('/[^A-Za-z0-9._-]/', '_', $basename);

        return trim((string) $sanitized, '._-') ?: 'image';
    }

    private function countRows(string $filePath, string $extension): int
    {
        if (in_array($extension, ['xlsx', 'xls'], true)) {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);

            return max(0, $reader->load($filePath)->getActiveSheet()->getHighestDataRow() - 1);
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return 0;
        }

        $count = 0;

        try {
            fgetcsv($handle);

            while (fgetcsv($handle) !== false) {
                $count++;
            }
        } finally {
            fclose($handle);
        }

        return $count;
    }

    /** @return \Generator<int, array<int, array{row: array<string, mixed>, row_number: int}>> */
    private function rowBatches(string $filePath, string $extension): \Generator
    {
        if (in_array($extension, ['xlsx', 'xls'], true)) {
            yield from $this->xlsxRowBatches($filePath);
        } else {
            yield from $this->csvRowBatches($filePath);
        }
    }

    /** @return \Generator<int, array<int, array{row: array<string, mixed>, row_number: int}>> */
    private function csvRowBatches(string $filePath): \Generator
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new RuntimeException("Could not open CSV file [{$filePath}].");
        }

        try {
            $headerRow = fgetcsv($handle);

            if ($headerRow === false) {
                return;
            }

            $header = [];

            foreach ($headerRow as $index => $name) {
                $key = $this->normalizeKey((string) $name);

                if ($key !== '') {
                    $header[$key] = $index;
                }
            }

            $batch = [];
            $rowNumber = 1;

            while (($values = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($values === [null]) {
                    continue;
                }

                $assoc = [];

                foreach ($header as $key => $index) {
                    $assoc[$key] = $values[$index] ?? null;
                }

                $batch[] = ['row' => $assoc, 'row_number' => $rowNumber];

                if (count($batch) >= self::CHUNK_SIZE) {
                    yield $batch;
                    $batch = [];
                }
            }

            if ($batch) {
                yield $batch;
            }
        } finally {
            fclose($handle);
        }
    }

    /** @return \Generator<int, array<int, array{row: array<string, mixed>, row_number: int}>> */
    private function xlsxRowBatches(string $filePath): \Generator
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $headerSheet = $reader->load($filePath)->getActiveSheet();
        $header = $this->normalizeXlsxHeader($headerSheet);
        $lastRow = $headerSheet->getHighestDataRow();

        $chunkFilter = new class implements IReadFilter
        {
            private int $startRow = 1;

            private int $endRow = 1;

            public function setRows(int $startRow, int $endRow): void
            {
                $this->startRow = $startRow;
                $this->endRow = $endRow;
            }

            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return $row >= $this->startRow && $row <= $this->endRow;
            }
        };

        $reader->setReadFilter($chunkFilter);

        $start = 2;

        while ($start <= $lastRow) {
            $end = min($start + self::CHUNK_SIZE - 1, $lastRow);
            $chunkFilter->setRows($start, $end);

            $worksheet = $reader->load($filePath)->getActiveSheet();
            $batch = [];

            foreach ($worksheet->getRowIterator($start, $end) as $row) {
                $values = [];

                foreach ($row->getCellIterator() as $cell) {
                    $values[$cell->getColumn()] = $cell->getValue();
                }

                $assoc = [];

                foreach ($header as $key => $column) {
                    $assoc[$key] = $values[$column] ?? null;
                }

                $batch[] = ['row' => $assoc, 'row_number' => $row->getRowIndex()];
            }

            if ($batch) {
                yield $batch;
            }

            $start = $end + 1;
        }
    }

    /** @return array<string, string> */
    private function normalizeXlsxHeader(Worksheet $sheet): array
    {
        $header = [];

        foreach ($sheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $key = $this->normalizeKey((string) $cell->getValue());

                if ($key !== '') {
                    $header[$key] = $cell->getColumn();
                }
            }
        }

        return $header;
    }

    private function normalizeKey(string $name): string
    {
        $name = str_replace("\xEF\xBB\xBF", '', $name);

        return preg_replace('/[^a-z0-9]/', '_', strtolower(trim($name))) ?? '';
    }

    private function extractZip(string $zipPath, string $extractTo): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Could not open the uploaded ZIP archive.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = str_replace('\\', '/', (string) $zip->getNameIndex($i));

            if ($entry === '' || str_contains($entry, '..') || str_starts_with($entry, '/') || (bool) preg_match('/^[A-Za-z]:\//', $entry)) {
                continue;
            }

            $basename = basename($entry);

            if ($basename === '' || $basename === '.') {
                continue;
            }

            $target = $extractTo.'/'.$entry;

            if (! str_starts_with($target, $extractTo)) {
                continue;
            }

            if (! is_dir(dirname($target))) {
                @mkdir(dirname($target), 0775, true);
            }

            copy('zip://'.$zipPath.'#'.$entry, $target);
        }

        $zip->close();
    }
}
```

> **XLSX gotcha**: PhpSpreadsheet cell iterators key by **column letters** (`A`, `B`, …), not integers — always use `$cell->getColumn()` and index `$values` by that.

---

## 9. Graceful `PostTooLargeException` Handling

PHP rejects over-limit uploads **before** Laravel validation runs, so `max:204800` never fires and the user would see a raw exception. Convert it to a friendly message in `bootstrap/app.php`:

```php
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

->withExceptions(function (Exceptions $exceptions) {
    // ...your other render/respond callbacks...

    $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
        if ($exception instanceof PostTooLargeException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The uploaded file is too large.',
                    'errors' => ['file' => 'The uploaded file exceeds the server upload limit.'],
                ], 413);
            }

            return back()
                ->with('error', 'The uploaded file exceeds the server upload limit.')
                ->setStatusCode(413);
        }

        return $response;
    });
});
```

For Inertia, the JSON branch surfaces the message in `useForm().errors.file`; for plain HTML the redirect shows `flash('error')`.

---

## 10. Frontend (Inertia + Vue 3)

Page: `resources/js/Pages/Admin/Settings/Catalog.vue`. Two cards (**Export Catalog**, **Import Catalog**) plus a **Recent Jobs** table. Behavior:

- `router.post(route('...settings.catalog.export'))` starts an export.
- `useForm({ file: null }).post(route('...settings.catalog.import'))` uploads the file; `errors.file` is rendered under the input.
- A `setInterval` polls `GET ...settings.catalog.status` every **2.5 s** while any job is `queued`/`processing`, and refreshes the jobs list.
- Progress bar = `processed / (total || total_rows)`.
- Completed exports get a **Download ZIP** link to `...settings.catalog.download/{id}`.
- Failed jobs show `error`; imports with per-row `errors` show a toggleable `Row N: message` list.

The exact component is in the reference repo; copy it and swap the `route()` names for yours. (Requires Wayfinder route helpers or your own `route()` helper.)

---

## 11. Operations / Deployment

- **Queue driver**: `database` (`QUEUE_CONNECTION=database`). Ensure the `jobs`, `job_batches`, and `failed_jobs` tables exist (`php artisan queue:table` / the default migration).
- **A worker must be running**: `php artisan queue:work` (or supervisor/Horizon). If no worker runs, jobs stay `queued` and the UI shows them stuck at 0% — this is the first thing to check when nothing progresses.
- **Server upload limits**: the app accepts uploads up to 200 MB (`max:204800`), but PHP rejects the whole request *before* that rule runs if the server limits are lower. The server `php.ini` (CLI for `php artisan serve`, FPM for nginx/Apache) must allow:
  ```ini
  upload_max_filesize = 200M
  post_max_size = 210M
  ```
  `post_max_size` must be **larger** than `upload_max_filesize` (the whole multipart body counts).
- Files are written to `Storage::disk('local')` (`storage/app/private/catalog/...`):
  - Export working dir `catalog/exports/{id}/` (deleted after the ZIP is built)
  - Final ZIP `catalog/exports/catalog-exports-{id}.zip`
  - Import upload `catalog/imports/original/` and extracted working dir `catalog/imports/{id}/extracted/`
- Failure handling: exceptions mark the record `failed`, store the message in `error`, and are released/retried per Laravel's default queue behavior.

---

## 12. Security

| Concern | Mitigation |
|---|---|
| Unauthorized access | Routes behind your admin auth + RBAC middleware |
| Malicious ZIP (path traversal) | Reject entries containing `..`, starting with `/`, or drive-letter absolute paths; only read within the extracted root |
| Image filename traversal | `basename()` every referenced image filename before copying |
| Upload size | 200 MB limit (`max:204800`) + graceful `PostTooLargeException` handling |
| Remote URL fetch (export & import) | `@file_get_contents` with try/catch; failures skipped, never fatal |
| Job payload | Only model IDs / file paths are serialized — never secrets |

---

## 13. Testing (Pest)

Reference: `tests/Feature/CatalogTransferTest.php`. It creates an admin user with a `manage-catalog` permission (adapt to your RBAC), uses `Queue::fake()` for dispatch tests, and real jobs for integration tests.

| Test | Asserts |
|---|---|
| Export creates record + dispatches job | `Queue::fake()`; `catalog_exports` row exists; `ExportCatalogJob` pushed |
| Single-file export ZIP | Run job → ZIP contains only `products.csv` (no `categories.csv`) + image files; category names embedded in the row |
| Import ZIP | ZIP with `products.csv` + images → product created, referenced category auto-created, image copied |
| Import products CSV | Standalone CSV auto-detected as products → categories created/pivoted, existing SKUs skipped |
| Import categories CSV | Standalone CSV auto-detected as categories → created, duplicate slugs skipped |
| Import invalid rows | Reported in `errors` with row numbers, valid rows still imported |
| Round-trip | Export → import the ZIP → no duplicate SKUs |
| Status endpoint | GET `...settings.catalog.status` returns jobs JSON |
| Download | Completed export streams the ZIP; incomplete export returns 404 |
| Oversized file | >200 MB upload rejected with a `file` validation error |
| Server limit exceeded | Request with `CONTENT_LENGTH` past `post_max_size` gets the friendly 413 JSON |

```bash
php artisan test --compact --filter=CatalogTransferTest
```

> Round-trip and job tests must run real queue jobs; use `Queue::fake()` only where you just assert the dispatch. If you don't have a SQLite driver for your PHP version, run against a MySQL test database (env overrides on `DB_CONNECTION`/`DB_DATABASE` beat phpunit.xml unless it forces them).

---

## 14. Usage Walkthrough

### Export

1. Open the Catalog Import/Export settings page.
2. Click **Export Catalog**.
3. A new `CatalogExport` appears in the jobs list with status `queued` → `processing` and a live progress bar.
4. When status becomes `completed`, click **Download ZIP** to receive `catalog-exports-{id}.zip`.
5. Extract the ZIP to see `products.csv` (one row per product, categories included as columns) and an `images/` folder.

### Import

1. On the same page, upload a **ZIP** (`products.csv` + images) or a plain **CSV/XLSX** (products or categories — detected from the columns).
2. Submit — a `CatalogImport` appears with live progress (`processed / total`).
3. When completed, review the summary: imported, skipped (existing SKUs/slugs), and per-row errors.
4. Errors: fix the offending rows in the file and re-upload; valid rows are not re-imported if their SKU now exists (they're skipped as duplicates).

### Troubleshooting

| Symptom | Cause / fix |
|---|---|
| Job stuck at `queued` / 0% | No queue worker running → run `php artisan queue:work` (or supervisor) |
| Download missing on completed export | Re-check worker; inspect `catalog_exports.error` / `laravel.log` |
| Some images missing from ZIP | Remote URL unreachable at export time (skipped silently) or local file missing |
| Import shows skipped for everything | SKUs already exist (expected behavior) or header mismatch — verify column names match the spec |
| Single-file import fails to start | Headers don't include `Title`/`SKU` (products) or `Name` (categories) |
| `PostTooLargeException` on upload | `post_max_size` / `upload_max_filesize` below the file size — raise them (§11) |
| Bengali/UTF-8 garbled in Excel | File includes UTF-8 BOM; if opened without BOM support, re-save the CSV as UTF-8 |

---

## 15. FAQ / Edge Cases

- **Why is there no `categories.csv`?** Shopify-style: everything lives in one file. Categories are represented as `Category`/`Categories` **name columns** on each product row and are auto-created on import.
- **What about category metadata (description, parent, is_active)?** Not part of the product format — categories are identified and created by name only. If a category already exists, it's reused unchanged. (A standalone categories CSV with a `Name` header *does* support `Slug`, `Parent`, `Description`, `Is Active`, `Image`.)
- **Do variants get exported?** No — scope is product-level fields.
- **What happens to product images when a SKU is skipped?** Nothing — skipped rows never touch the database.
- **Can I import categories without products?** Yes — upload a CSV with a `Name` header; it's auto-detected and only categories are touched.
- **Can I export only a filtered set?** No — export always covers the full catalog.
- **Are remote (seeded) images handled?** Yes — they're downloaded into the ZIP at export time, so the ZIP contains real files, not URLs.
- **Large catalogs?** Chunking keeps memory flat; the only cost is total processing time, which runs in the background.
- **Import format XLSX?** Accepted, but cannot carry images (no ZIP). Use ZIP for image transfer.
