<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StockStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public Product $product,
        public string $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $productImage = $this->product->images->first()?->image_path;

        return [
            'product_id' => $this->product->id,
            'product_title' => $this->product->title,
            'product_slug' => $this->product->slug,
            'product_image' => $productImage ? asset($productImage) : null,
            'status' => $this->status,
            'message' => $this->status === 'in_stock'
                ? "{$this->product->title} is now in stock!"
                : "{$this->product->title} is now out of stock.",
            'link' => route('product.show', $this->product->slug),
        ];
    }
}
