<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Session;
use App\Models\SiteSetting;
use App\Models\CmsPage;
use Throwable;

class AdminSettingsController extends Controller
{
    private function requireAdmin(): array
    {
        $user = $this->authUser();
        if (!$user || strtoupper($user['role'] ?? '') !== 'SUPER_ADMIN') {
            $this->redirect('/admin/login');
            exit;
        }
        return $user;
    }

    /**
     * GET /admin/settings
     * Super Admin Master Site Settings & Branding
     */
    public function index(): void
    {
        $admin = $this->requireAdmin();
        $settings = SiteSetting::getAllKeyed();
        $pages = CmsPage::all(false);

        $this->render('admin/settings', [
            'pageTitle' => 'Website Settings & Branding — Super Admin',
            'activeMenu' => 'settings',
            'admin' => $admin,
            'settings' => $settings,
            'pages' => $pages,
            'categories' => CmsPage::CATEGORIES
        ], 'admin');
    }

    /**
     * POST /admin/settings/general
     * Save Logo, Contact Info, and Social Media Links
     */
    public function updateGeneral(): void
    {
        $admin = $this->requireAdmin();

        $siteName = trim((string) $this->request->post('site_name', 'Sports Junction'));
        $siteTagline = trim((string) $this->request->post('site_tagline', ''));
        $contactPhone = trim((string) $this->request->post('contact_phone', ''));
        $contactEmail = trim((string) $this->request->post('contact_email', ''));
        $contactAddress = trim((string) $this->request->post('contact_address', ''));
        $workingHours = trim((string) $this->request->post('working_hours', ''));

        $socialFacebook = trim((string) $this->request->post('social_facebook', ''));
        $socialTwitter = trim((string) $this->request->post('social_twitter', ''));
        $socialInstagram = trim((string) $this->request->post('social_instagram', ''));

        // Input Validation
        if (empty($siteName)) {
            Session::flash('error', 'Website name is required.');
            $this->redirect('/admin/settings');
            return;
        }

        if (!empty($contactEmail) && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Please enter a valid business contact email address.');
            $this->redirect('/admin/settings');
            return;
        }

        // Handle Logo Upload
        if (!empty($_FILES['site_logo']['name']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['site_logo'];
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

            if (!in_array($mime, $allowed) && !in_array($ext, $allowedExts)) {
                Session::flash('error', 'Invalid logo image format. Allowed: JPG, PNG, WEBP, SVG.');
                $this->redirect('/admin/settings');
                return;
            }

            if ($file['size'] > 5 * 1024 * 1024) {
                Session::flash('error', 'Logo file size exceeds 5MB limit.');
                $this->redirect('/admin/settings');
                return;
            }

            $uploadDir = dirname(__DIR__, 2) . '/public/uploads/settings';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newFileName = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destPath = $uploadDir . '/' . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                SiteSetting::set('site_logo', '/uploads/settings/' . $newFileName, 'branding');
            }
        }

        // Save settings
        SiteSetting::set('site_name', $siteName, 'branding');
        SiteSetting::set('site_tagline', $siteTagline, 'branding');
        SiteSetting::set('contact_phone', $contactPhone, 'contact');
        SiteSetting::set('contact_email', $contactEmail, 'contact');
        SiteSetting::set('contact_address', $contactAddress, 'contact');
        SiteSetting::set('working_hours', $workingHours, 'contact');

        // Task 11: Exactly three social platforms (Facebook, X, Instagram)
        SiteSetting::set('social_facebook', $socialFacebook, 'social');
        SiteSetting::set('social_twitter', $socialTwitter, 'social');
        SiteSetting::set('social_instagram', $socialInstagram, 'social');
        SiteSetting::set('social_linkedin', '', 'social');
        SiteSetting::set('social_youtube', '', 'social');
        SiteSetting::set('social_tiktok', '', 'social');

        // Authentication & Passcode Mode
        $useStaticOtp = $this->request->post('use_static_otp') ? '1' : '0';
        SiteSetting::set('use_static_otp', $useStaticOtp, 'auth');

        Session::flash('success', 'Website settings, branding, and contact details updated successfully.');
        $this->redirect('/admin/settings');
    }

