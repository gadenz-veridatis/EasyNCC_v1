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
use App\Http\Controllers\Api\DictionaryController;
use App\Http\Controllers\Api\DriverAttachmentController;
use App\Http\Controllers\Api\VehicleAttachmentController;
use App\Http\Controllers\Api\VehicleUnavailabilityController;
use App\Http\Controllers\Api\DriverUnavailabilityController;
use App\Http\Controllers\Api\ServiceAttachmentController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AccountingTransactionController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\TelegramConfigController;
use App\Http\Controllers\Api\TelegramChatController;
use App\Http\Controllers\Api\TelegramUserController;
use App\Http\Controllers\Api\TelegramNotificationController;
use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Controllers\Api\GlobalSearchController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\QuoteEmailTemplateController;
use App\Http\Controllers\Api\SumupConfigController;
use App\Http\Controllers\Api\GmailAccountController;
use App\Http\Controllers\Api\SumUpWebhookController;
use App\Http\Controllers\Api\PricingDestinationController;
use App\Http\Controllers\Api\UnavailabilityCalendarController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\VehicleMileageEntryController;
use App\Http\Controllers\Api\TrashController;

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

// Telegram webhook - public endpoint (called by Telegram servers)
Route::post('/telegram/webhook/{companyId}', [TelegramWebhookController::class, 'handle'])
    ->where('companyId', '[0-9]+');

// SumUp webhook - public endpoint (called by SumUp servers)
Route::post('/sumup/webhook/{companyId}', [SumUpWebhookController::class, 'handle'])
    ->where('companyId', '[0-9]+');

