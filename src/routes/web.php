<?php

use App\Http\Controllers\EasyNCC\DashboardController;
use App\Http\Controllers\EasyNCC\VehicleWebController;
use App\Http\Controllers\EasyNCC\ServiceWebController;
use App\Http\Controllers\EasyNCC\UserWebController;
use App\Http\Controllers\EasyNCC\QuoteWebController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified', 'active'])->group(function () {

    // EasyNCC Routes
    Route::prefix('easyncc')->name('easyncc.')->group(function () {

        // Dashboard (currently disabled - calendar is the landing page)
        // Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Vehicles
        Route::prefix('vehicles')->name('vehicles.')->middleware('role:super-admin,admin,operator')->group(function () {
            Route::get('/', [VehicleWebController::class, 'index'])->name('index');
            Route::get('/create', [VehicleWebController::class, 'create'])->name('create');
            Route::get('/{id}', [VehicleWebController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [VehicleWebController::class, 'edit'])->name('edit');
        });

        // Services
        Route::prefix('services')->name('services.')->group(function () {
            Route::get('/', [ServiceWebController::class, 'index'])->name('index');
            Route::get('/calendar', [ServiceWebController::class, 'calendar'])->name('calendar');
            Route::get('/create', [ServiceWebController::class, 'create'])->name('create')->middleware('role:super-admin,admin,operator');
            Route::get('/{id}', [ServiceWebController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [ServiceWebController::class, 'edit'])->name('edit')->middleware('role:super-admin,admin,operator');
        });

        // Users
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserWebController::class, 'index'])->name('index')->middleware('role:super-admin,admin,operator');
            Route::get('/create', [UserWebController::class, 'create'])->name('create')->middleware('role:super-admin,admin,operator');
            Route::get('/{id}', [UserWebController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [UserWebController::class, 'edit'])->name('edit')->middleware('role:super-admin,admin,operator');
        });

        // Quotes (Preventivi)
        Route::prefix('quotes')->name('quotes.')->middleware('role:super-admin,admin,operator')->group(function () {
            Route::get('/', [QuoteWebController::class, 'index'])->name('index');
            Route::get('/create', [QuoteWebController::class, 'create'])->name('create');
            Route::get('/{id}', [QuoteWebController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [QuoteWebController::class, 'edit'])->name('edit');
        });

        // Drivers
        Route::prefix('drivers')->name('drivers.')->middleware('role:super-admin,admin,operator')->group(function () {
            Route::get('/', function () {
                return inertia('EasyNCC/Drivers/Index');
            })->name('index');
        });

        // Committenti
        Route::prefix('committenti')->name('committenti.')->middleware('role:super-admin,admin,operator')->group(function () {
            Route::get('/', function () {
                return inertia('EasyNCC/Committenti/Index');
            })->name('index');
        });

        // Fornitori
        Route::prefix('fornitori')->name('fornitori.')->middleware('role:super-admin,admin,operator')->group(function () {
            Route::get('/', function () {
                return inertia('EasyNCC/Fornitori/Index');
            })->name('index');
        });

        // Activities
        Route::prefix('activities')->name('activities.')->middleware('role:super-admin,admin,operator')->group(function () {
            Route::get('/', function () {
                return inertia('EasyNCC/Activities/Index');
            })->name('index');
            Route::get('/create', function () {
                return inertia('EasyNCC/Activities/Form');
            })->name('create');
            Route::get('/{id}/edit', function ($id) {
                // Load activity data for edit
                $activity = \App\Models\Activity::with(['activityType', 'supplier', 'company'])->findOrFail($id);
                return inertia('EasyNCC/Activities/Form', [
                    'activity' => $activity
                ]);
            })->name('edit');
        });

        // Tasks
        Route::prefix('tasks')->name('tasks.')->middleware('role:super-admin,admin,operator,driver,accountant')->group(function () {
            Route::get('/', function () {
                return inertia('EasyNCC/Tasks/Index');
            })->name('index');
            Route::get('/create', function () {
                return inertia('EasyNCC/Tasks/Form');
            })->name('create')->middleware('role:super-admin,admin,operator');
            Route::get('/{id}/edit', function ($id) {
                // Load task data for edit
                $task = \App\Models\Task::with(['assignedUser', 'company'])->findOrFail($id);
                return inertia('EasyNCC/Tasks/Form', [
                    'task' => $task
                ]);
            })->name('edit');
        });

        // Accounting Transactions
        Route::prefix('accounting-transactions')->name('accounting-transactions.')->middleware('role:super-admin,admin,operator')->group(function () {
            Route::get('/', function () {
                return inertia('EasyNCC/AccountingTransactions/Index');
            })->name('index');
            Route::get('/create', function () {
                return inertia('EasyNCC/AccountingTransactions/Form');
            })->name('create');
            Route::get('/{id}/edit', function ($id) {
                // Load transaction data for edit
                $transaction = \App\Models\AccountingTransaction::with([
                    'service',
                    'accountingEntry',
                    'counterpart',
                    'company'
                ])->findOrFail($id);
                return inertia('EasyNCC/AccountingTransactions/Form', [
                    'transaction' => $transaction
                ]);
            })->name('edit');
        });

        // Dictionaries
        Route::prefix('dictionaries')->name('dictionaries.')->middleware('role:super-admin,admin')->group(function () {
            Route::get('/dress-codes', function () {
                return inertia('EasyNCC/Dictionaries/DressCodes');
            })->name('dress-codes');

            Route::get('/payment-types', function () {
                return inertia('EasyNCC/Dictionaries/PaymentTypes');
            })->name('payment-types');

            Route::get('/driver-attachment-types', function () {
                return inertia('EasyNCC/Dictionaries/DriverAttachmentTypes');
            })->name('driver-attachment-types');

            Route::get('/vehicle-attachment-types', function () {
                return inertia('EasyNCC/Dictionaries/VehicleAttachmentTypes');
            })->name('vehicle-attachment-types');

            Route::get('/service-statuses', function () {
                return inertia('EasyNCC/Dictionaries/ServiceStatuses');
            })->name('service-statuses');

            Route::get('/ztl', function () {
                return inertia('EasyNCC/Dictionaries/Ztl');
            })->name('ztl');

            Route::get('/activity-types', function () {
                return inertia('EasyNCC/Dictionaries/ActivityTypes');
            })->name('activity-types');

            Route::get('/service-types', function () {
                return inertia('EasyNCC/Dictionaries/ServiceTypes');
            })->name('service-types');

            Route::get('/accounting-entries', function () {
                return inertia('EasyNCC/Dictionaries/AccountingEntries');
            })->name('accounting-entries');

            Route::get('/transaction-statuses', function () {
                return inertia('EasyNCC/Dictionaries/TransactionStatuses');
            })->name('transaction-statuses');
        });

        // Settings
        Route::get('/settings', function () {
            return inertia('EasyNCC/Settings/Index');
        })->name('settings')->middleware('role:super-admin,admin');

        Route::get('/settings/telegram', function () {
            return inertia('EasyNCC/Settings/Telegram');
        })->name('settings.telegram')->middleware('role:super-admin,admin');

        Route::get('/settings/pricing', function () {
            return inertia('EasyNCC/Settings/Pricing');
        })->name('settings.pricing')->middleware('role:super-admin,admin');

        // Telegram
        Route::prefix('telegram')->name('telegram.')->middleware('role:super-admin,admin')->group(function () {
            Route::get('/users', function () {
                return inertia('EasyNCC/Telegram/Users');
            })->name('users');
        });

        // Telegram Chat - accessible to admin, super-admin, and driver
        Route::get('/telegram/chat', function () {
            return inertia('EasyNCC/Telegram/Chat');
        })->name('telegram.chat')->middleware('role:super-admin,admin,driver');

        // Companies
        Route::prefix('companies')->name('companies.')->middleware('role:super-admin')->group(function () {
            Route::get('/', function () {
                return inertia('EasyNCC/Companies/Index');
            })->name('index');

            Route::get('/create', function () {
                return inertia('EasyNCC/Companies/Form');
            })->name('create');

            Route::get('/{id}', function ($id) {
                $company = \App\Models\Company::findOrFail($id);
                return inertia('EasyNCC/Companies/Show', ['company' => $company]);
            })->name('show');

            Route::get('/{id}/edit', function ($id) {
                $company = \App\Models\Company::findOrFail($id);
                return inertia('EasyNCC/Companies/Form', ['company' => $company]);
            })->name('edit');
        });
    });

    // Landing page: Services Calendar
    Route::get('/', function () {
        return redirect()->route('easyncc.services.calendar');
    });

});
