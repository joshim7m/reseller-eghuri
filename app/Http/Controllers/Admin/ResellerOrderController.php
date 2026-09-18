<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ResellerOrder;
use App\Models\Setting;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    public function report(Request $request)
    {
        $from = $request->date('from') ? $request->date('from')->startOfDay() : now()->subMonth();
        $to = $request->date('to') ? $request->date('to')->endOfDay() : now();

        $orders = ResellerOrder::with('items.variant')
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get()
            ->map(function (ResellerOrder $order) {
                return [
                    ...$order->only(['id', 'order_number', 'customer_name', 'mobile', 'shipping_address', 'total_amount', 'delivery_charge', 'status', 'created_at', 'notes']),
                    'quantity' => $order->items->sum('quantity'),
                    'items' => $order->items->map(fn ($item) => [
                        'id' => $item->id,
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'sale_price' => $item->sale_price,
                        'options' => $item->options,
                    ]),
                ];
            });

        return Inertia::render('Admin/ResellerOrders/Report', [
            'orders' => $orders,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function exportReport(Request $request)
    {
        $from = $request->date('from') ? $request->date('from')->startOfDay() : now()->subMonth();
        $to = $request->date('to') ? $request->date('to')->endOfDay() : now();

        $query = ResellerOrder::with('items')
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['pending', 'processing', 'completed']);

        if ($request->filled('ids')) {
            $query->whereIn('id', (array) $request->input('ids'));
        }

        $orders = $query->latest()->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Courier Report');

        $headers = ['Invoice', 'Name', 'Address', 'Phone', 'Amount', 'Note', 'Lot', 'Contact Name', 'Contact Number'];
        $sheet->fromArray($headers, null, 'A1');

        $companyName = Setting::get('company_name', '');
        $companyMobile = (string) Setting::get('company_mobile', '');

        $row = 2;
        foreach ($orders as $order) {
            $invoiceParts = $order->items->map(function ($item) {
                $variantOpts = collect($item->options ?? [])->pluck('value')->implode(' / ');

                return trim(implode(' ', array_filter([$item->product_name, (string) $variantOpts])));
            });
            $invoice = $invoiceParts->implode(', ');

            $amount = $order->items->sum(fn ($item) => ($item->sale_price ?? $item->unit_price) * $item->quantity) + $order->delivery_charge;

            $sheet->fromArray([
                $invoice,
                $order->customer_name ?? '',
                $order->shipping_address ?? '',
                $order->mobile ?? '',
                $amount,
                'null',
                '',
                $companyName,
                $companyMobile,
            ], null, "A{$row}");

            $sheet->getCell("D{$row}")->setValueExplicit($order->mobile ?? '', DataType::TYPE_STRING);

            $row++;
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'courier-report-'.now()->format('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
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
            'payment_status' => 'nullable|in:unpaid,paid',
            'total_amount' => 'required|numeric|min:0',
            'delivery_charge' => 'required|numeric|min:0',
            'shipping_address' => 'nullable|string|max:1000',
            'items' => 'nullable|array',
            'items.*.id' => 'required_with:items|exists:reseller_order_items,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ]);

        $paymentStatus = $validated['status'] === 'completed'
            ? 'paid'
            : ($validated['payment_status'] ?? $resellerOrder->payment_status);

        $resellerOrder->update([
            'status' => $validated['status'],
            'total_amount' => $validated['total_amount'],
            'delivery_charge' => $validated['delivery_charge'],
            'shipping_address' => $validated['shipping_address'],
            'payment_status' => $paymentStatus,
        ]);

        if (! empty($validated['items'])) {
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

        $resellerOrder->update([
            'status' => $validated['status'],
            'payment_status' => $validated['status'] === 'completed' ? 'paid' : $resellerOrder->payment_status,
        ]);

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
