<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckCustomReminders extends Command
{
    protected $signature = 'reminders:custom';

    protected $description = 'Send due custom reminders';

    public function handle(NotificationService $notificationService): int
    {
        if (! config('timebudget.scheduler_enabled', true)) {
            return self::SUCCESS;
        }

        $due = Reminder::where('trigger_at', '<=', now())
            ->where('sent', false)
            ->get();

        if ($due->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($due as $reminder) {
            $title = $reminder->title;
            $body = $reminder->description ?? 'This is your scheduled reminder.';

            try {
                $notificationService->sendNotification([
                    'userId' => $reminder->user_id,
                    'tenantId' => $reminder->tenant_id,
                    'title' => $title,
                    'body' => $body,
                    'notificationType' => 'custom_reminder',
                    'data' => [
                        'reminder_id' => $reminder->id,
                        'trigger_at' => $reminder->trigger_at->toIso8601String(),
                    ],
                ]);

                $reminder->update(['sent' => true]);
                $this->line("Reminder sent for {$reminder->id} (user {$reminder->user_id}, \"{$reminder->title}\")");
            } catch (\Throwable $e) {
                $this->error("Failed to send reminder {$reminder->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
