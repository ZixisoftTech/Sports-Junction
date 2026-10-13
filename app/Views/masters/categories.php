<?php
use App\Core\Response;
use App\Helpers\CSRF;
$baseUrl = Response::baseUrl();
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-grid-fill text-primary me-2"></i>Category Management</h4>
        <p class="text-muted small mb-0">Organize sports product categories, visual branding cards, display priority, and status.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <!-- Export Dropdown -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle shadow-sm px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download me-1.5"></i> Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 small">
                <li><h6 class="dropdown-header text-uppercase text-muted" style="font-size: 11px;">Current Filtered Results</h6></li>
                <li><a class="dropdown-item btn-export-action" href="#" data-format="csv" data-scope="current"><i class="bi bi-file-earmark-text text-primary me-2"></i>Export CSV (Current)</a></li>
                <li><a class="dropdown-item btn-export-action" href="#" data-format="excel" data-scope="current"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Export Excel (Current)</a></li>
                <li><a class="dropdown-item btn-export-action" href="#" data-format="pdf" data-scope="current"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Export PDF (Current)</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header text-uppercase text-muted" style="font-size: 11px;">Complete Dataset</h6></li>
                <li><a class="dropdown-item btn-export-action" href="#" data-format="csv" data-scope="all"><i class="bi bi-file-earmark-text text-primary me-2"></i>Export All Records (CSV)</a></li>
                <li><a class="dropdown-item btn-export-action" href="#" data-format="excel" data-scope="all"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Export All Records (Excel)</a></li>
                <li><a class="dropdown-item btn-export-action" href="#" data-format="pdf" data-scope="all"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Export All Records (PDF)</a></li>
            </ul>
        </div>

        <!-- Add New Button -->
        <button type="button" class="btn btn-sj-primary btn-sm shadow-sm px-3" id="btnAddNewCategory">
            <i class="bi bi-plus-lg me-1"></i> Add Category
        </button>
    </div>
</div>

