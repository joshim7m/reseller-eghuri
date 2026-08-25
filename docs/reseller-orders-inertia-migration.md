# Reseller Orders — Blade → Inertia-Vue Migration Plan

Porting the **Reseller Orders + Wallet** feature from the Blade project (`reseller-ecom`) into this Inertia-Vue project (`reseller2`), keeping the existing business logic intact and converting all views to Vue pages.

Source reference implementation: `/home/joshim/Laravel/reseller-ecom` (controllers, Blade views, migrations).

---

## 1. Scope

| Area | Action |
|---|---|
| Admin panel: Reseller Orders (grouped index, per-user day view, edit, status/payment updates) | Port to Inertia + Vue |
| Storefront: Reseller Orders (list, create w/ product picker, show) | Port to Inertia + Vue |
| Storefront: Reseller Transactions (wallet summary + transaction table) | Port to Inertia + Vue |
| Admin sidebar | Add "Reseller Orders" menu entry |
| Database | Create missing tables/columns |
| Models | Small additive fixes (relations/fillable) |
| Everything else (products, categories, orders, checkout, users, settings, …) | **Untouched** |

Business logic (validation rules, wallet math, status transitions, order numbering) is copied **as-is** from the source controllers. Only the response layer changes from Blade `view()` to `Inertia::render()`.

---

## 2. Current State & Gaps (discovered during audit)

Already present in this repo:

- Routes registered: `routes/admin.php` (5 routes under `admin.reseller-orders.*`) and `routes/frontend.php` (`reseller-orders.*`, `reseller-transactions`).
- Controllers copied but still returning Blade views: `app/Http/Controllers/Admin/ResellerOrderController.php`, `app/Http/Controllers/StoreFront/ResellerOrderController.php`.
- Models present: `ResellerOrder`, `ResellerOrderItem`, `Transaction`, `Wallet`; service: `WalletService`.
- Raw Blade copies were dumped into `resources/js/Pages/**` as `.blade.php` files (wrong format) plus Windows `*:Zone.Identifier` junk files.

Gaps to fix:

1. **No migrations** exist for `reseller_orders` / `reseller_order_items`.
2. `transactions` table is **missing the `reseller_order_id` column**, which `WalletService::createPendingTransaction()` writes on every reseller order.
3. `Transaction` model: missing `reseller_order_id` in `$fillable` and the `resellerOrder()` relation.
4. `User` model: missing `wallet()` HasOne relation (`auth()->user()->wallet` is used by the storefront controller).
5. Storefront reseller routes sit **outside** any auth middleware while every controller method calls `auth()->user()` → guests would crash with a PHP error instead of a redirect.
6. The "New Order" product picker needs a dedicated product-search endpoint returning `variants` (for size/color selection) and a cost price — ported from ecom's `api.products.search`.
7. No factories for `ResellerOrder` / `ResellerOrderItem`.

---

## 3. Database Changes

Three new migrations (run `php artisan migrate`):

1. **`create_reseller_orders_table`** — schema identical to source:

   | Column | Type |
   |---|---|
   | `id` | bigIncrements |
   | `user_id` | foreignId → users, cascadeOnDelete |
   | `customer_name` | string, nullable |
   | `mobile` | string, nullable |
   | `order_number` | string, unique |
   | `status` | enum `pending|processing|completed|cancelled`, default `pending` |
   | `total_amount` | decimal(10,2) |
   | `delivery_charge` | decimal(10,2), default 0 |
   | `shipping_address` | text |
   | `payment_method` | string, default `cash-on` |
   | `payment_status` | enum `unpaid|paid`, default `unpaid` |
   | `notes` | text, nullable |
   | + timestamps, index on `status` |

2. **`create_reseller_order_items_table`** — `reseller_order_id` FK cascadeOnDelete, `product_name` string, `quantity` int, `unit_price` decimal(10,2), `sale_price` decimal(10,2) nullable, `total` decimal(10,2), `product_variant_id` nullable FK → product_variants nullOnDelete, `size`/`color` nullable strings, timestamps.

3. **`add_reseller_order_id_to_transactions_table`** — `$table->foreignId('reseller_order_id')->nullable()->after('paymentmethod_name')->constrained('reseller_orders')->cascadeOnDelete()`. Nullable (source used required, but this DB may already hold unrelated rows; nullable is safe and matches all code paths).

---

## 4. Model Adjustments (additive only)

