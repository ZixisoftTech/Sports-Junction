<?php
use App\Core\Response;
use App\Helpers\CSRF;
$baseUrl = Response::baseUrl();
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-boxes text-primary me-2"></i>MOQ Unit Management</h4>
        <p class="text-muted small mb-0">Manage minimum order quantity (MOQ) units for bulk manufacturing lots and wholesale orders.</p>
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
        <button type="button" class="btn btn-sj-primary btn-sm shadow-sm px-3" id="btnAddNewMoqUnit">
            <i class="bi bi-plus-lg me-1"></i> Add MOQ Unit
        </button>
    </div>
</div>

<div class="sj-card">
    <div class="sj-card-header bg-white py-3">
        <div class="row align-items-center w-100 g-2">
            <div class="col-sm-6">
                <h6 class="m-0 fw-bold text-dark">MOQ Units Master List</h6>
            </div>
            <div class="col-sm-6 d-flex justify-content-sm-end align-items-center gap-2">
                <label for="muStatusFilter" class="small text-muted text-nowrap m-0">Status:</label>
                <select id="muStatusFilter" class="form-select form-select-sm" style="max-width: 140px;">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE">Active Only</option>
                    <option value="INACTIVE">Inactive Only</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="moqUnitsTable" class="table table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Unit Name</th>
                        <th style="width: 120px;">Symbol</th>
                        <th>Description</th>
                        <th style="width: 110px;">Status</th>
                        <th style="width: 130px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit MOQ Unit Modal -->
<div class="modal fade" id="moqUnitModal" tabindex="-1" aria-labelledby="moqUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold" id="moqUnitModalLabel">Add New MOQ Unit</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="moqUnitForm" novalidate>
                <?= CSRF::field() ?>
                <input type="hidden" name="id" value="">

                <div class="modal-body py-3">
                    <!-- Unit Name -->
                    <div class="mb-3">
                        <label for="muName" class="form-label small fw-semibold">Unit Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="muName" name="name" placeholder="e.g. Pieces, Sets, Boxes, Kilograms" required maxlength="50">
                    </div>

                    <!-- Unit Symbol -->
                    <div class="mb-3">
                        <label for="muSymbol" class="form-label small fw-semibold">Symbol / Abbreviation</label>
                        <input type="text" class="form-control form-control-sm" id="muSymbol" name="symbol" placeholder="e.g. pcs, sets, boxes, kgs" maxlength="20">
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="muDescription" class="form-label small fw-semibold">Description</label>
                        <textarea class="form-control form-control-sm" id="muDescription" name="description" rows="3" placeholder="Brief note on wholesale order batching..."></textarea>
                    </div>

                    <!-- Status -->
                    <div class="mb-2">
                        <label for="muStatus" class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="muStatus" name="status" required>
                            <option value="ACTIVE" selected>ACTIVE</option>
                            <option value="INACTIVE">INACTIVE</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-sj-primary px-3" id="saveMoqUnitBtn">
                        <i class="bi bi-check2 me-1"></i> Save MOQ Unit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    MasterModule.init({
        entity: 'moq-units',
        entityTitle: 'MOQ Unit',
        tableSelector: '#moqUnitsTable',
        modalSelector: '#moqUnitModal',
        formSelector: '#moqUnitForm',
        addNewBtnSelector: '#btnAddNewMoqUnit',
        statusFilterSelector: '#muStatusFilter',
        columns: [
            { data: 'id', name: 'id' },
            { 
                data: 'name', 
                name: 'name',
                render: function (data) {
                    return `<span class="fw-bold text-dark"><i class="bi bi-boxes me-1.5 text-primary"></i>${data}</span>`;
                }
            },
            { 
                data: 'symbol', 
                name: 'symbol',
                render: function (data) {
                    return data ? `<span class="badge bg-light text-dark border font-monospace">${data}</span>` : '<span class="text-muted">-</span>';
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
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-end',
                render: function (data, type, row) {
                    const statusIcon = row.status === 'ACTIVE' ? 'bi-toggle-on text-success' : 'bi-toggle-off text-secondary';
                    const statusTitle = row.status === 'ACTIVE' ? 'Deactivate MOQ Unit' : 'Activate MOQ Unit';

                    return `
                        <div class="action-btn-group">
                            <button type="button" class="btn btn-outline-primary btn-sm btn-edit-master" data-id="${row.id}" title="Edit MOQ Unit">
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
                status: $('#muStatusFilter').val()
            };
        }
    });
});
</script>
