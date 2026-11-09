@php
    $availableIds = array_diff($contents->modelKeys(), $reservedIds);
    $submittedIds = old('content_ids', $selectedIds);
    $initialIds = array_values(array_intersect(array_filter(is_array($submittedIds) ? $submittedIds : $selectedIds, 'is_scalar'), $availableIds));
@endphp
<x-dashboard-layout>
    <section class="ga-page" data-grow-ads x-data="growAdSelection(@js($initialIds))">
        <header class="ga-page-header"><div><a href="{{ route('grow-ads.index') }}" class="ga-back"><x-grow-ad-icon name="back" />Grow Ads</a><h1>Pilih konten iklan</h1><p>Pilih hingga 3 konten yang ingin kamu promosikan. Pilihan yang disimpan di draf ditahan selama 24 jam.</p></div></header>
        @include('grow-ads.partials.steps', ['step' => 1])
        @include('grow-ads.partials.feedback')
        <form method="POST" action="{{ route('grow-ads.select') }}" @submit="busy = true">
            @csrf
            <div class="ga-selection-heading"><h2>Koleksi konten</h2><span class="ga-selection-count" aria-live="polite"><strong x-text="selected.length">{{ count($initialIds) }}</strong> / 3 dipilih</span></div>
            <p class="ga-inline-hint" x-show="selected.length === 3" x-cloak role="status">Maksimal 3 konten. Batalkan salah satu pilihan untuk menggantinya.</p>
            <div class="ga-content-grid">
                @forelse ($contents as $content)
                    <article class="ga-content" :class="{'is-selected': selected.includes({{ $content->id }})}">
                        <div class="ga-content-media">@include('grow-ads.partials.media', ['item' => $content])<span class="ga-media-label">{{ $content->media_type === 'video' ? 'Video' : 'Gambar' }}</span></div>
                        <div class="ga-content-body"><h3>{{ $content->title }}</h3><p>{{ $content->description }}</p>
                            @if (in_array($content->id, $reservedIds))
                                <label class="ga-content-select"><input type="checkbox" disabled><span>Sudah dipilih pengguna lain</span></label>
                            @else
                                <label class="ga-content-select"><input type="checkbox" name="content_ids[]" value="{{ $content->id }}" x-model.number="selected" :disabled="selected.length >= 3 && !selected.includes({{ $content->id }})" @checked(in_array($content->id, $initialIds))><span x-text="selected.includes({{ $content->id }}) ? 'Konten dipilih' : 'Pilih konten ini'">Pilih konten ini</span></label>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="ga-empty ga-grid-full"><x-grow-ad-icon name="image" /><h2>Konten sedang disiapkan.</h2><p>Admin belum menyediakan konten iklan. Kamu bisa kembali setelah konten tersedia.</p><a href="{{ route('grow-ads.index') }}" class="ga-button ga-button--secondary">Kembali ke Grow Ads</a></div>
                @endforelse
            </div>
            @if ($availableIds)
                <div class="ga-form-footer"><p>Budget harian berlaku untuk seluruh konten pilihanmu.</p><button class="ga-button" type="submit" :disabled="!selected.length || busy"><span x-text="busy ? 'Menyimpan…' : 'Lanjut atur budget'">Lanjut atur budget</span><x-grow-ad-icon /></button></div>
            @endif
        </form>
    </section>
</x-dashboard-layout>
