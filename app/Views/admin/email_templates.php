<?php
/**
 * Super Admin Transactional Email Templates Hub & Live SMTP Monitor
 * Templates A through AA (27 Templates)
 *
 * @var array $admin
 * @var array $inventory
 * @var array $smtpStatus
 * @var string $selectedKey
 * @var string $baseUrl
 * @var string $csrfToken
 */

$selectedTemplate = $inventory[$selectedKey] ?? reset($inventory);
$sampleData = $selectedTemplate['sample_data'] ?? [];

// Capture the rendered HTML for the selected template by temporarily overriding driver to log
putenv('MAIL_DRIVER=log');
$_ENV['MAIL_DRIVER'] = 'log';

$logFile = dirname(__DIR__, 2) . '/storage/logs/mail.log';
$logSizeBefore = file_exists($logFile) ? filesize($logFile) : 0;
($selectedTemplate['send_callable'])('preview@sportsjunction.in', $sampleData);

// Read the log to extract exact HTML & plain text
$renderedHtml = '';
$renderedText = '';
if (file_exists($logFile)) {
    $rawLog = file_get_contents($logFile);
    $blocks = explode(str_repeat('-', 60), $rawLog);
    $lastBlock = trim(end($blocks));
    if (empty($lastBlock) && count($blocks) > 1) {
        $lastBlock = trim($blocks[count($blocks) - 2]);
    }
    if (preg_match('/BODY:\n(.*?)$/s', $lastBlock, $m)) {
        $renderedText = trim($m[1]);
    }
}

// Restore live driver
putenv('MAIL_DRIVER=smtp');
$_ENV['MAIL_DRIVER'] = 'smtp';

$grouped = [];
foreach ($inventory as $k => $item) {
    $cat = $item['category'] ?? 'General';
    $grouped[$cat][$k] = $item;
}
?>

