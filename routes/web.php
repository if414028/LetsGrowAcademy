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
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'role:Admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');

    //Create
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');

    //Show
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');

    //Edit
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
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

    //autocomplete customer
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
});

require __DIR__.'/auth.php';
