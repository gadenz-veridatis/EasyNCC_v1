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
    public function createDraft(string $to, string $subject, string $htmlBody): array
    {
        $raw = $this->buildMimeMessage($to, $this->account->email_address, $subject, $htmlBody);

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
     */
    private function buildMimeMessage(string $to, string $from, string $subject, string $htmlBody): string
    {
        $boundary = uniqid('boundary_');
        $mime = "From: {$from}\r\n";
        $mime .= "To: {$to}\r\n";
        $mime .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $mime .= "MIME-Version: 1.0\r\n";
        $mime .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n";
        $mime .= "--{$boundary}\r\n";
        $mime .= "Content-Type: text/html; charset=UTF-8\r\n";
        $mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $mime .= chunk_split(base64_encode($htmlBody));
        $mime .= "--{$boundary}--";

        return rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');
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
