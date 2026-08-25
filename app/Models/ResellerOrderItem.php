<?php

namespace App\Models;

use Database\Factories\ResellerOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerOrderItem extends Model
{
    /** @use HasFactory<ResellerOrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'reseller_order_id',
        'product_name',
        'product_id',
        'quantity',
        'unit_price',
        'sale_price',
        'total',
        'product_variant_id',
        'size',
        'color',
        'purchase_image_path',
    ];

    public function resellerOrder(): BelongsTo
    {
        return $this->belongsTo(ResellerOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
