# Plan — Storefront SEO (Best-in-class, Admin-Driven)

> Status: **Draft**
> Target: the public **storefront** (Laravel + Inertia v2 + Vue 3) for a reseller e-commerce in **Dhaka, Bangladesh**. Audience 18–50, male/female, Bangladeshi. All meta is **dynamic from the admin panel** — no hard-coded SEO in templates.

---

## 1. Goals

1. Make every storefront page indexable with **unique, keyword-aligned meta** (title + description), correct **canonical URL**, **Open Graph** and **Twitter cards**, and **JSON-LD structured data**.
2. Provide an **XML sitemap** (`/sitemap.xml`) and a proper **robots.txt** that points to it.
3. Give admins a **single SEO configuration page** in the admin panel plus per-product and per-category meta overrides — nothing hard-coded.
4. Target the **Bangladeshi buyer/reseller** with the agreed keyword mix (English primary + Bangla-transliterated terms).

### Decisions (confirmed by owner)

| Question | Decision |
|---|---|
| Metadata scope | **SEO config page + per-product and per-category overrides** (CMS pages use their title/description as fallback) |
| Sitemap | **Built-in XML route, no new package** — served straight from the DB (`spatie/laravel-sitemap` NOT added) |
| Meta rendering | **Frontend-rendered via Inertia `<Head>`** (`FrontEndMaster.vue` global head + per-page heads); controllers pass a `seo` prop |
| Meta language | **English primary + Bangla-transliterated keywords** (e.g. `resell kori`, `chiness`, `nightdress`) in keyword lists and select descriptions |
| Canonical base URL | `APP_URL` / Laravel `url()` helper — **must be set to the production domain** |

---

## 2. Current SEO State (verified 2026-09-15)

| Area | Status |
|---|---|
| `<Head>` in `resources/js/Layouts/Frontend/FrontEndMaster.vue:24-28` | Title from `settings.company_name`, description from `settings.company_description`, favicon only. **No canonical / OG / Twitter / schema.** |
| Per-page `<Head title>` | Present on every storefront page (Home: `Home/Index.vue:27`, Products: `Products/Index.vue:30`, NewArrivals: `NewArrivals.vue:41`, HotSale: `HotSale.vue:41`, Categories: `Categories/Index.vue:22`, auth/pages). Static strings, no per-entity meta. |
| Shared props | `HandleInertiaRequests.php:32` already shares `settings`, `socialMedia`, `pages`, `categories` on every request — good base. |
| `robots.txt` | Stub (`public/robots.txt`: `User-agent: *` + `Disallow:`). No `Sitemap:` line. |
| Sitemap | None. |
| Settings store | `Setting` model (`key`/`value`), `Setting::get/set`, seeded by `SettingsSeeder.php`. Admin update pattern exists (`Admin/SettingController.php` + `Admin/Settings/{Company,SiteConfig}.vue`). |
| Entity meta fields | Product and Category have **no** `meta_title`/`meta_description` columns. |

---

## 3. Keyword Strategy (Bangladeshi Audience)

Keywords are stored as **admin-editable settings** (`seo_keywords`, seeded below) and rendered only inside `<meta name="keywords">`. Canonical spellings decided:

| Group | Keywords (canonical form) |
|---|---|
| Nightwear | `nightdress`, `nighdress` *(typo kept — real search term)*, `night dress in Bangladesh`, `sexy nightdress`, `nightwear for women` |
| Sexy / lingerie | `sexy clothes`, `sexy women clothing`, `women secret wear`, `sexy bra panty set`, `lingerie online Bangladesh` |
| Chinese import | `chinese bra panty`, `chinese nightdress in bangladesh`, `china dress wholesale` |
| Reseller | `reseller website in bangladesh`, `resell kori`, `reseller shop in dhaka`, `reseller order bangladesh`, `wholesale dress in dhaka` |
| Local flavor | `eghuri`, Dhaka + Gulshan area terms, `নাইটড্রেস`, `রিসেল করি` |

**On-page rules:**
- Titles: 50–60 chars, keyword-first, unique per page, `| {site name}` suffix.
- Descriptions: 140–160 chars, first sentence actionable for a BD buyer ("COD available in Dhaka", "reseller price", "অর্ডার করতে WhatsApp").
- Category descriptions already exist (`categories.description`) — render them as the on-page intro AND fall back for `meta_description`.

