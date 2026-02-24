<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceCutoff extends Model
{
    protected $fillable = ['start_date', 'end_date', 'updated_by'];

    public static function current(): ?self
    {
        return static::query()->latest('id')->first();
    }
}
