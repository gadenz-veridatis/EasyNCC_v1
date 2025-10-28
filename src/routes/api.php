<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DressCodeController;
use App\Http\Controllers\Api\ServiceStatusController;
use App\Http\Controllers\Api\PaymentTypeController;

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

// Public routes
Route::post('/login', [UserController::class, 'login'])->name('api.login');

// Protected routes - require authentication + active user
Route::middleware(['auth:sanctum', 'active', 'company.context'])->group(function () {

    // Current user info
    Route::get('/user', function (Request $request) {
        return $request->user()->load(['company', 'driverProfile', 'clientProfile', 'intermediaryProfile', 'supplierProfile']);
    });

    // Dashboard - accessible to all authenticated users
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/upcoming-services', [DashboardController::class, 'upcomingServices']);

    // Companies - only super-admin and admin
    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::apiResource('companies', CompanyController::class);
    });

    // Users - admin and operator can CRU, only admin can delete
    Route::middleware(['role:super-admin,admin,operator'])->group(function () {
        Route::get('users', [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);
        Route::get('users/{user}', [UserController::class, 'show']);
        Route::put('users/{user}', [UserController::class, 'update']);
        Route::patch('users/{user}', [UserController::class, 'update']);
    });

    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::delete('users/{user}', [UserController::class, 'destroy']);
    });

    // Vehicles - admin and operator can CRU, only admin can delete
    Route::middleware(['role:super-admin,admin,operator'])->group(function () {
        Route::get('vehicles', [VehicleController::class, 'index']);
        Route::post('vehicles', [VehicleController::class, 'store']);
        Route::get('vehicles/{vehicle}', [VehicleController::class, 'show']);
        Route::put('vehicles/{vehicle}', [VehicleController::class, 'update']);
        Route::patch('vehicles/{vehicle}', [VehicleController::class, 'update']);
    });

    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::delete('vehicles/{vehicle}', [VehicleController::class, 'destroy']);
    });

    // Services - all can view, admin/operator can manage
    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{service}', [ServiceController::class, 'show']);

    Route::middleware(['role:super-admin,admin,operator'])->group(function () {
        Route::post('services', [ServiceController::class, 'store']);
        Route::put('services/{service}', [ServiceController::class, 'update']);
        Route::patch('services/{service}', [ServiceController::class, 'update']);
        Route::delete('services/{service}', [ServiceController::class, 'destroy']);
    });

    // Dictionaries - admin can manage, others can read
    $dictionaries = [
        'dress-codes' => DressCodeController::class,
        'service-statuses' => ServiceStatusController::class,
        'payment-types' => PaymentTypeController::class,
    ];

    foreach ($dictionaries as $route => $controller) {
        Route::get($route, [$controller, 'index']);
        Route::get("{$route}/{id}", [$controller, 'show']);

        Route::middleware(['role:super-admin,admin'])->group(function () use ($route, $controller) {
            Route::post($route, [$controller, 'store']);
            Route::put("{$route}/{id}", [$controller, 'update']);
            Route::patch("{$route}/{id}", [$controller, 'update']);
            Route::delete("{$route}/{id}", [$controller, 'destroy']);
        });
    }
});
