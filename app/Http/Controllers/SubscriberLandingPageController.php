<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class SubscriberLandingPageController extends Controller
{
    public function index(Request $request)
    {
        $consultant = $request->user();

        return view('subscriber-landing.index', [
            'consultant' => $consultant,
            'landingPageUrl' => route('subscriber-landing.show', $consultant->landing_page_slug),
            'canPublish' => (bool) $consultant->whatsapp_url,
        ]);
    }

    public function show(string $slug)
    {
        $consultant = User::query()
            ->where('landing_page_slug', $slug)
            ->whereHas('subscriptions', fn ($query) => $query
                ->where('status', 'active')
                ->where('ends_at', '>', now()))
            ->firstOrFail();

        abort_unless($consultant->whatsapp_url, 404);

        $message = 'Halo '.($consultant->full_name ?: $consultant->name).', saya ingin konsultasi produk Coway.';

        return view('subscriber-landing.show', [
            'consultant' => $consultant,
            'whatsappUrl' => $consultant->whatsapp_url.'?text='.rawurlencode($message),
        ]);
    }
}
