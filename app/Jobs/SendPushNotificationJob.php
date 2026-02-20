<?php

namespace App\Jobs;

use App\Services\FcmSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

    public function handle(FcmSender $fcm): void
    {
        $tokens = array_filter($this->tokens);
        if ($tokens === []) {
            Log::info('SendPushNotificationJob: No tokens to send to, skipping.');

            return;
        }

        if (! $fcm->isConfigured()) {
            Log::warning('SendPushNotificationJob: Firebase disabled or credentials not readable, skipping.');

            return;
        }

        Log::info('SendPushNotificationJob: Sending push notification.', [
            'device_count' => count($tokens),
            'title' => $this->title,
            'body' => $this->body,
        ]);

        foreach ($tokens as $token) {
            $ok = $fcm->send($token, $this->title, $this->body, $this->data);
            if ($ok) {
                Log::info('SendPushNotificationJob: Push sent successfully.', [
                    'token_preview' => substr($token, 0, 20) . '...',
                ]);
            } else {
                Log::warning('SendPushNotificationJob: Push failed for token.', [
                    'token_preview' => substr($token, 0, 20) . '...',
                ]);
            }
        }
    }
}
