<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $products = \App\Models\Product::query()
            ->when($q, fn($query) => $query->where(function ($qq) use ($q) {
                $qq->where('sku', 'like', "%{$q}%")
                ->orWhere('product_name', 'like', "%{$q}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('products.index', compact('products', 'q'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50', 'unique:products,sku'],
            'product_name' => ['required', 'string', 'max:150'],
            'model' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'product_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('product_image')) {
            $validated['product_image'] = $request->file('product_image')
                ->store('products', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active');

        Product::create($validated);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50', 'unique:products,sku,' . $product->id],
            'product_name' => ['required', 'string', 'max:150'],
            'model' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'product_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        // Replace image jika upload baru
        if ($request->hasFile('product_image')) {
            // hapus image lama (kalau ada)
            if ($product->product_image && Storage::disk('public')->exists($product->product_image)) {
                Storage::disk('public')->delete($product->product_image);
            }

            $validated['product_image'] = $request->file('product_image')->store('products', 'public');
        } else {
            // jangan overwrite kalau tidak upload baru
            unset($validated['product_image']);
        }

        $product->update($validated);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product berhasil diupdate.');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }
}
