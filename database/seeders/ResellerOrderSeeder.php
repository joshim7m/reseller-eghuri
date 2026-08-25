<?php

namespace Database\Seeders;

use App\Models\ResellerOrder;
use App\Models\ResellerOrderItem;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ResellerOrderSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $resellers = User::where('user_type', 'reseller')->get();

        if ($resellers->isEmpty()) {
            return;
        }

        $orders = [
            [
                'order_number' => 'RSL-001',
                'status' => 'completed',
                'payment_method' => 'bank_transfer',
                'payment_status' => 'paid',
                'notes' => 'Bulk order for upcoming season.',
                'items' => [
                    ['product_name' => 'Bulk Cotton T-Shirt - White (M)', 'quantity' => 2, 'unit_price' => 500, 'sale_price' => 600],
                    ['product_name' => 'Bulk Cotton T-Shirt - Black (M)', 'quantity' => 2, 'unit_price' => 500, 'sale_price' => 600],
                ],
            ],
            [
                'order_number' => 'RSL-002',
                'status' => 'processing',
                'payment_method' => 'cash-on',
                'payment_status' => 'unpaid',
                'notes' => 'Rush delivery requested.',
                'items' => [
                    ['product_name' => 'Wholesale Polo Shirt - Navy (L)', 'quantity' => 3, 'unit_price' => 450, 'sale_price' => 500],
                    ['product_name' => 'Wholesale Polo Shirt - Navy (XL)', 'quantity' => 2, 'unit_price' => 350, 'sale_price' => 500],
                ],
            ],
            [
                'order_number' => 'RSL-003',
                'status' => 'pending',
                'payment_method' => 'credit_card',
                'payment_status' => 'unpaid',
                'notes' => null,
                'items' => [
                    ['product_name' => 'Reseller Pack - Assorted Socks (50 Pairs)', 'quantity' => 1, 'unit_price' => 450, 'sale_price' => 450],
                    ['product_name' => 'Bulk Cotton T-Shirt - White (L)', 'quantity' => 1, 'unit_price' => 450, 'sale_price' => 500],
                    ['product_name' => 'Bulk Cotton T-Shirt - Black (L)', 'quantity' => 3, 'unit_price' => 400, 'sale_price' => 500],
                ],
            ],
            [
                'order_number' => 'RSL-004',
                'status' => 'completed',
                'payment_method' => 'bank_transfer',
                'payment_status' => 'paid',
                'notes' => 'Second order this month.',
                'items' => [
                    ['product_name' => 'Bulk Order - Cotton Nighty (Pack of 12) (M)', 'quantity' => 2, 'unit_price' => 600, 'sale_price' => 620],
                    ['product_name' => 'Bulk Order - Cotton Nighty (Pack of 12) (L)', 'quantity' => 1, 'unit_price' => 300, 'sale_price' => 350],
                ],
            ],
            [
                'order_number' => 'RSL-005',
                'status' => 'cancelled',
                'payment_method' => 'cash-on',
                'payment_status' => 'unpaid',
                'notes' => 'Cancelled due to stock shortage.',
                'items' => [
                    ['product_name' => 'Wholesale Polo Shirt - Navy (M)', 'quantity' => 2, 'unit_price' => 500, 'sale_price' => 520],
                ],
            ],
        ];

        $shippingAddress = 'House 12, Road 5, Gulshan-1, Dhaka 1212';

        foreach ($resellers as $reseller) {
            foreach ($orders as $data) {
                $items = $data['items'];
                unset($data['items']);
                $data['order_number'] = $data['order_number'].'-'.$reseller->id;

                $totalAmount = collect($items)->sum(fn ($i) => $i['unit_price'] * $i['quantity']);
                $deliveryCharge = $totalAmount > 50000 ? 0 : 3000;

                $data['total_amount'] = $totalAmount + $deliveryCharge;
                $data['delivery_charge'] = $deliveryCharge;

                $order = ResellerOrder::create(array_merge($data, [
                    'user_id' => $reseller->id,
                    'shipping_address' => $shippingAddress,
                ]));

                foreach ($items as $item) {
                    $unitPrice = $item['unit_price'];
                    $salePrice = $item['sale_price'] ?? $unitPrice;
                    ResellerOrderItem::create([
                        'reseller_order_id' => $order->id,
                        'product_name' => $item['product_name'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $unitPrice,
                        'sale_price' => $salePrice,
                        'total' => $unitPrice * $item['quantity'],
                    ]);
                }
            }
        }
    }
}
