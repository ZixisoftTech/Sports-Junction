<?php

require_once __DIR__ . '/../app/Helpers/Env.php';
\App\Helpers\Env::load(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Supplier.php';
require_once __DIR__ . '/../app/Models/CmsPage.php';

echo "=== RUNNING VERIFICATION FOR FRONTEND ADMIN LINK REMOVAL & CREDENTIALS VAULT ===\n\n";

$passCount = 0;
$failCount = 0;

function assertCondition($name, $condition, $detail = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] {$name}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$name} - {$detail}\n";
        $failCount++;
    }
}

// 1. Check frontend layout file
$frontendLayout = file_get_contents(__DIR__ . '/../app/Views/layouts/frontend.php');
assertCondition("Frontend layout does NOT contain '/admin/login'", strpos($frontendLayout, '/admin/login') === false, "Found /admin/login in frontend layout");
assertCondition("Frontend layout does NOT contain 'Super Admin Portal'", stripos($frontendLayout, 'Super Admin Portal') === false, "Found Super Admin Portal in frontend layout");
assertCondition("Frontend layout does NOT contain 'Master CRUD Management'", stripos($frontendLayout, 'Master CRUD Management') === false, "Found Master CRUD Management in frontend layout");
assertCondition("Frontend layout Column 3 heading is PLATFORM & POLICIES", strpos($frontendLayout, 'PLATFORM &amp; POLICIES') !== false || strpos($frontendLayout, 'PLATFORM & POLICIES') !== false, "Heading mismatch");

// 2. Check cms_pages database records
$db = \App\Core\Database::getConnection();
$adminCmsStmt = $db->query("SELECT COUNT(*) FROM cms_pages WHERE slug IN ('super-admin-portal', 'master-crud-management', 'product-publishing-hub') OR external_url LIKE '%/admin%' OR title LIKE '%Admin%'");
$adminCmsCount = (int)$adminCmsStmt->fetchColumn();
assertCondition("cms_pages database has no admin portal links", $adminCmsCount === 0, "Found {$adminCmsCount} admin links in cms_pages");

// 3. Check Admin Suppliers View
$suppliersView = file_get_contents(__DIR__ . '/../app/Views/admin/suppliers.php');
assertCondition("suppliers.php does NOT contain 'confirmImpersonateSupplier'", strpos($suppliersView, 'confirmImpersonateSupplier') === false, "confirmImpersonateSupplier still present");
assertCondition("suppliers.php does NOT contain 'One-Click Login as Seller'", strpos($suppliersView, 'One-Click Login as Seller') === false, "One-Click Login as Seller button still present");
assertCondition("suppliers.php contains showCredentialsModal action button", strpos($suppliersView, 'showCredentialsModal(${row.id}') !== false, "showCredentialsModal button missing");
assertCondition("suppliers.php contains bi-key-fill icon", strpos($suppliersView, 'bi-key-fill') !== false, "bi-key-fill icon missing");
assertCondition("suppliers.php contains #userCredentialsModal markup", strpos($suppliersView, 'id="userCredentialsModal"') !== false, "userCredentialsModal missing");
assertCondition("suppliers.php contains handleAdminSetPassword JS handler", strpos($suppliersView, 'function handleAdminSetPassword') !== false, "handleAdminSetPassword missing");

// 4. Check Admin Buyers View
$buyersView = file_get_contents(__DIR__ . '/../app/Views/admin/buyers.php');
assertCondition("buyers.php does NOT contain 'confirmImpersonate'", strpos($buyersView, 'confirmImpersonate') === false, "confirmImpersonate still present");
assertCondition("buyers.php does NOT contain 'One-Click Login as Buyer'", strpos($buyersView, 'One-Click Login as Buyer') === false, "One-Click Login as Buyer button still present");
assertCondition("buyers.php contains showCredentialsModal action button", strpos($buyersView, 'showCredentialsModal(${row.id}') !== false, "showCredentialsModal button missing");
assertCondition("buyers.php contains bi-key-fill icon", strpos($buyersView, 'bi-key-fill') !== false, "bi-key-fill icon missing");
assertCondition("buyers.php contains #userCredentialsModal markup", strpos($buyersView, 'id="userCredentialsModal"') !== false, "userCredentialsModal missing");
assertCondition("buyers.php contains handleAdminSetPassword JS handler", strpos($buyersView, 'function handleAdminSetPassword') !== false, "handleAdminSetPassword missing");

// 5. Test Password Vault Encryption & Decryption
$testSecret = "SecretPass_@" . bin2hex(random_bytes(4));
$encrypted = \App\Models\User::encryptSecret($testSecret);
$decrypted = \App\Models\User::decryptSecret($encrypted);
assertCondition("User::encryptSecret produces non-empty ciphertext", !empty($encrypted) && $encrypted !== $testSecret, "Encryption output error");
assertCondition("User::decryptSecret accurately restores plaintext", $decrypted === $testSecret, "Decrypted: '{$decrypted}' vs Expected: '{$testSecret}'");

// 6. Test User Password Vault Update
// Find or create test buyer user
$testUserStmt = $db->query("SELECT id, email FROM users WHERE role = 'BUYER' LIMIT 1");
$testUser = $testUserStmt->fetch(PDO::FETCH_ASSOC);
if ($testUser) {
    $newVaultPassword = "VaultTestPass_" . rand(1000, 9999);
    \App\Models\User::updatePassword($testUser['id'], $newVaultPassword);
    
    // Verify in database
    $verifyStmt = $db->prepare("SELECT password, vault_password FROM users WHERE id = ?");
    $verifyStmt->execute([$testUser['id']]);
    $updatedUser = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    assertCondition("Password updated with valid bcrypt hash", password_verify($newVaultPassword, $updatedUser['password']), "Bcrypt verification failed");
    assertCondition("vault_password column updated with encrypted value", !empty($updatedUser['vault_password']), "vault_password empty");
    
    $decryptedFromDb = \App\Models\User::decryptSecret($updatedUser['vault_password']);
    assertCondition("vault_password successfully decrypted from DB", $decryptedFromDb === $newVaultPassword, "Decrypted: '{$decryptedFromDb}' vs Expected: '{$newVaultPassword}'");
}

echo "\nVerification Summary: {$passCount} PASSED, {$failCount} FAILED\n";

if ($failCount > 0) {
    exit(1);
}
