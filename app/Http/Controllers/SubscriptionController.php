<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\MidtransPaymentUpdater;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly MidtransService $midtrans,
        private readonly MidtransPaymentUpdater $paymentUpdater,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        return view('subscriptions.index', [
            'activeSubscription' => $user->activeSubscription(),
            'pendingSubscription' => $user->pendingSubscription(),
            'latestSubscription' => $user->subscriptions()->latest()->first(),
            'durations' => config('subscription.durations'),
            'monthlyPrice' => config('subscription.monthly_price'),
            'bank' => config('subscription.bank'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'duration_months' => [
                'required',
                'integer',
                Rule::in(config('subscription.durations')),
            ],
        ]);

        $user = $request->user();

        if (! config('midtrans.server_key') || ! config('midtrans.client_key')) {
            return back()->with('error', 'Pembayaran Midtrans belum dikonfigurasi. Hubungi administrator.');
        }

        if ($pending = $user->pendingSubscription()) {
            if ($pending->snap_token) {
                return back()->with('snap_token', $pending->snap_token);
            }

            return back()->with('error', 'Pembayaran sebelumnya masih menunggu penyelesaian.');
        }

        $months = (int) $validated['duration_months'];
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'duration_months' => $months,
            'amount' => $months * (int) config('subscription.monthly_price'),
            'payment_status' => 'pending',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $orderId = 'SUB-'.$subscription->id.'-'.str()->uuid();

        try {
            $snapToken = $this->midtrans->createSnapToken([
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) $subscription->amount,
                ],
                'item_details' => [[
                    'id' => 'subscription-'.$months.'m',
                    'price' => (int) $subscription->amount,
                    'quantity' => 1,
                    'name' => "Subscription {$months} bulan",
                ]],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone_number,
                ],
                'callbacks' => [
                    'finish' => config('midtrans.finish_url'),
                ],
            ]);
        } catch (Throwable $exception) {
            $subscription->delete();
            Log::error('Failed to create Midtrans Snap token.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('error', 'Checkout belum dapat dibuat. Silakan coba lagi.');
        }

        $subscription->update([
            'midtrans_order_id' => $orderId,
            'snap_token' => $snapToken,
        ]);

        return redirect()->route('subscriptions.index')->with('snap_token', $snapToken);
    }

    public function adminIndex(Request $request)
    {
        $status = $request->string('status')->toString();

        $subscriptions = Subscription::query()
            ->with(['user.roles', 'reviewer'])
            ->when(in_array($status, ['pending', 'active', 'rejected'], true), fn ($query) => $query->where('status', $status))
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('subscriptions.admin-index', compact('subscriptions', 'status'));
    }

    public function syncPayment(Request $request, Subscription $subscription): JsonResponse
    {
        abort_unless($subscription->user_id === $request->user()->id, 403);
        abort_unless($subscription->midtrans_order_id, 422, 'This is not a Midtrans payment.');

        try {
            $status = $this->midtrans->transactionStatus($subscription->midtrans_order_id);
            $grossAmount = (int) round((float) ($status->gross_amount ?? 0));
            abort_unless($grossAmount === (int) $subscription->amount, 422, 'Payment amount does not match.');

            $subscription = $this->paymentUpdater->update($subscription, $status);
        } catch (Throwable $exception) {
            Log::warning('Unable to sync Midtrans transaction status.', [
                'subscription_id' => $subscription->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Status pembayaran belum dapat diperbarui.'], 503);
        }

        if ($subscription->payment_status === 'paid') {
            $request->session()->flash('payment_success', true);
        }

        return response()->json([
            'payment_status' => $subscription->payment_status,
            'subscription_status' => $subscription->status,
        ]);
    }

    public function finishPayment(Request $request)
    {
        $orderId = $request->string('order_id')->toString();
        $subscription = $request->user()->subscriptions()
            ->where('midtrans_order_id', $orderId)
            ->first();

        if (! $subscription) {
            return redirect()->route('subscriptions.index')
                ->with('error', 'Transaksi pembayaran tidak ditemukan.');
        }

        try {
            $status = $this->midtrans->transactionStatus($orderId);
            $grossAmount = (int) round((float) ($status->gross_amount ?? 0));
            abort_unless($grossAmount === (int) $subscription->amount, 422, 'Payment amount does not match.');

            $subscription = $this->paymentUpdater->update($subscription, $status);
        } catch (Throwable $exception) {
            Log::warning('Unable to finish Midtrans payment.', [
                'subscription_id' => $subscription->id,
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('subscriptions.index')
                ->with('error', 'Status pembayaran belum dapat diperbarui. Silakan muat ulang halaman.');
        }

        $redirect = redirect()->route('subscriptions.index')->with(
            $subscription->payment_status === 'paid' ? 'success' : 'error',
            $subscription->payment_status === 'paid'
                ? 'Pembayaran berhasil. Subscription kamu sudah aktif.'
                : 'Pembayaran belum berhasil dikonfirmasi.',
        );

        if ($subscription->payment_status === 'paid') {
            $redirect->with('payment_success', true);
        }

        return $redirect;
    }

    public function approve(Request $request, Subscription $subscription)
    {
        if ($subscription->midtrans_order_id) {
            return back()->with('error', 'Pembayaran Midtrans hanya dapat diaktifkan oleh notifikasi pembayaran yang valid.');
        }

        if ($subscription->status !== 'pending') {
            return back()->with('error', 'Permintaan ini sudah diproses.');
        }

        DB::transaction(function () use ($request, $subscription) {
            $currentEnd = $subscription->user->activeSubscription()?->ends_at;
            $startsAt = $currentEnd && $currentEnd->isFuture() ? $currentEnd->copy() : now();

            $subscription->update([
                'status' => 'active',
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMonthsNoOverflow($subscription->duration_months),
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'admin_note' => $request->validate(['admin_note' => ['nullable', 'string', 'max:1000']])['admin_note'] ?? null,
            ]);
        });

        return back()->with('success', 'Pembayaran disetujui dan subscription user sudah aktif.');
    }

    public function reject(Request $request, Subscription $subscription)
    {
        if ($subscription->midtrans_order_id) {
            return back()->with('error', 'Status pembayaran Midtrans diperbarui otomatis oleh Midtrans.');
        }

        if ($subscription->status !== 'pending') {
            return back()->with('error', 'Permintaan ini sudah diproses.');
        }

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $subscription->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'admin_note' => $validated['admin_note'] ?? null,
        ]);

        return back()->with('success', 'Permintaan subscription ditolak.');
    }
}
