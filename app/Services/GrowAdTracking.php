<?php

namespace App\Services;

use App\Models\GrowAdPlacement;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class GrowAdTracking
{
    public function placementForPage(Request $request, User $owner): ?GrowAdPlacement
    {
        // Meta and analytics append these parameters after signing the destination URL.
        // Ownership and creative identifiers always remain part of the signature.
        if (! $request->hasValidSignatureWhileIgnoring(['fbclid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'utm_id'])) {
            return null;
        }

        if (! is_scalar($request->query('creative')) || ! is_string($request->query('grow_ad'))) {
            return null;
        }
        $placement = GrowAdPlacement::with('campaign')->whereKey($request->query('creative'))
            ->whereHas('campaign', fn ($query) => $query->where('uuid', $request->query('grow_ad'))->where('user_id', $owner->id))
            ->first();

        return $placement?->campaign->isRunning() ? $placement : null;
    }

    public function tokenForPlacement(GrowAdPlacement $placement): string
    {
        return Crypt::encryptString(json_encode(['placement_id' => $placement->id, 'expires' => now()->addDays(7)->timestamp]));
    }

    public function placementFromToken(?string $token, User $owner): ?GrowAdPlacement
    {
        if (! $token) {
            return null;
        }
        try {
            $data = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException $exception) {
            return null;
        }
        if (! is_array($data) || ($data['expires'] ?? 0) < now()->timestamp) {
            return null;
        }

        return GrowAdPlacement::whereKey($data['placement_id'] ?? null)
            ->whereHas('campaign', fn ($query) => $query->where('user_id', $owner->id)
                ->where('payment_status', 'paid')->whereIn('status', ['active', 'completed']))
            ->first();
    }

    public function pixelId(?GrowAdPlacement $placement, bool $conversion = false): ?string
    {
        $pixelId = $placement?->pixel_id;
        if (! is_string($pixelId) || ! preg_match('/^[0-9]{5,30}$/', $pixelId)) {
            return null;
        }

        $campaign = $placement->campaign;
        $enabled = $conversion
            ? $campaign->payment_status === 'paid' && in_array($campaign->status, ['active', 'completed'], true)
            : $campaign->isRunning();

        return $enabled ? $pixelId : null;
    }
}
