# Stock Change Notifications — Implementation Plan

## Overview

When an admin updates a product's quantity in the admin panel, the storefront notifies all reseller users about the stock status change — either "In Stock" (quantity goes from 0 to >0) or "Out of Stock" (quantity goes from >0 to 0). Notifications are stored in the database and displayed via a dropdown bell icon in the storefront header, with a full notifications page for history. Only logged-in resellers see notifications.

---

## How It Works

### Trigger Flow

1. Admin edits a product and changes its quantity
2. `ProductController@update` saves the product
3. `ProductObserver::updated()` fires and compares old vs new quantity
4. If stock status changed (0→>0 or >0→0), a notification is created
5. Notification is saved to the database for all users with `user_type = 'reseller'`
6. Storefront users see the notification in their bell dropdown or notifications page

### What Counts as a Stock Change

- **In Stock**: old quantity was 0 and new quantity > 0 — triggers "In Stock".
- **Out of Stock**: old quantity was > 0 and new quantity is 0 — triggers "Out of Stock".
- **No change**: old == new — no notification.

---

## Database

### `notifications` Table

Laravel's standard notifications table with these columns:

- `id` (uuid, primary key)
- `type` (string — notification class name)
- `notifiable_type` + `notifiable_id` (polymorphic — references the user)
- `data` (JSON — product info, status, message, link, product_image)
- `read_at` (nullable timestamp)
- `created_at` / `updated_at`

Indexes on `(type, created_at)` and `(notifiable_type, notifiable_id)`.

---

## Backend Components

### 1. Notification Class — `StockStatusChanged`

- Lives in `app/Notifications/StockStatusChanged.php`
- Does **not** implement `ShouldQueue` — notifications are saved synchronously so they are immediately visible in the database channel
- Constructor receives the Product and a status string (`in_stock` or `out_of_stock`)
- Serializes to database with:
  - `product_id`, `product_title`, `product_slug`, `product_image`
  - `status` — `in_stock` or `out_of_stock`
  - `message` — human-readable sentence
  - `link` — product URL
- Delivery channel: `database` only

### 2. Model Observer — `ProductObserver`

- Lives in `app/Observers/ProductObserver.php`
- Hooks into the `updated` event on `Product`
- Compares `getOriginal('quantity')` vs current `quantity`
- If status changed, queries all `User` where `user_type = 'reseller'` and sends the notification
- Registered in `AppServiceProvider::boot()`

### 3. Notification Controller

- Lives in `app/Http/Controllers/StoreFront/NotificationController.php`
- `index()` — returns latest 10 notifications for the authenticated user (newest first) plus unread count — used by the dropdown
- `page()` — returns paginated notifications (20 per page) from the last week — used by the full notifications page
- `markAllRead()` — marks all unread notifications as read
- All routes require authentication

### 4. Routes

Added to `routes/frontend.php` under auth middleware:

- `GET /notifications` — fetch 10 latest notifications (JSON, for dropdown)
- `GET /notifications/all` — full notifications page (Inertia, 20 per page, last week only)
- `POST /notifications/read` — mark all as read

---

## Frontend Components

### 1. Composable — `useNotifications.js`

- Manages shared state: notifications array, unread count, loading, open/close
- `fetchNotifications()` — calls the GET endpoint
- `markAllRead()` — calls the POST endpoint, updates local state
- `toggle()` — opens dropdown, fetches fresh data, auto-marks as read if there are unread items

### 2. Component — `NotificationDropdown.vue`

- Bell icon with red badge showing unread count
- Click toggles a dropdown panel
- Panel shows:
  - Header with "Notifications" title and "Mark all read" button
  - List of up to 10 notifications, each with 3-line layout:
    - Product title (truncated)
    - Status text ("In Stock" / "Out of Stock")
    - Relative time (e.g., "2 hours ago")
  - Each notification links to the product page
  - "View All" link to the full notifications page
  - Empty state when no notifications exist
- Custom scrollbar styling
- Closes on outside click
- Only renders for authenticated users

### 3. Notifications Page — `Pages/StoreFront/Notifications/Index.vue`

- Full-page view accessible from the dropdown's "View All" link
- Displays notifications from the last week, paginated at 20 per page
- Each notification shows:
  - Product image thumbnail (48x48 rounded, with fallback icon)
  - Status badge overlay on the image (green checkmark for in-stock, red X for out-of-stock)
  - Product title and status text
  - Relative time
- Pagination controls at the bottom

### 4. Header Integration

- `NotificationDropdown` is added to the storefront header (`resources/js/Layouts/Frontend/Header.vue`)
- Placed before the user menu / wishlist area
- Only visible when `auth.user` exists

---

## Files Created

| File | Purpose |
|------|---------|
| `database/migrations/2026_08_28_020716_create_notifications_table.php` | Notifications table |
| `app/Notifications/StockStatusChanged.php` | Notification class |
| `app/Observers/ProductObserver.php` | Detects quantity changes |
| `app/Http/Controllers/StoreFront/NotificationController.php` | Returns notifications |
| `resources/js/composables/useNotifications.js` | Frontend state management |
| `resources/js/Components/StoreFront/NotificationDropdown.vue` | Bell + dropdown UI |
| `resources/js/Pages/StoreFront/Notifications/Index.vue` | Full notifications page |
| `tests/Feature/StockNotificationTest.php` | 9 passing tests |

## Files Edited

| File | Change |
|------|--------|
| `app/Providers/AppServiceProvider.php` | Register ProductObserver |
| `routes/frontend.php` | Add notification routes |
| `resources/js/Layouts/Frontend/Header.vue` | Add NotificationDropdown |

---

## Key Decisions

- **All resellers notified** — every user with `user_type = 'reseller'` gets the notification
- **Database driver only** — no broadcasting/WebSocket needed, simple and reliable
- **Synchronous dispatch** — not queued, so notifications are immediately visible in the database
- **Auto-mark on open** — clicking the bell icon marks all notifications as read
- **10 notification limit** in dropdown — "View All" links to the full paginated page
- **Last week only** on the full page — keeps the view focused on recent activity
