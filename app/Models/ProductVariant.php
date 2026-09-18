<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'options',
        'sku',
        'unit_price',
        'sale_price',
        'quantity',
        'product_image_id',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'sale_price' => 'integer',
            'options' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'product_image_id');
    }
}
