# Migration Rename Plan

## Overview

This document outlines the plan to rename all Laravel migrations in the e-commerce project with a clean, organized naming scheme. The goal is to group related migrations together with logical date gaps between groups for better maintainability.

## Status

**COMPLETED** — All migrations renamed and verified with `migrate:fresh --seed`. The tables below reflect the final (new) filenames.

## Current State

**Total Migrations:** 36 files  
**Renamed Groups:**
- **Group 1 (`2025_01_01`)** — Laravel Default Migrations
- **Group 2 (`2025_01_02`)** — Users and User Related
- **Group 4 (`2025_01_12`)** — Catalog Related
- **Group 6 (`2025_01_22`)** — Settings and Others

> Note: Groups 3 (Customers, `2025_01_07`) and 5 (Blog, `2025_01_17`) are intentionally left as empty date gaps in this project. No customer or blog tables exist, so the 5-day gaps are preserved for future insertions.

## New Naming Convention

### Format
```
YYYY_MM_DD_NNN_create_<table_name>_table.php
```

For non-create migrations (add/make/change), the descriptive suffix is kept instead of `create_<table>_table`:
```
YYYY_MM_DD_NNN_add_<description>.php
```

### Rules
1. **Date Format:** `YYYY_MM_DD` - Consistent year-month-day format
2. **Sequence Number:** `NNN` - Three-digit zero-padded sequence number (001-999)
3. **5-Day Gap:** Each group is separated by 5 days (01_01 → 01_02 → 01_12 → 01_22) for easy insertion of future migrations
4. **Group Dates:** `2025_01_01`, `2025_01_02`, `2025_01_07`, `2025_01_12`, `2025_01_17`, `2025_01_22` (6 slots, 4 used)

## Migration Groups

### Group 1: Laravel Default Migrations (2025_01_01)

**Purpose:** Core Laravel framework tables that don't depend on application logic

| Current File | New File | Tables Created |
|--------------|----------|----------------|
| `0001_01_01_000001_create_cache_table.php` | `2025_01_01_001_create_cache_table.php` | cache, cache_locks |
| `0001_01_01_000002_create_jobs_table.php` | `2025_01_01_002_create_jobs_table.php` | jobs, job_batches, failed_jobs |

**Dependencies:** None (foundation tables)

---

### Group 2: Users and User Related (2025_01_02)

**Purpose:** Authentication, authorization, and user management tables

| Current File | New File | Tables Created | Dependencies |
|--------------|----------|----------------|--------------|
| `2025_01_01_000000_create_modules_table.php` | `2025_01_02_001_create_modules_table.php` | modules | None |
| `2025_01_01_000001_create_permissions_table.php` | `2025_01_02_002_create_permissions_table.php` | permissions | modules |
| `2025_01_01_000002_create_roles_table.php` | `2025_01_02_003_create_roles_table.php` | roles | None |
| `2025_01_01_000003_create_permission_role_table.php` | `2025_01_02_004_create_permission_role_table.php` | permission_role | permissions, roles |
| `2025_01_02_000001_create_users_table.php` | `2025_01_02_005_create_users_table.php` | users, password_reset_tokens, sessions | roles |
| `2025_01_02_000002_create_user_details_table.php` | `2025_01_02_006_create_user_details_table.php` | user_details | users |

**Dependencies:** modules → permissions → permission_role ← roles → users → user_details

---

### Group 3: Customers and Customer Related (2025_01_07)

**Status:** Empty (reserved gap). No customer tables in this project. Future customer migrations should use `2025_01_07_NNN`.

---

### Group 4: Catalog Related (2025_01_12)

**Purpose:** Product catalog, categories, orders, invoices, payment processing, and reseller ordering

| Current File | New File | Tables Created | Dependencies |
|--------------|----------|----------------|--------------|
| `2025_01_02_000005_create_categories_table.php` | `2025_01_12_001_create_categories_table.php` | categories | None (self-referencing) |
| `2025_01_02_000006_create_products_table.php` | `2025_01_12_002_create_products_table.php` | products | categories |
| `2025_01_02_000007_create_product_images_table.php` | `2025_01_12_003_create_product_images_table.php` | product_images | products |
| `2025_01_02_000007_create_product_variants_table.php` | `2025_01_12_004_create_product_variants_table.php` | product_variants | products |
| `2026_07_30_031153_create_category_product_table.php` | `2025_01_12_005_create_category_product_table.php` | category_product | categories, products |
| `2026_07_30_000003_add_sort_order_to_product_images.php` | `2025_01_12_006_add_sort_order_to_product_images.php` | — (adds `sort_order` to product_images) | product_images |
| `2026_07_30_022444_add_price_and_image_to_product_variants.php` | `2025_01_12_007_add_price_and_image_to_product_variants.php` | — (adds price columns, `product_image_id` to product_variants) | product_variants, product_images |
| `2026_07_30_032043_make_category_id_nullable_on_products.php` | `2025_01_12_008_make_category_id_nullable_on_products.php` | — (makes `category_id` nullable on products) | products |
| `2025_01_02_000003_create_payment_methods_table.php` | `2025_01_12_009_create_payment_methods_table.php` | payment_methods | None |
| `2025_01_02_000008_create_orders_table.php` | `2025_01_12_010_create_orders_table.php` | orders | users, payment_methods |
| `2025_01_02_000009_create_order_items_table.php` | `2025_01_12_011_create_order_items_table.php` | order_items | orders, product_variants |
| `2025_01_02_000010_create_invoices_table.php` | `2025_01_12_012_create_invoices_table.php` | invoices | users, orders |
| `2026_08_22_023925_create_reseller_orders_table.php` | `2025_01_12_013_create_reseller_orders_table.php` | reseller_orders | users |
| `2026_08_22_023926_create_reseller_order_items_table.php` | `2025_01_12_014_create_reseller_order_items_table.php` | reseller_order_items | reseller_orders, products, product_variants |

