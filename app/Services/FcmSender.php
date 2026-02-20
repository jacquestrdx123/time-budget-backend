<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Send FCM messages via Firebase HTTP v1 API using service account credentials.
 * No external FCM package required.
 */
class FcmSender
{
    private ?string $accessToken = null;

    private ?array $credentials = null;

    private ?string $projectId = null;

    public function __construct()
    {
        $path = config('timebudget.firebase_credentials_path');
        $path = str_starts_with($path, '/') ? $path : base_path($path);
        if (is_readable($path)) {
            $this->credentials = json_decode((string) file_get_contents($path), true);
            $this->projectId = $this->credentials['project_id'] ?? null;
        }
    }

    public function isConfigured(): bool
    {
        return config('timebudget.firebase_enabled')
            && $this->credentials !== null
            && $this->projectId !== null;
    }

    /**
     * Send a push to one FCM token. Returns true on success.
     */
    public function send(string $token, string $title, string $body, array $data = []): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('FcmSender: Not configured, skipping send.');

            return false;
        }

        $accessToken = $this->getAccessToken();
        if ($accessToken === null) {
            return false;
        }

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
            ],
        ];
        if ($data !== []) {
            $payload['message']['data'] = array_map(strval(...), $data);
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
        $response = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $payload);

        if ($response->successful()) {
            return true;
        }

        Log::warning('FcmSender: FCM API error.', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    private function getAccessToken(): ?string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $jwt = $this->createJwt();
        if ($jwt === null) {
            return null;
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            Log::error('FcmSender: Failed to get OAuth2 access token.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $this->accessToken = $response->json('access_token');

        return $this->accessToken;
    }

    private function createJwt(): ?string
    {
        if ($this->credentials === null) {
            return null;
        }

        $now = time();
        $payload = [
            'iss' => $this->credentials['client_email'],
            'sub' => $this->credentials['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        ];

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $segments = [
            $this->base64UrlEncode(json_encode($header)),
            $this->base64UrlEncode(json_encode($payload)),
        ];
        $signatureInput = implode('.', $segments);

        $key = $this->credentials['private_key'] ?? null;
        if ($key === null) {
            Log::error('FcmSender: No private_key in credentials.');

            return null;
        }

        $signed = '';
        $ok = openssl_sign(
            $signatureInput,
            $signed,
            $key,
            OPENSSL_ALGO_SHA256
        );
        if (! $ok) {
            Log::error('FcmSender: openssl_sign failed.');

            return null;
        }

        $segments[] = $this->base64UrlEncode($signed);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
