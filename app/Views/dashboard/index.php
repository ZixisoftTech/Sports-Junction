<?php
use App\Core\Response;
$baseUrl = Response::baseUrl();
?>
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm p-4 text-white" style="background: linear-gradient(135deg, #1E3A8A 0%, #172554 100%); border-radius: 14px;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <span class="badge bg-danger mb-2 px-3 py-1.5 fw-bold letter-spacing-1">SUPER ADMIN PORTAL</span>
                    <h3 class="fw-bold mb-1">Sports Junction Master Management</h3>
                    <p class="mb-0 text-white-50 small">Manage core geographic, taxonomy, product specifications and catalog units for India's Sports Manufacturing Marketplace.</p>
                </div>
                <div class="mt-3 mt-md-0 d-flex gap-2">
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/masters/countries" class="btn btn-light btn-sm fw-semibold px-3">
                        <i class="bi bi-globe-americas me-1 text-primary"></i> Countries
                    </a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/masters/product-certificates" class="btn btn-outline-light btn-sm fw-semibold px-3">
                        <i class="bi bi-patch-check me-1"></i> Certificates
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Master Statistics Cards -->
<div class="row g-3 mb-4">
    <!-- Countries -->
    <div class="col-sm-6 col-xl-2">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Countries</span>
                    <span class="p-2 bg-primary bg-opacity-10 text-primary rounded-circle"><i class="bi bi-globe-americas fs-5"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-dark"><?= number_format($counts['total_countries'] ?? 0) ?></h3>
                <div class="small text-success"><i class="bi bi-check-circle-fill me-1"></i><?= $counts['active_countries'] ?? 0 ?> Active</div>
                <a href="<?= htmlspecialchars($baseUrl) ?>/admin/masters/countries" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- Product Certificates -->
    <div class="col-sm-6 col-xl-2">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Certificates</span>
                    <span class="p-2 bg-info bg-opacity-10 text-info rounded-circle"><i class="bi bi-patch-check fs-5"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-dark"><?= number_format($counts['total_certificates'] ?? 0) ?></h3>
                <div class="small text-info"><i class="bi bi-award me-1"></i>Standards</div>
                <a href="<?= htmlspecialchars($baseUrl) ?>/admin/masters/product-certificates" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- Certification Tags -->
    <div class="col-sm-6 col-xl-2">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Cert Tags</span>
                    <span class="p-2 bg-success bg-opacity-10 text-success rounded-circle"><i class="bi bi-bookmark-check fs-5"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-dark"><?= number_format($counts['total_tags'] ?? 0) ?></h3>
                <div class="small text-success"><i class="bi bi-tag me-1"></i>Compliance</div>
                <a href="<?= htmlspecialchars($baseUrl) ?>/admin/masters/certification-tags" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- Business Types -->
    <div class="col-sm-6 col-xl-2">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Business Types</span>
                    <span class="p-2 bg-warning bg-opacity-10 text-warning rounded-circle"><i class="bi bi-briefcase fs-5"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-dark"><?= number_format($counts['total_business_types'] ?? 0) ?></h3>
                <div class="small text-muted"><i class="bi bi-tags me-1"></i>Taxonomies</div>
                <a href="<?= htmlspecialchars($baseUrl) ?>/admin/masters/business-types" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- Price Units -->
    <div class="col-sm-6 col-xl-2">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Price Units</span>
                    <span class="p-2 bg-danger bg-opacity-10 text-danger rounded-circle"><i class="bi bi-currency-rupee fs-5"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-dark"><?= number_format($counts['total_price_units'] ?? 0) ?></h3>
                <div class="small text-muted"><i class="bi bi-tag-fill me-1"></i>Catalog Units</div>
                <a href="<?= htmlspecialchars($baseUrl) ?>/admin/masters/price-units" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- MOQ Units -->
    <div class="col-sm-6 col-xl-2">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-bold text-uppercase">MOQ Units</span>
                    <span class="p-2 bg-purple bg-opacity-10 text-primary rounded-circle"><i class="bi bi-boxes fs-5"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-dark"><?= number_format($counts['total_moq_units'] ?? 0) ?></h3>
                <div class="small text-muted"><i class="bi bi-box-seam me-1"></i>Bulk Order Units</div>
                <a href="<?= htmlspecialchars($baseUrl) ?>/admin/masters/moq-units" class="stretched-link"></a>
            </div>
        </div>
    </div>
</div>

<!-- Business Entities & Dependency Safety Overview -->
<div class="row g-4 mb-4">
    <div class="col-md-6" id="suppliers">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold m-0"><i class="bi bi-shield-check text-success me-2"></i>Protected Marketplace Suppliers</h6>
                <span class="badge bg-success-subtle text-success border"><?= $counts['total_suppliers'] ?? 0 ?> Registered</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Suppliers reference Business Types, Countries, States, and Cities. To protect business history, master records attached to suppliers are protected against accidental deletion.</p>
                <div class="list-group list-group-flush small">
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <span class="fw-bold text-dark d-block">Meerut Sports Gear Ltd</span>
                            <span class="text-muted">Direct Manufacturer • Meerut, Uttar Pradesh, India</span>
                        </div>
                        <span class="badge bg-primary">ACTIVE</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <span class="fw-bold text-dark d-block">Jalandhar International Inflatables</span>
                            <span class="text-muted">Direct Manufacturer • Jalandhar, Punjab, India</span>
                        </div>
                        <span class="badge bg-primary">ACTIVE</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6" id="products">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold m-0"><i class="bi bi-shield-check text-success me-2"></i>Catalog Products & Linked Units</h6>
                <span class="badge bg-primary-subtle text-primary border"><?= $counts['total_products'] ?? 0 ?> Products</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Products are priced with Price Units and ordered with MOQ Units. These units cannot be deleted while products are actively referencing them.</p>
                <div class="list-group list-group-flush small">
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <span class="fw-bold text-dark d-block">Grade-A English Willow Cricket Bat</span>
                            <span class="text-muted">Price: $55.00 / Piece • MOQ: 25 Pieces</span>
                        </div>
                        <span class="badge bg-success">ACTIVE</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <span class="fw-bold text-dark d-block">FIFA Quality Pro Synthetic Leather Football</span>
                            <span class="text-muted">Price: $12.50 / Piece • MOQ: 100 Pieces</span>
                        </div>
                        <span class="badge bg-success">ACTIVE</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Super Admin Audit Trail -->
<div class="card border-0 shadow-sm" id="audit">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <div>
            <h6 class="fw-bold m-0"><i class="bi bi-journal-text text-primary me-2"></i>Recent Master Management Audit Trail</h6>
            <small class="text-muted">Immutable log of creates, modifications, status toggles, and permanent deletions</small>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Timestamp</th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Target Record</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentAudits)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No audit logs recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentAudits as $log): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></td>
                                <td class="small fw-semibold"><?= htmlspecialchars($log['user_name'] ?? 'Super Admin') ?></td>
                                <td>
                                    <?php
                                    $actionBadge = match ($log['action']) {
                                        'CREATE'          => 'bg-success',
                                        'UPDATE'          => 'bg-info',
                                        'STATUS_CHANGE'   => 'bg-warning text-dark',
                                        'DELETE'          => 'bg-danger',
                                        'CASCADE_DELETE'  => 'bg-danger-subtle text-danger border border-danger',
                                        default           => 'bg-secondary',
                                    };
                                    ?>
                                    <span class="badge <?= $actionBadge ?> small"><?= htmlspecialchars($log['action']) ?></span>
                                </td>
                                <td class="small fw-bold text-dark"><?= htmlspecialchars($log['entity_type']) ?></td>
                                <td class="small"><?= htmlspecialchars($log['record_identifier'] ?? ('#' . $log['entity_id'])) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($log['details'] ?? '-') ?></td>
                                <td class="small text-muted"><code><?= htmlspecialchars($log['ip_address'] ?? '-') ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
