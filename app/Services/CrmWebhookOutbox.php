<?php

namespace App\Services;

use App\Models\CrmWebhookDelivery;
use App\Models\RecruitmentEventPayment;
use App\Models\RecruitmentLead;
use Illuminate\Support\Str;
use LogicException;

class CrmWebhookOutbox
{
    public function __construct(private readonly CrmRegistrationData $data) {}

    // Call inside the transaction that saves the registration/payment. No network I/O here.
    public function record(RecruitmentLead $lead, string $type, ?RecruitmentEventPayment $payment = null): void
    {
        if ($lead->source !== RecruitmentLead::SOURCE) {
            return;
        }

        if ($lead->getConnection()->transactionLevel() === 0) {
            throw new LogicException('CRM events must be saved in the registration transaction.');
        }

        $delivery = CrmWebhookDelivery::create([
            'event_id' => (string) Str::uuid(),
            'recruitment_lead_id' => $lead->id,
            'event_type' => $type,
            'payload' => [],
            'next_attempt_at' => now(),
        ]);

        $delivery->update(['payload' => [
            'schema_version' => '1.0',
            'event_id' => $delivery->event_id,
            'event_type' => $type,
            'sequence' => $delivery->id,
            'occurred_at' => $delivery->created_at->toISOString(),
            'data' => $this->data->registration($lead->fresh(), $delivery->id),
            ...($payment ? ['transaction' => $this->data->payment($payment)] : []),
        ]]);
    }
}