**Dependencies:** 
- categories → products → product_images → product_variants
- category_product connects categories ↔ products
- orders depends on users, payment_methods
- order_items depends on orders, product_variants
- invoices depends on users, orders

---

### Group 5: Blog Related (2025_01_17)

**Status:** Empty (reserved gap). No blog/post tables in this project. Future blog migrations should use `2025_01_17_NNN`.

---

### Group 6: Settings and Others (2025_01_22)

**Purpose:** Application settings, content management, financial tracking, and utility tables

| Current File | New File | Tables Created | Dependencies |
|--------------|----------|----------------|--------------|
| `2025_01_02_000005_create_settings_table.php` | `2025_01_22_001_create_settings_table.php` | settings | None |
| `2025_06_09_000003_create_instagram_images_table.php` | `2025_01_22_002_create_instagram_images_table.php` | instagram_images | None |
| `2025_06_09_000004_create_social_media_table.php` | `2025_01_22_003_create_social_media_table.php` | social_media | None |
| `2026_08_01_143748_create_faqs_table.php` | `2025_01_22_004_create_faqs_table.php` | faqs | None |
| `2026_08_01_144747_create_notices_table.php` | `2025_01_22_005_create_notices_table.php` | notices | None |
| `2026_08_04_020621_create_pages_table.php` | `2025_01_22_006_create_pages_table.php` | pages | None |
| `2025_07_1_000001_create_transactions_table.php` | `2025_01_22_007_create_transactions_table.php` | transactions | users |
| `2025_07_1_000002_create_wallets_table.php` | `2025_01_22_008_create_wallets_table.php` | wallets | users, transactions |
| `2026_08_22_023927_add_reseller_order_id_to_transactions_table.php` | `2025_01_22_009_add_reseller_order_id_to_transactions_table.php` | — (adds `reseller_order_id` to transactions) | transactions, reseller_orders |
| `2026_08_16_022709_create_catalog_exports_table.php` | `2025_01_22_010_create_catalog_exports_table.php` | catalog_exports | users |
| `2026_08_16_022710_create_catalog_imports_table.php` | `2025_01_22_011_create_catalog_imports_table.php` | catalog_imports | users |
| `2026_08_28_043737_create_notifications_table.php` | `2025_01_22_012_create_notifications_table.php` | notifications | None (morph) |
| `2026_07_1_200000_add_performance_indexes_to_tables.php` | `2025_01_22_013_add_performance_indexes_to_tables.php` | — (adds indexes) | all referenced tables |
| `2026_08_04_033121_add_second_round_performance_indexes_to_tables.php` | `2025_01_22_014_add_second_round_performance_indexes_to_tables.php` | — (adds indexes) | products, orders, product_images |

**Dependencies:** 
- settings, instagram_images, social_media, faqs, notices, pages are standalone
- transactions depends on users
- wallets depends on users, transactions
- reseller_order_id on transactions depends on reseller_orders (Group 4)
- catalog_exports, catalog_imports depend on users
- notifications uses polymorphic relation (notable)
- performance indexes migrate last (after all referenced tables exist)

## Dependency Graph