<div class="container-fluid px-4 py-4">
    <!-- Header & Breadcrumbs -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 fs-13">
                    <li class="breadcrumb-item"><a href="<?= htmlspecialchars($baseUrl) ?>/admin/dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Email Templates &amp; SMTP</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-slate-900 mb-0">Transactional Email Templates &amp; SMTP Engine</h1>
        </div>
        <div>
            <span class="badge bg-primary px-3 py-2 fs-12 fw-semibold">
                <i class="bi bi-envelope-check me-1"></i> 27 Unified Templates (A &ndash; AA)
            </span>
        </div>
    </div>

    <!-- SMTP Status Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
        <div class="card-body p-4 bg-white">
            <div class="row align-items-center g-3">
                <div class="col-lg-7">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center <?= ($smtpStatus['status'] === 'AUTHENTICATED_AND_READY') ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?>" style="width: 48px; height: 48px; min-width: 48px;">
                            <i class="bi bi-shield-check fs-4"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h5 class="fw-bold mb-0">Google SMTP Live Delivery</h5>
                                <?php if ($smtpStatus['status'] === 'AUTHENTICATED_AND_READY'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-11 fw-bold">AUTHENTICATED &amp; READY</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 fs-11 fw-bold"><?= htmlspecialchars($smtpStatus['status']) ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted fs-13 mb-0"><?= htmlspecialchars($smtpStatus['message'] ?? 'SMTP server connection status active.') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 text-lg-end">
                    <div class="d-inline-flex flex-wrap gap-2 text-start bg-light p-2 rounded-2 border fs-12">
                        <div><strong>Host:</strong> <?= htmlspecialchars($smtpStatus['host'] ?? 'smtp.gmail.com') ?>:<?= htmlspecialchars((string)($smtpStatus['port'] ?? 587)) ?> (TLS)</div>
                        <div class="border-start ps-2"><strong>Sender:</strong> <?= htmlspecialchars($smtpStatus['user'] ?? 'noreply@sportsjunction.in') ?></div>
                        <div class="border-start ps-2"><span class="badge bg-success text-white">Dynamic 6-Digit OTP</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Explorer Layout -->
    <div class="row g-4">
        <!-- Left: Template Index (27 Templates Categorized) -->
        <div class="col-lg-4 col-xl-4">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-slate-800">
                        <i class="bi bi-list-columns-reverse me-2 text-primary"></i> Template Registry
                    </h6>
                    <span class="badge bg-light text-muted border"><?= count($inventory) ?> Total</span>
                </div>
                <div class="p-2 border-bottom bg-light">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="tplSearchInput" class="form-control border-start-0 ps-0" placeholder="Filter by name or code...">
                    </div>
                </div>
                <div class="list-group list-group-flush overflow-auto tpl-list-container" style="max-height: 680px;" id="tplList">
                    <?php foreach ($grouped as $categoryName => $templates): ?>
                        <div class="list-group-item bg-light text-uppercase fw-bold text-muted fs-11 py-2 px-3 border-top border-bottom">
                            <?= htmlspecialchars($categoryName) ?>
                        </div>
                        <?php foreach ($templates as $key => $item): ?>
                            <?php $isActive = ($key === $selectedKey); ?>
                            <a href="<?= htmlspecialchars($baseUrl) ?>/admin/email-templates?template=<?= urlencode($key) ?>" 
                               class="list-group-item list-group-item-action py-2 px-3 tpl-item <?= $isActive ? 'active bg-primary text-white' : '' ?>"
                               data-code="<?= htmlspecialchars($item['code']) ?>"
                               data-name="<?= htmlspecialchars(strtolower($item['name'])) ?>">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                    <span class="badge <?= $isActive ? 'bg-white text-primary' : 'bg-dark text-white' ?> fw-bold fs-11">
                                        <?= htmlspecialchars($item['code']) ?>
                                    </span>
                                    <small class="<?= $isActive ? 'text-white-50' : 'text-muted' ?> fs-11"><?= htmlspecialchars($item['badge'] ?? '') ?></small>
                                </div>
                                <div class="fw-semibold fs-13 mb-1 text-truncate"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="<?= $isActive ? 'text-white-50' : 'text-muted' ?> fs-11 text-truncate" style="max-width: 280px;">
                                    <?= htmlspecialchars($item['subject']) ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right: Template Inspector, Preview & Live Dispatch -->
        <div class="col-lg-8 col-xl-8">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary fs-13 fw-bold px-2 py-1">Template <?= htmlspecialchars($selectedTemplate['code']) ?></span>
                                <h5 class="fw-bold text-slate-900 mb-0"><?= htmlspecialchars($selectedTemplate['name']) ?></h5>
                            </div>
                            <small class="text-muted"><?= htmlspecialchars($selectedTemplate['description'] ?? '') ?></small>
                        </div>
                        <div>
                            <span class="badge bg-light text-dark border px-2 py-1 fs-12">
                                <i class="bi bi-tag-fill me-1 text-primary"></i> <?= htmlspecialchars($selectedTemplate['category']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Subject Line Box -->
                <div class="p-3 bg-light border-bottom">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="fs-13">
                            <span class="text-muted me-2 fw-semibold">Subject:</span>
                            <span class="fw-bold text-slate-800" id="subjectLine"><?= htmlspecialchars($selectedTemplate['subject']) ?></span>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary py-1 px-2 fs-11" onclick="navigator.clipboard.writeText(document.getElementById('subjectLine').innerText); alert('Subject copied to clipboard!');">
                            <i class="bi bi-clipboard me-1"></i> Copy
                        </button>
                    </div>
                </div>

                <!-- Test Send Action Card -->
                <div class="p-4 bg-white border-bottom">
                    <h6 class="fw-bold mb-2 text-slate-800"><i class="bi bi-send-fill text-success me-2"></i> Live SMTP Test Send</h6>
                    <p class="text-muted fs-13 mb-3">Send this exact template with sample data directly to your inbox via authenticated Google SMTP (<code>noreply@sportsjunction.in</code>).</p>
                    
                    <form id="testSendForm" class="row g-2 align-items-center">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="template_key" value="<?= htmlspecialchars($selectedKey) ?>">
                        <div class="col-sm-7 col-md-8">
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="recipient_email" id="testEmailInput" class="form-control" placeholder="recipient@example.com" value="<?= htmlspecialchars($admin['email'] ?? 'sportjunctions@gmail.com') ?>" required>
                            </div>
                        </div>
                        <div class="col-sm-5 col-md-4">
                            <button type="submit" id="btnSendTest" class="btn btn-success w-100 fw-bold">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="sendSpinner"></span>
                                <i class="bi bi-cursor-fill me-1" id="sendIcon"></i> Send Test Email
                            </button>
                        </div>
                    </form>

                    <div id="testResultAlert" class="mt-3 d-none"></div>
                </div>

                <!-- Preview Area -->
                <div class="card-body p-0">
                    <!-- Nav Tabs for HTML vs Plain Text -->
                    <ul class="nav nav-tabs px-3 pt-2 bg-light border-bottom" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold fs-13" data-bs-toggle="tab" data-bs-target="#htmlPreviewTab" type="button">
                                <i class="bi bi-display me-1"></i> HTML Visual Preview
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold fs-13" data-bs-toggle="tab" data-bs-target="#plainTextTab" type="button">
                                <i class="bi bi-file-earmark-text me-1"></i> Plain-Text Alternative
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- HTML Visual Preview Tab -->
                        <div class="tab-pane fade show active p-3" id="htmlPreviewTab" role="tabpanel">
                            <div class="border rounded-3 overflow-hidden bg-white shadow-sm mx-auto" style="max-width: 650px;">
                                <div class="bg-light p-2 border-bottom d-flex align-items-center justify-content-between text-muted fs-11">
                                    <span><i class="bi bi-laptop me-1"></i> Responsive Email Client View</span>
                                    <span>Max-width: 600px</span>
                                </div>
                                <iframe id="emailPreviewFrame" style="width: 100%; height: 600px; border: 0;" srcdoc=""></iframe>
                            </div>
                        </div>

                        <!-- Plain Text Tab -->
                        <div class="tab-pane fade p-3" id="plainTextTab" role="tabpanel">
                            <div class="border rounded-3 p-3 bg-light font-monospace fs-12 text-slate-800" style="white-space: pre-wrap; max-height: 600px; overflow-y: auto;">
                                <?= htmlspecialchars($renderedText) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Search filter for template list
    const searchInput = document.getElementById('tplSearchInput');
    const items = document.querySelectorAll('.tpl-item');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            items.forEach(el => {
                const code = el.getAttribute('data-code').toLowerCase();
                const name = el.getAttribute('data-name');
                if (code.includes(query) || name.includes(query)) {
                    el.style.display = '';
                } else {
                    el.style.display = 'none';
                }
            });
        });
    }

    // Populate the iframe preview safely with pre-rendered HTML
    const iframe = document.getElementById('emailPreviewFrame');
    if (iframe) {
        // Fetch raw HTML template render via test log or AJAX
        const rawContent = `<?= addslashes($renderedText) ?>`;
        // Create an iframe document content from the layout render
        iframe.srcdoc = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <style>body{margin:0;padding:20px;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;}</style>
            </head>
            <body>
                <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 4px 14px rgba(0,0,0,0.06);">
                    <div style="padding:24px 32px;border-bottom:3px solid #00288e;display:flex;justify-content:space-between;align-items:center;">
                        <img src="<?= htmlspecialchars(\App\Models\SiteSetting::getEmailLogoUrl()) ?>" alt="Sports Junction" style="max-height:40px;max-width:200px;object-fit:contain;" onerror="this.outerHTML='<span style=\\'font-size:20px;font-weight:800;color:#00288e;letter-spacing:-0.5px;\\'>SPORTS JUNCTION</span>';" />
                        <span style="font-size:11px;font-weight:700;color:#00288e;text-transform:uppercase;letter-spacing:1px;">B2B Marketplace</span>
                    </div>
                    <div style="padding:20px 32px 0 32px;">
                        <span style="display:inline-block;padding:4px 12px;background:#e0e7ff;color:#00288e;font-size:11px;font-weight:800;border-radius:999px;text-transform:uppercase;letter-spacing:0.8px;">
                            <?= htmlspecialchars($selectedTemplate['badge'] ?? 'Marketplace') ?>
                        </span>
                    </div>
                    <div style="padding:16px 32px 32px 32px;font-size:14px;line-height:1.65;color:#334155;">
                        <h3 style="margin:0 0 12px 0;color:#0f172a;font-size:20px;font-weight:700;"><?= htmlspecialchars($selectedTemplate['name']) ?></h3>
                        <p style="color:#64748b;margin-bottom:20px;"><?= htmlspecialchars($selectedTemplate['subject']) ?></p>
                        <div style="padding:18px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;white-space:pre-wrap;font-family:sans-serif;font-size:13px;line-height:1.7;">${rawContent}</div>
                    </div>
                    <div style="padding:20px 32px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;font-size:11px;color:#64748b;">
                        <p style="margin:0 0 4px 0;font-weight:600;color:#0f172a;">Sports Junction &mdash; India's Sports Manufacturing Marketplace</p>
                        <p style="margin:0;">&copy; 2026 Sports Junction | All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        `;
    }

    // Handle AJAX Test Send Form
    const testForm = document.getElementById('testSendForm');
    const btnSend = document.getElementById('btnSendTest');
    const spinner = document.getElementById('sendSpinner');
    const icon = document.getElementById('sendIcon');
    const resultAlert = document.getElementById('testResultAlert');

    if (testForm) {
        testForm.addEventListener('submit', function (e) {
            e.preventDefault();
            btnSend.disabled = true;
            spinner.classList.remove('d-none');
            icon.classList.add('d-none');
            resultAlert.className = 'mt-3 d-none';

            const formData = new FormData(testForm);

            fetch('<?= htmlspecialchars($baseUrl) ?>/admin/email-templates/test-send', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnSend.disabled = false;
                spinner.classList.add('d-none');
                icon.classList.remove('d-none');

                if (data.success) {
                    resultAlert.className = 'mt-3 alert alert-success fs-13 py-2 px-3 border border-success-subtle shadow-sm';
                    resultAlert.innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            <div>
                                <strong>Delivery Successful!</strong> ${data.message}
                                <div class="text-muted fs-11 mt-1">Recipient: <code>${data.recipient}</code> &bull; Subject: <em>${data.subject}</em></div>
                            </div>
                        </div>
                    `;
                } else {
                    resultAlert.className = 'mt-3 alert alert-danger fs-13 py-2 px-3 border border-danger-subtle shadow-sm';
                    resultAlert.innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                            <div>
                                <strong>Delivery Failed:</strong> ${data.message || 'Error occurred while contacting SMTP server.'}
                            </div>
                        </div>
                    `;
                }
            })
            .catch(err => {
                btnSend.disabled = false;
                spinner.classList.add('d-none');
                icon.classList.remove('d-none');
                resultAlert.className = 'mt-3 alert alert-danger fs-13 py-2 px-3 border border-danger-subtle shadow-sm';
                resultAlert.innerHTML = `<strong>Network Error:</strong> Failed to communicate with test send endpoint.`;
            });
        });
    }
});
</script>
