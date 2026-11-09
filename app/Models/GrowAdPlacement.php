<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class GrowAdPlacement extends Model
{
    protected $guarded = ['id'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(GrowAdCampaign::class, 'campaign_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(RecruitmentLead::class, 'grow_ad_placement_id');
    }

    public function scopeReservedByAnotherUser(Builder $query, int $userId): Builder
    {
        return $query->join('grow_ad_campaigns as campaign', 'campaign.id', '=', 'grow_ad_placements.campaign_id')
            ->where('campaign.user_id', '!=', $userId)
            ->where(fn (Builder $query) => $query
                ->whereIn('campaign.status', ['awaiting_payment', 'preparing'])
                ->orWhere(fn (Builder $active) => $active->where('campaign.status', 'active')->where('campaign.ends_at', '>', now()))
                ->orWhere(fn (Builder $draft) => $draft->where('campaign.status', 'draft')
                    ->where('campaign.updated_at', '>', now()->subDay())));
    }

    public function getMediaUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->media_path);
    }

    public function landingUrl(): string
    {
        return URL::signedRoute('subscriber-recruitment.show', [
            'slug' => $this->campaign->user->landing_page_slug,
            'grow_ad' => $this->campaign->uuid,
            'creative' => $this->id,
        ]);
    }
}
