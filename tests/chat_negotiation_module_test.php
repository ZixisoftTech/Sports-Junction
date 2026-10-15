<?php
declare(strict_types=1);

/**
 * =======================================================================
 * SPORTS JUNCTION — REAL-TIME CHAT, NEGOTIATION & DEAL MANAGEMENT TEST SUITE
 * Comprehensive verification of:
 *   1. Database Table & Index Integrity
 *   2. Contact Restriction Policy (Detection, Redaction, False-Positive Prevention)
 *   3. Shared Conversation Architecture & Deal Progression
 *   4. Messaging, Delivery Status & Read Receipts
 *   5. Document & Attachment MIME Validation
 *   6. Structured B2B Offers, Counteroffers (v1 -> v2) & Deal Acceptance
 *   7. Safety, User Blocking & Misconduct Reporting
 *   8. Super Admin Audit, Support Intervention & Private Internal Notes
 * =======================================================================
 */

require_once __DIR__ . '/../app/Helpers/Env.php';
require_once __DIR__ . '/../app/Core/Database.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

\App\Helpers\Env::load(dirname(__DIR__) . '/.env');

use App\Core\Database;
use App\Helpers\ContactPolicy;
use App\Models\ChatAttachment;
use App\Models\ChatBlock;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatOffer;
use App\Models\ChatReport;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;

$db = Database::getConnection();
$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $label): void
{
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$label}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$label}\n";
        $failed++;
    }
}

echo "=======================================================================\n";
echo " SPORTS JUNCTION — REAL-TIME CHAT & NEGOTIATION MODULE TEST SUITE\n";
echo " Timestamp: " . date('Y-m-d H:i:s T') . "\n";
echo "=======================================================================\n\n";

// -------------------------------------------------------------
// 1. DATABASE SCHEMA INTEGRITY
// -------------------------------------------------------------
echo ">>> 1. Verifying Database Schema & Table Integrity...\n";
$tables = ['chat_conversations', 'chat_messages', 'chat_attachments', 'chat_offers', 'chat_blocks', 'chat_reports', 'notifications'];
foreach ($tables as $table) {
    $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
    assertTest($stmt->rowCount() > 0, "Table `{$table}` exists in database");
}

// -------------------------------------------------------------
// 2. CONTACT POLICY & DISINTERMEDIATION PREVENTION
// -------------------------------------------------------------
echo "\n>>> 2. Testing Contact Policy & False-Positive Prevention...\n";

// Prohibited contact checks
assertTest(ContactPolicy::containsRestrictedContact("Call me at 9876543210 for wholesale pricing"), "Detects 10-digit Indian phone number");
assertTest(ContactPolicy::containsRestrictedContact("Reach me at +91 98765 43210 directly"), "Detects +91 international phone number");
assertTest(ContactPolicy::containsRestrictedContact("My email is supplier_sales@gmail.com send details"), "Detects standard email address");
assertTest(ContactPolicy::containsRestrictedContact("Email me at sales [at] factory [dot] com"), "Detects obfuscated email format");
assertTest(ContactPolicy::containsRestrictedContact("Let's talk on whatsapp wa.me/919876543210"), "Detects direct WhatsApp link");
assertTest(ContactPolicy::containsRestrictedContact("Add my telegram: @sportsdeals_meerut"), "Detects external chat handle");

// False positive prevention checks (Legitimate specs, pricing, quantities must NOT be flagged)
assertTest(!ContactPolicy::containsRestrictedContact("The carton dimensions are 12x6x4 cm master packing"), "Preserves dimensions without false positive");
assertTest(!ContactPolicy::containsRestrictedContact("We need 500 pcs at ₹4,500 / piece FOB Delhi"), "Preserves price & quantity without false positive");
assertTest(!ContactPolicy::containsRestrictedContact("Fabric weight is 280 GSM, blade thickness 2.5 mm"), "Preserves technical specifications without false positive");

// Redaction test
$masked = ContactPolicy::maskRestrictedContact("Contact me on 9876543210 or sales@supplier.com");
assertTest(str_contains($masked, '[Contact information hidden'), "Successfully masks restricted phone and email contact details");

// -------------------------------------------------------------
// 3. CONVERSATION ARCHITECTURE & PARTICIPANTS
// -------------------------------------------------------------
echo "\n>>> 3. Testing Conversation Lifecycle & Deal States...\n";

