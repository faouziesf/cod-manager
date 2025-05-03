<?php


use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});



// Routes publiques
Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Routes authentifiées
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Profile
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unreadCount');
    
    // Commandes
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
    
    // Traitement des commandes
    Route::get('/orders/process/{type?}', [OrderController::class, 'process'])->name('orders.process');
    Route::post('/orders/{order}/process', [OrderController::class, 'processAction'])->name('orders.processAction');
    Route::post('/orders/{order}/assign', [OrderController::class, 'assign'])->name('orders.assign');
    
    // Recherche de commandes
    Route::get('/orders/search/advanced', [OrderController::class, 'search'])->name('orders.search');
    Route::get('/orders/export/csv', [OrderController::class, 'downloadCsv'])->name('orders.export.csv');
    
    // Import de commandes (admin et manager seulement)
    Route::middleware('can:manage-orders')->group(function () {
        Route::get('/orders/import/form', [OrderController::class, 'importForm'])->name('orders.import.form');
        Route::post('/orders/import', [OrderController::class, 'import'])->name('orders.import');
    });
    
    // Produits
    Route::resource('products', ProductController::class);
    Route::get('/products/out-of-stock', [ProductController::class, 'outOfStock'])->name('products.outOfStock');
    
    // Utilisateurs (admin et manager seulement)
    Route::middleware('can:manage-users')->group(function () {
        Route::resource('users', UserController::class);
        Route::get('/users/online/list', [UserController::class, 'online'])->name('users.online');
    });
    
    // Paramètres (admin seulement)
    Route::middleware('can:manage-settings')->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });
});