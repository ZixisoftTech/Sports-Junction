<?php
use App\Core\Response;
use App\Helpers\CSRF;
$baseUrl = Response::baseUrl();
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-diagram-3-fill text-primary me-2"></i>Subcategory Management</h4>
        <p class="text-muted small mb-0">Manage secondary sports product classifications linked to parent categories.</p>
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
        <button type="button" class="btn btn-sj-primary btn-sm shadow-sm px-3" id="btnAddNewSubcategory">
            <i class="bi bi-plus-lg me-1"></i> Add Subcategory
        </button>
    </div>
</div>

<div class="sj-card">
    <div class="sj-card-header bg-white py-3">
        <div class="row align-items-center w-100 g-2">
            <div class="col-sm-5">
                <h6 class="m-0 fw-bold text-dark">Subcategories Master List</h6>
            </div>
            <div class="col-sm-7 d-flex justify-content-sm-end align-items-center gap-2 flex-wrap">
                <label for="subcatCategoryFilter" class="small text-muted text-nowrap m-0">Category:</label>
                <select id="subcatCategoryFilter" class="form-select form-select-sm" style="max-width: 170px;">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="subcatStatusFilter" class="small text-muted text-nowrap m-0">Status:</label>
                <select id="subcatStatusFilter" class="form-select form-select-sm" style="max-width: 130px;">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE">Active Only</option>
                    <option value="INACTIVE">Inactive Only</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="subcategoriesTable" class="table table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="width: 70px;">Image</th>
                        <th>Subcategory Name</th>
                        <th>Parent Category</th>
                        <th>Slug</th>
                        <th style="width: 80px;">Order</th>
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

