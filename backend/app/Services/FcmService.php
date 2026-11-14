<?php

namespace App\Services;

use App\Models\FcmToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pengirim push notification Firebase Cloud Messaging (HTTP v1 API).
 * Autentikasi memakai service account JSON (JWT -> OAuth2 access token),
 * tanpa paket composer tambahan.
 */
class FcmService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function isConfigured(): bool
    {
        return $this->credentials() !== null;
    }

    /**
     * Kirim notifikasi ke semua perangkat milik user.
     *
     * @param  array<string,string>  $data
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        $creds = $this->credentials();
        if (! $creds) {
            Log::info('FCM dilewati: credentials belum dikonfigurasi (FCM_CREDENTIALS_PATH).');

            return;
        }

        $tokens = FcmToken::where('user_id', $userId)->pluck('token');
        if ($tokens->isEmpty()) {
            return;
        }

        $accessToken = $this->accessToken($creds);
        if (! $accessToken) {
            return;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$creds['project_id']}/messages:send";
        $data = array_map('strval', $data);

        foreach ($tokens as $token) {
            $response = Http::withoutVerifying()->withToken($accessToken)->timeout(10)->post($url, [
                'message' => [
                    'token' => $token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => (object) $data,
                    'android' => [
                        'priority' => 'HIGH',
                        'notification' => [
                            'channel_id' => 'setoran_poin_channel',
                            'visibility' => 'PUBLIC', // tampil penuh di lock screen
                            'sound' => 'default',
                        ],
                    ],
                    'apns' => [
                        'headers' => ['apns-priority' => '10'],
                        'payload' => ['aps' => ['sound' => 'default']],
                    ],
                ],
            ]);

            if ($response->successful()) {
                Log::info('FCM berhasil terkirim ke user', [
                    'user_id' => $userId,
                    'title' => $title,
                    'token' => substr($token, 0, 25) . '...',
                    'response' => $response->json(),
                ]);
                continue;
            }

            // Token kadaluarsa / aplikasi di-uninstall -> hapus
            $status = $response->json('error.status');
            if (in_array($status, ['NOT_FOUND', 'UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
                FcmToken::where('token', $token)->delete();
            }
            Log::warning('FCM gagal terkirim', ['status' => $status, 'body' => $response->body()]);
        }
    }

    /** @return array<string,mixed>|null */
    private function credentials(): ?array
    {
        $path = config('services.fcm.credentials');
        if (! $path) {
            return null;
        }
        if (! is_file($path)) {
            $path = base_path($path);
        }
        if (! is_file($path)) {
            return null;
        }
        $json = json_decode((string) file_get_contents($path), true);

        return is_array($json) && isset($json['private_key'], $json['client_email'], $json['project_id'])
            ? $json
            : null;
    }

    /** @param array<string,mixed> $creds */
    private function accessToken(array $creds): ?string
    {
        $cached = Cache::get('fcm_access_token');
        if ($cached && is_string($cached)) {
            return $cached;
        }

        $now = time();
        $b64 = fn (string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $header = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $b64(json_encode([
            'iss' => $creds['client_email'],
            'scope' => self::SCOPE,
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signature = '';
        if (! openssl_sign("$header.$claims", $signature, $creds['private_key'], OPENSSL_ALGO_SHA256)) {
            Log::error('FCM: gagal menandatangani JWT');

            return null;
        }

        $res = Http::withoutVerifying()->asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => "$header.$claims.".$b64($signature),
        ]);

        if (! $res->successful()) {
            Log::error('FCM: gagal mengambil access token', ['body' => $res->body()]);

            return null;
        }

        $token = $res->json('access_token');
        if ($token) {
            Cache::put('fcm_access_token', $token, 3300);
        }

        return $token;
    }
}