---

## 4. Meta Template per Page Type

All values resolve from **settings keys**; entity-level `meta_title`/`meta_description` override the pattern. Slogan suffix from `seo.title_suffix`.

| Page | title (setting / fallback) | description (setting / fallback) | canonical |
|---|---|---|---|
| Home | `seo.home_title` → `company_name` | `seo.home_description` → `company_description` | `/` |
| All Products | `seo.products_title` → "Products" | `seo.products_description` → `company_description` | `/products` |
| New Arrivals | `seo.new_arrivals_title` → "New Arrivals" | `seo.new_arrivals_description` → `company_description` | `/new-arrivals` |
| Hot Sale | `seo.hot_sale_title` → "Hot Sale" | `seo.hot_sale_description` → `company_description` | `/hot-sale` |
| Categories index | `seo.categories_title` → "Categories" | `seo.categories_description` → `company_description` | `/categories` |
| Category page | `{category.meta_title \|\| seo.category_title {name}}` | `{category.meta_description \|\| category.description \|\| seo.category_description}` | `/category/{slug}` |
| Product page | `{product.meta_title \|\| seo.product_title {title}}` | `{product.meta_description \|\| seo.product_description {title}}` | `/product/{slug}` |
| CMS page | `{page.title}` | `seo.page_description {title}` | `/page/{slug}` |
| 404 | `seo.not_found_title` → "Page Not Found" | `seo.not_found_description` | — |

**Title suffix:** `seo.title_suffix` (default `| {company_name}`) appended in `FrontEndMaster.vue` unless the title already contains the company name.

**Pagination:** listing pages (`/products`, `/category/...`, `/new-arrivals`) canonical points to the **first page**; paginated URLs (`?page=N`) stay canonically merged via the same canonical tag. Listing pages render `ItemList` JSON-LD of the current page slice.

---

## 5. Data Model & Settings Keys

### 5.1 Entity columns (new migration)
- `products`: add `meta_title` (string, nullable), `meta_description` (text, nullable).
- `categories`: add `meta_title` (string, nullable), `meta_description` (text, nullable).

### 5.2 New settings keys (seeded, admin-editable)
Global: `seo_keywords`, `seo_title_suffix`, `seo_home_title`, `seo_home_description`, `seo_products_title`, `seo_products_description`, `seo_new_arrivals_title`, `seo_new_arrivals_description`, `seo_hot_sale_title`, `seo_hot_sale_description`, `seo_categories_title`, `seo_categories_description`, `seo_category_title_pattern` (`{name}` placeholder), `seo_category_description_pattern`, `seo_product_title_pattern` (`{title}`), `seo_product_description_pattern`, `seo_page_description_pattern`, `seo_not_found_title`, `seo_not_found_description`, `seo_og_image` (file upload or URL), `seo_twitter_handle`, `seo_enable_sitemap` (bool), `seo_robots_custom` (optional robots.txt body), `seo_schema_org` (bool), `seo_schema_website` (bool), `seo_title_suffix`.

---

## 6. Backend Implementation

1. **Migration** — add meta columns (section 5.1); fillable arrays on `Product` and `Category`.
2. **SettingsSeeder** — add the `seo_*` keys seeded with the keyword set in section 3.
3. **Admin routes** (`routes/admin.php`):
   - `GET admin/settings/seo` → `SettingController@seo` (new Inertia page `Admin/Settings/Seo.vue`).
   - `POST admin/settings` already persists any posted keys — **reused as-is** (`SettingController@update`).
4. **Admin sidebar** (`AdminSidebar.vue` → `settingsSubItems`) — add **"SEO Settings"** entry.
5. **Product/Category admin forms** — add `meta_title` / `meta_description` inputs; `ProductController`/`CategoryController` update/validation extended.
6. **StorefrontController** — every render gains a computed `seo` prop (title, description, canonical, ogImage, twitterHandle, keywords). A shared private helper (`seoMeta(...)`) builds it from settings + entity overrides. `Category`/`Product` models append `meta_title`/`meta_description` in their `toArray` for pages (or add to the request).
7. **sitemap.xml route** (`routes/frontend.php`, in the `check-status` group or before it):
   - `GET /sitemap.xml` → `StorefrontController@sitemap`
   - Returns `text/xml` with `<url>` entries: home, products, new-arrivals, hot-sale, categories, each active category, each active product, each active page. `lastmod` from `updated_at`, `priority` from entity kind, `changefreq`.
   - **Honors `seo_enable_sitemap`** (disabled → 404).
