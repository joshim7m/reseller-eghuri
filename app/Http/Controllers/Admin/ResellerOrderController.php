<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ResellerOrder;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResellerOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = ResellerOrder::with('user.userDetail');

        if ($search = $request->get('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('userDetail', fn ($cq) => $cq->where('mobile', 'like', "%{$search}%"));
            });
        }

        $orders = $query->latest()
            ->get()
            ->groupBy(fn ($o) => $o->created_at->format('Y-m-d'))
            ->map(fn ($dateGroup) => $dateGroup->groupBy('user_id'))
            ->map(function ($userGroups, $date) {
                return [
                    'date' => $date,
                    'groups' => $userGroups->values()->map(function ($userOrders) {
                        $first = $userOrders->first();

                        return [
                            'user_id' => $first->user_id,
                            'user_name' => $first->user->name ?? 'N/A',
                            'company' => $first->user?->userDetail?->company,
                            'count' => $userOrders->count(),
                            'total' => (float) $userOrders->sum('total_amount'),
                        ];
                    })->all(),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('Admin/ResellerOrders/Index', compact('orders'));
    }

    public function byUser(User $user, string $date)
    {
        $orders = ResellerOrder::with('user.userDetail', 'items.product.images', 'items.variant.product.images')
            ->where('user_id', $user->id)
            ->whereDate('created_at', $date)
            ->latest()
            ->get();

        abort_if($orders->isEmpty(), 404);

        return Inertia::render('Admin/ResellerOrders/ByUser', compact('orders', 'user', 'date'));
    }

    public function edit(ResellerOrder $resellerOrder)
    {
        $resellerOrder->load('user.userDetail', 'items.product.images', 'items.variant.product.images');
        return Inertia::render('Admin/ResellerOrders/Edit', compact('resellerOrder'));
    }

    public function update(Request $request, ResellerOrder $resellerOrder)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled,returned',
            'total_amount' => 'required|numeric|min:0',
            'delivery_charge' => 'required|numeric|min:0',
            'shipping_address' => 'nullable|string|max:1000',
            'items' => 'nullable|array',
            'items.*.id' => 'required_with:items|exists:reseller_order_items,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ]);

        $paymentStatus = $validated['status'] === 'completed' ? 'paid' : $resellerOrder->payment_status;

        $resellerOrder->update([
            'status' => $validated['status'],
            'total_amount' => $validated['total_amount'],
            'delivery_charge' => $validated['delivery_charge'],
            'shipping_address' => $validated['shipping_address'],
            'payment_status' => $paymentStatus,
        ]);

        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $itemData) {
                $resellerOrder->items()->where('id', $itemData['id'])->update([
                    'quantity' => $itemData['quantity'],
                ]);
            }
        }

        $this->handleWalletUpdate($resellerOrder);

        return redirect()->route('admin.reseller-orders.index')
            ->with('success', 'Order "'.$resellerOrder->order_number.'" updated successfully.');
    }

    public function updateStatus(Request $request, ResellerOrder $resellerOrder)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled,returned',
        ]);

        $resellerOrder->update(['status' => $validated['status']]);

        $this->handleWalletUpdate($resellerOrder);

        return redirect()->back()
            ->with('success', 'Order "'.$resellerOrder->order_number.'" status updated to "'.$validated['status'].'".');
    }

    public function updatePaymentStatus(Request $request, ResellerOrder $resellerOrder)
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:unpaid,paid',
        ]);

        $resellerOrder->update(['payment_status' => $validated['payment_status']]);

        $this->handleWalletUpdate($resellerOrder);

        return redirect()->back()
            ->with('success', 'Order "'.$resellerOrder->order_number.'" payment status updated to "'.$validated['payment_status'].'".');
    }

    private function handleWalletUpdate(ResellerOrder $order): void
    {
        $walletService = app(WalletService::class);

        if ($order->status === 'completed' && $order->payment_status === 'paid') {
            $walletService->completeTransaction($order);
        } elseif ($order->status === 'cancelled' || $order->status === 'returned') {
            $walletService->cancelTransaction($order);
        }
    }
}
