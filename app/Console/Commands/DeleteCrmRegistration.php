<?php

namespace App\Console\Commands;

use App\Models\CrmRegistrationTombstone;
use App\Models\RecruitmentLead;
use App\Services\RecruitmentRegistrationPrivacy;
use Illuminate\Console\Command;

class DeleteCrmRegistration extends Command
{
    protected $signature = 'crm:delete-registration {registration : Registration ID, for example LGA-123} {--force : Confirm deletion without an interactive prompt}';

    protected $description = 'Delete a recruitment registration and its local snapshots, then queue a CRM tombstone';

    public function handle(RecruitmentRegistrationPrivacy $privacy): int
    {
        $registration = (string) $this->argument('registration');
        if (! preg_match('/^LGA-[1-9][0-9]*$/', $registration)) {
            $this->error('Invalid registration ID.');

            return self::FAILURE;
        }
        if (CrmRegistrationTombstone::where('registration_id', $registration)->exists()) {
            $this->info('Registration was already deleted; its existing deletion event is retained.');

            return self::SUCCESS;
        }
        $lead = RecruitmentLead::where('source', RecruitmentLead::SOURCE)->find(substr($registration, 4));
        if (! $lead) {
            $this->error('Recruitment registration not found.');

            return self::FAILURE;
        }
        if (! $this->option('force') && ! $this->confirm('Permanently delete '.$registration.', its payment records, and CRM snapshots?')) {
            $this->info('Deletion cancelled.');

            return self::SUCCESS;
        }
        $privacy->delete($lead);
        $this->info('Registration deleted; CRM deletion event queued. This does not cancel or refund a gateway payment.');

        return self::SUCCESS;
    }
}
