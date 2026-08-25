<?php

namespace Database\Factories;

use App\Models\CatalogExport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogExport>
 */
class CatalogExportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => CatalogExport::STATUS_QUEUED,
            'total' => 0,
            'processed' => 0,
        ];
    }
}
