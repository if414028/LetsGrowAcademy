<?php

namespace App\Services;

use InvalidArgumentException;
use LogicException;

class SubscriptionPricing
{
    public function paymentMethods(): array
    {
        return array_intersect_key(
            config('subscription.payment_methods', []),
            array_flip(config('subscription.enabled_payment_methods', [])),
        );
    }

    public function quote(int $months, string $paymentMethod): array
    {
        $method = $this->paymentMethods()[$paymentMethod] ?? null;

        if (! $method || ! in_array($months, config('subscription.durations'), true)) {
            throw new InvalidArgumentException('Invalid subscription duration or payment method.');
        }

        $subtotal = $months * (int) config('subscription.monthly_price');
        $fixedFee = (int) $method['fixed_fee'];
        $basisPoints = (int) $method['fee_basis_points'];
        $vatPercent = (int) config('subscription.gateway_vat_percent');

        if ($subtotal < 1 || $fixedFee < 0 || $basisPoints < 0 || $vatPercent < 0 || $vatPercent > 100
            || $basisPoints * (100 + $vatPercent) >= 1000000) {
            throw new LogicException('Invalid subscription pricing configuration.');
        }

        // QRIS MDR and its VAT are always absorbed by the merchant.
        $merchantCoversFee = $paymentMethod === 'gopay_qris';
        $total = $subtotal;
        $fee = $vat = 0;

        if (! $merchantCoversFee) {
            // Midtrans deducts percentage fees from the final gross amount, including
            // fees. Converge to the smallest whole-rupiah total covering that deduction.
            do {
                $gross = $total;
                $fee = $fixedFee + (int) ceil($gross * $basisPoints / 10000);
                $vat = (int) ceil($fee * $vatPercent / 100);
                $total = $subtotal + $fee + $vat;
            } while ($total !== $gross);
        }

        $estimatedGatewayFee = $merchantCoversFee ? $fixedFee + (int) ceil($total * $basisPoints / 10000) : $fee;
        $estimatedGatewayVat = $merchantCoversFee ? (int) ceil($estimatedGatewayFee * $vatPercent / 100) : $vat;

        return [
            'payment_method' => $paymentMethod,
            'payment_method_label' => $method['label'],
            'snap_payment_method' => $method['snap_payment_method'] ?? $paymentMethod,
            'snap_ui_mode' => $method['snap_ui_mode'] ?? 'auto',
            'subtotal' => $subtotal,
            'fixed_fee' => $fixedFee,
            'fee_basis_points' => $basisPoints,
            'transaction_fee' => $fee,
            'gateway_vat_percent' => $vatPercent,
            'gateway_vat_amount' => $vat,
            'merchant_covers_fee' => $merchantCoversFee,
            'estimated_gateway_fee' => $estimatedGatewayFee,
            'estimated_gateway_vat_amount' => $estimatedGatewayVat,
            'total' => $total,
        ];
    }
}
