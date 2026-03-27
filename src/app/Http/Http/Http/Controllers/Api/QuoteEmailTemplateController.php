<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuoteEmailTemplate;
use App\Services\QuoteTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuoteEmailTemplateController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $this->getCompanyId($request);

        $templates = QuoteEmailTemplate::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['creator:id,name,surname', 'updater:id,name,surname'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $templates]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:500',
            'body_html' => 'required|string',
            'is_default' => 'boolean',
        ]);

        $user = Auth::user();
        $companyId = $this->getCompanyId($request);

        // If setting as default, unset other defaults
        if (!empty($validated['is_default'])) {
            QuoteEmailTemplate::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $template = QuoteEmailTemplate::create(array_merge($validated, [
            'company_id' => $companyId,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Template email creato con successo',
            'data' => $template->load(['creator:id,name,surname', 'updater:id,name,surname']),
        ], 201);
    }

    public function show($id)
    {
        $user = Auth::user();
        $template = QuoteEmailTemplate::withoutGlobalScopes()
            ->where('id', $id)
            ->with(['creator:id,name,surname', 'updater:id,name,surname'])
            ->firstOrFail();

        return response()->json(['data' => $template]);
    }

    public function update(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $template = QuoteEmailTemplate::withoutGlobalScopes()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'subject' => 'sometimes|required|string|max:500',
            'body_html' => 'sometimes|required|string',
            'is_default' => 'boolean',
        ]);

        $user = Auth::user();

        // If setting as default, unset other defaults
        if (!empty($validated['is_default'])) {
            QuoteEmailTemplate::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('is_default', true)
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $template->update(array_merge($validated, [
            'updated_by' => $user->id,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Template email aggiornato con successo',
            'data' => $template->fresh()->load(['creator:id,name,surname', 'updater:id,name,surname']),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $template = QuoteEmailTemplate::withoutGlobalScopes()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template email eliminato con successo',
        ]);
    }

    /**
     * Set a template as the default for the company.
     */
    public function setDefault(Request $request, $id)
    {
        $companyId = $this->getCompanyId($request);
        $template = QuoteEmailTemplate::withoutGlobalScopes()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        // Unset all defaults
        QuoteEmailTemplate::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        $template->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Template impostato come predefinito',
            'data' => $template->fresh(),
        ]);
    }

    /**
     * Get available placeholders for templates.
     */
    public function placeholders()
    {
        return response()->json([
            'data' => QuoteTemplateService::getAvailablePlaceholders(),
        ]);
    }

    private function getCompanyId(Request $request): int
    {
        $user = Auth::user();
        if ($user->role === 'super-admin' && $request->has('company_id')) {
            return (int) $request->company_id;
        }
        return $user->company_id;
    }
}
