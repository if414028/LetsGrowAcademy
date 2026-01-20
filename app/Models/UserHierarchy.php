<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserHierarchy extends Model
{
    protected $fillable = [
        'parent_user_id', 
        'child_user_id', 
        'relation_type'
    ];

    public function parentUser()
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function childUser()
    {
        return $this->belongsTo(User::class, 'child_user_id');
    }
}
