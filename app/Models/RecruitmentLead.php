<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'whatsapp_consent',
        'whatsapp_consented_at',
        'whatsapp_consent_version',
        'grow_ad_placement_id',
        'event_payment_status',
        'event_paid_at',
        'event_checkout_id',
    ];

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime', 'event_paid_at' => 'datetime',
            'whatsapp_consent' => 'boolean', 'whatsapp_consented_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function eventPayments(): HasMany
    {
        return $this->hasMany(RecruitmentEventPayment::class);
    }

    public function crmWebhookDeliveries(): HasMany
    {
        return $this->hasMany(CrmWebhookDelivery::class);
    }

    public function growAdPlacement(): BelongsTo
    {
        return $this->belongsTo(GrowAdPlacement::class);
    }

    public function scopeRegisteredForEvent(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query->whereNull('event_checkout_id')->orWhere('event_payment_status', 'paid'));
    }

    public function scopeVisibleInGrowAdCampaign(Builder $query, GrowAdCampaign $campaign): Builder
    {
        return $query->where('owner_user_id', $campaign->user_id)->where('source', self::SOURCE)
            ->where(fn (Builder $query) => $query
                ->whereIn('grow_ad_placement_id', $campaign->placements->pluck('id'))
                ->orWhere(fn (Builder $direct) => $direct->whereNull('grow_ad_placement_id')->where('event_payment_status', 'paid')));
    }

    public function isRegisteredForEvent(): bool
    {
        return $this->event_checkout_id === null || $this->event_payment_status === 'paid';
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
