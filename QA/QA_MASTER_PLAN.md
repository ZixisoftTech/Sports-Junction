# Sports Junction — Master QA & Test Plan

**Document Version:** 1.0  
**Date:** October 30, 2026  
**Environment:** Local Development (macOS, PHP 8.4, MySQL 8.0, Timezone: Asia/Kolkata IST)  
**Execution Lead:** Antigravity QA & Engineering Team  
**Confidentiality:** Internal / Sports Junction Marketplace

---

## 1. Executive Summary & Objective

The objective of this Master QA Plan is to execute an aggressive, exhaustive, end-to-end Quality Assurance audit, testing, defect resolution, and regression audit of the **Sports Junction B2B Marketplace**.

The scope encompasses:
1. **Public Website:** Home page, catalog, category navigation, product detail pages, supplier storefronts, 21 CMS & footer pages, contact & support forms, feedback & suggestions, careers & job applications.
2. **Buyer Portal & Workflows:** Registration, email OTP verification, password recovery, profile management, product search & filter, Sort List (bulk selection), bulk enquiry submission, direct product enquiries, prototype sample requests, supplier chat & negotiation (offers/counter-offers), notifications.
3. **Supplier/Manufacturer Portal:** Onboarding & registration, OTP verification, pending approval state, dashboard metrics, product management (single & bulk Excel import), product approval workflow, enquiry reception (product, RFQ, OEM, sample), buyer chat, profile updates.
4. **Super Admin Management Suite:**
   - Security & Authentication (RBAC, session management, CSRF).
   - Real-time Dashboard with accurate KPI counters.
   - Master Management (Countries, States, Cities, Business Types, Price Units, MOQ Units, Product Certificates, Certification Tags).
   - Catalog Management (Categories, Subcategories, Products).
   - Approval Engines (Suppliers, Products, Revision workflow).
   - Buyers & Suppliers Management (Activate, Suspend, Impersonate).
   - Centralized & Dedicated Enquiry Pages (Product Inquiries, RFQ, Fast OEM, Prototype Samples, General, Contact Us, Support, Bulk Product Enquiries, Suggestions & Feedback).
   - Communication & Negotiation Center (Chat monitoring, offer history, dispute resolution).
   - Notification Center (Real-time polling, read status persistence in database).
   - Website Settings, CMS Pages, Hero Banners, Autoload Popups, Dynamic Logo, Seller Agreement, Email Templates & SMTP.
   - Audit Trail (All audit records logged in IST Asia/Kolkata).
5. **Security, Load, Email & UI/UX Audit:**
   - RBAC, IDOR, SQL injection, XSS, file upload validation, resume download protection.
   - Concurrency & load benchmark.
   - Actual email template rendering & SMTP delivery verification.
   - Responsive multi-viewport UI/UX audit.

---

## 2. System Architecture & Testing Environment

- **Server Architecture:** Custom PSR-4 PHP MVC Architecture (`App\Core\Router`, `App\Core\Controller`, `App\Core\Model`, `App\Core\Database`).
- **Database:** MySQL 8.x (`sports_junction`, 42 relational tables, InnoDB, utf8mb4).
- **Timezone:** `Asia/Kolkata` (IST, UTC+05:30) enforced at PHP entry point (`public/index.php`) and MySQL session connection.
- **Frontend Stack:** Tailwind CSS + Bootstrap 5.3 + DataTables + Feather/Bootstrap Icons + Vanilla JS.
- **Storage Subsystems:**
  - Public Assets: `public/uploads/` (`products/`, `categories/`, `banners/`, `popups/`, `certificates/`, `suppliers/`, `settings/`).
  - Secure & Private Documents: `storage/uploads/` (`resumes/`, `feedback/`, `inquiries/`, `communication/`).
- **Notification Subsystems:** In-app notification bell with database persistence (`notifications` table) + AJAX polling (`/api/admin/notifications/poll`).

---

## 3. QA Execution Phases

| Phase | Description | Key Focus Area | Output Deliverable |
|---|---|---|---|
| **Phase 1** | Environment & Application Health | PHP config, DB connectivity, permissions, upload paths, lint check | `QA_EXECUTION_REPORT.md` (Sec 1) |
| **Phase 2** | Super Admin Module Testing | Auth, Dashboard, Masters, Catalog, Approvals, 9 Enquiries, CMS, Audit | `QA_EXECUTION_REPORT.md` (Sec 2) |
| **Phase 3** | Supplier Portal Testing | Registration, Approval, Product CRUD, Bulk Upload, Enquiries, Chat | `QA_EXECUTION_REPORT.md` (Sec 3) |
| **Phase 4** | Buyer Portal Testing | Auth, Catalog, Sort List, Bulk Enquiry, Chat, Enquiries | `QA_EXECUTION_REPORT.md` (Sec 4) |
| **Phase 5** | End-to-End Marketplace Workflows | Workflows 1–9 from supplier onboarding to deal agreement | `E2E_WORKFLOW_REPORT.md` |
| **Phase 6** | Email & SMTP Testing | Delivery, template rendering, OTP, reference IDs, headers | `EMAIL_TEST_REPORT.md` |
| **Phase 7** | Automated Unit & Integration Testing | Model logic, permissions, routing, upload validations | `QA_EXECUTION_REPORT.md` (Sec 5) |
| **Phase 8** | Security Testing | IDOR, XSS, CSRF, SQLi, file upload attacks, access control | `SECURITY_REPORT.md` |
| **Phase 9** | Load & Performance Testing | Concurrency, response times, memory, query optimization | `PERFORMANCE_REPORT.md` |
| **Phase 10** | UI/UX & Responsive Testing | Viewports (360px to 1920px), modals, tables, overflows | `UI_UX_REPORT.md` |
| **Phase 11** | Full Regression Testing | Retest all fixed bugs, verify zero unintended breakages | `REGRESSION_REPORT.md` |
| **Phase 12** | GitHub Push & Production QA Prep | Final verification summary, readiness checklist | `FINAL_QA_SUMMARY.md` |

---

## 4. Defect Severity & Classification

Defects identified during testing are logged according to the following criteria:
- **P0 - Critical (Blocker):** System crash, 500 error, data loss, unauthorized access/privilege escalation, payment/enquiry routing failure. Must be fixed immediately before moving forward.
- **P1 - High:** Major feature failure, broken workflow, unhandled validation exception, inaccurate database metrics, broken email delivery.
- **P2 - Medium:** UI misalignment, missing validation feedback, non-blocking edge-case error, pagination/sorting glitch.
- **P3 - Low:** Minor visual polish, typo, cosmetic spacing.

---

## 5. Entry & Exit Criteria

### Entry Criteria
1. Local database seeded and reachable.
2. PHP extensions verified.
3. Storage directories writable.

### Exit Criteria (Sign-Off)
1. 100% of planned test cases executed.
2. 0 open Critical (P0) or High (P1) defects.
3. All 9 end-to-end business workflows pass.
4. Security and permission tests pass without vulnerabilities.
5. All 11 QA documentation deliverables generated and persisted in `QA/`.
