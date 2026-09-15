<?php

namespace App\Http\Controllers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SellingKitController extends Controller
{
    public function index()
    {
        return view('selling-kit.index', ['categories' => config('selling-kit.categories', [])]);
    }

    public function category(string $category)
    {
        $item = collect(config('selling-kit.categories', []))->firstWhere('slug', $category);

        abort_unless($item, 404);

        return view('selling-kit.category', ['category' => $item]);
    }

    public function show(string $document): StreamedResponse
    {
        $item = $this->document($document);

        return $this->disk()->response($item['file'], $item['title'].'.pdf', [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$item['slug'].'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function download(string $document): StreamedResponse
    {
        $item = $this->document($document);

        return $this->disk()->download($item['file'], $item['slug'].'.pdf', [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function document(string $slug): array
    {
        $document = collect(config('selling-kit.categories', []))
            ->flatMap(fn (array $category) => $category['documents'])
            ->firstWhere('slug', $slug);

        abort_unless($document && $this->disk()->exists($document['file']), 404);

        return $document;
    }

    private function disk(): FilesystemAdapter
    {
        return Storage::disk('local');
    }
}
