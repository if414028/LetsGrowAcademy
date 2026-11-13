<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmWebhookDelivery extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'next_attempt_at' => 'datetime',
            'locked_until' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }
}
