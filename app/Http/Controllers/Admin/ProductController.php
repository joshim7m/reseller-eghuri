<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category', 'images', 'variants', 'categories');

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->filled('category_id')) {
            $cat = Category::find($request->category_id);
            $catIds = $cat ? $cat->children->pluck('id')->push($cat->id)->values() : [$request->category_id];
            $query->where(function ($q) use ($catIds) {
                $q->whereIn('category_id', $catIds)
                    ->orWhereHas('categories', fn ($sub) => $sub->whereIn('categories.id', $catIds));
            });
        }

        $sortField = match ($request->sort_by) {
            'price' => 'sale_price',
            'status' => 'status',
            default => null,
        };

        if ($sortField) {
            $query->orderBy($sortField, $request->sort_order === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $products = $query->paginate(15)->withQueryString();
        $categories = Category::with('children')->orderBy('name')->get();

        return Inertia::render('Admin/Products/Index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::all();

        return Inertia::render('Admin/Products/Create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|integer|exists:categories,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'description' => 'required|string',
            'specification' => 'nullable|string',
            'sku' => 'nullable|string|max:255',
            'quantity' => 'nullable|integer|min:0',
            'unit_price' => 'required|integer|min:0',
            'sale_price' => 'required|integer|min:0|gte:unit_price',
            'status' => 'required|in:active,inactive,draft',
            'featured' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'variants' => 'nullable|array',
            'variants.*.option1' => 'nullable|string|max:50',
            'variants.*.option2' => 'nullable|string|max:50',
            'variants.*.option3' => 'nullable|string|max:50',
            'variants.*.sku' => 'nullable|string|max:255',
            'variants.*.unit_price' => 'nullable|integer|min:0',
            'variants.*.sale_price' => 'nullable|integer|min:0',
            'variants.*.quantity' => 'required|integer|min:0',
            'variants.*.product_image_id' => 'nullable|integer|exists:product_images,id',
        ], [
            'sale_price.gte' => 'Sale price must be greater than or equal to unit price.',
        ]);

        $product = Product::create([
            'category_id' => $validated['category_id'] ?? null,
            'title' => $validated['title'],
            'slug' => ! empty($validated['slug']) ? $validated['slug'] : Str::slug($validated['title']),
            'description' => $validated['description'],
            'specification' => $validated['specification'] ?? null,
            'sku' => $validated['sku'] ?? null,
            'quantity' => $validated['quantity'] ?? 0,
            'unit_price' => $validated['unit_price'],
            'sale_price' => $validated['sale_price'],
            'status' => $validated['status'],
            'featured' => $validated['featured'] ?? false,
        ]);

        if (! empty($validated['category_ids'])) {
            $product->categories()->sync($validated['category_ids']);
        }

        if (! empty($validated['variants'])) {
            foreach ($validated['variants'] as $variant) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'size' => $variant['option1'] ?? null,
                    'color' => $variant['option2'] ?? null,
                    'sku' => $variant['sku'] ?? null,
                    'unit_price' => $variant['unit_price'] ?? null,
                    'sale_price' => $variant['sale_price'] ?? null,
                    'quantity' => $variant['quantity'],
                    'product_image_id' => $variant['product_image_id'] ?? null,
                ]);
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $filename = time().'_'.$file->getClientOriginalName();
                $file->move(public_path('images/products'), $filename);
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => 'images/products/'.$filename,
                ]);
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product "'.$product->title.'" created successfully.');
    }

    public function edit(Product $product)
    {
        $categories = Category::all();
        $product->load(['images' => fn ($q) => $q->orderBy('sort_order'), 'variants.image', 'categories']);

        return Inertia::render('Admin/Products/Edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|integer|exists:categories,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,'.$product->id,
            'description' => 'required|string',
            'specification' => 'nullable|string',
            'sku' => 'nullable|string|max:255',
            'quantity' => 'nullable|integer|min:0',
            'unit_price' => 'required|integer|min:0',
            'sale_price' => 'required|integer|min:0|gte:unit_price',
            'status' => 'required|in:active,inactive,draft',
            'featured' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer',
            'variants.*.option1' => 'nullable|string|max:50',
            'variants.*.option2' => 'nullable|string|max:50',
            'variants.*.option3' => 'nullable|string|max:50',
            'variants.*.sku' => 'nullable|string|max:255',
            'variants.*.unit_price' => 'nullable|integer|min:0',
            'variants.*.sale_price' => 'nullable|integer|min:0',
            'variants.*.quantity' => 'required|integer|min:0',
            'variants.*.product_image_id' => 'nullable|integer|exists:product_images,id',
        ], [
            'sale_price.gte' => 'Sale price must be greater than or equal to unit price.',
        ]);

        $product->update([
            'category_id' => $validated['category_id'] ?? null,
            'title' => $validated['title'],
            'slug' => ! empty($validated['slug']) ? $validated['slug'] : Str::slug($validated['title']),
            'description' => $validated['description'],
            'specification' => $validated['specification'] ?? null,
            'sku' => $validated['sku'] ?? null,
            'quantity' => $validated['quantity'] ?? 0,
            'unit_price' => $validated['unit_price'],
            'sale_price' => $validated['sale_price'],
            'status' => $validated['status'],
            'featured' => $validated['featured'] ?? false,
        ]);

        if (! empty($validated['category_ids'])) {
            $product->categories()->sync($validated['category_ids']);
        }

        $submittedIds = [];
        if (! empty($validated['variants'])) {
            foreach ($validated['variants'] as $variant) {
                $data = [
                    'size' => $variant['option1'] ?? null,
                    'color' => $variant['option2'] ?? null,
                    'sku' => $variant['sku'] ?? null,
                    'unit_price' => $variant['unit_price'] ?? null,
                    'sale_price' => $variant['sale_price'] ?? null,
                    'quantity' => $variant['quantity'],
                    'product_image_id' => $variant['product_image_id'] ?? null,
                ];

                if (! empty($variant['id'])) {
                    $existing = ProductVariant::find($variant['id']);
                    if ($existing && $existing->product_id === $product->id) {
                        $existing->update($data);
                        $submittedIds[] = $existing->id;
                    }
                } else {
                    $new = ProductVariant::create(array_merge($data, ['product_id' => $product->id]));
                    $submittedIds[] = $new->id;
                }
            }
        }

        $product->variants()->whereNotIn('id', $submittedIds)->delete();

        if ($request->filled('image_order')) {
            foreach ($request->image_order as $index => $imageId) {
                ProductImage::where('id', $imageId)->where('product_id', $product->id)->update(['sort_order' => $index]);
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $filename = time().'_'.$file->getClientOriginalName();
                $file->move(public_path('images/products'), $filename);
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => 'images/products/'.$filename,
                ]);
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product "'.$product->title.'" updated successfully.');
    }

    public function destroyImage(Product $product, ProductImage $image)
    {
        if ($image->product_id !== $product->id) {
            abort(404);
        }

        if (file_exists(public_path($image->image_path))) {
            unlink(public_path($image->image_path));
        }

        $image->delete();

        return back()->with('success', 'Image deleted successfully.');
    }

    public function destroy(Product $product)
    {
        foreach ($product->images as $img) {
            if (file_exists(public_path($img->image_path))) {
                unlink(public_path($img->image_path));
            }
        }
        $product->images()->delete();
        $product->variants()->delete();
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
