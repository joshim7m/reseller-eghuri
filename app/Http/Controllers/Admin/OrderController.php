<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = Order::with('user.userDetail', 'items', 'invoice');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('user.userDetail', fn ($dq) => $dq->where('mobile', 'like', "%{$search}%"))
                    ->orWhereHas('items.variant', fn ($vq) => $vq->where('sku', 'like', "%{$search}%"));
            });
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Orders/Index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load('user.userDetail', 'items.variant.image', 'items.variant.product.images', 'invoice');

        if ($order->invoice && $order->invoice->payment_status === 'paid' && $order->invoice->due_amount > 0) {
            $total = (int) $order->total_amount;
            $order->invoice->update([
                'paid_amount' => $total,
                'due_amount' => 0,
            ]);
            $order->refresh();
        }

        return Inertia::render('Admin/Orders/Show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled',
        ]);

        $order->update(['status' => $validated['status']]);

        if ($order->invoice && $validated['status'] === 'cancelled') {
            $order->invoice->update(['payment_status' => 'unpaid']);
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Order #'.$order->order_number.' status updated to "'.$validated['status'].'".');
    }

    public function issueInvoice(Request $request, Order $order)
    {
        if ($order->invoice) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Invoice already exists for order #'.$order->order_number.'.');
        }

        $validated = $request->validate([
            'payment_method' => 'required|string|max:255',
            'paid_amount' => 'required|integer|min:0|lte:'.(int) $order->total_amount,
        ], [
            'paid_amount.lte' => 'Paid amount cannot exceed the order total of ৳'.number_format($order->total_amount, 2).'.',
        ]);

        $invoice = DB::transaction(function () use ($order, $validated) {
            $paidAmount = $validated['paid_amount'];
            $totalAmount = (int) $order->total_amount;

            $paymentStatus = match (true) {
                $paidAmount >= $totalAmount => 'paid',
                $paidAmount > 0 => 'partial',
                default => 'unpaid',
            };

            $order->update(['payment_status' => $paymentStatus === 'paid' ? 'paid' : 'unpaid']);

            return Invoice::create([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'invoice_number' => 'INV-'.str_pad($order->id, 3, '0', STR_PAD_LEFT),
                'invoice_date' => now(),
                'payment_method' => $validated['payment_method'],
                'payment_status' => $paymentStatus,
                'total_amount' => $totalAmount,
                'due_amount' => $totalAmount - $paidAmount,
                'paid_amount' => $paidAmount,
                'pdf_path' => null,
                'shipping_address' => null,
            ]);
        });

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Invoice #'.$invoice->invoice_number.' issued successfully.');
    }

    public function updatePayment(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_status' => 'required|string|in:unpaid,paid',
        ]);

        $order->update(['payment_status' => $validated['payment_status']]);

        if ($order->invoice) {
            $isPaid = $validated['payment_status'] === 'paid';
            $total = (int) $order->total_amount;
            $order->invoice->update([
                'payment_status' => $isPaid ? 'paid' : 'unpaid',
                'paid_amount' => $isPaid ? $total : 0,
                'due_amount' => $isPaid ? 0 : $total,
            ]);
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Order #'.$order->order_number.' payment status updated to "'.$validated['payment_status'].'".');
    }

    public function downloadInvoice(Order $order)
    {
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