- `App\Models\Transaction`: add `'reseller_order_id'` to `$fillable`; add `resellerOrder(): BelongsTo`.
- `App\Models\User`: add `wallet(): HasOne` (targeting `Wallet.user_id`).
- New factories: `ResellerOrderFactory`, `ResellerOrderItemFactory` (used by tests).

No changes to `WalletService` or `ResellerOrder`/`ResellerOrderItem` models — logic is already correct.

---

## 5. Route Fix

`routes/frontend.php`: move the `reseller-orders` prefix group and the `reseller-transactions` route inside the existing `Route::middleware(['auth', 'check-status'])` group so guests get redirected to login (consistent with profile/orders routes). Route names/URIs unchanged.

---

## 6. Controller Conversion (Blade → Inertia)

### 6.1 `Admin\ResellerOrderController`

| Method | Change |
|---|---|
| `index()` | Same search + date/user grouping. Map grouped collection to a serializable array before render: `[ { date: 'Y-m-d', groups: [ { user_id, user_name, company, count, total } ] } ]`. Render `Admin/ResellerOrders/Index`. |
| `byUser()` | Same eager-load (`items.variant.product.images`). Render `Admin/ResellerOrders/ByUser` with `orders`, `user`, `date`. |
| `edit()` | Same eager-load. Render `Admin/ResellerOrders/Edit`. |
| `update()` | Unchanged (redirect + flash works natively with Inertia). |
| `updateStatus()` | Unchanged. |
| `updatePaymentStatus()` | Unchanged. |
| `handleWalletUpdate()` | Unchanged. |

### 6.2 `StoreFront\ResellerOrderController`

| Method | Change |
|---|---|
| `index()` | Render `StoreFront/ResellerOrders/Index` with `orders` (paginated), `wallet`, `pendingBalance`, `cancelledBalance`. |
| `create()` | Render `StoreFront/ResellerOrders/Create`. |
| `store()` | Unchanged (DB transaction, `RSL-######` number, items, pending wallet transaction). Redirect + flash renders via layout toast. Validation errors flow through `useForm` automatically. |
| `show()` | Ownership check unchanged. Eager-load `items`, `transaction`. Render `StoreFront/ResellerOrders/Show`. |
| `transactions()` | Render `StoreFront/ResellerTransactions/Index` with same props. |

### 6.3 `ResellerOrderController@searchProducts` (dedicated endpoint, faithful port)

Port of ecom's `StorefrontController@searchProductsAjax` (`/api/products/search`, name `api.products.search`) into the reseller controller as `reseller-orders/search-products` (name `reseller-orders.search-products`, registered before the `{resellerOrder}` wildcard). Behavior identical: `q` shorter than 2 chars returns `[]`; active products matched by title only; `take(10)`; payload `{id, title, slug, sale_price, purchase_price (= unit_price), image, variants: [{id, size, color, quantity}]}`. `StorefrontController@searchAjax` (navbar search) stays untouched.

---

## 7. Vue Pages (new files)

Conventions observed in this repo: Ziggy `route()` helper, `<Head>` titles, `AdminMaster` / `FrontEndMaster` layouts (both render flash success/error automatically), Tailwind with dark-mode variants, `useForm` for forms, debounced `router.get` search like `Admin/Orders/Index.vue`.

Delete misplaced blade copies first: `resources/js/Pages/Admin/ResellerOrder/*` and `resources/js/Pages/StoreFront/{ResellerOrders,ResellerTransactions}/*.blade.php`, plus all stray `*:Zone.Identifier` junk files.

### 7.1 Admin

**`resources/js/Pages/Admin/ResellerOrders/Index.vue`**
- Heading + debounced search input (by name/mobile) hitting `admin.reseller-orders.index` with `preserveState/preserveScroll`.
- One card-table per date group; date label computed client-side (Today / Yesterday / `j M, Y`).
- Rows: reseller name (+ company), order count, formatted total, eye icon → `admin.reseller-orders.by-user`.
- Empty state when no orders.

**`resources/js/Pages/Admin/ResellerOrders/ByUser.vue`**
- Back link, title = reseller name, subtitle = date + order count.
- Card per order: order number, status + payment badges, actions dropdown (Edit / Change Status / Change Payment Status).
- Item list: variant image (fallback N/A tile), name, qty, unit/sale prices, size/color; per-order subtotal row (unit vs sale).
- Customer block: name, mobile with clipboard copy, shipping address, delivery charge, notes.
- Status modal + payment modal using the shared `Modal.vue` component; submits via `router.patch` to `admin.reseller-orders.update-status` / `update-payment-status`.

