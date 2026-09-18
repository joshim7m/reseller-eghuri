<?php

namespace App\Http\Controllers\StoreFront;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function index()
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'You must be logged in to place an order.');
        }

        $user = auth()->user()->load('userDetail');

        return Inertia::render('StoreFront/Checkout/Index', [
            'user' => [
                'name' => $user->name,
                'mobile' => $user->userDetail?->mobile ?? '',
                'shipping_address' => $user->userDetail?->address ?? '',
            ],
        ]);
    }

    public function store(Request $request)
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'You must be logged in to place an order.');
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.options' => 'nullable|array',
            'items.*.options.*.name' => 'required|string|max:50',
            'items.*.options.*.value' => 'required|string|max:50',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'items.*.price' => 'required|numeric|min:0|max:99999999',
            'payment_method' => 'required|string|in:cod,bkash,nagad',
            'shipping_address' => 'required|string|min:12|max:60',
            'name' => 'required|string|min:3|max:30',
            'mobile' => ['required', 'string', 'regex:/^(013|014|015|016|017|018|019)\d{8}$/'],
            'delivery_charge' => 'required|integer|in:50,120',
        ], [
            'items.required' => 'Your cart is empty. Add items before checking out.',
            'items.*.quantity.max' => 'Maximum 100 units per item allowed.',
            'shipping_address.min' => 'Please provide a complete shipping address (min 12 characters).',
            'shipping_address.max' => 'Shipping address must not exceed 60 characters.',
            'name.min' => 'Name must be at least 3 characters.',
            'name.max' => 'Name must not exceed 30 characters.',
            'mobile.regex' => 'Enter a valid Bangladeshi mobile number.',
            'delivery_charge.in' => 'Invalid delivery charge selected.',
        ]);

        $order = DB::transaction(function () use ($validated) {
            $totalAmount = collect($validated['items'])->sum(fn ($item) => $item['price'] * $item['quantity']);

            do {
                $orderNumber = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            } while (Order::where('order_number', $orderNumber)->exists());

            $deliveryCharge = (int) $validated['delivery_charge'];

            $order = Order::create([
                'user_id' => auth()->id(),
                'order_number' => $orderNumber,
                'payment_status' => 'unpaid',
                'status' => 'pending',
                'total_amount' => $totalAmount + $deliveryCharge,
                'delivery_charge' => $deliveryCharge,
                'shipping_address' => $validated['shipping_address'],
                'payment_method' => $validated['payment_method'],
            ]);

            foreach ($validated['items'] as $item) {
                if (! empty($item['variant_id'])) {
                    $variant = ProductVariant::findOrFail($item['variant_id']);
                    if ($variant->quantity < $item['quantity']) {
                        throw new \RuntimeException("Insufficient stock for {$item['item_name']}.");
                    }
                    $variant->decrement('quantity', $item['quantity']);
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'item_name' => $item['item_name'],
                    'quantity' => $item['quantity'],
                    'price_at_purchase' => $item['price'],
                    'total' => $item['price'] * $item['quantity'],
                    'product_variant_id' => $item['variant_id'] ?: null,
                    'options' => $item['options'] ?? null,
                ]);
            }

            Invoice::create([
                'user_id' => auth()->id(),
                'order_id' => $order->id,
                'invoice_number' => 'INV-'.str_pad($order->id, 3, '0', STR_PAD_LEFT),
                'invoice_date' => now(),
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'unpaid',
                'total_amount' => $totalAmount + $deliveryCharge,
                'due_amount' => $totalAmount + $deliveryCharge,
                'paid_amount' => 0,
                'pdf_path' => null,
                'shipping_address' => null,
            ]);

            return $order;
        });

        return redirect()->route('checkout.thank-you', $order)
            ->with('success', 'Order placed successfully.');
    }

    public function thankYou(Order $order)
    {
        abort_if($order->user_id !== auth()->id(), 403);

        return Inertia::render('StoreFront/Checkout/ThankYou', compact('order'));
    }

    public function orders()
    {
        $orders = Order::where('user_id', auth()->id())
            ->with('items')
            ->latest()
            ->paginate(10);

        return Inertia::render('StoreFront/UserProfile/Orders', compact('orders'));
    }

    public function show(Order $order)
    {
        abort_if($order->user_id !== auth()->id(), 403);

        $order->load('items.variant.product.images', 'invoice');

        if ($order->invoice && $order->invoice->payment_status === 'paid' && $order->invoice->due_amount > 0) {
            $total = (int) $order->total_amount;
            $order->invoice->update([
                'paid_amount' => $total,
                'due_amount' => 0,
            ]);
            $order->refresh();
        }

        return Inertia::render('StoreFront/UserProfile/Show', compact('order'));
    }

    public function downloadInvoice(Order $order)
    {
        abort_if($order->user_id !== auth()->id(), 403);

        $order->load('items', 'invoice', 'user');

        $html = view('storefront.user-profile.invoice-pdf', compact('order'))->render();

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-'.$order->invoice->invoice_number.'.pdf"',
        ]);
    }
}
