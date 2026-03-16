<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private string $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    /**
     * Store notification in DB and optionally push via FCM.
     */
    public function sendToUser(User $user, string $title, string $body, string $type, array $data = []): void
    {
        // Persist notification
        Notification::create([
            'user_id' => $user->id,
            'title'   => $title,
            'body'    => $body,
            'type'    => $type,
            'data'    => $data,
        ]);

        // Push via FCM
        $tokens = $user->deviceTokens()->pluck('fcm_token');
        if ($tokens->isEmpty()) {
            return;
        }

        $serverKey = config('services.firebase.server_key');
        if (!$serverKey) {
            Log::warning('FCM server key not configured.');
            return;
        }

        foreach ($tokens as $token) {
            try {
                Http::withHeaders([
                    'Authorization' => "key={$serverKey}",
                    'Content-Type'  => 'application/json',
                ])->post($this->fcmUrl, [
                    'to'           => $token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data'         => array_merge($data, ['type' => $type]),
                ]);
            } catch (\Throwable $e) {
                Log::error('FCM push failed', ['token' => $token, 'error' => $e->getMessage()]);
            }
        }
    }
}
