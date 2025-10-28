<?php

namespace App\Http\Controllers\EasyNCC;

use App\Http\Controllers\Controller;
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

    public function edit(string $id): Response
    {
        return Inertia::render('EasyNCC/Services/Form', ['serviceId' => $id]);
    }

    public function calendar(): Response
    {
        return Inertia::render('EasyNCC/Services/Calendar');
    }
}
