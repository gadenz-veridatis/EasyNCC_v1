<?php

namespace App\Http\Controllers\EasyNCC;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleWebController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('EasyNCC/Vehicles/Index');
    }

    public function create(): Response
    {
        return Inertia::render('EasyNCC/Vehicles/Form');
    }

    public function show(Request $request, string $id): Response
    {
        $vehicle = Vehicle::with(['company', 'assignedDrivers'])
            ->findOrFail($id);

        // Check if user can edit
        $canEdit = $request->user()->hasAnyRole(['super-admin', 'admin', 'operator']);

        return Inertia::render('EasyNCC/Vehicles/Show', [
            'vehicle' => $vehicle->toArray(),
            'canEdit' => $canEdit
        ]);
    }

    public function edit(string $id): Response
    {
        $vehicle = Vehicle::with(['company', 'assignedDrivers'])
            ->findOrFail($id);

        return Inertia::render('EasyNCC/Vehicles/Form', ['vehicle' => $vehicle]);
    }
}
