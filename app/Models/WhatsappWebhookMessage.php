<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappWebhookMessage extends Model
{
    protected $fillable = [
        'whatsapp_instance_id',
        'instance_name',
        'event',
        'direction',
        'sender_number',
        'sender_name',
        'message_text',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsappInstance::class, 'whatsapp_instance_id');
    }
}