```
┌─────────────────────────────────────────────────────────────┐
│                    GROUP 1: LARAVEL DEFAULT                 │
│                         (2025_01_01)                        │
│  ┌─────────────┐    ┌─────────────┐                        │
│  │    cache    │    │    jobs     │                        │
│  └─────────────┘    └─────────────┘                        │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                 GROUP 2: USERS & AUTH                       │
│                       (2025_01_02)                          │
│  ┌─────────┐    ┌────────────┐    ┌─────────┐             │
│  │ modules │───▶│permissions │───▶│  roles  │             │
│  └─────────┘    └────────────┘    └────┬────┘             │
│                                        │                   │
│                                        ▼                   │
│                              ┌─────────────────┐          │
│                              │ permission_role │          │
│                              └────────┬────────┘          │
│                                       │                    │
│                                       ▼                    │
│  ┌─────────┐    ┌─────────────┐                         │
│  │  users  │◀───│user_details │                          │
│  └────┬────┘    └─────────────┘                          │
│       │                                                  │
└───────┼──────────────────────────────────────────────────┘
        │
        ▼
     2025_01_07  (empty reserved gap)
        │
        ▼
┌─────────────────────────────────────────────────────────────┐
│             GROUP 4: CATALOG (2025_01_12)                   │
│                                                             │
│  ┌────────────┐    ┌──────────┐    ┌─────────────┐        │
│  │ categories │───▶│ products │───▶│product_images│        │
│  └──────┬─────┘    └────┬─────┘    └──────┬──────┘        │
│         │               │                  │                │
│         │               ▼                  ▼                │
│         │      ┌─────────────────┐  ┌───────────────┐     │
│         │      │category_product │  │product_variants│     │
│         │      └─────────────────┘  └───────┬───────┘     │
│         │                                    │              │
│         ▼                                    ▼              │
│  ┌──────────────┐    ┌───────────┐    ┌────────────┐      │
│  │payment_methods│   │  orders   │◀───│order_items │      │
│  └──────────────┘    └─────┬─────┘    └────────────┘      │
│                            │                                │
│                            ▼                                │
│                   ┌──────────────┐                         │
│                   │   invoices   │                         │
│                   └──────────────┘                         │
│                                                             │
│  ┌────────────────────┐   ┌───────────────────────┐       │
│  │   reseller_orders  │◀──│reseller_order_items   │       │
│  └────────────────────┘   └───────────────────────┘       │
└─────────────────────────────────────────────────────────────┘
        │
        ▼
     2025_01_17  (empty reserved gap)
        │
        ▼
┌─────────────────────────────────────────────────────────────┐
│           GROUP 6: SETTINGS & OTHERS (2025_01_22)           │
│                                                             │
│  ┌──────────┐  ┌──────────────────┐  ┌─────────────┐      │
│  │ settings │  │instagram_images  │  │ social_media│      │
│  └──────────┘  └──────────────────┘  └─────────────┘      │
│                                                             │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                 │
│  │   faqs   │  │ notices  │  │  pages   │                 │
│  └──────────┘  └──────────┘  └──────────┘                 │
│                                                             │
│  ┌──────────────┐    ┌─────────┐                           │
│  │transactions  │───▶│ wallets │                           │
│  └──────┬───────┘    └─────────┘                           │
│         └──────────────┐                                   │
│  ┌──────────────┐    ┌──────────────┐                     │
│  │reseller_order_id (on transactions)                     │
│  └──────────────┘    └──────────────┘                     │
│                                                             │
│  ┌──────────────────┐  ┌──────────────────┐               │
│  │catalog_exports   │  │catalog_imports   │               │
│  └──────────────────┘  └──────────────────┘               │
│                                                             │
│  ┌─────────────────┐  ┌───────────────────────┐           │
│  │ notifications   │  │ + performance indexes │           │
│  └─────────────────┘  └───────────────────────┘           │
└─────────────────────────────────────────────────────────────┘
```

## Implementation Steps (Completed)

1. **Backup:** Not performed (development environment; `migrate:fresh` is the established workflow).
2. **Git Branch:** `feature/rename-migrations` created.
3. **Fresh Migration:** `php artisan migrate:fresh --seed` — all 36 migrations Ran in Batch [1], no ordering or FK errors.
4. **Verify:** `php artisan migrate:status` confirms all 36 migrations with new names; test suite passing (except pre-existing failures).

## Rollback Plan

If issues arise after renaming:

### Option 1: Git Rollback
```bash
git checkout main
git branch -D feature/rename-migrations
```

### Option 2: Database Reset
```bash
# If database is corrupted
php artisan migrate:fresh --seed
```

### Option 3: Manual Fix
```bash
# Rename files back to original names (see "Current File" columns above)
# Re-run migrations
php artisan migrate:refresh
```

## Benefits

1. **Logical Grouping:** Related tables are grouped together
2. **Clear Dependencies:** Migration order reflects actual dependencies
3. **Easy Insertion:** 5-day gaps allow inserting new migrations within groups
4. **Consistent Format:** Uniform naming pattern across all migrations
5. **Better Readability:** Clean, predictable naming scheme
6. **Future-Proof:** Room for expansion within each group

## Notes

- **No Schema Changes:** Only filenames are changed, not table structures
- **Data Preservation:** Running `migrate:fresh` will lose data; use `migrate:reset` + `migrate` if data must be preserved
- **Testing:** Always run test suite after migration changes
- **Documentation:** Keep this document updated as migrations are added

## Future Migrations

When adding new migrations:

1. **Identify Group:** Determine which group the new table belongs to
2. **Check Dependencies:** Ensure dependent tables exist in earlier groups
3. **Use Gap Numbers:** Use the next free sequence number within the group's date
4. **Update Document:** Add new migration to this plan

### Example: Adding a new "reviews" table
- **Group:** 4 (Catalog Related)
- **Date:** 2025_01_12
- **Sequence:** 015 (after reseller_order_items)
- **New File:** `2025_01_12_015_create_reviews_table.php`

### Example: Adding a new "blog_posts" table
- **Group:** 5 (Blog Related, currently empty)
- **Date:** 2025_01_17
- **Sequence:** 001
- **New File:** `2025_01_17_001_create_blog_posts_table.php`