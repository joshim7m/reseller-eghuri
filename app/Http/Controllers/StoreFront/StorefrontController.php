<?php

namespace App\Http\Controllers\StoreFront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Faq;
use App\Models\InstagramImage;
use App\Models\Notice;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StorefrontController extends Controller
{
    public function index()
    {
        $categories = Category::with('children')->orderBy('id')->get();

        $categoryIds = $categories->pluck('id');

        $counts = DB::table('category_product')
            ->join('products', 'products.id', '=', 'category_product.product_id')
            ->whereIn('category_product.category_id', $categoryIds)
            ->where('products.status', 'active')
            ->select('category_product.category_id')
            ->selectRaw('count(*) as total')
            ->groupBy('category_product.category_id')
            ->pluck('total', 'category_product.category_id')
            ->map(fn ($value) => (int) $value);

        $categories->each(function (Category $category) use ($counts) {
            $total = (int) $counts->get($category->id, 0);

            if ($category->children->isNotEmpty()) {
                $total += $category->children->sum(fn (Category $child) => (int) $counts->get($child->id, 0));
            }

            $category->products_count = $total;
        });

        $featured = Product::where('status', 'active')->where('featured', true)
            ->with('category', 'images')->latest()->take(12)->get();

        $remaining = 12 - $featured->count();
        $latest = collect();
        if ($remaining > 0) {
            $latest = Product::where('status', 'active')->where('featured', false)
                ->with('category', 'images')->latest()->take($remaining)->get();
        }

        $products = $featured->concat($latest);

        $newArrivals = Product::where('status', 'active')
            ->with('images')
            ->latest()
            ->take(12)
            ->get();

        $instagramImages = InstagramImage::latest()->take(6)->get();

        $faqs = Faq::active()->orderBy('sort_order')->get();

        $notices = Notice::active()->latest()->get();

        return Inertia::render('StoreFront/Home/Index', compact('categories', 'products', 'newArrivals', 'instagramImages', 'faqs', 'notices'));
    }

    public function categories()
    {
        $categories = Category::withCount(['products' => function ($q) {
            $q->where('status', 'active');
        }])->get();

        return Inertia::render('StoreFront/Categories/Index', compact('categories'));
    }

    public function category(Request $request, Category $category)
    {
        $category->load('children', 'parent');

        $categoryIds = $category->children->pluck('id')->push($category->id)->values();

        $query = Product::whereIn('category_id', $categoryIds)
            ->where('status', 'active')
            ->with('images', 'variants');

        $query = $this->applyFilters($request, $query);
        $query = $this->applySort($request, $query);

        $products = $query->paginate(12)->withQueryString();

        $filters = $this->getFilterOptions($request);

        return Inertia::render('StoreFront/Categories/Show', compact('category', 'products', 'filters'));
    }

    public function products(Request $request)
    {
        $query = Product::where('status', 'active')
            ->with('images', 'variants', 'category');

        $query = $this->applyFilters($request, $query);
        $query = $this->applySort($request, $query);

        $products = $query->paginate(12)->withQueryString();

        $filters = $this->getFilterOptions($request);
        $categories = Category::whereNull('parent_id')->where('is_active', true)->orderBy('name')->get();

        return Inertia::render('StoreFront/Products/Index', compact('products', 'filters', 'categories'));
    }

    public function newArrivals(Request $request)
    {
        $query = Product::where('status', 'active')
            ->with('images', 'variants', 'category');

        if ($request->get('sort')) {
            $query = $this->applySort($request, $query);
        } else {
            $query->latest();
        }

        $products = $query->paginate(12)->withQueryString();
        $filters = $this->getFilterOptions($request);
        $categories = Category::whereNull('parent_id')->where('is_active', true)->orderBy('name')->get();

        return Inertia::render('StoreFront/NewArrivals', compact('products', 'filters', 'categories'));
    }

    public function hotSale(Request $request)
    {
        $featured = Product::where('status', 'active')->where('featured', true)
            ->with('images', 'variants', 'category')->latest()->take(20)->get();

        $remaining = 20 - $featured->count();
        $latest = collect();
        if ($remaining > 0) {
            $latest = Product::where('status', 'active')->where('featured', false)
                ->with('images', 'variants', 'category')->latest()->take($remaining)->get();
        }

        $products = $featured->concat($latest);
        $filters = $this->getFilterOptions($request);
        $categories = Category::whereNull('parent_id')->where('is_active', true)->orderBy('name')->get();

        return Inertia::render('StoreFront/HotSale', compact('products', 'filters', 'categories'));
    }

    public function product(Product $product)
    {
        $product->load('category', 'images', 'variants.image');

        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->with('images', 'variants')
            ->take(6)
            ->get();

        if ($related->count() < 6) {
            $excludedIds = $related->pluck('id')->push($product->id);

            $more = Product::where('status', 'active')
                ->whereNotIn('id', $excludedIds)
                ->with('images', 'variants')
                ->inRandomOrder()
                ->take(6 - $related->count())
                ->get();

            $related = $related->concat($more);
        }

        return Inertia::render('StoreFront/Products/Show', compact('product', 'related'));
    }

    public function showPage(Page $page)
    {
        abort_unless($page->status, 404);

        return Inertia::render('StoreFront/Pages/Show', compact('page'));
    }

    public function searchAjax(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $query = Product::where('status', 'active');

        if ($q !== '') {
            $query->where(function ($qry) use ($q) {
                $qry->where('title', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhereHas('variants', function ($variantQry) use ($q) {
                        $variantQry->where('sku', 'like', "%{$q}%");
                    });
            });
        }

        $products = $query->orderByDesc('featured')->latest()->take(5)->get()
            ->load('variants')
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'sku' => $product->sku ?: $product->variants->first()?->sku,
                'sale_price' => $product->sale_price,
                'unit_price' => $product->unit_price,
                'total_stock' => $product->total_stock,
                'image' => $product->image_url,
            ]);

        return response()->json($products);
    }

    public function cart()
    {
        return Inertia::render('StoreFront/Cart/Index');
    }

    public function wishlistPage()
    {
        $products = Product::where('status', 'active')
            ->with('images', 'variants', 'category')
            ->latest()
            ->get();

        return Inertia::render('StoreFront/Wishlist/Index', compact('products'));
    }

    public function exportWishlist(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:products,id'],
        ]);

        $products = Product::whereIn('id', $validated['ids'])
            ->where('status', 'active')
            ->with('images', 'variants')
            ->get()
            ->keyBy('id');

        $ordered = collect($validated['ids'])
            ->map(fn (int $id) => $products[$id] ?? null)
            ->filter()
            ->values();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Wishlist');

        $sheet->fromArray(['SL No', 'Image', 'Title', 'SKU', 'Variants', 'Price', 'Stock'], null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $columnWidths = ['A' => 7, 'B' => 18, 'C' => 45, 'D' => 16, 'E' => 30, 'F' => 12, 'G' => 10];
        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $row = 2;
        foreach ($ordered as $index => $product) {
            $sheet->setCellValue("A{$row}", $index + 1);
            $sheet->setCellValue("C{$row}", $product->title);
            $sheet->setCellValue("D{$row}", $product->sku);
            $sheet->setCellValue("E{$row}", $this->variantLabel($product));
            $sheet->setCellValue("F{$row}", $product->sale_price ?: $product->unit_price);
            $sheet->setCellValue("G{$row}", $product->total_stock);
            $sheet->getRowDimension($row)->setRowHeight(75);

            $imagePath = $product->images->sortBy('sort_order')->first()?->image_path;

            if ($imagePath) {
                $resolved = $this->resolveImageForExport($imagePath);

                if ($resolved) {
                    $drawing = new Drawing;
                    $drawing->setName($product->title);
                    $drawing->setPath($resolved);
                    $drawing->setCoordinates("B{$row}");
                    $drawing->setHeight(70);
                    $drawing->setOffsetX(4);
                    $drawing->setOffsetY(4);
                    $drawing->setWorksheet($sheet);
                }
            }

            $row++;
        }

        $filename = 'wishlist.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function variantLabel(Product $product): string
    {
        return $product->variants
            ->map(fn ($variant) => trim(implode(' / ', array_filter([$variant->size, $variant->color]))))
            ->filter()
            ->implode("\n");
    }

    private function resolveImageForExport(?string $imagePath): ?string
    {
        if (! $imagePath) {
            return null;
        }

        if (! str_starts_with($imagePath, 'http')) {
            $local = public_path($imagePath);

            if (is_file($local)) {
                return $local;
            }
        }

        $url = str_starts_with($imagePath, 'http') ? $imagePath : asset($imagePath);

        try {
            $contents = @file_get_contents($url);

            if ($contents === false) {
                return null;
            }

            $temp = tempnam(sys_get_temp_dir(), 'wishlist_img');

            file_put_contents($temp, $contents);

            return $temp;
        } catch (\Throwable) {
            return null;
        }
    }

    public function profile()
    {
        return Inertia::render('StoreFront/UserProfile/Profile');
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'mobile' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:2000',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        UserDetail::updateOrCreate(
            ['user_id' => $user->id],
            [
                'mobile' => $validated['mobile'],
                'company' => $validated['company'],
                'address' => $validated['address'],
            ]
        );

        return redirect()->route('profile')->with('success', 'Profile updated successfully.');
    }

    private function applyFilters(Request $request, $query)
    {
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->where('title', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhereHas('variants', function ($variantQry) use ($q) {
                        $variantQry->where('sku', 'like', "%{$q}%");
                    });
            });
        }

        if ($request->filled('min_price')) {
            $query->where('sale_price', '>=', (int) $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('sale_price', '<=', (int) $request->max_price);
        }

        if ($request->filled('categories')) {
            $cats = (array) $request->categories;
            $query->whereIn('category_id', $cats);
        }

        if ($request->filled('sizes')) {
            $sizes = (array) $request->sizes;
            $query->whereHas('variants', function ($q) use ($sizes) {
                $q->whereIn('size', $sizes)->where('quantity', '>', 0);
            });
        }

        if ($request->filled('colors')) {
            $colors = (array) $request->colors;
            $query->whereHas('variants', function ($q) use ($colors) {
                $q->whereIn('color', $colors)->where('quantity', '>', 0);
            });
        }

        return $query;
    }

    private function applySort(Request $request, $query)
    {
        return match ($request->sort) {
            'price_asc' => $query->orderBy('sale_price'),
            'price_desc' => $query->orderByDesc('sale_price'),
            'name_asc' => $query->orderBy('title'),
            'name_desc' => $query->orderByDesc('title'),
            'oldest' => $query->orderBy('created_at'),
            default => $query->latest(),
        };
    }

    private function getFilterOptions(Request $request)
    {
        $categories = Category::whereNull('parent_id')
            ->withCount(['products' => function ($q) {
                $q->where('status', 'active');
            }])
            ->orderBy('name')
            ->get();

        $prices = Product::where('status', 'active')
            ->selectRaw('MIN(sale_price) as min_price, MAX(sale_price) as max_price')
            ->first();

        $sizes = ProductVariant::whereHas('product', function ($q) {
            $q->where('status', 'active');
        })->whereNotNull('size')->where('size', '!=', '')
            ->selectRaw('size, SUM(quantity) as total_qty')
            ->groupBy('size')
            ->having('total_qty', '>', 0)
            ->orderBy('size')
            ->pluck('size')
            ->toArray();

        $colors = ProductVariant::whereHas('product', function ($q) {
            $q->where('status', 'active');
        })->whereNotNull('color')->where('color', '!=', '')
            ->selectRaw('color, SUM(quantity) as total_qty')
            ->groupBy('color')
            ->having('total_qty', '>', 0)
            ->orderBy('color')
            ->pluck('color')
            ->toArray();

        return compact('categories', 'prices', 'sizes', 'colors');
    }
}