// Get or create test users
$buyer = $db->query("SELECT * FROM users WHERE role = 'BUYER' AND status = 'ACTIVE' LIMIT 1")->fetch();
if (!$buyer) {
    $db->exec("INSERT INTO users (name, email, password, role, status) VALUES ('Test ChatBuyer', 'testbuyer_chat@example.com', 'hash', 'BUYER', 'ACTIVE')");
    $buyer = $db->query("SELECT * FROM users WHERE email = 'testbuyer_chat@example.com'")->fetch();
}
$buyerId = (int) $buyer['id'];

$supplier = $db->query("SELECT * FROM suppliers WHERE approval_status = 'APPROVED' AND user_id IS NOT NULL AND user_id > 0 LIMIT 1")->fetch();
if (!$supplier) {
    $db->exec("INSERT INTO users (name, email, password, role, status) VALUES ('Test ChatSupplier', 'testsupplier_chat@example.com', 'hash', 'SUPPLIER', 'ACTIVE')");
    $suppUser = $db->query("SELECT * FROM users WHERE email = 'testsupplier_chat@example.com'")->fetch();
    $db->exec("INSERT INTO suppliers (user_id, company_name, contact_person, status, approval_status) VALUES ({$suppUser['id']}, 'Apex Sports Factory', 'Rajesh Sharma', 'ACTIVE', 'APPROVED')");
    $supplier = $db->query("SELECT * FROM suppliers WHERE user_id = {$suppUser['id']}")->fetch();
}
$supplierId = (int) $supplier['id'];
$supplierUserId = (int) $supplier['user_id'];

// Get a product
$product = $db->query("SELECT * FROM products WHERE status = 'ACTIVE' AND approval_status = 'APPROVED' LIMIT 1")->fetch();
$productId = $product ? (int) $product['id'] : null;

// Find or create conversation
$conv = ChatConversation::findOrCreate($buyerId, $supplierId, $productId);
assertTest(!empty($conv['id']), "Created/retrieved conversation #{$conv['id']}");
$convId = (int) $conv['id'];

// Check idempotent findOrCreate
$conv2 = ChatConversation::findOrCreate($buyerId, $supplierId, $productId);
assertTest((int)$conv2['id'] === $convId, "findOrCreate returns existing conversation without creating duplicates");

// Update deal states
ChatConversation::updateStatus($convId, 'NEGOTIATION_ONGOING', 'DISCUSSING_TERMS');
$updatedConv = ChatConversation::findById($convId);
assertTest($updatedConv['status'] === 'NEGOTIATION_ONGOING', "Updated conversation status to NEGOTIATION_ONGOING");
assertTest($updatedConv['deal_status'] === 'DISCUSSING_TERMS', "Updated conversation deal_status to DISCUSSING_TERMS");

// -------------------------------------------------------------
// 4. MESSAGES, POLICY AUDIT & READ RECEIPTS
// -------------------------------------------------------------
echo "\n>>> 4. Testing Messaging, Violation Tagging & Read Receipts...\n";

// Clean message
$msg1 = ChatMessage::create([
    'conversation_id' => $convId,
    'sender_user_id' => $buyerId,
    'sender_role' => 'BUYER',
    'message_type' => 'TEXT',
    'message_body' => 'Hello, can you manufacture 500 units of this product with custom branding?'
]);
assertTest(!empty($msg1['id']), "Buyer message sent successfully (#{$msg1['id']})");
assertTest((int)$msg1['has_restricted_contact'] === 0, "Clean message is not flagged for contact violation");

// Message containing contact info
$msgViolating = ChatMessage::create([
    'conversation_id' => $convId,
    'sender_user_id' => $buyerId,
    'sender_role' => 'BUYER',
    'message_type' => 'TEXT',
    'message_body' => 'Please call me at 9876543210 to negotiate offline.'
]);
assertTest((int)$msgViolating['has_restricted_contact'] === 1, "Off-platform contact attempt is flagged with has_restricted_contact = 1");

// Read Receipts
$unreadCountBefore = ChatConversation::getTotalUnreadCountForUser($supplierUserId, 'SUPPLIER');
assertTest($unreadCountBefore > 0, "Unread message count properly tracked for recipient");

$readRows = ChatMessage::markAsRead($convId, $supplierUserId);
assertTest($readRows >= 1, "markAsRead marks unread incoming messages as READ");

$unreadCountAfter = ChatConversation::getTotalUnreadCountForUser($supplierUserId, 'SUPPLIER');
assertTest($unreadCountAfter < $unreadCountBefore, "Unread message count decrements after marking read");

