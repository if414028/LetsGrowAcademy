<x-dashboard-layout>
    <div class="mx-auto max-w-7xl" x-data="{ categoryMode: '{{ old('new_category_name') ? 'new' : 'existing' }}', submitting: false }">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-sky-600">Subscription · Admin</p>
                <h1 class="mt-2 text-3xl font-extrabold text-slate-950">Manage Selling Kit</h1>
                <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-500">Kelola materi premium yang dapat diakses oleh seluruh subscriber aktif.</p>
            </div>
            <button type="button" onclick="document.getElementById('upload-form').scrollIntoView({ behavior: 'smooth' })" class="inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 text-sm font-extrabold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Tambah Selling Kit
            </button>
        </div>

        @if (session('success'))
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        <section class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan Selling Kit">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Total materi</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $documents->total() }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Kategori</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $categories->count() }}</p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Format yang didukung</p><p class="mt-2 text-sm font-extrabold text-slate-800">PDF · PowerPoint · JPG/PNG/WebP · MP4/MOV/AVI/WebM</p><p class="mt-1 text-xs font-medium text-slate-500">Maksimal 100 MB per file.</p></div>
        </section>

        <section id="upload-form" class="mt-7 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="upload-title">
            <div class="flex items-center gap-3"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 16V4m0 0-4 4m4-4 4 4M5 14v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-5"/></svg></span><div><h2 id="upload-title" class="text-xl font-extrabold text-slate-950">Upload materi baru</h2><p class="text-sm font-medium text-slate-500">Pilih kategori yang ada atau buat kategori baru.</p></div></div>

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><p class="font-extrabold">Periksa kembali data berikut:</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <form method="POST" action="{{ route('admin.selling-kit.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6" @submit="submitting = true">
                @csrf
                <fieldset>
                    <legend class="text-sm font-extrabold text-slate-800">Kategori</legend>
                    <div class="mt-3 inline-flex rounded-xl bg-slate-100 p-1">
                        <button type="button" @click="categoryMode = 'existing'" :class="categoryMode === 'existing' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500'" class="min-h-10 cursor-pointer rounded-lg px-4 text-sm font-bold transition">Pilih kategori</button>
                        <button type="button" @click="categoryMode = 'new'" :class="categoryMode === 'new' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500'" class="min-h-10 cursor-pointer rounded-lg px-4 text-sm font-bold transition">Buat kategori baru</button>
                    </div>
                    <div class="mt-4" x-show="categoryMode === 'existing'">
                        <label for="category_id" class="text-sm font-bold text-slate-700">Kategori Selling Kit</label>
                        <select id="category_id" name="category_id" :disabled="categoryMode !== 'existing'" class="mt-2 min-h-12 w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }} ({{ $category->documents_count }})</option>@endforeach
                        </select>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2" x-show="categoryMode === 'new'">
                        <div><label for="new_category_name" class="text-sm font-bold text-slate-700">Nama kategori baru</label><input id="new_category_name" name="new_category_name" :disabled="categoryMode !== 'new'" value="{{ old('new_category_name') }}" class="mt-2 min-h-12 w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Contoh: Materi Follow Up"></div>
                        <div><label for="new_category_description" class="text-sm font-bold text-slate-700">Deskripsi kategori <span class="font-medium text-slate-400">(opsional)</span></label><input id="new_category_description" name="new_category_description" :disabled="categoryMode !== 'new'" value="{{ old('new_category_description') }}" class="mt-2 min-h-12 w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Kegunaan materi dalam kategori ini"></div>
                    </div>
                </fieldset>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div><label for="title" class="text-sm font-bold text-slate-700">Judul Selling Kit</label><input id="title" name="title" required value="{{ old('title') }}" class="mt-2 min-h-12 w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Masukkan judul materi"></div>
                    <div><label for="file" class="text-sm font-bold text-slate-700">File Selling Kit</label><input id="file" name="file" type="file" required accept=".pdf,.ppt,.pptx,.jpg,.jpeg,.png,.webp,.mp4,.mov,.avi,.webm" class="mt-2 block min-h-12 w-full cursor-pointer rounded-xl border border-slate-300 bg-white text-sm file:mr-4 file:min-h-12 file:border-0 file:bg-blue-50 file:px-4 file:font-bold file:text-blue-700 hover:file:bg-blue-100"></div>
                </div>
                <div><label for="description" class="text-sm font-bold text-slate-700">Deskripsi <span class="font-medium text-slate-400">(opsional)</span></label><textarea id="description" name="description" rows="3" class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Jelaskan isi dan kegunaan materi">{{ old('description') }}</textarea></div>
                <div class="flex justify-end"><button type="submit" :disabled="submitting" class="inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 text-sm font-extrabold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60"><svg x-show="submitting" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/><path class="opacity-75" fill="currentColor" d="M21 12a9 9 0 0 0-9-9v3a6 6 0 0 1 6 6h3Z"/></svg><span x-text="submitting ? 'Mengupload…' : 'Simpan Selling Kit'">Simpan Selling Kit</span></button></div>
            </form>
        </section>

        <section class="mt-7 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" aria-labelledby="list-title">
            <div class="border-b border-slate-200 px-6 py-5 sm:px-8"><h2 id="list-title" class="text-xl font-extrabold text-slate-950">Semua Selling Kit</h2><p class="mt-1 text-sm font-medium text-slate-500">Materi terbaru ditampilkan paling atas.</p></div>
            <div class="divide-y divide-slate-100">
                @forelse ($documents as $document)
                    <article class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                        <div class="flex min-w-0 items-start gap-4"><span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-sky-50 px-2 text-center text-[9px] font-black text-sky-700">{{ $document->type_label }}</span><div class="min-w-0"><h3 class="truncate text-base font-extrabold text-slate-900">{{ $document->title }}</h3><p class="mt-1 text-xs font-bold text-slate-500">{{ $document->category->name }} · {{ $document->formatted_size }}</p><p class="mt-1 truncate text-xs text-slate-400">{{ $document->original_name }}</p></div></div>
                        <div class="flex shrink-0 gap-2"><a href="{{ route('admin.selling-kit.show', $document) }}" target="_blank" class="inline-flex min-h-11 cursor-pointer items-center rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Lihat file</a><a href="{{ route('admin.selling-kit.download', $document) }}" class="inline-flex min-h-11 cursor-pointer items-center rounded-xl bg-slate-900 px-4 text-sm font-bold text-white transition hover:bg-slate-700">Unduh</a></div>
                    </article>
                @empty
                    <div class="px-6 py-16 text-center text-sm font-medium text-slate-500">Belum ada Selling Kit.</div>
                @endforelse
            </div>
            @if ($documents->hasPages())<div class="border-t border-slate-200 px-6 py-4">{{ $documents->links() }}</div>@endif
        </section>
    </div>
</x-dashboard-layout>
