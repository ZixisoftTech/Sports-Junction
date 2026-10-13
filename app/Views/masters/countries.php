<?php
use App\Core\Response;
use App\Helpers\CSRF;
$baseUrl = Response::baseUrl();
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-globe-americas text-primary me-2"></i>Country Management</h4>
        <p class="text-muted small mb-0">Define international and domestic markets, ISO Alpha-2/3 identifiers, and telephone calling codes.</p>
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
        <button type="button" class="btn btn-sj-primary btn-sm shadow-sm px-3" id="btnAddNewCountry">
            <i class="bi bi-plus-lg me-1"></i> Add Country
        </button>
    </div>
</div>

<div class="sj-card">
    <div class="sj-card-header bg-white py-3">
        <div class="row align-items-center w-100 g-2">
            <div class="col-sm-6">
                <h6 class="m-0 fw-bold text-dark">Countries Master List</h6>
            </div>
            <div class="col-sm-6 d-flex justify-content-sm-end align-items-center gap-2">
                <label for="countryStatusFilter" class="small text-muted text-nowrap m-0">Status:</label>
                <select id="countryStatusFilter" class="form-select form-select-sm" style="max-width: 140px;">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE">Active Only</option>
                    <option value="INACTIVE">Inactive Only</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="countriesTable" class="table table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Country Name</th>
                        <th style="width: 120px;">ISO Code</th>
                        <th style="width: 120px;">Calling Code</th>
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

<!-- Add / Edit Country Modal -->
<div class="modal fade" id="countryModal" tabindex="-1" aria-labelledby="countryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold" id="countryModalLabel">Add New Country</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="countryForm" novalidate>
                <?= CSRF::field() ?>
                <input type="hidden" name="id" value="">

                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label for="countryName" class="form-label small fw-semibold">Country Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="countryName" name="name" placeholder="e.g. India" required maxlength="100">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="isoAlpha2" class="form-label small fw-semibold">ISO Alpha-2 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm text-uppercase" id="isoAlpha2" name="iso_alpha2" placeholder="e.g. IN" required minlength="2" maxlength="2">
                            <small class="text-muted" style="font-size: 11px;">Standard 2-letter code</small>
                        </div>
                        <div class="col-6">
                            <label for="isoAlpha3" class="form-label small fw-semibold">ISO Alpha-3</label>
                            <input type="text" class="form-control form-control-sm text-uppercase" id="isoAlpha3" name="iso_alpha3" placeholder="e.g. IND" maxlength="3">
                            <small class="text-muted" style="font-size: 11px;">Optional 3-letter code</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phoneCode" class="form-label small fw-semibold">Phone Calling Code</label>
                        <input type="text" class="form-control form-control-sm" id="phoneCode" name="phone_code" placeholder="e.g. +91" maxlength="10">
                    </div>

                    <div class="mb-2">
                        <label for="countryStatus" class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="countryStatus" name="status" required>
                            <option value="ACTIVE" selected>ACTIVE</option>
                            <option value="INACTIVE">INACTIVE</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-sj-primary px-3" id="saveCountryBtn">
                        <i class="bi bi-check2 me-1"></i> Save Country
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    MasterModule.init({
        entity: 'countries',
        entityTitle: 'Country',
        tableSelector: '#countriesTable',
        modalSelector: '#countryModal',
        formSelector: '#countryForm',
        addNewBtnSelector: '#btnAddNewCountry',
        statusFilterSelector: '#countryStatusFilter',
        columns: [
            { data: 'id', name: 'id' },
            { 
                data: 'name', 
                name: 'name',
                render: function (data, type, row) {
                    return `<span class="fw-bold text-dark">${data}</span>`;
                }
            },
            { 
                data: 'iso_alpha2', 
                name: 'iso_alpha2',
                render: function (data, type, row) {
                    let out = `<span class="badge bg-light text-dark border">${data}</span>`;
                    if (row.iso_alpha3) {
                        out += ` <span class="badge bg-light text-muted border">${row.iso_alpha3}</span>`;
                    }
                    return out;
                }
            },
            { 
                data: 'phone_code', 
                name: 'phone_code',
                render: function (data) {
                    return data ? `<code class="text-dark">${data}</code>` : '<span class="text-muted">-</span>';
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
                    const statusTitle = row.status === 'ACTIVE' ? 'Deactivate Country' : 'Activate Country';

                    return `
                        <div class="action-btn-group">
                            <button type="button" class="btn btn-outline-primary btn-sm btn-edit-master" data-id="${row.id}" title="Edit Country">
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
                status: $('#countryStatusFilter').val()
            };
        }
    });
});
</script>
