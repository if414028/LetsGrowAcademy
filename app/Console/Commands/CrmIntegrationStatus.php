<?php

namespace App\Console\Commands;

use App\Models\CrmRegistrationTombstone;
use App\Models\CrmWebhookDelivery;
use App\Models\RecruitmentLead;
use App\Services\CrmWhatsappLink;
use App\Services\RecruitmentEventDetails;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class CrmIntegrationStatus extends Command
{
    protected $signature = 'crm:integration-status';

    protected $description = 'Show CRM configuration readiness and historical counts without exposing secrets or participant data';

    public function handle(RecruitmentEventDetails $details): int
    {
        $ready = Schema::hasTable('crm_registration_tombstones')
            && Schema::hasColumns('recruitment_event_payments', ['selected_payment_method', 'pricing_details', 'snap_token']);
        try {
            $event = $details->snapshot();
            $eventError = null;
        } catch (RuntimeException $exception) {
            $event = null;
            $eventError = $exception->getMessage();
        }
        $counts = $ready ? RecruitmentLead::where('source', RecruitmentLead::SOURCE)
            ->selectRaw('event_payment_status, count(*) as total')->groupBy('event_payment_status')->pluck('total', 'event_payment_status')->all() : null;
        $result = [
            'schema_version' => config('crm.schema_version'),
            'environment' => config('crm.environment'),
            'base_url' => rtrim(config('app.url'), '/').'/api/v1/crm',
            'migration_ready' => $ready,
            'api_token_configured' => strlen((string) config('crm.api_token')) >= 32,
            'webhook_secret_configured' => strlen((string) config('crm.webhook_secret')) >= 32,
            'registration_endpoint_configured' => (bool) config('crm.registration_webhook_url'),
            'payment_endpoint_configured' => (bool) config('crm.payment_webhook_url'),
            'webhooks_enabled' => (bool) config('crm.webhooks_enabled'),
            'whatsapp_number_configured' => app(CrmWhatsappLink::class)->number() !== null,
            'midtrans_mode' => config('midtrans.is_production') ? 'production' : 'sandbox',
            'event' => $event,
            'event_configuration_error' => $eventError,
            'historical_registrations' => $counts === null ? null : array_sum($counts),
            'registrations_by_payment_status' => $counts,
            'pending_webhooks' => $ready ? CrmWebhookDelivery::whereNull('delivered_at')->count() : null,
            'deletion_records' => $ready ? CrmRegistrationTombstone::count() : null,
        ];
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return $ready && $eventError === null ? self::SUCCESS : self::FAILURE;
    }
}
