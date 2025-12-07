<?php

namespace App\Http\Controllers\EasyNCC;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceWebController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('EasyNCC/Services/Index');
    }

    public function create(): Response
    {
        return Inertia::render('EasyNCC/Services/Form');
    }

    public function show(string $id): Response
    {
        return Inertia::render('EasyNCC/Services/Show', ['serviceId' => $id]);
    }

    public function edit(string $id): Response
    {
        $service = Service::with([
            'vehicle',
            'client',
            'intermediary',
            'supplier',
            'dressCode',
            'status',
            'drivers.driverProfile',
            'passengers',
            'stops',
            'payments',
            'costs',
            'activities.activityType',
            'activities.supplier',
            'accountingTransactions.accountingEntry',
            'accountingTransactions.counterpart',
            'tasks.assignedUsers',
            'company'
        ])->findOrFail($id);

        return Inertia::render('EasyNCC/Services/Form', ['service' => $service]);
    }

    public function calendar(): Response
    {
        return Inertia::render('EasyNCC/Services/Calendar');
    }
}
