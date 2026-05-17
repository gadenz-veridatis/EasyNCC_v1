<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Task;
use App\Models\AccountingTransaction;
use App\Models\DriverUnavailability;
use App\Models\VehicleUnavailability;
use App\Models\DriverAttachment;
use App\Models\VehicleAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->isSuperAdmin()
            ? $request->input('company_id', $user->company_id)
            : $user->company_id;

        $today = Carbon::today()->toDateString();
        $tomorrow = Carbon::tomorrow()->toDateString();
        $weekFromNow = Carbon::today()->addDays(7)->toDateString();
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd = Carbon::now()->endOfMonth()->toDateString();
        $prevMonthStart = Carbon::now()->subMonth()->startOfMonth()->toDateString();
        $prevMonthEnd = Carbon::now()->subMonth()->endOfMonth()->toDateString();

        // === KPI ===
        $servicesToday = Service::where('company_id', $companyId)
            ->whereDate('pickup_datetime', $today)
            ->count();

        $tasksPending = Task::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'to_complete')
            ->count();

        $paymentsPending = AccountingTransaction::where('company_id', $companyId)
            ->where('transaction_type', 'sale')
            ->where('status', 'to_pay')
            ->count();

        $upcomingDeadlines = AccountingTransaction::where('company_id', $companyId)
            ->whereNotNull('document_due_date')
            ->whereDate('document_due_date', '<=', $weekFromNow)
            ->whereDate('document_due_date', '>=', $today)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->count();

        // === Services today + tomorrow ===
        $servicesTodayTomorrow = Service::where('company_id', $companyId)
            ->whereDate('pickup_datetime', '>=', $today)
            ->whereDate('pickup_datetime', '<=', $tomorrow)
            ->with([
                'drivers:id,name,surname',
                'drivers.driverProfile:user_id,color',
                'vehicle:id,license_plate,brand,model',
                'status:id,name,color_code',
                'passengers:id,service_id,surname,name',
            ])
            ->orderBy('pickup_datetime', 'asc')
            ->limit(20)
            ->get()
            ->map(function ($s) use ($today) {
                return [
                    'id' => $s->id,
                    'reference_number' => $s->reference_number,
                    'service_type' => $s->service_type,
                    'pickup_datetime' => $s->pickup_datetime,
                    'pickup_address' => $s->pickup_address,
                    'is_today' => Carbon::parse($s->pickup_datetime)->toDateString() === $today,
                    'passenger_name' => $s->passengers->first()
                        ? trim(($s->passengers->first()->surname ?? '') . ' ' . ($s->passengers->first()->name ?? ''))
                        : null,
                    'drivers' => $s->drivers->map(fn($d) => [
                        'id' => $d->id,
                        'name' => trim(($d->surname ?? '') . ' ' . ($d->name ?? '')),
                        'color' => $d->driverProfile->color ?? '#6c757d',
                    ]),
                    'vehicle' => $s->vehicle ? [
                        'license_plate' => $s->vehicle->license_plate,
                        'label' => trim(($s->vehicle->brand ?? '') . ' ' . ($s->vehicle->model ?? '')),
                    ] : null,
                    'status' => $s->status ? [
                        'name' => $s->status->name,
                        'color_code' => $s->status->color_code,
                    ] : null,
                ];
            });

        // === Tasks due soon ===
        $tasksDueSoon = Task::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'to_complete')
            ->where(function ($q) use ($weekFromNow) {
                $q->whereDate('due_date', '<=', $weekFromNow)
                  ->orWhereNull('due_date');
            })
            ->with('assignedUsers:id,name,surname')
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->limit(10)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'due_date' => $t->due_date,
                'service_id' => $t->service_id,
                'assigned_users' => $t->assignedUsers->map(fn($u) => trim(($u->surname ?? '') . ' ' . ($u->name ?? ''))),
            ]);

        // === Accounting summary ===
        $currentMonthRevenue = (float) AccountingTransaction::where('company_id', $companyId)
            ->where('transaction_type', 'sale')
            ->whereDate('transaction_date', '>=', $monthStart)
            ->whereDate('transaction_date', '<=', $monthEnd)
            ->sum('amount');

        $currentMonthCosts = (float) AccountingTransaction::where('company_id', $companyId)
            ->where('transaction_type', 'purchase')
            ->whereDate('transaction_date', '>=', $monthStart)
            ->whereDate('transaction_date', '<=', $monthEnd)
            ->sum('amount');

        $prevMonthRevenue = (float) AccountingTransaction::where('company_id', $companyId)
            ->where('transaction_type', 'sale')
            ->whereDate('transaction_date', '>=', $prevMonthStart)
            ->whereDate('transaction_date', '<=', $prevMonthEnd)
            ->sum('amount');

        $prevMonthCosts = (float) AccountingTransaction::where('company_id', $companyId)
            ->where('transaction_type', 'purchase')
            ->whereDate('transaction_date', '>=', $prevMonthStart)
            ->whereDate('transaction_date', '<=', $prevMonthEnd)
            ->sum('amount');

        // === Unavailabilities today ===
        $driverUnavail = DriverUnavailability::join('users', 'driver_unavailabilities.user_id', '=', 'users.id')
            ->where('users.company_id', $companyId)
            ->whereDate('driver_unavailabilities.start_date', '<=', $today)
            ->whereDate('driver_unavailabilities.end_date', '>=', $today)
            ->with(['user:id,name,surname', 'leaveType:id,name'])
            ->select('driver_unavailabilities.*')
            ->get()
            ->map(fn($u) => [
                'driver_name' => trim(($u->user->surname ?? '') . ' ' . ($u->user->name ?? '')),
                'reason' => $u->leaveType->name ?? '',
                'start_date' => $u->start_date->format('d/m'),
                'end_date' => $u->end_date->format('d/m'),
            ]);

        $vehicleUnavail = VehicleUnavailability::join('vehicles', 'vehicle_unavailabilities.vehicle_id', '=', 'vehicles.id')
            ->where('vehicles.company_id', $companyId)
            ->whereDate('vehicle_unavailabilities.start_date', '<=', $today)
            ->whereDate('vehicle_unavailabilities.end_date', '>=', $today)
            ->with(['vehicle:id,license_plate,brand,model', 'unavailabilityType:id,name'])
            ->select('vehicle_unavailabilities.*')
            ->get()
            ->map(fn($u) => [
                'vehicle_plate' => $u->vehicle->license_plate ?? '',
                'vehicle_label' => trim(($u->vehicle->brand ?? '') . ' ' . ($u->vehicle->model ?? '')),
                'reason' => $u->unavailabilityType->name ?? '',
                'start_date' => $u->start_date->format('d/m'),
                'end_date' => $u->end_date->format('d/m'),
            ]);

        // === Upcoming document deadlines ===
        $documentDeadlines = AccountingTransaction::where('company_id', $companyId)
            ->whereNotNull('document_due_date')
            ->whereDate('document_due_date', '>=', $today)
            ->whereDate('document_due_date', '<=', Carbon::today()->addDays(30)->toDateString())
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->with([
                'service:id,reference_number',
                'counterpart:id,name,surname',
            ])
            ->orderBy('document_due_date', 'asc')
            ->limit(10)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'document_due_date' => $t->document_due_date,
                'amount' => (float) $t->amount,
                'transaction_type' => $t->transaction_type,
                'status' => $t->status,
                'service_reference' => $t->service->reference_number ?? null,
                'service_id' => $t->service_id,
                'counterpart_name' => $t->counterpart
                    ? trim(($t->counterpart->surname ?? '') . ' ' . ($t->counterpart->name ?? ''))
                    : null,
            ]);

        // === Driver attachment expirations (within 90 days or already expired) ===
        $driverAttachmentExpirations = DriverAttachment::join('users', 'driver_attachments.user_id', '=', 'users.id')
            ->where('users.company_id', $companyId)
            ->whereNotNull('driver_attachments.expiration_date')
            ->whereDate('driver_attachments.expiration_date', '<=', Carbon::today()->addDays(90)->toDateString())
            ->with('user:id,name,surname')
            ->select('driver_attachments.*')
            ->orderBy('driver_attachments.expiration_date', 'asc')
            ->limit(15)
            ->get()
            ->map(fn($a) => [
                'type' => 'driver',
                'entity_name' => trim(($a->user->surname ?? '') . ' ' . ($a->user->name ?? '')),
                'document_type' => $a->attachment_type,
                'expiration_date' => $a->expiration_date->format('Y-m-d'),
                'is_expired' => $a->expiration_date->lt(Carbon::today()),
            ]);

        // === Vehicle attachment expirations (within 90 days or already expired) ===
        $vehicleAttachmentExpirations = VehicleAttachment::join('vehicles', 'vehicle_attachments.vehicle_id', '=', 'vehicles.id')
            ->where('vehicles.company_id', $companyId)
            ->whereNotNull('vehicle_attachments.expiration_date')
            ->whereDate('vehicle_attachments.expiration_date', '<=', Carbon::today()->addDays(90)->toDateString())
            ->with('vehicle:id,license_plate,brand,model')
            ->select('vehicle_attachments.*')
            ->orderBy('vehicle_attachments.expiration_date', 'asc')
            ->limit(15)
            ->get()
            ->map(fn($a) => [
                'type' => 'vehicle',
                'entity_name' => $a->vehicle->license_plate ?? '',
                'entity_label' => trim(($a->vehicle->brand ?? '') . ' ' . ($a->vehicle->model ?? '')),
                'document_type' => $a->attachment_type,
                'expiration_date' => $a->expiration_date->format('Y-m-d'),
                'is_expired' => $a->expiration_date->lt(Carbon::today()),
            ]);

        // Merge and sort all expirations
        $allExpirations = $driverAttachmentExpirations
            ->concat($vehicleAttachmentExpirations)
            ->sortBy('expiration_date')
            ->values()
            ->take(20);

        // Update KPI: count expired + expiring within 30 days
        $expiringDocuments = $allExpirations->filter(fn($e) =>
            Carbon::parse($e['expiration_date'])->lte(Carbon::today()->addDays(30))
        )->count();

        return response()->json([
            'kpi' => [
                'services_today' => $servicesToday,
                'tasks_pending' => $tasksPending,
                'payments_pending' => $paymentsPending,
                'upcoming_deadlines' => $upcomingDeadlines,
                'expiring_documents' => $expiringDocuments,
            ],
            'services_today_tomorrow' => $servicesTodayTomorrow,
            'tasks_due_soon' => $tasksDueSoon,
            'accounting_summary' => [
                'current_month' => [
                    'revenue' => round($currentMonthRevenue, 2),
                    'costs' => round($currentMonthCosts, 2),
                    'margin' => round($currentMonthRevenue - $currentMonthCosts, 2),
                ],
                'previous_month' => [
                    'revenue' => round($prevMonthRevenue, 2),
                    'costs' => round($prevMonthCosts, 2),
                    'margin' => round($prevMonthRevenue - $prevMonthCosts, 2),
                ],
            ],
            'unavailabilities_today' => [
                'drivers' => $driverUnavail,
                'vehicles' => $vehicleUnavail,
            ],
            'upcoming_document_deadlines' => $documentDeadlines,
            'document_expirations' => $allExpirations,
        ]);
    }
}
