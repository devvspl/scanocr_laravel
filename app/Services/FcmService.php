<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Send push notification to a user's device.
     */
    public static function sendToUser(int $userId, string $title, string $body, array $data = []): bool
    {
        $user = User::find($userId);
        if (!$user || empty($user->fcm_token)) return false;

        return self::send($user->fcm_token, $title, $body, $data);
    }

    /**
     * Send push notification to a specific FCM token.
     */
    public static function send(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        $serverKey = config('services.firebase.server_key');
        if (!$serverKey) {
            Log::warning('FCM server key not configured.');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type'  => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'data' => $data,
                'priority' => 'high',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('FCM send failed: ' . $e->getMessage());
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
            "Bill '{$documentName}' has been assigned to you for approval.",
            ['type' => 'bill_assigned', 'scan_id' => (string) $scanId],
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
            ['type' => 'bill_' . $action, 'scan_id' => (string) $scanId],
        );
    }
}
