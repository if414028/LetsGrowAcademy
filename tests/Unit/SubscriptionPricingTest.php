<?php

namespace Tests\Unit;

use App\Services\SubscriptionPricing;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SubscriptionPricingTest extends TestCase
{
    #[DataProvider('pricingCases')]
    public function test_whole_rupiah_quotes_cover_gateway_deductions(string $method, int $months, int $fee, int $vat, int $total): void
    {
        config()->set('subscription.monthly_price', 50000);
        config()->set('subscription.gateway_vat_percent', 11);
        config()->set('subscription.enabled_payment_methods', [$method]);

        $quote = app(SubscriptionPricing::class)->quote($months, $method);
        $this->assertSame($fee, $quote['transaction_fee']);
        $this->assertSame($vat, $quote['gateway_vat_amount']);
        $this->assertSame($total, $quote['total']);
        $this->assertSame($months * 50000, $total - $fee - $vat);
    }

    public static function pricingCases(): array
    {
        return [
            'VA 3 months' => ['bni_va', 3, 4000, 440, 154440],
            'VA 6 months' => ['bni_va', 6, 4000, 440, 304440],
            'VA 12 months' => ['bni_va', 12, 4000, 440, 604440],
            'QRIS 3 months' => ['gopay_qris', 3, 0, 0, 150000],
            'QRIS 6 months' => ['gopay_qris', 6, 0, 0, 300000],
            'QRIS 12 months' => ['gopay_qris', 12, 0, 0, 600000],
            'DANA 3 months' => ['dana', 3, 2289, 252, 152541],
            'DANA 6 months' => ['dana', 6, 4577, 504, 305081],
            'DANA 12 months' => ['dana', 12, 9153, 1007, 610160],
            'OVO 3 months' => ['ovo', 3, 4223, 465, 154688],
            'OVO 6 months' => ['ovo', 6, 8446, 930, 309376],
            'OVO 12 months' => ['ovo', 12, 16892, 1859, 618751],
            'GoPay digital' => ['gopay', 6, 12558, 1382, 313940],
            'ShopeePay digital 3 months' => ['shopeepay', 3, 6279, 691, 156970],
            'ShopeePay digital 6 months' => ['shopeepay', 6, 12558, 1382, 313940],
            'ShopeePay digital 12 months' => ['shopeepay', 12, 25116, 2763, 627879],
        ];
    }

    public function test_unavailable_methods_cannot_be_quoted(): void
    {
        config()->set('subscription.enabled_payment_methods', ['gopay_qris']);
        $this->expectException(InvalidArgumentException::class);
        app(SubscriptionPricing::class)->quote(6, 'bni_va');
    }

    public function test_qris_remains_merchant_funded_when_its_fee_changes(): void
    {
        config()->set('subscription.payment_methods.gopay_qris.fee_basis_points', 100);
        $quote = app(SubscriptionPricing::class)->quote(6, 'gopay_qris');
        $this->assertTrue($quote['merchant_covers_fee']);
        $this->assertSame(300000, $quote['total']);
    }
}
