<?php

use App\Http\Controllers\BundleController;
use App\Http\Controllers\ContestController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});


Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'active'])
    ->name('dashboard');

/**
 * Authenticated routes
 */
Route::middleware('auth', 'active')->group(function () {

    // Profile (semua role): redirect ke halaman detail user yang login
    Route::get('/profile', function (\Illuminate\Http\Request $request) {
        return redirect()->route('users.show', $request->user()->id);
    })->name('profile');


    // Admin-only: manajemen user (kecuali show karena sudah di atas)
    Route::middleware('role:Admin|Head Admin')->group(function () {
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');

        Route::resource('users', UserController::class)
            ->except(['show', 'edit', 'update', 'create', 'store']);

        Route::get('/users/referrers/search', [UserController::class, 'searchReferrers'])
            ->name('users.referrers.search');

        // Sales Order
        Route::get('/sales-orders/create', [SalesOrderController::class, 'create'])->name('sales-orders.create');
        Route::post('/sales-orders', [SalesOrderController::class, 'store'])->name('sales-orders.store');
        Route::get('/sales-orders/{salesOrder}/edit', [SalesOrderController::class, 'edit'])->name('sales-orders.edit');
        Route::put('/sales-orders/{salesOrder}', [SalesOrderController::class, 'update'])->name('sales-orders.update');
    });

    Route::middleware('role:Admin|Head Admin')->group(function () {
        Route::get('/users/bulk-upload', [UserController::class, 'bulkUploadForm'])->name('users.bulk.form');
        Route::get('/users/bulk-upload/template', [UserController::class, 'bulkUploadTemplate'])->name('users.bulk.template');
        Route::post('/users/bulk-upload', [UserController::class, 'bulkUploadStore'])->name('users.bulk.store');
    });

    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');

    // Bulk-upload Produk
    Route::middleware(['auth', 'active', 'role:Admin|Head Admin'])->group(function () {
        Route::get('/products/bulk-upload', [\App\Http\Controllers\ProductController::class, 'bulkUploadForm'])
            ->name('products.bulk.form');
        Route::get('/products/bulk-upload/template', [\App\Http\Controllers\ProductController::class, 'bulkUploadTemplate'])
            ->name('products.bulk.template');
        Route::post('/products/bulk-upload', [\App\Http\Controllers\ProductController::class, 'bulkUploadStore'])
            ->name('products.bulk.store');
    });

    // Products
    Route::resource('products', ProductController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);

    // Sales Orders
    Route::get('/sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::get('/sales-orders/{salesOrder}', [SalesOrderController::class, 'show'])->name('sales-orders.show');

    // Autocomplete / Search
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::get('/sales-orders/sales-users/search', [SalesOrderController::class, 'searchSalesUsers'])
        ->name('sales-orders.sales-users.search');

    // Performances
    Route::middleware(['auth'])->group(function () {
        Route::get('/performance', [PerformanceController::class, 'index'])->name('performances.index');
    });

    Route::get('/performance/team/{user}', [PerformanceController::class, 'teamDetail']);

    // Reports
    Route::middleware(['auth'])->group(function () {
        Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])
            ->name('reports.index');
    });

    // LIST
    Route::middleware('can:viewAny,App\Models\Contest')->group(function () {
        Route::get('/contests', [ContestController::class, 'index'])->name('contests.index');
    });

    // CREATE (harus sebelum {contest})
    Route::middleware('can:create,App\Models\Contest')->group(function () {
        Route::get('/contests/create', [ContestController::class, 'create'])->name('contests.create');
        Route::post('/contests', [ContestController::class, 'store'])->name('contests.store');
    });

    // SHOW (taruh setelah create)
    Route::middleware('can:viewAny,App\Models\Contest')->group(function () {
        Route::get('/contests/{contest}', [ContestController::class, 'show'])->name('contests.show');
    });

    // EDIT/UPDATE/DELETE (setelah show juga aman)
    Route::middleware('can:update,contest')->group(function () {
        Route::get('/contests/{contest}/edit', [ContestController::class, 'edit'])->name('contests.edit');
        Route::put('/contests/{contest}', [ContestController::class, 'update'])->name('contests.update');
        Route::delete('/contests/{contest}', [ContestController::class, 'destroy'])->name('contests.destroy');
    });

    Route::post('/contests/{contest}/publish', [ContestController::class, 'publish'])
        ->name('contests.publish');

    // Bundles Product
    Route::get('/bundles', [BundleController::class, 'index'])->name('bundles.index');
    Route::get('/bundles/create', [BundleController::class, 'create'])->name('bundles.create');
    Route::post('/bundles', [BundleController::class, 'store'])->name('bundles.store');

    Route::get('/bundles/{bundle}', [BundleController::class, 'show'])->name('bundles.show');
    Route::get('/bundles/{bundle}/edit', [BundleController::class, 'edit'])->name('bundles.edit');
    Route::put('/bundles/{bundle}', [BundleController::class, 'update'])->name('bundles.update');
});

require __DIR__ . '/auth.php';
