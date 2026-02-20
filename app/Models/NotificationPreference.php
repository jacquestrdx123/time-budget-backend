<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    public $timestamps = false;

    protected $table = 'notification_preferences';

    protected $fillable = [
        'user_id', 'push_enabled', 'email_enabled', 'task_assigned', 'task_updated',
        'shift_reminder', 'project_updated', 'custom_reminder',
    ];

    protected function casts(): array
    {
        return [
            'push_enabled' => 'boolean',
            'email_enabled' => 'boolean',
            'task_assigned' => 'boolean',
            'task_updated' => 'boolean',
            'shift_reminder' => 'boolean',
            'project_updated' => 'boolean',
            'custom_reminder' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
