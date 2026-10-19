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
                            <label for="catStatus" class="form-label fw-semibold small">Status <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="catStatus" name="status" required>
                                <option value="ACTIVE">Active (Published)</option>
                                <option value="INACTIVE">Inactive (Hidden)</option>
                            </select>
                            <div class="form-text small text-muted">Overall category availability</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small d-block">Show on Homepage</label>
                            <div class="form-check form-switch pt-1">
                                <input type="hidden" name="show_on_homepage" value="0">
                                <input class="form-check-input fs-5" type="checkbox" role="switch" id="catShowOnHomepage" name="show_on_homepage" value="1">
                                <label class="form-check-label small fw-semibold text-muted ms-1" for="catShowOnHomepage" id="catShowOnHomepageLabel">
                                    OFF: Hidden from the homepage
                                </label>
                            </div>
                            <div class="form-text small text-muted">Toggle public homepage visibility</div>
                        </div>

                        <!-- Icon Selection (Searchable Library OR Custom File Upload) -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small mb-1">Category Icon</label>
                            
                            <ul class="nav nav-pills nav-fill mb-2 p-1 bg-light rounded border" id="catIconTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active py-1.5 px-3 small fw-semibold" id="tab-icon-library" data-bs-toggle="pill" data-bs-target="#pane-icon-library" type="button" role="tab">
                                        <i class="bi bi-grid-3x3-gap-fill me-1.5"></i> Searchable Icon Library
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-1.5 px-3 small fw-semibold" id="tab-icon-upload" data-bs-toggle="pill" data-bs-target="#pane-icon-upload" type="button" role="tab">
                                        <i class="bi bi-cloud-arrow-up-fill me-1.5"></i> Upload Custom Icon File
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content border rounded p-3 bg-white" id="catIconTabContent">
                                <!-- Tab 1: Icon Library -->
                                <div class="tab-pane fade show active" id="pane-icon-library" role="tabpanel">
                                    <div class="row g-2 align-items-center mb-2">
                                        <div class="col-sm-7">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                                <input type="text" class="form-control" id="catIconSearch" placeholder="Search icon (e.g. soccer, tennis, bike, gym)...">
                                            </div>
                                        </div>
                                        <div class="col-sm-5">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white" id="catIconPreviewWrapper">
                                                    <span class="material-symbols-outlined text-primary fs-5" id="catIconPreview">fitness_center</span>
                                                </span>
                                                <input type="text" class="form-control font-monospace" id="catIcon" name="icon" value="fitness_center" placeholder="icon_name">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex flex-wrap gap-1 p-2 bg-light rounded border overflow-auto" id="catIconLibraryGrid" style="max-height: 130px;">
                                        <!-- Rendered dynamically by JS -->
                                    </div>
                                    <div class="form-text small text-muted mt-1" style="font-size: 11px;">
                                        Click any icon above or type any valid Google Material Symbol name.
                                    </div>
                                </div>

                                <!-- Tab 2: Upload Icon File -->
                                <div class="tab-pane fade" id="pane-icon-upload" role="tabpanel">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="border rounded bg-light p-1 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px;">
                                            <img id="catCustomIconPreview" src="" alt="Icon" class="d-none w-100 h-100 object-fit-contain">
                                            <i class="bi bi-image text-muted fs-4" id="catCustomIconPlaceholder"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <input type="file" class="form-control form-control-sm mb-1" id="catIconFile" name="icon_file" accept="image/svg+xml,image/png,image/webp,image/jpeg">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted" style="font-size: 11px;">SVG or PNG, 64×64px or 128×128px (Max 2MB).</span>
                                                <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none small" id="btnResetCustomIcon" style="font-size: 11px;">
                                                    <i class="bi bi-x-circle me-1"></i>Clear Upload
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
    
    // Icon selection elements
    const iconInput = document.getElementById('catIcon');
    const iconPreview = document.getElementById('catIconPreview');
    const iconSearch = document.getElementById('catIconSearch');
    const iconGrid = document.getElementById('catIconLibraryGrid');
    const iconFileInput = document.getElementById('catIconFile');
    const customIconPreview = document.getElementById('catCustomIconPreview');
    const customIconPlaceholder = document.getElementById('catCustomIconPlaceholder');
    const resetCustomIconBtn = document.getElementById('btnResetCustomIcon');
    const tabLibraryBtn = document.getElementById('tab-icon-library');
    const tabUploadBtn = document.getElementById('tab-icon-upload');

    // Sports & Ecommerce icon library
    const popularIcons = [
        'sports_soccer', 'sports_cricket', 'sports_tennis', 'sports_basketball', 'sports_football', 
        'sports_baseball', 'sports_volleyball', 'sports_golf', 'sports_hockey', 'sports_martial_arts', 
        'sports_rugby', 'sports_handball', 'sports_gymnastics', 'sports_motorsports', 'fitness_center', 
        'directions_bike', 'directions_run', 'pool', 'hiking', 'skateboarding', 'surfing', 'snowboarding', 
        'downhill_skiing', 'kayaking', 'rowing', 'scuba_diving', 'sailing', 'sports_kabaddi', 'sports_mma', 
        'ice_skating', 'roller_skating', 'kitesurfing', 'sledding', 'sports', 'sports_score', 'trophy', 
        'military_tech', 'exercise', 'scoreboard', 'timer', 'stadium', 'shield', 'award_star', 'verified', 
        'bolt', 'category', 'local_mall', 'storefront', 'inventory_2', 'check_circle'
    ];

    function renderIconGrid(filterText = '') {
        if (!iconGrid) return;
        iconGrid.innerHTML = '';
        const query = filterText.toLowerCase().trim();
        const filtered = popularIcons.filter(ic => ic.toLowerCase().includes(query));

        if (filtered.length === 0) {
            iconGrid.innerHTML = `<span class="text-muted small p-2">No matching icons found. You can type any icon name directly above.</span>`;
            return;
        }

        filtered.forEach(iconName => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `btn btn-sm ${iconInput.value === iconName ? 'btn-primary' : 'btn-outline-secondary'} d-flex align-items-center justify-content-center p-2`;
            btn.style.width = '38px';
            btn.style.height = '38px';
            btn.title = iconName;
            btn.innerHTML = `<span class="material-symbols-outlined fs-5">${iconName}</span>`;
            btn.addEventListener('click', function () {
                iconInput.value = iconName;
                if (iconPreview) iconPreview.textContent = iconName;
                // Clear custom icon input when selecting from library
                if (iconFileInput) iconFileInput.value = '';
                if (customIconPreview) {
                    customIconPreview.src = '';
                    customIconPreview.classList.add('d-none');
                }
                if (customIconPlaceholder) customIconPlaceholder.classList.remove('d-none');
                renderIconGrid(iconSearch ? iconSearch.value : '');
            });
            iconGrid.appendChild(btn);
        });
    }

    renderIconGrid();

    if (iconSearch) {
        iconSearch.addEventListener('input', function () {
            renderIconGrid(this.value);
        });
    }

    // Live icon text input preview
    if (iconInput && iconPreview) {
        iconInput.addEventListener('input', function () {
            iconPreview.textContent = this.value.trim() || 'fitness_center';
            renderIconGrid(iconSearch ? iconSearch.value : '');
        });
    }

    // Custom Icon file preview
    if (iconFileInput) {
        iconFileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    if (customIconPreview) {
                        customIconPreview.src = e.target.result;
                        customIconPreview.classList.remove('d-none');
                    }
                    if (customIconPlaceholder) customIconPlaceholder.classList.add('d-none');
                    // Set iconInput placeholder
                    if (iconInput) iconInput.value = 'custom_uploaded_icon';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    if (resetCustomIconBtn) {
        resetCustomIconBtn.addEventListener('click', function () {
            if (iconFileInput) iconFileInput.value = '';
            if (customIconPreview) {
                customIconPreview.src = '';
                customIconPreview.classList.add('d-none');
            }
            if (customIconPlaceholder) customIconPlaceholder.classList.remove('d-none');
            if (iconInput && iconInput.value === 'custom_uploaded_icon') {
                iconInput.value = 'fitness_center';
                if (iconPreview) iconPreview.textContent = 'fitness_center';
            }
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
                    const iconVal = (row.icon && row.icon.trim()) ? row.icon.trim() : 'sports_tennis';
                    const isImageIcon = iconVal.includes('/') || iconVal.startsWith('http') || iconVal.startsWith('data:') || iconVal.includes('.');
                    const iconHtml = isImageIcon
                        ? `<img src="${window.SportsJunction.formatImageUrl(iconVal)}" class="rounded object-fit-contain border bg-white p-0.5 me-2 flex-shrink-0" style="width: 24px; height: 24px;" alt="Icon" onerror="this.onerror=null;this.replaceWith(document.createElement('span'));">`
                        : `<span class="material-symbols-outlined text-primary fs-5 me-2 flex-shrink-0">${iconVal}</span>`;
                    return `
                        <div class="d-flex align-items-center">
                            ${iconHtml}
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
                width: '105px',
                className: 'text-center',
                render: function (data, type, row) {
                    const isActive = data === 'ACTIVE';
                    const badgeClass = isActive ? 'bg-success' : 'bg-danger';
                    const isHomepage = parseInt(row.show_on_homepage) === 1;
                    const homepageBadge = isHomepage
                        ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle" title="Visible on Homepage" style="font-size: 10px;"><i class="bi bi-house-door-fill me-1"></i>Homepage</span>`
                        : `<span class="badge bg-light text-muted border" title="Hidden from Homepage" style="font-size: 10px;">Off Homepage</span>`;
                    return `
                        <div class="d-flex flex-column align-items-center justify-content-center gap-1">
                            <div class="d-flex align-items-center gap-1">
                                <span class="badge ${badgeClass}">${data}</span>
                                <button type="button" class="btn btn-sm btn-link p-0 text-muted btn-toggle-status" data-id="${row.id}" data-name="${row.name}" data-status="${data}" title="Toggle status">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                            </div>
                            <div>${homepageBadge}</div>
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
            
            const isImageIcon = iconVal.includes('/') || iconVal.startsWith('http') || iconVal.includes('.');
            if (isImageIcon) {
                // Switch to upload tab and show preview
                const triggerEl = document.getElementById('tab-icon-upload');
                if (triggerEl) {
                    bootstrap.Tab.getOrCreateInstance(triggerEl).show();
                }
                if (customIconPreview) {
                    customIconPreview.src = window.SportsJunction.formatImageUrl(iconVal);
                    customIconPreview.classList.remove('d-none');
                }
                if (customIconPlaceholder) customIconPlaceholder.classList.add('d-none');
            } else {
                // Switch to library tab
                const triggerEl = document.getElementById('tab-icon-library');
                if (triggerEl) {
                    bootstrap.Tab.getOrCreateInstance(triggerEl).show();
                }
                if (iconPreview) iconPreview.textContent = iconVal;
                if (customIconPreview) {
                    customIconPreview.src = '';
                    customIconPreview.classList.add('d-none');
                }
                if (customIconPlaceholder) customIconPlaceholder.classList.remove('d-none');
            }
            renderIconGrid(iconSearch ? iconSearch.value : '');

            document.getElementById('catStatus').value = data.status || 'ACTIVE';
            
            const showOnHomepage = parseInt(data.show_on_homepage) === 1;
            document.getElementById('catShowOnHomepage').checked = showOnHomepage;
            updateHomepageToggleLabel();

            document.getElementById('catImageUrl').value = data.image || '';
            if (fileInput) fileInput.value = '';
            if (iconFileInput) iconFileInput.value = '';
            document.getElementById('catDescription').value = data.description || '';
            updatePreview(data.image);
        }
    });

    function updateHomepageToggleLabel() {
        const cb = document.getElementById('catShowOnHomepage');
        const lbl = document.getElementById('catShowOnHomepageLabel');
        if (!cb || !lbl) return;
        if (cb.checked) {
            lbl.textContent = 'ON: Visible on the homepage';
            lbl.className = 'form-check-label small fw-bold text-success ms-1';
        } else {
            lbl.textContent = 'OFF: Hidden from the homepage';
            lbl.className = 'form-check-label small fw-semibold text-muted ms-1';
        }
    }

    const catShowOnHomepageEl = document.getElementById('catShowOnHomepage');
    if (catShowOnHomepageEl) {
        catShowOnHomepageEl.addEventListener('change', updateHomepageToggleLabel);
    }

    // Reset preview on Add New
    document.getElementById('btnAddNewCategory').addEventListener('click', function () {
        if (fileInput) fileInput.value = '';
        if (iconFileInput) iconFileInput.value = '';
        if (customIconPreview) {
            customIconPreview.src = '';
            customIconPreview.classList.add('d-none');
        }
        if (customIconPlaceholder) customIconPlaceholder.classList.remove('d-none');
        const triggerEl = document.getElementById('tab-icon-library');
        if (triggerEl) {
            bootstrap.Tab.getOrCreateInstance(triggerEl).show();
        }
        document.getElementById('catIcon').value = 'fitness_center';
        if (iconPreview) iconPreview.textContent = 'fitness_center';
        renderIconGrid();
        updatePreview('');

        document.getElementById('catShowOnHomepage').checked = false;
        updateHomepageToggleLabel();
    });
});
</script>
