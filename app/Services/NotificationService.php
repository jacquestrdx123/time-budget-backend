<?php

namespace App\Services;

use App\Jobs\SendPushNotificationJob;
use App\Models\FcmDevice;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;

class NotificationService
{
    public function getOrCreatePreferences(int $userId): NotificationPreference
    {
        $prefs = NotificationPreference::where('user_id', $userId)->first();
        if (! $prefs) {
            $prefs = NotificationPreference::create(['user_id' => $userId]);
        }

        return $prefs;
    }

    /**
     * Create notification record and optionally send push/email (Phase 6 will add FCM/Mail).
     */
    public function sendNotification(array $params): ?Notification
    {
        $userId = $params['userId'] ?? null;
        $tenantId = $params['tenantId'] ?? null;
        $title = $params['title'] ?? '';
        $body = $params['body'] ?? '';
        $notificationType = $params['notificationType'] ?? 'general';
        $data = $params['data'] ?? null;

        if (! $userId) {
            return null;
        }

        $user = User::find($userId);
        if (! $user) {
            return null;
        }

        $tid = $tenantId ?? $user->tenant_id;
        $prefs = $this->getOrCreatePreferences($userId);

        $sentPush = false;
        $sentEmail = false;
        // Phase 6: implement push (FCM) and email
        if ($prefs->push_enabled && $this->isTypeEnabled($prefs, $notificationType)) {
            // $sentPush = $this->sendPushToUser($userId, $title, $body, $data);
        }
        if ($prefs->email_enabled && $this->isTypeEnabled($prefs, $notificationType)) {
            // $sentEmail = $this->sendEmailToUser($user->email, $title, $body);
        }

        $channel = 'none';
        if ($sentPush && $sentEmail) {
            $channel = 'all';
        } elseif ($sentPush) {
            $channel = 'push';
        } elseif ($sentEmail) {
            $channel = 'email';
        }

        return Notification::create([
            'tenant_id' => $tid,
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'notification_type' => $notificationType,
            'channel' => $channel,
            'sent_push' => $sentPush,
            'sent_email' => $sentEmail,
        ]);
    }

    private function isTypeEnabled(NotificationPreference $prefs, string $notificationType): bool
    {
        $map = [
            'task_assigned' => $prefs->task_assigned,
            'task_updated' => $prefs->task_updated,
            'shift_reminder' => $prefs->shift_reminder,
            'project_updated' => $prefs->project_updated,
            'custom_reminder' => $prefs->custom_reminder,
        ];

        return $map[$notificationType] ?? true;
    }

    /**
     * Test push to current user (Phase 6: implement FCM).
     */
    public function testPushToUser(int $userId): array
    {
        $user = User::find($userId);
        if (! $user) {
            return ['success' => false, 'error' => 'User not found', 'devices' => []];
        }

        $devices = FcmDevice::where('user_id', $userId)->get();
        if ($devices->isEmpty()) {
            return [
                'success' => false,
                'error' => 'No registered devices. Open the app on your phone/browser and allow notifications first.',
                'devices' => [],
            ];
        }

        if (! config('timebudget.firebase_enabled')) {
            return [
                'success' => false,
                'error' => 'Push notifications are not configured (FIREBASE_ENABLED is false).',
                'devices' => $devices->map(fn ($d) => ['id' => $d->id, 'device_name' => $d->device_name])->all(),
            ];
        }

        $tokens = $devices->pluck('token')->all();
        SendPushNotificationJob::dispatch(
            $tokens,
            'Test notification',
            'This is a test push from TimeBudget.',
            ['type' => 'test']
        );

        return [
            'success' => true,
            'error' => null,
            'devices' => $devices->map(fn ($d) => ['id' => $d->id, 'device_name' => $d->device_name])->all(),
            'queued' => true,
        ];
    }
}
