<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'full_name',
        'phone_number',
        'address'
    ];

    /**
     * Customer punya banyak sales order
     */
    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }
}
