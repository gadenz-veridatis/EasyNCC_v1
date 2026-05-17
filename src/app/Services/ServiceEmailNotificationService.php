<?php

namespace App\Services;

use App\Models\GmailAccount;
use App\Models\QuoteEmailTemplate;
use App\Models\Service;
use App\Models\ServiceEmailToken;
use App\Models\Settings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ServiceEmailNotificationService
{
    private ServicePdfService $pdfService;
    private ServiceTemplateService $templateService;

    public function __construct(ServicePdfService $pdfService, ServiceTemplateService $templateService)
    {
        $this->pdfService = $pdfService;
        $this->templateService = $templateService;
    }

    /**
     * Check if a service should use the email flow (supplier != default supplier).
     */
    public function shouldUseEmailFlow(Service $service): bool
    {
        $settings = Settings::withoutGlobalScopes()
            ->where('company_id', $service->company_id)
            ->first();

        if (!$settings || !$settings->default_supplier_id) {
            return false;
        }

        return $service->supplier_id && $service->supplier_id !== $settings->default_supplier_id;
    }

    /**
     * Get the supplier's operational email for a service.
     * Returns null if not available.
     */
    public function getSupplierOperationalEmail(Service $service): ?string
    {
        $service->loadMissing('supplier.clientProfile');

        if (!$service->supplier || !$service->supplier->clientProfile) {
            return null;
        }

        return $service->supplier->clientProfile->operational_email ?: null;
    }

    /**
     * Prepare the email content for preview/editing before sending.
     * Returns rendered subject, body, and metadata.
     */
    public function prepareAssignmentEmail(Service $service): array
    {
        $settings = Settings::withoutGlobalScopes()
            ->where('company_id', $service->company_id)
            ->first();

        if (!$settings) {
            throw new \RuntimeException('Settings non configurate per questa azienda');
        }

        // Get template
        $template = null;
        if ($settings->email_assignment_template_id) {
            $template = QuoteEmailTemplate::withoutGlobalScopes()->find($settings->email_assignment_template_id);
        }
        if (!$template) {
            $template = QuoteEmailTemplate::withoutGlobalScopes()
                ->where('company_id', $service->company_id)
                ->where('type', 'service_assignment')
                ->where('is_default', true)
                ->first();
        }

        if (!$template) {
            throw new \RuntimeException('Nessun template email per assegnazione servizio configurato');
        }

        // Create accept token
        $token = $this->createToken($service, 'accept', $settings->email_token_expiry_days ?? 7);

        // Build accept link button HTML
        $acceptUrl = url("/service-action/{$token->token}");
        $acceptLinkHtml = '<a href="' . $acceptUrl . '" style="display:inline-block;padding:12px 30px;background-color:#28a745;color:#ffffff;text-decoration:none;border-radius:5px;font-weight:bold;font-size:16px;">ACCETTA SERVIZIO</a>';

        // Render template
        $rendered = $this->templateService->render($template, $service, [
            '{{accept_link}}' => $acceptLinkHtml,
        ]);

        // Get supplier email
        $supplierEmail = $this->getSupplierOperationalEmail($service);

        return [
            'subject' => $rendered['subject'],
            'body' => $rendered['body'],
            'to' => $supplierEmail,
            'template_id' => $template->id,
            'token' => $token->token,
            'gmail_account_id' => $settings->email_gmail_account_id,
        ];
    }

    /**
     * Send the assignment email (after user confirms/edits).
     */
    public function sendAssignmentEmail(Service $service, string $to, string $subject, string $body, int $gmailAccountId): array
    {
        $gmailAccount = GmailAccount::findOrFail($gmailAccountId);
        $gmailService = new GmailService($gmailAccount);

        // Generate PDF
        $pdfPath = $this->pdfService->generate($service);
        if (!$pdfPath) {
            throw new \RuntimeException('Errore durante la generazione del PDF');
        }

        try {
            $result = $gmailService->sendDirect($to, $subject, $body, [
                [
                    'path' => $pdfPath,
                    'name' => "servizio_{$service->reference_number}.pdf",
                    'mime' => 'application/pdf',
                ],
            ]);

            Log::info('Service assignment email sent', [
                'service_id' => $service->id,
                'to' => $to,
                'message_id' => $result['message_id'] ?? null,
            ]);

            return $result;
        } finally {
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
        }
    }

    /**
     * Handle service acceptance via token.
     */
    public function handleAcceptAction(ServiceEmailToken $token): void
    {
        $service = $token->service;
        $settings = Settings::withoutGlobalScopes()
            ->where('company_id', $token->company_id)
            ->first();

        if (!$settings) {
            throw new \RuntimeException('Settings non trovate');
        }

        // Update service status to "accepted"
        if ($settings->email_accepted_status_id) {
            $service->update(['status_id' => $settings->email_accepted_status_id]);
        }

        // Mark token as used
        $token->update(['used_at' => now()]);

        // Notify admin via email
        if ($settings->email_notification_address && $settings->email_gmail_account_id) {
            $this->sendAdminNotification(
                $service,
                $settings,
                'accettato',
                "Il servizio {$service->reference_number} è stato accettato dal collega."
            );
        }

        // Send closure email to supplier
        $this->sendClosureEmail($service, $settings);
    }

    /**
     * Handle service closure via token.
     */
    public function handleCloseAction(ServiceEmailToken $token): void
    {
        $service = $token->service;
        $settings = Settings::withoutGlobalScopes()
            ->where('company_id', $token->company_id)
            ->first();

        if (!$settings) {
            throw new \RuntimeException('Settings non trovate');
        }

        // Update service status to "closed"
        if ($settings->email_closed_status_id) {
            $service->update(['status_id' => $settings->email_closed_status_id]);
        }

        // Mark token as used
        $token->update(['used_at' => now()]);

        // Notify admin via email
        if ($settings->email_notification_address && $settings->email_gmail_account_id) {
            $this->sendAdminNotification(
                $service,
                $settings,
                'chiuso',
                "Il servizio {$service->reference_number} è stato chiuso dal collega."
            );
        }
    }

    /**
     * Send the closure email after acceptance.
     */
    private function sendClosureEmail(Service $service, Settings $settings): void
    {
        try {
            // Get closure template
            $template = null;
            if ($settings->email_closure_template_id) {
                $template = QuoteEmailTemplate::withoutGlobalScopes()->find($settings->email_closure_template_id);
            }
            if (!$template) {
                $template = QuoteEmailTemplate::withoutGlobalScopes()
                    ->where('company_id', $service->company_id)
                    ->where('type', 'service_closure')
                    ->where('is_default', true)
                    ->first();
            }

            if (!$template) {
                Log::warning('No closure template found', ['service_id' => $service->id]);
                return;
            }

            // Create closure token
            $token = $this->createToken($service, 'close', $settings->email_token_expiry_days ?? 7);

            $closureUrl = url("/service-action/{$token->token}");
            $closureLinkHtml = '<a href="' . $closureUrl . '" style="display:inline-block;padding:12px 30px;background-color:#007bff;color:#ffffff;text-decoration:none;border-radius:5px;font-weight:bold;font-size:16px;">CHIUDI SERVIZIO</a>';

            $rendered = $this->templateService->render($template, $service, [
                '{{closure_link}}' => $closureLinkHtml,
            ]);

            $supplierEmail = $this->getSupplierOperationalEmail($service);
            if (!$supplierEmail) {
                Log::warning('No supplier email for closure', ['service_id' => $service->id]);
                return;
            }

            if (!$settings->email_gmail_account_id) {
                Log::warning('No Gmail account configured for closure email', ['service_id' => $service->id]);
                return;
            }

            $gmailAccount = GmailAccount::find($settings->email_gmail_account_id);
            if (!$gmailAccount) {
                return;
            }

            $gmailService = new GmailService($gmailAccount);
            $gmailService->sendDirect($supplierEmail, $rendered['subject'], $rendered['body']);

            Log::info('Service closure email sent', [
                'service_id' => $service->id,
                'to' => $supplierEmail,
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending closure email', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification email to admin.
     */
    private function sendAdminNotification(Service $service, Settings $settings, string $action, string $message): void
    {
        try {
            $gmailAccount = GmailAccount::find($settings->email_gmail_account_id);
            if (!$gmailAccount) {
                return;
            }

            $gmailService = new GmailService($gmailAccount);

            $subject = "Servizio {$service->reference_number} - {$action}";
            $body = "<div style='font-family:Arial,sans-serif;'>"
                . "<p>{$message}</p>"
                . "<p><strong>Riferimento:</strong> {$service->reference_number}</p>"
                . "<p><strong>Data:</strong> " . ($service->pickup_datetime ? \Carbon\Carbon::parse($service->pickup_datetime)->format('d/m/Y H:i') : 'N/D') . "</p>"
                . "</div>";

            $gmailService->sendDirect($settings->email_notification_address, $subject, $body);
        } catch (\Exception $e) {
            Log::error('Error sending admin notification', [
                'service_id' => $service->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create a secure token for service actions.
     */
    private function createToken(Service $service, string $action, int $expiryDays): ServiceEmailToken
    {
        return ServiceEmailToken::create([
            'service_id' => $service->id,
            'company_id' => $service->company_id,
            'token' => Str::random(64),
            'action' => $action,
            'expires_at' => now()->addDays($expiryDays),
        ]);
    }
}
