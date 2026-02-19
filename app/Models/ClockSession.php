<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClockSession extends Model
{
    protected $table = 'clock_sessions';

    protected $fillable = [
        'tenant_id', 'user_id', 'shift_id', 'project_id',
        'clocked_in_at', 'clocked_out_at',
    ];

    protected function casts(): array
    {
        return [
            'clocked_in_at' => 'datetime',
            'clocked_out_at' => 'datetime',
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

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Return shift-like shape for API (start_time, end_time, clocked_in_at, clocked_out_at) */
    public function toShiftLikeArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'tenant_id' => $this->tenant_id,
            'project_id' => $this->project_id,
            'shift_id' => $this->shift_id,
            'start_time' => $this->clocked_in_at?->toIso8601String(),
            'end_time' => $this->clocked_out_at?->toIso8601String(),
            'clocked_in_at' => $this->clocked_in_at?->toIso8601String(),
            'clocked_out_at' => $this->clocked_out_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
