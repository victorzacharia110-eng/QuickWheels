<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Owner\OwnerDashboardController;
use App\Http\Controllers\Api\Owner\VehicleController;
use App\Http\Controllers\Api\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\Api\Customer\PaymentController as CustomerPaymentController;
use App\Http\Controllers\Api\Customer\ReviewController;
use App\Http\Controllers\Api\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Api\Employee\MaintenanceController;
use App\Http\Controllers\Api\Employee\EmployeeDashboardController;
use App\Http\Controllers\Api\Employee\BookingController as EmployeeBookingController;
use App\Http\Controllers\Api\Employee\VehicleController as EmployeeVehicleController;
use App\Http\Controllers\Api\Payments\PaymentController;
use App\Http\Controllers\Api\Contracts\ContractController;
use App\Http\Controllers\Api\Contracts\ContractController as ContractsContractController;
use App\Http\Controllers\Api\Contracts\ContractTemplateController;
use App\Http\Controllers\Api\GpsController;
use App\Http\Controllers\Api\ContractPdfController;
use App\Http\Controllers\Api\SiteContentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==================== AUTH ROUTES ====================
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::patch('/update-nida', [AuthController::class, 'updateNida']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });
});

// ==================== PUBLIC VEHICLE ROUTES ====================
Route::get('/vehicles', [VehicleController::class, 'index']);
Route::get('/vehicles/{id}', [VehicleController::class, 'show']);

