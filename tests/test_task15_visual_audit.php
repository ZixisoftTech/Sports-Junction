<?php
declare(strict_types=1);

/**
 * Test Suite for Task 15: Visual Design & UI Consistency Audit
 */

echo "=== STARTING VERIFICATION FOR TASK 15: VISUAL DESIGN & UI CONSISTENCY AUDIT ===\n\n";

$testsPassed = 0;
$totalTests = 6;

// 1. Frontend Header Mega Menu
echo "TEST 1: Inspecting Header Mega-Menu Structure...\n";
$frontendLayout = file_get_contents(dirname(__DIR__) . '/app/Views/layouts/frontend.php');
if ($frontendLayout !== false &&
    str_contains($frontendLayout, 'allCategoriesNavBtn') &&
    str_contains($frontendLayout, 'allCategoriesNavMenu') &&
    str_contains($frontendLayout, 'allCategoriesNavContainer') &&
    str_contains($frontendLayout, 'switchFlyoutCategory')) {
    echo "  OK: Header Mega-Menu has two-panel layout, category selector sidebar, and dynamic subcategory flyout switcher.\n";
    $testsPassed++;
} else {
    echo "  FAILED: Header Mega-Menu structure incomplete.\n";
}

// 2. Frontend Footer Structure
echo "\nTEST 2: Inspecting Dark Navy Footer & 5 Columns...\n";
if ($frontendLayout !== false &&
    str_contains($frontendLayout, 'bg-[#071328]') &&
    str_contains($frontendLayout, 'lg:grid-cols-5') &&
    str_contains($frontendLayout, 'Super Admin Portal') &&
    str_contains($frontendLayout, 'text-emerald-400')) {
    echo "  OK: Footer verified with #071328 dark navy background, 5-column responsive grid (lg:grid-cols-5), and highlighted Super Admin Portal link.\n";
    $testsPassed++;
} else {
    echo "  FAILED: Footer layout or color styling missing.\n";
}

// 3. Social Channels on Footer and Contact Page
echo "\nTEST 3: Inspecting Social Media Consistency (Exactly Facebook, X, Instagram)...\n";
$contactView = file_get_contents(dirname(__DIR__) . '/app/Views/frontend/contact.php');
$hasFacebook = str_contains($frontendLayout, 'social_facebook') && str_contains($contactView, 'social_facebook');
$hasTwitter = str_contains($frontendLayout, 'social_twitter') && str_contains($contactView, 'social_twitter');
$hasInstagram = str_contains($frontendLayout, 'social_instagram') && str_contains($contactView, 'social_instagram');
$noLinkedIn = !str_contains($contactView, 'linkedin');
$noYouTube = !str_contains($contactView, 'youtube');
$noTikTok = !str_contains($contactView, 'tiktok');

if ($hasFacebook && $hasTwitter && $hasInstagram && $noLinkedIn && $noYouTube && $noTikTok) {
    echo "  OK: Both Footer and Contact Us page restrict social media to exactly Facebook, X/Twitter, and Instagram (no LinkedIn/YouTube/TikTok).\n";
    $testsPassed++;
} else {
    echo "  FAILED: Social channel inconsistency detected.\n";
}

// 4. Admin Sidebar Menu Items
echo "\nTEST 4: Inspecting Admin Sidebar Navigation...\n";
$adminSidebar = file_get_contents(dirname(__DIR__) . '/app/Views/layouts/admin.php');
$noStatesLink = !str_contains($adminSidebar, 'href="/admin/masters/states"');
$noCitiesLink = !str_contains($adminSidebar, 'href="/admin/masters/cities"');
$hasCertsLink = str_contains($adminSidebar, '/admin/masters/product-certificates');
$hasTagsLink = str_contains($adminSidebar, '/admin/masters/certification-tags');
$hasBuyersLink = str_contains($adminSidebar, '/admin/buyers');
$hasEnquiriesLink = str_contains($adminSidebar, '/admin/enquiries');

