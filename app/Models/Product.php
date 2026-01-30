<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'product_name',
        'model',
        'price',
        'product_image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Product punya banyak sales order
     */
    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'product_id');
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function primaryPrice()
    {
        return $this->hasOne(ProductPrice::class)
            ->where('billing_type', 'one_time')
            ->orderBy('sort_order');
    }
}
