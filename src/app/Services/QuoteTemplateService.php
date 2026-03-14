<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\QuoteEmailTemplate;

class QuoteTemplateService
{
    /**
     * Render a template with quote data substituted for placeholders.
     */
    public function render(QuoteEmailTemplate $template, Quote $quote): array
    {
        $placeholders = $this->buildPlaceholderMap($quote);

        $subject = $this->replacePlaceholders($template->subject, $placeholders);
        $body = $this->replacePlaceholders($template->body_html, $placeholders);

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Get all available placeholders with descriptions.
     */
    public static function getAvailablePlaceholders(): array
    {
        return [
            ['key' => '{{client_name}}', 'description' => 'Nome cliente'],
            ['key' => '{{client_email}}', 'description' => 'Email cliente'],
            ['key' => '{{service_date}}', 'description' => 'Data servizio (DD/MM/YYYY)'],
            ['key' => '{{destination_name}}', 'description' => 'Nome destinazione'],
            ['key' => '{{service_type}}', 'description' => 'Tipo servizio (TRF, TOUR HD, etc.)'],
            ['key' => '{{pax_count}}', 'description' => 'Numero passeggeri'],
            ['key' => '{{quote_id}}', 'description' => 'ID preventivo'],
            ['key' => '{{company_name}}', 'description' => 'Nome azienda'],
            ['key' => '{{payment_link}}', 'description' => 'Link di pagamento SumUp'],
            ['key' => '{{taxable_price_rounded}}', 'description' => 'Imponibile totale (arr. 5€)'],
            ['key' => '{{discounted_taxable}}', 'description' => 'Imponibile scontato'],
            ['key' => '{{discount_percentage}}', 'description' => 'Percentuale sconto'],
            ['key' => '{{discount_name}}', 'description' => 'Nome promozione'],
            ['key' => '{{vat_amount}}', 'description' => 'Importo IVA'],
            ['key' => '{{cc_fees_amount}}', 'description' => 'Importo Card Fees'],
            ['key' => '{{final_price_rounded}}', 'description' => 'Prezzo finale (arr. 5€)'],
            ['key' => '{{deposit_percentage}}', 'description' => 'Percentuale acconto'],
            ['key' => '{{deposit_taxable}}', 'description' => 'Acconto imponibile'],
            ['key' => '{{deposit_handling_fees}}', 'description' => 'Acconto handling fees'],
            ['key' => '{{deposit_total}}', 'description' => 'Acconto totale'],
            ['key' => '{{balance_taxable}}', 'description' => 'Saldo imponibile'],
            ['key' => '{{balance_handling_fees}}', 'description' => 'Saldo handling fees'],
            ['key' => '{{balance_card_fees}}', 'description' => 'Saldo card fees'],
        ];
    }

    /**
     * Build the placeholder → value map from a quote.
     */
    private function buildPlaceholderMap(Quote $quote): array
    {
        $company = $quote->company ?? \App\Models\Company::find($quote->company_id);
        $fmt = fn($val) => number_format(floatval($val), 2, ',', '.');

        return [
            '{{client_name}}' => $quote->client_name ?? '',
            '{{client_email}}' => $quote->client_email ?? '',
            '{{service_date}}' => $quote->service_date ? $quote->service_date->format('d/m/Y') : '',
            '{{destination_name}}' => $quote->destination_name ?? '',
            '{{service_type}}' => $quote->service_type ?? '',
            '{{pax_count}}' => (string)($quote->pax_count ?? 0),
            '{{quote_id}}' => (string)$quote->id,
            '{{company_name}}' => $company->name ?? '',
            '{{payment_link}}' => $quote->sumup_checkout_url ?? '',
            '{{taxable_price_rounded}}' => $fmt($quote->taxable_price_rounded),
            '{{discounted_taxable}}' => $fmt($quote->discounted_taxable),
            '{{discount_percentage}}' => (string)($quote->discount_percentage ?? 0),
            '{{discount_name}}' => $quote->discount_name ?? '',
            '{{vat_amount}}' => $fmt($quote->vat_amount),
            '{{cc_fees_amount}}' => $fmt($quote->cc_fees_amount),
            '{{final_price_rounded}}' => $fmt($quote->final_price_rounded),
            '{{deposit_percentage}}' => (string)($quote->deposit_percentage ?? 0),
            '{{deposit_taxable}}' => $fmt($quote->deposit_taxable),
            '{{deposit_handling_fees}}' => $fmt($quote->deposit_handling_fees),
            '{{deposit_total}}' => $fmt($quote->deposit_total),
            '{{balance_taxable}}' => $fmt($quote->balance_taxable),
            '{{balance_handling_fees}}' => $fmt($quote->balance_handling_fees),
            '{{balance_card_fees}}' => $fmt($quote->balance_card_fees),
        ];
    }

    private function replacePlaceholders(string $text, array $placeholders): string
    {
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }
}
