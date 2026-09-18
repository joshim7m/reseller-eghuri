<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\User;
use App\Notifications\StockStatusChanged;

class ProductObserver
{
    public function updated(Product $product): void
    {
        $oldQuantity = $product->getOriginal('quantity');
        $newQuantity = $product->quantity;

        if ($oldQuantity === $newQuantity) {
            return;
        }

        $wasInStock = $oldQuantity > 0;
        $isInStock = $newQuantity > 0;

        if ($wasInStock === $isInStock) {
            return;
        }

        $status = $isInStock ? 'in_stock' : 'out_of_stock';

        $users = User::where('user_type', 'reseller')->get();

        foreach ($users as $user) {
            $user->notify(new StockStatusChanged($product, $status));
        }
    }
}
