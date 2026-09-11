<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['full_name', 'date_of_birth', 'phone_number', 'address', 'religion', 'unit_serial_number', 'ktp_path', 'unit_barcode_path', 'health_planner_id'];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'health_planner_id');
    }

    public function healthPlanner()
    {
        return $this->belongsTo(User::class, 'health_planner_id');
    }

    /**
     * Customer punya banyak sales order
     */
    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }
}
