<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            ['name' => 'Bkash', 'provider' => 'Bkash Ltd', 'image' => '', 'type' => 'withdrawal', 'account_number' => '01735364877', 'status' => 'active'],
            ['name' => 'Bkash', 'provider' => 'Bkash Ltd', 'image' => '', 'type' => 'ecommerce', 'account_number' => '01735364877', 'status' => 'active'],
            ['name' => 'Nagad', 'provider' => 'Nagad Ltd', 'image' => '', 'type' => 'withdrawal', 'account_number' => '01735364866', 'status' => 'active'],
            ['name' => 'Nagad', 'provider' => 'Nagad Ltd', 'image' => '', 'type' => 'ecommerce', 'account_number' => '01735364866', 'status' => 'active'],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['account_number' => $method['account_number'], 'type' => $method['type']],
                $method
            );
        }
    }
}
