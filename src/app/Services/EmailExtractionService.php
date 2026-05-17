<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailExtractionService
{
    private string $apiKey;
    private string $model = 'claude-sonnet-4-20250514';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key', env('ANTHROPIC_API_KEY', ''));
    }

    /**
     * Extract service request lines from an email body.
     *
     * @param string $emailBody The email text content
     * @param string|null $subject The email subject
     * @return array Array of extracted service lines with confidence levels
     */
    public function extract(string $emailBody, ?string $subject = null): array
    {
        if (empty($this->apiKey)) {
            Log::channel('gmail')->error('Anthropic API key not configured');
            return ['righe' => [], 'lingua' => null, 'error' => 'API key non configurata'];
        }

        $prompt = $this->buildPrompt($emailBody, $subject);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => 2048,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if ($response->failed()) {
                Log::channel('gmail')->error('Anthropic API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['righe' => [], 'lingua' => null, 'error' => 'Errore API: ' . $response->status()];
            }

            $content = $response->json('content.0.text', '');
            return $this->parseResponse($content);

        } catch (\Exception $e) {
            Log::channel('gmail')->error('Email extraction failed', [
                'error' => $e->getMessage(),
            ]);
            return ['righe' => [], 'lingua' => null, 'error' => $e->getMessage()];
        }
    }

    private function buildPrompt(string $emailBody, ?string $subject): string
    {
        $subjectLine = $subject ? "Subject: {$subject}\n" : '';
        $today = now()->format('Y-m-d');
        $dayOfWeek = now()->locale('it')->dayName;

        return <<<PROMPT
Analizza questa email di richiesta servizio NCC (Noleggio Con Conducente) ed estrai le informazioni sui servizi richiesti.

Oggi è {$today} ({$dayOfWeek}). Usa questa informazione per risolvere date relative come "domani", "prossimo lunedì", "fra 3 giorni", ecc.

{$subjectLine}Email body:
---
{$emailBody}
---

Per ogni servizio richiesto, estrai:
- data: data del servizio (formato YYYY-MM-DD)
- ora_pickup: orario di pickup (formato HH:MM, null se non specificato)
- pickup: luogo di partenza
- dropoff: luogo di arrivo
- passeggeri: numero di passeggeri (null se non specificato)
- tipo_servizio: uno tra "trasferimento", "tour", "esperienza", "altro"
- veicolo_preferito: tipo di veicolo richiesto (null se non specificato)
- note: eventuali note aggiuntive per questo servizio

Per ogni campo, indica il livello di confidenza:
- "alta": il dato è esplicitamente scritto nell'email
- "media": il dato è ragionevolmente deducibile dal contesto
- "bassa": il dato è incerto o ipotizzato

Indica anche la lingua dell'email (codice ISO: it, en, de, fr, es, etc.)

Rispondi SOLO con JSON valido in questo formato, senza altro testo:
{
  "lingua": "it",
  "righe": [
    {
      "data": "2026-04-20",
      "confidenza_data": "alta",
      "ora_pickup": "10:00",
      "confidenza_ora": "media",
      "pickup": "Aeroporto FCO",
      "confidenza_pickup": "alta",
      "dropoff": "Hotel Roma Centro",
      "confidenza_dropoff": "alta",
      "passeggeri": 3,
      "confidenza_passeggeri": "alta",
      "tipo_servizio": "trasferimento",
      "confidenza_tipo": "alta",
      "veicolo_preferito": null,
      "confidenza_veicolo": "bassa",
      "note": null
    }
  ]
}
PROMPT;
    }

    private function parseResponse(string $content): array
    {
        // Extract JSON from response (may be wrapped in markdown code blocks)
        $content = trim($content);
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
            $content = preg_replace('/\s*```\s*$/m', '', $content);
        }

        $data = json_decode(trim($content), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::channel('gmail')->warning('Failed to parse LLM extraction response', [
                'content' => $content,
                'error' => json_last_error_msg(),
            ]);
            return ['righe' => [], 'lingua' => null, 'error' => 'Risposta LLM non valida'];
        }

        // Build confidence map for each riga
        $righe = [];
        foreach ($data['righe'] ?? [] as $riga) {
            $confidenza = [];
            foreach (['data', 'ora', 'pickup', 'dropoff', 'passeggeri', 'tipo', 'veicolo'] as $field) {
                $key = "confidenza_{$field}";
                if (isset($riga[$key])) {
                    $confidenza[$field] = $riga[$key];
                }
            }

            $righe[] = [
                'data_servizio' => $riga['data'] ?? null,
                'ora_pickup' => $riga['ora_pickup'] ?? null,
                'pickup' => $riga['pickup'] ?? null,
                'dropoff' => $riga['dropoff'] ?? null,
                'passeggeri' => $riga['passeggeri'] ?? null,
                'tipo_servizio' => $riga['tipo_servizio'] ?? 'altro',
                'veicolo_preferito' => $riga['veicolo_preferito'] ?? null,
                'note' => $riga['note'] ?? null,
                'confidenza' => $confidenza,
            ];
        }

        return [
            'righe' => $righe,
            'lingua' => $data['lingua'] ?? null,
        ];
    }
}
