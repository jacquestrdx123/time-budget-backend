<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string>  $tokens  FCM device tokens
     * @param  array<string, string>  $data  Optional key-value data payload
     */
    public function __construct(
        public array $tokens,
        public string $title,
        public string $body,
        public array $data = []
    ) {}

    public function handle(): void
    {
        $tokens = array_filter($this->tokens);
        if ($tokens === []) {
            Log::info('SendPushNotificationJob: No tokens to send to, skipping.');

            return;
        }

        $credentialsPath = config('timebudget.firebase_credentials_path');
        $path = str_starts_with($credentialsPath, '/') ? $credentialsPath : base_path($credentialsPath);

        if (! config('timebudget.firebase_enabled') || ! is_readable($path)) {
            Log::warning('SendPushNotificationJob: Firebase disabled or credentials not readable, skipping.', [
                'firebase_enabled' => config('timebudget.firebase_enabled'),
                'path' => $path,
            ]);

            return;
        }

        Log::info('SendPushNotificationJob: Sending push notification.', [
            'device_count' => count($tokens),
            'title' => $this->title,
            'body' => $this->body,
        ]);

        try {
            $factory = (new Factory)->withServiceAccount($path);
            $messaging = $factory->createMessaging();
        } catch (\Throwable $e) {
            Log::error('SendPushNotificationJob: Failed to create Firebase messaging.', [
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $notification = Notification::create($this->title, $this->body);

        foreach ($tokens as $token) {
            try {
                $message = CloudMessage::new()
                    ->withNotification($notification)
                    ->withToken($token);

                if ($this->data !== []) {
                    $message = $message->withData($this->data);
                }

                $messaging->send($message);
                Log::info('SendPushNotificationJob: Push sent successfully.', [
                    'token_preview' => substr($token, 0, 20) . '...',
                ]);
            } catch (\Throwable $e) {
                Log::warning('SendPushNotificationJob: Push failed for token.', [
                    'token_preview' => substr($token, 0, 20) . '...',
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
