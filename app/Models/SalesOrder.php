<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_no',
        'sales_user_id',
        'customer_id',
        'customer_type',
        'key_in_at',
        'install_date',
        'is_recurring',
        'payment_method',
        'payment_method_remarks',
        'status',
        'ccp_status',
        'ccp_remarks',
        'ccp_approved_at',
        'status_reason',
    ];

    protected $casts = [
        'key_in_at'     => 'datetime',
        'install_date'  => 'date',
        'is_recurring'  => 'boolean',
        'ccp_approved_at'  => 'datetime',
    ];

    /**
     * Order ini dibuat oleh sales siapa
     */
    public function salesUser()
    {
        return $this->belongsTo(User::class, 'sales_user_id');
    }

    /**
     * Customer dari order ini
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Produk yang dipesan (jika sales_orders punya product_id)
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function items()
    {
        return $this->hasMany(\App\Models\SalesOrderItem::class);
    }

    // app/Models/SalesOrder.php

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'partial'  => 'CC',
            'outright' => 'POA',
            default    => (string) $this->payment_method,
        };
    }
}
