<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $appends = ['image_url', 'total_stock'];

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'description',
        'specification',
        'unit_price',
        'sale_price',
        'sku',
        'quantity',
        'status',
        'featured',
        'meta_title',
        'meta_description',
        'youtube_url',
    ];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->title);
            }
        });
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /** @return HasMany<ProductImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        $first = $this->images->first();

        return $first?->image_path ? asset($first->image_path) : null;
    }

    public function getTotalStockAttribute(): int
    {
        return $this->variants->isNotEmpty() ? $this->variants->sum('quantity') : ($this->quantity ?? 0);
    }
}
