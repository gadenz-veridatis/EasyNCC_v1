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
use App\Http\Controllers\API\DictionaryController;
use App\Http\Controllers\Api\DriverAttachmentController;
use App\Http\Controllers\Api\VehicleAttachmentController;
use App\Http\Controllers\Api\VehicleUnavailabilityController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AccountingTransactionController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\SettingsController;

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
        return $request->user()->load(['company', 'driverProfile', 'clientProfile', 'operatorProfile']);
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

    // Driver Attachments - admin and operator can manage
    Route::middleware(['role:super-admin,admin,operator'])->group(function () {
        Route::get('users/{user}/attachments', [DriverAttachmentController::class, 'index']);
        Route::post('users/{user}/attachments', [DriverAttachmentController::class, 'store']);
        Route::get('users/{user}/attachments/{attachment}/download', [DriverAttachmentController::class, 'download']);
        Route::put('users/{user}/attachments/{attachment}', [DriverAttachmentController::class, 'update']);
        Route::patch('users/{user}/attachments/{attachment}', [DriverAttachmentController::class, 'update']);
        Route::delete('users/{user}/attachments/{attachment}', [DriverAttachmentController::class, 'destroy']);
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

    // Vehicle Attachments - admin and operator can manage
    Route::middleware(['role:super-admin,admin,operator'])->group(function () {
        Route::get('vehicles/{vehicle}/attachments', [VehicleAttachmentController::class, 'index']);
        Route::post('vehicles/{vehicle}/attachments', [VehicleAttachmentController::class, 'store']);
        Route::get('vehicles/{vehicle}/attachments/{attachment}/download', [VehicleAttachmentController::class, 'download']);
        Route::put('vehicles/{vehicle}/attachments/{attachment}', [VehicleAttachmentController::class, 'update']);
        Route::patch('vehicles/{vehicle}/attachments/{attachment}', [VehicleAttachmentController::class, 'update']);
        Route::delete('vehicles/{vehicle}/attachments/{attachment}', [VehicleAttachmentController::class, 'destroy']);
    });

    // Vehicle Unavailabilities - admin and operator can manage
    Route::middleware(['role:super-admin,admin,operator'])->group(function () {
        Route::get('vehicles/{vehicle}/unavailabilities', [VehicleUnavailabilityController::class, 'index']);
        Route::post('vehicles/{vehicle}/unavailabilities', [VehicleUnavailabilityController::class, 'store']);
        Route::put('vehicles/{vehicle}/unavailabilities/{unavailability}', [VehicleUnavailabilityController::class, 'update']);
        Route::patch('vehicles/{vehicle}/unavailabilities/{unavailability}', [VehicleUnavailabilityController::class, 'update']);
        Route::delete('vehicles/{vehicle}/unavailabilities/{unavailability}', [VehicleUnavailabilityController::class, 'destroy']);
    });

    // Services - all can view, admin/operator can manage
    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{service}', [ServiceController::class, 'show']);

    Route::middleware(['role:super-admin,admin,operator'])->group(function () {
        Route::post('services/check-overlaps', [ServiceController::class, 'checkOverlaps']);
        Route::post('services', [ServiceController::class, 'store']);
        Route::put('services/{service}', [ServiceController::class, 'update']);
        Route::patch('services/{service}', [ServiceController::class, 'update']);
        Route::delete('services/{service}', [ServiceController::class, 'destroy']);
    });

    // Activities - admin and operator can CRU, only admin can delete
    Route::middleware(['role:super-admin,admin,operator'])->group(function () {
        Route::get('activities', [ActivityController::class, 'index']);
        Route::post('activities', [ActivityController::class, 'store']);
        Route::get('activities/{activity}', [ActivityController::class, 'show']);
        Route::put('activities/{activity}', [ActivityController::class, 'update']);
        Route::patch('activities/{activity}', [ActivityController::class, 'update']);
    });

    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::delete('activities/{activity}', [ActivityController::class, 'destroy']);
    });

    // Accounting Transactions - admin and operator can CRU, only admin can delete
    Route::middleware(['role:super-admin,admin,operator'])->group(function () {
        Route::get('accounting-transactions', [AccountingTransactionController::class, 'index']);
        Route::post('accounting-transactions', [AccountingTransactionController::class, 'store']);
        Route::get('accounting-transactions/{accountingTransaction}', [AccountingTransactionController::class, 'show']);
        Route::put('accounting-transactions/{accountingTransaction}', [AccountingTransactionController::class, 'update']);
        Route::patch('accounting-transactions/{accountingTransaction}', [AccountingTransactionController::class, 'update']);
    });

    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::delete('accounting-transactions/{accountingTransaction}', [AccountingTransactionController::class, 'destroy']);
    });

    // Tasks - all authenticated users can view, admin/operator can CRU, only admin can delete
    Route::middleware(['role:super-admin,admin,operator,driver,accountant'])->group(function () {
        Route::get('tasks', [TaskController::class, 'index']);
        Route::get('tasks/{task}', [TaskController::class, 'show']);
        Route::put('tasks/{task}', [TaskController::class, 'update']);
        Route::patch('tasks/{task}', [TaskController::class, 'update']);
    });

    Route::middleware(['role:super-admin,admin,operator'])->group(function () {
        Route::post('tasks', [TaskController::class, 'store']);
    });

    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::delete('tasks/{task}', [TaskController::class, 'destroy']);
    });

    // Dictionaries - admin can manage, others can read
    Route::get('dictionaries/{type}', [DictionaryController::class, 'index']);
    Route::get('dictionaries/{type}/{id}', [DictionaryController::class, 'show']);

    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::post('dictionaries/{type}', [DictionaryController::class, 'store']);
        Route::put('dictionaries/{type}/{id}', [DictionaryController::class, 'update']);
        Route::patch('dictionaries/{type}/{id}', [DictionaryController::class, 'update']);
        Route::delete('dictionaries/{type}/{id}', [DictionaryController::class, 'destroy']);
    });

    // Settings - only admin and super-admin can access
    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::get('settings', [SettingsController::class, 'index']);
        Route::put('settings', [SettingsController::class, 'update']);
        Route::get('settings/accounting-entries', [SettingsController::class, 'getAccountingEntries']);
    });
});