<div class="sj-card">
    <div class="sj-card-header bg-white py-3">
        <div class="row align-items-center w-100 g-2">
            <div class="col-sm-6">
                <h6 class="m-0 fw-bold text-dark">Sports Categories Master</h6>
            </div>
            <div class="col-sm-6 d-flex justify-content-sm-end align-items-center gap-2">
                <label for="categoryStatusFilter" class="small text-muted text-nowrap m-0">Status:</label>
                <select id="categoryStatusFilter" class="form-select form-select-sm" style="max-width: 140px;">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE">Active Only</option>
                    <option value="INACTIVE">Inactive Only</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="categoriesTable" class="table table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="width: 70px;">Banner</th>
                        <th>Category Name</th>
                        <th>Slug</th>
                        <th style="width: 80px;">Order</th>
                        <th style="width: 80px;">Subcats</th>
                        <th style="width: 80px;">Products</th>
                        <th style="width: 90px;">Status</th>
                        <th style="width: 110px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Category Modal (Add / Edit) -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold text-dark" id="categoryModalLabel">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="categoryForm" enctype="multipart/form-data">
                <input type="hidden" name="id" id="categoryId">
                <input type="hidden" name="_csrf" value="<?= CSRF::getToken() ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="catName" class="form-label fw-semibold small">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="catName" name="name" required placeholder="e.g. Sports & Fitness" maxlength="150">
                        </div>
                        <div class="col-md-5">
                            <label for="catSlug" class="form-label fw-semibold small">URL Slug <span class="text-muted">(Optional, auto-generated)</span></label>
                            <input type="text" class="form-control form-control-sm" id="catSlug" name="slug" placeholder="e.g. sports-and-fitness" maxlength="160">
                        </div>

                        <div class="col-md-4">
                            <label for="catOrder" class="form-label fw-semibold small">Display Priority Order</label>
                            <input type="number" class="form-control form-control-sm" id="catOrder" name="display_order" value="0" min="0">
                            <div class="form-text small text-muted">Lower numbers appear first (0, 1, 2...)</div>
                        </div>
                        <div class="col-md-4">
                            <label for="catIcon" class="form-label fw-semibold small">Material Icon Name</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white" id="catIconPreviewWrapper">
                                    <span class="material-symbols-outlined text-primary fs-5" id="catIconPreview">fitness_center</span>
                                </span>
                                <input type="text" class="form-control" id="catIcon" name="icon" value="fitness_center" placeholder="e.g. fitness_center, sports_cricket">
                            </div>
                            <div class="form-text small text-muted">e.g. sports_cricket, sports_soccer, hiking, fitness_center</div>
                        </div>
                        <div class="col-md-4">
                            <label for="catStatus" class="form-label fw-semibold small">Status <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="catStatus" name="status" required>
                                <option value="ACTIVE">Active (Published)</option>
                                <option value="INACTIVE">Inactive (Hidden)</option>
                            </select>
                        </div>

                        <!-- Modern Image Upload & Preview Section -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small mb-1">Category Image / Banner</label>
                            <div class="sj-img-preview-card d-flex flex-column flex-sm-row align-items-center gap-3 p-3">
                                <div class="sj-img-preview-box flex-shrink-0" style="width: 130px; height: 95px;">
                                    <img id="catImagePreview" src="" alt="Preview" class="rounded object-fit-cover w-100 h-100">
                                    <span id="catImagePlaceholder" class="text-muted small d-none"><i class="bi bi-image fs-3"></i></span>
                                </div>
                                <div class="flex-grow-1 w-100">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="fw-semibold small text-dark"><i class="bi bi-cloud-arrow-up text-primary me-1"></i>Upload New Image</span>
                                        <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none small" id="btnResetCatImage" title="Reset to default brand logo">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Use Default Logo
                                        </button>
                                    </div>
                                    <input type="file" class="form-control form-control-sm mb-2" id="catImageFile" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif">
                                    <div class="text-muted small" style="font-size: 11px;">Max 8MB. Recommended size: 600×400px (JPG, PNG, WEBP). Live preview appears instantly above.</div>
                                    
                                    <div class="mt-2 pt-2 border-top">
                                        <label for="catImageUrl" class="form-label small text-muted mb-1" style="font-size: 11px;">Or Stored Image Path / External URL (Optional):</label>
                                        <input type="text" class="form-control form-control-sm font-monospace" id="catImageUrl" name="image" placeholder="/uploads/categories/... or https://..." style="font-size: 12px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="catDescription" class="form-label fw-semibold small">Description / SEO Summary</label>
                            <textarea class="form-control form-control-sm" id="catDescription" name="description" rows="3" placeholder="Brief summary displayed on marketplace category landing cards..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sj-primary btn-sm px-4" id="btnSaveCategory">
                        <i class="bi bi-check-lg me-1"></i> Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = window.SportsJunction.baseUrl;
    const defaultLogo = window.SportsJunction.defaultLogo;

    // Image file live preview
    const fileInput = document.getElementById('catImageFile');
    const urlInput = document.getElementById('catImageUrl');
    const previewImg = document.getElementById('catImagePreview');
    const placeholder = document.getElementById('catImagePlaceholder');
    const resetBtn = document.getElementById('btnResetCatImage');
    const iconInput = document.getElementById('catIcon');
    const iconPreview = document.getElementById('catIconPreview');

    // Live icon preview
    if (iconInput && iconPreview) {
        iconInput.addEventListener('input', function () {
            iconPreview.textContent = this.value.trim() || 'category';
        });
    }

    function updatePreview(src) {
        if (src && src.trim() !== '') {
            previewImg.src = window.SportsJunction.formatImageUrl(src);
        } else {
            previewImg.src = defaultLogo;
        }
        previewImg.onerror = function () {
            this.onerror = null;
            this.src = defaultLogo;
        };
        previewImg.classList.remove('d-none');
        if (placeholder) placeholder.classList.add('d-none');
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewImg.classList.remove('d-none');
                    if (placeholder) placeholder.classList.add('d-none');
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    if (urlInput) {
        urlInput.addEventListener('input', function () {
            if ((!fileInput.files || fileInput.files.length === 0)) {
                updatePreview(this.value.trim());
            }
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (fileInput) fileInput.value = '';
            if (urlInput) urlInput.value = '';
            updatePreview('');
        });
    }

    MasterModule.init({
        entity: 'categories',
        entityTitle: 'Category',
        tableSelector: '#categoriesTable',
        statusFilterSelector: '#categoryStatusFilter',
        addNewBtnSelector: '#btnAddNewCategory',
        modalSelector: '#categoryModal',
        formSelector: '#categoryForm',
        useFormData: true,
        columns: [
            { data: 'id', width: '50px' },
            { 
                data: 'image', 
                width: '70px',
                orderable: false,
                render: function (data) {
                    const imgUrl = window.SportsJunction.formatImageUrl(data);
                    return `<img src="${imgUrl}" class="rounded object-fit-cover border bg-white p-0.5 shadow-xs" style="width: 48px; height: 36px;" alt="Banner" onerror="this.onerror=null;this.src='${defaultLogo}';">`;
                }
            },
            { 
                data: 'name',
                render: function (data, type, row) {
                    const iconName = (row.icon && row.icon.trim()) ? row.icon.trim() : 'sports_tennis';
                    return `
                        <div class="d-flex align-items-center">
                            <span class="material-symbols-outlined text-primary fs-5 me-2 flex-shrink-0">${iconName}</span>
                            <span class="fw-bold">${data}</span>
                        </div>
                    `;
                }
            },
            { 
                data: 'slug',
                render: function (data) {
                    return `<code class="text-primary small">${data}</code>`;
                }
            },
            { 
                data: 'display_order', 
                width: '70px',
                className: 'text-center',
                render: function (data) {
                    return `<span class="badge bg-secondary-subtle text-secondary border">${data}</span>`;
                }
            },
            { 
                data: 'subcategories_count',
                width: '70px',
                className: 'text-center',
                render: function (data) {
                    return `<span class="badge bg-info-subtle text-info border">${data}</span>`;
                }
            },
            { 
                data: 'products_count',
                width: '70px',
                className: 'text-center',
                render: function (data) {
                    return `<span class="badge bg-primary-subtle text-primary border">${data}</span>`;
                }
            },
            { 
                data: 'status', 
                width: '85px',
                className: 'text-center',
                render: function (data, type, row) {
                    const isActive = data === 'ACTIVE';
                    const badgeClass = isActive ? 'bg-success' : 'bg-danger';
                    return `
                        <div class="d-flex align-items-center justify-content-center gap-1">
                            <span class="badge ${badgeClass}">${data}</span>
                            <button type="button" class="btn btn-sm btn-link p-0 text-muted btn-toggle-status" data-id="${row.id}" data-name="${row.name}" data-status="${data}" title="Toggle status">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                        </div>
                    `;
                }
            },
            { 
                data: null, 
                orderable: false, 
                searchable: false,
                width: '100px',
                className: 'text-end',
                render: function (data, type, row) {
                    return `
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary btn-edit-master" data-id="${row.id}" title="Edit Category">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete-master" data-id="${row.id}" data-name="${row.name}" title="Delete Category">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[4, 'asc']],
        onEditPopulate: function (data) {
            document.getElementById('categoryId').value = data.id || '';
            document.getElementById('catName').value = data.name || '';
            document.getElementById('catSlug').value = data.slug || '';
            document.getElementById('catOrder').value = data.display_order ?? 0;
            const iconVal = data.icon || 'fitness_center';
            document.getElementById('catIcon').value = iconVal;
            if (iconPreview) iconPreview.textContent = iconVal;
            document.getElementById('catStatus').value = data.status || 'ACTIVE';
            document.getElementById('catImageUrl').value = data.image || '';
            if (fileInput) fileInput.value = '';
            document.getElementById('catDescription').value = data.description || '';
            updatePreview(data.image);
        }
    });

    // Reset preview on Add New
    document.getElementById('btnAddNewCategory').addEventListener('click', function () {
        if (fileInput) fileInput.value = '';
        if (iconPreview) iconPreview.textContent = 'fitness_center';
        updatePreview('');
    });
});
</script>
