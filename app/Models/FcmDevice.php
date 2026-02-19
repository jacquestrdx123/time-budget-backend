<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcmDevice extends Model
{
    protected $table = 'fcm_devices';

    protected $fillable = ['user_id', 'token', 'device_name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
