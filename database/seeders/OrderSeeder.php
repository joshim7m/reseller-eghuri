<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $customer = User::where('email', 'rahim@example.com')->first();

        Order::whereIn('order_number', ['ORD-001', 'ORD-002', 'ORD-003', 'ORD-004', 'ORD-005'])->delete();

        $orders = [
            [
                'order_number' => 'ORD-001',
                'payment_status' => 'paid',
                'status' => 'completed',
                'payment_method' => 'credit_card',
                'items' => [
                    ['item_name' => 'Wireless Bluetooth Headphones', 'quantity' => 1, 'price' => 999],
                    ['item_name' => 'Smartphone Stand', 'quantity' => 2, 'price' => 1499],
                ],
                'paid_amount' => 2097,
            ],
            [
                'order_number' => 'ORD-002',
                'payment_status' => 'unpaid',
                'status' => 'processing',
                'payment_method' => 'bank_transfer',
                'items' => [
                    ['item_name' => 'Classic Denim Jacket', 'quantity' => 1, 'price' => 999],
                    ['item_name' => 'Running Sneakers', 'quantity' => 1, 'price' => 599],
                    ['item_name' => 'Ceramic Coffee Mug Set', 'quantity' => 2, 'price' => 1299],
                ],
                'paid_amount' => 300,
            ],
            [
                'order_number' => 'ORD-003',
                'payment_status' => 'unpaid',
                'status' => 'pending',
                'payment_method' => 'credit_card',
                'items' => [
                    ['item_name' => 'LED Desk Lamp', 'quantity' => 1, 'price' => 499],
                    ['item_name' => 'Wireless Bluetooth Headphones', 'quantity' => 1, 'price' => 999],
                ],
                'paid_amount' => 0,
            ],
            [
                'order_number' => 'ORD-004',
                'payment_status' => 'unpaid',
                'status' => 'processing',
                'payment_method' => 'cash_on_delivery',
                'items' => [
                    ['item_name' => 'Classic Denim Jacket (M / Blue)', 'quantity' => 1, 'price' => 999, 'variant_product' => 'Classic Denim Jacket', 'size' => 'M', 'color' => 'Blue'],
                    ['item_name' => 'Ceramic Coffee Mug Set (Assorted)', 'quantity' => 3, 'price' => 299, 'variant_product' => 'Ceramic Coffee Mug Set', 'size' => null, 'color' => 'Assorted'],
                ],
                'paid_amount' => 0,
            ],
            [
                'order_number' => 'ORD-005',
                'payment_status' => 'paid',
                'status' => 'completed',
                'payment_method' => 'credit_card',
                'items' => [
                    ['item_name' => 'Running Sneakers (9 / Black)', 'quantity' => 1, 'price' => 999, 'variant_product' => 'Running Sneakers', 'size' => '9', 'color' => 'Black'],
                    ['item_name' => 'Wireless Bluetooth Headphones (Black)', 'quantity' => 2, 'price' => 299, 'variant_product' => 'Wireless Bluetooth Headphones', 'size' => null, 'color' => 'Black'],
                ],
                'paid_amount' => 1997,
            ],
        ];

        $shippingAddress = '123 Main Street, Springfield, IL 62701, United States';

        foreach ($orders as $data) {
            $items = $data['items'];
            $paidAmount = $data['paid_amount'];
            unset($data['items'], $data['paid_amount']);

            $totalAmount = collect($items)->sum(fn ($i) => $i['price'] * $i['quantity']);

            $order = Order::create([
                'user_id' => $customer->id,
                'order_number' => $data['order_number'],
                'payment_status' => $data['payment_status'],
                'status' => $data['status'],
                'total_amount' => $totalAmount,
                'shipping_address' => $shippingAddress,
                'payment_method' => $data['payment_method'],
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'item_name' => $item['item_name'],
                    'quantity' => $item['quantity'],
                    'price_at_purchase' => $item['price'],
                    'total' => $item['price'] * $item['quantity'],
                    'product_variant_id' => isset($item['variant_product'])
                        ? $this->findVariantId($item['variant_product'], $item['size'] ?? null, $item['color'] ?? null)
                        : null,
                    'size' => $item['size'] ?? null,
                    'color' => $item['color'] ?? null,
                ]);
            }

            Invoice::create([
                'user_id' => $customer->id,
                'order_id' => $order->id,
                'invoice_number' => 'INV-'.str_pad($order->id, 3, '0', STR_PAD_LEFT),
                'invoice_date' => now(),
                'payment_method' => $data['payment_method'],
                'payment_status' => $paidAmount >= $totalAmount ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid'),
                'total_amount' => $totalAmount,
                'due_amount' => $totalAmount - $paidAmount,
                'paid_amount' => $paidAmount,
                'pdf_path' => null,
                'shipping_address' => null,
            ]);
        }
    }

    private function findVariantId(string $productName, ?string $size = null, ?string $color = null): ?int
    {
        $query = ProductVariant::query()->whereHas(
            'product',
            fn ($q) => $q->where('title', 'like', "%{$productName}%")
        );

        $variant = (clone $query)
            ->when($size !== null, fn ($q) => $q->where('size', $size))
            ->when($color !== null, fn ($q) => $q->where('color', $color))
            ->first();

        return $variant?->id ?? $query->first()?->id;
    }
}
