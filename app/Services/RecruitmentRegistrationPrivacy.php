<?php

namespace App\Services;

use App\Models\CrmRegistrationTombstone;
use App\Models\RecruitmentLead;
use Illuminate\Support\Facades\DB;

class RecruitmentRegistrationPrivacy
{
    public function __construct(private readonly CrmWebhookOutbox $outbox) {}

    public function revokeWhatsapp(RecruitmentLead $lead): bool
    {
        return DB::transaction(function () use ($lead) {
            $lead = RecruitmentLead::where('source', RecruitmentLead::SOURCE)->lockForUpdate()->findOrFail($lead->id);
            if ($lead->whatsapp_consent === false && $lead->whatsapp_consent_revoked_at !== null) {
                return false;
            }
            $lead->update(['whatsapp_consent' => false, 'whatsapp_consent_revoked_at' => now()]);
            // Supersede undelivered snapshots that still contain an earlier opt-in.
            $lead->crmWebhookDeliveries()->whereNull('delivered_at')->delete();
            $this->outbox->record($lead, 'registration.updated');

            return true;
        });
    }

    public function delete(RecruitmentLead $lead): CrmRegistrationTombstone
    {
        return DB::transaction(function () use ($lead) {
            $lead = RecruitmentLead::where('source', RecruitmentLead::SOURCE)->lockForUpdate()->findOrFail($lead->id);
            $tombstone = CrmRegistrationTombstone::create([
                'registration_id' => $lead->registrationId(),
                'environment' => config('crm.environment'),
                'deleted_at' => now(),
            ]);
            $this->outbox->recordDeletion($tombstone);
            // Existing FK cascades remove participant payment rows and all PII
            // snapshots. The unlinked tombstone event survives for CRM delivery.
            $lead->delete();

            return $tombstone;
        });
    }
}
