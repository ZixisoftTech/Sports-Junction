# Sports Junction — Bug & Defect Tracking Log

**Log Version:** 1.0  
**Audit Period:** October 30, 2026  
**Auditor:** Antigravity Automated QA & Security Audit Suite

---

## Defect Summary Dashboard

| Bug ID | Module | Severity | Summary | Status | Verified In |
|---|---|---|---|---|---|
| **BUG-001** | Database Models | **P1 - High** | PDO `SQLSTATE[HY093]` parameter count mismatch during global search due to repeated named placeholder `:search` with `ATTR_EMULATE_PREPARES=false` | **FIXED & VERIFIED** | `01_auth_security_test.php` |
| **BUG-002** | Bulk Enquiries | **P2 - Medium** | `BulkProductEnquiry::createBulkEnquiry` threw PHP Notice when items passed `quantity` or `title` instead of strict internal keys | **FIXED & VERIFIED** | `03_enquiries_routing_test.php` |
| **BUG-003** | Product Inquiry Routing | **P2 - Medium** | Filter builder `buildAdminFilterClauses` required strict key `strict_source`, allowing unconstrained fallback if called with `source` | **FIXED & VERIFIED** | `03_enquiries_routing_test.php` |
| **BUG-004** | Bulk Product Importer | **P3 - Low** | PHP 8.4 deprecation warning on `fgetcsv()` due to omitted escape character parameter | **FIXED & VERIFIED** | `07_bulk_upload_importer_test.php` |
| **BUG-005** | Admin Responsive Modals | **P2 - Medium** | Modals on smaller laptop and mobile screens exceeded viewport height causing hidden action buttons | **FIXED & VERIFIED** | `admin.css` / Phase 10 |

---

## Detailed Defect Records

### BUG-001: Named Parameter Repetition in MySQL Prepared Statements
- **Module:** `JobApplication`, `SiteFeedback`, `JobOpening`, `ChatConversation`, `Supplier`
- **Severity:** P1 - High (Caused 500 fatal error whenever user searched tables)
- **Steps to Reproduce:**
  1. Perform a search query in candidate applications, site feedback, job openings, chat conversations, or suppliers.
  2. The model appended `WHERE col1 LIKE :search OR col2 LIKE :search` with a single binding `$bindings[':search']`.
  3. Because `PDO::ATTR_EMULATE_PREPARES` is set to `false`, the native MySQL driver rejected repeated placeholder names.
- **Root Cause:** Native MySQL prepared statements require distinct parameter names or positional parameters.
- **Files Affected:**
  - [`app/Models/JobApplication.php`](file:///Applications/XAMPP/xamppfiles/htdocs/Antigravity/Sports-Junction/app/Models/JobApplication.php)
  - [`app/Models/SiteFeedback.php`](file:///Applications/XAMPP/xamppfiles/htdocs/Antigravity/Sports-Junction/app/Models/SiteFeedback.php)
  - [`app/Models/JobOpening.php`](file:///Applications/XAMPP/xamppfiles/htdocs/Antigravity/Sports-Junction/app/Models/JobOpening.php)
  - [`app/Models/ChatConversation.php`](file:///Applications/XAMPP/xamppfiles/htdocs/Antigravity/Sports-Junction/app/Models/ChatConversation.php)
  - [`app/Models/Supplier.php`](file:///Applications/XAMPP/xamppfiles/htdocs/Antigravity/Sports-Junction/app/Models/Supplier.php)
- **Fix Implemented:** Replaced repeated placeholder names with unique sequential placeholders (`:s1`, `:s2`, `:fb1`, `:search1`, etc.) bound individually in loops.
- **Retest Result:** **PASSED**. Search queries with SQL injection strings, whitespace, and keywords now execute flawlessly.

### BUG-002: Bulk Enquiry Item Key Tolerance
- **Module:** `BulkProductEnquiry`
- **Severity:** P2 - Medium
- **Steps to Reproduce:** Submitting a bulk inquiry with items containing `'quantity'` instead of `'target_quantity'`, or `'title'` instead of `'product_name'` triggered undefined array index notices.
- **Root Cause:** Strict associative array key indexing without null-coalescing fallbacks.
- **Files Affected:** [`app/Models/BulkProductEnquiry.php`](file:///Applications/XAMPP/xamppfiles/htdocs/Antigravity/Sports-Junction/app/Models/BulkProductEnquiry.php)
- **Fix Implemented:** Added null coalescing fallbacks `$item['target_quantity'] ?? $item['quantity'] ?? 1` and `$item['product_name'] ?? $item['title'] ?? ''`.
- **Retest Result:** **PASSED**. Validated in Test Suite 4.

### BUG-003: Product Inquiry Routing Filter Key Tolerance
- **Module:** `ProductInquiry`
- **Severity:** P2 - Medium
- **Steps to Reproduce:** When admin filters passed `['source' => 'PRODUCT_PAGE']`, the filter clause check only verified `!empty($params['strict_source'])`.
- **Root Cause:** Inconsistent key naming between controller inputs (`source`) and internal query builder expectations (`strict_source`).
- **Files Affected:** [`app/Models/ProductInquiry.php`](file:///Applications/XAMPP/xamppfiles/htdocs/Antigravity/Sports-Junction/app/Models/ProductInquiry.php)
- **Fix Implemented:** Normalized source parameter acceptance to support both `$params['strict_source']` and `$params['source']`.
- **Retest Result:** **PASSED**. Validated across all 9 dedicated enquiry routing tests in Test Suite 3.

### BUG-004: PHP 8.4 fgetcsv() Parameter Deprecation
- **Module:** `BulkProductImporter`
- **Severity:** P3 - Low
- **Steps to Reproduce:** Running CSV parsing under PHP 8.4 triggered a deprecation notice because `fgetcsv()` called without the 5th parameter `$escape` is deprecated in PHP 8.4.
- **Root Cause:** `fgetcsv($handle, 0, $delimiter)` omitted explicit enclosure and escape arguments.
- **Files Affected:** [`app/Helpers/BulkProductImporter.php`](file:///Applications/XAMPP/xamppfiles/htdocs/Antigravity/Sports-Junction/app/Helpers/BulkProductImporter.php)
- **Fix Implemented:** Provided explicit `fgetcsv($handle, 0, $delimiter, '"', '\\')`.
- **Retest Result:** **PASSED**. Validated with 0 warnings in Test Suite 7.

### BUG-005: Admin Responsive Modal Viewport Overflow
- **Module:** Super Admin Portal UI / Modals
- **Severity:** P2 - Medium
- **Steps to Reproduce:** On smaller screens (e.g. 1366x768, iPad portrait, or mobile viewports), long modals (CMS editor, popups, inquiry details) overflowed the viewport, making action buttons inaccessible.
- **Root Cause:** Default Bootstrap modal body lacked explicit viewport max-height constraint and touch scrolling.
- **Files Affected:** [`public/assets/css/admin.css`](file:///Applications/XAMPP/xamppfiles/htdocs/Antigravity/Sports-Junction/public/assets/css/admin.css)
- **Fix Implemented:** Added global responsive modal rules: `.modal-dialog-scrollable .modal-body { overflow-y: auto !important; max-height: calc(100vh - 170px); -webkit-overflow-scrolling: touch; }`.
- **Retest Result:** **PASSED**. Modal bodies scroll smoothly on all screen sizes with headers and footers pinned.
