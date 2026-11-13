<?php

namespace App\Console\Commands;

use App\Models\CrmWebhookDelivery;
use App\Services\CrmWebhookSender;
use Illuminate\Console\Command;
use RuntimeException;

class DeliverCrmWebhooks extends Command
{
    protected $signature = 'crm:deliver-webhooks {--limit=100 : Maximum events to attempt (1-1000)}';

    protected $description = 'Deliver due CRM webhooks and retry unsuccessful deliveries';

    public function handle(CrmWebhookSender $sender): int
    {
        if (! config('crm.webhooks_enabled')) {
            $this->info('CRM webhook delivery is disabled; events remain in the outbox.');

            return self::SUCCESS;
        }

        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
        if ($limit === false) {
            $this->error('--limit must be between 1 and 1000.');

            return self::FAILURE;
        }
        try {
            $sender->validateConfiguration();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $deliveries = CrmWebhookDelivery::whereNull('delivered_at')->where('next_attempt_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('locked_until')->orWhere('locked_until', '<=', now()))
            ->orderBy('id')->limit($limit)->get();
        foreach ($deliveries as $delivery) {
            $sender->send($delivery);
        }

        $failed = $deliveries->isNotEmpty()
            && CrmWebhookDelivery::whereIn('id', $deliveries->modelKeys())->whereNull('delivered_at')->exists();
        $this->info('Processed '.$deliveries->count().' CRM webhook events.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
