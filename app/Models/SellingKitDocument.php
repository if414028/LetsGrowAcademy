<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellingKitDocument extends Model
{
    protected $fillable = [
        'selling_kit_category_id', 'uploaded_by', 'title', 'slug', 'description',
        'file_path', 'original_name', 'mime_type', 'extension', 'file_size',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SellingKitCategory::class, 'selling_kit_category_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFormattedSizeAttribute(): string
    {
        return $this->file_size >= 1048576
            ? number_format($this->file_size / 1048576, 1, ',', '.').' MB'
            : number_format($this->file_size / 1024, 0, ',', '.').' KB';
    }

    public function getTypeLabelAttribute(): string
    {
        return match (true) {
            $this->extension === 'pdf' => 'PDF',
            in_array($this->extension, ['ppt', 'pptx'], true) => 'PowerPoint',
            str_starts_with($this->mime_type, 'image/') => 'Gambar',
            str_starts_with($this->mime_type, 'video/') => 'Video',
            default => strtoupper($this->extension),
        };
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
