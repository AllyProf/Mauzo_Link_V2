<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlansController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\OtpVerificationController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UpgradeController;

// Landing Page
Route::get('/', [HomeController::class, 'index'])->name('home');

// Static Pages
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/services', [ServiceController::class, 'index'])->name('services');
Route::get('/menu', function() {
    return view('landing.menu');
})->name('menu');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');

// Customer Ordering (Public)
Route::get('/order', [\App\Http\Controllers\CustomerOrderController::class, 'index'])->name('customer.order');
Route::get('/cart', [\App\Http\Controllers\CustomerOrderController::class, 'cart'])->name('customer.cart');
Route::post('/order', [\App\Http\Controllers\CustomerOrderController::class, 'store'])->name('customer.order.store');
Route::get('/order/success/{order}', [\App\Http\Controllers\CustomerOrderController::class, 'success'])->name('customer.order.success');

// Plans & Pricing
Route::get('/plans', [PlansController::class, 'index'])->name('plans.index');

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// OTP Verification Routes
Route::get('/verify-otp', [OtpVerificationController::class, 'showVerificationForm'])->name('otp.verify');
Route::post('/verify-otp', [OtpVerificationController::class, 'verify']);
Route::post('/resend-otp', [OtpVerificationController::class, 'resend'])->name('otp.resend');

// Business Configuration Routes (Must be before require.configuration middleware)
// Allow both regular users and staff to access (but staff should only view, not edit)
Route::middleware('allow.staff')->group(function () {
    Route::prefix('business-configuration')->name('business-configuration.')->group(function () {
        Route::get('/', [\App\Http\Controllers\BusinessConfigurationController::class, 'index'])->name('index');
        Route::match(['get', 'post'], '/step1', [\App\Http\Controllers\BusinessConfigurationController::class, 'step1'])->name('step1');
        Route::match(['get', 'post'], '/step2', [\App\Http\Controllers\BusinessConfigurationController::class, 'step2'])->name('step2');
        Route::match(['get', 'post'], '/step3', [\App\Http\Controllers\BusinessConfigurationController::class, 'step3'])->name('step3');
        Route::match(['get', 'post'], '/step4', [\App\Http\Controllers\BusinessConfigurationController::class, 'step4'])->name('step4');
        Route::get('/edit', [\App\Http\Controllers\BusinessConfigurationController::class, 'edit'])->name('edit');
        Route::post('/update', [\App\Http\Controllers\BusinessConfigurationController::class, 'update'])->name('update');
    });
});

// Kiosk Route (Public access - but needs owner context from session if available)
Route::get('/kiosk', [\App\Http\Controllers\Bar\WaiterController::class, 'kiosk'])->name('kiosk.public');
Route::post('/kiosk/login', [\App\Http\Controllers\Bar\WaiterController::class, 'kioskLogin'])->name('kiosk.public.login');
Route::post('/kiosk/logout', [\App\Http\Controllers\Bar\WaiterController::class, 'kioskLogout'])->name('kiosk.public.logout');

