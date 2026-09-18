# Plan — Admin Reseller Order Report + Courier Excel Export

> Status: **Draft**
> Implements the **Reseller Order Report** page at `/admin/reseller-orders/report` (new route, new sidebar entry). Goal: date-filtered reseller order reporting with a one-click courier-service **Excel (.xlsx)** export.

---

## 1. Requirements

1. **Default view:** last 1 month of reseller orders.
2. **Manual filters:** Today · This Week · This Month · Last 1 Month · Custom (start-date → end-date).
3. **Courier Excel (.xlsx) export** of selected (or all filtered) orders with the exact column layout below.

### Decisions (confirmed by owner)

| Question | Decision |
|---|---|
| `Invoice` column content | **`product_name` + variant options** (e.g. `Padded Bra L/White`) — one row per order; multi-item orders join items with `", "` |
| `Amount` column content | `sum(item.sale_price ?? item.unit_price) * quantity` + `order.delivery_charge` |
| `Note` / `Lot` columns | `Note` = literal `"null"`, `Lot` = empty (orders have no note field used in export) |
| Status handling on export | **Skip** `cancelled` / `returned` orders (courier dispatch only) |
| Report table itself | Shows **all** statuses with badges (it is a report); only the export filters statuses |
| Export format | **Excel .xlsx** via `phpoffice/phpspreadsheet` — phone numbers stored as text cells so leading zeros survive; dashboard/report UI unchanged |

---

## 2. Data Mapping (Excel columns → DB)

Header (tab / comma separated, 9 columns):

```
Invoice, Name, Address, Phone, Amount, Note, Lot, Contact Name, Contact Number
```

| Excel column | Source | Example |
|---|---|---|
| Invoice | `ResellerOrderItem.product_name` + `variant.options[].value` (joined, one row per order) | `Padded Bra L/White` |
| Name | `ResellerOrder.customer_name` | `Nasrin Akter` |
| Address | `ResellerOrder.shipping_address` | `7/d, Sector-7 Uttara Dhaka` |
| Phone | `ResellerOrder.mobile` | `01945090085` |
| Amount | sum of `(sale_price ?? unit_price) * quantity` for all items + `ResellerOrder.delivery_charge` (Number) | `660` |
| Note | literal `"null"` | `null` |
| Lot | empty | *(blank)* |
| Contact Name | `Setting::get('company_name')` | `companyname` |
| Contact Number | `Setting::get('company_mobile')` | `01779967919` |

> `Contact Name` / `Contact Number` come from the `settings` table (`company_name` and `company_mobile` keys, seeded by `SettingsSeeder`).

---

## 3. Data Model Reference

### `reseller_orders` table

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | FK → `users` | Reseller who placed the order |
| `customer_name` | string, nullable | End-customer name |
| `mobile` | string, nullable | End-customer phone |
| `order_number` | string, unique | e.g. `RSL-000042` |
| `status` | enum | `pending`, `processing`, `completed`, `cancelled`, `returned` |
| `total_amount` | decimal(10,2) | |
| `delivery_charge` | decimal(10,2) | default `0` |
| `shipping_address` | text | |
| `payment_method` | string | default `cash-on` |
| `payment_status` | enum | `unpaid`, `paid` |
| `notes` | text, nullable | |
| `created_at` | timestamp | Used for date filtering |

### `reseller_order_items` table

| Column | Type | Notes |
|---|---|---|
| `reseller_order_id` | FK → `reseller_orders` | |
| `product_name` | string | Snapshot name at order time |
| `product_id` | FK → `products`, nullable | |
| `product_variant_id` | FK → `product_variants`, nullable | |
| `quantity` | integer | |
| `unit_price` | decimal(10,2) | |
| `sale_price` | decimal(10,2), nullable | |
| `total` | decimal(10,2) | |
| `options` | json, nullable | e.g. `[{"name":"Color","value":"White"}]` |

---

## 4. Implementation

### 4.1 Route — `routes/admin.php`

Add one new GET route inside the existing auth + role middleware group:

```php
Route::get('reseller-orders/report', [ResellerOrderController::class, 'report'])->name('reseller-orders.report');
```

No new controller needed — method goes on the existing `Admin\ResellerOrderController`.

### 4.2 Controller — `app/Http/Controllers/Admin/ResellerOrderController.php`

Add `report()` method (rendered via Inertia):

```php
public function report(Request $request)
{
    $from = $request->date('from') ? $request->date('from')->startOfDay() : now()->subMonth();
    $to   = $request->date('to') ? $request->date('to')->endOfDay() : now();

    $orders = ResellerOrder::with('items.variant')
        ->whereBetween('created_at', [$from, $to])
        ->latest()
        ->get();

    return Inertia::render('Admin/ResellerOrders/Report', [
        'orders' => $orders,
        'from'   => $from->toDateString(),
        'to'     => $to->toDateString(),
    ]);
}
```

### 4.3 Export endpoint — same controller

Add `exportReport()` method returning a streamed `.xlsx` download (server-side PhpSpreadsheet — no JS dependencies needed):