8. **robots.txt** — move from `public/` static file to a **route** `GET /robots.txt` → `StorefrontController@robots` (`text/plain`). Default body `User-agent: *\nDisallow:\nSitemap: {url}/sitemap.xml`; optional `seo_robots_custom` setting overrides. Ensure `public/robots.txt` is **removed** so the route wins (Laravel serves `public/` assets first).

---

## 7. Frontend Implementation

### 7.1 `FrontEndMaster.vue` — global head (all storefront pages)
From the `seo` page prop (with current `settings` fallback):
- `<title>` with suffix logic,
- `<meta name="description">`,
- `<meta name="keywords">` (from `seo_keywords`),
- `<link rel="canonical" :href="seo.canonical">`,
- Open Graph: `og:type` (website/article/product), `og:title`, `og:description`, `og:image` (entity image or `seo_og_image`), `og:url`, `og:site_name`, `og:locale` (`bn_BD` primary hint / `en_US`),
- Twitter: `twitter:card` (`summary_large_image`), `twitter:title`, `twitter:description`, `twitter:image`, `twitter:site` (from `seo_twitter_handle`),
- robots meta from `seo_robots_meta` if provided (fetch/noindex),
- JSON-LD **Organization** + **WebSite** blocks (togged by `seo_schema_org` / `seo_schema_website`), injected via a `<Head>` `script type="application/ld+json"` using `JSON.stringify` computed script.

### 7.2 Per-page heads
- **Product page** (`StoreFront/Products/Show.vue`): JSON-LD **Product** (name, image, description, brand→site, offers with price/currency `BDT`, availability `InStock` from `total_stock`, sku) + **BreadcrumbList** (Home → Category → Product). OG `type: product`.
- **Category page** (`Categories/Show.vue`): **ItemList** (products slice) + **BreadcrumbList** (Home → Category). A `h1` with category name + description intro.
- **Listing pages** (Products/NewArrivals/HotSale): **ItemList** of current page slice.
- **CMS pages** & others: title/description only (global head covers the rest).

---

## 8. Verification / Testing (Pest)

- `StorefrontSeoTest`:
  - `GET /` contains the `seo.home_title` value in `<title>` and canonical `/`.
  - `GET /products/…` (product w/ meta) title uses product `meta_title`; canonical `/product/{slug}`.
  - product without meta falls back to product title pattern.
  - `GET /sitemap.xml` returns 200 `text/xml` and contains product/category/page URLs; disabled setting → 404.
  - `GET /robots.txt` returns `Sitemap:` line.
  - `SettingController@update` persists arbitrary `seo_*` keys (existing endpoint).
- Manual: Google Search Console — submit sitemap, inspect products/categories coverage; Screaming Frog spot-check for duplicate titles/canonicals.

---

## 9. Performance & Indexability Notes

- Images already `loading="lazy"` in most storefront components; **product OG image** should only be the primary image to avoid huge payloads.
- Keep sitemap **cached** (`Cache::remember`) with `seo.enable_sitemap` + DB `updated_at` invalidation or a short TTL (60 min) — regenerate when entities change.
- `APP_URL` must match production domain (canonical correctness). Verify on live env.
- Keep `noindex,follow` available as a setting for staging.

---

## 10. Follow-up Checklist (build order)

- [x] SEO plan doc (`docs/storefront-seo-plan.md`)
- [ ] Migration: `meta_title`, `meta_description` on products & categories
- [ ] SettingsSeeder: `seo_*` keys + keyword defaults
- [ ] `SettingController::seo()` + `Admin/Settings/Seo.vue` + sidebar entry
- [ ] Product/Category admin forms: meta fields
- [ ] StorefrontController: `seo` prop per page + `sitemap` + `robots`
- [ ] FrontEndMaster global head: canonical / OG / Twitter / JSON-LD / keywords
- [ ] Product/Category/Listing page JSON-LD + breadcrumbs
- [ ] Remove static `public/robots.txt`
- [ ] Pest feature tests
- [ ] Pint + `php artisan test` + `php artisan wayfinder:generate`