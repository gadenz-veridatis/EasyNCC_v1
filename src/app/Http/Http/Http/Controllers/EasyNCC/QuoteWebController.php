<?php

namespace App\Http\Controllers\EasyNCC;

use App\Http\Controllers\Controller;
use App\Models\GmailAccount;
use App\Models\PricingDestination;
use App\Models\Quote;
use App\Models\QuoteEmailTemplate;
use App\Models\Settings;
use App\Models\SumupConfig;
use App\Services\PricingCalculatorService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class QuoteWebController extends Controller
{
    public function index()
    {
        return Inertia::render('EasyNCC/Quotes/Index');
    }

    public function create()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $pricingDestinations = PricingDestination::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $pricingConfig = PricingCalculatorService::getConfig($companyId);

        return Inertia::render('EasyNCC/Quotes/Form', [
            'pricingDestinations' => $pricingDestinations,
            'pricingConfig' => $pricingConfig,
        ]);
    }

    public function show($id)
    {
        $quote = Quote::with([
            'user:id,name,surname',
            'creator:id,name,surname',
            'updater:id,name,surname',
            'contact',
            'sumupConfig',
            'gmailAccount',
            'emailTemplate',
            'service:id,pickup_datetime,pickup_location,status_id',
            'stateTransitions' => fn($q) => $q->with('actor:id,name,surname')->orderBy('created_at', 'desc'),
            'items',
        ])->findOrFail($id);

        $user = Auth::user();
        $companyId = $quote->company_id;

        // Load integration options for workflow
        $sumupConfigs = SumupConfig::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('merchant_name')
            ->get(['id', 'merchant_name', 'merchant_code']);

        $gmailAccounts = GmailAccount::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('account_label')
            ->get(['id', 'account_label', 'email_address']);

        $emailTemplates = QuoteEmailTemplate::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'subject', 'is_default']);

        // Load company defaults
        $settings = Settings::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->first();

        $defaults = [
            'sumup_config_id' => $settings->default_sumup_config_id ?? null,
            'gmail_account_id' => $settings->default_gmail_account_id ?? null,
            'email_template_id' => $settings->default_email_template_id ?? null,
        ];

        // Load all versions of this quote group
        $versions = Quote::withoutGlobalScopes()
            ->where('company_id', $quote->company_id)
            ->where('quote_group_id', $quote->quote_group_id)
            ->withCount('items')
            ->orderBy('version', 'desc')
            ->get();

        return Inertia::render('EasyNCC/Quotes/Show', [
            'quote' => $quote,
            'versions' => $versions,
            'integrationOptions' => [
                'sumup_configs' => $sumupConfigs,
                'gmail_accounts' => $gmailAccounts,
                'email_templates' => $emailTemplates,
                'defaults' => $defaults,
            ],
        ]);
    }

    public function edit($id)
    {
        // If editing an archived version, redirect to the active version
        $checkQuote = Quote::findOrFail($id);
        if (!$checkQuote->is_active_version) {
            $activeVersion = Quote::withoutGlobalScopes()
                ->where('company_id', $checkQuote->company_id)
                ->where('quote_group_id', $checkQuote->quote_group_id)
                ->where('is_active_version', true)
                ->first();
            if ($activeVersion) {
                return redirect("/easyncc/quotes/{$activeVersion->id}/edit");
            }
        }

        $quote = Quote::with([
            'user:id,name,surname',
            'creator:id,name,surname',
            'contact',
            'sumupConfig',
            'gmailAccount',
            'emailTemplate',
            'service:id,pickup_datetime,pickup_location,status_id',
            'stateTransitions' => fn($q) => $q->with('actor:id,name,surname')->orderBy('created_at', 'desc'),
            'items',
        ])->findOrFail($id);

        $companyId = $quote->company_id;

        $sumupConfigs = SumupConfig::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('merchant_name')
            ->get(['id', 'merchant_name', 'merchant_code']);

        $gmailAccounts = GmailAccount::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('account_label')
            ->get(['id', 'account_label', 'email_address']);

        $emailTemplates = QuoteEmailTemplate::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'subject', 'is_default']);

        $settings = Settings::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->first();

        $pricingDestinations = PricingDestination::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $pricingConfig = PricingCalculatorService::getConfig($companyId);

        // Load all versions of this quote group
        $versions = Quote::withoutGlobalScopes()
            ->where('company_id', $quote->company_id)
            ->where('quote_group_id', $quote->quote_group_id)
            ->withCount('items')
            ->orderBy('version', 'desc')
            ->get();

        return Inertia::render('EasyNCC/Quotes/Form', [
            'quote' => $quote,
            'versions' => $versions,
            'pricingDestinations' => $pricingDestinations,
            'pricingConfig' => $pricingConfig,
            'integrationOptions' => [
                'sumup_configs' => $sumupConfigs,
                'gmail_accounts' => $gmailAccounts,
                'email_templates' => $emailTemplates,
                'defaults' => [
                    'sumup_config_id' => $settings->default_sumup_config_id ?? null,
                    'gmail_account_id' => $settings->default_gmail_account_id ?? null,
                    'email_template_id' => $settings->default_email_template_id ?? null,
                ],
            ],
        ]);
    }

    public function pricingCalculator()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $pricingDestinations = PricingDestination::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $pricingConfig = PricingCalculatorService::getConfig($companyId);

        return Inertia::render('EasyNCC/Quotes/PricingCalculator', [
            'pricingDestinations' => $pricingDestinations,
            'pricingConfig' => $pricingConfig,
        ]);
    }
}
