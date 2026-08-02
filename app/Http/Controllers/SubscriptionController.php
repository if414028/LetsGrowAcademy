<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
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
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $user = $request->user();

        if ($user->pendingSubscription()) {
            return back()->with('error', 'Permintaan subscription kamu masih menunggu pengecekan admin.');
        }

        $months = (int) $validated['duration_months'];
        $paymentProof = $request->file('payment_proof')->store('subscriptions/payment-proofs', 'public');

        Subscription::create([
            'user_id' => $user->id,
            'duration_months' => $months,
            'amount' => $months * (int) config('subscription.monthly_price'),
            'payment_proof' => $paymentProof,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        return redirect()
            ->route('subscriptions.index')
            ->with('success', 'Konfirmasi transfer terkirim. Admin akan memeriksa pembayaran kamu.');
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

    public function approve(Request $request, Subscription $subscription)
    {
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
