<?php

namespace App\Http\Controllers\EasyNCC;

use App\Http\Controllers\Controller;
use App\Services\QuoteTemplateService;
use Inertia\Inertia;

class QuoteEmailTemplateWebController extends Controller
{
    public function index()
    {
        return Inertia::render('EasyNCC/Settings/QuoteEmailTemplates', [
            'availablePlaceholders' => QuoteTemplateService::getAvailablePlaceholders(),
        ]);
    }
}
