<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentEventPayment extends Model
{
    protected $fillable = ['midtrans_order_id', 'class_amount', 'service_fee', 'checkout_url', 'status'];

    public function total(): int
    {
        return $this->class_amount + $this->service_fee;
    }
}
