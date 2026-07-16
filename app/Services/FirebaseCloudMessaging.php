<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseCloudMessaging
{
    public function send(string $deviceToken, array $data): Response
    {
        $projectId = config('services.firebase.project_id');
        $credentialsPath = config('services.firebase.credentials');

        if (! $projectId || ! $credentialsPath) {
            throw new RuntimeException('Firebase FCM is not configured.');
        }

        $credentialsPath = $this->absolutePath($credentialsPath);
        $credentials = json_decode((string) @file_get_contents($credentialsPath), true);

        if (! is_array($credentials)
            || empty($credentials['client_email'])
            || empty($credentials['private_key'])
            || empty($credentials['token_uri'])) {
            throw new RuntimeException('Firebase service account credentials are unreadable or invalid.');
        }

        $accessToken = $this->accessToken($credentials);
        $stringData = array_map(
            fn ($value) => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value,
            $data
        );

        return Http::withToken($accessToken)
            ->acceptJson()
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $stringData['title'],
                        'body' => $stringData['body'],
                    ],
                    'data' => $stringData,
                    'android' => [
                        'priority' => $data['priority'] === 'normal' ? 'NORMAL' : 'HIGH',
                        'notification' => ['sound' => 'default'],
                    ],
                ],
            ]);
    }

    public function tokenIsInvalid(Response $response): bool
    {
        $body = $response->json();
        $errorCode = data_get($body, 'error.details.0.errorCode');
        $message = strtolower((string) data_get($body, 'error.message'));

        return $errorCode === 'UNREGISTERED'
            || str_contains($message, 'registration token is not a valid fcm registration token');
    }

    private function accessToken(array $credentials): string
    {
        $now = time();
        $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $this->base64Url(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $credentials['token_uri'],
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        if (! openssl_sign("{$header}.{$claims}", $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Unable to sign Firebase OAuth assertion.');
        }

        $assertion = "{$header}.{$claims}.{$this->base64Url($signature)}";
        $response = Http::asForm()->post($credentials['token_uri'], [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion,
        ]);

        if (! $response->successful() || ! $response->json('access_token')) {
            throw new RuntimeException('Unable to obtain Firebase OAuth access token.');
        }

        return $response->json('access_token');
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function absolutePath(string $path): string
    {
        if (preg_match('/^(?:[A-Za-z]:[\\\\\/]|\/)/', $path)) {
            return $path;
        }

        return base_path($path);
    }
}
