<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WaiterApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
Route::post('/waiter/login', [WaiterApiController::class, 'login']);

// ZKTeco Biometric Device Push SDK Routes (Public - device calls these directly)
Route::prefix('iclock')->group(function () {
    Route::get('getrequest', [\App\Http\Controllers\Api\BiometricPushController::class, 'getRequest'])->name('biometric.push.getrequest');
    Route::post('cdata', [\App\Http\Controllers\Api\BiometricPushController::class, 'cdata'])->name('biometric.push.cdata');
});

// Protected routes (require authentication token)
Route::middleware([\App\Http\Middleware\AuthenticateApi::class])->group(function () {
    // Authentication
    Route::post('/waiter/logout', [WaiterApiController::class, 'logout']);
    
    // Products & Tables
    Route::get('/waiter/products', [WaiterApiController::class, 'getProducts']);
    Route::get('/waiter/food-items', [WaiterApiController::class, 'getFoodItems']);
    Route::get('/waiter/tables', [WaiterApiController::class, 'getTables']);
    
    // Orders
    Route::post('/waiter/orders', [WaiterApiController::class, 'createOrder']);
    Route::get('/waiter/orders', [WaiterApiController::class, 'getOrderHistory']);
    Route::get('/waiter/orders/completed', [WaiterApiController::class, 'getCompletedOrders']);
    Route::get('/waiter/orders/{orderId}', [WaiterApiController::class, 'getOrderDetails']);
    Route::post('/waiter/orders/{orderId}/cancel', [WaiterApiController::class, 'cancelOrder']);
    
    // Payments
    Route::post('/waiter/orders/{orderId}/payment', [WaiterApiController::class, 'recordPayment']);
    
    // Sales & Reports
    Route::get('/waiter/sales/daily', [WaiterApiController::class, 'getDailySales']);
    
    // Reconciliation
    Route::get('/waiter/reconciliation', [WaiterApiController::class, 'getReconciliation']);
    
    // Notifications
    Route::get('/waiter/notifications', [WaiterApiController::class, 'getNotifications']);
    Route::post('/waiter/notifications/{notificationId}/read', [WaiterApiController::class, 'markNotificationRead']);
    Route::post('/waiter/notifications/read-all', [WaiterApiController::class, 'markAllNotificationsRead']);
});

