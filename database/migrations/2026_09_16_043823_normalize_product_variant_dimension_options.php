<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Canonicalize variant dimension option names/values so the same
     * dimension (e.g. "Ccolor"/"color"/"Size"/"size") maps to one entry.
     */
    public function up(): void
    {
        DB::table('product_variants')
            ->select('id', 'options')
            ->whereNotNull('options')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $options = json_decode($row->options, true);

                    if (! is_array($options)) {
                        continue;
                    }

                    $changed = false;

                    foreach ($options as &$option) {
                        if (! is_array($option) || ! isset($option['name'], $option['value'])) {
                            continue;
                        }

                        $name = $this->normalizeDimensionName($option['name']);
                        $value = trim((string) $option['value']);

                        if ($option['name'] !== $name) {
                            $option['name'] = $name;
                            $changed = true;
                        }

                        if ($option['value'] !== $value) {
                            $option['value'] = $value;
                            $changed = true;
                        }
                    }
                    unset($option);

                    if ($changed) {
                        DB::table('product_variants')
                            ->where('id', $row->id)
                            ->update(['options' => json_encode(array_values($options), JSON_UNESCAPED_UNICODE)]);
                    }
                }
            });
    }

    public function down(): void
    {
        //
    }

    private function normalizeDimensionName(string $name): string
    {
        $name = mb_strtolower(preg_replace('/\s+/', '', trim($name)));
        $name = preg_replace('/(.)\1+/u', '$1', $name) ?? $name;

        return match ($name) {
            'colour', 'coulour', 'ccolor' => 'color',
            default => $name,
        };
    }
};
