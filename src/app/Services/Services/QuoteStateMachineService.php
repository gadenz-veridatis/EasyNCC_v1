<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\QuoteEmailTemplate;
use App\Models\QuoteStateTransition;
use App\Models\Settings;
use App\Models\SumupConfig;
use App\Models\GmailAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuoteStateMachineService
{
    private const ALLOWED_TRANSITIONS = [
        Quote::STATUS_DRAFT => [Quote::STATUS_APPROVED],
        Quote::STATUS_APPROVED => [Quote::STATUS_SENT, Quote::STATUS_DRAFT],
        Quote::STATUS_SENT => [Quote::STATUS_DEPOSIT_RECEIVED],
    ];

    /**
     * Check if a transition is valid.
     */
    public function canTransitionTo(Quote $quote, string $newState): bool
    {
        $allowed = self::ALLOWED_TRANSITIONS[$quote->status] ?? [];
        return in_array($newState, $allowed);
    }

    /**
     * Transition draft → approved.
     * Creates SumUp checkout and Gmail draft.
     */
    public function transitionToApproved(Quote $quote, User $actor, array $options = []): Quote
    {
        $this->validateTransition($quote, Quote::STATUS_APPROVED);
        $this->validateQuoteCompleteness($quote, $options);

        return DB::transaction(function () use ($quote, $actor, $options) {
            // Resolve integration configs
            $sumupConfig = $this->resolveSumupConfig($quote, $options);
            $gmailAccount = $this->resolveGmailAccount($quote, $options);
            $emailTemplate = $this->resolveEmailTemplate($quote, $options);

            // Update client_email if provided
            if (!empty($options['client_email'])) {
                $quote->client_email = $options['client_email'];
            }

            // 1. Create SumUp checkout (unique reference to avoid 409 on retry)
            $sumupService = new SumUpService($sumupConfig);
            $uniqueRef = "QUOTE-{$quote->id}-" . time();
            $checkout = $sumupService->createCheckout(
                reference: $uniqueRef,
                amount: floatval($quote->deposit_total),
                currency: 'EUR',
                description: "Preventivo #{$quote->id} - {$quote->client_name} - {$quote->destination_name}",
            );

            $quote->sumup_config_id = $sumupConfig->id;
            $quote->sumup_checkout_id = $checkout['checkout_id'];
            $quote->sumup_checkout_url = $checkout['checkout_url'];

            // 2. Create Gmail draft
            $templateService = new QuoteTemplateService();
            $quote->save(); // Save first so template can access sumup_checkout_url

            $rendered = $templateService->render($emailTemplate, $quote);

            $gmailService = new GmailService($gmailAccount);
            $draft = $gmailService->createDraft(
                to: $quote->client_email,
                subject: $rendered['subject'],
                htmlBody: $rendered['body'],
            );

            $quote->gmail_account_id = $gmailAccount->id;
            $quote->email_template_id = $emailTemplate->id;
            $quote->gmail_draft_id = $draft['draft_id'];
            $quote->gmail_thread_id = $draft['thread_id'];
            $quote->rendered_subject = $rendered['subject'];
            $quote->rendered_body_html = $rendered['body'];

            // 3. Update status
            $quote->status = Quote::STATUS_APPROVED;
            $quote->approved_at = now();
            $quote->save();

            $this->logTransition($quote, Quote::STATUS_DRAFT, Quote::STATUS_APPROVED, $actor, 'user', [
                'sumup_checkout_id' => $checkout['checkout_id'],
                'gmail_draft_id' => $draft['draft_id'],
            ]);

            return $quote->fresh();
        });
    }

    /**
     * Transition approved → sent.
     * Sends the Gmail draft.
     */
    public function transitionToSent(Quote $quote, User $actor, array $options = []): Quote
    {
        $this->validateTransition($quote, Quote::STATUS_SENT);

        return DB::transaction(function () use ($quote, $actor, $options) {
            $gmailAccount = $quote->gmailAccount;
            if (!$gmailAccount || !$quote->gmail_draft_id) {
                throw new \RuntimeException('Gmail draft not found for this quote');
            }

            $gmailService = new GmailService($gmailAccount);

            // If operator modified the email, update the draft before sending
            $subject = $options['rendered_subject'] ?? $quote->rendered_subject;
            $body = $options['rendered_body_html'] ?? $quote->rendered_body_html;

            if (!empty($options['rendered_subject']) || !empty($options['rendered_body_html'])) {
                // Delete old draft and create new one with modified content
                try {
                    $gmailService->deleteDraft($quote->gmail_draft_id);
                } catch (\Exception $e) {
                    Log::warning("Failed to delete old Gmail draft: " . $e->getMessage());
                }

                $draft = $gmailService->createDraft(
                    to: $quote->client_email,
                    subject: $subject,
                    htmlBody: $body,
                );

                $quote->gmail_draft_id = $draft['draft_id'];
                $quote->rendered_subject = $subject;
                $quote->rendered_body_html = $body;
            }

            $gmailService->sendDraft($quote->gmail_draft_id);

            $quote->status = Quote::STATUS_SENT;
            $quote->sent_at = now();
            $quote->save();

            $this->logTransition($quote, Quote::STATUS_APPROVED, Quote::STATUS_SENT, $actor, 'user');

            return $quote->fresh();
        });
    }

    /**
     * Revert approved → draft.
     * Cleans up SumUp checkout and Gmail draft.
     */
    public function revertToDraft(Quote $quote, User $actor): Quote
    {
        $this->validateTransition($quote, Quote::STATUS_DRAFT);

        return DB::transaction(function () use ($quote, $actor) {
            // Cleanup SumUp checkout
            if ($quote->sumup_checkout_id && $quote->sumupConfig) {
                try {
                    $sumupService = new SumUpService($quote->sumupConfig);
                    $sumupService->deactivateCheckout($quote->sumup_checkout_id);
                } catch (\Exception $e) {
                    Log::warning("Failed to deactivate SumUp checkout: " . $e->getMessage());
                }
            }

            // Cleanup Gmail draft
            if ($quote->gmail_draft_id && $quote->gmailAccount) {
                try {
                    $gmailService = new GmailService($quote->gmailAccount);
                    $gmailService->deleteDraft($quote->gmail_draft_id);
                } catch (\Exception $e) {
                    Log::warning("Failed to delete Gmail draft: " . $e->getMessage());
                }
            }

            // Clear integration references
            $quote->sumup_checkout_id = null;
            $quote->sumup_checkout_url = null;
            $quote->gmail_draft_id = null;
            $quote->gmail_thread_id = null;
            $quote->sumup_config_id = null;
            $quote->gmail_account_id = null;
            $quote->email_template_id = null;
            $quote->rendered_subject = null;
            $quote->rendered_body_html = null;
            $quote->approved_at = null;

            $quote->status = Quote::STATUS_DRAFT;
            $quote->save();

            $this->logTransition($quote, Quote::STATUS_APPROVED, Quote::STATUS_DRAFT, $actor, 'user', [
                'reason' => 'Rollback to draft by user',
            ]);

            return $quote->fresh();
        });
    }

    /**
     * Transition sent → deposit_received.
     * Called by webhook, creates a service.
     */
    public function transitionToDepositReceived(Quote $quote, array $webhookPayload = []): Quote
    {
        $this->validateTransition($quote, Quote::STATUS_DEPOSIT_RECEIVED);

        return DB::transaction(function () use ($quote, $webhookPayload) {
            // Create partial service
            $serviceCreator = new QuoteServiceCreationService();
            $service = $serviceCreator->createFromQuote($quote);

            $quote->service_id = $service->id;
            $quote->status = Quote::STATUS_DEPOSIT_RECEIVED;
            $quote->deposit_received_at = now();
            $quote->save();

            $this->logTransition(
                $quote,
                Quote::STATUS_SENT,
                Quote::STATUS_DEPOSIT_RECEIVED,
                null,
                'webhook',
                $webhookPayload
            );

            return $quote->fresh();
        });
    }

    /**
     * Validate that the transition is allowed.
     */
    private function validateTransition(Quote $quote, string $newState): void
    {
        if (!$this->canTransitionTo($quote, $newState)) {
            throw new \RuntimeException(
                "Invalid transition from '{$quote->status}' to '{$newState}'"
            );
        }
    }

    /**
     * Validate that the quote has all required fields for approval.
     */
    private function validateQuoteCompleteness(Quote $quote, array $options): void
    {
        $clientEmail = $options['client_email'] ?? $quote->client_email;

        $errors = [];
        if (empty($quote->client_name)) $errors[] = 'Nome cliente obbligatorio';
        if (empty($clientEmail)) $errors[] = 'Email cliente obbligatoria';
        if (empty($quote->service_date)) $errors[] = 'Data servizio obbligatoria';
        if (floatval($quote->deposit_total) <= 0) $errors[] = 'Acconto totale deve essere maggiore di zero';

        if (!empty($errors)) {
            throw new \RuntimeException('Preventivo incompleto: ' . implode(', ', $errors));
        }
    }

    /**
     * Resolve which SumUp config to use.
     */
    private function resolveSumupConfig(Quote $quote, array $options): SumupConfig
    {
        if (!empty($options['sumup_config_id'])) {
            $config = SumupConfig::withoutGlobalScopes()
                ->where('id', $options['sumup_config_id'])
                ->where('company_id', $quote->company_id)
                ->where('is_active', true)
                ->first();
            if ($config) return $config;
        }

        // Fall back to company default
        $settings = Settings::withoutGlobalScopes()->where('company_id', $quote->company_id)->first();
        if ($settings && $settings->default_sumup_config_id) {
            $config = SumupConfig::withoutGlobalScopes()
                ->where('id', $settings->default_sumup_config_id)
                ->where('is_active', true)
                ->first();
            if ($config) return $config;
        }

        // Fall back to first active config for company
        $config = SumupConfig::withoutGlobalScopes()
            ->where('company_id', $quote->company_id)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            throw new \RuntimeException('Nessun merchant SumUp configurato per questa azienda');
        }

        return $config;
    }

    /**
     * Resolve which Gmail account to use.
     */
    private function resolveGmailAccount(Quote $quote, array $options): GmailAccount
    {
        if (!empty($options['gmail_account_id'])) {
            $account = GmailAccount::withoutGlobalScopes()
                ->where('id', $options['gmail_account_id'])
                ->where('company_id', $quote->company_id)
                ->where('is_active', true)
                ->first();
            if ($account) return $account;
        }

        $settings = Settings::withoutGlobalScopes()->where('company_id', $quote->company_id)->first();
        if ($settings && $settings->default_gmail_account_id) {
            $account = GmailAccount::withoutGlobalScopes()
                ->where('id', $settings->default_gmail_account_id)
                ->where('is_active', true)
                ->first();
            if ($account) return $account;
        }

        $account = GmailAccount::withoutGlobalScopes()
            ->where('company_id', $quote->company_id)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            throw new \RuntimeException('Nessun account Gmail configurato per questa azienda');
        }

        return $account;
    }

    /**
     * Resolve which email template to use.
     */
    private function resolveEmailTemplate(Quote $quote, array $options): QuoteEmailTemplate
    {
        if (!empty($options['email_template_id'])) {
            $template = QuoteEmailTemplate::withoutGlobalScopes()
                ->where('id', $options['email_template_id'])
                ->where('company_id', $quote->company_id)
                ->first();
            if ($template) return $template;
        }

        $settings = Settings::withoutGlobalScopes()->where('company_id', $quote->company_id)->first();
        if ($settings && $settings->default_email_template_id) {
            $template = QuoteEmailTemplate::withoutGlobalScopes()
                ->where('id', $settings->default_email_template_id)
                ->first();
            if ($template) return $template;
        }

        $template = QuoteEmailTemplate::withoutGlobalScopes()
            ->where('company_id', $quote->company_id)
            ->where('is_default', true)
            ->first();

        if (!$template) {
            $template = QuoteEmailTemplate::withoutGlobalScopes()
                ->where('company_id', $quote->company_id)
                ->first();
        }

        if (!$template) {
            throw new \RuntimeException('Nessun template email configurato per questa azienda');
        }

        return $template;
    }

    /**
     * Log a state transition.
     */
    private function logTransition(
        Quote $quote,
        ?string $from,
        string $to,
        ?User $actor,
        string $source,
        ?array $metadata = null
    ): void {
        QuoteStateTransition::create([
            'quote_id' => $quote->id,
            'company_id' => $quote->company_id,
            'from_state' => $from,
            'to_state' => $to,
            'transitioned_by' => $actor?->id,
            'transition_source' => $source,
            'metadata' => $metadata,
        ]);
    }
}
