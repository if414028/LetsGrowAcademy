<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContestParticipant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contest_id',
        'user_id',
        'joined_at',
        'status'
    ];
    
    protected $casts = [
        'joined_at' => 'date'
    ];

    /**
     * Relasi ke Contest
     */
    public function contest()
    {
        return $this->belongsTo(Contest::class, 'contest_id');
    }

    /**
     * Relasi ke User (peserta contest)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}