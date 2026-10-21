<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentLead extends Model
{
    public const SOURCE = 'recruitment_landing';

    protected $fillable = [
        'source',
        'source_slug',
        'source_url',
        'full_name',
        'phone_number',
        'domicile',
        'activity',
        'interest',
        'consented_at',
    ];

    protected function casts(): array
    {
        return ['consented_at' => 'datetime'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public static function normalizePhoneNumber(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits;
    }

    public function getWhatsappUrlAttribute(): string
    {
        return 'https://wa.me/'.$this->phone_number;
    }
}