// Dashboard Routes (Protected - allow both users and staff)
Route::middleware('allow.staff')->group(function () {
    // Dashboard - accessible without configuration (to show payment messages)
    // Base dashboard route (for regular users)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Role-specific dashboard route (for staff members) - must come after base route
    Route::get('/dashboard/{role}', [DashboardController::class, 'index'])->name('dashboard.role')->where('role', '[a-z0-9-]+');
    
    // Invoice Routes
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    
    // Payment Routes
    Route::get('/payments/instructions/{invoice}', [PaymentController::class, 'showInstructions'])->name('payments.instructions');
    Route::post('/payments/proof/{invoice}', [PaymentController::class, 'storeProof'])->name('payments.store-proof');
    Route::get('/payments/history', [PaymentController::class, 'history'])->name('payments.history');
    
    // Upgrade Routes
    Route::get('/upgrade', [UpgradeController::class, 'index'])->name('upgrade.index');
    Route::post('/upgrade', [UpgradeController::class, 'upgrade'])->name('upgrade.process');
    
    
    // Sales Routes (Require Payment & Configuration)
    Route::middleware(['require.payment', 'require.configuration'])->group(function () {
        Route::get('/sales/pos', [\App\Http\Controllers\SalesController::class, 'pos'])->name('sales.pos');
        Route::get('/sales/orders', [\App\Http\Controllers\SalesController::class, 'orders'])->name('sales.orders');
        Route::get('/sales/transactions', [\App\Http\Controllers\SalesController::class, 'transactions'])->name('sales.transactions');
    });
    
    // Products Routes (Require Payment & Configuration)
    Route::middleware(['require.payment', 'require.configuration'])->group(function () {
        Route::get('/products', [\App\Http\Controllers\ProductController::class, 'index'])->name('products.index');
        Route::get('/products/categories', [\App\Http\Controllers\ProductController::class, 'categories'])->name('products.categories');
        Route::get('/products/inventory', [\App\Http\Controllers\ProductController::class, 'inventory'])->name('products.inventory');
    });
    
    // Customers Routes (Require Payment & Configuration)
    Route::middleware(['require.payment', 'require.configuration'])->group(function () {
        Route::get('/customers', [\App\Http\Controllers\CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/groups', [\App\Http\Controllers\CustomerController::class, 'groups'])->name('customers.groups');
    });
    
    // Settings Routes
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/profile', [\App\Http\Controllers\SettingsController::class, 'updateProfile'])->name('settings.update-profile');
    Route::post('/settings/password', [\App\Http\Controllers\SettingsController::class, 'updatePassword'])->name('settings.update-password');
    Route::post('/settings/system', [\App\Http\Controllers\SettingsController::class, 'updateSystemSettings'])->name('settings.update-system')->middleware('admin');
    
    // Staff Routes (Require Payment & Configuration, Free/Pro plans only)
    Route::middleware(['require.payment', 'require.configuration'])->group(function () {
        // Specific routes must come before resource routes to avoid conflicts
        Route::get('/staff/roles-by-business-type', [\App\Http\Controllers\StaffController::class, 'getRolesByBusinessType'])->name('staff.roles-by-business-type');
        
        Route::get('/staff', [\App\Http\Controllers\StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/create', [\App\Http\Controllers\StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff', [\App\Http\Controllers\StaffController::class, 'store'])->name('staff.store');
        Route::get('/staff/{staff}', [\App\Http\Controllers\StaffController::class, 'show'])->name('staff.show');
        Route::get('/staff/{staff}/edit', [\App\Http\Controllers\StaffController::class, 'edit'])->name('staff.edit');
        Route::put('/staff/{staff}', [\App\Http\Controllers\StaffController::class, 'update'])->name('staff.update');
        Route::delete('/staff/{staff}', [\App\Http\Controllers\StaffController::class, 'destroy'])->name('staff.destroy');
    });
    
    // HR Routes (Require Payment & Configuration)
    Route::middleware(['require.payment', 'require.configuration'])->prefix('hr')->name('hr.')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\HRController::class, 'dashboard'])->name('dashboard');
        Route::get('attendance', [\App\Http\Controllers\HRController::class, 'attendance'])->name('attendance');
        Route::get('attendance/json', [\App\Http\Controllers\HRController::class, 'getAttendanceJson'])->name('attendance.json');
        Route::post('attendance/mark', [\App\Http\Controllers\HRController::class, 'markAttendance'])->name('attendance.mark');
        Route::get('leaves', [\App\Http\Controllers\HRController::class, 'leaves'])->name('leaves');
        Route::post('leaves/{leave}/update-status', [\App\Http\Controllers\HRController::class, 'updateLeaveStatus'])->name('leaves.update-status');
        Route::get('payroll', [\App\Http\Controllers\HRController::class, 'payroll'])->name('payroll');
        Route::post('payroll/generate', [\App\Http\Controllers\HRController::class, 'generatePayroll'])->name('payroll.generate');
        Route::get('performance-reviews', [\App\Http\Controllers\HRController::class, 'performanceReviews'])->name('performance-reviews');
        Route::post('performance-reviews', [\App\Http\Controllers\HRController::class, 'storePerformanceReview'])->name('performance-reviews.store');
        
        // Biometric Device Management
        Route::get('biometric-devices', [\App\Http\Controllers\HR\BiometricDeviceController::class, 'index'])->name('biometric-devices');
        Route::post('biometric-devices/test-connection', [\App\Http\Controllers\HR\BiometricDeviceController::class, 'testConnection'])->name('biometric-devices.test-connection');
        Route::post('biometric-devices/staff/{staff}/register', [\App\Http\Controllers\HR\BiometricDeviceController::class, 'registerStaff'])->name('biometric-devices.register-staff');
        Route::post('biometric-devices/staff/{staff}/unregister', [\App\Http\Controllers\HR\BiometricDeviceController::class, 'unregisterStaff'])->name('biometric-devices.unregister-staff');
        Route::post('biometric-devices/sync-attendance', [\App\Http\Controllers\HR\BiometricDeviceController::class, 'syncAttendance'])->name('biometric-devices.sync-attendance');
        Route::post('biometric-devices/get-users', [\App\Http\Controllers\HR\BiometricDeviceController::class, 'getDeviceUsers'])->name('biometric-devices.get-users');
    });
    
    // Bar Operations Routes (Require Payment & Configuration)
    Route::middleware(['require.payment', 'require.configuration'])->prefix('bar')->name('bar.')->group(function () {
        // Suppliers
        Route::resource('suppliers', \App\Http\Controllers\Bar\SupplierController::class);
        // Products
        Route::get('products/get-by-category', [\App\Http\Controllers\Bar\ProductController::class, 'getByCategory'])->name('products.get-by-category');
        Route::resource('products', \App\Http\Controllers\Bar\ProductController::class);
        // Stock Receipts
        Route::resource('stock-receipts', \App\Http\Controllers\Bar\StockReceiptController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::get('stock-receipts/print-batch/{receiptNumber}', [\App\Http\Controllers\Bar\StockReceiptController::class, 'printBatch'])->name('stock-receipts.print-batch');
        // Stock Transfers - Specific routes must come before resource route
        Route::get('stock-transfers/available', [\App\Http\Controllers\Bar\StockTransferController::class, 'available'])->name('stock-transfers.available');
        Route::get('stock-transfers/history', [\App\Http\Controllers\Bar\StockTransferController::class, 'history'])->name('stock-transfers.history');
        Route::post('stock-transfers/real-time-profit', [\App\Http\Controllers\Bar\StockTransferController::class, 'getRealTimeProfit'])->name('stock-transfers.real-time-profit');
        Route::resource('stock-transfers', \App\Http\Controllers\Bar\StockTransferController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('stock-transfers/{stockTransfer}/approve', [\App\Http\Controllers\Bar\StockTransferController::class, 'approve'])->name('stock-transfers.approve');
        Route::post('stock-transfers/{stockTransfer}/reject', [\App\Http\Controllers\Bar\StockTransferController::class, 'reject'])->name('stock-transfers.reject');
        Route::post('stock-transfers/{stockTransfer}/reject-with-reason', [\App\Http\Controllers\Bar\StockTransferController::class, 'rejectWithReason'])->name('stock-transfers.reject-with-reason');
        Route::post('stock-transfers/{stockTransfer}/mark-as-prepared', [\App\Http\Controllers\Bar\StockTransferController::class, 'markAsPrepared'])->name('stock-transfers.mark-as-prepared');
        Route::post('stock-transfers/{stockTransfer}/mark-as-moved', [\App\Http\Controllers\Bar\StockTransferController::class, 'markAsMoved'])->name('stock-transfers.mark-as-moved');
        // Orders - Specific routes must come before resource route
        Route::get('orders/food', [\App\Http\Controllers\Bar\OrderController::class, 'foodOrders'])->name('orders.food');
        Route::get('orders/drinks', [\App\Http\Controllers\Bar\OrderController::class, 'drinksOrders'])->name('orders.drinks');
        Route::get('orders/juice', [\App\Http\Controllers\Bar\OrderController::class, 'juiceOrders'])->name('orders.juice');
        Route::post('orders/{order}/update-status', [\App\Http\Controllers\Bar\OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::get('orders/{order}/details', [\App\Http\Controllers\Bar\OrderController::class, 'getOrderDetails'])->name('orders.details');
        Route::resource('orders', \App\Http\Controllers\Bar\OrderController::class)->only(['index', 'create', 'store', 'show']);
        // Payments
        Route::resource('payments', \App\Http\Controllers\Bar\PaymentController::class)->only(['index', 'show']);
        // Beverage Inventory
        Route::get('beverage-inventory', [\App\Http\Controllers\Bar\BeverageInventoryController::class, 'index'])->name('beverage-inventory.index');
        Route::get('beverage-inventory/add', [\App\Http\Controllers\Bar\BeverageInventoryController::class, 'addBeverage'])->name('beverage-inventory.add');
        Route::get('beverage-inventory/stock-levels', [\App\Http\Controllers\Bar\BeverageInventoryController::class, 'stockLevels'])->name('beverage-inventory.stock-levels');
        Route::get('beverage-inventory/low-stock-alerts', [\App\Http\Controllers\Bar\BeverageInventoryController::class, 'lowStockAlerts'])->name('beverage-inventory.low-stock-alerts');
        Route::get('beverage-inventory/warehouse-stock', [\App\Http\Controllers\Bar\BeverageInventoryController::class, 'warehouseStock'])->name('beverage-inventory.warehouse-stock');
        // Tables
        Route::resource('tables', \App\Http\Controllers\Bar\TableController::class);
        // Waiter Routes
        Route::get('waiter/dashboard', [\App\Http\Controllers\Bar\WaiterController::class, 'dashboard'])->name('waiter.dashboard');
        Route::post('waiter/create-order', [\App\Http\Controllers\Bar\WaiterController::class, 'createOrder'])->name('waiter.create-order');
        Route::post('waiter/cancel-order/{order}', [\App\Http\Controllers\Bar\WaiterController::class, 'cancelOrder'])->name('waiter.cancel-order');
        Route::post('waiter/record-payment/{order}', [\App\Http\Controllers\Bar\WaiterController::class, 'recordPayment'])->name('waiter.record-payment');
        Route::get('waiter/print-receipt/{order}', [\App\Http\Controllers\Bar\WaiterController::class, 'printReceipt'])->name('waiter.print-receipt');
        Route::get('waiter/order-history', [\App\Http\Controllers\Bar\WaiterController::class, 'orderHistory'])->name('waiter.order-history');
        // Waiter Sales & Reconciliation
        Route::get('waiter/sales', [\App\Http\Controllers\Bar\WaiterSalesController::class, 'salesDashboard'])->name('waiter.sales');
        Route::post('waiter/submit-reconciliation', [\App\Http\Controllers\Bar\WaiterSalesController::class, 'submitReconciliation'])->name('waiter.submit-reconciliation');
        // Kiosk Routes (accessible but needs owner context)
        Route::get('kiosk', [\App\Http\Controllers\Bar\WaiterController::class, 'kiosk'])->name('kiosk');
        Route::post('kiosk/login', [\App\Http\Controllers\Bar\WaiterController::class, 'kioskLogin'])->name('kiosk.login');
        Route::post('kiosk/logout', [\App\Http\Controllers\Bar\WaiterController::class, 'kioskLogout'])->name('kiosk.logout');
        // Counter Routes
        Route::get('counter/dashboard', [\App\Http\Controllers\Bar\CounterController::class, 'dashboard'])->name('counter.dashboard');
        // Inventory Settings Routes
        Route::get('inventory-settings', [\App\Http\Controllers\Bar\InventorySettingsController::class, 'index'])->name('inventory-settings.index');
        Route::put('inventory-settings', [\App\Http\Controllers\Bar\InventorySettingsController::class, 'update'])->name('inventory-settings.update');
        // Counter Settings Routes
        Route::get('counter-settings', [\App\Http\Controllers\Bar\CounterSettingsController::class, 'index'])->name('counter-settings.index');
        Route::put('counter-settings', [\App\Http\Controllers\Bar\CounterSettingsController::class, 'update'])->name('counter-settings.update');
        Route::get('counter/waiter-orders', [\App\Http\Controllers\Bar\CounterController::class, 'waiterOrders'])->name('counter.waiter-orders');
        Route::get('counter/customer-orders', [\App\Http\Controllers\Bar\CounterController::class, 'customerOrders'])->name('counter.customer-orders');
        Route::get('counter/warehouse-stock', [\App\Http\Controllers\Bar\CounterController::class, 'warehouseStock'])->name('counter.warehouse-stock');
        Route::get('counter/counter-stock', [\App\Http\Controllers\Bar\CounterController::class, 'counterStock'])->name('counter.counter-stock');
        Route::get('counter/analytics', [\App\Http\Controllers\Bar\CounterController::class, 'analytics'])->name('counter.analytics');
        Route::get('counter/stock-transfer-requests', [\App\Http\Controllers\Bar\CounterController::class, 'stockTransferRequests'])->name('counter.stock-transfer-requests');
        Route::get('counter/record-voice', [\App\Http\Controllers\Bar\CounterController::class, 'recordVoice'])->name('counter.record-voice');
        // Counter Reconciliation
        Route::get('counter/reconciliation', [\App\Http\Controllers\Bar\CounterReconciliationController::class, 'reconciliation'])->name('counter.reconciliation');
        Route::post('counter/reconciliation/{reconciliation}/verify', [\App\Http\Controllers\Bar\CounterReconciliationController::class, 'verifyReconciliation'])->name('counter.verify-reconciliation');
        Route::post('counter/mark-all-paid', [\App\Http\Controllers\Bar\CounterReconciliationController::class, 'markAllOrdersPaid'])->name('counter.mark-all-paid');
        Route::get('counter/reconciliation/waiter-orders/{waiter}', [\App\Http\Controllers\Bar\CounterReconciliationController::class, 'getWaiterOrders'])->name('counter.reconciliation.waiter-orders');
        Route::post('counter/save-voice-clip', [\App\Http\Controllers\Bar\CounterController::class, 'saveVoiceClip'])->name('counter.save-voice-clip');
        Route::put('counter/voice-clips/{id}', [\App\Http\Controllers\Bar\CounterController::class, 'updateVoiceClip'])->name('counter.update-voice-clip');
        Route::get('counter/get-voice-clips', [\App\Http\Controllers\Bar\CounterController::class, 'getVoiceClips'])->name('counter.get-voice-clips');
        Route::delete('counter/voice-clips/{id}', [\App\Http\Controllers\Bar\CounterController::class, 'deleteVoiceClip'])->name('counter.delete-voice-clip');
        Route::post('counter/request-stock-transfer', [\App\Http\Controllers\Bar\CounterController::class, 'requestStockTransfer'])->name('counter.request-stock-transfer');
        Route::post('counter/orders/{order}/update-status', [\App\Http\Controllers\Bar\CounterController::class, 'updateOrderStatus'])->name('counter.update-order-status');
        Route::post('counter/orders/{order}/mark-paid', [\App\Http\Controllers\Bar\CounterController::class, 'markAsPaid'])->name('counter.mark-paid');
        Route::get('counter/orders-by-status', [\App\Http\Controllers\Bar\CounterController::class, 'getOrdersByStatus'])->name('counter.orders-by-status');
        Route::get('counter/latest-orders', [\App\Http\Controllers\Bar\CounterController::class, 'getLatestOrders'])->name('counter.latest-orders');
        // Chef Routes
        Route::get('chef/dashboard', [\App\Http\Controllers\Bar\ChefController::class, 'dashboard'])->name('chef.dashboard');
        Route::get('chef/kds', [\App\Http\Controllers\Bar\ChefController::class, 'kds'])->name('chef.kds');
        Route::post('chef/kitchen-items/{kitchenOrderItem}/update-status', [\App\Http\Controllers\Bar\ChefController::class, 'updateItemStatus'])->name('chef.update-item-status');
        Route::post('chef/kitchen-items/{kitchenOrderItem}/mark-taken', [\App\Http\Controllers\Bar\ChefController::class, 'markItemAsTaken'])->name('chef.mark-item-taken');
        Route::get('chef/latest-orders', [\App\Http\Controllers\Bar\ChefController::class, 'getLatestOrders'])->name('chef.latest-orders');
        // Food Items Management
        Route::get('chef/food-items', [\App\Http\Controllers\Bar\ChefController::class, 'foodItems'])->name('chef.food-items');
        Route::get('chef/food-items/create', [\App\Http\Controllers\Bar\ChefController::class, 'createFoodItem'])->name('chef.food-items.create');
        Route::post('chef/food-items', [\App\Http\Controllers\Bar\ChefController::class, 'storeFoodItem'])->name('chef.food-items.store');
        Route::get('chef/food-items/{foodItem}/edit', [\App\Http\Controllers\Bar\ChefController::class, 'editFoodItem'])->name('chef.food-items.edit');
        Route::put('chef/food-items/{foodItem}', [\App\Http\Controllers\Bar\ChefController::class, 'updateFoodItem'])->name('chef.food-items.update');
        Route::delete('chef/food-items/{foodItem}', [\App\Http\Controllers\Bar\ChefController::class, 'destroyFoodItem'])->name('chef.food-items.destroy');
        // Recipe Management
        Route::get('chef/food-items/{foodItem}/recipe', [\App\Http\Controllers\Bar\ChefController::class, 'manageRecipe'])->name('chef.food-items.recipe');
        Route::post('chef/food-items/{foodItem}/recipe', [\App\Http\Controllers\Bar\ChefController::class, 'saveRecipe'])->name('chef.food-items.recipe.save');
        // Ingredients Management
        Route::get('chef/ingredients', [\App\Http\Controllers\Bar\ChefController::class, 'ingredients'])->name('chef.ingredients');
        Route::get('chef/ingredients/create', [\App\Http\Controllers\Bar\ChefController::class, 'createIngredient'])->name('chef.ingredients.create');
        Route::post('chef/ingredients', [\App\Http\Controllers\Bar\ChefController::class, 'storeIngredient'])->name('chef.ingredients.store');
        Route::get('chef/ingredients/{ingredient}/edit', [\App\Http\Controllers\Bar\ChefController::class, 'editIngredient'])->name('chef.ingredients.edit');
        Route::put('chef/ingredients/{ingredient}', [\App\Http\Controllers\Bar\ChefController::class, 'updateIngredient'])->name('chef.ingredients.update');
        Route::delete('chef/ingredients/{ingredient}', [\App\Http\Controllers\Bar\ChefController::class, 'destroyIngredient'])->name('chef.ingredients.destroy');
        // Ingredient Receipts
        Route::get('chef/ingredient-receipts', [\App\Http\Controllers\Bar\IngredientReceiptController::class, 'index'])->name('chef.ingredient-receipts');
        Route::get('chef/ingredient-receipts/create', [\App\Http\Controllers\Bar\IngredientReceiptController::class, 'create'])->name('chef.ingredient-receipts.create');
        Route::post('chef/ingredient-receipts', [\App\Http\Controllers\Bar\IngredientReceiptController::class, 'store'])->name('chef.ingredient-receipts.store');
        Route::get('chef/ingredient-receipts/{receipt}', [\App\Http\Controllers\Bar\IngredientReceiptController::class, 'show'])->name('chef.ingredient-receipts.show');
        // Ingredient Stock Movements
        Route::get('chef/ingredient-stock-movements', [\App\Http\Controllers\Bar\ChefController::class, 'ingredientStockMovements'])->name('chef.ingredient-stock-movements');
        // Ingredient Batches
        Route::get('chef/ingredient-batches', [\App\Http\Controllers\Bar\ChefController::class, 'ingredientBatches'])->name('chef.ingredient-batches');
        // Restaurant Reports
        Route::get('chef/reports', [\App\Http\Controllers\Bar\ChefController::class, 'reports'])->name('chef.reports');
        Route::get('chef/reconciliation', [\App\Http\Controllers\Bar\ChefController::class, 'reconciliation'])->name('chef.reconciliation');
        Route::get('chef/reconciliation/waiter-orders/{waiter}', [\App\Http\Controllers\Bar\ChefController::class, 'getWaiterFoodOrders'])->name('chef.reconciliation.waiter-orders');
        Route::post('chef/mark-all-food-paid', [\App\Http\Controllers\Bar\ChefController::class, 'markAllFoodOrdersPaid'])->name('chef.mark-all-food-paid');
        
        // Stock Keeper Ingredients Management Routes (same controllers as Chef)
        Route::get('stock-keeper/ingredients', [\App\Http\Controllers\Bar\ChefController::class, 'ingredients'])->name('stock-keeper.ingredients');
        Route::get('stock-keeper/ingredients/create', [\App\Http\Controllers\Bar\ChefController::class, 'createIngredient'])->name('stock-keeper.ingredients.create');
        Route::post('stock-keeper/ingredients', [\App\Http\Controllers\Bar\ChefController::class, 'storeIngredient'])->name('stock-keeper.ingredients.store');
        Route::get('stock-keeper/ingredients/{ingredient}/edit', [\App\Http\Controllers\Bar\ChefController::class, 'editIngredient'])->name('stock-keeper.ingredients.edit');
        Route::put('stock-keeper/ingredients/{ingredient}', [\App\Http\Controllers\Bar\ChefController::class, 'updateIngredient'])->name('stock-keeper.ingredients.update');
        Route::delete('stock-keeper/ingredients/{ingredient}', [\App\Http\Controllers\Bar\ChefController::class, 'destroyIngredient'])->name('stock-keeper.ingredients.destroy');
        // Ingredient Receipts
        Route::get('stock-keeper/ingredient-receipts', [\App\Http\Controllers\Bar\IngredientReceiptController::class, 'index'])->name('stock-keeper.ingredient-receipts');
        Route::get('stock-keeper/ingredient-receipts/create', [\App\Http\Controllers\Bar\IngredientReceiptController::class, 'create'])->name('stock-keeper.ingredient-receipts.create');
        Route::post('stock-keeper/ingredient-receipts', [\App\Http\Controllers\Bar\IngredientReceiptController::class, 'store'])->name('stock-keeper.ingredient-receipts.store');
        Route::get('stock-keeper/ingredient-receipts/{receipt}', [\App\Http\Controllers\Bar\IngredientReceiptController::class, 'show'])->name('stock-keeper.ingredient-receipts.show');
        // Ingredient Stock Movements
        Route::get('stock-keeper/ingredient-stock-movements', [\App\Http\Controllers\Bar\ChefController::class, 'ingredientStockMovements'])->name('stock-keeper.ingredient-stock-movements');
        // Ingredient Batches
        Route::get('stock-keeper/ingredient-batches', [\App\Http\Controllers\Bar\ChefController::class, 'ingredientBatches'])->name('stock-keeper.ingredient-batches');
    });
    
    // Accountant Routes (Require Payment & Configuration)
        Route::middleware(['require.payment', 'require.configuration'])->prefix('accountant')->name('accountant.')->group(function () {
            Route::get('dashboard', [\App\Http\Controllers\Accountant\AccountantController::class, 'dashboard'])->name('dashboard');
            Route::get('reconciliations', [\App\Http\Controllers\Accountant\AccountantController::class, 'reconciliations'])->name('reconciliations');
            Route::get('reconciliations/{id}', [\App\Http\Controllers\Accountant\AccountantController::class, 'reconciliationDetails'])->name('reconciliation-details');
            Route::get('counter-reconciliation', [\App\Http\Controllers\Accountant\AccountantController::class, 'counterReconciliation'])->name('counter-reconciliation');
            Route::post('counter/reconciliation/{reconciliation}/verify', [\App\Http\Controllers\Bar\CounterReconciliationController::class, 'verifyReconciliation'])->name('counter.verify-reconciliation');
            Route::get('counter/reconciliation/waiter-orders/{waiter}', [\App\Http\Controllers\Bar\CounterReconciliationController::class, 'getWaiterOrders'])->name('counter.reconciliation.waiter-orders');
            Route::post('stock-transfers/{stockTransfer}/verify', [\App\Http\Controllers\Accountant\AccountantController::class, 'verifyStockTransfer'])->name('verify-stock-transfer');
            Route::get('reports', [\App\Http\Controllers\Accountant\AccountantController::class, 'reports'])->name('reports');
            Route::get('reports/pdf', [\App\Http\Controllers\Accountant\AccountantController::class, 'exportReportsPdf'])->name('reports.pdf');
        });
    
    // Marketing Routes (Require Payment & Configuration)
    Route::middleware(['require.payment', 'require.configuration'])->prefix('marketing')->name('marketing.')->group(function () {
        // Dashboard
        Route::get('dashboard', [\App\Http\Controllers\Marketing\MarketingController::class, 'dashboard'])->name('dashboard');
        // Customers
        Route::get('customers', [\App\Http\Controllers\Marketing\MarketingController::class, 'customers'])->name('customers');
        // Direct SMS sending
        Route::post('send-sms', [\App\Http\Controllers\Marketing\MarketingController::class, 'sendDirectSms'])->name('send-sms');
        // Campaigns
        Route::get('campaigns', [\App\Http\Controllers\Marketing\MarketingController::class, 'campaigns'])->name('campaigns');
        Route::get('campaigns/create', [\App\Http\Controllers\Marketing\MarketingController::class, 'createCampaign'])->name('campaigns.create');
        Route::post('campaigns', [\App\Http\Controllers\Marketing\MarketingController::class, 'storeCampaign'])->name('campaigns.store');
        Route::get('campaigns/{id}', [\App\Http\Controllers\Marketing\MarketingController::class, 'showCampaign'])->name('campaigns.show');
        Route::post('campaigns/{id}/send', [\App\Http\Controllers\Marketing\MarketingController::class, 'sendCampaignRoute'])->name('campaigns.send');
        // Templates
        Route::get('templates', [\App\Http\Controllers\Marketing\MarketingController::class, 'templates'])->name('templates');
        Route::get('templates/json', [\App\Http\Controllers\Marketing\MarketingController::class, 'getTemplatesJson'])->name('templates.json');
        Route::post('templates', [\App\Http\Controllers\Marketing\MarketingController::class, 'storeTemplate'])->name('templates.store');
    });
    
    // Admin Routes (Admin Only)
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        // Admin Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard.index');
        
        // User Management
        Route::get('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'show'])->name('users.show');
        Route::post('/users/{user}/activate', [\App\Http\Controllers\Admin\UserManagementController::class, 'activate'])->name('users.activate');
        Route::post('/users/{user}/deactivate', [\App\Http\Controllers\Admin\UserManagementController::class, 'deactivate'])->name('users.deactivate');
        
        // Subscription Management
        Route::get('/subscriptions', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'index'])->name('subscriptions.index');
        Route::get('/subscriptions/{subscription}', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'show'])->name('subscriptions.show');
        Route::post('/subscriptions/{subscription}/activate', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'activate'])->name('subscriptions.activate');
        Route::post('/subscriptions/{subscription}/suspend', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'suspend'])->name('subscriptions.suspend');
        Route::post('/subscriptions/{subscription}/cancel', [\App\Http\Controllers\Admin\SubscriptionManagementController::class, 'cancel'])->name('subscriptions.cancel');
        
        // Plan Management
        Route::get('/plans', [\App\Http\Controllers\Admin\PlanManagementController::class, 'index'])->name('plans.index');
        Route::get('/plans/create', [\App\Http\Controllers\Admin\PlanManagementController::class, 'create'])->name('plans.create');
        Route::post('/plans', [\App\Http\Controllers\Admin\PlanManagementController::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}/edit', [\App\Http\Controllers\Admin\PlanManagementController::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}', [\App\Http\Controllers\Admin\PlanManagementController::class, 'update'])->name('plans.update');
        Route::post('/plans/{plan}/toggle-status', [\App\Http\Controllers\Admin\PlanManagementController::class, 'toggleStatus'])->name('plans.toggle-status');
        
        // Payment Management
        Route::get('/payments', [\App\Http\Controllers\Admin\PaymentVerificationController::class, 'index'])->name('payments.index');
        Route::get('/payments/{payment}', [\App\Http\Controllers\Admin\PaymentVerificationController::class, 'show'])->name('payments.show');
        Route::post('/payments/{payment}/verify', [\App\Http\Controllers\Admin\PaymentVerificationController::class, 'verify'])->name('payments.verify');
        Route::post('/payments/{payment}/reject', [\App\Http\Controllers\Admin\PaymentVerificationController::class, 'reject'])->name('payments.reject');
        
        // Analytics
        Route::get('/analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index');
    });
});
