<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\CSRF;
use App\Helpers\Session;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Product;
use App\Models\SiteFeedback;
use App\Models\SiteSetting;
use App\Models\Supplier;
use Throwable;

class CmsPageController extends Controller
{
    /**
     * Render dynamic CMS page by slug
     */
    public function show(array $params = []): void
    {
        $slug = trim((string) ($params['slug'] ?? ''));
        $page = CmsPage::findBySlug($slug);

        $authUser = $this->authUser();
        $isSuperAdmin = ($authUser && ($authUser['role'] ?? '') === 'SUPER_ADMIN');

        // Allow Super Admin to preview unpublished drafts
        if (!$page || (empty($page['is_active']) && !$isSuperAdmin)) {
            http_response_code(404);
            $this->render('frontend/404', [
                'pageTitle'  => 'Page Not Found — Sports Junction',
                'categories' => Category::all()
            ], 'frontend');
            return;
        }

        $isDraftPreview = empty($page['is_active']) && $isSuperAdmin;

        // Fetch related pages in the same column category
        $categoryName = CmsPage::CATEGORIES[$page['column_category']] ?? 'Sports Junction Information';
        $allPagesInCat = CmsPage::getByCategory($page['column_category'], true);

        $siteSettings = SiteSetting::getAllKeyed();
        $categories = Category::all();

        // Extra context for specialized pages
        $extraData = [];
        $slugNormalized = strtolower($page['slug']);

        if ($slugNormalized === 'sitemap' || $slugNormalized === 'xml-sitemap') {
            $extraData['categoriesWithSubs'] = Category::allActive();
            $extraData['cmsPagesByColumn'] = CmsPage::getByColumns(true, false);
            try {
                $extraData['totalProducts'] = (int) Product::fetchColumn("SELECT COUNT(*) FROM products WHERE status IN ('PUBLISHED', 'ACTIVE') AND approval_status = 'APPROVED'");
                $extraData['totalSuppliers'] = (int) Supplier::fetchColumn("SELECT COUNT(*) FROM suppliers WHERE approval_status = 'APPROVED'");
            } catch (\Throwable $e) {
                $extraData['totalProducts'] = 0;
                $extraData['totalSuppliers'] = 0;
            }
        }

        $pageTitle = !empty($page['meta_title']) 
            ? htmlspecialchars($page['meta_title']) 
            : htmlspecialchars($page['title']) . ' — ' . ($siteSettings['site_name'] ?? 'Sports Junction');

        $this->render('frontend/cms_page', array_merge([
            'pageTitle'       => $pageTitle,
            'metaDescription' => $page['meta_description'] ?? null,
            'page'            => $page,
            'categoryName'    => $categoryName,
            'relatedPages'    => $allPagesInCat,
            'siteSettings'    => $siteSettings,
            'categories'      => $categories,
            'isDraftPreview'  => $isDraftPreview,
        ], $extraData), 'frontend');
    }

    /**
     * POST /api/feedback or /feedback-suggestions (AJAX)
     * Handle user suggestions & feedback submission
     */
    public function submitFeedback(): void
    {
        $name = trim((string) $this->request->post('name', ''));
        $email = trim((string) $this->request->post('email', ''));
        $role = trim((string) $this->request->post('user_role', 'BUYER'));
        $category = trim((string) $this->request->post('category', 'GENERAL'));
        $rating = (int) $this->request->post('rating', 5);
        $subject = trim((string) $this->request->post('subject', ''));
        $message = trim((string) $this->request->post('message', ''));

        // Basic validation
        if (empty($name) || empty($email) || empty($message)) {
            $this->json([
                'success' => false,
                'message' => 'Please provide your name, valid email, and your message or feedback.'
            ], 422);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json([
                'success' => false,
                'message' => 'Please provide a valid email address.'
            ], 422);
            return;
        }

        try {
            $feedbackId = SiteFeedback::create([
                'name'       => $name,
                'email'      => $email,
                'user_role'  => $role,
                'category'   => $category,
                'rating'     => $rating,
                'subject'    => $subject,
                'message'    => $message,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);

            if ($feedbackId > 0) {
                $this->json([
                    'success' => true,
                    'message' => 'Thank you for your valuable feedback! Our product and operations teams have received your suggestions.'
                ]);
            } else {
                $this->json([
                    'success' => false,
                    'message' => 'Failed to record feedback. Please try again or email us directly.'
                ], 500);
            }
        } catch (Throwable $e) {
            error_log("CmsPageController::submitFeedback exception: " . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'An error occurred while saving your feedback.'
            ], 500);
        }
    }
}