```php
public function exportReport(Request $request)
{
    $from = $request->date('from') ? $request->date('from')->startOfDay() : now()->subMonth();
    $to   = $request->date('to') ? $request->date('to')->endOfDay() : now();
    $selectedIds = $request->input('ids'); // null = all filtered

    $query = ResellerOrder::with('items.variant')
        ->whereBetween('created_at', [$from, $to])
        ->whereIn('status', ['pending', 'processing', 'completed']); // skip cancelled/returned

    if ($selectedIds) {
        $query->whereIn('id', $selectedIds);
    }

    $orders = $query->latest()->get();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Courier Report');

    // Headers
    $headers = ['Invoice', 'Name', 'Address', 'Phone', 'Amount', 'Note', 'Lot', 'Contact Name', 'Contact Number'];
    $sheet->fromArray($headers, null, 'A1');

    $companyName = Setting::get('company_name', '');
    $companyMobile = (string) Setting::get('company_mobile', '');

    $row = 2;
    foreach ($orders as $order) {
        // Build invoice: join all item names with options
        $invoiceParts = $order->items->map(function ($item) {
            $variantOpts = collect($item->options ?? [])->pluck('value')->implode(' / ');
            return trim(implode(' ', array_filter([$item->product_name, $variantOpts])));
        });
        $invoice = $invoiceParts->implode(', ');

        // Amount = sum(item totals) + delivery_charge
        $amount = $order->items->sum(fn ($i) => ($i->sale_price ?? $i->unit_price) * $i->quantity) + $order->delivery_charge;

        $sheet->fromArray([
            $invoice,
            $order->customer_name ?? '',
            $order->shipping_address ?? '',
            $order->mobile ?? '',
            $amount,
            'null',
            '',
            $companyName,
            $companyMobile,
        ], null, "A{$row}");

        // Phone as text so leading zeros survive
        $sheet->getCell("D{$row}")->setValueExplicit($order->mobile ?? '', DataType::TYPE_STRING);

        $row++;
    }

    // Column widths
    foreach (['A','B','C','D','E','F','G','H','I'] as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $filename = 'courier-report-' . now()->format('Y-m-d') . '.xlsx';

    return response()->streamDownload(function () use ($spreadsheet) {
        (new Xlsx($spreadsheet))->save('php://output');
    }, $filename, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
}
```

### 4.4 Page — `resources/js/Pages/Admin/ResellerOrders/Report.vue`

**Layout** (single column, matches admin design system — Tailwind slate/purple palette, dark-mode paired classes, `AdminMaster` wrapper):

1. **Filter bar**
   - Preset chips: `Today` / `This Week` / `This Month` / `Last 1 Month` *(default)* / `Custom`.
   - `Custom` reveals Start date + End date `<input type="date">` pickers.
   - Range computed **client-side in local time** (week starts **Sunday** per BD convention; "Last 1 Month" = 30 days back), sent as query params to the server via `router.get()`.
   - Active preset highlighted; changing preset triggers an Inertia reload with new `from`/`to` query params.
2. **Summary cards** — Orders count, Revenue (sum of `total_amount` excluding cancelled/returned), Items sold (sum of all item quantities).
3. **Orders table**
   - Select-all checkbox + per-row checkbox.
   - Columns: checkbox · Order No (link to `admin.reseller-orders.edit`) · Date · Customer · Phone · Address · Items (count + tooltip/list) · Amount · Status badge (reuse the `statusBadge()` map from `ByUser.vue`).
   - Client-side pagination, 10 rows/page.
   - Empty state when the range has no orders.
4. **Export footer bar**
   - **Export Selected (N)** and **Export All (filtered)** buttons.
   - Both hit `admin.reseller-orders.export-report` via `router.get()` with `from`, `to`, and optional `ids[]` — server returns streamed `.xlsx` download.
   - Export skips `cancelled` / `returned` orders (done server-side); toast reports count of skipped orders.

### 4.5 Sidebar — `resources/js/Layouts/Admin/AdminSidebar.vue`

Add to the `Orders` section `items` array (after "Reseller Orders"):

```js
{
    label: 'Reseller Report',
    route: 'admin.reseller-orders.report',
    pattern: 'admin.reseller-orders.report*',
    icon: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
},
```

Also add to `flatLinks` array for mobile sidebar:

```js
{ label: 'Reseller Report', route: 'admin.reseller-orders.report' },
```

---

## 5. Files to create / modify

| # | File | Change |
|---|---|---|
| 1 | `routes/admin.php` | Add `GET reseller-orders/report` route |
| 2 | `app/Http/Controllers/Admin/ResellerOrderController.php` | Add `report()` + `exportReport()` methods |
| 3 | `resources/js/Pages/Admin/ResellerOrders/Report.vue` | **New** — full report UI + client-side pagination |
| 4 | `resources/js/Layouts/Admin/AdminSidebar.vue` | Add sidebar entries for report page |

No DB migration needed. `phpoffice/phpspreadsheet` is already installed (`composer.json`).

---

## 6. Edge cases handled

- **Empty range** → empty-state UI, export buttons disabled.
- **Order with missing items** → fallback empty cell in Invoice; Amount = `delivery_charge` only.
- **Multi-item orders** → one row per order; `Items` column shows count.
- **Timezone** → presets use the admin's browser local time; server compares against stored `created_at` (UTC) via full ISO instants — acceptable for internal reporting.
- **Large ranges** → Custom range capped at 1 year; fetch-all fine at current volumes.
- **Phone leading zeros** → stored as text cells via `TYPE_STRING` in PhpSpreadsheet, so `01945090085` survives Excel's number parsing.
- **Options field** → `ResellerOrderItem.options` is a JSON array of `{name, value}` objects; values are joined with ` / ` and appended to `product_name`.

---

## 7. Testing checklist

1. Default load = last 1 month, correct totals.
2. Each preset returns the expected window; Custom range works (start > end blocked with validation).
3. Selection: select-all / individual toggle / count label; pagination preserves selection.
4. Export All → `.xlsx` opens in Excel with 9 columns, correct data, phone as text.
5. Export Selected → same, but only selected (eligible) orders.
6. `cancelled` / `returned` orders are excluded from export; toast reports skip count.
7. Dark mode visual pass.
8. Run `vendor/bin/pint --dirty --format agent` after edits.
