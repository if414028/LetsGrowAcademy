<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'full_name',
        'status',
        'dst_code',
        'date_of_birth',
        'phone_number',
        'join_date',
        'city_of_domicile',
        'photo',
        'id_card',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'join_date' => 'date',
            'last_login_at' => 'datetime',
        ];
    }

    /* =========================
     | USER HIERARCHY RELATIONS
     ========================= */

    public function hierarchyParent()
    {
        return $this->hasOne(\App\Models\UserHierarchy::class, 'child_user_id');
    }

    public function hierarchyChildren()
    {
        return $this->hasMany(\App\Models\UserHierarchy::class, 'parent_user_id');
    }

    public function parentUser()
    {
        return $this->belongsToMany(
            self::class,
            'user_hierarchies',
            'child_user_id',
            'parent_user_id'
        )->withTimestamps();
    }

    public function childrenUsers()
    {
        return $this->belongsToMany(
            self::class,
            'user_hierarchies',
            'parent_user_id',
            'child_user_id'
        )->withTimestamps();
    }


    /* =========================
     | SALES & ORDER RELATIONS
     ========================= */

    /**
     * Sales order yang dibuat user ini (sebagai sales)
     */
    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'sales_user_id');
    }

    /**
     * KPI harian sales
     */
    public function salesKpiDailies()
    {
        return $this->hasMany(SalesKpiDaily::class, 'sales_user_id');
    }

    /* =========================
     | CONTEST RELATIONS
     ========================= */

    /**
     * Contest yang dibuat user ini
     */
    public function createdContests()
    {
        return $this->hasMany(Contest::class, 'created_by_user_id');
    }

    /**
     * Contest yang diikuti user ini
     */
    public function contestParticipants()
    {
        return $this->hasMany(ContestParticipant::class, 'user_id');
    }

    /**
     * Shortcut: contest yang diikuti
     */
    public function contests()
    {
        return $this->belongsToMany(
            Contest::class,
            'contest_participants',
            'user_id',
            'contest_id'
        );
    }

    /**
     * Progress contest harian user
     */
    public function contestProgressDailies()
    {
        return $this->hasMany(ContestProgressDaily::class, 'user_id');
    }

    /**
     * Contest yang dimenangkan user
     */
    public function contestWinners()
    {
        return $this->hasMany(ContestWinner::class, 'user_id');
    }
}
