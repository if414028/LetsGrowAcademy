<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::middleware(['auth'])->group(function () {
    // profile yang login (boleh semua role)
    Route::get('/profile', function () {
        return redirect()->route('users.show', Auth::auth()->id());
    })->name('profile');

    // show user (nanti di controller kita batasi: non-admin cuma boleh lihat dirinya sendiri)
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    });

    Route::middleware(['auth','role:Admin'])->group(function () {
        // yang admin-only: index/create/store/edit/update/destroy
        Route::resource('users', UserController::class)->except(['show']);
    });
});

Route::middleware(['auth'])->group(function () {

    // ✅ Profile yang login (semua role boleh)
    Route::get('/profile', function (\Illuminate\Http\Request $request) {
        return redirect()->route('users.show', $request->user()->id);
    })->middleware('auth')->name('profile');

    // ✅ Show user: semua role bisa akses, tapi dibatasi di controller (non-admin hanya diri sendiri)
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');

    // ✅ Admin-only: semua manajemen user selain show
    Route::middleware(['role:Admin'])->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('/users/referrers/search', [UserController::class, 'searchReferrers'])
            ->name('users.referrers.search');
    });

    Route::middleware(['auth'])->group(function () {
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update_profile');
    });

    Route::middleware(['auth','role:Admin'])->group(function () {
        Route::resource('users', UserController::class)->except(['show','edit','update']);
    });

});

Route::middleware(['auth'])->group(function () {
    Route::resource('products', ProductController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    
    Route::get('/sales-orders/create', [SalesOrderController::class, 'create'])->name('sales-orders.create');
    
    Route::post('/sales-orders', [SalesOrderController::class, 'store'])->name('sales-orders.store');
    
    Route::get('/sales-orders/{salesOrder}', [SalesOrderController::class, 'show'])
    ->name('sales-orders.show');
    
    Route::get('/sales-orders/{salesOrder}/edit', [SalesOrderController::class, 'edit'])
    ->name('sales-orders.edit');

    Route::put('/sales-orders/{salesOrder}', [SalesOrderController::class, 'update'])
        ->name('sales-orders.update');

    //autocomplete customer
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');

    Route::get('/sales-orders/sales-users/search', [SalesOrderController::class, 'searchSalesUsers'])
    ->name('sales-orders.sales-users.search');

});

require __DIR__.'/auth.php';
