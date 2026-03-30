<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WhatsappInstance extends Model
{
    protected $fillable = [
        'user_id',
        'instance_name',
        'phone_number',
        'display_name',
        'status',
        'qrcode',
        'pairing_code',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateInstanceName(): string
    {
        return 'instance-' . Str::random(12);
    }
}
