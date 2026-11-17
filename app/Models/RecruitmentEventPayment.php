<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentEventPayment extends Model
{
    protected $attributes = ['status' => 'pending'];

    protected $fillable = ['midtrans_order_id', 'midtrans_transaction_id', 'class_amount', 'service_fee', 'checkout_url', 'status', 'paid_at', 'selected_payment_method', 'pricing_details', 'snap_token'];

    protected $hidden = ['snap_token', 'checkout_url'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'pricing_details' => 'array'];
    }

    public function total(): int
    {
        return $this->class_amount + $this->service_fee;
    }
}
