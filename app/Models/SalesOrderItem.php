<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'qty'
    ];  

    protected $casts = [
        'qty' => 'integer',
    ];

    /**
     * Item ini milik sales order apa
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    /**
     * Product apa yang dijual di item ini
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
