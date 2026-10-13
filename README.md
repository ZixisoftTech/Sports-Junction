# Sports Junction — India's Premier B2B Sports Manufacturing Marketplace (V1 Final Handover)

> **Version:** `V1.0.0 — Final Production Handover`  
> **Repository:** [https://github.com/ZixisoftTech/Sports-Junction](https://github.com/ZixisoftTech/Sports-Junction)  
> **Architecture:** Custom PSR-4 MVC (PHP 8.2+, MySQL / PDO, Bootstrap 5.3 + DataTables 1.13 for Admin, Tailwind CSS + Material Symbols for Storefront & Portals)

---

## 1. Executive Summary & Locked Requirements

**Sports Junction** is a full-stack B2B sports manufacturing marketplace connecting global sports buyers, academies, importers, and wholesalers directly with audited Indian sports equipment manufacturers (Meerut, Jalandhar, Gujarat, and pan-India clusters).

The platform implements **four locked, role-governed experiences** backed by a unified MySQL database:
1. **Public B2B Storefront & Dynamic Catalog** (`/`, `/products`, `/category/{slug}`, `/product/{slug}`, `/contact`, `/sitemap.xml`)
2. **Buyer Portal** (`/buyer/*` — Dashboard, My Enquiries, Favourites, Recently Viewed, Profile & Security)
3. **Seller / Supplier Portal** (`/supplier/*` — Dashboard, 5-Step Product Submission & Revision Wizard, Customer Enquiries, Factory Profile)
4. **Super Admin Governance Panel** (`/admin/*` — Master Data CRUD, Catalog Management, Supplier Approvals, Product & Revision Approvals, Buyer Management, and Centralized Enquiry Management)

---

## 2. Default Credentials & OTP Configuration

### Super Admin Access
- **Login URL:** `/admin/login` (or `/login`)
- **Email:** `sportjunctions@gmail.com`
- **Password:** `Sport@123`
- **Role:** `SUPER_ADMIN`

### OTP Email Verification Mode (Locked for V1)
- All OTP verification flows (Buyer Registration, Supplier Registration, Email Change Re-verification, and Password Reset) are configured to accept the static master OTP **`123456`** across all environments, while also dispatching HTML emails via SMTP when configured in `.env`.

---

## 3. Complete Feature Matrix (Locked V1 Specification)

### A. Authentication, Onboarding & Security
- **Unified & Role-Specific Sign-In:**
  - `/login` — Unified Sign-In routing `SUPER_ADMIN` to `/admin/dashboard`, approved `SUPPLIER` to `/supplier/dashboard`, unapproved `SUPPLIER` to `/supplier/pending-approval`, and `BUYER` to `/buyer/dashboard`.
  - `/seller/login` & `/supplier/login` — Dedicated Seller Portal sign-in.
  - `/register` — Buyer registration with Country/Company/Mobile validation and OTP verification.
  - `/supplier/register` & `/seller/register` — Multi-field Manufacturer Registration with dynamic Country $\rightarrow$ State $\rightarrow$ City cascading dropdowns (`/api/public/states-by-country/{id}`, `/api/public/cities-by-state/{id}`).
- **Security Hardening:**
  - Bcrypt password hashing (`cost = 12`).
  - Password visibility toggle (eye icon) on all password inputs; Google OAuth removed per V1 specification.
  - Rate limiting (`RateLimiter`) locking repeated failed login attempts for 15 minutes.
  - Strict CSRF validation (`CSRF::validate`) across all state-changing POST/PUT/PATCH/DELETE endpoints.
  - Live database status verification in `BuyerAuthMiddleware` and `SupplierAuthMiddleware` so deactivating or suspending an account takes effect immediately.

---

### B. Buyer Portal (`/buyer/*`)
- **Buyer Dashboard (`/buyer/dashboard`):** Real-time counters and quick access to recent enquiries, saved favourites, and recently viewed products.
- **My Enquiries (`/buyer/enquiries`, `/buyer/enquiries/{id}`):**
  - Displays all enquiries submitted by the buyer (`Product Page`, `General Requirement`, and authenticated `Contact Us` enquiries).
  - Shows Enquiry ID (`#ENQ-XXXXX`), Enquiry Source badge, Product / Requirement, Supplier Name (`Unassigned` if not yet assigned), Quantity, Submission Date, Status, and full two-way Conversation / Reply thread.
- **Saved Favourites (`/buyer/favourites`, `/api/buyer/favourites/toggle`):** Add/remove products from wishlist without duplicates.
- **Recently Viewed (`/buyer/recently-viewed`):** Automatically records authenticated buyer product views (`buyer_recently_viewed`) with one-click history clearing.
- **Profile & Security (`/buyer/profile`):** Update name, company, phone, country, and password. Changing the business email automatically triggers OTP re-verification.

---

### C. Seller / Supplier Portal (`/supplier/*`)
- **Two-Stage Onboarding Gate:**
  1. Register $\rightarrow$ `PENDING_VERIFICATION`
  2. Verify OTP (`123456`) $\rightarrow$ `PENDING_APPROVAL`
  3. Super Admin Approval $\rightarrow$ `APPROVED` (Unlocks `/supplier/dashboard`)
- **5-Step Product Wizard (`/supplier/products/add`, `/supplier/products/edit/{id}`):**
  - Dedicated full-page 5-step wizard matching the Super Admin product form:
    - *Step 1:* Core Identity & Categorization (Name, SKU, Category, Subcategory, Short Description, Full Overview)
    - *Step 2:* B2B Pricing & MOQ (Min/Max Wholesale Price, Price Unit, MOQ Quantity, MOQ Unit, Supply Ability, Bulk Tier Matrix)
    - *Step 3:* Media & Visuals (Primary Image Upload + Multi-Gallery Upload)
    - *Step 4:* Specifications & OEM Readiness (Dynamic Key-Value Technical Specs, Lead Time, Country of Origin, Certifications, OEM/Private Label toggle; *"Highlight as Featured Product"* is strictly hidden from Suppliers and reserved for Super Admin only)
    - *Step 5:* SEO Metadata (URL Slug, Meta Title, Meta Description)
  - Progressive step validation: button displays **"Next"** on Steps 1–4 (blocking progression until required fields in the current step are valid) and **"Save Product"** on Step 5.
- **Safe Revision Workflow:**
  - New supplier products are saved as `PENDING_APPROVAL` / `DRAFT` (hidden from public storefront until Super Admin approves).
  - Editing an already-approved product stores changes in `pending_changes` JSON while keeping the previously approved version live on the public storefront until Super Admin approves the revision.
- **Customer Enquiries (`/supplier/enquiries`, `/supplier/enquiries/{id}`):**
  - Displays all enquiries for products owned by the supplier or explicitly assigned to the supplier by Super Admin.
  - Opening an enquiry automatically transitions its status from `SUBMITTED`/`PENDING` to `VIEWED`; replying transitions it to `RESPONDED`.

---

### D. Super Admin Governance Panel (`/admin/*`)

#### 1. Buyer Management (`/admin/buyers`)
- Server-side searchable, sortable, paginated DataTable of all registered buyers (`role = 'BUYER'`).
- Columns: Buyer ID, Full Name, Company Name, Business Email, Mobile Number, Country, Email Verification Status, Account Status, Registration Date, Actions.
- **Capabilities:**
  - Filter by Account Status (`ACTIVE`, `INACTIVE`, `PENDING_VERIFICATION`), Email Verification (`VERIFIED`, `UNVERIFIED`), and Country.
  - **View Buyer Profile Modal:** Complete buyer details + embedded table of all enquiries submitted by that buyer.
  - **Edit Buyer Details Modal:** Edit name, company, email, phone, country, and status. Changing the email automatically resets verification status to `PENDING_VERIFICATION`.
  - **Activate / Deactivate Buyer:** Blocks login and protected actions immediately without deleting historical enquiries.
  - **Export:** CSV, Excel (`.xls`), and print-ready PDF via `Exporter`.

#### 2. Centralized Enquiry Management (`/admin/enquiries`, `/admin/enquiries/{id}`)
- Unified management of all **3 enquiry sources** in `product_inquiries`:
  1. `PRODUCT_PAGE` — Submitted from a Product Detail page.
  2. `GENERAL` — Submitted from Homepage / Header "Request for Quotation (RFQ)" modal.
  3. `CONTACT_US` — Submitted from the public `/contact` page (`POST /api/contact`).
- **Searchable Supplier Filter & Multi-Filters:**
  - Filter by Supplier (searchable dropdown matching `i.supplier_id = :sid OR p.supplier_id = :sid` or `UNASSIGNED`), Enquiry Source, Enquiry Status (`SUBMITTED`, `VIEWED`, `RESPONDED`, `IN_DISCUSSION`, `RESOLVED`, `CLOSED`), Product Category, Date Range (`start_date` to `end_date`), and Buyer (`?buyer_id={id}`).
- **Server-Side Global Search** across 14 fields: Enquiry ID, Buyer Name, Buyer Company, Buyer Email, Buyer Phone, Product Title, Product ID, Supplier Company/Contact, Subject, Specifications/Message, Delivery Location, Source, and Status.
- **Admin Actions on Enquiry Detail (`/admin/enquiries/{id}`):**
  - Assign or reassign any enquiry to a verified supplier (`/api/admin/enquiries/{id}/assign-supplier`).
  - Update enquiry lifecycle status (`/api/admin/enquiries/{id}/status`) or one-click **Mark as Resolved**.
  - Save confidential **Internal Super Admin Notes** (`admin_notes` — strictly excluded from Buyer/Supplier views and CSV/Excel/PDF exports).
  - Post official Super Admin replies to the enquiry conversation thread.

#### 3. Supplier Approvals & Management (`/admin/suppliers`)
- Full CRUD, Approve, Reject (with reason), Suspend (immediately hides all supplier products from public storefront), Reactivate, and Dependency-Aware Delete (blocks deletion if supplier owns products or enquiries).

#### 4. Product & Revision Approvals (`/admin/product-approvals`, `/admin/products`)
- Super Admin can **View and Edit all 5 steps of product details both before and after approval** (`/admin/products/edit/{id}`).
- Super Admin exclusively controls the **Highlight as Featured Product** flag.
- Approve or Reject new supplier products and pending product revisions (`pending_changes`).

#### 5. Master Data CRUD (`/admin/masters/*`) & Categories (`/admin/categories`, `/admin/subcategories`)
- Countries, States, Cities (cascading), Business Types, Price Units, MOQ Units, Categories, and Subcategories with dependency protection, transaction-safe cascade delete (`"DELETE"` confirmation), and CSV/Excel/PDF exports.

---

## 4. Database Setup & Migrations

### Prerequisites
- PHP 8.2+ with `pdo_mysql`, `mbstring`, `gd` / `fileinfo`
- MySQL 5.7+ / 8.0+ or MariaDB 10.4+ (XAMPP supported out of the box)

### Environment Configuration (`.env`)
Copy `.env.example` to `.env` and configure your database and SMTP settings:
```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost/Antigravity/Sports-Junction/public

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sports_junction
DB_USERNAME=root
DB_PASSWORD=
```

### Running Setup & Migrations
```bash
# 1. Base Schema & Seeders
php bin/setup.php

# 2. Marketplace & Portal Migrations (Idempotent / Safe to run anytime)
php database/marketplace_migration.php
php database/auth_and_approval_migration.php
php database/buyer_seller_portal_migration.php
php database/buyer_enquiry_admin_migration.php
```

---

## 5. Comprehensive QA & Automated Test Suite

The project includes **7 automated QA verification suites** (95+ individual assertions) covering unit, integration, security, workflow, and structural checks.

### Run the Entire V1 Final Handover QA Suite
```bash
php tests/v1_final_qa_suite.php
```

### Individual Test Suites
| Suite File | Coverage Area | Status |
|---|---|---|
| `php tests/run_tests.php` | Super Admin Auth, CSRF, Rate Limiter, 6 Master CRUDs, Dependency Guards, Formula-Safe Exports | **PASS (24/24)** |
| `php tests/marketplace_test.php` | Categories, Subcategories, Product Specs/Tiers, Storefront Rendering, XML Sitemap | **PASS (9/9)** |
| `php tests/validation_and_upload_test.php` | Validator rules, CSRF 419/403 handling, MIME-verified Image Uploader | **PASS (9/9)** |
| `php tests/auth_and_approval_workflow_test.php` | Buyer/Seller Registration, Static OTP (`123456`), Supplier Approval, Product Revisions | **PASS (14/14)** |
| `php tests/buyer_seller_admin_test.php` | Buyer Favourites/History, Seller Portal, Pre/Post-Approval Edits, Supplier Suspension | **PASS (22/22)** |
| `php tests/buyer_enquiry_management_test.php` | Super Admin Buyer Management, Centralized 3-Source Enquiries, Private Admin Notes | **PASS (11/11)** |
| `php tests/v1_final_qa_suite.php` | Master QA Runner + Structural & Featured-Flag Role Restriction Verification | **PASS (7/7 Suites)** |

---

## 6. Production Deployment Checklist

1. Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`.
2. Ensure `public/uploads/` and `storage/` directories are writable (`chmod -R 775 public/uploads storage`).
3. Point your Apache/Nginx VirtualHost `DocumentRoot` to the project root or `public/` directory (both root `.htaccess` and `public/.htaccess` are configured).
4. Verify `php tests/v1_final_qa_suite.php` outputs `7 / 7 TEST SUITES PASSED`.
