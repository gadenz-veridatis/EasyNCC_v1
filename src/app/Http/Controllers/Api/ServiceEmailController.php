<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\ServiceEmailNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceEmailController extends Controller
{
    private ServiceEmailNotificationService $emailService;

    public function __construct(ServiceEmailNotificationService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Check if a service should use the email flow and get supplier email info.
     */
    public function checkEmailFlow(Request $request, Service $service): JsonResponse
    {
        $shouldUseEmail = $this->emailService->shouldUseEmailFlow($service);
        $supplierEmail = $this->emailService->getSupplierOperationalEmail($service);

        return response()->json([
            'should_use_email' => $shouldUseEmail,
            'supplier_email' => $supplierEmail,
            'supplier_name' => $service->supplier
                ? ($service->supplier->clientProfile?->business_name ?: trim($service->supplier->name . ' ' . $service->supplier->surname))
                : null,
        ]);
    }

    /**
     * Prepare the assignment email for preview/editing.
     */
    public function prepareEmail(Request $request, Service $service): JsonResponse
    {
        try {
            $emailData = $this->emailService->prepareAssignmentEmail($service);

            return response()->json([
                'success' => true,
                'data' => $emailData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Send the assignment email (after user editing).
     */
    public function sendEmail(Request $request, Service $service): JsonResponse
    {
        $validated = $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
            'gmail_account_id' => 'required|exists:gmail_accounts,id',
        ]);

        try {
            $result = $this->emailService->sendAssignmentEmail(
                $service,
                $validated['to'],
                $validated['subject'],
                $validated['body'],
                $validated['gmail_account_id']
            );

            return response()->json([
                'success' => true,
                'message' => 'Email inviata con successo',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'invio: ' . $e->getMessage(),
            ], 500);
        }
    }
}
