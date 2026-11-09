<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GrowAdCampaign extends Model
{
    public const STATUS_LABELS = [
        'draft' => 'Draf',
        'awaiting_payment' => 'Menunggu pembayaran',
        'preparing' => 'Sedang disiapkan',
        'active' => 'Sedang berjalan',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(fn (self $campaign) => $campaign->uuid ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime', 'starts_at' => 'datetime', 'ends_at' => 'datetime',
            'metrics_updated_at' => 'datetime', 'payment_payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(GrowAdPlacement::class, 'campaign_id');
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'active' && $this->ends_at?->isPast()) {
            return 'Periode berakhir';
        }

        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isRunning(): bool
    {
        return $this->payment_status === 'paid' && $this->status === 'active'
            && $this->starts_at?->lte(now()) && $this->ends_at?->isFuture();
    }
}
