<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\QuoteEmailTemplate;
use App\Models\QuoteItem;

class QuoteTemplateService
{
    /**
     * Render a template with quote data substituted for placeholders.
     */
    public function render(QuoteEmailTemplate $template, Quote $quote): array
    {
        $quote->loadMissing('items');
        $placeholders = $this->buildPlaceholderMap($quote);

        $subject = $this->expandEachServiceBlocks($template->subject, $quote);
        $subject = $this->replacePlaceholders($subject, $placeholders);

        $body = $this->expandEachServiceBlocks($template->body_html, $quote);
        $body = $this->replacePlaceholders($body, $placeholders);

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
            ['key' => '{{services_table}}', 'description' => 'Tabella servizi del preventivo'],
            ['key' => '{{services_count}}', 'description' => 'Numero totale servizi'],
            // --- Blocco ripetitivo per servizi ---
            ['key' => '{{#each_service}}', 'description' => 'Inizio blocco ripetuto per ogni servizio', 'is_block' => true],
            ['key' => '{{item_number}}', 'description' => 'Numero progressivo servizio (1, 2, 3...)', 'is_item' => true],
            ['key' => '{{item_destination_name}}', 'description' => 'Destinazione del servizio', 'is_item' => true],
            ['key' => '{{item_service_type}}', 'description' => 'Tipo servizio (TRF, TOUR HD, etc.)', 'is_item' => true],
            ['key' => '{{item_mileage}}', 'description' => 'Chilometraggio', 'is_item' => true],
            ['key' => '{{item_extra_km}}', 'description' => 'Km extra', 'is_item' => true],
            ['key' => '{{item_duration_hours}}', 'description' => 'Durata ore', 'is_item' => true],
            ['key' => '{{item_extension_hours}}', 'description' => 'Ore estensione', 'is_item' => true],
            ['key' => '{{item_extra_travel_hours}}', 'description' => 'Ore extra viaggio', 'is_item' => true],
            ['key' => '{{item_toll_cost}}', 'description' => 'Costo pedaggio', 'is_item' => true],
            ['key' => '{{item_pax_count}}', 'description' => 'Numero passeggeri', 'is_item' => true],
            ['key' => '{{item_experience_per_pax}}', 'description' => 'Esperienza per persona', 'is_item' => true],
            ['key' => '{{item_taxable_price}}', 'description' => 'Imponibile del servizio', 'is_item' => true],
            ['key' => '{{/each_service}}', 'description' => 'Fine blocco ripetuto', 'is_block' => true],
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
            '{{services_table}}' => $this->buildServicesTable($quote, $fmt),
            '{{services_count}}' => (string)$quote->items->count(),
        ];
    }

    private function buildServicesTable(Quote $quote, \Closure $fmt): string
    {
        $quote->loadMissing('items');
        $items = $quote->items;

        if ($items->isEmpty()) {
            return '';
        }

        $html = '<table style="width:100%;border-collapse:collapse;border:1px solid #ddd;font-family:Arial,sans-serif;font-size:14px;">';
        $html .= '<thead><tr style="background:#f5f5f5;">';
        $html .= '<th style="padding:8px 12px;border:1px solid #ddd;text-align:left;">Servizio</th>';
        $html .= '<th style="padding:8px 12px;border:1px solid #ddd;text-align:left;">Tipo</th>';
        $html .= '<th style="padding:8px 12px;border:1px solid #ddd;text-align:right;">Km</th>';
        $html .= '<th style="padding:8px 12px;border:1px solid #ddd;text-align:right;">Ore</th>';
        $html .= '<th style="padding:8px 12px;border:1px solid #ddd;text-align:right;">Pax</th>';
        $html .= '<th style="padding:8px 12px;border:1px solid #ddd;text-align:right;">Imponibile</th>';
        $html .= '</tr></thead><tbody>';

        $total = 0;
        foreach ($items as $item) {
            $total += floatval($item->taxable_price);
            $html .= '<tr>';
            $html .= '<td style="padding:8px 12px;border:1px solid #ddd;">' . e($item->destination_name ?: '-') . '</td>';
            $html .= '<td style="padding:8px 12px;border:1px solid #ddd;">' . e($item->service_type ?: '-') . '</td>';
            $html .= '<td style="padding:8px 12px;border:1px solid #ddd;text-align:right;">' . number_format(floatval($item->mileage), 0) . '</td>';
            $html .= '<td style="padding:8px 12px;border:1px solid #ddd;text-align:right;">' . number_format(floatval($item->duration_hours), 1) . '</td>';
            $html .= '<td style="padding:8px 12px;border:1px solid #ddd;text-align:right;">' . intval($item->pax_count) . '</td>';
            $html .= '<td style="padding:8px 12px;border:1px solid #ddd;text-align:right;">' . $fmt($item->taxable_price) . '</td>';
            $html .= '</tr>';
        }

        $html .= '<tr style="background:#f5f5f5;font-weight:bold;">';
        $html .= '<td colspan="5" style="padding:8px 12px;border:1px solid #ddd;text-align:right;">Totale</td>';
        $html .= '<td style="padding:8px 12px;border:1px solid #ddd;text-align:right;">' . $fmt($total) . '</td>';
        $html .= '</tr></tbody></table>';

        return $html;
    }

    /**
     * Expand {{#each_service}}...{{/each_service}} blocks by repeating the inner
     * content once per quote item, replacing item-level placeholders.
     */
    private function expandEachServiceBlocks(string $text, Quote $quote): string
    {
        $pattern = '/\{\{#each_service\}\}(.*?)\{\{\/each_service\}\}/s';

        return preg_replace_callback($pattern, function ($matches) use ($quote) {
            $blockTemplate = $matches[1];
            $items = $quote->items;

            if ($items->isEmpty()) {
                return '';
            }

            $fmt = fn($val) => number_format(floatval($val), 2, ',', '.');
            $output = '';

            foreach ($items as $index => $item) {
                $itemPlaceholders = $this->buildItemPlaceholderMap($item, $index + 1, $fmt);
                $output .= str_replace(
                    array_keys($itemPlaceholders),
                    array_values($itemPlaceholders),
                    $blockTemplate
                );
            }

            return $output;
        }, $text);
    }

    private function buildItemPlaceholderMap(QuoteItem $item, int $number, \Closure $fmt): array
    {
        return [
            '{{item_number}}' => (string)$number,
            '{{item_destination_name}}' => $item->destination_name ?? '',
            '{{item_service_type}}' => $item->service_type ?? '',
            '{{item_mileage}}' => number_format(floatval($item->mileage), 0),
            '{{item_extra_km}}' => number_format(floatval($item->extra_km), 0),
            '{{item_duration_hours}}' => number_format(floatval($item->duration_hours), 1),
            '{{item_extension_hours}}' => number_format(floatval($item->extension_hours), 1),
            '{{item_extra_travel_hours}}' => number_format(floatval($item->extra_travel_hours), 1),
            '{{item_toll_cost}}' => $fmt($item->toll_cost),
            '{{item_pax_count}}' => (string)intval($item->pax_count),
            '{{item_experience_per_pax}}' => $fmt($item->experience_per_pax),
            '{{item_taxable_price}}' => $fmt($item->taxable_price),
        ];
    }

    private function replacePlaceholders(string $text, array $placeholders): string
    {
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }
}
