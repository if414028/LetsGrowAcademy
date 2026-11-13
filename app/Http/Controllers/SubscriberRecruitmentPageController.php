<?php

namespace App\Http\Controllers;

use App\Models\RecruitmentLead;
use App\Models\User;
use App\Services\CrmWebhookOutbox;
use App\Services\GrowAdTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SubscriberRecruitmentPageController extends Controller
{
    public function index(Request $request)
    {
        $consultant = $request->user();

        return view('subscriber-recruitment.index', [
            'consultant' => $consultant,
            'landingPageUrl' => route('subscriber-recruitment.show', $consultant->landing_page_slug),
            'canPublish' => (bool) $consultant->whatsapp_url,
            'leadCount' => $consultant->recruitmentLeads()->where('source', RecruitmentLead::SOURCE)->count(),
        ]);
    }

    public function leads(Request $request)
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $search = trim($validated['q'] ?? '');
        $consultant = $request->user();

        // The authenticated owner scopes every query, including grouped search conditions.
        $query = $consultant->recruitmentLeads()->where('source', RecruitmentLead::SOURCE);
        $leadCount = (clone $query)->count();
        $phoneSearch = preg_match('/^[+\d\s()\-]+$/', $search)
            ? RecruitmentLead::normalizePhoneNumber($search)
            : $search;

        $leads = $query->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
            ->where('full_name', 'like', '%'.$search.'%')
            ->orWhere('phone_number', 'like', '%'.$phoneSearch.'%')
            ->orWhere('domicile', 'like', '%'.$search.'%')))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return response()->view('subscriber-recruitment.leads', [
            'consultant' => $consultant,
            'leads' => $leads,
            'leadCount' => $leadCount,
            'search' => $search,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, string $slug, GrowAdTracking $tracking)
    {
        $consultant = $this->consultant($slug);
        $consultantName = $consultant->full_name ?: $consultant->name;
        $placement = $tracking->placementForPage($request, $consultant);
        $oldToken = $request->old('grow_ad_token');
        $token = $placement ? $tracking->tokenForPlacement($placement) : null;
        if (! $placement && is_string($oldToken)) {
            $placement = $tracking->placementFromToken($oldToken, $consultant);
            $token = $placement ? $oldToken : null;
        }

        return view('subscriber-recruitment.show', [
            'consultant' => $consultant,
            'consultantName' => $consultantName,
            'consultantRole' => $consultant->getRoleNames()
                ->first(fn ($role) => in_array($role, ['Sales Manager', 'Health Manager', 'Health Planner']))
                ?: 'Pendamping Kemitraan',
            'content' => config('recruitment'),
            'growAdToken' => $token,
            'growAdPixelId' => $tracking->pixelId($placement),
        ]);
    }

    public function trackFormClick(Request $request, string $slug, GrowAdTracking $tracking)
    {
        $data = $request->validate(['grow_ad_token' => ['required', 'string', 'max:2048']]);
        $placement = $tracking->placementFromToken($data['grow_ad_token'], $this->consultant($slug));
        if ($tracking->pixelId($placement)) {
            $placement->increment('form_cta_clicks');
        }

        return response()->noContent();
    }

    public function submit(Request $request, string $slug, GrowAdTracking $tracking, CrmWebhookOutbox $crm)
    {
        // Resolve the owner from the public URL, never from a visitor-supplied user ID or phone.
        $consultant = $this->consultant($slug);
        $data = $request->only(['full_name', 'phone_number', 'domicile', 'activity', 'interest', 'consent', 'whatsapp_consent']);
        if (is_string($data['phone_number'] ?? null)) {
            $data['phone_number'] = str_replace([' ', '-', '(', ')'], '', $data['phone_number']);
        }

        $validator = Validator::make($data, [
            'full_name' => ['required', 'string', 'max:120'],
            'phone_number' => ['required', 'string', 'regex:/^(?:\+?62|0)8[1-9][0-9]{6,10}$/'],
            'domicile' => ['required', 'string', 'max:120'],
            'activity' => ['required', Rule::in(config('recruitment.activities'))],
            'interest' => ['nullable', Rule::in(config('recruitment.interests'))],
            'consent' => ['accepted'],
            'whatsapp_consent' => ['sometimes', 'boolean'],
        ], [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute maksimal :max karakter.',
            'in' => 'Pilih :attribute dari pilihan yang tersedia.',
            'phone_number.regex' => 'Gunakan nomor WhatsApp Indonesia yang valid, diawali 08 atau +628.',
            'consent.accepted' => 'Setujui penggunaan data terlebih dahulu untuk melanjutkan.',
        ], [
            'full_name' => 'Nama lengkap',
            'phone_number' => 'Nomor WhatsApp',
            'domicile' => 'Domisili',
            'activity' => 'aktivitas saat ini',
            'interest' => 'hal yang ingin diketahui',
        ]);

        if ($validator->fails()) {
            return redirect(route('subscriber-recruitment.show', $slug).'#form-minat')
                ->withErrors($validator)
                ->withInput($request->only([...array_keys($data), 'grow_ad_token']));
        }

        $validated = $validator->validated();
        $placement = $tracking->placementFromToken(is_string($request->input('grow_ad_token')) ? $request->input('grow_ad_token') : null, $consultant);

        // Save before confirming registration. The URL determines attribution, not hidden form fields.
        $lead = DB::transaction(function () use ($consultant, $validated, $placement, $crm) {
            $lead = $consultant->recruitmentLeads()->create([
                'source' => RecruitmentLead::SOURCE,
                'source_slug' => $consultant->landing_page_slug,
                'source_url' => route('subscriber-recruitment.show', $consultant->landing_page_slug),
                'full_name' => $validated['full_name'],
                'phone_number' => RecruitmentLead::normalizePhoneNumber($validated['phone_number']),
                'domicile' => $validated['domicile'],
                'activity' => $validated['activity'],
                'interest' => $validated['interest'] ?? null,
                'consented_at' => now(),
                'whatsapp_consent' => (bool) ($validated['whatsapp_consent'] ?? false),
                'whatsapp_consented_at' => ! empty($validated['whatsapp_consent']) ? now() : null,
                'whatsapp_consent_version' => config('recruitment.form.whatsapp_consent_version'),
                'grow_ad_placement_id' => $placement?->id,
                'event_checkout_id' => (string) Str::uuid(),
                'event_payment_status' => 'awaiting_payment',
            ]);
            $crm->record($lead, 'registration.created');

            return $lead;
        });

        return redirect()->route('subscriber-recruitment.payment.show', [$slug, $lead->event_checkout_id], 303);
    }

    private function consultant(string $slug): User
    {
        $consultant = User::query()
            ->where('landing_page_slug', $slug)
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'Inactive'))
            ->whereHas('subscriptions', fn ($query) => $query
                ->where('status', 'active')
                ->where('ends_at', '>', now()))
            ->firstOrFail();

        abort_unless($consultant->whatsapp_url, 404);

        return $consultant;
    }
}