// Protected routes - require authentication + active user
Route::middleware(['auth:sanctum', 'active', 'company.context'])->group(function () {

    // Current user info
    Route::get('/user', function (Request $request) {
        return $request->user()->load(['company', 'driverProfile', 'clientProfile', 'operatorProfile']);
    });

    // Global Search - accessible to all authenticated users
    Route::get('search', [GlobalSearchController::class, 'search']);

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
        Route::post('users/{user}/restore', [UserController::class, 'restore']);
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
        Route::post('vehicles/{vehicle}/restore', [VehicleController::class, 'restore']);
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

    // Global unavailability endpoints
    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::get('driver-unavailabilities', [DriverUnavailabilityController::class, 'listAll']);
        Route::post('driver-unavailabilities', [DriverUnavailabilityController::class, 'storeGlobal']);
        Route::get('vehicle-unavailabilities', [VehicleUnavailabilityController::class, 'listAll']);
        Route::post('vehicle-unavailabilities', [VehicleUnavailabilityController::class, 'storeGlobal']);
    });

    // Vehicle Unavailabilities - admin and operator can manage
    Route::middleware(['role:super-admin,admin,operator'])->group(function () {
        Route::get('vehicles/{vehicle}/unavailabilities', [VehicleUnavailabilityController::class, 'index']);
        Route::post('vehicles/{vehicle}/unavailabilities', [VehicleUnavailabilityController::class, 'store']);
        Route::put('vehicles/{vehicle}/unavailabilities/{unavailability}', [VehicleUnavailabilityController::class, 'update']);
        Route::patch('vehicles/{vehicle}/unavailabilities/{unavailability}', [VehicleUnavailabilityController::class, 'update']);
        Route::delete('vehicles/{vehicle}/unavailabilities/{unavailability}', [VehicleUnavailabilityController::class, 'destroy']);
    });

    // Driver Unavailabilities - admin and operator can manage
    Route::middleware(['role:super-admin,admin,operator'])->group(function () {
        Route::get('users/{user}/unavailabilities', [DriverUnavailabilityController::class, 'index']);
        Route::post('users/{user}/unavailabilities', [DriverUnavailabilityController::class, 'store']);
        Route::put('users/{user}/unavailabilities/{unavailability}', [DriverUnavailabilityController::class, 'update']);
        Route::patch('users/{user}/unavailabilities/{unavailability}', [DriverUnavailabilityController::class, 'update']);
        Route::delete('users/{user}/unavailabilities/{unavailability}', [DriverUnavailabilityController::class, 'destroy']);
    });

    // Vehicle Mileage Entries - admin and operator can manage
    Route::middleware(['role:super-admin,admin,operator'])->group(function () {
        Route::get('vehicles/{vehicle}/mileage-entries', [VehicleMileageEntryController::class, 'index']);
        Route::post('vehicles/{vehicle}/mileage-entries', [VehicleMileageEntryController::class, 'store']);
        Route::put('vehicles/{vehicle}/mileage-entries/{entry}', [VehicleMileageEntryController::class, 'update']);
        Route::delete('vehicles/{vehicle}/mileage-entries/{entry}', [VehicleMileageEntryController::class, 'destroy']);
    });

    // Unavailabilities calendar - aggregated view for calendar
    Route::get('unavailabilities/calendar', [UnavailabilityCalendarController::class, 'index']);

    // Services - all can view, admin/operator can manage
    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/form-data', [ServiceController::class, 'formData']);
    Route::get('services/{service}', [ServiceController::class, 'show']);

    Route::middleware(['role:super-admin,admin,operator'])->group(function () {
        Route::post('services/check-overlaps', [ServiceController::class, 'checkOverlaps']);
        Route::post('services/recalculate-overlaps', [ServiceController::class, 'recalculateOverlaps']);
        Route::post('services', [ServiceController::class, 'store']);
        Route::patch('services/{service}/inline', [ServiceController::class, 'inlineUpdate']);
        Route::put('services/{service}', [ServiceController::class, 'update']);
        Route::patch('services/{service}', [ServiceController::class, 'update']);
        Route::delete('services/{service}', [ServiceController::class, 'destroy']);
        Route::post('services/{service}/duplicate', [ServiceController::class, 'duplicate']);
        Route::post('services/{service}/return', [ServiceController::class, 'returnService']);
        // Service Attachments
        Route::get('services/{service}/attachments', [ServiceAttachmentController::class, 'index']);
        Route::post('services/{service}/attachments', [ServiceAttachmentController::class, 'store']);
        Route::get('services/{service}/attachments/{attachment}/download', [ServiceAttachmentController::class, 'download']);
        Route::delete('services/{service}/attachments/{attachment}', [ServiceAttachmentController::class, 'destroy']);
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
        Route::get('accounting-transactions/summary', [AccountingTransactionController::class, 'summary']);
        Route::get('accounting-transactions/services', [AccountingTransactionController::class, 'servicesForDropdown']);
        Route::get('accounting-transactions/counterparts', [AccountingTransactionController::class, 'counterpartsForDropdown']);
        Route::post('accounting-transactions', [AccountingTransactionController::class, 'store']);
        Route::post('accounting-transactions/batch', [AccountingTransactionController::class, 'batchUpsert']);
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

    // Contacts - admin and operator can manage
    Route::middleware(['role:super-admin,admin,operator'])->group(function () {
        Route::get('contacts/search', [ContactController::class, 'search']);
        Route::apiResource('contacts', ContactController::class);
    });

    // Quotes (Preventivi) - admin and operator can manage
    Route::middleware(['role:super-admin,admin,operator'])->group(function () {
        Route::get('quotes', [QuoteController::class, 'index']);
        Route::post('quotes', [QuoteController::class, 'store']);
        Route::post('quotes/calculate', [QuoteController::class, 'calculate']);
        Route::get('quotes/{quote}', [QuoteController::class, 'show']);
        Route::put('quotes/{quote}', [QuoteController::class, 'update']);
        Route::patch('quotes/{quote}', [QuoteController::class, 'update']);
        Route::delete('quotes/{quote}', [QuoteController::class, 'destroy']);
        Route::post('quotes/{id}/restore', [QuoteController::class, 'restore']);
        Route::post('quotes/{quote}/transition', [QuoteController::class, 'transition']);
        Route::get('quotes/{quote}/transitions', [QuoteController::class, 'getTransitions']);
        Route::post('quotes/{quote}/preview-email', [QuoteController::class, 'previewEmail']);
        Route::post('quotes/{quote}/duplicate', [QuoteController::class, 'duplicate']);
        Route::post('quotes/{quote}/create-version', [QuoteController::class, 'createVersion']);
        Route::get('quotes/{quote}/versions', [QuoteController::class, 'getVersions']);
        Route::post('quotes/{quote}/restore-version', [QuoteController::class, 'restoreVersion']);
    });

    // Quote Email Templates - admin can manage
    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::get('quote-email-templates', [QuoteEmailTemplateController::class, 'index']);
        Route::post('quote-email-templates', [QuoteEmailTemplateController::class, 'store']);
        Route::get('quote-email-templates/placeholders', [QuoteEmailTemplateController::class, 'placeholders']);
        Route::get('quote-email-templates/{id}', [QuoteEmailTemplateController::class, 'show']);
        Route::put('quote-email-templates/{id}', [QuoteEmailTemplateController::class, 'update']);
        Route::delete('quote-email-templates/{id}', [QuoteEmailTemplateController::class, 'destroy']);
        Route::post('quote-email-templates/{id}/set-default', [QuoteEmailTemplateController::class, 'setDefault']);
    });

    // SumUp Configs - admin can manage
    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::get('sumup-configs', [SumupConfigController::class, 'index']);
        Route::post('sumup-configs', [SumupConfigController::class, 'store']);
        Route::put('sumup-configs/{id}', [SumupConfigController::class, 'update']);
        Route::delete('sumup-configs/{id}', [SumupConfigController::class, 'destroy']);
    });

    // Gmail Accounts - admin can manage
    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::get('gmail-accounts', [GmailAccountController::class, 'index']);
        Route::post('gmail-accounts', [GmailAccountController::class, 'store']);
        Route::put('gmail-accounts/{id}', [GmailAccountController::class, 'update']);
        Route::delete('gmail-accounts/{id}', [GmailAccountController::class, 'destroy']);
        Route::post('gmail-accounts/{id}/test-connection', [GmailAccountController::class, 'testConnection']);
    });

    // Pricing Destinations - admin can manage, operator can read
    Route::middleware(['role:super-admin,admin,operator'])->group(function () {
        Route::get('pricing-destinations', [PricingDestinationController::class, 'index']);
    });
    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::post('pricing-destinations', [PricingDestinationController::class, 'store']);
        Route::put('pricing-destinations/{pricingDestination}', [PricingDestinationController::class, 'update']);
        Route::delete('pricing-destinations/{pricingDestination}', [PricingDestinationController::class, 'destroy']);
    });

    // Settings - only admin and super-admin can access
    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::get('settings', [SettingsController::class, 'index']);
        Route::put('settings', [SettingsController::class, 'update']);
        Route::get('settings/accounting-entries', [SettingsController::class, 'getAccountingEntries']);
        Route::get('settings/suppliers', [SettingsController::class, 'getSuppliers']);
        Route::get('settings/service-statuses', [SettingsController::class, 'getServiceStatuses']);
        Route::get('settings/company-data', [SettingsController::class, 'getCompanyData']);
        Route::post('settings/company-data', [SettingsController::class, 'updateCompanyData']);
    });

    // Public settings - accessible to all authenticated users
    Route::get('settings/public', [SettingsController::class, 'publicSettings']);

    // Telegram Notifications - accessible to all authenticated users
    Route::get('notifications', [TelegramNotificationController::class, 'index']);
    Route::get('notifications/unread-count', [TelegramNotificationController::class, 'unreadCount']);
    Route::post('notifications/mark-all-read', [TelegramNotificationController::class, 'markAllAsRead']);
    Route::post('notifications/{id}/mark-read', [TelegramNotificationController::class, 'markAsRead']);

    // Telegram Config - only admin and super-admin can access
    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::get('telegram/config', [TelegramConfigController::class, 'index']);
        Route::put('telegram/config', [TelegramConfigController::class, 'update']);
        Route::post('telegram/webhook/activate', [TelegramConfigController::class, 'activateWebhook']);
        Route::post('telegram/webhook/deactivate', [TelegramConfigController::class, 'deactivateWebhook']);
        Route::get('telegram/webhook/info', [TelegramConfigController::class, 'webhookInfo']);

        Route::get('telegram/users', [TelegramUserController::class, 'index']);
        Route::put('telegram/users/{id}/associate', [TelegramUserController::class, 'associate']);
        Route::get('telegram/users/available-drivers', [TelegramUserController::class, 'availableDrivers']);

        Route::get('telegram/chat/conversations', [TelegramChatController::class, 'conversations']);
        Route::get('telegram/chat/messages', [TelegramChatController::class, 'messages']);
        Route::post('telegram/chat/send', [TelegramChatController::class, 'send']);
        Route::post('telegram/chat/mark-read', [TelegramChatController::class, 'markAsRead']);
    });

    // Trash (Cestino) - admin and super-admin only
    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::get('trash/counts', [TrashController::class, 'counts']);
        Route::get('trash/{type}', [TrashController::class, 'index']);
        Route::post('trash/{type}/{id}/restore', [TrashController::class, 'restore']);
        Route::delete('trash/{type}/{id}', [TrashController::class, 'forceDelete']);
    });

    // Telegram Chat - driver access (filtered to own conversation)
    Route::middleware(['role:driver'])->group(function () {
        Route::get('telegram/chat/driver/conversation', [TelegramChatController::class, 'driverConversation']);
        Route::get('telegram/chat/driver/messages', [TelegramChatController::class, 'driverMessages']);
        Route::post('telegram/chat/driver/send', [TelegramChatController::class, 'driverSend']);
        Route::post('telegram/chat/driver/mark-read', [TelegramChatController::class, 'driverMarkAsRead']);
    });
});
