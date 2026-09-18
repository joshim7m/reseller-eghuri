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
use App\Models\Setting;
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
        $allChildIds = $categories->flatMap->children->pluck('id');
        $allIds = $categoryIds->merge($allChildIds)->unique();

        $pivotCounts = DB::table('category_product')
            ->join('products', 'products.id', '=', 'category_product.product_id')
            ->whereIn('category_product.category_id', $allIds)
            ->where('products.status', 'active')
            ->select('category_product.category_id')
            ->selectRaw('count(*) as total')
            ->groupBy('category_product.category_id')
            ->pluck('total', 'category_product.category_id')
            ->map(fn ($value) => (int) $value);

        $fkCounts = Product::where('status', 'active')
            ->whereIn('category_id', $allIds)
            ->select('category_id')
            ->selectRaw('count(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id')
            ->map(fn ($value) => (int) $value);

        $categories->each(function (Category $category) use ($pivotCounts, $fkCounts) {
            $total = (int) $pivotCounts->get($category->id, 0) + (int) $fkCounts->get($category->id, 0);

            if ($category->children->isNotEmpty()) {
                $total += $category->children->sum(fn (Category $child) => (int) $pivotCounts->get($child->id, 0) + (int) $fkCounts->get($child->id, 0));
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

        return Inertia::render('StoreFront/Home/Index', [
            'categories' => $categories,
            'products' => $products,
            'newArrivals' => $newArrivals,
            'instagramImages' => $instagramImages,
            'faqs' => $faqs,
            'notices' => $notices,
            'seo' => $this->seoMeta([
                'title' => Setting::get('seo_home_title', Setting::get('company_name', '')),
                'description' => Setting::get('seo_home_description', Setting::get('company_description', '')),
                'canonical' => route('home'),
            ]),
        ]);
    }

    public function categories()
    {
        $categories = Category::where('is_active', true)
            ->with('children')
            ->orderBy('name')
            ->get();

        $allIds = $categories->pluck('id')
            ->merge($categories->flatMap->children->pluck('id'))
            ->unique();

        $pivotCounts = DB::table('category_product')
            ->join('products', 'products.id', '=', 'category_product.product_id')
            ->whereIn('category_product.category_id', $allIds)
            ->where('products.status', 'active')
            ->select('category_product.category_id')
            ->selectRaw('count(*) as total')
            ->groupBy('category_product.category_id')
            ->pluck('total', 'category_product.category_id')
            ->map(fn ($value) => (int) $value);

        $fkCounts = Product::where('status', 'active')
            ->whereIn('category_id', $allIds)
            ->select('category_id')
            ->selectRaw('count(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id')
            ->map(fn ($value) => (int) $value);

        $categories->each(function (Category $category) use ($pivotCounts, $fkCounts) {
            $total = (int) $pivotCounts->get($category->id, 0) + (int) $fkCounts->get($category->id, 0);

            if ($category->children->isNotEmpty()) {
                $total += $category->children->sum(fn (Category $child) => (int) $pivotCounts->get($child->id, 0) + (int) $fkCounts->get($child->id, 0));
            }

            $category->products_count = $total;
        });

        return Inertia::render('StoreFront/Categories/Index', [
            'categories' => $categories,
            'seo' => $this->seoMeta([
                'title' => Setting::get('seo_categories_title', 'Categories'),
                'description' => Setting::get('seo_categories_description', Setting::get('company_description', '')),
                'canonical' => route('categories.index'),
            ]),
        ]);
    }

    public function category(Request $request, Category $category)
    {
        $category->load('children', 'parent');

        $categoryIds = $category->children->pluck('id')->push($category->id)->values();

        $query = Product::where(function ($q) use ($categoryIds) {
            $q->whereIn('category_id', $categoryIds)
                ->orWhereHas('categories', fn ($sub) => $sub->whereIn('categories.id', $categoryIds));
        })->where('status', 'active')
            ->with('images', 'variants');

        $query = $this->applyFilters($request, $query);
        $query = $this->applySort($request, $query);

        $products = $query->paginate(12)->withQueryString();

        $filters = $this->getFilterOptions($request);

        $categoryTitle = $category->name;
        $categoryDescription = $category->description ?? '';

        return Inertia::render('StoreFront/Categories/Show', [
            'category' => $category,
            'products' => $products,
            'filters' => $filters,
            'seo' => $this->seoMeta([
                'title' => $category->meta_title ?: $this->fillPattern(Setting::get('seo_category_title_pattern', '{name}'), ['name' => $category->name]),
                'description' => $category->meta_description ?: ($categoryDescription ?: $this->fillPattern(Setting::get('seo_category_description_pattern', ''), ['name' => $category->name])),
                'canonical' => route('category.show', $category->slug),
                'og_image' => $category->image_url,
                'schema' => [
                    'type' => 'item_list',
                    'heading' => $categoryTitle,
                    'intro' => $categoryDescription,
                    'products' => $products->items(),
                    'breadcrumbs' => $this->categoryBreadcrumbs($category),
                ],
            ]),
        ]);
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

        return Inertia::render('StoreFront/Products/Index', [
            'products' => $products,
            'filters' => $filters,
            'categories' => $categories,
            'seo' => $this->seoMeta([
                'title' => Setting::get('seo_products_title', 'Products'),
                'description' => Setting::get('seo_products_description', Setting::get('company_description', '')),
                'canonical' => route('products.index'),
                'schema' => ['type' => 'item_list', 'products' => $products->items()],
            ]),
        ]);
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

        return Inertia::render('StoreFront/NewArrivals', [
            'products' => $products,
            'filters' => $filters,
            'categories' => $categories,
            'seo' => $this->seoMeta([
                'title' => Setting::get('seo_new_arrivals_title', 'New Arrivals'),
                'description' => Setting::get('seo_new_arrivals_description', Setting::get('company_description', '')),
                'canonical' => route('products.new-arrivals'),
                'schema' => ['type' => 'item_list', 'products' => $products->items()],
            ]),
        ]);
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

        return Inertia::render('StoreFront/HotSale', [
            'products' => $products,
            'filters' => $filters,
            'categories' => $categories,
            'seo' => $this->seoMeta([
                'title' => Setting::get('seo_hot_sale_title', 'Hot Sale'),
                'description' => Setting::get('seo_hot_sale_description', Setting::get('company_description', '')),
                'canonical' => route('products.hot-sale'),
                'schema' => ['type' => 'item_list', 'products' => $products->values()->all()],
            ]),
        ]);
    }

    public function product(Product $product)
    {
        $product->load('categories', 'category', 'images', 'variants.image');

        $related = Product::where(function ($q) use ($product) {
            $q->where('category_id', $product->category_id)
                ->orWhereHas('categories', fn ($sub) => $sub->where('categories.id', $product->category_id));
        })->where('id', '!=', $product->id)
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

        $productTitle = $product->title;
        $productDescription = $product->description ?? '';

        return Inertia::render('StoreFront/Products/Show', [
            'product' => $product,
            'related' => $related,
            'seo' => $this->seoMeta([
                'title' => $product->meta_title ?: $this->fillPattern(Setting::get('seo_product_title_pattern', '{title}'), ['title' => $productTitle]),
                'description' => $product->meta_description ?: ($productDescription ?: $this->fillPattern(Setting::get('seo_product_description_pattern', ''), ['title' => $productTitle])),
                'canonical' => route('product.show', $product->slug),
                'og_image' => $product->image_url,
                'og_type' => 'product',
                'schema' => $this->productSchema($product),
            ]),
        ]);
    }

    public function showPage(Page $page)
    {
        abort_unless($page->status, 404);

        return Inertia::render('StoreFront/Pages/Show', [
            'page' => $page,
            'seo' => $this->seoMeta([
                'title' => $page->title,
                'description' => $this->fillPattern(Setting::get('seo_page_description_pattern', ''), ['title' => $page->title]),
                'canonical' => route('pages.show', $page->slug),
            ]),
        ]);
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
            ->map(function ($variant) {
                $options = $variant->options ?? [];

                if (! empty($options) && is_array($options)) {
                    return trim(implode(' / ', array_map(fn ($opt) => $opt['value'], $options)));
                }

                return '';
            })
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

    public function sitemap()
    {
        if (! $this->settingEnabled('seo_enable_sitemap')) {
            abort(404);
        }

        $urls = [
            ['loc' => route('home'), 'lastmod' => now()->toIso8601String(), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => route('products.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => route('products.new-arrivals'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => route('products.hot-sale'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => route('categories.index'), 'priority' => '0.7', 'changefreq' => 'weekly'],
        ];

        Category::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->each(function (Category $category) use (&$urls) {
                $urls[] = [
                    'loc' => route('category.show', $category->slug),
                    'lastmod' => $category->updated_at?->toIso8601String(),
                    'priority' => '0.7',
                    'changefreq' => 'weekly',
                ];
            });

        Product::where('status', 'active')
            ->orderByDesc('updated_at')
            ->get()
            ->each(function (Product $product) use (&$urls) {
                $urls[] = [
                    'loc' => route('product.show', $product->slug),
                    'lastmod' => $product->updated_at?->toIso8601String(),
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                ];
            });

        Page::active()->orderBy('title')->get()->each(function (Page $page) use (&$urls) {
            $urls[] = [
                'loc' => route('pages.show', $page->slug),
                'priority' => '0.5',
                'changefreq' => 'monthly',
            ];
        });

        return response(view('sitemap', compact('urls')))
            ->header('Content-Type', 'application/xml');
    }

    public function robots()
    {
        $custom = (string) Setting::get('seo_robots_custom', '');
        $body = $custom !== '' ? $custom : "User-agent: *\nAllow: /\n";

        if ($this->settingEnabled('seo_enable_sitemap') && ! str_contains($body, 'Sitemap:')) {
            $body .= 'Sitemap: '.route('sitemap')."\n";
        }

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }

    private function settingEnabled(string $key, bool $default = true): bool
    {
        return filter_var(Setting::get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Build the shared SEO metadata passed to the storefront <Head>.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function seoMeta(array $data = []): array
    {
        return [
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'canonical' => $data['canonical'] ?? request()->url(),
            'keywords' => $data['keywords'] ?? Setting::get('seo_keywords', ''),
            'og_image' => $data['og_image'] ?? null,
            'og_type' => $data['og_type'] ?? 'website',
            'twitter_handle' => $data['twitter_handle'] ?? Setting::get('seo_twitter_handle', ''),
            'robots' => $data['robots'] ?? null,
            'schema' => $data['schema'] ?? null,
        ];
    }

    /**
     * Replace {placeholder} tokens in a pattern with entity values.
     *
     * @param  array<string, string>  $values
     */
    private function fillPattern(string $pattern, array $values): string
    {
        foreach ($values as $key => $value) {
            $pattern = str_replace('{'.$key.'}', (string) $value, $pattern);
        }

        return trim($pattern);
    }

    /**
     * @return array<string, mixed>
     */
    private function productSchema(Product $product): array
    {
        $category = $product->category ?? $product->categories->first();

        return [
            'type' => 'product',
            'product' => [
                'name' => $product->title,
                'description' => $product->description ?: $product->meta_description,
                'image' => $product->image_url,
                'sku' => $product->sku,
                'price' => $product->sale_price ?: $product->unit_price,
                'currency' => 'BDT',
                'availability' => $product->total_stock > 0 ? 'InStock' : 'OutOfStock',
            ],
            'breadcrumbs' => $this->categoryBreadcrumbs($category, $product),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function categoryBreadcrumbs(?Category $category, ?Product $product = null): array
    {
        $crumbs = [
            ['name' => 'Home', 'url' => route('home')],
        ];

        $trail = [];
        $current = $category;

        while ($current) {
            $trail[] = $current;
            $current = $current->parent;
        }

        foreach (array_reverse($trail) as $cat) {
            $crumbs[] = ['name' => $cat->name, 'url' => route('category.show', $cat->slug)];
        }

        if ($product) {
            $crumbs[] = ['name' => $product->title, 'url' => route('product.show', $product->slug)];
        }

        return $crumbs;
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
            $query->where(function ($q) use ($cats) {
                $q->whereIn('category_id', $cats)
                    ->orWhereHas('categories', fn ($sub) => $sub->whereIn('categories.id', $cats));
            });
        }

        $dimensionFilters = $this->parseDimensionFilters($request);

        if ($dimensionFilters !== []) {
            $variants = ProductVariant::whereHas('product', function ($q) {
                $q->where('status', 'active');
            })->where('quantity', '>', 0)
                ->whereNotNull('options')
                ->select('id', 'options')
                ->get();

            foreach ($dimensionFilters as $name => $values) {
                $normalizedValues = collect($values)
                    ->map(fn ($value) => mb_strtolower(trim((string) $value)))
                    ->filter(fn ($value) => $value !== '')
                    ->values()
                    ->all();

                if ($normalizedValues === []) {
                    continue;
                }

                $matchingIds = $variants->filter(function ($variant) use ($name, $normalizedValues) {
                    foreach ($variant->options ?? [] as $option) {
                        if ($this->normalizeDimensionName((string) ($option['name'] ?? '')) !== $name) {
                            continue;
                        }

                        if (in_array(mb_strtolower(trim((string) ($option['value'] ?? ''))), $normalizedValues, true)) {
                            return true;
                        }
                    }

                    return false;
                })->pluck('id');

                $query->whereHas('variants', fn ($q) => $q->whereIn('id', $matchingIds));
            }
        }

        return $query;
    }

    /**
     * Parse generic dimension filter params: options[color][]=Red&options[color][]=Blue.
     *
     * @return array<string, list<string>>
     */
    private function parseDimensionFilters(Request $request): array
    {
        $raw = $request->input('options', []);

        if (! is_array($raw)) {
            return [];
        }

        $filters = [];

        foreach ($raw as $name => $values) {
            $name = $this->normalizeDimensionName((string) $name);

            if ($name === '') {
                continue;
            }

            $clean = [];

            foreach ((array) $values as $value) {
                $value = trim((string) $value);

                if ($value !== '') {
                    $clean[] = $value;
                }
            }

            if ($clean !== []) {
                $filters[$name] = $clean;
            }
        }

        return $filters;
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
            ->with('children')
            ->orderBy('name')
            ->get();

        $allIds = $categories->pluck('id')
            ->merge($categories->flatMap->children->pluck('id'))
            ->unique();

        $pivotCounts = DB::table('category_product')
            ->join('products', 'products.id', '=', 'category_product.product_id')
            ->whereIn('category_product.category_id', $allIds)
            ->where('products.status', 'active')
            ->select('category_product.category_id')
            ->selectRaw('count(*) as total')
            ->groupBy('category_product.category_id')
            ->pluck('total', 'category_product.category_id')
            ->map(fn ($value) => (int) $value);

        $fkCounts = Product::where('status', 'active')
            ->whereIn('category_id', $allIds)
            ->select('category_id')
            ->selectRaw('count(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id')
            ->map(fn ($value) => (int) $value);

        $categories->each(function (Category $category) use ($pivotCounts, $fkCounts) {
            $total = (int) $pivotCounts->get($category->id, 0) + (int) $fkCounts->get($category->id, 0);

            if ($category->children->isNotEmpty()) {
                $total += $category->children->sum(fn (Category $child) => (int) $pivotCounts->get($child->id, 0) + (int) $fkCounts->get($child->id, 0));
            }

            $category->products_count = $total;
        });

        $prices = Product::where('status', 'active')
            ->selectRaw('MIN(sale_price) as min_price, MAX(sale_price) as max_price')
            ->first();

        $dimensionRows = ProductVariant::whereHas('product', function ($q) {
            $q->where('status', 'active');
        })->where('quantity', '>', 0)
            ->whereNotNull('options')
            ->get()
            ->flatMap(function ($variant) {
                $options = $variant->options ?? [];
                $inStock = (int) $variant->quantity;

                return collect($options)
                    ->filter(fn ($opt) => isset($opt['name'], $opt['value']))
                    ->filter(fn ($opt) => trim((string) $opt['name']) !== '' && trim((string) $opt['value']) !== '')
                    ->map(fn ($opt) => [
                        'name' => $this->normalizeDimensionName((string) $opt['name']),
                        'value' => trim((string) $opt['value']),
                        'qty' => $inStock,
                    ]);
            });

        $dimensions = $dimensionRows->groupBy('name')->map(function ($rows, $name) {
            $values = $rows->groupBy(fn ($row) => mb_strtolower($row['value']))
                ->map(function ($group) {
                    $qty = $group->sum('qty');
                    $representative = $group->groupBy('value')->map->count()->sortDesc()->keys()->first();

                    return ['value' => $representative, 'qty' => $qty];
                })
                ->filter(fn ($value) => $value['qty'] > 0)
                ->sortBy('value')
                ->pluck('value')
                ->values()
                ->toArray();

            return ['name' => $name, 'values' => $values];
        })->values()
            ->filter(fn ($dimension) => $dimension['values'] !== [])
            ->values()
            ->toArray();

        return compact('categories', 'prices', 'dimensions');
    }

    private function normalizeDimensionName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/(.)\1+/u', '$1', $name) ?? $name;

        return match ($name) {
            'colour', 'coulour', 'ccolor' => 'color',
            default => $name,
        };
    }
}
