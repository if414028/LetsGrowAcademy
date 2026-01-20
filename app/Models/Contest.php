<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;

class Contest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'target_unit',
        'reward',
        'banner_url',
        'created_by_user_id',
        'created_by_role_id',
        'status'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    /**
     * Contest dibuat oleh user siapa
     */
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Contest dibuat oleh role apa (SM/HM/Admin, dll)
     */
    public function createdByRole()
    {
        return $this->belongsTo(Role::class, 'created_by_role_id');
    }

    /**
     * Peserta contest (pivot: contest_participants)
     * pivot fields: joined_at, status
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'contest_participants', 'contest_id', 'user_id')
            ->withPivot(['joined_at', 'status']);
    }

    /**
     * Role yang eligible ikut contest ini (pivot: contest_eligibility_roles)
     */
    public function eligibleRoles()
    {
        return $this->belongsToMany(Role::class, 'contest_eligibility_roles', 'contest_id', 'eligible_role_id');
    }

    /**
     * Progress harian per user untuk contest ini
     */
    public function progressDailies()
    {
        return $this->hasMany(ContestProgressDaily::class, 'contest_id');
    }

    /**
     * Winners contest ini
     */
    public function winners()
    {
        return $this->hasMany(ContestWinner::class, 'contest_id');
    }
    
}
