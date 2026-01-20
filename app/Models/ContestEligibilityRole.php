<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;

class ContestEligibilityRole extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contest_id',
        'eligible_role_id'
    ];

    /**
     * Relasi ke Contest
     */
    public function contest()
    {
        return $this->belongsTo(Contest::class, 'contest_id');
    }

    /**
     * Relasi ke Role yang eligible
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'eligible_role_id');
    }
}
