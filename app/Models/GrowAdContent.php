<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GrowAdContent extends Model
{
    protected $fillable = ['title', 'description', 'media_path', 'media_type', 'is_active'];

    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query->latest()->orderByDesc('id');
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getMediaUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->media_path);
    }
}
