<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * Send push notification to a specific FCM token.
     */
    public static function send(string $fcmToken, string $title, string $body, string $type = 'general', ?int $userId = null, ?int $scanId = null, array $data = []): bool
    {
        $serverKey = config('services.firebase.server_key');

        if (!$serverKey) {
            self::log($userId, $scanId, $type, $title, $body, $fcmToken, 'failed', 'FCM server key not configured', $data);
            Log::warning('FCM server key not configured.');
            return false;
        }

        try {
            $payload = [
                'to' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'data' => array_merge($data, ['type' => $type, 'scan_id' => (string) ($scanId ?? '')]),
                'priority' => 'high',
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type'  => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            $success = $response->successful();
            $responseCode = $response->status();
            $errorMsg = !$success ? ($response->body() ?? 'Unknown error') : null;

            self::log($userId, $scanId, $type, $title, $body, $fcmToken, $success ? 'sent' : 'failed', $errorMsg, $data, $responseCode);

            if (!$success) {
                Log::error('FCM send failed: ' . $response->body());
            }

            return $success;
        } catch (\Exception $e) {
            self::log($userId, $scanId, $type, $title, $body, $fcmToken, 'failed', $e->getMessage(), $data);
            Log::error('FCM send exception: ' . $e->getMessage());
            return false;
        }
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