// ==================== PROTECTED ROUTES (Auth Required) ====================
Route::middleware('auth:sanctum')->group(function () {
    
    // ==================== OWNER ROUTES ====================
    Route::prefix('owner')->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [OwnerDashboardController::class, 'index']);
        Route::get('/dashboard/stats', [OwnerDashboardController::class, 'stats']);
        Route::get('/dashboard/revenue', [OwnerDashboardController::class, 'revenue']);
        Route::get('/dashboard/recent-bookings', [OwnerDashboardController::class, 'recentBookings']);
        Route::get('/dashboard/chart', [OwnerDashboardController::class, 'chart']);
        Route::get('/dashboard/vehicle-performance', [OwnerDashboardController::class, 'vehiclePerformance']);
        
        // Vehicles
        Route::get('/vehicles', [VehicleController::class, 'index']);
        Route::post('/vehicles', [VehicleController::class, 'store']);
        Route::get('/vehicles/{id}', [VehicleController::class, 'show']);
        Route::put('/vehicles/{id}', [VehicleController::class, 'update']);
        Route::delete('/vehicles/{id}', [VehicleController::class, 'destroy']);
        Route::patch('/vehicles/{id}/status', [VehicleController::class, 'updateStatus']);
        Route::get('/vehicles/stats', [VehicleController::class, 'stats']);
        
        // Bookings
        Route::get('/bookings', [CustomerBookingController::class, 'index']);
        Route::get('/bookings/{id}', [CustomerBookingController::class, 'show']);
        Route::post('/bookings/{id}/approve', [CustomerBookingController::class, 'approve']);
        Route::post('/bookings/{id}/reject', [CustomerBookingController::class, 'reject']);
        Route::post('/bookings/{id}/complete', [CustomerBookingController::class, 'complete']);
        Route::post('/bookings/{id}/cancel', [CustomerBookingController::class, 'cancel']);
        
        // Employees
        Route::get('/employees', [EmployeeDashboardController::class, 'index']);
        Route::post('/employees', [EmployeeDashboardController::class, 'store']);
        Route::get('/employees/{id}', [EmployeeDashboardController::class, 'show']);
        Route::put('/employees/{id}', [EmployeeDashboardController::class, 'update']);
        Route::delete('/employees/{id}', [EmployeeDashboardController::class, 'destroy']);
        Route::patch('/employees/{id}/toggle-status', [EmployeeDashboardController::class, 'toggleStatus']);
        Route::post('/employees/{id}/assign-vehicle', [EmployeeDashboardController::class, 'assignVehicle']);
        Route::delete('/employees/{id}/remove-vehicle', [EmployeeDashboardController::class, 'removeVehicle']);
        Route::get('/employees/stats', [EmployeeDashboardController::class, 'stats']);
        Route::get('/employees/with-vehicles', [EmployeeDashboardController::class, 'withVehicles']);
        Route::get('/employees/without-vehicles', [EmployeeDashboardController::class, 'withoutVehicles']);
        Route::get('/employees/email/{email}', [EmployeeDashboardController::class, 'getByEmail']);
        Route::get('/employees/name/{name}', [EmployeeDashboardController::class, 'getByName']);
        Route::get('/employees/dashboard', [EmployeeDashboardController::class, 'dashboard']);
        
        // Contracts (Owner)
        Route::get('/contracts', [ContractsContractController::class, 'index']);
        Route::post('/contracts', [ContractsContractController::class, 'store']);
        Route::get('/contracts/{id}', [ContractsContractController::class, 'show']);
        Route::put('/contracts/{id}', [ContractsContractController::class, 'update']);
        Route::delete('/contracts/{id}', [ContractsContractController::class, 'destroy']);
        Route::post('/contracts/{id}/payments', [ContractsContractController::class, 'recordPayment']);
        Route::post('/contracts/{id}/sign-owner', [ContractsContractController::class, 'signOwner']);
        Route::post('/contracts/{id}/activate', [ContractsContractController::class, 'activate']);
        Route::post('/contracts/{id}/complete', [ContractsContractController::class, 'complete']);
        Route::post('/contracts/{id}/cancel', [ContractsContractController::class, 'cancel']);
        Route::get('/contracts/stats', [ContractsContractController::class, 'stats']);
        Route::get('/contracts/dashboard', [ContractsContractController::class, 'dashboard']);
        Route::get('/contracts/driver/{driverId}', [ContractsContractController::class, 'getByDriver']);
        
        // Payments
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::get('/payments/{id}', [PaymentController::class, 'show']);
        Route::post('/payments/{id}/approve', [PaymentController::class, 'approve']);
        Route::post('/payments/{id}/reject', [PaymentController::class, 'reject']);
        Route::get('/payments/stats', [PaymentController::class, 'stats']);
        Route::get('/payments/dashboard', [PaymentController::class, 'dashboard']);
        Route::get('/payments/by-method', [PaymentController::class, 'getByMethod']);
        Route::get('/payments/contract/{contractId}', [PaymentController::class, 'getByContract']);
        Route::get('/payments/driver/{driverId}', [PaymentController::class, 'getByDriver']);
        
        // GPS Tracking (Owner)
        Route::post('/gps/update', [GpsController::class, 'updateLocation']);
        Route::get('/gps/vehicle/{vehicleId}/latest', [GpsController::class, 'getLatestLocation']);
        Route::get('/gps/vehicle/{vehicleId}/history', [GpsController::class, 'getVehicleHistory']);
        Route::get('/gps/all-latest', [GpsController::class, 'getAllLatest']);
    });
    
    // ==================== EMPLOYEE ROUTES ====================
    Route::prefix('employee')->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [EmployeeDashboardController::class, 'index']);
        Route::get('/dashboard/stats', [EmployeeDashboardController::class, 'stats']);
        
        // Bookings
        Route::get('/bookings', [EmployeeBookingController::class, 'index']);
        Route::get('/bookings/{id}', [EmployeeBookingController::class, 'show']);
        Route::put('/bookings/{id}/status', [EmployeeBookingController::class, 'updateStatus']);
        Route::post('/bookings/{id}/confirm', [EmployeeBookingController::class, 'confirm']);
        
        // Vehicles
        Route::get('/vehicles', [EmployeeVehicleController::class, 'index']);
        Route::get('/vehicles/{id}', [EmployeeVehicleController::class, 'show']);
        
        // Maintenance
        Route::get('/maintenance', [MaintenanceController::class, 'index']);
        Route::post('/maintenance', [MaintenanceController::class, 'store']);
        Route::put('/maintenance/{id}', [MaintenanceController::class, 'update']);
        Route::post('/maintenance/{id}/complete', [MaintenanceController::class, 'complete']);
        
        // Customers
        Route::get('/customers', [App\Http\Controllers\Api\Employee\CustomerController::class, 'index']);
        Route::get('/customers/{id}', [App\Http\Controllers\Api\Employee\CustomerController::class, 'show']);
    });
    
    // ==================== CUSTOMER ROUTES ====================
    Route::prefix('customer')->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [CustomerDashboardController::class, 'index']);
        Route::get('/available-vehicles', [CustomerDashboardController::class, 'availableVehicles']);
        Route::get('/available-drivers', [CustomerDashboardController::class, 'availableDrivers']);
        Route::get('/nearby-drivers', [CustomerDashboardController::class, 'nearbyDrivers']);
        
        // Bookings / Ride Request
        Route::post('/bookings', [CustomerBookingController::class, 'store']);
        Route::get('/bookings', [CustomerBookingController::class, 'index']);
        Route::get('/bookings/{id}', [CustomerBookingController::class, 'show']);
        Route::post('/bookings/{id}/cancel', [CustomerBookingController::class, 'cancel']);
        
        // Quick Ride
        Route::post('/request-ride', [CustomerDashboardController::class, 'requestRide']);
        Route::get('/my-rides', [CustomerDashboardController::class, 'myRides']);
        Route::post('/rides/{id}/cancel', [CustomerDashboardController::class, 'cancelRide']);
        
        // Payments
        Route::post('/payments', [CustomerPaymentController::class, 'process']);
        Route::get('/payments', [CustomerPaymentController::class, 'index']);
        Route::get('/payments/{id}', [CustomerPaymentController::class, 'show']);
        
        // Reviews
        Route::post('/reviews/vehicle/{vehicleId}', [ReviewController::class, 'store']);
        Route::get('/reviews', [ReviewController::class, 'index']);
    });
});

