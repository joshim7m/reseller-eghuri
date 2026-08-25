<?php

namespace App\Http\Controllers\StoreFront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ResellerOrder;
use App\Models\ResellerOrderItem;
use App\Models\Transaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ResellerOrderController extends Controller
{
    private function authorizeReseller(): void
    {
        if (auth()->user()->user_type !== 'reseller') {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->authorizeReseller();
        $wallet = auth()->user()->wallet;

        $pendingBalance = Transaction::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->where('type', 'credit')
            ->sum('amount');

        $cancelledBalance = Transaction::where('user_id', auth()->id())
            ->where('status', 'cancelled')
            ->where('type', 'credit')
            ->sum('amount');

        $orders = ResellerOrder::where('user_id', auth()->id())
            ->with('items')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('order_number', 'like', '%'.$request->search.'%');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('StoreFront/ResellerOrders/Index', compact('orders', 'wallet', 'pendingBalance', 'cancelledBalance'));
    }

    public function create()
    {
        $this->authorizeReseller();

        return Inertia::render('StoreFront/ResellerOrders/Create');
    }

    public function searchProducts(Request $request)
    {
        $q = $request->get('q');
        if (! $q || strlen($q) < 2) {
            return response()->json([]);
        }

        $products = Product::where('status', 'active')
            ->where('title', 'like', "%{$q}%")
            ->with('variants')
            ->take(10)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'sale_price' => $product->sale_price,
                'purchase_price' => $product->unit_price,
                'image' => $product->image_url,
                'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                    'id' => $variant->id,
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'quantity' => $variant->quantity,
                ]),
            ]);

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $this->authorizeReseller();
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'items.*.unit_price' => 'required|numeric|min:0|max:99999999',
            'items.*.sale_price' => 'required|numeric|min:0|max:99999999|gte:items.*.unit_price',
            'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.size' => 'nullable|string|max:50',
            'items.*.color' => 'nullable|string|max:50',
            'customer_name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'delivery_charge' => 'required|integer|in:50,120',
            'shipping_address' => 'required|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $order = DB::transaction(function () use ($validated) {
            $itemsTotal = collect($validated['items'])->sum(fn ($item) => $item['sale_price'] * $item['quantity']);
            $totalAmount = $itemsTotal + $validated['delivery_charge'];

            do {
                $orderNumber = 'RSL-'.str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            } while (ResellerOrder::where('order_number', $orderNumber)->exists());

            $order = ResellerOrder::create([
                'user_id' => auth()->id(),
                'customer_name' => $validated['customer_name'],
                'mobile' => $validated['mobile'],
                'order_number' => $orderNumber,
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'delivery_charge' => $validated['delivery_charge'],
                'shipping_address' => $validated['shipping_address'],
                'payment_method' => 'cash-on',
                'payment_status' => 'unpaid',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $unitPrice = $item['unit_price'];
                $salePrice = $item['sale_price'];

                ResellerOrderItem::create([
                    'reseller_order_id' => $order->id,
                    'product_name' => $item['product_name'],
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'sale_price' => $salePrice,
                    'total' => $salePrice * $item['quantity'],
                    'product_variant_id' => $item['variant_id'] ?: null,
                    'size' => $item['size'] ?: null,
                    'color' => $item['color'] ?: null,
                ]);
            }

            app(WalletService::class)->createPendingTransaction($order);

            return $order;
        });

        return redirect()->route('reseller-orders.index')
            ->with('success', "Reseller order {$order->order_number} placed successfully.");
    }

    public function show(ResellerOrder $resellerOrder)
    {
        abort_if($resellerOrder->user_id !== auth()->id(), 403);
        $resellerOrder->load('items', 'transaction');

        return Inertia::render('StoreFront/ResellerOrders/Show', compact('resellerOrder'));
    }

    public function transactions(Request $request)
    {
        $this->authorizeReseller();
        $wallet = auth()->user()->wallet;

        $pendingBalance = Transaction::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->where('type', 'credit')
            ->sum('amount');

        $cancelledBalance = Transaction::where('user_id', auth()->id())
            ->where('status', 'cancelled')
            ->where('type', 'credit')
            ->sum('amount');

        $transactions = Transaction::where('user_id', auth()->id())
            ->with('resellerOrder')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('StoreFront/ResellerTransactions/Index', compact('wallet', 'transactions', 'pendingBalance', 'cancelledBalance'));
    }
}