    /**
     * GET /admin/pages
     * List all dynamic footer & CMS pages
     */
    public function pages(): void
    {
        $admin = $this->requireAdmin();
        $pages = CmsPage::all(false);

        $this->render('admin/cms_pages_list', [
            'pageTitle' => 'Footer & CMS Pages — Super Admin',
            'activeMenu' => 'cms_pages',
            'admin' => $admin,
            'pages' => $pages,
            'categories' => CmsPage::CATEGORIES
        ], 'admin');
    }

    /**
     * POST /admin/pages/create
     * Create new dynamic page
     */
    public function storePage(): void
    {
        $admin = $this->requireAdmin();

        $title = trim((string) $this->request->post('title', ''));
        $slug = trim((string) $this->request->post('slug', ''));
        $cat = trim((string) $this->request->post('column_category', 'TOP_SECTORS'));
        $content = trim((string) $this->request->post('content', ''));
        $url = trim((string) $this->request->post('external_url', ''));
        $metaTitle = trim((string) $this->request->post('meta_title', ''));
        $metaDesc = trim((string) $this->request->post('meta_description', ''));
        $order = (int) $this->request->post('sort_order', 0);
        $isActive = (int) $this->request->post('is_active', 1);

        if (empty($title)) {
            Session::flash('error', 'Page title is required.');
            $this->redirect('/admin/pages');
            return;
        }

        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        }

        // Check if slug exists
        if (CmsPage::findBySlug($slug)) {
            $slug .= '-' . time();
        }

        CmsPage::create([
            'title' => $title,
            'slug' => $slug,
            'column_category' => $cat,
            'content' => $content,
            'external_url' => $url,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDesc,
            'sort_order' => $order,
            'is_active' => $isActive
        ]);

