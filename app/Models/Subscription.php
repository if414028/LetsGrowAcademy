<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'duration_months',
        'amount',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'payment_status',
        'payment_type',
        'snap_token',
        'paid_at',
        'payment_payload',
        'payment_proof',
        'status',
        'submitted_at',
        'starts_at',
        'ends_at',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'paid_at' => 'datetime',
            'payment_payload' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
