<?php

namespace App\Services;

use App\Models\CrmWebhookDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CrmWebhookSender
{
    public function validateConfiguration(): void
    {
        if (strlen((string) config('crm.webhook_secret')) < 32) {
            throw new RuntimeException('CRM_WEBHOOK_SECRET must contain at least 32 characters.');
        }

        foreach (['registration_webhook_url', 'payment_webhook_url'] as $key) {
            $url = (string) config('crm.'.$key);
            $parts = parse_url($url);
            if (! filter_var($url, FILTER_VALIDATE_URL) || ($parts['scheme'] ?? '') !== 'https'
                || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
                throw new RuntimeException('CRM webhook endpoints must be HTTPS URLs without embedded credentials or fragments.');
            }
        }
    }

    public function send(CrmWebhookDelivery $delivery): void
    {
        $lease = (string) Str::uuid();
        $claimed = CrmWebhookDelivery::whereKey($delivery->id)->whereNull('delivered_at')
            ->where('next_attempt_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('locked_until')->orWhere('locked_until', '<=', now()))
            ->update([
                'lease_token' => $lease, 'locked_until' => now()->addMinutes(2),
                'attempts' => DB::raw('attempts + 1'),
            ]);
        if (! $claimed) {
            return;
        }

        $delivery->refresh();
        $httpStatus = null;
        $errorCode = null;
        try {
            $body = json_encode($delivery->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $timestamp = (string) now()->timestamp;
            $signature = hash_hmac('sha256', $timestamp.'.'.$body, (string) config('crm.webhook_secret'));
            $url = config('crm.'.($delivery->event_type === 'registration.created' ? 'registration_webhook_url' : 'payment_webhook_url'));
            $response = Http::acceptJson()->connectTimeout(3)->timeout(10)->withoutRedirecting()
                ->withHeaders([
                    'X-LGA-Event-Id' => $delivery->event_id,
                    'X-LGA-Event-Type' => $delivery->event_type,
                    'X-LGA-Timestamp' => $timestamp,
                    'X-LGA-Signature' => 'v1='.$signature,
                ])->withBody($body, 'application/json')->post($url);
            $httpStatus = $response->status();
            if (! $response->successful()) {
                $errorCode = 'http_'.$httpStatus;
            }
        } catch (Throwable $exception) {
            $errorCode = 'delivery_exception';
            // Never log the URL, participant data, response bodies, signatures, or secrets.
            Log::warning('CRM webhook delivery failed.', ['event_id' => $delivery->event_id, 'error_type' => $exception::class]);
        }

        $delivered = $errorCode === null;
        $delays = [60, 300, 900, 3600, 10800, 21600, 86400];
        CrmWebhookDelivery::whereKey($delivery->id)->where('lease_token', $lease)->update([
            'delivered_at' => $delivered ? now() : null,
            'next_attempt_at' => $delivered ? null : now()->addSeconds($delays[min($delivery->attempts - 1, count($delays) - 1)]),
            'last_http_status' => $httpStatus,
            'last_error_code' => $errorCode,
            'lease_token' => null,
            'locked_until' => null,
        ]);
    }
}
