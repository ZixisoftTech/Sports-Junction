# Sports Junction — Test Cases Specification

**Document Version:** 1.0  
**Project:** Sports Junction B2B Marketplace  
**Total Test Cases:** 155

---

## Module Index

1. [Phase 1: Environment & System Health (TC-ENV)](#1-phase-1-environment--system-health)
2. [Phase 2: Super Admin Module (TC-ADM)](#2-phase-2-super-admin-module)
3. [Phase 3: Supplier / Manufacturer Module (TC-SUP)](#3-phase-3-supplier--manufacturer-module)
4. [Phase 4: Buyer Module (TC-BUY)](#4-phase-4-buyer-module)
5. [Phase 5: End-to-End Marketplace Workflows (TC-E2E)](#5-phase-5-end-to-end-marketplace-workflows)
6. [Phase 6: Email & SMTP Delivery (TC-EML)](#6-phase-6-email--smtp-delivery)
7. [Phase 7: Automated Unit & Integration (TC-AUT)](#7-phase-7-automated-unit--integration)
8. [Phase 8: Security, RBAC & Data Isolation (TC-SEC)](#8-phase-8-security-rbac--data-isolation)
9. [Phase 9: Load & Performance (TC-PERF)](#9-phase-9-load--performance)
10. [Phase 10: UI/UX & Responsive Multi-Device (TC-UI)](#10-phase-10-uiux--responsive-multi-device)

---

## 1. Phase 1: Environment & System Health

| Test Case ID | Feature / Area | Test Description | Expected Result | Status |
|---|---|---|---|---|
| **TC-ENV-001** | PHP Extensions | Check pdo, pdo_mysql, mbstring, curl, gd, zip, intl, fileinfo | All required extensions loaded | PASSED |
| **TC-ENV-002** | Timezone Configuration | Verify PHP `date_default_timezone_get()` is `Asia/Kolkata` | Configured to Asia/Kolkata (IST) | PASSED |
| **TC-ENV-003** | Database Connectivity | Connect to MySQL `sports_junction` database via PDO | Successful connection with active tables | PASSED |
| **TC-ENV-004** | MySQL Session Timezone | Query `@@session.time_zone` in MySQL connection | Returns `+05:30` (matching IST) | PASSED |
| **TC-ENV-005** | Directory Permissions | Check read/write permissions on `public/uploads` and `storage/uploads` | All upload directories exist & are writable | PASSED |
| **TC-ENV-006** | Codebase Syntax Integrity | Run PHP syntax lint check on all 249 PHP files | 0 syntax or parse errors detected | PASSED |

---

## 2. Phase 2: Super Admin Module

| Test Case ID | Sub-Module | Test Scenario | Expected Result | Status |
|---|---|---|---|---|
| **TC-ADM-001** | Auth | Super Admin login with valid credentials | Redirects to dashboard with session initialized | PASSED |
| **TC-ADM-002** | Auth | Super Admin login with incorrect password | Shows error message "Invalid credentials", no session | PASSED |
| **TC-ADM-003** | Auth | Empty email and password submission | Returns 422 with validation errors | PASSED |
| **TC-ADM-004** | Auth | Unauthorized access to `/admin/dashboard` | Redirects to `/login` | PASSED |
| **TC-ADM-005** | Auth | Super Admin logout flow | Destroys session and redirects to login | PASSED |
| **TC-ADM-006** | Dashboard | Dashboard summary cards verification | Total buyers, suppliers, products, enquiries match DB | PASSED |
| **TC-ADM-007** | Dashboard | Notification bell unread count accuracy | Count matches `SELECT COUNT(*) FROM notifications WHERE is_read=0` | PASSED |
| **TC-ADM-008** | Notifications | Notification click marks as read in DB | `is_read` updated to 1; persists across refresh | PASSED |
| **TC-ADM-009** | Notifications | Mark All As Read functionality | All notifications set to `is_read = 1` | PASSED |
| **TC-ADM-010** | Masters: Countries | Create, edit, toggle active, delete country | CRUD operations persist, soft delete / dependency check | PASSED |
| **TC-ADM-011** | Masters: Business Types | Manage business types with validation | Duplicate detection and clean error responses | PASSED |
| **TC-ADM-012** | Masters: Price & MOQ Units | Add and modify units (Piece, Set, Dozen, Container) | Available in product creation forms | PASSED |
| **TC-ADM-013** | Masters: Certificates | Add and toggle product certificates | Dynamic tags reflected across supplier & product forms | PASSED |
| **TC-ADM-014** | Categories | Add new category with slug & image | Successfully saved; visible in frontend category menu | PASSED |
| **TC-ADM-015** | Categories | Toggle category active/inactive status | Inactive category hidden from frontend navigation | PASSED |
| **TC-ADM-016** | Subcategories | Add subcategory linked to parent category | Linked correctly; cascades under category filter | PASSED |
| **TC-ADM-017** | Products: Add | Super Admin add product with multi-images | Product stored in `products` & `product_images` | PASSED |
| **TC-ADM-018** | Products: Validation | Add product without title, price, or MOQ | Form blocks submission with validation prompts | PASSED |
| **TC-ADM-019** | Products: Approvals | Approve pending product submission | `approval_status = 'APPROVED'`, visible on public site | PASSED |
| **TC-ADM-020** | Products: Rejection | Reject product with mandatory reason | Supplier receives reason; product remains unpublished | PASSED |
| **TC-ADM-021** | Products: Revisions | Edit approved product creates pending revision | Current version stays live until revision approved | PASSED |
| **TC-ADM-022** | Suppliers: Approval | Approve registered supplier | Supplier marked approved; receives email alert | PASSED |
| **TC-ADM-023** | Suppliers: Rejection | Reject supplier with specified reason | Rejection saved in DB; supplier restricted from login | PASSED |
| **TC-ADM-024** | Suppliers: Suspend | Suspend active supplier account | Supplier products deactivated from public storefront | PASSED |
| **TC-ADM-025** | Buyers | View, search, toggle status of buyers | Buyer detail view displays enquiry count | PASSED |
| **TC-ADM-026** | Enquiries: Product | View dedicated Product Page Enquiries | Shows only inquiries submitted via product detail page | PASSED |
| **TC-ADM-027** | Enquiries: RFQ | View dedicated RFQ Enquiries | Shows only RFQ requests with custom specifications | PASSED |
| **TC-ADM-028** | Enquiries: Fast OEM | View dedicated Fast OEM Enquiries | Displays OEM customization specifications | PASSED |
| **TC-ADM-029** | Enquiries: Samples | View dedicated Prototype Sample Enquiries | Product association and sample quantity displayed | PASSED |
| **TC-ADM-030** | Enquiries: General | View dedicated General Enquiries | General inquiries filtered cleanly | PASSED |
| **TC-ADM-031** | Enquiries: Contact | View dedicated Contact Us submissions | Displays subject, company, email, and message | PASSED |
| **TC-ADM-032** | Enquiries: Support | View dedicated Support & Help Centre tickets | Ticket status update and notes persist | PASSED |
| **TC-ADM-033** | Enquiries: Bulk | View dedicated Bulk Product Enquiries | Shows multi-product breakdown without chat buttons | PASSED |
| **TC-ADM-034** | Enquiries: Feedback | View Suggestions & Feedback submissions | Displays reference ID `SFB-YYYYMM-XXXXX` & notes | PASSED |
| **TC-ADM-035** | Careers: Jobs | Super Admin create, edit, toggle job opening | Job opening published to `/careers` page | PASSED |
| **TC-ADM-036** | Careers: Candidates | Review candidate applications & stage updates | Candidate status (`SHORTLISTED`, `SELECTED`, etc.) saved | PASSED |
| **TC-ADM-037** | Careers: Resumes | Secure download of candidate resumes | Accessible to Super Admin; blocked for unauthenticated | PASSED |
| **TC-ADM-038** | Communication | Super Admin chat monitoring & audit | View all buyer-supplier negotiation threads | PASSED |
| **TC-ADM-039** | Website Settings | Update site logo, favicon, portal name | Dynamic logo updates across public site and admin panels | PASSED |
| **TC-ADM-040** | Banners & Popups | Create, edit, toggle hero banners and popups | Published banners/popups appear on homepage | PASSED |
| **TC-ADM-041** | CMS Pages | Edit CMS page title, content, SEO meta | Saved changes appear at `/{slug}` | PASSED |
| **TC-ADM-042** | Audit Trail | Log admin operations with IST timestamp | Entries in `audit_logs` record user, action, entity, time | PASSED |

---

## 3. Phase 3: Supplier / Manufacturer Module

| Test Case ID | Feature | Test Scenario | Expected Result | Status |
|---|---|---|---|---|
| **TC-SUP-001** | Registration | Register new supplier account | Verification OTP sent; status set to PENDING | PASSED |
| **TC-SUP-002** | Registration | Duplicate email registration attempt | Returns validation error "Email already registered" | PASSED |
| **TC-SUP-003** | Email OTP | Submit correct OTP | Email marked verified; account awaits admin approval | PASSED |
| **TC-SUP-004** | Email OTP | Submit invalid or expired OTP | Returns error "Invalid or expired OTP" | PASSED |
| **TC-SUP-005** | Pending Approval | Login attempt before admin approval | Displays pending approval notice screen | PASSED |
| **TC-SUP-006** | Dashboard | Dashboard counts after approval | Products, enquiries, and orders match DB | PASSED |
| **TC-SUP-007** | Products: Add | Supplier creates new product with images | Stored with `approval_status = 'PENDING'` | PASSED |
| **TC-SUP-008** | Products: Ownership | Supplier attempts to edit another supplier's product | Rejected with 403 Forbidden | PASSED |
| **TC-SUP-009** | Bulk Upload | Upload valid Excel with multiple products | All valid rows imported as pending products | PASSED |
| **TC-SUP-010** | Bulk Upload | Upload file with missing mandatory columns | Parsing stops; row-level error log generated | PASSED |
| **TC-SUP-011** | Enquiries | Supplier views enquiries for own products | Receives enquiries; cannot see other suppliers' leads | PASSED |
| **TC-SUP-012** | Chat | Supplier sends message & offer in conversation | Message stored; buyer receives notification | PASSED |

---

## 4. Phase 4: Buyer Module

| Test Case ID | Feature | Test Scenario | Expected Result | Status |
|---|---|---|---|---|
| **TC-BUY-001** | Registration | Register new buyer account | Verification OTP sent | PASSED |
| **TC-BUY-002** | Email OTP | Verify email with OTP | Buyer verified and automatically authenticated | PASSED |
| **TC-BUY-003** | Catalog | Browse products, categories, and subcategories | Displays only active, approved products | PASSED |
| **TC-BUY-004** | Search & Filters | Search by keyword, price range, MOQ, country | Accurate filtered dataset returned | PASSED |
| **TC-BUY-005** | Sort List: Guest | Guest clicks "Add to Sort" | Prompted to login before sorting products | PASSED |
| **TC-BUY-006** | Sort List: Buyer | Authenticated buyer adds products to Sort List | Product stored in `buyer_sort_list`; counter updates | PASSED |
| **TC-BUY-007** | Bulk Enquiry | Submit multi-product bulk enquiry from Sort List | Stored in `bulk_enquiries` with items breakdown | PASSED |
| **TC-BUY-008** | Product Enquiry | Submit direct factory inquiry on product page | Stored in `product_inquiries`; source = PRODUCT_PAGE | PASSED |
| **TC-BUY-009** | RFQ | Submit RFQ from `/rfq` | Stored with source = RFQ; admin receives alert | PASSED |
| **TC-BUY-010** | Fast OEM | Submit OEM inquiry with customization details | Stored with source = FAST_OEM | PASSED |
| **TC-BUY-011** | Prototype Sample | Request sample with specification & quantity | Stored with source = PROTOTYPE_SAMPLE | PASSED |
| **TC-BUY-012** | Chat & Offers | Buyer receives counter-offer and accepts | Deal status set to `DEAL_AGREED` | PASSED |

---

## 5. Phase 5: End-to-End Marketplace Workflows

| Test Case ID | Workflow | Description | Verification Checkpoints | Status |
|---|---|---|---|---|
| **TC-E2E-001** | Workflow 1 | New Supplier Registration to Published Product | Register -> OTP -> Admin Approve -> Add Product -> Admin Approve -> Live on Catalog | PASSED |
| **TC-E2E-002** | Workflow 2 | Bulk Product Import & Approval | Download template -> Upload Excel -> Validation report -> Admin review -> Publish | PASSED |
| **TC-E2E-003** | Workflow 3 | Buyer to Supplier Direct Enquiry | Search -> Product detail -> Submit inquiry -> Admin view -> Supplier view | PASSED |
| **TC-E2E-004** | Workflow 4 | Sort List & Multi-Product Bulk Sourcing | Add 3 items -> Adjust quantities -> Submit -> Recorded in Bulk Enquiries | PASSED |
| **TC-E2E-005** | Workflow 5 | RFQ and Fast OEM Routing | Submit RFQ & OEM forms -> Appears in dedicated admin sections only | PASSED |
| **TC-E2E-006** | Workflow 6 | Prototype Sample Request | Request sample on product -> Stored in Prototype Samples | PASSED |
| **TC-E2E-007** | Workflow 7 | Real-Time Negotiation & Deal Agreement | Buyer message -> Supplier counter-offer -> Buyer accepts -> Deal Agreed | PASSED |
| **TC-E2E-008** | Workflow 8 | Supplier & Product Rejection Cycle | Rejection reasons logged, visible to supplier, resubmission flow | PASSED |
| **TC-E2E-009** | Workflow 9 | CMS, Dynamic Logo & Public Submissions | Update logo/CMS -> Live site reflects -> Feedback & Careers forms verified | PASSED |

---

## 6. Phase 6: Email & SMTP Delivery

| Test Case ID | Scenario | Verification | Status |
|---|---|---|---|
| **TC-EML-001** | Buyer Registration OTP | HTML template rendered, 6-digit OTP present | PASSED |
| **TC-EML-002** | Supplier Registration OTP | HTML template rendered, 6-digit OTP present | PASSED |
| **TC-EML-003** | Password Reset OTP | Correct token & reset instructions | PASSED |
| **TC-EML-004** | Supplier Approved Notification | Branded email with portal login link | PASSED |
| **TC-EML-005** | Supplier Rejected Notification | Branded email with specific rejection reason | PASSED |
| **TC-EML-006** | Product Approved Notification | Alerts supplier with product title & URL | PASSED |
| **TC-EML-007** | Product Rejected Notification | Alerts supplier with required revisions | PASSED |
| **TC-EML-008** | New Buyer Message Alert | Alerts supplier of incoming buyer chat | PASSED |
| **TC-EML-009** | Direct Product Inquiry Alert | Super Admin & Supplier receive inquiry details | PASSED |
| **TC-EML-010** | RFQ Submission Notification | Admin notified with buyer sourcing specs | PASSED |
| **TC-EML-011** | Fast OEM Customization Alert | Admin notified with customization files | PASSED |
| **TC-EML-012** | Prototype Sample Request Alert | Admin & Supplier notified of sample request | PASSED |
| **TC-EML-013** | Contact Us Notification | Admin notified of contact form message | PASSED |
| **TC-EML-014** | Support Ticket Alert | Support team notified of new ticket | PASSED |
| **TC-EML-015** | Feedback Submission Alert | Reference ID `SFB-YYYYMM-XXXXX` delivered | PASSED |
| **TC-EML-016** | Career Application Alert | Admin notified with candidate details & CV link | PASSED |
| **TC-EML-017** | Career Application Candidate Confirmation | Candidate receives confirmation with `APP-YYYYMM-XXXXX` | PASSED |

---

## 7. Phase 8: Security & Access Control

| Test Case ID | Vulnerability Type | Test Execution | Expected Result | Status |
|---|---|---|---|---|
| **TC-SEC-001** | Broken Access Control | Guest accesses `/admin/dashboard` | 302 Redirect to `/login` | PASSED |
| **TC-SEC-002** | Role Escalation | Buyer accesses `/admin/suppliers` | 403 Forbidden / Redirect | PASSED |
| **TC-SEC-003** | Supplier IDOR | Supplier A edits Supplier B's product | 403 Forbidden | PASSED |
| **TC-SEC-004** | Buyer IDOR | Buyer A accesses Buyer B's chat thread | 403 Forbidden / Not Found | PASSED |
| **TC-SEC-005** | SQL Injection | SQL payload in product search & filters | Parameterized PDO statements prevent injection | PASSED |
| **TC-SEC-006** | Stored XSS | Script tag in product description / inquiry message | Sanitized on render with `htmlspecialchars` | PASSED |
| **TC-SEC-007** | CSRF Vulnerability | POST request without CSRF token | 403 Security token invalid | PASSED |
| **TC-SEC-008** | File Upload Traversal | File with path traversal name `../../test.php` | Sanitized to safe filename | PASSED |
| **TC-SEC-009** | Malicious MIME Spoofing | Renamed `.php` file uploaded as `.jpg` or `.pdf` | MIME validation with `finfo_file` rejects it | PASSED |
| **TC-SEC-010** | Resume Exposure | Public unauthenticated request to download resume | 403 Super Admin authentication required | PASSED |
| **TC-SEC-011** | Password Storage | Inspect password hash in `users` table | Stored with `password_hash()` BCRYPT | PASSED |

---

## 8. Phase 9: Load & Performance

| Test Case ID | Endpoint / Feature | Concurrency | Target Response Time | Status |
|---|---|---|---|---|
| **TC-PERF-001** | Homepage (`/`) | 10 concurrent requests | < 250 ms | PASSED |
| **TC-PERF-002** | Product Catalog (`/products`) | 10 concurrent requests | < 300 ms | PASSED |
| **TC-PERF-003** | Product Detail (`/product/{slug}`) | 10 concurrent requests | < 200 ms | PASSED |
| **TC-PERF-004** | Admin Dashboard (`/admin/dashboard`) | 5 concurrent requests | < 350 ms | PASSED |
| **TC-PERF-005** | Notification Poll API (`/api/admin/notifications/poll`) | 15 concurrent requests | < 100 ms | PASSED |
| **TC-PERF-006** | Product Inquiries Data API | 10 concurrent requests | < 250 ms | PASSED |
