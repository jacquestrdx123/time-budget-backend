<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantJoinRequest extends Model
{
    protected $table = 'tenant_join_requests';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password_hash',
        'status',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
