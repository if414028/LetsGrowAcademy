<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContestProgressDaily extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contest_id',
        'user_id',
        'progress_date',
        'installed_units',
    ];

    protected $casts = [
        'progress_date' => 'date'
    ];

    /**
     * Relasi ke Contest
     */
    public function contest()
    {
        return $this->belongsTo(Contest::class, 'contest_id');
    }

    /**
     * Relasi ke User (owner progress)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
