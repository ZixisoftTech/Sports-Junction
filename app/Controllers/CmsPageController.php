<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CmsPage;
use App\Models\SiteSetting;
use App\Models\Category;

class CmsPageController extends Controller
{
    /**
     * GET /page/{slug}
     * Render dynamic CMS page
     */
    public function show(array $params = []): void
    {
        $slug = trim((string) ($params['slug'] ?? ''));
        $page = CmsPage::findBySlug($slug);
        if (!$page && $slug === 'policies-and-rules') {
            $page = CmsPage::findBySlug('terms-manufacturing-sla');
        }

        if (!$page || empty($page['is_active'])) {
            http_response_code(404);
            $this->render('frontend/404', [
                'pageTitle' => 'Page Not Found — Sports Junction',
                'categories' => Category::all()
            ], 'frontend');
            return;
        }

        // Fetch related pages in the same column category
        $categoryName = CmsPage::CATEGORIES[$page['column_category']] ?? 'Sports Junction Information';
        $allPagesInCat = CmsPage::getByCategory($page['column_category'], true);

        $siteSettings = SiteSetting::getAllKeyed();
        $categories = Category::all();

        $this->render('frontend/cms_page', [
            'pageTitle' => htmlspecialchars($page['title']) . ' — ' . ($siteSettings['site_name'] ?? 'Sports Junction'),
            'page' => $page,
            'categoryName' => $categoryName,
            'relatedPages' => $allPagesInCat,
            'siteSettings' => $siteSettings,
            'categories' => $categories
        ], 'frontend');
    }
}
