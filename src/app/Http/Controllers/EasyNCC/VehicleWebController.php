<?php

namespace App\Http\Controllers\EasyNCC;

use App\Http\Controllers\Controller;
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

    public function edit(string $id): Response
    {
        return Inertia::render('EasyNCC/Vehicles/Form', ['vehicleId' => $id]);
    }
}
