<?php

namespace App\Services;

use App\Models\GmailAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GmailService
{
    private GmailAccount $account;
    private string $baseUrl = 'https://gmail.googleapis.com/gmail/v1/users/me';

    public function __construct(GmailAccount $account)
    {
        $this->account = $account;
    }

    /**
     * Create a draft email.
     */
    public function createDraft(string $to, string $subject, string $htmlBody, array $attachments = []): array
    {
        $raw = $this->buildMimeMessage($to, $this->account->email_address, $subject, $htmlBody, $attachments);

        $response = $this->call('POST', '/drafts', [
            'message' => [
                'raw' => $raw,
            ],
        ]);

        return [
            'draft_id' => $response['id'] ?? null,
            'thread_id' => $response['message']['threadId'] ?? null,
        ];
    }

    /**
     * Send an email directly (without creating a draft first).
     */
    public function sendDirect(string $to, string $subject, string $htmlBody, array $attachments = []): array
    {
        $raw = $this->buildMimeMessage($to, $this->account->email_address, $subject, $htmlBody, $attachments);

        $response = $this->call('POST', '/messages/send', [
            'raw' => $raw,
        ]);

        return [
            'message_id' => $response['id'] ?? null,
            'thread_id' => $response['threadId'] ?? null,
        ];
    }

    /**
     * Send an existing draft.
     */
    public function sendDraft(string $draftId): array
    {
        $response = $this->call('POST', '/drafts/send', [
            'id' => $draftId,
        ]);

        return [
            'message_id' => $response['id'] ?? null,
            'thread_id' => $response['threadId'] ?? null,
        ];
    }

    /**
     * Delete a draft (for rollback).
     */
    public function deleteDraft(string $draftId): bool
    {
        try {
            $this->call('DELETE', "/drafts/{$draftId}");
            return true;
        } catch (\Exception $e) {
            Log::channel('gmail')->warning("Failed to delete draft {$draftId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test the connection by refreshing the token and calling Gmail profile.
     */
    public function testConnection(): array
    {
        // Force refresh to validate credentials
        $token = $this->refreshAccessToken();

        // Verify the token works by fetching user profile
        $response = Http::withToken($token)
            ->timeout(10)
            ->acceptJson()
            ->get($this->baseUrl . '/profile');

        if ($response->failed()) {
            throw new \RuntimeException('Token ottenuto ma accesso Gmail fallito: ' . ($response->json('error.message') ?? $response->body()));
        }

        return $response->json();
    }

    /**
     * Get a valid access token, refreshing if expired.
     */
    private function getAccessToken(): string
    {
        $accessToken = $this->account->getRawOriginal('access_token');
        $expiresAt = $this->account->token_expires_at;

        if ($accessToken && $expiresAt && $expiresAt->isFuture()) {
            return $accessToken;
        }

        return $this->refreshAccessToken();
    }

    /**
     * Refresh the OAuth access token using the refresh token.
     */
    private function refreshAccessToken(): string
    {
        $clientId = $this->account->getRawOriginal('client_id');
        $clientSecret = $this->account->getRawOriginal('client_secret');
        $refreshToken = $this->account->getRawOriginal('refresh_token');

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            $errorData = $response->json();
            $errorDesc = $errorData['error_description'] ?? $errorData['error'] ?? $response->body();
            Log::channel('gmail')->error('Gmail token refresh failed', [
                'status' => $response->status(),
                'response' => $errorData,
            ]);
            throw new \RuntimeException("Token refresh fallito ({$response->status()}): {$errorDesc}");
        }

        $data = $response->json();
        $newAccessToken = $data['access_token'];
        $expiresIn = $data['expires_in'] ?? 3600;

        $this->account->update([
            'access_token' => $newAccessToken,
            'token_expires_at' => now()->addSeconds($expiresIn - 60),
        ]);

        return $newAccessToken;
    }

    /**
     * Build a base64url-encoded MIME message.
     *
     * @param array $attachments Array of ['path' => '/path/to/file', 'name' => 'filename.pdf', 'mime' => 'application/pdf']
     */
    private function buildMimeMessage(string $to, string $from, string $subject, string $htmlBody, array $attachments = []): string
    {
        $boundary = uniqid('boundary_');
        $mime = "From: {$from}\r\n";
        $mime .= "To: {$to}\r\n";
        $mime .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $mime .= "MIME-Version: 1.0\r\n";

        if (empty($attachments)) {
            // Simple HTML-only message
            $mime .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n";
            $mime .= "--{$boundary}\r\n";
            $mime .= "Content-Type: text/html; charset=UTF-8\r\n";
            $mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $mime .= chunk_split(base64_encode($htmlBody));
            $mime .= "--{$boundary}--";
        } else {
            // Mixed message with attachments
            $mime .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n\r\n";

            // HTML body part
            $mime .= "--{$boundary}\r\n";
            $mime .= "Content-Type: text/html; charset=UTF-8\r\n";
            $mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $mime .= chunk_split(base64_encode($htmlBody));

            // Attachment parts
            foreach ($attachments as $attachment) {
                $filePath = $attachment['path'];
                $fileName = $attachment['name'] ?? basename($filePath);
                $mimeType = $attachment['mime'] ?? 'application/octet-stream';

                if (!file_exists($filePath)) {
                    continue;
                }

                $fileContent = file_get_contents($filePath);
                $mime .= "--{$boundary}\r\n";
                $mime .= "Content-Type: {$mimeType}; name=\"{$fileName}\"\r\n";
                $mime .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n";
                $mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $mime .= chunk_split(base64_encode($fileContent));
            }

            $mime .= "--{$boundary}--";
        }

        return rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');
    }

    /**
     * List labels for the account. Used to resolve label name → label ID.
     */
    public function listLabels(): array
    {
        $response = $this->call('GET', '/labels');
        return $response['labels'] ?? [];
    }

    /**
     * Resolve a label name to its Gmail internal ID.
     * Returns null if not found.
     */
    public function resolveLabelId(string $labelName): ?string
    {
        $labels = $this->listLabels();
        foreach ($labels as $label) {
            if (strcasecmp($label['name'] ?? '', $labelName) === 0) {
                return $label['id'];
            }
        }
        return null;
    }

    /**
     * Fetch new messages since the given historyId.
     *
     * Detects two types of events:
     * 1. labelAdded: new emails tagged with the configured label (new richieste)
     * 2. messageAdded: new messages in threads we already track (replies to existing richieste)
     *
     * @param string $startHistoryId Gmail history cursor
     * @param string $labelId Label ID to watch for new richieste
     * @param array $knownThreadIds Gmail thread IDs we already track in thread_emails
     * @return array message_ids to process + new history_id
     */
    public function fetchNewMessages(string $startHistoryId, string $labelId, array $knownThreadIds = []): array
    {
        $messageIds = [];
        $pageToken = null;

        do {
            $params = [
                'startHistoryId' => $startHistoryId,
            ];
            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $response = $this->call('GET', '/history', $params);

            $histories = $response['history'] ?? [];
            foreach ($histories as $history) {
                // 1. New label applied → new richiesta
                $labelsAdded = $history['labelsAdded'] ?? [];
                foreach ($labelsAdded as $event) {
                    $msgLabels = $event['labelIds'] ?? [];
                    if (in_array($labelId, $msgLabels)) {
                        $msgId = $event['message']['id'] ?? null;
                        if ($msgId && !in_array($msgId, $messageIds)) {
                            $messageIds[] = $msgId;
                        }
                    }
                }

                // 2. New message in a known thread → reply to existing richiesta
                $messagesAdded = $history['messagesAdded'] ?? [];
                foreach ($messagesAdded as $event) {
                    $msgId = $event['message']['id'] ?? null;
                    $threadId = $event['message']['threadId'] ?? null;
                    if ($msgId && $threadId && in_array($threadId, $knownThreadIds)) {
                        if (!in_array($msgId, $messageIds)) {
                            $messageIds[] = $msgId;
                        }
                    }
                }
            }

            $pageToken = $response['nextPageToken'] ?? null;
        } while ($pageToken);

        $latestHistoryId = $response['historyId'] ?? $startHistoryId;

        return [
            'message_ids' => $messageIds,
            'history_id' => $latestHistoryId,
        ];
    }

    /**
     * Get full message details including RFC headers and body.
     */
    public function getMessageDetail(string $messageId): array
    {
        $response = $this->call('GET', "/messages/{$messageId}", [
            'format' => 'full',
        ]);

        $headers = [];
        foreach ($response['payload']['headers'] ?? [] as $header) {
            $name = strtolower($header['name']);
            $headers[$name] = $header['value'];
        }

        $body = $this->extractBody($response['payload'] ?? []);

        return [
            'id' => $response['id'] ?? null,
            'thread_id' => $response['threadId'] ?? null,
            'label_ids' => $response['labelIds'] ?? [],
            'internal_date' => isset($response['internalDate'])
                ? \Carbon\Carbon::createFromTimestampMs($response['internalDate'])
                : null,
            'headers' => $headers,
            'message_id_rfc' => $headers['message-id'] ?? null,
            'in_reply_to' => $headers['in-reply-to'] ?? null,
            'references' => $headers['references'] ?? null,
            'from' => $headers['from'] ?? null,
            'to' => $headers['to'] ?? null,
            'subject' => $headers['subject'] ?? null,
            'body_text' => $body['text'] ?? null,
            'body_html' => $body['html'] ?? null,
        ];
    }

    /**
     * Get the current profile (includes historyId).
     */
    public function getProfile(): array
    {
        return $this->call('GET', '/profile');
    }

    /**
     * Extract text and html body from message payload.
     */
    private function extractBody(array $payload): array
    {
        $result = ['text' => null, 'html' => null];

        $mimeType = $payload['mimeType'] ?? '';
        $bodyData = $payload['body']['data'] ?? null;

        if ($bodyData && $mimeType === 'text/plain') {
            $result['text'] = base64_decode(strtr($bodyData, '-_', '+/'));
        } elseif ($bodyData && $mimeType === 'text/html') {
            $result['html'] = base64_decode(strtr($bodyData, '-_', '+/'));
        }

        // Recurse into parts
        foreach ($payload['parts'] ?? [] as $part) {
            $partResult = $this->extractBody($part);
            if ($partResult['text'] && !$result['text']) {
                $result['text'] = $partResult['text'];
            }
            if ($partResult['html'] && !$result['html']) {
                $result['html'] = $partResult['html'];
            }
        }

        return $result;
    }

    /**
     * Make an authenticated API call to Gmail.
     */
    private function call(string $method, string $endpoint, array $data = []): array
    {
        try {
            $token = $this->getAccessToken();

            $request = Http::withToken($token)
                ->timeout(15)
                ->acceptJson();

            $url = $this->baseUrl . $endpoint;

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url, $data),
                'POST' => $request->asJson()->post($url, $data),
                'PUT' => $request->asJson()->put($url, $data),
                'DELETE' => $request->delete($url),
            };

            if ($response->failed()) {
                Log::channel('gmail')->error("Gmail API error: {$response->status()}", [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'response' => $response->json(),
                ]);
                throw new \RuntimeException("Gmail API error: {$response->status()} - " . ($response->json('error.message') ?? $response->body()));
            }

            return $response->json() ?? [];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::channel('gmail')->error("Gmail connection error: " . $e->getMessage());
            throw new \RuntimeException("Gmail connection error: " . $e->getMessage());
        }
    }
}
