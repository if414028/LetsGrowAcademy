<?php

namespace App\Http\Controllers;

use App\Models\GrowAdCampaign;
use App\Models\GrowAdContent;
use App\Models\RecruitmentLead;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class AdminGrowAdController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->validate(['status' => ['nullable', Rule::in(array_keys(GrowAdCampaign::STATUS_LABELS))]])['status'] ?? null;

        return view('grow-ads.admin.index', [
            'campaigns' => GrowAdCampaign::with(['user', 'placements'])->where('status', '!=', 'draft')
                ->when($status, fn ($query) => $query->where('status', $status))->latest()->paginate(15)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function contents()
    {
        return view('grow-ads.admin.contents', ['contents' => GrowAdContent::newestFirst()->paginate(12)]);
    }

    public function createContent()
    {
        return view('grow-ads.admin.content-form', ['content' => new GrowAdContent(['is_active' => true])]);
    }

    public function editContent(GrowAdContent $content)
    {
        return view('grow-ads.admin.content-form', compact('content'));
    }

    public function storeContent(Request $request)
    {
        GrowAdContent::create($this->contentData($request));

        return redirect()->route('admin.grow-ads.contents')->with('success', 'Konten berhasil ditambahkan.');
    }

    public function updateContent(Request $request, GrowAdContent $content)
    {
        $content->update($this->contentData($request, $content));

        return redirect()->route('admin.grow-ads.contents')->with('success', 'Konten diperbarui. Konten kampanye yang sudah dipilih tetap tersimpan.');
    }

    private function contentData(Request $request, ?GrowAdContent $content = null): array
    {
        $media = $request->file('media');
        $uploadError = $media instanceof UploadedFile && $media->getError() === UPLOAD_ERR_INI_SIZE
            ? 'Ukuran file melebihi batas unggah server ('.ini_get('upload_max_filesize').'). Pilih file yang lebih kecil.'
            : 'File gagal diterima server. Pilih kembali file dan coba simpan lagi.';

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:3000'],
            'is_active' => ['required', 'boolean'],
            'media' => [$content ? 'nullable' : 'required', File::types(['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mov'])->max(30 * 1024)],
        ], ['media.uploaded' => $uploadError]);
        unset($data['media']);
        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $data['media_path'] = $file->store('grow-ads', 'public');
            $data['media_type'] = str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image';
        }

        return $data;
    }

    public function show(GrowAdCampaign $campaign)
    {
        $campaign->load(['user', 'placements']);

        return response()->view('grow-ads.admin.show', [
            'campaign' => $campaign,
            'leads' => RecruitmentLead::visibleInGrowAdCampaign($campaign)->latest('id')->paginate(15),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, GrowAdCampaign $campaign)
    {
        abort_unless($campaign->payment_status === 'paid', 422, 'Pembayaran harus terkonfirmasi sebelum iklan disiapkan.');
        $data = $request->validate([
            'status' => ['required', Rule::in(['preparing', 'active', 'completed'])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'placements' => ['required', 'array', 'size:'.$campaign->placements()->count()],
            'placements.*.id' => ['required', 'integer', 'distinct', Rule::exists('grow_ad_placements', 'id')->where('campaign_id', $campaign->id)],
            'placements.*.meta_ad_id' => ['nullable', 'string', 'regex:/^\d{5,40}$/'],
            'placements.*.pixel_id' => ['nullable', 'string', 'regex:/^[0-9]{5,30}$/'],
            'placements.*.impressions' => ['nullable', 'integer', 'min:0', 'max:1000000000000'],
            'placements.*.clicks' => ['nullable', 'integer', 'min:0', 'max:1000000000000'],
        ], [
            'placements.*.pixel_id.regex' => 'Meta Pixel ID harus berisi 5–30 digit angka.',
        ]);

        DB::transaction(function () use ($data, $campaign) {
            $locked = GrowAdCampaign::lockForUpdate()->findOrFail($campaign->id);
            abort_unless($locked->payment_status === 'paid', 422);
            $transitions = ['preparing' => ['preparing', 'active'], 'active' => ['active', 'completed'], 'completed' => ['completed']];
            if (! in_array($data['status'], $transitions[$locked->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Perubahan status tidak tersedia. Muat ulang halaman untuk melihat status terbaru.']);
            }
            $hasMetrics = false;
            foreach ($data['placements'] as $index => $row) {
                $row += ['impressions' => null, 'clicks' => null, 'meta_ad_id' => null];
                if (($row['impressions'] === null) !== ($row['clicks'] === null)) {
                    throw ValidationException::withMessages(['placements' => 'Isi tayangan dan klik bersama-sama, atau kosongkan keduanya.']);
                }
                if (($row['clicks'] ?? 0) > ($row['impressions'] ?? 0)) {
                    throw ValidationException::withMessages(['placements' => 'Jumlah klik tidak boleh melebihi tayangan.']);
                }
                $placement = $locked->placements()->findOrFail($row['id']);
                $placement->fill(collect($row)->only(['meta_ad_id', 'pixel_id', 'impressions', 'clicks'])->all());
                if ($data['status'] === 'active' && (! $placement->pixel_id || ! $placement->meta_ad_id)) {
                    throw ValidationException::withMessages([
                        "placements.$index.pixel_id" => 'Isi Meta Ad ID dan Meta Pixel ID untuk iklan “'.$placement->title.'” sebelum mulai tayang.',
                    ]);
                }
                $hasMetrics = $hasMetrics || $placement->isDirty(['impressions', 'clicks']);
                $placement->save();
            }
            if ($data['status'] === 'active') {
                if (! $locked->user->hasActiveSubscription() || ! $locked->user->whatsapp_url) {
                    throw ValidationException::withMessages(['status' => 'Landing page pemilik belum tersedia. Periksa subscription dan nomor WhatsApp.']);
                }
                if (! $locked->starts_at) {
                    $locked->starts_at = now();
                    $locked->ends_at = now()->addDays($locked->duration_days);
                }
            }
            $locked->fill(collect($data)->only(['status', 'admin_note'])->all());
            if ($hasMetrics) {
                $locked->metrics_updated_at = now();
            }
            $locked->save();
        });

        return back()->with('success', 'Penyiapan dan performa iklan diperbarui.');
    }

    public function updateLead(Request $request, GrowAdCampaign $campaign, RecruitmentLead $lead)
    {
        abort_unless($campaign->payment_status === 'paid' && $lead->owner_user_id === $campaign->user_id
            && $campaign->placements()->whereKey($lead->grow_ad_placement_id)->exists(), 404);
        abort_unless($lead->event_checkout_id === null, 422, 'Pembayaran online diverifikasi otomatis.');
        $data = $request->validate(['event_payment_status' => ['required', Rule::in(['unconfirmed', 'paid'])]]);
        DB::transaction(function () use ($lead, $data) {
            $lead = RecruitmentLead::lockForUpdate()->findOrFail($lead->id);
            $lead->update($data + ['event_paid_at' => $data['event_payment_status'] === 'paid' ? ($lead->event_paid_at ?? now()) : null]);
            if ($lead->wasChanged(['event_payment_status', 'event_paid_at'])) {
                app(\App\Services\CrmWebhookOutbox::class)->record($lead, 'payment.updated');
            }
        });

        return back()->with('success', 'Status pembayaran peserta diperbarui.');
    }
}
