<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BirthdayGreetingSetting extends Model
{
    protected $fillable = ['user_id', 'enabled', 'activated_at'];

    protected $casts = ['enabled' => 'boolean', 'activated_at' => 'datetime'];
}
