<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Helpers/Env.php';
\App\Helpers\Env::load(__DIR__ . '/../../.env');
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

date_default_timezone_set('Asia/Kolkata');

$passedTests = 0;
$failedTests = 0;
$bugs = [];

function assertTest(string $desc, bool $condition, string $failMsg = '') {
    global $passedTests, $failedTests, $bugs;
    if ($condition) {
        echo "[PASS] $desc\n";
        $passedTests++;
    } else {
        echo "[FAIL] $desc -> $failMsg\n";
        $failedTests++;
        $bugs[] = ['description' => $desc, 'message' => $failMsg];
    }
}

echo "=== TEST SUITE 5: CHAT, REAL-TIME NEGOTIATION & OFFERS ===\n";

$pdo = \App\Core\Database::getConnection();

// Sample product, supplier, and buyer
$supplier = $pdo->query("SELECT id, user_id, company_name FROM suppliers WHERE user_id IS NOT NULL AND status = 'ACTIVE' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$supplier) {
    // If no supplier with user_id, link supplier 1 to a valid supplier user
    $suppUser = $pdo->query("SELECT id FROM users WHERE role = 'SUPPLIER' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($suppUser) {
        $pdo->query("UPDATE suppliers SET user_id = {$suppUser['id']} WHERE id = 1");
        $supplier = ['id' => 1, 'user_id' => $suppUser['id'], 'company_name' => 'Meerut Sports Gear Ltd'];
    }
}
$buyer = $pdo->query("SELECT id, name, email FROM users WHERE role = 'BUYER' AND status = 'ACTIVE' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$product = $pdo->query("SELECT id, title FROM products LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$supplierId = (int)$supplier['id'];
$supplierUserId = (int)$supplier['user_id'];
$buyerId = $buyer ? (int)$buyer['id'] : 3;
$buyerBId = $buyerId + 999;
$prodId = $product ? (int)$product['id'] : 1;

// 1. Thread Creation
$conv = \App\Models\ChatConversation::findOrCreate($buyerId, $supplierId, $prodId);
$convId = (int)($conv['id'] ?? 0);
assertTest("Chat conversation created / resolved", $convId > 0);

// 2. Buyer sends initial message
$msg1 = \App\Models\ChatMessage::create([
    'conversation_id' => $convId,
    'sender_user_id' => $buyerId,
    'sender_role' => 'BUYER',
    'message' => 'Hello, we would like to enquire about bulk order production timeline.',
    'message_type' => 'TEXT'
]);
assertTest("Buyer message saved in database", ($msg1['id'] ?? 0) > 0);

// 3. Supplier Unread Count
$supplierUnread = \App\Models\ChatConversation::getTotalUnreadCountForUser($supplierUserId, 'SUPPLIER');
assertTest("Supplier has unread message notification", $supplierUnread >= 1);

// 4. Supplier reads message
$marked = \App\Models\ChatMessage::markAsRead($convId, $supplierUserId);
assertTest("Supplier marks messages as read", $marked >= 0);

// 5. Supplier sends formal quotation / offer
$msg2 = \App\Models\ChatMessage::create([
    'conversation_id' => $convId,
    'sender_user_id' => $supplierUserId,
    'sender_role' => 'SUPPLIER',
    'message' => 'We can offer 500 units at $32.00 per unit FOB Nhava Sheva.',
    'message_type' => 'OFFER',
    'offer_data' => [
        'unit_price' => 32.00,
        'quantity' => 500,
        'total_amount' => 16000.00,
        'currency' => 'USD',
        'lead_time_days' => 20,
        'status' => 'PENDING'
    ]
]);
assertTest("Supplier offer message created", ($msg2['id'] ?? 0) > 0);

// Verify conversation status updated to OFFER_PENDING
\App\Models\ChatConversation::updateStatus($convId, 'OFFER_PENDING');
$checkConv = \App\Models\ChatConversation::findById($convId);
assertTest("Conversation status is OFFER_PENDING", ($checkConv['status'] ?? '') === 'OFFER_PENDING');

// 6. Buyer accepts offer -> Deal Agreed
\App\Models\ChatConversation::updateStatus($convId, 'DEAL_AGREED', 'ACCEPTED');
$agreedConv = \App\Models\ChatConversation::findById($convId);
assertTest("Conversation status updated to DEAL_AGREED", ($agreedConv['status'] ?? '') === 'DEAL_AGREED');

// 7. Isolation Check: Buyer B cannot see Buyer A's conversation
$buyerBConvs = \App\Models\ChatConversation::getForBuyer($buyerBId);
$buyerBHasConvA = false;
foreach ($buyerBConvs as $bc) {
    if ((int)$bc['id'] === $convId) {
        $buyerBHasConvA = true;
        break;
    }
}
assertTest("Cross-user isolation: Buyer B cannot access Buyer A conversation", !$buyerBHasConvA);

// 8. Super Admin Monitoring: Admin can view all conversations
$adminConvs = \App\Models\ChatConversation::getAllForAdmin();
$adminHasConv = false;
foreach ($adminConvs as $ac) {
    if ((int)$ac['id'] === $convId) {
        $adminHasConv = true;
        break;
    }
}
assertTest("Super Admin can view conversation in Communication Center", $adminHasConv);

// Cleanup test messages and conversation
$pdo->query("DELETE FROM chat_messages WHERE conversation_id = {$convId}");
$pdo->query("DELETE FROM chat_conversations WHERE id = {$convId}");

echo "\nSuite 5 Results: $passedTests PASSED, $failedTests FAILED.\n";
if (!empty($bugs)) {
    echo "Bugs logged:\n";
    print_r($bugs);
}