// -------------------------------------------------------------
// 5. STRUCTURED OFFERS, COUNTEROFFERS & DEAL ACCEPTANCE
// -------------------------------------------------------------
echo "\n>>> 5. Testing Structured Offers, Counteroffer Versions & Deal Finalization...\n";

// Supplier creates initial offer (v1)
$offer1 = ChatOffer::createOffer([
    'conversation_id' => $convId,
    'product_id' => $productId,
    'created_by_user_id' => $supplierUserId,
    'created_by_role' => 'SUPPLIER',
    'quantity' => 500,
    'unit' => 'Pieces',
    'unit_price' => 450.00,
    'delivery_location' => 'Ex-Factory Meerut',
    'lead_time' => '14 Days',
    'customization_terms' => 'Screen printed logo included'
]);
assertTest(!empty($offer1['id']), "Supplier created initial Offer v1 (#{$offer1['id']})");
assertTest((int)$offer1['offer_version'] === 1, "Initial offer version is 1");
assertTest((float)$offer1['total_amount'] === 225000.00, "Calculates total amount correctly (500 * 450 = 225,000)");

$convOfferPending = ChatConversation::findById($convId);
assertTest($convOfferPending['status'] === 'OFFER_PENDING', "Conversation transitioned to OFFER_PENDING");

// Buyer submits counteroffer (v2)
$offer2 = ChatOffer::createOffer([
    'conversation_id' => $convId,
    'product_id' => $productId,
    'created_by_user_id' => $buyerId,
    'created_by_role' => 'BUYER',
    'parent_offer_id' => (int)$offer1['id'],
    'quantity' => 500,
    'unit' => 'Pieces',
    'unit_price' => 420.00,
    'delivery_location' => 'CIF Mumbai Port',
    'lead_time' => '10 Days'
]);
assertTest(!empty($offer2['id']), "Buyer submitted Counteroffer v2 (#{$offer2['id']})");
assertTest((int)$offer2['offer_version'] === 2, "Counteroffer automatically incremented to version 2");

// Check parent offer was updated to COUNTERED
$parentOfferUpdated = ChatOffer::findById((int)$offer1['id']);
assertTest($parentOfferUpdated['status'] === 'COUNTERED', "Parent offer status updated to COUNTERED");

// Supplier accepts Buyer's counteroffer
$acceptedOffer = ChatOffer::acceptOffer((int)$offer2['id'], $supplierUserId);
assertTest($acceptedOffer['status'] === 'ACCEPTED', "Supplier accepted counteroffer");
assertTest((int)$acceptedOffer['accepted_by_user_id'] === $supplierUserId, "Accepted by recorded accurately");

$convDealAgreed = ChatConversation::findById($convId);
assertTest($convDealAgreed['status'] === 'DEAL_AGREED', "Conversation transitioned to DEAL_AGREED upon offer acceptance");

// -------------------------------------------------------------
// 6. ATTACHMENT RULES & VALIDATION
// -------------------------------------------------------------
echo "\n>>> 6. Testing Document & File Attachment Handling...\n";
assertTest(in_array('pdf', ChatAttachment::ALLOWED_EXTENSIONS, true), "PDF extension is permitted");
assertTest(in_array('png', ChatAttachment::ALLOWED_EXTENSIONS, true), "PNG extension is permitted");
assertTest(in_array('webp', ChatAttachment::ALLOWED_EXTENSIONS, true), "WebP extension is permitted");
assertTest(!in_array('exe', ChatAttachment::ALLOWED_EXTENSIONS, true), "Executable files are strictly rejected");
assertTest(ChatAttachment::MAX_FILE_SIZE === 10485760, "File size limit is set to 10MB");

// -------------------------------------------------------------
// 7. USER BLOCKING & SAFETY REPORTS
// -------------------------------------------------------------
echo "\n>>> 7. Testing Safety Blocking & Misconduct Reporting...\n";

// Blocking
ChatBlock::block($buyerId, $supplierUserId);
assertTest(ChatBlock::isBlocked($buyerId, $supplierUserId), "User blocking recorded and verified in both directions");
assertTest(ChatBlock::isBlockedBy($buyerId, $supplierUserId), "isBlockedBy identifies specific blocker");

ChatBlock::unblock($buyerId, $supplierUserId);
assertTest(!ChatBlock::isBlocked($buyerId, $supplierUserId), "Unblock restores communication capability");

