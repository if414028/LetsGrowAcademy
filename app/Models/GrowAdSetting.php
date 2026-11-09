<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrowAdSetting extends Model
{
    protected $fillable = ['user_id', 'activated_at'];

    protected function casts(): array
    {
        return ['activated_at' => 'datetime'];
    }
}