        Session::flash('success', "Page '{$title}' created successfully.");
        $this->redirect('/admin/pages');
    }

    /**
     * POST /admin/pages/edit/{id}
     * Update CMS page
     */
    public function updatePage(array $params = []): void
    {
        $admin = $this->requireAdmin();
        $id = (int) ($params['id'] ?? 0);

        $page = CmsPage::findById($id);
        if (!$page) {
            Session::flash('error', 'Page not found.');
            $this->redirect('/admin/pages');
            return;
        }

        $title = trim((string) $this->request->post('title', ''));
        $slug = trim((string) $this->request->post('slug', ''));
        $cat = trim((string) $this->request->post('column_category', 'TOP_SECTORS'));
        $content = trim((string) $this->request->post('content', ''));
        $url = trim((string) $this->request->post('external_url', ''));
        $metaTitle = trim((string) $this->request->post('meta_title', ''));
        $metaDesc = trim((string) $this->request->post('meta_description', ''));
        $order = (int) $this->request->post('sort_order', 0);
        $isActive = (int) $this->request->post('is_active', 1);

        if (empty($title)) {
            Session::flash('error', 'Page title is required.');
            $this->redirect('/admin/pages');
            return;
        }

        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        }

        // Check slug collision with other pages
        $existing = CmsPage::findBySlug($slug);
        if ($existing && (int)$existing['id'] !== $id) {
            $slug .= '-' . time();
        }

        CmsPage::update($id, [
            'title' => $title,
            'slug' => $slug,
            'column_category' => $cat,
            'content' => $content,
            'external_url' => $url,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDesc,
            'sort_order' => $order,
            'is_active' => $isActive
        ]);

        Session::flash('success', "Page '{$title}' updated successfully.");
        $this->redirect('/admin/pages');
    }

    /**
     * POST /admin/pages/delete/{id}
     * Delete CMS page
     */
    public function deletePage(array $params = []): void
    {
        $admin = $this->requireAdmin();
        $id = (int) ($params['id'] ?? 0);

        CmsPage::delete($id);
        Session::flash('success', 'Page deleted successfully.');
        $this->redirect('/admin/pages');
    }

    /**
     * GET /admin/seller-agreement
     * Dedicated Seller Agreement Management for Super Admin
     */
    public function sellerAgreement(): void
    {
        $admin = $this->requireAdmin();
        $page = CmsPage::findBySlug('seller-agreement');

        if (!$page) {
            $id = CmsPage::create([
                'title' => 'Seller & Manufacturer Agreement',
                'slug' => 'seller-agreement',
                'column_category' => 'PLATFORM_ADMIN',
                'meta_title' => 'Seller & Manufacturer Master Agreement — Sports Junction',
                'meta_description' => 'Official Seller Agreement and manufacturing terms for Sports Junction manufacturers.',
                'content' => '<h2>1. Manufacturer & Supplier Eligibility</h2><p>Sports Junction operates as an authorized B2B sports equipment manufacturing marketplace. By applying for or maintaining a manufacturer account, you represent and warrant that your facility holds authentic business registration licenses, factory inspection certifications, and legal manufacturing rights in your jurisdiction.</p><h2>2. Facility Verification & Super Admin Approval</h2><p>All supplier registrations remain in <strong>Pending Verification</strong> status until verified via business email OTP and subsequently reviewed by the Super Admin trade desk. The Super Admin reserves the right to request proof of factory address, export licenses, or tax documentation before approving any seller profile. Unapproved suppliers cannot upload, publish, or market products.</p><h2>3. Catalog Publishing & Moderation</h2><p>All product additions, bulk spreadsheet imports, and specification revisions undergo mandatory moderation by the Super Admin catalog desk. Products must meet industrial sports quality standards, declare accurate MOQ (Minimum Order Quantity), specify authentic wholesale price tiers in USD, and provide true export packaging dimensions.</p><h2>4. Inquiry & RFQ Fulfillment</h2><p>Suppliers agree to acknowledge and respond to buyer inquiries, requests for quotation (RFQs), and sample requests within 24 to 48 business hours. Suppliers must honor quoted specifications, lead times, and manufacturing tolerances agreed during direct chat negotiations.</p><h2>5. Intellectual Property & Brand OEM Protection</h2><p>Suppliers must respect third-party intellectual property, trademarks, and patents. Fabricating counterfeit sporting goods, misrepresenting brand authorizations, or unauthorized use of international sports federation marks will result in immediate suspension, listing removal, and referral to trade authorities.</p><h2>6. Suspension & Account Termination</h2><p>Sports Junction may suspend or terminate manufacturer facilities that engage in fraudulent representations, fail quality audits, attempt off-platform fee avoidance, or breach agreed manufacturing SLAs.</p>',
                'sort_order' => 5,
                'is_active' => 1
            ]);
            $page = CmsPage::findById($id);
        }

        $this->render('admin/seller_agreement', [
            'pageTitle'  => 'Seller Agreement Management — Super Admin',
            'activeMenu' => 'seller_agreement',
            'admin'      => $admin,
            'page'       => $page
        ], 'admin');
    }

    /**
     * POST /admin/seller-agreement
     * Save updated Seller Agreement content
     */
    public function updateSellerAgreement(): void
    {
        $admin = $this->requireAdmin();

        $title = trim((string) $this->request->post('title', ''));
        $content = trim((string) $this->request->post('content', ''));
        $metaTitle = trim((string) $this->request->post('meta_title', ''));
        $metaDesc = trim((string) $this->request->post('meta_description', ''));
        $isActive = (int) $this->request->post('is_active', 1);

        if (empty($title)) {
            Session::flash('error', 'Agreement title is required.');
            $this->redirect('/admin/seller-agreement');
            return;
        }

        if (empty($content)) {
            Session::flash('error', 'Agreement content cannot be empty.');
            $this->redirect('/admin/seller-agreement');
            return;
        }

        // Sanitize content against stored XSS
        $sanitizedContent = CmsPage::sanitizeContent($content);

        $page = CmsPage::findBySlug('seller-agreement');
        if ($page) {
            CmsPage::update((int)$page['id'], [
                'title'            => $title,
                'slug'             => 'seller-agreement',
                'column_category'  => $page['column_category'] ?? 'PLATFORM_ADMIN',
                'content'          => $sanitizedContent,
                'meta_title'       => !empty($metaTitle) ? $metaTitle : $title,
                'meta_description' => $metaDesc,
                'is_active'        => $isActive,
                'sort_order'       => $page['sort_order'] ?? 5
            ]);
        } else {
            CmsPage::create([
                'title'            => $title,
                'slug'             => 'seller-agreement',
                'column_category'  => 'PLATFORM_ADMIN',
                'content'          => $sanitizedContent,
                'meta_title'       => !empty($metaTitle) ? $metaTitle : $title,
                'meta_description' => $metaDesc,
                'is_active'        => $isActive,
                'sort_order'       => 5
            ]);
        }

        Session::flash('success', 'Seller Agreement has been successfully saved and published.');
        $this->redirect('/admin/seller-agreement');
    }
}
