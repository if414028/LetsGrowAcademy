<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContestWinner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contest_id',
        'user_id',
        'rank',
        'final_installed_units',
        'reached_target',
        'computed_at'
    ];

    protected $casts = [
        'computed_at'     => 'datetime',
        'reached_target'  => 'boolean',
        'rank'            => 'integer',
        'final_installed_units' => 'integer',
    ];

    /**
     * Relasi ke Contest
     */
    public function contest()
    {
        return $this->belongsTo(Contest::class, 'contest_id');
    }

    /**
     * Relasi ke User (winner)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
