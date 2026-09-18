<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    use WithoutModelEvents;

    private array $slugSet = [];

    public function run(): void
    {
        $path = database_path('catalog.json');

        if (! file_exists($path)) {
            $this->command?->error('catalog.json not found. Run: php artisan app:fetch-catalog');

            return;
        }

        $catalog = json_decode(file_get_contents($path), true);

        if (! is_array($catalog)) {
            $this->command?->error('Invalid catalog.json format.');

            return;
        }

        $this->seedCategories($catalog['categories'] ?? []);
        $this->seedProducts($catalog['products'] ?? []);
    }

    private function cleanSlug(string $text): string
    {
        $slug = str($text)
            ->lower()
            ->trim()
            ->replace("'", '')
            ->replace('&', 'and')
            ->replaceMatches('/[^\w\s-]/', '')
            ->replaceMatches('/[\s_]+/', '-')
            ->trim('-')
            ->replaceMatches('/-+/', '-')
            ->limit(200, '')
            ->toString();

        $base = $slug;
        $counter = 2;
        while (in_array($slug, $this->slugSet, true)) {
            $slug = $base.'-'.$counter++;
        }
        $this->slugSet[] = $slug;

        return $slug;
    }

    private function seedCategories(array $categories): void
    {
        foreach ($categories as $catData) {
            $parent = Category::create([
                'name' => $catData['name'],
                'slug' => $catData['slug'] ?? $this->cleanSlug($catData['name']),
                'image_path' => $catData['image'] ?? null,
                'description' => $catData['description'] ?? '',
                'is_active' => true,
            ]);

            foreach (($catData['subs'] ?? []) as $subData) {
                Category::create([
                    'parent_id' => $parent->id,
                    'name' => $subData['name'],
                    'slug' => $subData['slug'] ?? $this->cleanSlug($subData['name']),
                    'description' => $subData['description'] ?? '',
                    'is_active' => true,
                ]);
            }
        }
    }

    private function seedProducts(array $products): void
    {
        $categoryIds = Category::whereNotNull('parent_id')
            ->pluck('id');

        if ($categoryIds->isEmpty()) {
            return;
        }

        foreach ($products as $data) {
            $categoryId = $categoryIds->random();

            $name = $data['name'];
            $slug = $this->cleanSlug($name);

            $product = Product::create([
                'category_id' => $categoryId,
                'title' => $name,
                'slug' => $slug,
                'description' => $data['description'] ?? '',
                'unit_price' => $data['unitPrice'] ?? '',
                'sale_price' => $data['salePrice'] ?? '',
                'status' => 'active',
                'featured' => false,
                'quantity' => 10,
            ]);

            $images = $data['images'] ?? ($data['image'] ? [$data['image']] : []);
            foreach ($images as $imagePath) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $imagePath,
                ]);
            }
        }
    }
}
