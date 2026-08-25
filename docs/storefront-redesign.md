# Storefront Redesign — "Quiet Luxury"

Redesign of the customer-facing storefront to match the approved mockup
(muted rose/charcoal palette, Inter font, Material Symbols icons,
Pinterest-style hero grid). Mobile-first markup with full dark-mode support.

Reference mockup: "Radiant Picks - Quiet Luxury" HTML (provided in chat).

---

## 1. Design Tokens (`tailwind.config.js`)

Keep `darkMode: 'class'`. Add to `theme.extend`:

```js
colors: {
    // Brand
    primary: '#82514e',            // muted rose
    'primary-container': '#e3a6a1',
    'on-primary-container': '#673a37',
    charcoal: '#2D2D2D',
    ivory: '#FFFFFF',
    'sale-price': '#D97B73',
    'muted-gold': '#C5A059',
    // Surfaces (light values; pair with dark: classes in markup)
    surface: { DEFAULT: '#fff8f7', dim: '#e2d8d6', bright: '#fff8f7' },
    'surface-container': {
        DEFAULT: '#f1f1f1',
        low: '#f4f4f4',
        high: '#ebebeb',
        highest: '#e0e0e0',
        lowest: '#ffffff',
    },
    'surface-variant': '#ebe0df',
    'on-surface': '#1f1a1a',
    'on-surface-variant': '#514442',
    outline: { DEFAULT: '#847372', variant: '#d6c2c0' },
},
spacing: {
    'margin-mobile': '16px',
    'margin-desktop': '64px',
    gutter: '24px',
},
fontFamily: {
    // IMPORTANT: `sans` stays Figtree so the Admin panel keeps its look.
    // Storefront applies `font-inter` at the FrontEndMaster root instead.
    sans: ['Figtree', ...defaultTheme.fontFamily.sans],
    inter: ['Inter', ...defaultTheme.fontFamily.sans],
},
```

### Dark mode strategy

Every light token is paired with a dark class in markup:

| Light | Dark |
|---|---|
| `bg-white` / `bg-background` | `dark:bg-[#171212]` |
| `bg-surface-container` / `-low` | `dark:bg-[#241d1c]` / `dark:bg-[#201a19]` |
| `text-charcoal` / `text-on-surface` | `dark:text-[#f9eeed]` |
| `text-on-surface-variant` | `dark:text-[#cbb8b6]` |
| `border-surface-container-high` | `dark:border-[#3a302e]` |
| `text-primary` | `dark:text-[#f6b7b2]` (inverse-primary) |

Existing dark-mode toggle in Header stays; it toggles `.dark` on `<html>`
(localStorage) as today.

---

## 2. Fonts & Icons

**`resources/views/app.blade.php`:**

```html
<!-- add next to existing font links -->
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700&display=swap" rel="stylesheet">
```

**`resources/css/app.css`** (new utilities):

```css
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24;
}
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
```

---

## 3. Header (`resources/js/Layouts/Frontend/Header.vue`)

Restyle only — all existing behavior preserved (search overlay, cart drawer,
wishlist count, auth menu, dark toggle, mobile search).

- Bar: `sticky top-0 z-40 bg-white dark:bg-[#171212] border-b border-surface-container-high dark:border-[#3a302e]`
- Logo: company logo image, else `<span material-symbols-outlined text-primary>auto_awesome</span> + name`
- Desktop nav links (hidden md:flex): first-level categories from
  `page.props.categories` if available, else static All Products /
  New Arrivals / Hot Sale links.
- Search: pill input style
  `rounded-full bg-surface-container-low border border-surface-container-high`
  (restyled SearchBox wrapper).
- Right side: Favorites (heart + count), Cart (icon + count), Sign In pill
  button (`bg-charcoal text-white rounded-full`) or avatar dropdown.
- Mobile: hamburger opens existing slide-over drawer (restyle surfaces to
  quiet-luxury tokens), mobile search row kept.

## 4. Footer (`resources/js/Layouts/Frontend/Footer.vue`)

Restyle per mockup light footer; keep social media bar, contact info,
pages links, mobile bottom nav, WhatsApp float.

- `bg-white dark:bg-[#171212] border-t border-surface-container-high pt-16 pb-8`
- Grid: brand blurb (`md:col-span-1`) + link columns from `pages`
  + contact block (email/mobile/address/hours).
- Bottom bar: copyright + page links.
- Mobile bottom nav: white surface, charcoal icons, primary active state.

---

## 5. Home Page Partials

New folder: `resources/js/Pages/StoreFront/Home/Partials/`

Each section = one partial component. **Mobile-first**: base styles are the
phone layout, `md:` variants expand. All include `dark:` pairs.

