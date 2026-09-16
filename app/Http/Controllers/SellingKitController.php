<?php

namespace App\Http\Controllers;

use App\Models\SellingKitCategory;
use App\Models\SellingKitDocument;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SellingKitController extends Controller
{
    public function index(): View
    {
        return view('selling-kit.index', [
            'categories' => SellingKitCategory::query()->withCount('documents')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function category(SellingKitCategory $category): View
    {
        $category->load('documents');

        return view('selling-kit.category', compact('category'));
    }

    public function show(SellingKitDocument $document): StreamedResponse
    {
        abort_unless($this->disk()->exists($document->file_path), 404);

        return $this->disk()->response($document->file_path, $document->original_name, [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function download(SellingKitDocument $document): StreamedResponse
    {
        abort_unless($this->disk()->exists($document->file_path), 404);

        return $this->disk()->download($document->file_path, $document->slug.'.'.$document->extension, [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function manage(): View
    {
        return view('selling-kit.manage', [
            'categories' => SellingKitCategory::query()->withCount('documents')->orderBy('sort_order')->orderBy('name')->get(),
            'documents' => SellingKitDocument::query()->with(['category', 'uploader'])->latest()->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', Rule::exists('selling_kit_categories', 'id'), 'required_without:new_category_name'],
            'new_category_name' => ['nullable', 'string', 'max:100', 'required_without:category_id'],
            'new_category_description' => ['nullable', 'string', 'max:500'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'max:102400', 'mimes:pdf,ppt,pptx,jpg,jpeg,png,webp,mp4,mov,avi,webm'],
        ], [
            'file.max' => 'Ukuran file maksimal 100 MB.',
            'file.mimes' => 'File harus berupa PDF, PowerPoint, gambar, atau video.',
            'category_id.required_without' => 'Pilih kategori atau isi nama kategori baru.',
        ]);

        if (! empty($validated['new_category_name'])) {
            $category = SellingKitCategory::create([
                'name' => $validated['new_category_name'],
                'slug' => $this->uniqueSlug(SellingKitCategory::class, $validated['new_category_name']),
                'description' => $validated['new_category_description'] ?? null,
                'color' => ['sky', 'emerald', 'violet'][SellingKitCategory::count() % 3],
                'sort_order' => (SellingKitCategory::max('sort_order') ?? 0) + 1,
            ]);
        } else {
            $category = SellingKitCategory::findOrFail($validated['category_id']);
        }

        $file = $request->file('file');
        $slug = $this->uniqueSlug(SellingKitDocument::class, $validated['title']);
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs('selling-kit/uploads', $slug.'.'.$extension, 'local');

        SellingKitDocument::create([
            'selling_kit_category_id' => $category->id,
            'uploaded_by' => $request->user()->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'extension' => $extension,
            'file_size' => $file->getSize(),
        ]);

        return redirect()->route('admin.selling-kit.index')->with('success', 'Selling Kit berhasil ditambahkan.');
    }

    private function uniqueSlug(string $model, string $value): string
    {
        $base = Str::slug($value) ?: 'selling-kit';
        $slug = $base;
        $suffix = 2;

        while ($model::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function disk(): FilesystemAdapter
    {
        return Storage::disk('local');
    }
}
