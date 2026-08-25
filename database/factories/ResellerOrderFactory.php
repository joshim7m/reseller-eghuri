<?php

namespace Database\Factories;

use App\Models\ResellerOrder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResellerOrder>
 */
class ResellerOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(function () {
                $role = Role::firstOrCreate(['slug' => 'reseller'], ['name' => 'Reseller']);

                return ['user_type' => 'reseller', 'status' => true, 'role_id' => $role->id];
            }),
            'customer_name' => fake()->name(),
            'mobile' => fake()->numerify('01#########'),
            'order_number' => 'RSL-'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'status' => 'pending',
            'total_amount' => 0,
            'delivery_charge' => 50,
            'shipping_address' => fake()->address(),
            'payment_method' => 'cash-on',
            'payment_status' => 'unpaid',
        ];
    }

    /**
     * Indicate the order is completed and paid (wallet credit released).
     */
    public function completedPaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Indicate the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
