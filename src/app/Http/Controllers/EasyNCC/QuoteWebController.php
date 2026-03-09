<?php

namespace App\Http\Controllers\EasyNCC;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Inertia\Inertia;

class QuoteWebController extends Controller
{
    public function index()
    {
        return Inertia::render('EasyNCC/Quotes/Index');
    }

    public function create()
    {
        return Inertia::render('EasyNCC/Quotes/Form');
    }

    public function show($id)
    {
        $quote = Quote::with(['user:id,name,surname', 'creator:id,name,surname', 'updater:id,name,surname'])
            ->findOrFail($id);

        return Inertia::render('EasyNCC/Quotes/Show', [
            'quote' => $quote,
        ]);
    }

    public function edit($id)
    {
        $quote = Quote::with(['user:id,name,surname'])
            ->findOrFail($id);

        return Inertia::render('EasyNCC/Quotes/Form', [
            'quote' => $quote,
        ]);
    }
}
