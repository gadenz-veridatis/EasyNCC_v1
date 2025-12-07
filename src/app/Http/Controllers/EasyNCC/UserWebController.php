<?php

namespace App\Http\Controllers\EasyNCC;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserWebController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('EasyNCC/Users/Index');
    }

    public function create(): Response
    {
        return Inertia::render('EasyNCC/Users/Form');
    }

    public function show(Request $request, string $id): Response
    {
        $user = User::with(['company', 'driverProfile', 'clientProfile.businessContacts', 'operatorProfile'])
            ->findOrFail($id);

        // Check if user can edit
        $canEdit = $request->user()->hasAnyRole(['super-admin', 'admin', 'operator']);

        return Inertia::render('EasyNCC/Users/Show', [
            'user' => $user->toArray(),
            'canEdit' => $canEdit
        ]);
    }

    public function edit(string $id): Response
    {
        $user = User::with(['company', 'driverProfile', 'clientProfile.businessContacts', 'operatorProfile'])
            ->findOrFail($id);

        // Convert to array to ensure proper serialization for Inertia
        $userData = $user->toArray();

        return Inertia::render('EasyNCC/Users/Form', ['user' => $userData]);
    }
}
