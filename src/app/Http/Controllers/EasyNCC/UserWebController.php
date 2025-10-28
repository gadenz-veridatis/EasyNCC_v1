<?php

namespace App\Http\Controllers\EasyNCC;

use App\Http\Controllers\Controller;
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

    public function edit(string $id): Response
    {
        return Inertia::render('EasyNCC/Users/Form', ['userId' => $id]);
    }
}
