<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentEventPayment extends Model
{
    protected $attributes = ['status' => 'pending'];

    protected $fillable = ['midtrans_order_id', 'midtrans_transaction_id', 'class_amount', 'service_fee', 'checkout_url', 'status', 'paid_at'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function total(): int
    {
        return $this->class_amount + $this->service_fee;
    }
}
