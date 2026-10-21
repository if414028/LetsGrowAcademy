<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BirthdayGreetingRecipient extends Model
{
    protected $fillable = ['user_id', 'customer_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
