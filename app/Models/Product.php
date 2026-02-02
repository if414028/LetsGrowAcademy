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
        'description',
        'type',
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
            ->where('is_active', true)
            ->orderBy('sort_order');
    }
     
    public function displayPrice()
    {
        return $this->hasOne(ProductPrice::class, 'product_id')
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN billing_type='one_time' THEN 0 ELSE 1 END")
            ->orderBy('sort_order');
    }


    public function bundleItems()
    {
        return $this->belongsToMany(Product::class, 'bundle_items', 'bundle_id', 'product_id')
            ->withPivot(['qty','sort_order'])
            ->orderBy('bundle_items.sort_order');
    }

    public function usedInBundles()
    {
        return $this->belongsToMany(Product::class, 'bundle_items', 'product_id', 'bundle_id')
            ->withPivot(['qty','sort_order']);
    }
}