<!-- Subcategory Modal (Add / Edit) -->
<div class="modal fade" id="subcategoryModal" tabindex="-1" aria-labelledby="subcategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold text-dark" id="subcategoryModalLabel">Add Subcategory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="subcategoryForm" enctype="multipart/form-data">
                <input type="hidden" name="id" id="subcategoryId">
                <input type="hidden" name="_csrf" value="<?= CSRF::getToken() ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="subcatParent" class="form-label fw-semibold small">Parent Category <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="subcatParent" name="category_id" required>
                                <option value="">Select Category...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="subcatName" class="form-label fw-semibold small">Subcategory Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="subcatName" name="name" required placeholder="e.g. Cricket Bats" maxlength="150">
                        </div>

                        <div class="col-md-6">
                            <label for="subcatSlug" class="form-label fw-semibold small">URL Slug <span class="text-muted">(Optional, auto-generated)</span></label>
                            <input type="text" class="form-control form-control-sm" id="subcatSlug" name="slug" placeholder="e.g. cricket-bats" maxlength="160">
                        </div>
                        <div class="col-md-3">
                            <label for="subcatOrder" class="form-label fw-semibold small">Display Order</label>
                            <input type="number" class="form-control form-control-sm" id="subcatOrder" name="display_order" value="0" min="0">
                        </div>
                        <div class="col-md-3">
                            <label for="subcatStatus" class="form-label fw-semibold small">Status <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="subcatStatus" name="status" required>
                                <option value="ACTIVE">Active (Published)</option>
                                <option value="INACTIVE">Inactive (Hidden)</option>
                            </select>
                        </div>

                        <!-- Modern Image Upload & Preview Section -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small mb-1">Subcategory Image</label>
                            <div class="sj-img-preview-card d-flex flex-column flex-sm-row align-items-center gap-3 p-3">
                                <div class="sj-img-preview-box flex-shrink-0" style="width: 130px; height: 95px;">
                                    <img id="subcatImagePreview" src="" alt="Preview" class="rounded object-fit-cover w-100 h-100">
                                    <span id="subcatImagePlaceholder" class="text-muted small d-none"><i class="bi bi-image fs-3"></i></span>
                                </div>
                                <div class="flex-grow-1 w-100">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="fw-semibold small text-dark"><i class="bi bi-cloud-arrow-up text-primary me-1"></i>Upload New Image</span>
                                        <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none small" id="btnResetSubcatImage" title="Reset to default brand logo">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Use Default Logo
                                        </button>
                                    </div>
                                    <input type="file" class="form-control form-control-sm mb-2" id="subcatImageFile" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif">
                                    <div class="text-muted small" style="font-size: 11px;">Max 8MB. Recommended size: 500×500px (JPG, PNG, WEBP). Live preview appears instantly above.</div>
                                    
                                    <div class="mt-2 pt-2 border-top">
                                        <label for="subcatImageUrl" class="form-label small text-muted mb-1" style="font-size: 11px;">Or Stored Image Path / External URL (Optional):</label>
                                        <input type="text" class="form-control form-control-sm font-monospace" id="subcatImageUrl" name="image" placeholder="/uploads/subcategories/... or https://..." style="font-size: 12px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="subcatDescription" class="form-label fw-semibold small">Description</label>
                            <textarea class="form-control form-control-sm" id="subcatDescription" name="description" rows="3" placeholder="Brief description for SEO and subcategory navigation..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sj-primary btn-sm px-4" id="btnSaveSubcategory">
                        <i class="bi bi-check-lg me-1"></i> Save Subcategory
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

    // Image preview
    const fileInput = document.getElementById('subcatImageFile');
    const urlInput = document.getElementById('subcatImageUrl');
    const previewImg = document.getElementById('subcatImagePreview');
    const placeholder = document.getElementById('subcatImagePlaceholder');
    const resetBtn = document.getElementById('btnResetSubcatImage');

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
        entity: 'subcategories',
        entityTitle: 'Subcategory',
        tableSelector: '#subcategoriesTable',
        statusFilterSelector: '#subcatStatusFilter',
        addNewBtnSelector: '#btnAddNewSubcategory',
        modalSelector: '#subcategoryModal',
        formSelector: '#subcategoryForm',
        useFormData: true,
        getExtraFilters: function () {
            return {
                category_id: $('#subcatCategoryFilter').val()
            };
        },
        columns: [
            { data: 'id', width: '50px' },
            { 
                data: 'image', 
                width: '70px',
                orderable: false,
                render: function (data) {
                    const imgUrl = window.SportsJunction.formatImageUrl(data);
                    return `<img src="${imgUrl}" class="rounded object-fit-cover border bg-white p-0.5 shadow-xs" style="width: 48px; height: 36px;" alt="Subcategory" onerror="this.onerror=null;this.src='${defaultLogo}';">`;
                }
            },
            { 
                data: 'name',
                render: function (data) {
                    return `<strong>${data}</strong>`;
                }
            },
            { 
                data: 'category_name',
                render: function (data) {
                    return `<span class="badge bg-light text-dark border"><i class="bi bi-folder2-open text-primary me-1"></i>${data || '-'}</span>`;
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
                            <button type="button" class="btn btn-outline-primary btn-edit-master" data-id="${row.id}" title="Edit Subcategory">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete-master" data-id="${row.id}" data-name="${row.name}" title="Delete Subcategory">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[5, 'asc']],
        onEditPopulate: function (data) {
            document.getElementById('subcategoryId').value = data.id || '';
            document.getElementById('subcatParent').value = data.category_id || '';
            document.getElementById('subcatName').value = data.name || '';
            document.getElementById('subcatSlug').value = data.slug || '';
            document.getElementById('subcatOrder').value = data.display_order ?? 0;
            document.getElementById('subcatStatus').value = data.status || 'ACTIVE';
            document.getElementById('subcatImageUrl').value = data.image || '';
            if (fileInput) fileInput.value = '';
            document.getElementById('subcatDescription').value = data.description || '';
            updatePreview(data.image);
        }
    });

    // Reset preview on Add New
    document.getElementById('btnAddNewSubcategory').addEventListener('click', function () {
        if (fileInput) fileInput.value = '';
        updatePreview('');
    });

    // Reload on category filter change
    $('#subcatCategoryFilter').on('change', function () {
        $('#subcategoriesTable').DataTable().ajax.reload();
    });
});
</script>
