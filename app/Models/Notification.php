<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'tenant_id', 'user_id', 'title', 'body', 'notification_type', 'channel',
        'is_read', 'sent_push', 'sent_email',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'sent_push' => 'boolean',
            'sent_email' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
