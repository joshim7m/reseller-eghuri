<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;

class FetchCatalog extends Command
{
    protected $signature = 'app:fetch-catalog
        {--category= : Only scrape this category slug}
        {--limit= : Max products per category}
        {--skip-details : Skip fetching product detail pages}';

    protected $description = 'Scrape categories and products from eghuri.com into database/catalog.json';

    private Client $http;

    private array $categories = [];

    private array $products = [];

    private array $seenProductUrls = [];

    public function handle(): int
    {
        $this->http = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ],
        ]);

        $this->info('Fetching homepage for categories...');

        $homepage = $this->fetch('https://eghuri.com');
        $this->parseCategories($homepage);

        $filterCategory = $this->option('category');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $skipDetails = $this->option('skip-details');

        $categoriesToScrape = $filterCategory
            ? array_filter($this->categories, fn ($c) => $c['slug'] === $filterCategory)
            : $this->categories;

        foreach ($categoriesToScrape as $catIndex => $cat) {
            $this->info(PHP_EOL."Category: {$cat['name']} (".($catIndex + 1).'/'.count($categoriesToScrape).')');

            $this->scrapeCategoryProducts($cat, $limit, $skipDetails);
        }

        $catalog = [
            'categories' => $this->categories,
            'products' => $this->products,
        ];

        $json = json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $path = database_path('catalog.json');
        file_put_contents($path, $json);

        $this->info(PHP_EOL.'Done! Wrote '.count($this->categories).' categories and '.count($this->products).' products to database/catalog.json');

        return self::SUCCESS;
    }

    private function fetch(string $url): string
    {
        try {
            $response = $this->http->get($url);

            return (string) $response->getBody();
        } catch (\Exception $e) {
            $this->error("Failed to fetch {$url}: {$e->getMessage()}");

            return '';
        }
    }

    private function parseCategories(string $html): void
    {
        $crawler = new Crawler($html);

        $crawler->filter('li.parent-category')->each(function (Crawler $node) {
            $link = $node->filter('a.menu-category-name');
            $href = $link->attr('href');
            $name = trim($link->text());

            if (! $href || str_contains($href, 'order-track')) {
                return;
            }

            $slug = $this->extractSlug($href, '/category/');
            $imageUrl = $node->filter('img.side_cat_img')->attr('src');

            $subs = [];
            $node->filter('ul.second-nav li.parent-subcategory a.menu-subcategory-name')->each(function (Crawler $subLink) use (&$subs) {
                $subHref = $subLink->attr('href');
                $subs[] = [
                    'name' => trim($subLink->text()),
                    'slug' => $this->extractSlug($subHref, '/subcategory/'),
                ];
            });

            $this->categories[] = [
                'name' => $name,
                'slug' => $slug,
                'url' => $href,
                'image' => $imageUrl,
                'description' => '',
                'subs' => $subs,
            ];

            $this->info("  Found category: {$name} (".count($subs).' subcategories)');
        });
    }

    private function scrapeCategoryProducts(array $category, ?int $limit, bool $skipDetails): void
    {
        $page = 1;
        $totalProducts = 0;

        while (true) {
            $url = $page === 1
                ? $category['url']
                : $category['url'].'?page='.$page;

            $this->info("  Fetching page {$page}: {$url}");

            $html = $this->fetch($url);
            if (empty($html)) {
                break;
            }

            $crawler = new Crawler($html);
            $productNodes = $crawler->filter('div.product_item.wist_item');
            $count = $productNodes->count();

            if ($count === 0) {
                break;
            }

            $productNodes->each(function (Crawler $node) use ($category, $limit, $skipDetails, &$totalProducts) {
                if ($limit && $totalProducts >= $limit) {
                    return;
                }

                $nameNode = $node->filter('.pro_des .pro_name a');
                $name = $this->cleanTitle($nameNode->text());
                $productUrl = $nameNode->attr('href');

                if (! $productUrl || isset($this->seenProductUrls[$productUrl])) {
                    return;
                }

                $this->seenProductUrls[$productUrl] = true;

                $imageUrl = $node->filter('.pro_img img')->attr('src');

                $priceText = $node->filter('.pro_price p')->text('');
                $prices = $this->parsePrices($priceText);

                $product = [
                    'name' => $name,
                    'url' => $productUrl,
                    'image' => $imageUrl,
                    'unitPrice' => $prices['sale'] ?? $prices['original'] ?? 0,
                    'originalPrice' => $prices['original'] ?? 0,
                    'images' => $imageUrl ? [$imageUrl] : [],
                    'description' => '',
                    'category_slug' => $category['slug'],
                ];

                if (! $skipDetails) {
                    $this->info("    Fetching product: {$name}");
                    $this->enrichProductFromDetail($product);
                }

                $this->products[] = $product;
                $totalProducts++;
            });

            $hasMorePages = $crawler->filter('.pagination .page-item a.page-link[rel="next"]')->count() > 0;
            if (! $hasMorePages || ($limit && $totalProducts >= $limit)) {
                break;
            }

            $page++;
            usleep(500000);
        }

        $this->info("  Found {$totalProducts} products in {$category['name']}");
    }

    private function enrichProductFromDetail(array &$product): void
    {
        $html = $this->fetch($product['url']);
        if (empty($html)) {
            return;
        }

        $crawler = new Crawler($html);

        $images = [];
        $crawler->filter('div.dimage_item img')->each(function (Crawler $img) use (&$images) {
            $src = $img->attr('src');
            if ($src && ! in_array($src, $images, true)) {
                $images[] = $src;
            }
        });

        if (count($images) > 0) {
            $product['images'] = $images;
        }

        $descriptionNode = $crawler->filter('div#description');
        if ($descriptionNode->count() > 0) {
            $descHtml = $descriptionNode->html();
            $product['description'] = $this->stripHtml($descHtml);
        }

        $brandNode = $crawler->filter('div.pro_brand p');
        if ($brandNode->count() > 0) {
            $brandText = trim($brandNode->text());
            $product['brand'] = preg_replace('/^Brand\s*:\s*/i', '', $brandText);
        }

        usleep(300000);
    }

    private function extractSlug(string $url, string $prefix): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $slug = str_replace($prefix, '', $path);
        $slug = urldecode($slug);
        $slug = html_entity_decode($slug);
        $slug = trim($slug, '/');

        return $slug;
    }

    private function parsePrices(string $text): array
    {
        $text = str_replace(['৳', ','], '', $text);
        $text = preg_replace('/[\/\-]/', ' ', $text);
        preg_match_all('/\d+/', $text, $matches);

        $prices = array_map('intval', $matches[0]);
        $prices = array_filter($prices, fn ($p) => $p > 0 && $p < 100000);
        $prices = array_values($prices);

        if (count($prices) >= 2) {
            return [
                'original' => $prices[0],
                'sale' => $prices[1],
            ];
        }

        if (count($prices) === 1) {
            return ['sale' => $prices[0]];
        }

        return [];
    }

    private function stripHtml(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function cleanTitle(string $name): string
    {
        $name = html_entity_decode($name, ENT_QUOTES, 'UTF-8');
        $name = trim($name);
        $name = preg_replace('/\s*\.{2,}\s*$/', '', $name);
        $name = preg_replace('/\s*,{2,}\s*$/', '', $name);
        $name = preg_replace('/\s*-{2,}\s*$/', '', $name);
        $name = preg_replace('/\s*\(\s*\)\s*$/', '', $name);
        $name = preg_replace('/\s*\(\d{1,4}\s*$/', '', $name);
        $name = preg_replace('/[\s\-]+\d{2,4}\s*$/u', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name, " \t\n\r\0\x0B-,.");
    }
}
