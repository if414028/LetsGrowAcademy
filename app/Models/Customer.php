<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['full_name', 'date_of_birth', 'phone_number', 'address'];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    /**
     * Customer punya banyak sales order
     */
    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }
}