**`resources/js/Pages/Admin/ResellerOrders/Edit.vue`**
- Breadcrumb (Reseller Orders / order_number), back link.
- Left: read-only Order Items card (same item rendering as ByUser).
- Right: Reseller info card (name, mobile + copy, company) and form card: status select, payment status select, total amount, delivery charge, shipping address textarea, payment-method hint, notes display — submitted with `useForm.put` to `admin.reseller-orders.update`, field errors from `form.errors`.

### 7.2 Storefront

**`resources/js/Pages/StoreFront/ResellerOrders/Index.vue`**
- Breadcrumb Home › Reseller Orders; header buttons: Transactions, + New Order.
- Debounced search by order number.
- Wallet Summary card: available balance, pending balance, cancelled balance.
- Paginated list of order cards (number, status badge, total, date, item count) linking to Show; Laravel-style pagination links; empty state CTA.

**`resources/js/Pages/StoreFront/ResellerOrders/Create.vue`** (Alpine `resellerOrderForm` ported to Vue reactivity)
- Product search box: debounced fetch of `route('reseller-orders.search-products')` (?q=, min 2 chars), dropdown results (image, title, price, variant count), click adds item (duplicate increments qty). `useForm` holds items + customer fields so state survives validation-error redirects (parity with Blade `old()`).
- Item editor cards: quantity, sale price with inline validation ("must be ≥ unit price"), size/color selects derived from variants, hidden variant resolution matching size+color pair, line total.
- Customer Information: customer_name*, mobile*, shipping_address*, notes — server-side errors shown via `useForm`.
- Delivery & Summary: area select (Inside Dhaka ৳50 / Outside Dhaka ৳120), totals computed (subtotal, delivery, grand total), submit disabled when no items or price errors; POSTs items[] shape expected by `store()`: `product_id, product_name, quantity, unit_price, sale_price, variant_id, size, color`.

**`resources/js/Pages/StoreFront/ResellerOrders/Show.vue`**
- Breadcrumb Home › Reseller Orders › #order_number; status badge in header.
- Items card with per-item profit line; Summary card: delivery charge, total amount, total profit, payment badge, method, wallet transaction status + amount (when present); Customer card; Notes card; back link.

**`resources/js/Pages/StoreFront/ResellerTransactions/Index.vue`**
- Breadcrumb; + New Order button; wallet summary card (same component markup as orders index).
- Transactions table: Date, Order (link to show when related order exists), Type badge (credit/debit), signed amount, Status badge, Note (truncated); pagination; empty state.

---

## 8. Admin Sidebar

`resources/js/Layouts/Admin/AdminSidebar.vue`:

- Desktop `navItems`: add `{ label: 'Reseller Orders', route: 'admin.reseller-orders.index', pattern: 'admin.reseller-orders.*', icon: <heroicon bag path> }` inside the **Orders** section (after Orders).
- Mobile `flatLinks`: add `{ label: 'Reseller Orders', route: 'admin.reseller-orders.index' }`.

---

## 9. Tests (Pest)

New `tests/Feature/ResellerOrderTest.php` (uses RefreshDatabase + new factories):

- Guest hitting storefront `/reseller-orders` is redirected to login (route middleware fix).
- Authenticated non-reseller gets 403; reseller sees own paginated orders + wallet props.
- Reseller store creates order + items + **pending credit transaction** (profit = Σ(sale−unit)×qty) via `WalletService`.
- Admin index returns the grouped structure; by-user 404s when no orders that date.
- Admin update: setting completed+paid completes the pending transaction and increments wallet credit/balance.
- Admin cancel cancels the transaction (and reverses a completed wallet credit).
- Payment-status patch persists and triggers the same wallet handling.

Follow existing test conventions (factories, Pest `it()/expect()` style used in the repo).

---

## 10. Verification Checklist

1. `php artisan migrate` — new tables/column applied cleanly.
2. `php artisan test --compact --filter=ResellerOrder` — all green.
3. Full suite spot-check: `php artisan test --compact` (no regressions elsewhere).
4. `vendor/bin/pint --dirty --format agent` — formatting.
5. Manual smoke (browser): admin index → by-user → status modal → edit → save; storefront create order → flash → show → transactions reflect pending profit; complete+paid in admin → wallet credited.

---

## 11. Out of Scope / Explicitly Untouched

- All other controllers, models, pages, and routes.
- `UserProfile/invoice-pdf.blade.php` stray file (belongs to another feature).
- Wallet/transaction schema beyond the single added column.
- Wayfinder generation (`@/actions`) — this project's pages use the Ziggy `route()` helper; consistency wins.
