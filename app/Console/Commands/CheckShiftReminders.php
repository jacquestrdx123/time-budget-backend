<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\Shift;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckShiftReminders extends Command
{
    protected $signature = 'reminders:shift';

    protected $description = 'Send notifications for shifts starting within the configured reminder window';

    public function handle(NotificationService $notificationService): int
    {
        if (! config('timebudget.scheduler_enabled', true)) {
            return self::SUCCESS;
        }

        $minutes = config('timebudget.shift_reminder_minutes', 5);
        $now = now();
        $windowEnd = $now->copy()->addMinutes($minutes);

        $upcoming = Shift::where('start_time', '>', $now)
            ->where('start_time', '<=', $windowEnd)
            ->where('reminder_sent', false)
            ->get();

        if ($upcoming->isEmpty()) {
            return self::SUCCESS;
        }

        $this->info("Found {$upcoming->count()} shift(s) starting within {$minutes} minutes");

        foreach ($upcoming as $shift) {
            $project = Project::find($shift->project_id);
            $projectName = $project?->name ?? 'Unknown project';
            $startStr = $shift->start_time->format('H:i');

            $title = 'Shift starting soon';
            $body = "Your shift on {$projectName} starts at {$startStr} (in ~{$minutes} min).";

            try {
                $notificationService->sendNotification([
                    'userId' => $shift->user_id,
                    'tenantId' => $shift->tenant_id,
                    'title' => $title,
                    'body' => $body,
                    'notificationType' => 'shift_reminder',
                    'data' => [
                        'shift_id' => $shift->id,
                        'project_id' => $shift->project_id,
                        'start_time' => $shift->start_time->toIso8601String(),
                    ],
                ]);

                $shift->update(['reminder_sent' => true]);
                $this->line("Reminder sent for shift {$shift->id} (user {$shift->user_id}, project {$projectName} at {$startStr})");
            } catch (\Throwable $e) {
                $this->error("Failed to send reminder for shift {$shift->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
