<?php

namespace Database\Factories;

use App\Models\CatalogImport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogImport>
 */
class CatalogImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => CatalogImport::STATUS_QUEUED,
            'original_name' => 'catalog.csv',
            'file_path' => 'catalog/imports/original/catalog.csv',
            'total_rows' => 0,
            'processed' => 0,
            'imported' => 0,
            'skipped' => 0,
        ];
    }
}
