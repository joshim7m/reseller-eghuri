<?php

namespace Database\Seeders;

use App\Models\ResellerOrder;
use App\Models\ResellerOrderItem;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ResellerOrderSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $resellers = User::where('user_type', 'reseller')->get();

        if ($resellers->isEmpty()) {
            return;
        }

        $templates = [
            [
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

        $dates = $this->orderDates();

        $orders = [];
        foreach ($dates as $date) {
            $template = $templates[array_rand($templates)];
            $orders[] = [
                'template' => $template,
                'created_at' => $date,
            ];
        }

        foreach ($orders as $index => $order) {
            $reseller = $resellers[$index % $resellers->count()];
            $template = $order['template'];
            $items = $template['items'];

            $totalAmount = collect($items)->sum(fn ($i) => $i['unit_price'] * $i['quantity']);
            $deliveryCharge = $totalAmount > 50000 ? 0 : 3000;

            $resellerOrder = ResellerOrder::create([
                'user_id' => $reseller->id,
                'order_number' => 'RSL-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'status' => $template['status'],
                'payment_method' => $template['payment_method'],
                'payment_status' => $template['payment_status'],
                'notes' => $template['notes'],
                'total_amount' => $totalAmount + $deliveryCharge,
                'delivery_charge' => $deliveryCharge,
                'shipping_address' => $shippingAddress,
            ]);

            $resellerOrder->created_at = $order['created_at'];
            $resellerOrder->save();

            foreach ($items as $item) {
                $unitPrice = $item['unit_price'];
                $salePrice = $item['sale_price'] ?? $unitPrice;
                ResellerOrderItem::create([
                    'reseller_order_id' => $resellerOrder->id,
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'sale_price' => $salePrice,
                    'total' => $unitPrice * $item['quantity'],
                ]);
            }
        }
    }

    /**
     * Build 22 created_at timestamps: 5 today, 7 later this week, 10
     * earlier this month, so the Week/Month report counts are cumulative.
     *
     * @return list<Carbon>
     */
    private function orderDates(): array
    {
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $weekStart = $now->copy()->startOfWeek(Carbon::SUNDAY);
        $monthStart = $now->copy()->startOfMonth();

        $dates = [];

        for ($i = 0; $i < 5; $i++) {
            $dates[] = $this->randomTime($todayStart, $now);
        }

        $weekUpper = $todayStart->copy()->subSecond();
        for ($i = 0; $i < 7; $i++) {
            $dates[] = $this->randomTime($weekStart, $weekUpper);
        }

        $monthUpper = $weekStart->copy()->subSecond();
        for ($i = 0; $i < 10; $i++) {
            $dates[] = $this->randomTime($monthStart, $monthUpper);
        }

        return $dates;
    }

    private function randomTime(Carbon $from, Carbon $to): Carbon
    {
        if ($from->greaterThanOrEqualTo($to)) {
            return $from->copy();
        }

        $min = $from->getTimestamp();
        $max = $to->getTimestamp();

        return Carbon::createFromTimestamp(random_int($min, $max));
    }
}
