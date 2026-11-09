<x-dashboard-layout>
    <section class="ga-page" data-grow-ads><header class="ga-page-header"><div><a class="ga-back" href="{{ route('admin.grow-ads.contents') }}"><x-grow-ad-icon name="back" />Koleksi konten</a><h1>{{ $content->exists ? 'Edit konten iklan' : 'Tambah konten iklan' }}</h1><p>Konten yang tersedia dapat dipilih oleh subscriber HP/HM.</p></div></header>
        @include('grow-ads.partials.feedback')
        <form method="POST" enctype="multipart/form-data" action="{{ $content->exists ? route('admin.grow-ads.contents.update', $content) : route('admin.grow-ads.contents.store') }}" class="ga-panel ga-editor" x-data="{busy:false}" @submit="busy=true">@csrf @if($content->exists) @method('PUT') @endif
            <div class="ga-field"><label for="title">Judul konten</label><input id="title" name="title" value="{{ old('title', $content->title) }}" required maxlength="120" placeholder="Contoh: Mulai peluang baru bersama Coway"></div>
            <div class="ga-field"><label for="description">Deskripsi konten</label><textarea id="description" name="description" required maxlength="3000" rows="4">{{ old('description', $content->description) }}</textarea></div>
            @if($content->exists)<div class="ga-editor-preview">@include('grow-ads.partials.media', ['item' => $content])</div>@endif
            <div class="ga-field">
                <label for="media">{{ $content->exists ? 'Ganti gambar atau video (opsional)' : 'Gambar atau video' }}</label>
                <x-file-upload
                    id="media"
                    name="media"
                    label="gambar atau video iklan"
                    accept=".jpg,.jpeg,.png,.webp,.mp4,.mov"
                    :max-size="30"
                    hint="JPG, PNG, WebP, MP4, atau MOV · Maksimal 30 MB"
                    :required="! $content->exists"
                />
            </div>
            <div class="ga-field">
                <label for="is_active">Ketersediaan konten</label>
                <select name="is_active" id="is_active">
                    <option value="1" @selected(old('is_active', $content->is_active) == 1)>Tersedia untuk dipilih</option>
                    <option value="0" @selected(old('is_active', $content->is_active) == 0)>Diarsipkan</option>
                </select>
                <p>Mengarsipkan konten tidak mengubah kampanye yang sudah dibuat.</p>
            </div>
            <div class="ga-form-footer"><a href="{{ route('admin.grow-ads.contents') }}" class="ga-text-link">Batalkan</a><button type="submit" class="ga-button" :disabled="busy" x-text="busy ? 'Menyimpan…' : 'Simpan konten'">Simpan konten</button></div>
        </form>
    </section>
</x-dashboard-layout>
