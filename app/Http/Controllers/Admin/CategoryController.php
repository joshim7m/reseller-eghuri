<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::with('children', 'parent')->withCount('products');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('parent_id')) {
            if ((int) $request->parent_id === 0) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        if ($request->filled('active_status')) {
            $query->where('is_active', $request->boolean('active_status'));
        }

        $sortField = match ($request->sort_by) {
            'name' => 'name',
            'is_active' => 'is_active',
            default => null,
        };

        if ($sortField) {
            $query->orderBy($sortField, $request->sort_order === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $categories = $query->paginate(15)->withQueryString();
        $parentCategories = Category::whereNull('parent_id')->orderBy('name')->get();

        return Inertia::render('Admin/Categories/Index', compact('categories', 'parentCategories'));
    }

    public function create()
    {
        $categories = Category::all();

        return Inertia::render('Admin/Categories/Create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = ! empty($validated['slug']) ? $validated['slug'] : Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image_path')) {
            $file = $request->file('image_path');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('images/categories'), $filename);
            $validated['image_path'] = 'images/categories/'.$filename;
        } else {
            $validated['image_path'] = null;
        }

        Category::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category "'.$validated['name'].'" created successfully.');
    }

    public function edit(Category $category)
    {
        $categories = Category::where('id', '!=', $category->id)->get();

        return Inertia::render('Admin/Categories/Edit', compact('category', 'categories'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                Rule::notIn([$category->id]),
            ],
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,'.$category->id,
            'description' => 'nullable|string',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'boolean',
        ], [
            'parent_id.not_in' => 'A category cannot be its own parent.',
        ]);

        $validated['slug'] = ! empty($validated['slug']) ? $validated['slug'] : Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image_path')) {
            if ($category->image_path && file_exists(public_path($category->image_path))) {
                unlink(public_path($category->image_path));
            }
            $file = $request->file('image_path');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('images/categories'), $filename);
            $validated['image_path'] = 'images/categories/'.$filename;
        } else {
            unset($validated['image_path']);
        }

        $category->update($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category "'.$category->name.'" updated successfully.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
