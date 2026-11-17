<?php

namespace App\Services;

use InvalidArgumentException;

class SubscriptionPricing
{
    public function __construct(private readonly GatewayPricing $gateway) {}

    public function paymentMethods(): array
    {
        return $this->gateway->paymentMethods();
    }

    public function quote(int $months, string $paymentMethod): array
    {
        if (! in_array($months, config('subscription.durations'), true)) {
            throw new InvalidArgumentException('Invalid subscription duration.');
        }

        return $this->gateway->quote($months * (int) config('subscription.monthly_price'), $paymentMethod);
    }
}
