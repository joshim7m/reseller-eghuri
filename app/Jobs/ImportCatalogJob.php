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

    /**
     * @param  array<int, array{row: int, message: string}>  $errors
     */
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

    /**
     * @param  Collection<string, int|string>|EloquentCollection<string, int|string>  $seen
     */
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

    /**
     * @return list<string>
     */
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

    /**
     * Yields batches of up to 100 normalized rows: [['row' => array, 'row_number' => int], ...].
     *
     * @return \Generator<int, array<int, array{row: array<string, mixed>, row_number: int}>>
     */
    private function rowBatches(string $filePath, string $extension): \Generator
    {
        if (in_array($extension, ['xlsx', 'xls'], true)) {
            yield from $this->xlsxRowBatches($filePath);
        } else {
            yield from $this->csvRowBatches($filePath);
        }
    }

    /**
     * @return \Generator<int, array<int, array{row: array<string, mixed>, row_number: int}>>
     */
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

    /**
     * @return \Generator<int, array<int, array{row: array<string, mixed>, row_number: int}>>
     */
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

    /**
     * @return array<string, string>
     */
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
