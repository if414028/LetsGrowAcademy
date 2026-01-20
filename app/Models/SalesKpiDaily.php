<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesKpiDaily extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'kpi_date',
        'sales_user_id',
        'units',
        'order_count',
        'installed_count',
        'ccp_approved_count',
        'recurring_count'
    ];

    protected $casts = [
        'kpi_date'            => 'date',
        'units'               => 'integer',
        'order_count'         => 'integer',
        'installed_count'     => 'integer',
        'ccp_approved_count'  => 'integer',
        'recurring_count'     => 'integer',
    ];

    /**
     * KPI ini milik sales user siapa
     */
    public function salesUser()
    {
        return $this->belongsTo(User::class, 'sales_user_id');
    }
}