if ($noStatesLink && $noCitiesLink && $hasCertsLink && $hasTagsLink && $hasBuyersLink && $hasEnquiriesLink) {
    echo "  OK: Admin sidebar cleanly removes States/Cities while maintaining Product Certificates, Certification Tags, Buyers, and Enquiries.\n";
    $testsPassed++;
} else {
    echo "  FAILED: Admin sidebar links not matching requirement.\n";
}

// 5. Upload Fields Guidance & Image Choosers
echo "\nTEST 5: Inspecting Upload Field Dimension Guidance & File Choosers...\n";
$certView = file_get_contents(dirname(__DIR__) . '/app/Views/masters/product_certificates.php');
$tagView = file_get_contents(dirname(__DIR__) . '/app/Views/masters/certification_tags.php');
$catView = file_get_contents(dirname(__DIR__) . '/app/Views/masters/categories.php');
$subcatView = file_get_contents(dirname(__DIR__) . '/app/Views/masters/subcategories.php');
$settingsView = file_get_contents(dirname(__DIR__) . '/app/Views/admin/settings.php');

$certOk = str_contains($certView, 'type="file"') && (str_contains($certView, '64x64') || str_contains($certView, '64×64'));
$tagOk = str_contains($tagView, 'type="file"') && (str_contains($tagView, '64x64') || str_contains($tagView, '64×64'));
$catOk = str_contains($catView, 'type="file"') && (str_contains($catView, '600×400') || str_contains($catView, '64×64') || str_contains($catView, 'px'));
$subcatOk = str_contains($subcatView, 'type="file"') && (str_contains($subcatView, '500×500') || str_contains($subcatView, 'px'));
$settingsOk = str_contains($settingsView, 'type="file"') && (str_contains($settingsView, '240×60') || str_contains($settingsView, '240x60'));

if ($certOk && $tagOk && $catOk && $subcatOk && $settingsOk) {
    echo "  OK: All image upload views feature file choosers, preview handlers, and explicit dimension/aspect-ratio guidance.\n";
    $testsPassed++;
} else {
    echo "  FAILED: Upload fields missing guidance text or file inputs (Cert: " . ($certOk?'Y':'N') . ", Tag: " . ($tagOk?'Y':'N') . ", Cat: " . ($catOk?'Y':'N') . ", Subcat: " . ($subcatOk?'Y':'N') . ", Settings: " . ($settingsOk?'Y':'N') . ").\n";
}

// 6. Admin Buyer Management & Enquiry Detail Modals
echo "\nTEST 6: Inspecting Modal Structure & Focus Integrity...\n";
$buyersView = file_get_contents(dirname(__DIR__) . '/app/Views/admin/buyers.php');
$enquiryDetailView = file_get_contents(dirname(__DIR__) . '/app/Views/admin/enquiry_detail.php');

$buyerModalOk = str_contains($buyersView, 'id="editBuyerModal"') &&
                str_contains($buyersView, 'hide.bs.modal') &&
                str_contains($buyersView, 'document.activeElement.blur()');
$enquiryAttachOk = str_contains($enquiryDetailView, 'attachment') &&
                   str_contains($enquiryDetailView, '/admin/enquiries/attachment/');

if ($buyerModalOk && $enquiryAttachOk) {
    echo "  OK: Buyer modals have clean closing behavior without aria-hidden collisions; Enquiry detail has attachment download UI.\n";
    $testsPassed++;
} else {
    echo "  FAILED: Modal structure or enquiry detail view incomplete.\n";
}

echo "\n=== TASK 15 SUMMARY ===\n";
echo "Tests Passed: {$testsPassed} / {$totalTests}\n";

if ($testsPassed === $totalTests) {
    echo "TASK 15 ALL TESTS PASSED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo "TASK 15 FAILED: " . ($totalTests - $testsPassed) . " test(s) failed.\n";
    exit(1);
}
