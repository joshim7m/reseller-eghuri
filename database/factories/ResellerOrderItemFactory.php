<?php

namespace Database\Factories;

use App\Models\ResellerOrder;
use App\Models\ResellerOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResellerOrderItem>
 */
class ResellerOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(100, 500);
        $salePrice = $unitPrice + fake()->numberBetween(10, 100);
        $quantity = fake()->numberBetween(1, 5);

        return [
            'reseller_order_id' => ResellerOrder::factory(),
            'product_name' => fake()->words(3, true),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'sale_price' => $salePrice,
            'total' => $salePrice * $quantity,
            'size' => null,
            'color' => null,
        ];
    }
}
