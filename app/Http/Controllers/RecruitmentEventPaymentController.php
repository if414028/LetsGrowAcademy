<?php

namespace App\Http\Controllers;

use App\Models\RecruitmentLead;
use App\Services\CrmWhatsappLink;
use App\Services\GatewayPricing;
use App\Services\GrowAdTracking;
use App\Services\RecruitmentEventCheckout;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class RecruitmentEventPaymentController extends Controller
{
    public function show(Request $request, string $slug, string $checkout, GatewayPricing $pricing)
    {
        $lead = $this->lead($slug, $checkout);
        if ($lead->event_payment_status === 'paid') {
            return redirect()->route('subscriber-recruitment.thank-you', [$slug, $checkout]);
        }

        $payment = $lead->eventPayments()->when($lead->event_payment_status === 'refunded', fn ($query) => $query->where('status', 'refunded'))->latest('id')->first();
        $locked = $payment?->status === 'pending' || $lead->event_payment_status === 'refunded';
        $classAmount = (int) ($payment?->class_amount ?? config('recruitment.event_payment.class_amount'));
        $paymentMethods = $pricing->paymentMethods();
        $quotes = [];
        if (! $locked) {
            foreach ($paymentMethods as $method => $details) {
                $quotes[$method] = $pricing->quote($classAmount, $method);
            }
        }
        $oldMethod = $request->old('payment_method');
        $selectedMethod = $locked ? $payment?->selected_payment_method
            : (is_string($oldMethod) && isset($quotes[$oldMethod]) ? $oldMethod : array_key_first($quotes));
        $quote = $locked ? $payment?->pricing_details : ($quotes[$selectedMethod] ?? null);
        $additionalFee = $locked ? (int) ($payment?->service_fee ?? 0)
            : ($quote ? $quote['transaction_fee'] + $quote['gateway_vat_amount'] : 0);
        $checkoutConfig = [
            'quotes' => $quotes,
            'selectedMethod' => $selectedMethod,
            'locked' => $locked,
            'lockedQuote' => $locked ? $quote : null,
            'classAmount' => $classAmount,
            'additionalFee' => $additionalFee,
            'total' => $classAmount + $additionalFee,
            'snapToken' => $payment?->status === 'pending' ? $payment->snap_token : null,
            'snapUiMode' => $payment?->pricing_details['snap_ui_mode'] ?? 'auto',
            'autoOpen' => $payment?->snap_token && $request->session()->get('event_snap_payment_id') === $payment->id,
            'statusUrl' => route('subscriber-recruitment.payment.return', [$slug, $checkout]),
        ];

        return response()->view('subscriber-recruitment.payment', [
            'lead' => $lead,
            'payment' => $payment,
            'classAmount' => $classAmount,
            'additionalFee' => $additionalFee,
            'paymentMethods' => $paymentMethods,
            'selectedMethod' => $selectedMethod,
            'quote' => $quote,
            'locked' => $locked,
            'checkoutConfig' => $checkoutConfig,
            'crmWhatsappUrl' => app(CrmWhatsappLink::class)->forRegistration($lead),
        ])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function pay(Request $request, string $slug, string $checkout, RecruitmentEventCheckout $payments)
    {
        $lead = $this->lead($slug, $checkout);
        try {
            $payment = $payments->start($lead, $request->input('payment_method'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('subscriber-recruitment.payment.show', [$slug, $checkout], 303)
                ->with('payment_error', 'Pembayaran belum bisa dibuka. Silakan coba lagi.');
        }

        if ($payment?->snap_token) {
            return redirect()->route('subscriber-recruitment.payment.show', [$slug, $checkout], 303)
                ->with('event_snap_payment_id', $payment->id);
        }

        return $payment
            ? redirect()->away($payment->checkout_url, 303)->header('Referrer-Policy', 'no-referrer')
            : redirect()->route('subscriber-recruitment.payment.show', [$slug, $checkout], 303);
    }

    public function refresh(string $slug, string $checkout, RecruitmentEventCheckout $payments)
    {
        $lead = $this->lead($slug, $checkout);
        $payment = $lead->eventPayments()->latest('id')->first();
        if ($payment?->status === 'pending') {
            try {
                $payments->sync($payment);
            } catch (Throwable) {
                return redirect()->route('subscriber-recruitment.payment.show', [$slug, $checkout])
                    ->with('payment_error', 'Status pembayaran belum dapat diperiksa. Silakan cek lagi dalam beberapa saat.');
            }
        }

        return redirect()->route(
            $lead->refresh()->event_payment_status === 'paid' ? 'subscriber-recruitment.thank-you' : 'subscriber-recruitment.payment.show',
            [$slug, $checkout],
        );
    }

    public function thankYou(string $slug, GrowAdTracking $tracking, ?string $checkout = null)
    {
        abort_unless($checkout, 404);
        $lead = $this->lead($slug, $checkout);
        if ($lead->event_payment_status !== 'paid') {
            return redirect()->route('subscriber-recruitment.payment.show', [$slug, $checkout]);
        }

        $pixelId = $tracking->pixelId($lead->growAdPlacement, conversion: true);
        $sendPixel = $pixelId && RecruitmentLead::whereKey($lead->id)
            ->where('event_payment_status', 'paid')->whereNull('registration_pixel_sent_at')
            ->update(['registration_pixel_sent_at' => now()]);
        $payment = $sendPixel ? $lead->eventPayments()->where('status', 'paid')->latest('id')->first() : null;

        return response()->view('subscriber-recruitment.thank-you', [
            'lead' => $lead,
            'crmWhatsappUrl' => app(CrmWhatsappLink::class)->forRegistration($lead),
            'growAdPixelId' => $sendPixel ? $pixelId : null,
            'purchase' => $payment ? ['value' => $payment->total(), 'currency' => 'IDR'] : null,
        ])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    private function lead(string $slug, string $checkout): RecruitmentLead
    {
        return RecruitmentLead::where('event_checkout_id', $checkout)
            ->where('source_slug', $slug)
            ->where('source', RecruitmentLead::SOURCE)
            ->firstOrFail();
    }
}
