<?php
declare(strict_types=1);

$baseUrl = \App\Core\Response::baseUrl();

$offersById = [];
if (!empty($offers)) {
    foreach ($offers as $off) {
        $offersById[(int)$off['id']] = $off;
    }
}

$adminLastMsgId = 0;
if (!empty($messages)) {
    foreach ($messages as $m) {
        $adminLastMsgId = max($adminLastMsgId, (int) $m['id']);
    }
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="<?= $baseUrl ?>/admin/communication" class="text-decoration-none text-muted small fw-bold">
                <i class="bi bi-arrow-left"></i> BACK TO COMMUNICATION CENTER
            </a>
            <span class="text-muted">•</span>
            <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-0.5 rounded fw-bold" style="font-size: 11px;">CONVERSATION AUDIT #<?= (int)$conversation['id'] ?></span>
        </div>
        <h4 class="fw-bold text-slate-900 mb-0">Conversation Audit & Administrative Intervention</h4>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $baseUrl ?>/admin/communication" class="btn btn-outline-secondary btn-sm fw-semibold px-3 py-1.5 rounded-3">
            <i class="bi bi-x-lg me-1"></i> Close Audit
        </a>
    </div>
</div>

<!-- Overview Card -->
<div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
    <div class="card-body p-3">
        <div class="row g-3">
            <div class="col-md-3 border-end">
                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 10px;">Buyer</div>
                <h6 class="fw-bold text-slate-900 mb-0 text-truncate"><?= htmlspecialchars($conversation['buyer_company'] ?: ($conversation['buyer_first_name'] . ' ' . $conversation['buyer_last_name'])) ?></h6>
                <div class="text-muted small" style="font-size: 11px;"><?= htmlspecialchars($conversation['buyer_email'] ?? '') ?></div>
                <div class="text-muted small" style="font-size: 11px;"><?= htmlspecialchars($conversation['buyer_phone'] ?? 'No phone') ?></div>
            </div>

            <div class="col-md-3 border-end">
                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 10px;">Supplier / Manufacturer</div>
                <h6 class="fw-bold text-teal-800 mb-0 text-truncate"><?= htmlspecialchars($conversation['supplier_company_name'] ?? 'Supplier') ?></h6>
                <div class="text-muted small" style="font-size: 11px;">Contact: <?= htmlspecialchars($conversation['supplier_contact_person'] ?? 'Rep') ?></div>
                <div class="text-muted small" style="font-size: 11px;"><?= htmlspecialchars($conversation['supplier_email'] ?? '') ?></div>
            </div>

            <div class="col-md-3 border-end">
                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 10px;">Product / Item</div>
                <?php if (!empty($conversation['product_title'])): ?>
                    <h6 class="fw-bold text-primary mb-0 text-truncate" style="font-size: 12px;"><?= htmlspecialchars($conversation['product_title']) ?></h6>
                    <div class="text-slate-700 small font-monospace" style="font-size: 11px;">$<?= number_format((float)($conversation['product_price'] ?? 0), 2) ?> | MOQ: <?= (int)($conversation['product_moq'] ?? 1) ?></div>
                    <?php if (!empty($conversation['product_slug'])): ?>
                        <a href="<?= $baseUrl ?>/product/<?= htmlspecialchars($conversation['product_slug']) ?>" target="_blank" class="text-decoration-none" style="font-size: 10px;">
                            <i class="bi bi-box-arrow-up-right me-1"></i> View Live Product
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-muted small fst-italic" style="font-size: 11px;">General Sourcing Inquiry</div>
                <?php endif; ?>
            </div>

            <div class="col-md-3">
                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 10px;">Current Deal Status</div>
                <div class="d-flex align-items-center gap-2 mt-0.5">
                    <span class="badge bg-primary px-2 py-1" style="font-size: 11px;"><?= htmlspecialchars($conversation['status']) ?></span>
                </div>
                <div class="text-muted small mt-1" style="font-size: 11px;">Stage: <strong><?= htmlspecialchars($conversation['deal_status']) ?></strong></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- LEFT: CHAT AUDIT STREAM & ADMIN INTERVENTION (8 Cols) -->
    <div class="col-lg-8">
        <!-- Message Log Card -->
        <div class="card border-0 shadow-sm rounded-3" style="height: calc(100vh - 290px); min-height: 480px; max-height: 640px; display: flex; flex-direction: column;">
            <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between flex-shrink-0">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-journal-text text-primary"></i>
                    <h6 class="fw-bold mb-0 text-slate-900" style="font-size: 13px;">Immutable Audit Trail (<?= count($messages) ?> Messages)</h6>
                </div>
                <span class="badge bg-light text-muted border" style="font-size: 10px;">Server Time Recorded</span>
            </div>

            <div class="card-body p-3 bg-light" style="flex: 1 1 auto; overflow-y: auto; overscroll-behavior: contain;" id="adminChatStream">
                <?php if (empty($messages)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-chat-dots display-6 text-slate-300 d-block mb-2"></i>
                        No messages recorded yet in this thread.
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php 
                            $role = $msg['sender_role'];
                            $isInternalNote = !empty($msg['is_internal_admin_note']);
                            $isOnBehalf = !empty($msg['is_on_behalf_of_supplier']);
                            $hasViolation = !empty($msg['has_restricted_contact']);
                            $type = $msg['message_type'];
                        ?>
                        <div id="admin-msg-<?= (int)$msg['id'] ?>" class="mb-3 <?= $isInternalNote ? 'p-3 bg-warning bg-opacity-10 border border-warning rounded-3' : '' ?>">
                            <?php if ($isInternalNote): ?>
                                <!-- Internal Super Admin Private Note -->
                                <div class="d-flex align-items-center justify-content-between text-warning fw-bold small mb-1">
                                    <span><i class="bi bi-lock-fill me-1"></i> PRIVATE SUPER ADMIN NOTE (STRICTLY HIDDEN FROM BUYER & SUPPLIER)</span>
                                    <span><?= date('d M Y, H:i', strtotime($msg['created_at'])) ?></span>
                                </div>
                                <div class="text-dark small bg-white p-2.5 rounded border border-warning border-opacity-25">
                                    <?= nl2br(htmlspecialchars($msg['message_body'])) ?>
                                </div>
                            <?php elseif ($type === 'SYSTEM'): ?>
                                <!-- System Message -->
                                <div class="text-center my-2">
                                    <span class="badge bg-secondary bg-opacity-25 text-secondary px-3 py-1.5 rounded-pill small">
                                        <i class="bi bi-info-circle me-1"></i> <?= htmlspecialchars($msg['message_body']) ?>
                                        <span class="opacity-75 ms-1"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                                    </span>
                                </div>
                            <?php elseif ($type === 'OFFER'): ?>
                                <!-- Formal Offer Card in Audit Stream -->
                                <?php 
                                    $meta = !empty($msg['metadata']) ? (is_array($msg['metadata']) ? $msg['metadata'] : json_decode($msg['metadata'], true)) : [];
                                    $offId = (int)($meta['offer_id'] ?? 0);
                                    $off = $offersById[$offId] ?? null;
                                    $st = $off['status'] ?? 'PENDING';
                                ?>
                                <div class="d-flex <?= ($role === 'BUYER') ? 'justify-content-start' : 'justify-content-end' ?> my-2">
                                    <div class="card shadow-xs border p-3" style="max-width: 82%; border-left: 4px solid <?= ($st === 'ACCEPTED') ? '#10b981' : (($role === 'BUYER') ? '#2563eb' : '#0d9488') ?> !important; background: #ffffff;">
                                        <div class="d-flex align-items-center justify-content-between gap-3 mb-2 pb-2 border-bottom">
                                            <div class="d-flex align-items-center gap-1.5">
                                                <span class="badge <?= ($role === 'BUYER') ? 'bg-primary' : 'bg-success' ?>" style="font-size: 10px;"><?= $role ?> PROPOSAL</span>
                                                <strong class="small text-dark" style="font-size: 11px;">Formal Price Quotation</strong>
                                            </div>
                                            <?php if ($st === 'ACCEPTED'): ?>
                                                <span class="badge bg-success" style="font-size: 10px;"><i class="bi bi-check-circle me-1"></i> Deal Agreed & Accepted ✓</span>
                                            <?php elseif ($st === 'REJECTED'): ?>
                                                <span class="badge bg-danger" style="font-size: 10px;">Declined</span>
                                            <?php elseif ($st === 'COUNTERED'): ?>
                                                <span class="badge bg-secondary" style="font-size: 10px;">Countered</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark" style="font-size: 10px;">Pending Review</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small text-dark mb-1 fw-bold"><?= htmlspecialchars($msg['message_body']) ?></div>
                                        <?php if (!empty($meta['lead_time'])): ?>
                                            <div class="text-muted small" style="font-size: 11px;">Lead Time: <strong><?= htmlspecialchars($meta['lead_time']) ?></strong></div>
                                        <?php endif; ?>
                                        <div class="mt-2 text-end text-muted" style="font-size: 9px;">
                                            Recorded: <?= date('d M Y, H:i:s', strtotime($msg['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="d-flex <?= ($role === 'BUYER') ? 'justify-content-start align-items-start gap-2.5' : 'justify-content-end align-items-start gap-2.5' ?>">
                                    <?php if ($role === 'BUYER'): ?>
                                        <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-center shadow-xs flex-shrink-0 mt-1" style="width: 32px; height: 32px; font-size: 12px;">
                                            <?= strtoupper(substr($msg['sender_company'] ?? $msg['sender_first_name'] ?? 'B', 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="card shadow-xs border p-3 rounded-4" style="max-width: 80%; background-color: <?= ($role === 'BUYER') ? '#ffffff' : (($role === 'SUPER_ADMIN') ? '#f3e8ff' : '#f0fdf4') ?>;">
                                        <div class="d-flex align-items-center justify-content-between gap-3 mb-1">
                                            <div class="d-flex align-items-center gap-1.5">
                                                <?php if ($role === 'BUYER'): ?>
                                                    <span class="badge bg-primary text-white" style="font-size: 10px;">BUYER</span>
                                                    <span class="fw-bold small text-slate-800"><?= htmlspecialchars($msg['sender_company'] ?: ($msg['sender_first_name'] . ' ' . $msg['sender_last_name'])) ?></span>
                                                <?php elseif ($role === 'SUPER_ADMIN'): ?>
                                                    <span class="badge text-white" style="background:#7e22ce; font-size: 10px;">SUPER ADMIN OFFICIAL SUPPORT</span>
                                                <?php elseif ($isOnBehalf): ?>
                                                    <span class="badge bg-warning text-dark" style="font-size: 10px;">ON BEHALF OF SUPPLIER</span>
                                                    <span class="text-muted small">(Authorized Admin Intermediary)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success text-white" style="font-size: 10px;">SUPPLIER</span>
                                                    <span class="fw-bold small text-teal-800"><?= htmlspecialchars($conversation['supplier_company_name']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="text-muted small" style="font-size: 10px;"><?= date('d M Y, H:i:s', strtotime($msg['created_at'])) ?></span>
                                        </div>

                                        <!-- Violation Banner if Contact Info Detected -->
                                        <?php if ($hasViolation): ?>
                                            <div class="alert alert-danger py-1 px-2 mb-2 text-danger small d-flex align-items-center gap-1.5" style="font-size: 11px;">
                                                <i class="bi bi-shield-slash-fill"></i>
                                                <span>POLICY VIOLATION DETECTED: Message contains phone number, email, or off-platform handle.</span>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Message Body -->
                                        <?php 
                                            $hasAttachment = !empty($msg['attachment_path']);
                                            $isAutoAttachText = $hasAttachment && str_starts_with($msg['message_body'], 'Sent an attachment:');
                                        ?>
                                        <?php if (!$isAutoAttachText && !empty(trim($msg['message_body']))): ?>
                                            <div class="text-slate-800 text-sm whitespace-pre-wrap leading-relaxed">
                                                <?= nl2br(htmlspecialchars($msg['message_body'])) ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Attachment if Present -->
                                        <?php if ($hasAttachment): ?>
                                            <?php
                                                $ext = strtolower(pathinfo($msg['attachment_name'], PATHINFO_EXTENSION));
                                                $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                                                $isPdf = ($ext === 'pdf');
                                                $fileUrl = htmlspecialchars($baseUrl . $msg['attachment_path']);
                                                $downloadUrl = htmlspecialchars($baseUrl . '/api/chat/attachments/' . (int)$msg['attachment_id'] . '/download');
                                                $fileName = htmlspecialchars($msg['attachment_name']);
                                                $fileSize = round($msg['attachment_size'] / 1024, 1) . ' KB';
                                            ?>
                                            <?php if ($isImg): ?>
                                                <div class="mt-2.5 rounded-3 border overflow-hidden bg-light" style="max-width: 320px;">
                                                    <a href="<?= $fileUrl ?>" target="_blank" class="d-block text-decoration-none">
                                                        <img src="<?= $fileUrl ?>" alt="<?= $fileName ?>" class="img-fluid" style="max-height: 180px; width: 100%; object-fit: cover;">
                                                        <div class="p-2 bg-white small d-flex justify-content-between align-items-center text-muted border-top">
                                                            <span class="text-truncate fw-semibold text-dark" style="max-width: 200px; font-size: 11px;"><?= $fileName ?></span>
                                                            <i class="bi bi-box-arrow-up-right small"></i>
                                                        </div>
                                                    </a>
                                                    <div class="px-2 py-1 bg-light d-flex justify-content-between align-items-center border-top" style="font-size: 11px;">
                                                        <span class="text-muted"><?= $fileSize ?></span>
                                                        <a href="<?= $downloadUrl ?>" class="text-primary fw-bold text-decoration-none">
                                                            <i class="bi bi-download me-1"></i> Download
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php elseif ($isPdf): ?>
                                                <div class="mt-2.5 p-2.5 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-3 d-flex align-items-center justify-content-between gap-2" style="max-width: 340px;">
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                        <span class="badge bg-danger p-2 fw-bold" style="font-size: 10px;">PDF</span>
                                                        <div class="text-truncate small">
                                                            <span class="fw-bold d-block text-truncate text-dark" style="font-size: 11px;"><?= $fileName ?></span>
                                                            <span class="text-muted" style="font-size: 10px;"><?= $fileSize ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                                        <a href="<?= $fileUrl ?>" target="_blank" class="btn btn-sm btn-light border py-1 px-2" style="font-size: 11px;" title="View PDF">
                                                            <i class="bi bi-eye me-1 text-danger"></i> View
                                                        </a>
                                                        <a href="<?= $downloadUrl ?>" class="btn btn-sm btn-danger py-1 px-2 text-white" style="font-size: 11px;" title="Download PDF">
                                                            <i class="bi bi-download"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="mt-2.5 p-2 bg-light border rounded-3 d-flex align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                        <i class="bi bi-paperclip fs-5 text-primary"></i>
                                                        <div class="text-truncate small">
                                                            <span class="fw-bold d-block text-truncate"><?= $fileName ?></span>
                                                            <span class="text-muted" style="font-size: 10px;"><?= strtoupper($ext) ?> • <?= $fileSize ?></span>
                                                        </div>
                                                    </div>
                                                    <a href="<?= $downloadUrl ?>" class="btn btn-sm btn-outline-primary py-1 px-2" title="Download Document">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <div class="mt-1 text-end text-muted" style="font-size: 9px;">
                                            Delivery Status: <?= htmlspecialchars($msg['delivery_status']) ?>
                                        </div>
                                    </div>

                                    <?php if ($role !== 'BUYER'): ?>
                                        <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center shadow-xs flex-shrink-0 mt-1" style="width: 32px; height: 32px; font-size: 12px; background: <?= ($role === 'SUPER_ADMIN') ? '#7e22ce' : '#0d9488' ?>;">
                                            <?= ($role === 'SUPER_ADMIN') ? '<i class="bi bi-shield-check"></i>' : strtoupper(substr($conversation['supplier_company_name'] ?? 'S', 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Admin Intervention Reply Box -->
            <div class="card-footer bg-white p-3 border-top flex-shrink-0">
                <form id="adminReplyForm" onsubmit="handleAdminReply(event)">
                    <input type="hidden" name="conversation_id" value="<?= (int)$conversation['id'] ?>">
                    
                    <div class="mb-2">
                        <label class="form-label fw-bold small text-slate-700 mb-1" style="font-size: 11px;">Administrative Intervention Action</label>
                        <div class="d-flex flex-wrap gap-3 small" style="font-size: 11px;">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="reply_mode" id="modeSupport" value="SUPPORT" checked>
                                <label class="form-check-label fw-semibold text-purple" for="modeSupport" style="color: #7e22ce;">
                                    Official Super Admin Support Reply
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="reply_mode" id="modeOnBehalf" value="ON_BEHALF_SUPPLIER">
                                <label class="form-check-label fw-semibold text-teal-800" for="modeOnBehalf">
                                    Reply on Behalf of Supplier
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="reply_mode" id="modeInternal" value="INTERNAL_NOTE">
                                <label class="form-check-label fw-semibold text-warning" for="modeInternal">
                                    Add Private Internal Note (Admins Only)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="input-group">
                        <textarea name="message_body" id="adminReplyText" rows="2" class="form-control text-sm" placeholder="Type administrative response or internal audit note..." required></textarea>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            <i class="bi bi-send-fill me-1"></i> Send / Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- RIGHT: STRUCTURED OFFERS & AUDIT DETAILS (4 Cols) -->
    <div class="col-lg-4">
        <div style="height: calc(100vh - 290px); min-height: 480px; max-height: 640px; overflow-y: auto; overscroll-behavior: contain;" class="d-flex flex-column gap-3">
            <!-- Structured Offer Negotiation History -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-slate-900" style="font-size: 13px;"><i class="bi bi-handshake me-1 text-primary"></i> Negotiation Offer Chain</h6>
                    <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 11px;"><?= count($offers) ?> Versions</span>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($offers)): ?>
                        <p class="text-muted small text-center mb-0 py-3" style="font-size: 11px;">No formal price offers submitted yet.</p>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2.5">
                            <?php foreach ($offers as $off): ?>
                                <div class="p-2.5 rounded-3 border <?= ($off['status'] === 'ACCEPTED') ? 'border-success bg-success bg-opacity-10' : (($off['status'] === 'PENDING_ACCEPTANCE') ? 'border-warning bg-warning bg-opacity-10' : 'bg-light') ?>">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="fw-bold small text-slate-900" style="font-size: 11px;">Offer v<?= (int)$off['offer_version'] ?></span>
                                        <?php if ($off['status'] === 'ACCEPTED'): ?>
                                            <span class="badge bg-success" style="font-size: 10px;">DEAL AGREED</span>
                                        <?php elseif ($off['status'] === 'PENDING_ACCEPTANCE'): ?>
                                            <span class="badge bg-warning text-dark" style="font-size: 10px;">PENDING</span>
                                        <?php elseif ($off['status'] === 'COUNTERED'): ?>
                                            <span class="badge bg-secondary" style="font-size: 10px;">COUNTERED</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary" style="font-size: 10px;"><?= htmlspecialchars($off['status']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-slate-700" style="font-size: 11px;">
                                        <div><strong>Qty:</strong> <?= number_format((int)$off['quantity']) ?> <?= htmlspecialchars($off['unit']) ?></div>
                                        <div><strong>Unit Price:</strong> $<?= number_format((float)$off['unit_price'], 2) ?></div>
                                        <div class="fw-bold text-primary mt-0.5">Total: $<?= number_format((float)$off['total_amount'], 2) ?></div>
                                        <?php if (!empty($off['lead_time'])): ?>
                                            <div class="text-muted mt-0.5" style="font-size: 10px;">Lead Time: <?= htmlspecialchars($off['lead_time']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted mt-1 text-end" style="font-size: 9px;">
                                        By <?= htmlspecialchars($off['created_by_role']) ?> on <?= date('d M Y, H:i', strtotime($off['created_at'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Safety Reports on this Conversation -->
            <?php if (!empty($reports)): ?>
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-danger" style="font-size: 13px;"><i class="bi bi-shield-exclamation me-1"></i> Active Misconduct Reports</h6>
                    </div>
                    <div class="card-body p-3">
                        <?php foreach ($reports as $r): ?>
                            <div class="p-2.5 border rounded-3 mb-2 bg-danger bg-opacity-10 border-danger">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="fw-bold small text-danger" style="font-size: 11px;">Report #<?= (int)$r['id'] ?></span>
                                    <span class="badge bg-danger" style="font-size: 10px;"><?= htmlspecialchars($r['status']) ?></span>
                                </div>
                                <div class="small fw-semibold text-slate-900" style="font-size: 11px;"><?= htmlspecialchars($r['reason']) ?></div>
                                <p class="small text-slate-600 mb-2 mt-0.5" style="font-size: 11px;"><?= htmlspecialchars($r['description'] ?? 'No extra details.') ?></p>
                                <form method="POST" action="<?= $baseUrl ?>/admin/communication/reports/<?= (int)$r['id'] ?>/resolve">
                                    <input type="hidden" name="status" value="RESOLVED">
                                    <input type="hidden" name="admin_notes" value="Resolved via conversation audit intervention">
                                    <button type="submit" class="btn btn-sm btn-outline-success w-100 fw-bold" style="font-size: 11px;">
                                        <i class="bi bi-check-circle me-1"></i> Mark Resolved
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let adminLastMessageId = <?= (int) $adminLastMsgId ?>;
const convId = <?= (int) $conversation['id'] ?>;
const baseUrl = '<?= $baseUrl ?>';
let adminSse = null;
let adminPollInterval = null;

function scrollAdminStreamToBottom() {
    const stream = document.getElementById('adminChatStream');
    if (stream) stream.scrollTop = stream.scrollHeight;
}
scrollAdminStreamToBottom();

// Polling for incoming messages
function startAdminPolling() {
    stopAdminPolling();
    adminPollInterval = setInterval(pollAdminMessages, 2500);
}

function stopAdminPolling() {
    if (adminPollInterval) {
        clearInterval(adminPollInterval);
        adminPollInterval = null;
    }
}

async function pollAdminMessages() {
    try {
        const res = await fetch(`${baseUrl}/api/chat/poll?conversation_id=${convId}&last_id=${adminLastMessageId}`);
        const data = await res.json();
        if (data.success && Array.isArray(data.messages) && data.messages.length > 0) {
            data.messages.forEach(msg => {
                if (parseInt(msg.id) > adminLastMessageId) {
                    adminLastMessageId = Math.max(adminLastMessageId, parseInt(msg.id));
                    appendAdminStreamMessage(msg);
                }
            });
            scrollAdminStreamToBottom();
        }
    } catch(e) {}
}

// SSE real-time stream
function initAdminSse() {
    if (adminSse) {
        adminSse.close();
        adminSse = null;
    }
    if (!window.EventSource) return;

    try {
        adminSse = new EventSource(`${baseUrl}/api/chat/stream?conversation_id=${convId}&last_id=${adminLastMessageId}`);

        adminSse.addEventListener('message', function(e) {
            try {
                const msg = JSON.parse(e.data);
                if (msg && parseInt(msg.id) > adminLastMessageId) {
                    adminLastMessageId = Math.max(adminLastMessageId, parseInt(msg.id));
                    appendAdminStreamMessage(msg);
                    scrollAdminStreamToBottom();
                }
            } catch(err) {}
        });

        adminSse.addEventListener('timeout', function() {
            if (adminSse) adminSse.close();
            setTimeout(initAdminSse, 1000);
        });

        adminSse.onerror = function() {
            if (adminSse) adminSse.close();
            setTimeout(initAdminSse, 3000);
        };
    } catch(err) {}
}

startAdminPolling();
initAdminSse();

// Handle Admin Reply Submission (Real-Time without Reload)
async function handleAdminReply(e) {
    e.preventDefault();
    const form = document.getElementById('adminReplyForm');
    const textarea = document.getElementById('adminReplyText');
    const submitBtn = form.querySelector('button[type="submit"]');
    const bodyText = textarea ? textarea.value.trim() : '';

    if (!bodyText) return;

    const formData = new FormData(form);
    if (submitBtn) submitBtn.disabled = true;

    try {
        const res = await fetch(`${baseUrl}/api/chat/admin/reply`, {
            method: 'POST',
            body: new URLSearchParams(formData)
        });
        const data = await res.json();
        if (data.success && data.message) {
            if (textarea) textarea.value = '';
            if (parseInt(data.message.id) > adminLastMessageId) {
                adminLastMessageId = Math.max(adminLastMessageId, parseInt(data.message.id));
            }
            appendAdminStreamMessage(data.message);
            scrollAdminStreamToBottom();
        } else {
            alert(data.message || 'Failed to submit administrative response.');
        }
    } catch(err) {
        alert('Network error submitting response.');
    } finally {
        if (submitBtn) submitBtn.disabled = false;
    }
}

// Dynamically Append Message to Admin Stream
function appendAdminStreamMessage(msg) {
    const stream = document.getElementById('adminChatStream');
    if (!stream) return;

    if (document.getElementById(`admin-msg-${msg.id}`)) {
        return;
    }

    const wrapper = document.createElement('div');
    wrapper.id = `admin-msg-${msg.id}`;

    const isInternalNote = !!msg.is_internal_admin_note;
    const isOnBehalf = !!msg.is_on_behalf_of_supplier;
    const role = msg.sender_role || 'SUPER_ADMIN';
    const type = msg.message_type || 'TEXT';
    const hasViolation = !!msg.has_restricted_contact;
    const rawBody = msg.message_body || '';
    const timeStr = msg.created_at ? msg.created_at.substr(11, 8) : 'Just now';

    if (isInternalNote) {
        wrapper.className = 'mb-3 p-3 bg-warning bg-opacity-10 border border-warning rounded-3';
        wrapper.innerHTML = `
            <div class="d-flex align-items-center justify-content-between text-warning fw-bold small mb-1">
                <span><i class="bi bi-lock-fill me-1"></i> PRIVATE SUPER ADMIN NOTE (STRICTLY HIDDEN FROM BUYER & SUPPLIER)</span>
                <span>${timeStr}</span>
            </div>
            <div class="text-dark small bg-white p-2.5 rounded border border-warning border-opacity-25 whitespace-pre-wrap">
                ${escapeAdminHtml(rawBody)}
            </div>
        `;
        stream.appendChild(wrapper);
        return;
    }

    if (type === 'SYSTEM') {
        wrapper.className = 'mb-3 text-center my-2';
        wrapper.innerHTML = `
            <span class="badge bg-secondary bg-opacity-25 text-secondary px-3 py-1.5 rounded-pill small">
                <i class="bi bi-info-circle me-1"></i> ${escapeAdminHtml(rawBody)}
                <span class="opacity-75 ms-1">${timeStr}</span>
            </span>
        `;
        stream.appendChild(wrapper);
        return;
    }

    wrapper.className = 'mb-3';

    let badgeHtml = '';
    if (role === 'BUYER') {
        badgeHtml = `<span class="badge bg-primary text-white" style="font-size: 10px;">BUYER</span> <span class="fw-bold small text-slate-800">${escapeAdminHtml(msg.sender_company || msg.sender_first_name || 'Buyer')}</span>`;
    } else if (role === 'SUPER_ADMIN') {
        badgeHtml = `<span class="badge text-white" style="background:#7e22ce; font-size: 10px;">SUPER ADMIN OFFICIAL SUPPORT</span>`;
    } else if (isOnBehalf) {
        badgeHtml = `<span class="badge bg-warning text-dark" style="font-size: 10px;">ON BEHALF OF SUPPLIER</span> <span class="text-muted small">(Authorized Admin Intermediary)</span>`;
    } else {
        badgeHtml = `<span class="badge bg-success text-white" style="font-size: 10px;">SUPPLIER</span> <span class="fw-bold small text-teal-800"><?= htmlspecialchars($conversation['supplier_company_name']) ?></span>`;
    }

    const violationHtml = hasViolation ? `
        <div class="alert alert-danger py-1 px-2 mb-2 text-danger small d-flex align-items-center gap-1.5" style="font-size: 11px;">
            <i class="bi bi-shield-slash-fill"></i>
            <span>POLICY VIOLATION DETECTED: Message contains phone number, email, or off-platform handle.</span>
        </div>
    ` : '';

    // Attachment card handling
    let meta = msg.metadata;
    if (typeof meta === 'string') {
        try { meta = JSON.parse(meta); } catch(e) { meta = {}; }
    }
    meta = meta || {};
    const attachId = msg.attachment_id || meta.attachment_id || 0;
    const hasAttachment = !!msg.attachment_path || !!attachId || type === 'ATTACHMENT';
    const isAutoAttachText = hasAttachment && rawBody.startsWith('Sent an attachment:');

    let textHtml = (!isAutoAttachText && rawBody.trim())
        ? `<div class="text-slate-800 text-sm whitespace-pre-wrap leading-relaxed">${escapeAdminHtml(rawBody)}</div>`
        : '';

    let attachHtml = '';
    if (hasAttachment) {
        let fileName = msg.attachment_name || '';
        let ext = (msg.attachment_type || '').toLowerCase();
        if (!fileName) {
            const m = rawBody.match(/Sent an attachment:\s*(.*?)\s*\((.*?)\)/i);
            if (m) {
                fileName = m[1].trim();
                ext = (ext || m[2].trim()).toLowerCase();
            } else {
                fileName = 'Document';
            }
        }
        if (!ext && fileName.includes('.')) {
            ext = fileName.split('.').pop().toLowerCase();
        }

        const isImg = ['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext);
        const isPdf = (ext === 'pdf');
        const fileUrl = msg.attachment_path ? `${baseUrl}${msg.attachment_path}` : (attachId ? `${baseUrl}/api/chat/attachments/${attachId}/download` : '#');
        const downloadUrl = attachId ? `${baseUrl}/api/chat/attachments/${attachId}/download` : fileUrl;
        const fileSize = msg.attachment_size ? `${Math.round(msg.attachment_size / 1024 * 10) / 10} KB` : '';

        if (isImg) {
            attachHtml = `
                <div class="mt-2.5 rounded-3 border overflow-hidden bg-light" style="max-width: 320px;">
                    <a href="${fileUrl}" target="_blank" class="d-block text-decoration-none">
                        <img src="${fileUrl}" alt="${escapeAdminHtml(fileName)}" class="img-fluid" style="max-height: 180px; width: 100%; object-fit: cover;">
                        <div class="p-2 bg-white small d-flex justify-content-between align-items-center text-muted border-top">
                            <span class="text-truncate fw-semibold text-dark" style="max-width: 200px; font-size: 11px;">${escapeAdminHtml(fileName)}</span>
                            <i class="bi bi-box-arrow-up-right small"></i>
                        </div>
                    </a>
                    <div class="px-2 py-1 bg-light d-flex justify-content-between align-items-center border-top" style="font-size: 11px;">
                        <span class="text-muted">${fileSize}</span>
                        <a href="${downloadUrl}" class="text-primary fw-bold text-decoration-none">
                            <i class="bi bi-download me-1"></i> Download
                        </a>
                    </div>
                </div>
            `;
        } else if (isPdf) {
            attachHtml = `
                <div class="mt-2.5 p-2.5 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-3 d-flex align-items-center justify-content-between gap-2" style="max-width: 340px;">
                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                        <span class="badge bg-danger p-2 fw-bold" style="font-size: 10px;">PDF</span>
                        <div class="text-truncate small">
                            <span class="fw-bold d-block text-truncate text-dark" style="font-size: 11px;">${escapeAdminHtml(fileName)}</span>
                            <span class="text-muted" style="font-size: 10px;">${fileSize}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                        <a href="${fileUrl}" target="_blank" class="btn btn-sm btn-light border py-1 px-2" style="font-size: 11px;" title="View PDF">
                            <i class="bi bi-eye me-1 text-danger"></i> View
                        </a>
                        <a href="${downloadUrl}" class="btn btn-sm btn-danger py-1 px-2 text-white" style="font-size: 11px;" title="Download PDF">
                            <i class="bi bi-download"></i>
                        </a>
                    </div>
                </div>
            `;
        } else {
            attachHtml = `
                <div class="mt-2.5 p-2 bg-light border rounded-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                        <i class="bi bi-paperclip fs-5 text-primary"></i>
                        <div class="text-truncate small">
                            <span class="fw-bold d-block text-truncate">${escapeAdminHtml(fileName)}</span>
                            <span class="text-muted" style="font-size: 10px;">${ext.toUpperCase()} • ${fileSize}</span>
                        </div>
                    </div>
                    <a href="${downloadUrl}" class="btn btn-sm btn-outline-primary py-1 px-2" title="Download Document">
                        <i class="bi bi-download"></i>
                    </a>
                </div>
            `;
        }
    }

    const isBuyer = (role === 'BUYER');
    const cardBg = isBuyer ? '#ffffff' : (role === 'SUPER_ADMIN' ? '#f3e8ff' : '#f0fdf4');

    wrapper.innerHTML = `
        <div class="d-flex ${isBuyer ? 'justify-content-start align-items-start gap-2.5' : 'justify-content-end align-items-start gap-2.5'}">
            ${isBuyer ? `
                <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-center shadow-xs flex-shrink-0 mt-1" style="width: 32px; height: 32px; font-size: 12px;">
                    ${(msg.sender_company || msg.sender_first_name || 'B').charAt(0).toUpperCase()}
                </div>
            ` : ''}
            <div class="card shadow-xs border p-3 rounded-4" style="max-width: 80%; background-color: ${cardBg};">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-1">
                    <div class="d-flex align-items-center gap-1.5">
                        ${badgeHtml}
                    </div>
                    <span class="text-muted small" style="font-size: 10px;">${timeStr}</span>
                </div>
                ${violationHtml}
                ${textHtml}
                ${attachHtml}
            </div>
        </div>
    `;

    stream.appendChild(wrapper);
}

function escapeAdminHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
