<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellingKitCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'color', 'sort_order'];

    public function documents(): HasMany
    {
        return $this->hasMany(SellingKitDocument::class)->latest();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
