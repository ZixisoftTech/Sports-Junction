<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\CSRF;
use App\Helpers\Session;
use App\Models\SupportQuery;
use Throwable;

class SupportQueryController extends Controller
{
    /**
     * POST /api/help-centre/submit-query
     * Handle query submission from Buyer Help Centre or Seller Help Centre
     */
    public function submit(): void
    {
        $isAjax = $this->request->isAjax() || 
                  str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') ||
                  !empty($_SERVER['HTTP_X_REQUESTED_WITH']);

        $name = trim((string) $this->request->post('name', ''));
        $email = trim((string) $this->request->post('email', ''));
        $phone = trim((string) $this->request->post('phone', ''));
        $companyName = trim((string) $this->request->post('company_name', ''));
        $countryName = trim((string) $this->request->post('country_name', ''));
        $category = trim((string) $this->request->post('category', ''));
        $subject = trim((string) $this->request->post('subject', ''));
        $orderId = trim((string) $this->request->post('order_id', ''));
        $existingTicketId = trim((string) $this->request->post('existing_ticket_id', ''));
        $description = trim((string) $this->request->post('description', ''));
        $sourceType = strtoupper(trim((string) $this->request->post('source_type', 'BUYER')));

        if (!in_array($sourceType, SupportQuery::SOURCE_TYPES, true)) {
            $sourceType = 'BUYER';
        }

        // Field Validations
        if (empty($name)) {
            $this->json(['success' => false, 'message' => 'Please provide your full name.'], 422);
            return;
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Please provide a valid email address.'], 422);
            return;
        }

        if (empty($phone)) {
            $this->json(['success' => false, 'message' => 'Please provide your contact number.'], 422);
            return;
        }

        if (empty($category)) {
            $category = 'Other';
        }

        if (empty($subject)) {
            $this->json(['success' => false, 'message' => 'Please provide a subject for your query.'], 422);
            return;
        }

        if (empty($description) || mb_strlen($description) < 5) {
            $this->json(['success' => false, 'message' => 'Please provide a detailed description of your issue (at least 5 characters).'], 422);
            return;
        }

        // Prepend company/country info to description for transparent visibility across all admin panels
        $metaHeaders = [];
        if (!empty($companyName)) $metaHeaders[] = "Organization / Company: " . $companyName;
        if (!empty($countryName)) $metaHeaders[] = "Country / Region: " . $countryName;
        if (!empty($metaHeaders)) {
            $description = implode(" | ", $metaHeaders) . "\n\n" . $description;
        }

        // Handle File Attachment (Optional)
        $attachmentPath = null;
        $attachmentName = null;
        $attachmentSize = null;
        $attachmentMime = null;

        if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['attachment'];
            $maxBytes = 10 * 1024 * 1024; // 10MB limit

            if ($file['size'] > $maxBytes) {
                $this->json(['success' => false, 'message' => 'Attachment file size exceeds the 10MB limit.'], 422);
                return;
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip'];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = [
                'image/jpeg', 'image/png', 'image/webp',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
                'application/zip',
                'application/x-zip-compressed'
            ];

            if (!in_array($ext, $allowedExts, true) || !in_array($mime, $allowedMimes, true)) {
                $this->json([
                    'success' => false,
                    'message' => 'Invalid file type. Allowed formats: JPG, PNG, WEBP, PDF, DOC, DOCX, XLS, XLSX, TXT, ZIP.'
                ], 422);
                return;
            }

            $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/support_queries';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            $cleanOriginalName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file['name']);
            $savedFileName = 'sup_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $destination = $uploadDir . '/' . $savedFileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $attachmentPath = 'storage/uploads/support_queries/' . $savedFileName;
                $attachmentName = $cleanOriginalName;
                $attachmentSize = (int) $file['size'];
                $attachmentMime = $mime;
            } else {
                $this->json(['success' => false, 'message' => 'Failed to save attachment file. Please try again.'], 500);
                return;
            }
        } elseif (!empty($_FILES['attachment']['error']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
            $this->json(['success' => false, 'message' => 'An error occurred during file upload. Error code: ' . $_FILES['attachment']['error']], 422);
            return;
        }

        try {
            $authUser = $this->authUser();
            $userId = $authUser ? (int)($authUser['id'] ?? 0) : null;
            if ($userId <= 0) $userId = null;

            $query = SupportQuery::create([
                'source_type'        => $sourceType,
                'name'               => $name,
                'email'              => $email,
                'phone'              => $phone,
                'category'           => $category,
                'subject'            => $subject,
                'order_id'           => $orderId ?: null,
                'existing_ticket_id' => $existingTicketId ?: null,
                'description'        => $description,
                'attachment_path'    => $attachmentPath,
                'attachment_name'    => $attachmentName,
                'attachment_size'    => $attachmentSize,
                'attachment_mime'    => $attachmentMime,
                'user_id'            => $userId,
                'ip_address'         => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            $this->json([
                'success'   => true,
                'ticket_id' => $query['ticket_id'],
                'message'   => "Your query has been submitted successfully. Your Ticket ID is {$query['ticket_id']}. Our support team will review and update you shortly.",
                'query'     => [
                    'id'                 => $query['id'],
                    'ticket_id'          => $query['ticket_id'],
                    'source_type'        => $query['source_type'],
                    'created_at'         => $query['created_at'],
                    'order_id'           => $query['order_id'],
                    'existing_ticket_id' => $query['existing_ticket_id'],
                ]
            ]);
        } catch (Throwable $e) {
            @error_log("Error creating support query: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'An internal server error occurred while processing your request. Please try again.'], 500);
        }
    }
}
