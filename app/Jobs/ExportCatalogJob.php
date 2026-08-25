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

    /**
     * @param  array<int, string>  $headers
     */
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
