<?php

use App\Http\Controllers\StoreFront\AuthController;
use App\Http\Controllers\StoreFront\CheckoutController;
use App\Http\Controllers\StoreFront\NotificationController;
use App\Http\Controllers\StoreFront\ResellerOrderController;
use App\Http\Controllers\StoreFront\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('home')->middleware('check-status');

Route::get('/sitemap.xml', [StorefrontController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [StorefrontController::class, 'robots'])->name('robots');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/account-inactive', [AuthController::class, 'showInactive'])->name('account.inactive');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('check-status')->group(function () {
    Route::get('/categories', [StorefrontController::class, 'categories'])->name('categories.index');
    Route::get('/cart', [StorefrontController::class, 'cart'])->name('cart.index');
    Route::get('/wishlist', [StorefrontController::class, 'wishlistPage'])->name('wishlist.index');
    Route::post('/wishlist/export', [StorefrontController::class, 'exportWishlist'])->name('wishlist.export');
    Route::get('/products', [StorefrontController::class, 'products'])->name('products.index');
    Route::get('/new-arrivals', [StorefrontController::class, 'newArrivals'])->name('products.new-arrivals');
    Route::get('/hot-sale', [StorefrontController::class, 'hotSale'])->name('products.hot-sale');
    Route::get('/category/{category:slug}', [StorefrontController::class, 'category'])->name('category.show');
    Route::get('/product/{product:slug}', [StorefrontController::class, 'product'])->name('product.show');
    Route::get('/page/{page:slug}', [StorefrontController::class, 'showPage'])->name('pages.show');

    Route::get('/api/search', [StorefrontController::class, 'searchAjax'])->name('api.search');
});

Route::middleware(['auth', 'check-status'])->group(function () {
    Route::prefix('reseller-orders')->name('reseller-orders.')->group(function () {
        Route::get('/', [ResellerOrderController::class, 'index'])->name('index');
        Route::get('/create', [ResellerOrderController::class, 'create'])->name('create');
        Route::get('/search-products', [ResellerOrderController::class, 'searchProducts'])->name('search-products');
        Route::post('/withdraw', [ResellerOrderController::class, 'withdraw'])->name('withdraw');
        Route::post('/', [ResellerOrderController::class, 'store'])->name('store');
        Route::get('/{resellerOrder}', [ResellerOrderController::class, 'show'])->name('show');
    });

    Route::get('/reseller-transactions', [ResellerOrderController::class, 'transactions'])->name('reseller-transactions');

    Route::get('/profile', [StorefrontController::class, 'profile'])->name('profile');
    Route::patch('/profile', [StorefrontController::class, 'updateProfile'])->name('profile.update');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/thank-you/{order}', [CheckoutController::class, 'thankYou'])->name('checkout.thank-you');
    Route::get('/orders', [CheckoutController::class, 'orders'])->name('orders.index');
    Route::get('/orders/{order}', [CheckoutController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/invoice', [CheckoutController::class, 'downloadInvoice'])->name('orders.invoice');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/all', [NotificationController::class, 'page'])->name('notifications.page');
    Route::post('/notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
});
