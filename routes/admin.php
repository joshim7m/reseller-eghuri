<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DatabaseBackupController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\InstagramImageController;
use App\Http\Controllers\Admin\NoticeController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ResellerOrderController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SellerController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\SocialMediaController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

    Route::middleware(['auth', 'verified', 'check-status', 'check-role:manage-catalog,manage-orders,manage-users,manage-settings'])->group(function () {

        Route::get('reseller-orders', [ResellerOrderController::class, 'index'])->name('reseller-orders.index');
        Route::get('reseller-orders/report', [ResellerOrderController::class, 'report'])->name('reseller-orders.report');
        Route::get('reseller-orders/report/export', [ResellerOrderController::class, 'exportReport'])->name('reseller-orders.export-report');
        Route::get('reseller-orders/user/{user}/{date}', [ResellerOrderController::class, 'byUser'])->name('reseller-orders.by-user');
        Route::get('reseller-orders/{resellerOrder}/edit', [ResellerOrderController::class, 'edit'])->name('reseller-orders.edit');
        Route::put('reseller-orders/{resellerOrder}', [ResellerOrderController::class, 'update'])->name('reseller-orders.update');
        Route::patch('reseller-orders/{resellerOrder}/status', [ResellerOrderController::class, 'updateStatus'])->name('reseller-orders.update-status');
        Route::patch('reseller-orders/{resellerOrder}/payment-status', [ResellerOrderController::class, 'updatePaymentStatus'])->name('reseller-orders.update-payment-status');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('products', ProductController::class)->except('show');
        Route::delete('products/{product}/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
        Route::patch('products/{product}/status', [ProductController::class, 'updateStatus'])->name('products.update-status');
        Route::patch('products/{product}/featured', [ProductController::class, 'updateFeatured'])->name('products.update-featured');

        Route::get('withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
        Route::get('withdrawals/report', [WithdrawalController::class, 'report'])->name('withdrawals.report');
        Route::get('withdrawals/report/export', [WithdrawalController::class, 'exportReport'])->name('withdrawals.export-report');
        Route::post('withdrawals/{transaction}/accept', [WithdrawalController::class, 'accept'])->name('withdrawals.accept');
        Route::post('withdrawals/{transaction}/reject', [WithdrawalController::class, 'reject'])->name('withdrawals.reject');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::patch('orders/{order}/payment', [OrderController::class, 'updatePayment'])->name('orders.update-payment');
        Route::post('orders/{order}/invoice', [OrderController::class, 'issueInvoice'])->name('orders.issue-invoice');
        Route::get('orders/{order}/invoice/download', [OrderController::class, 'downloadInvoice'])->name('orders.invoice');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/customers', [UserController::class, 'customers'])->name('users.customers');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('sellers', [SellerController::class, 'index'])->name('sellers.index');
        Route::get('sellers/{user}/edit', [SellerController::class, 'edit'])->name('sellers.edit');
        Route::put('sellers/{user}', [SellerController::class, 'update'])->name('sellers.update');
        Route::delete('sellers/{user}', [SellerController::class, 'destroy'])->name('sellers.destroy');

        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');

        Route::get('settings/company', [SettingController::class, 'companyInfo'])->name('settings.company');
        Route::get('settings/site-config', [SettingController::class, 'siteConfig'])->name('settings.site-config');
        Route::get('settings/seo', [SettingController::class, 'seo'])->name('settings.seo');
        Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('settings/catalog', [CatalogController::class, 'index'])->name('settings.catalog');
        Route::post('settings/catalog/export', [CatalogController::class, 'export'])->name('settings.catalog.export');
        Route::post('settings/catalog/import', [CatalogController::class, 'import'])->name('settings.catalog.import');
        Route::get('settings/catalog/status', [CatalogController::class, 'status'])->name('settings.catalog.status');
        Route::get('settings/catalog/exports/{export}/download', [CatalogController::class, 'download'])->name('settings.catalog.download');

        Route::get('settings/backup', [DatabaseBackupController::class, 'index'])->name('settings.backup');
        Route::post('settings/backup', [DatabaseBackupController::class, 'store'])->name('settings.backup.store');
        Route::get('settings/backup/{filename}/download', [DatabaseBackupController::class, 'download'])->name('settings.backup.download');
        Route::delete('settings/backup/{filename}', [DatabaseBackupController::class, 'destroy'])->name('settings.backup.destroy');

        Route::resource('payment-methods', PaymentMethodController::class);
        Route::patch('payment-methods/{paymentMethod}/status', [PaymentMethodController::class, 'toggleStatus'])->name('payment-methods.toggle-status');

        Route::resource('instagram-images', InstagramImageController::class);
        Route::resource('sliders', SliderController::class)->except('show');
        Route::resource('social-media', SocialMediaController::class)->parameters(['social-media' => 'socialMedium']);
        Route::resource('faqs', FaqController::class)->except('show');
        Route::resource('notices', NoticeController::class)->except('show');
        Route::resource('pages', PageController::class)->except('show');
    });
});
