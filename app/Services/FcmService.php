<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FcmService
{
    /**
     * Send push notification to a user's device and log the result.
     */
    public static function sendToUser(int $userId, string $title, string $body, string $type = 'general', ?int $scanId = null, array $data = []): bool
    {
        $user = User::find($userId);

        if (!$user) {
            self::log($userId, $scanId, $type, $title, $body, null, 'failed', 'User not found', $data);
            return false;
        }

        if (empty($user->fcm_token)) {
            self::log($userId, $scanId, $type, $title, $body, null, 'no_token', 'No FCM token registered', $data);
            return false;
        }

        return self::send($user->fcm_token, $title, $body, $type, $userId, $scanId, $data);
    }

    /**
     * Send push notification via Firebase HTTP v1 API.
     */
    public static function send(string $fcmToken, string $title, string $body, string $type = 'general', ?int $userId = null, ?int $scanId = null, array $data = []): bool
    {
        $projectId = config('services.firebase.project_id');
        $credentialsPath = config('services.firebase.credentials');

        if (!$projectId || !$credentialsPath) {
            self::log($userId, $scanId, $type, $title, $body, $fcmToken, 'failed', 'Firebase project_id or credentials not configured', $data);
            return false;
        }

        try {
            $accessToken = self::getAccessToken($credentialsPath);

            $payload = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    'data' => array_merge(
                        array_map('strval', $data),
                        ['type' => $type, 'scan_id' => (string) ($scanId ?? ''), 'click_action' => 'FLUTTER_NOTIFICATION_CLICK']
                    ),
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ],
                    ],
                ],
            ];

            $response = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

            $success = $response->successful();
            $responseCode = $response->status();
            $errorMsg = !$success ? ($response->body() ?? 'Unknown error') : null;

            self::log($userId, $scanId, $type, $title, $body, $fcmToken, $success ? 'sent' : 'failed', $errorMsg, $data, $responseCode);

            if (!$success) Log::error('FCM v1 send failed: ' . $response->body());

            return $success;
        } catch (\Exception $e) {
            self::log($userId, $scanId, $type, $title, $body, $fcmToken, 'failed', $e->getMessage(), $data);
            Log::error('FCM v1 exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get OAuth2 access token for Firebase HTTP v1 API.
     */
    private static function getAccessToken(string $credentialsPath): string
    {
        return Cache::remember('fcm_access_token', 3000, function () use ($credentialsPath) {
            $fullPath = base_path($credentialsPath);
            if (!file_exists($fullPath)) {
                throw new \Exception("Firebase credentials file not found: {$fullPath}");
            }

            $credentials = json_decode(file_get_contents($fullPath), true);
            $now = time();

            // Create JWT
            $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = base64_encode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $toSign = $header . '.' . $claims;
            openssl_sign($toSign, $signature, $credentials['private_key'], 'sha256');
            $jwt = $toSign . '.' . base64_encode($signature);

            // Exchange JWT for access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to get FCM access token: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    /**
     * Notify bill approver when a new bill is assigned.
     */
    public static function notifyBillAssigned(int $approverId, int $scanId, string $documentName): bool
    {
        return self::sendToUser(
            $approverId,
            'New Bill for Approval',
            "Bill '{$documentName}' has been assigned to you.",
            'bill_assigned',
            $scanId,
            ['scan_id' => (string) $scanId],
        );
    }

    /**
     * Notify scanner when their bill is approved/rejected.
     */
    public static function notifyBillAction(int $scannerId, int $scanId, string $action, string $documentName): bool
    {
        $title = $action === 'approved' ? 'Bill Approved' : 'Bill Rejected';
        $body  = "Your bill '{$documentName}' has been {$action}.";

        return self::sendToUser(
            $scannerId,
            $title,
            $body,
            'bill_' . $action,
            $scanId,
            ['scan_id' => (string) $scanId],
        );
    }

    /**
     * Log notification attempt to database.
     */
    private static function log(?int $userId, ?int $scanId, string $type, string $title, string $body, ?string $fcmToken, string $status, ?string $error, array $data = [], ?int $responseCode = null): void
    {
        try {
            DB::table('notification_logs')->insert([
                'user_id'       => $userId ?? 0,
                'scan_id'       => $scanId,
                'type'          => $type,
                'title'         => $title,
                'body'          => $body,
                'fcm_token'     => $fcmToken ? substr($fcmToken, 0, 500) : null,
                'status'        => $status,
                'error_message' => $error,
                'data'          => json_encode($data),
                'response_code' => $responseCode,
                'sent_at'       => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log notification: ' . $e->getMessage());
        }
    }
}
