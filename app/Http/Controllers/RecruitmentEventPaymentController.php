<?php

namespace App\Http\Controllers;

use App\Models\RecruitmentLead;
use App\Services\GrowAdTracking;
use App\Services\RecruitmentEventCheckout;
use Throwable;

class RecruitmentEventPaymentController extends Controller
{
    public function show(string $slug, string $checkout)
    {
        $lead = $this->lead($slug, $checkout);
        if ($lead->event_payment_status === 'paid') {
            return redirect()->route('subscriber-recruitment.thank-you', [$slug, $checkout]);
        }

        $payment = $lead->eventPayments()->latest('id')->first();
        $price = $payment?->only(['class_amount', 'service_fee']) ?? config('recruitment.event_payment');

        return response()->view('subscriber-recruitment.payment', [
            'lead' => $lead,
            'payment' => $payment,
            'price' => $price,
        ])->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function pay(string $slug, string $checkout, RecruitmentEventCheckout $payments)
    {
        $lead = $this->lead($slug, $checkout);
        try {
            $payment = $payments->start($lead);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('subscriber-recruitment.payment.show', [$slug, $checkout], 303)
                ->with('payment_error', 'Pembayaran belum bisa dibuka. Silakan coba lagi.');
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
