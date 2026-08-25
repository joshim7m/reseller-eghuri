<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $totalSales = Order::whereIn('status', ['completed', 'processing'])->sum('total_amount');
        $pendingOrders = Order::where('status', 'pending')->count();
        $totalProducts = Product::count();
        $totalCustomers = User::whereHas('role', fn ($q) => $q->where('slug', 'customer'))->count();

        $recentOrders = Order::with('user')
            ->latest()
            ->take(5)
            ->get();

        $topSellers = User::whereIn('user_type', ['reseller', 'wholeseller'])
            ->with('userDetail')
            ->withSum('orders', 'total_amount')
            ->orderByDesc('orders_sum_total_amount')
            ->take(10)
            ->get();

        $weeklyCategorySales = OrderItem::whereHas('order', function ($q) {
            $q->where('created_at', '>=', now()->subDays(7))
                ->whereIn('status', ['completed', 'processing']);
        })
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as category, SUM(order_items.total) as total')
            ->groupBy('categories.id', 'categories.name')
            ->get();

        $salesData = [
            'total_sales' => $totalSales,
            'pending_orders' => $pendingOrders,
            'total_products' => $totalProducts,
            'total_customers' => $totalCustomers,
        ];

        return Inertia::render('Admin/Dashboard', compact('salesData', 'recentOrders', 'topSellers', 'weeklyCategorySales'));
    }
}
