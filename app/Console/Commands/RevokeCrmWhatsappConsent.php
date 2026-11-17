<?php

namespace App\Console\Commands;

use App\Models\RecruitmentLead;
use App\Services\RecruitmentRegistrationPrivacy;
use Illuminate\Console\Command;

class RevokeCrmWhatsappConsent extends Command
{
    protected $signature = 'crm:revoke-whatsapp-consent {registration : Registration ID, for example LGA-123}';

    protected $description = 'Record a participant WhatsApp opt-out and queue an updated CRM snapshot';

    public function handle(RecruitmentRegistrationPrivacy $privacy): int
    {
        $registration = (string) $this->argument('registration');
        $lead = preg_match('/^LGA-[1-9][0-9]*$/', $registration)
            ? RecruitmentLead::where('source', RecruitmentLead::SOURCE)->find(substr($registration, 4)) : null;
        if (! $lead) {
            $this->error('Recruitment registration not found.');

            return self::FAILURE;
        }
        $changed = $privacy->revokeWhatsapp($lead);
        $this->info($changed ? 'WhatsApp consent revoked; CRM update queued.' : 'WhatsApp consent was already revoked.');

        return self::SUCCESS;
    }
}
