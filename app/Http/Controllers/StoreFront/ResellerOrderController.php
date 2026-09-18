<?php

namespace App\Http\Controllers\StoreFront;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ResellerOrder;
use App\Models\ResellerOrderItem;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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

        $pendingWithdrawalAmount = (float) Transaction::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->where('type', 'debit')
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

        $withdrawalMethods = PaymentMethod::active()
            ->where('type', 'withdrawal')
            ->orderBy('name')
            ->get()
            ->map(fn (PaymentMethod $method) => [
                'id' => $method->id,
                'name' => $method->name,
                'provider' => $method->provider,
                'account_number' => $method->account_number,
                'image_url' => $method->image_url,
            ]);

        return Inertia::render('StoreFront/ResellerOrders/Index', compact('orders', 'wallet', 'pendingBalance', 'cancelledBalance', 'pendingWithdrawalAmount', 'withdrawalMethods'));
    }

    public function create()
    {
        $this->authorizeReseller();

        $deliveryAreas = collect(json_decode((string) Setting::get('delivery_areas', '[]'), true))
            ->filter(fn ($area) => ! empty($area['name']))
            ->values()
            ->all();

        return Inertia::render('StoreFront/ResellerOrders/Create', compact('deliveryAreas'));
    }

    public function searchProducts(Request $request)
    {
        $q = $request->get('q');
        if (! $q || strlen($q) < 2) {
            return response()->json([]);
        }

        $products = Product::where('status', 'active')
            ->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhereHas('variants', fn ($variant) => $variant->where('sku', 'like', "%{$q}%"));
            })
            ->with('variants.image')
            ->take(10)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'sale_price' => $product->sale_price,
                'purchase_price' => $product->unit_price,
                'image' => $product->image_url,
                'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'unit_price' => $variant->unit_price,
                    'sale_price' => $variant->sale_price,
                    'options' => $variant->options ?? [],
                    'quantity' => $variant->quantity,
                    'image' => $variant->image?->image_url,
                ]),
            ]);

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $this->authorizeReseller();

        $configuredCharges = collect(json_decode((string) Setting::get('delivery_areas', '[]'), true))
            ->pluck('charge')
            ->map(fn ($charge) => (int) $charge)
            ->unique()
            ->values()
            ->all();

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'items.*.unit_price' => 'required|numeric|min:0|max:99999999',
            'items.*.sale_price' => 'required|numeric|min:0|max:99999999',
            'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.options' => 'nullable|array',
            'items.*.options.*.name' => 'required|string|max:50',
            'items.*.options.*.value' => 'required|string|max:50',
            'customer_name' => 'required|string|max:255',
            'mobile' => 'required|string|regex:/^01[3-9][0-9]{8}$/',
            'delivery_charge' => ['required', 'integer', Rule::in($configuredCharges ?: [50, 120])],
            'shipping_address' => 'required|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        foreach ($validated['items'] as $index => $item) {
            $variant = $item['variant_id'] ? ProductVariant::find($item['variant_id']) : null;

            $validated['items'][$index]['unit_price'] = $variant
                ? (float) $variant->unit_price
                : (float) (Product::find($item['product_id'])->unit_price ?? 0);
        }

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
                    'options' => $item['options'] ?? null,
                ]);
            }

            app(WalletService::class)->createPendingTransaction($order);

            return $order;
        });

        return redirect()->route('reseller-orders.index')
            ->with('success', "Reseller order {$order->order_number} placed successfully.");
    }

    public function withdraw(Request $request)
    {
        $this->authorizeReseller();

        $wallet = Wallet::where('user_id', auth()->id())->first();

        $methodNames = PaymentMethod::active()
            ->where('type', 'withdrawal')
            ->pluck('name');

        $validated = $request->validate([
            'amount' => 'required|numeric|min:500|max:99999999',
            'payment_method' => ['required', 'string', Rule::in($methodNames->all())],
            'account_number' => 'required|string|regex:/^01[3-9][0-9]{8}$/',
            'password' => ['required', 'current_password:web'],
        ]);

        $availableAt = app(WalletService::class)->withdrawalAvailableAt($request->user());

        if ($availableAt) {
            return back()->withErrors([
                'amount' => 'You can request one withdrawal every 24 hours. Your next withdrawal will be available at '.$availableAt->format('d M Y, h:i A').'.',
            ]);
        }

        if (! $wallet || $wallet->balance < $validated['amount']) {
            return back()->withErrors(['amount' => 'Withdrawal amount exceeds your wallet balance.']);
        }

        app(WalletService::class)->requestWithdrawal(
            auth()->user(),
            (float) $validated['amount'],
            $validated['payment_method'],
            $validated['account_number'],
        );

        return back()->with('success', 'Withdrawal request submitted. Balance held for review.');
    }

    public function show(ResellerOrder $resellerOrder)
    {
        abort_if($resellerOrder->user_id !== auth()->id(), 403);
        $resellerOrder->load('items.variant.image');

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

        $pendingWithdrawalAmount = (float) Transaction::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->where('type', 'debit')
            ->sum('amount');

        $transactions = Transaction::where('user_id', auth()->id())
            ->with('resellerOrder')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('StoreFront/ResellerTransactions/Index', compact('wallet', 'transactions', 'pendingBalance', 'cancelledBalance', 'pendingWithdrawalAmount'));
    }
}