// Reporting
$report = ChatReport::createReport(
    $convId,
    $buyerId,
    $supplierUserId,
    'Off-platform payment or contact solicitation',
    'Supplier repeatedly requested external bank transfer.'
);
assertTest(!empty($report['id']), "Safety report submitted successfully (#{$report['id']})");
assertTest($report['status'] === 'PENDING', "Report created in PENDING review state");

// -------------------------------------------------------------
// 8. SUPER ADMIN OVERSIGHT, AUDIT & INTERVENTION
// -------------------------------------------------------------
echo "\n>>> 8. Testing Super Admin Oversight, Intervention & Internal Notes...\n";

$adminUser = $db->query("SELECT * FROM users WHERE role = 'SUPER_ADMIN' LIMIT 1")->fetch();
$adminId = $adminUser ? (int) $adminUser['id'] : 1;

// Overview metrics
$stats = ChatConversation::getStats();
assertTest(isset($stats['total_conversations']) && $stats['total_conversations'] > 0, "Super Admin stats calculates total conversations");
assertTest(isset($stats['deals_agreed']) && $stats['deals_agreed'] > 0, "Super Admin stats tracks agreed deals");
assertTest(isset($stats['flagged_messages']) && $stats['flagged_messages'] > 0, "Super Admin stats flags policy violations");

// Super Admin Official Support Reply
$adminSupportMsg = ChatMessage::create([
    'conversation_id' => $convId,
    'sender_user_id' => $adminId,
    'sender_role' => 'SUPER_ADMIN',
    'message_type' => 'TEXT',
    'message_body' => 'Official Sports Junction Support: All payments must be processed via platform escrow.'
]);
assertTest($adminSupportMsg['sender_role'] === 'SUPER_ADMIN', "Super Admin official support reply injected with SUPER_ADMIN role");

// Super Admin Authorized On-Behalf Reply
$onBehalfMsg = ChatMessage::create([
    'conversation_id' => $convId,
    'sender_user_id' => $supplierUserId,
    'sender_role' => 'SUPPLIER',
    'sender_admin_id' => $adminId,
    'is_on_behalf_of_supplier' => 1,
    'message_type' => 'TEXT',
    'message_body' => 'We confirm the sample dispatch schedule for tomorrow.'
]);
assertTest((int)$onBehalfMsg['is_on_behalf_of_supplier'] === 1, "Authorized on-behalf message marked with is_on_behalf_of_supplier = 1");
assertTest((int)$onBehalfMsg['sender_admin_id'] === $adminId, "Audit trail records sender_admin_id for on-behalf replies");

// Super Admin Private Internal Note
$internalNote = ChatMessage::create([
    'conversation_id' => $convId,
    'sender_user_id' => $adminId,
    'sender_role' => 'SUPER_ADMIN',
    'is_internal_admin_note' => 1,
    'message_type' => 'TEXT',
    'message_body' => 'Auditor note: Buyer verified by credit team, high trade limit approved.'
]);
assertTest((int)$internalNote['is_internal_admin_note'] === 1, "Internal note stored with is_internal_admin_note = 1");

// Verify internal note is strictly hidden from Buyer/Supplier message queries
$publicMessages = ChatMessage::getByConversation($convId, null, 100, false);
$hasInternalLeak = false;
foreach ($publicMessages as $m) {
    if ((int)$m['is_internal_admin_note'] === 1) {
        $hasInternalLeak = true;
        break;
    }
}
assertTest(!$hasInternalLeak, "Internal notes are STRICTLY hidden from standard participant chat feed");

// Verify internal note is visible to Super Admin audit query
$adminMessages = ChatMessage::getByConversation($convId, null, 100, true);
$foundInternal = false;
foreach ($adminMessages as $m) {
    if ((int)$m['is_internal_admin_note'] === 1) {
        $foundInternal = true;
        break;
    }
}
assertTest($foundInternal, "Internal notes are fully visible during Super Admin conversation audits");

// Resolve safety report
$resolved = ChatReport::resolve((int)$report['id'], $adminId, 'RESOLVED', 'Both parties reminded of escrow compliance.');
assertTest($resolved, "Super Admin successfully resolved safety report");
$updatedReport = ChatReport::findById((int)$report['id']);
assertTest($updatedReport['status'] === 'RESOLVED', "Report status transitioned to RESOLVED in database");

echo "\n=======================================================================\n";
echo " CHAT & NEGOTIATION MODULE QA SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "=======================================================================\n";

if ($failed > 0) {
    exit(1);
}