// ==================== CONTRACT ROUTES (Full) ====================
Route::middleware('auth:sanctum')->prefix('contracts')->group(function () {
    Route::get('/', [ContractController::class, 'index']);
    Route::post('/', [ContractController::class, 'store']);
    Route::get('/{id}', [ContractController::class, 'show']);
    Route::put('/{id}', [ContractController::class, 'update']);
    Route::delete('/{id}', [ContractController::class, 'destroy']);
    Route::post('/{id}/payments', [ContractController::class, 'recordPayment']);
    Route::post('/{id}/sign-customer', [ContractController::class, 'signCustomer']);
    Route::post('/{id}/sign-owner', [ContractController::class, 'signOwner']);
    Route::post('/{id}/send', [ContractController::class, 'send']);
    Route::post('/{id}/activate', [ContractController::class, 'activate']);
    Route::post('/{id}/complete', [ContractController::class, 'complete']);
    Route::post('/{id}/cancel', [ContractController::class, 'cancel']);
    Route::get('/stats', [ContractController::class, 'stats']);
    Route::get('/dashboard', [ContractController::class, 'dashboard']);
    Route::get('/driver/{driverId}', [ContractController::class, 'getByDriver']);
    Route::get('/driver-name/{driverName}', [ContractController::class, 'getByDriverName']);
    Route::get('/templates', [ContractTemplateController::class, 'getTemplates']);
    Route::get('/template/{type}', [ContractTemplateController::class, 'getTemplate']);
    Route::post('/preview', [ContractTemplateController::class, 'preview']);
    Route::get('/{id}/download', [ContractController::class, 'download']);
    
    // Contract PDF
    Route::get('/{id}/pdf', [ContractPdfController::class, 'download']);
    Route::get('/{id}/pdf/preview', [ContractPdfController::class, 'preview']);
});

// ==================== PAYMENT ROUTES (Full) ====================
Route::middleware('auth:sanctum')->prefix('payments')->group(function () {
    Route::get('/', [PaymentController::class, 'index']);
    Route::post('/', [PaymentController::class, 'store']);
    Route::get('/{id}', [PaymentController::class, 'show']);
    Route::put('/{id}', [PaymentController::class, 'update']);
    Route::delete('/{id}', [PaymentController::class, 'destroy']);
    Route::post('/{id}/approve', [PaymentController::class, 'approve']);
    Route::post('/{id}/reject', [PaymentController::class, 'reject']);
    Route::get('/stats', [PaymentController::class, 'stats']);
    Route::get('/dashboard', [PaymentController::class, 'dashboard']);
    Route::get('/by-method', [PaymentController::class, 'getByMethod']);
    Route::get('/contract/{contractId}', [PaymentController::class, 'getByContract']);
    Route::get('/driver/{driverId}', [PaymentController::class, 'getByDriver']);
    Route::get('/driver-name/{driverName}', [PaymentController::class, 'getByDriverName']);
    Route::get('/status/{status}', [PaymentController::class, 'getByStatus']);
});

// ==================== SITE CONTENT ====================
Route::get('/site-content', [SiteContentController::class, 'index']);

Route::middleware('auth:sanctum')->prefix('owner')->group(function () {
    Route::put('/site-content', [SiteContentController::class, 'update']);
});

// ==================== USER ROUTE ====================
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');