# Sports Junction — Super Admin & Master CRUD Management Module

> **India's Sports Manufacturing Marketplace**  
> Complete Production-Ready Super Admin Authentication & Master CRUD Management Platform.

---

## 1. Overview & Credentials

Sports Junction is a specialized B2B marketplace connecting Indian sports equipment manufacturers (e.g., cricket gear clusters in Meerut, inflatable football clusters in Jalandhar, apparel, racquets, protective gear) with global buyers, wholesalers, and sporting brands.

### Super Admin Credentials
- **Portal URL:** `http://localhost/Antigravity/Sports-Junction/admin/login` (or `http://localhost:8000/admin/login`)
- **Email:** `sportjunctions@gmail.com`
- **Password:** `Sport@123`
- **Role:** `SUPER_ADMIN`
- **Password Hashing:** Bcrypt (`cost => 12`)
- **Rate Limiting:** Maximum 5 failed attempts per 15-minute sliding window.

---

## 2. Branding & Design System

The application design reflects the official Sports Junction identity:
- **Logo:** `public/assets/images/logo.jpg`
- **Brand Colors:**
  - Primary Navy Blue: `#1E3A8A` / `#172554`
  - Vibrant Red Accent: `#DC2626` / `#B91C1C`
  - Background & Surface: `#F8FAFC` / `#FFFFFF`
  - Text & Accents: `#0F172A` / `#64748B`
- **Typography & Icons:** Segoe UI / System Font Stack, Bootstrap Icons (`bi-*`).

---

## 3. Database Architecture & Setup

### Database Configuration (XAMPP MySQL)
- Database: `sports_junction`
- Host: `127.0.0.1:3306`
- Unix Socket: `/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock`
- Username: `root`
- Password: *(empty by default in XAMPP)*

### One-Command Setup
To create/reset the database, run migrations, import sample data, and initialize the Super Admin account:

```bash
php bin/setup.php
```

To initialize or verify the Super Admin account without dropping tables:

```bash
php bin/seed_admin.php
```

---

## 4. Master Management Modules

The Admin sidebar features a dedicated **Master Management** section with six dedicated modules:

| Master Entity | Route | Fields | Dependency Rules |
|---|---|---|---|
| **1. Countries** | `/admin/masters/countries` | Country Name, ISO Alpha-2, ISO Alpha-3, Calling Code, Status | Parent to States and Cities. Deletion blocked if protected suppliers/products exist. Cascade allowed on child states/cities with `"DELETE"` confirmation. |
| **2. States** | `/admin/masters/states` | Country, State Name, State Code, Status | Belongs to Country; parent to Cities. Deletion blocked if suppliers/products exist. |
| **3. Cities** | `/admin/masters/cities` | Country, State (dynamic dependent dropdown), City Name, Status | Belongs to State & Country. Deletion blocked if suppliers/products exist. |
| **4. Business Types** | `/admin/masters/business-types` | Business Type Name, Description, Status | Used by Suppliers (e.g. Manufacturer, Wholesaler, Exporter). Deletion blocked if suppliers exist. |
| **5. Price Units** | `/admin/masters/price-units` | Unit Name, Unit Symbol, Description, Status | Used by Products (e.g. Piece, Set, Box, Kg, Ton, Meter, Dozen). Deletion blocked if products exist. |
| **6. MOQ Units** | `/admin/masters/moq-units` | Unit Name, Unit Symbol, Description, Status | Used by Products for minimum order quantity (e.g. Pieces, Sets, Boxes, Kgs, Dozens). Deletion blocked if products exist. |

---

## 5. Key Architecture & Features

### 5.1 Common DataTables
- **Server-side pagination:** Handles datasets of any scale (10, 25, 50, 100 entries per page).
- **Global search:** Full-text searching across all text columns.
- **Sorting:** Multi-column sort ascending/descending.
- **Dynamic Dependent Dropdown:** In City CRUD, selecting a Country loads its States via AJAX (`/api/masters/states-by-country/{countryId}`); changing the Country resets the selected State.
- **Status badges:** Green active badge (`ACTIVE`) and red inactive badge (`INACTIVE`).

### 5.2 Export Functionality
- **Export formats:** CSV, Excel (`.xls` XML spreadsheet), and PDF (clean print-ready layout).
- **Scope control:**
  - *Current Filtered Results:* Respects the active search query and status filters.
  - *All Records:* Exports the complete dataset regardless of pagination.
- **Formula Injection Prevention:** Prepends a single quote (`'`) to values starting with `=`, `+`, `-`, `@`, `\t`, `\r` to protect spreadsheet consumers.

### 5.3 Active / Inactive Status Management
- Checks for active dependencies before deactivating a record.
- If dependent child records exist (e.g., active states for a country, active suppliers for a business type), deactivation is blocked by default with an informational warning modal showing affected counts.

### 5.4 Permanent Hard Delete (Actual Database Deletion)
- **Protected Business Record Shield:** Marketplace records (Suppliers, Vendor accounts, Products, Enquiries) are protected business assets. The backend **strictly rejects** deletion of any master record referenced by an active supplier or product.
- **Eligible Cascade Deletion:** If only sub-master records exist (e.g., Country has States and Cities with no business accounts attached), cascading deletion requires the administrator to explicitly type `"DELETE"` into the confirmation modal.
- **Database Transactions:** All deletions run inside a `PDO::beginTransaction()` transaction. If any step fails, the operation rolls back completely (`PDO::rollBack()`).
- **Audit Trail:** Every deletion records a detailed log in the `audit_logs` table with user ID, affected counts, entity type, and IP address.

---

## 6. Running Automated Tests

Run the full verification test suites from the project root:

```bash
# 1. Functional & Security Test Suite
php tests/run_tests.php

# 2. Web & Controller Integration Test Suite
php tests/web_integration_test.php
```

Both test suites should return `PASSED` with 0 failures.
