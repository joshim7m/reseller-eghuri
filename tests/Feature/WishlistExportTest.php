<?php

use App\Models\Product;
use App\Models\ProductImage;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

function readExportSheet(string $contents): Worksheet
{
    $file = tempnam(sys_get_temp_dir(), 'wishlist_test');

    file_put_contents($file, $contents);

    try {
        return (new Xlsx)->load($file)->getActiveSheet();
    } finally {
        @unlink($file);
    }
}

it('exports the wishlist products to an excel file', function () {
    $product = Product::create([
        'title' => 'Wishlist Product',
        'slug' => 'wishlist-product',
        'unit_price' => 500,
        'sale_price' => 450,
        'quantity' => 12,
        'status' => 'active',
    ]);

    $response = $this->post(route('wishlist.export'), ['ids' => [$product->id]])
        ->assertOk()
        ->assertDownload('wishlist.xlsx');

    $sheet = readExportSheet($response->streamedContent());

    expect($sheet->getCell('A2')->getValue())->toBe(1)
        ->and($sheet->getCell('C2')->getValue())->toBe('Wishlist Product')
        ->and($sheet->getCell('F2')->getValue())->toBe(450)
        ->and($sheet->getCell('G2')->getValue())->toBe(12);
});

it('keeps the exported rows in the order the ids were sent', function () {
    $first = Product::create([
        'title' => 'First Product',
        'slug' => 'first-product',
        'unit_price' => 100,
        'sale_price' => 100,
        'status' => 'active',
    ]);
    $second = Product::create([
        'title' => 'Second Product',
        'slug' => 'second-product',
        'unit_price' => 200,
        'sale_price' => 200,
        'status' => 'active',
    ]);

    $response = $this->post(route('wishlist.export'), ['ids' => [$second->id, $first->id]])
        ->assertOk();

    $sheet = readExportSheet($response->streamedContent());

    expect($sheet->getCell('A2')->getValue())->toBe(1)
        ->and($sheet->getCell('C2')->getValue())->toBe('Second Product')
        ->and($sheet->getCell('A3')->getValue())->toBe(2)
        ->and($sheet->getCell('C3')->getValue())->toBe('First Product');
});

it('validates the wishlist export ids', function () {
    $this->post(route('wishlist.export'), ['ids' => []])
        ->assertSessionHasErrors('ids');

    $this->post(route('wishlist.export'), ['ids' => [999999]])
        ->assertSessionHasErrors('ids.0');
});

it('embeds the first product image into the excel file', function () {
    $relative = 'images/test-exports/export-image.png';
    $absolute = public_path($relative);

    if (! is_dir(dirname($absolute))) {
        mkdir(dirname($absolute), 0755, true);
    }

    file_put_contents($absolute, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));

    $product = Product::create([
        'title' => 'Image Product',
        'slug' => 'image-product',
        'unit_price' => 100,
        'sale_price' => 100,
        'status' => 'active',
    ]);

    ProductImage::create([
        'product_id' => $product->id,
        'image_path' => $relative,
        'sort_order' => 1,
    ]);

    try {
        $response = $this->post(route('wishlist.export'), ['ids' => [$product->id]])
            ->assertOk();

        $file = tempnam(sys_get_temp_dir(), 'wishlist_test');
        file_put_contents($file, $response->streamedContent());

        try {
            $spreadsheet = (new Xlsx)->load($file);

            expect($spreadsheet->getActiveSheet()->getDrawingCollection())->toHaveCount(1);
        } finally {
            @unlink($file);
        }
    } finally {
        @unlink($absolute);
    }
});
