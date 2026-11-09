<?php

namespace App\Http\Controllers;

use App\Models\GrowAdCampaign;
use App\Models\GrowAdContent;
use App\Models\GrowAdPlacement;
use App\Models\GrowAdSetting;
use App\Models\RecruitmentLead;
use App\Services\GrowAdPaymentUpdater;
use App\Services\GrowAdPricing;
use App\Services\MidtransService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class GrowAdController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $activated = GrowAdSetting::where('user_id', $user->id)->exists();

        return view('grow-ads.index', [
            'activated' => $activated,
            'campaigns' => $activated ? GrowAdCampaign::where('user_id', $user->id)->with('placements')->latest()->paginate(10) : null,
        ]);
    }

    public function activate(Request $request)
    {
        GrowAdSetting::firstOrCreate(['user_id' => $request->user()->id], ['activated_at' => now()]);

        return redirect()->route('grow-ads.create');
    }

    public function create(Request $request)
    {
        abort_unless(GrowAdSetting::where('user_id', $request->user()->id)->exists(), 403);
        $draft = GrowAdCampaign::where('user_id', $request->user()->id)->where('status', 'draft')->with('placements')->first();
        $contents = GrowAdContent::where('is_active', true)->newestFirst()->get();
        $reservedIds = GrowAdPlacement::reservedByAnotherUser($request->user()->id)
            ->whereIn('content_id', $contents->modelKeys())->pluck('content_id')->all();

        return view('grow-ads.select', [
            'contents' => $contents,
            'reservedIds' => $reservedIds,
            'selectedIds' => $draft?->placements->pluck('content_id')->all() ?? [],
        ]);
    }

    public function select(Request $request)
    {
        $data = $request->validate([
            'content_ids' => ['required', 'array', 'min:1', 'max:3'],
            'content_ids.*' => ['required', 'integer', 'distinct', Rule::exists('grow_ad_contents', 'id')->where('is_active', true)],
        ], ['content_ids.required' => 'Pilih minimal satu konten.', 'content_ids.max' => 'Pilih maksimal 3 konten.']);

        $campaign = DB::transaction(function () use ($request, $data) {
            GrowAdSetting::where('user_id', $request->user()->id)->lockForUpdate()->firstOrFail();
            $pending = GrowAdCampaign::where('user_id', $request->user()->id)->where('status', 'awaiting_payment')->first();
            if ($pending) {
                return $pending;
            }
            $contents = $this->lockAvailableContents($data['content_ids'], $request->user()->id);
            $campaign = GrowAdCampaign::firstOrCreate(['user_id' => $request->user()->id, 'status' => 'draft']);
            $campaign->placements()->delete();
            foreach ($contents as $content) {
                $campaign->placements()->create($content->only(['title', 'description', 'media_path', 'media_type']) + ['content_id' => $content->id]);
            }
            $campaign->touch();

            return $campaign;
        }, 3);

        return redirect()->route($campaign->status === 'draft' ? 'grow-ads.budget' : 'grow-ads.show', $campaign);
    }

    public function budget(Request $request, GrowAdCampaign $campaign, GrowAdPricing $pricing)
    {
        $this->authorizeOwner($request, $campaign);
        if ($campaign->status !== 'draft') {
            return redirect()->route('grow-ads.show', $campaign);
        }

        return view('grow-ads.budget', [
            'campaign' => $campaign->load('placements'),
            'quote' => $pricing->quote($campaign->daily_budget ?? 50000, $campaign->duration_days ?? 7),
        ]);
    }

    public function checkout(Request $request, GrowAdCampaign $campaign, GrowAdPricing $pricing, MidtransService $midtrans)
    {
        $this->authorizeOwner($request, $campaign);
        $data = $request->validate([
            'daily_budget' => ['required', 'integer', 'min:'.config('grow-ads.min_daily_budget'), 'max:'.config('grow-ads.max_daily_budget')],
            'duration_days' => ['required', 'integer', 'min:1', 'max:'.config('grow-ads.max_duration_days')],
        ]);
        if (! config('midtrans.server_key') || ! config('midtrans.client_key')) {
            return back()->withInput()->with('error', 'Pembayaran belum tersedia. Hubungi Admin untuk konfigurasi Midtrans.');
        }
        if (! $request->user()->whatsapp_url) {
            return back()->withInput()->with('error', 'Lengkapi nomor WhatsApp di profil agar landing page Anda dapat digunakan.');
        }

        try {
            DB::transaction(function () use ($campaign, $data, $pricing, $midtrans) {
                GrowAdSetting::where('user_id', $campaign->user_id)->lockForUpdate()->firstOrFail();
                if ($campaign->refresh()->status !== 'draft') {
                    return;
                }
                $ids = $campaign->placements()->pluck('content_id')->all();
                abort_unless(count($ids) >= 1 && count($ids) <= 3, 422, 'Kembali dan pilih 1–3 konten.');
                $this->lockAvailableContents($ids, $campaign->user_id);
                $locked = GrowAdCampaign::lockForUpdate()->findOrFail($campaign->id);
                $locked->fill($pricing->quote((int) $data['daily_budget'], (int) $data['duration_days']));
                $locked->midtrans_order_id = 'GADS-'.$locked->uuid;
                $payload = [
                    'transaction_details' => ['order_id' => $locked->midtrans_order_id, 'gross_amount' => (int) $locked->total_amount],
                    'item_details' => [
                        ['id' => 'grow-ads-budget', 'price' => (int) $locked->ad_budget, 'quantity' => 1, 'name' => 'Budget iklan '.$locked->duration_days.' hari'],
                        ['id' => 'grow-ads-vat', 'price' => (int) $locked->vat_amount, 'quantity' => 1, 'name' => 'PPN '.$locked->vat_percent.'%'],
                        ['id' => 'grow-ads-service', 'price' => (int) $locked->service_fee, 'quantity' => 1, 'name' => 'Biaya layanan aplikasi'],
                    ],
                    'customer_details' => ['first_name' => $locked->user->name, 'email' => $locked->user->email, 'phone' => $locked->user->phone_number],
                    'callbacks' => ['finish' => route('grow-ads.payment-finish')],
                ];
                $locked->snap_token = $midtrans->createSnapToken($payload);
                $locked->status = 'awaiting_payment';
                $locked->save();
            });
        } catch (ValidationException $exception) {
            return redirect()->route('grow-ads.create')->withErrors($exception->errors());
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            Log::warning('Grow Ads checkout unavailable.', ['campaign_id' => $campaign->id, 'message' => $exception->getMessage()]);

            return back()->withInput()->with('error', 'Pembayaran belum dapat dibuat. Pilihan Anda tersimpan, silakan coba lagi.');
        }

        return redirect()->route('grow-ads.show', $campaign);
    }

    public function show(Request $request, GrowAdCampaign $campaign)
    {
        $this->authorizeOwner($request, $campaign);
        if ($campaign->status === 'draft') {
            return redirect()->route('grow-ads.budget', $campaign);
        }
        $campaign->load(['user', 'placements' => fn ($query) => $query->withCount([
            'leads' => fn ($leads) => $leads->registeredForEvent(),
        ])]);
        $leads = RecruitmentLead::visibleInGrowAdCampaign($campaign);
        $adLeads = (clone $leads)->whereNotNull('grow_ad_placement_id');
        $registeredCount = (clone $adLeads)->registeredForEvent()->count();
        $paidLeads = (clone $adLeads)->where('event_payment_status', 'paid');
        $paidCount = (clone $paidLeads)->count();

        return response()->view('grow-ads.show', [
            'campaign' => $campaign, 'registeredCount' => $registeredCount, 'paidCount' => $paidCount,
            'funnel' => [
                'AddPaymentInfo' => $campaign->placements->sum('form_cta_clicks'),
                'AddToCart' => $adLeads->whereNotNull('event_checkout_id')->count(),
                'Purchase' => $paidLeads->whereHas('eventPayments', fn ($payments) => $payments->where('status', 'paid'))->count(),
            ],
            'leads' => $leads->latest('id')->paginate(15),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function syncPayment(Request $request, GrowAdCampaign $campaign, MidtransService $midtrans, GrowAdPaymentUpdater $updater)
    {
        $this->authorizeOwner($request, $campaign);
        abort_unless($campaign->midtrans_order_id, 422);

        return $this->sync($campaign, $midtrans, $updater);
    }

    public function finishPayment(Request $request, MidtransService $midtrans, GrowAdPaymentUpdater $updater)
    {
        $campaign = GrowAdCampaign::where('user_id', $request->user()->id)
            ->where('midtrans_order_id', $request->string('order_id')->toString())->firstOrFail();

        return $this->sync($campaign, $midtrans, $updater);
    }

    private function sync(GrowAdCampaign $campaign, MidtransService $midtrans, GrowAdPaymentUpdater $updater)
    {
        try {
            $updater->update($campaign, $midtrans->transactionStatus($campaign->midtrans_order_id));
        } catch (Throwable $exception) {
            Log::warning('Grow Ads payment verification failed.', ['campaign_id' => $campaign->id, 'message' => $exception->getMessage()]);

            return redirect()->route('grow-ads.show', $campaign)->with('error', 'Status pembayaran belum dapat diverifikasi. Coba periksa lagi sebentar.');
        }

        return redirect()->route('grow-ads.show', $campaign)->with('success', $campaign->fresh()->payment_status === 'paid'
            ? 'Pembayaran terkonfirmasi. Admin akan menyiapkan iklan Anda.' : 'Status pembayaran sudah diperbarui.');
    }

    private function authorizeOwner(Request $request, GrowAdCampaign $campaign): void
    {
        abort_unless($campaign->user_id === $request->user()->id, 403);
    }

    private function lockAvailableContents(array $ids, int $userId): Collection
    {
        // Lock in the same order for every request, then read current reservations.
        $contents = GrowAdContent::whereIn('id', $ids)->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
        $reserved = GrowAdPlacement::reservedByAnotherUser($userId)->whereIn('content_id', $ids)
            ->lockForUpdate()->pluck('content_id');

        if ($contents->count() !== count($ids) || $reserved->isNotEmpty()) {
            throw ValidationException::withMessages([
                'content_ids' => 'Sebagian konten sudah dipilih pengguna lain atau tidak tersedia. Pilih konten lain.',
            ]);
        }

        return $contents;
    }
}
