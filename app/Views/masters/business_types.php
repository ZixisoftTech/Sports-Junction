<?php
use App\Core\Response;
use App\Helpers\CSRF;
$baseUrl = Response::baseUrl();
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-briefcase text-primary me-2"></i>Business Type Management</h4>
        <p class="text-muted small mb-0">Define vendor operational models (Manufacturer, Wholesaler, Exporter) used in onboarding and filtering.</p>
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
        <button type="button" class="btn btn-sj-primary btn-sm shadow-sm px-3" id="btnAddNewBusinessType">
            <i class="bi bi-plus-lg me-1"></i> Add Business Type
        </button>
    </div>
</div>

<div class="sj-card">
    <div class="sj-card-header bg-white py-3">
        <div class="row align-items-center w-100 g-2">
            <div class="col-sm-6">
                <h6 class="m-0 fw-bold text-dark">Business Types Master List</h6>
            </div>
            <div class="col-sm-6 d-flex justify-content-sm-end align-items-center gap-2">
                <label for="btStatusFilter" class="small text-muted text-nowrap m-0">Status:</label>
                <select id="btStatusFilter" class="form-select form-select-sm" style="max-width: 140px;">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE">Active Only</option>
                    <option value="INACTIVE">Inactive Only</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="businessTypesTable" class="table table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Business Type</th>
                        <th>Description</th>
                        <th style="width: 110px;">Status</th>
                        <th style="width: 140px;">Created Date</th>
                        <th style="width: 130px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit Business Type Modal -->
<div class="modal fade" id="businessTypeModal" tabindex="-1" aria-labelledby="businessTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold" id="businessTypeModalLabel">Add New Business Type</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="businessTypeForm" novalidate>
                <?= CSRF::field() ?>
                <input type="hidden" name="id" value="">

                <div class="modal-body py-3">
                    <!-- Business Type Name -->
                    <div class="mb-3">
                        <label for="btName" class="form-label small fw-semibold">Business Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="btName" name="name" placeholder="e.g. Manufacturer, Wholesaler, Exporter" required maxlength="100">
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="btDescription" class="form-label small fw-semibold">Description</label>
                        <textarea class="form-control form-control-sm" id="btDescription" name="description" rows="3" placeholder="Explain the profile, scope or trading rights of this business type..."></textarea>
                    </div>

                    <!-- Status -->
                    <div class="mb-2">
                        <label for="btStatus" class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="btStatus" name="status" required>
                            <option value="ACTIVE" selected>ACTIVE</option>
                            <option value="INACTIVE">INACTIVE</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-sj-primary px-3" id="saveBusinessTypeBtn">
                        <i class="bi bi-check2 me-1"></i> Save Business Type
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    MasterModule.init({
        entity: 'business-types',
        entityTitle: 'Business Type',
        tableSelector: '#businessTypesTable',
        modalSelector: '#businessTypeModal',
        formSelector: '#businessTypeForm',
        addNewBtnSelector: '#btnAddNewBusinessType',
        statusFilterSelector: '#btStatusFilter',
        columns: [
            { data: 'id', name: 'id' },
            { 
                data: 'name', 
                name: 'name',
                render: function (data) {
                    return `<span class="fw-bold text-dark"><i class="bi bi-briefcase me-1.5 text-primary"></i>${data}</span>`;
                }
            },
            { 
                data: 'description', 
                name: 'description',
                render: function (data) {
                    return data ? `<span class="text-muted small">${data}</span>` : '<span class="text-muted">-</span>';
                }
            },
            { 
                data: 'status', 
                name: 'status',
                render: function (data) {
                    return data === 'ACTIVE' 
                        ? '<span class="badge-active">ACTIVE</span>' 
                        : '<span class="badge-inactive">INACTIVE</span>';
                }
            },
            { 
                data: 'created_at', 
                name: 'created_at',
                render: function (data) {
                    if (!data) return '-';
                    const d = new Date(data);
                    return `<span class="text-muted small">${d.toLocaleDateString('en-GB')}</span>`;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-end',
                render: function (data, type, row) {
                    const statusIcon = row.status === 'ACTIVE' ? 'bi-toggle-on text-success' : 'bi-toggle-off text-secondary';
                    const statusTitle = row.status === 'ACTIVE' ? 'Deactivate Business Type' : 'Activate Business Type';

                    return `
                        <div class="action-btn-group">
                            <button type="button" class="btn btn-outline-primary btn-sm btn-edit-master" data-id="${row.id}" title="Edit Business Type">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-toggle-status" data-id="${row.id}" data-name="${row.name}" data-status="${row.status}" title="${statusTitle}">
                                <i class="bi ${statusIcon}"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm btn-delete-master" data-id="${row.id}" data-name="${row.name}" title="Permanently Delete">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        getExtraFilters: function () {
            return {
                status: $('#btStatusFilter').val()
            };
        }
    });
});
</script>