### 5.1 `HeroGrid.vue` (replaces HeroSlider on Home)
Pinterest-style bento grid driven by real data:
- Props: `categories` (top 3 with images), fallback gradient placeholders.
- Layout:
  - Mobile: vertical stack of tiles (`grid-cols-1`).
  - `md:` `grid-cols-4 auto-rows-[250px] lg:auto-rows-[300px] gap-4`:
    - Tile A `md:col-span-2 md:row-span-2` — "Our Starting Lineup / New
      Arrivals" over first category image, left gradient scrim,
      `View Collection` charcoal pill → `route('products.new-arrivals')`.
    - Tile B/C `md:col-span-1` — categories 2 & 3, bottom scrim, name +
      underlined `Discover Now`.
    - Tile D `md:col-span-2` — wide tile, `route('products.index')`.
- Hover: image `scale-105` transition (group-hover).

### 5.2 `TopCategories.vue` (replaces CategoryGrid usage)
- Props: `categories`.
- Centered heading: "Top Categories" + subtitle.
- Horizontal scroll row (`flex overflow-x-auto gap-6 pb-4 no-scrollbar`),
  snap on mobile; cards `w-[200px] md:w-[240px]`, image card
  (`rounded-2xl bg-surface-container aspect-[4/5]`) with overlapping white
  pill label (`shadow-md rounded-full`), whole card is a Link to
  `route('category.show', slug)`.

### 5.3 `BestSellers.vue` (replaces TopSellingProducts usage)
- Props: `products`.
- Centered heading "Best Sellers Products" + subtitle.
- Static grid (no slider): `grid-cols-2 md:grid-cols-4 gap-6`;
  4th card hidden on mobile (`hidden md:block` when >3 items).
- Uses restyled `HomeProductCard`.

### 5.4 `NewArrivalsRow.vue` (keeps New Arrivals section, same visual language)
- Props: `newArrivals`. Same heading/grid pattern as BestSellers
  ("New Arrivals", view-all → `route('products.new-arrivals')`).

### 5.5 `BoutiqueBanner.vue` (new, static content from mockup)
- Split banner: image half (`aspect-square md:h-[500px] object-cover`) +
  text half: eyebrow ("NEW FOR 2026"), H2 "Boutique Style", paragraph,
  `View Collection` charcoal pill.
- Card container `rounded-3xl bg-surface-container dark:bg-[#241d1c] overflow-hidden`,
  stacks vertically on mobile.

### 5.6 Existing dynamic sections (kept, harmonized)
InstagramGallery, NoticeSection, FaqSection remain on the page below the new
sections (admin-managed content). Only their section headings get token
updates so they don't clash; full redesign can follow later.

---

## 6. Product Card (`Components/StoreFront/HomeProductCard.vue`)

Restyled to mockup while keeping logic (guest price gate, stock, swatches):

- Image: `rounded-xl bg-surface-container aspect-[3/4] overflow-hidden`,
  hover scale; keep discount/out-of-stock badges (token colors).
- Meta row: color swatch dots (`w-6 h-6 rounded-full border-outline-variant`)
  + review/stock hint text.
- Title: `font-bold text-charcoal dark:text-[#f9eeed]`.
- Price: sale price `text-sale-price font-bold` + strikethrough original
  (`text-outline line-through`); plain `text-charcoal` when no discount;
  guest sees existing login prompt link.
- Wrapper: flat card, no heavy borders/shadows (quiet-luxury look);
  subtle hover lift only.

---

## 7. Home Composition (`Pages/StoreFront/Home/Index.vue`)

```vue
<FrontEndMaster>
    <HeroGrid :categories="categories" />
    <TopCategories :categories="categories" />
    <BestSellers :products="products" />
    <NewArrivalsRow :products="newArrivals" />
    <BoutiqueBanner />
    <InstagramGallery :instagramImages="instagramImages" />
    <NoticeSection :notices="notices" />
    <FaqSection :faqs="faqs" />
</FrontEndMaster>
```

Old HeroSlider/CategoryGrid/ProductGridSlider files stay untouched for other
usages; Home simply stops importing them.

---

## 8. Out of Scope (this pass)

- Products index/show, cart, checkout inner pages (inherit new header/footer
  via FrontEndMaster automatically; inner restyling later).
- Removing Instagram/Notice/FAQ sections.
- Swiper removal project-wide (HeroSlider keeps its file; unused by Home).

## 9. Verification

1. `npm run build` — assets compile.
2. `php artisan test --compact --filter=Storefront` (or existing home tests).
3. Manual smoke via `php artisan serve`: light/dark toggle, mobile drawer,
   search overlay, cart drawer, wishlist counts, guest price gate,
   WhatsApp float, bottom nav.
