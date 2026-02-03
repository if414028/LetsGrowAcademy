<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'product_price_id',
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

    public function productPrice()
    {
        return $this->belongsTo(\App\Models\ProductPrice::class, 'product_price_id');
    }

}
