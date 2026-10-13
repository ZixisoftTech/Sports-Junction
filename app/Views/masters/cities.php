<?php
use App\Core\Response;
use App\Helpers\CSRF;
$baseUrl = Response::baseUrl();
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-buildings text-primary me-2"></i>City Management</h4>
        <p class="text-muted small mb-0">Manage cities, sports goods production hubs (e.g. Meerut, Jalandhar), and state associations.</p>
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
        <button type="button" class="btn btn-sj-primary btn-sm shadow-sm px-3" id="btnAddNewCity">
            <i class="bi bi-plus-lg me-1"></i> Add City
        </button>
    </div>
</div>

<div class="sj-card">
    <div class="sj-card-header bg-white py-3">
        <div class="row align-items-center w-100 g-2">
            <div class="col-sm-4">
                <h6 class="m-0 fw-bold text-dark">Cities Master List</h6>
            </div>
            <div class="col-sm-8 d-flex justify-content-sm-end align-items-center gap-2 flex-wrap">
                <!-- Country Filter -->
                <select id="cityCountryFilter" class="form-select form-select-sm" style="max-width: 150px;">
                    <option value="">All Countries</option>
                    <?php foreach ($countries as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- State Filter -->
                <select id="cityStateFilter" class="form-select form-select-sm" style="max-width: 150px;">
                    <option value="">All States</option>
                </select>

                <!-- Status Filter -->
                <select id="cityStatusFilter" class="form-select form-select-sm" style="max-width: 120px;">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE">Active Only</option>
                    <option value="INACTIVE">Inactive Only</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="citiesTable" class="table table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>City Name</th>
                        <th>State</th>
                        <th>Country</th>
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

<!-- Add / Edit City Modal -->
<div class="modal fade" id="cityModal" tabindex="-1" aria-labelledby="cityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold" id="cityModalLabel">Add New City</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="cityForm" novalidate>
                <?= CSRF::field() ?>
                <input type="hidden" name="id" value="">

                <div class="modal-body py-3">
                    <!-- Country Selector (Triggers Dynamic Dependent State Loading) -->
                    <div class="mb-3">
                        <label for="cityCountryId" class="form-label small fw-semibold">Country <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="cityCountryId" name="country_id" required>
                            <option value="">-- Select Country --</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['iso_alpha2'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Dependent State Selector -->
                    <div class="mb-3">
                        <label for="cityStateId" class="form-label small fw-semibold d-flex justify-content-between">
                            <span>State <span class="text-danger">*</span></span>
                            <span id="stateLoadingSpinner" class="spinner-border spinner-border-sm text-primary d-none" role="status"></span>
                        </label>
                        <select class="form-select form-select-sm" id="cityStateId" name="state_id" required disabled>
                            <option value="">-- Select Country First --</option>
                        </select>
                    </div>

                    <!-- City Name -->
                    <div class="mb-3">
                        <label for="cityName" class="form-label small fw-semibold">City Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="cityName" name="name" placeholder="e.g. Meerut, Jalandhar" required maxlength="100">
                    </div>

                    <!-- Status -->
                    <div class="mb-2">
                        <label for="cityStatus" class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="cityStatus" name="status" required>
                            <option value="ACTIVE" selected>ACTIVE</option>
                            <option value="INACTIVE">INACTIVE</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-sj-primary px-3" id="saveCityBtn">
                        <i class="bi bi-check2 me-1"></i> Save City
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = window.SportsJunction.baseUrl;

    // Dynamic Dependent Dropdown Function
    function loadStates(countryId, targetSelect, selectedStateId = null) {
        const select = $(targetSelect);
        select.prop('disabled', true).empty().append('<option value="">Loading states...</option>');
        $('#stateLoadingSpinner').removeClass('d-none');

        if (!countryId) {
            select.empty().append('<option value="">-- Select Country First --</option>');
            $('#stateLoadingSpinner').addClass('d-none');
            return;
        }

        fetch(`${baseUrl}/api/masters/states-by-country/${countryId}`)
            .then(res => res.json())
            .then(res => {
                $('#stateLoadingSpinner').addClass('d-none');
                select.empty().append('<option value="">-- Select State --</option>');

                if (res.success && res.data && res.data.length > 0) {
                    res.data.forEach(s => {
                        const isSel = (selectedStateId && parseInt(selectedStateId) === parseInt(s.id)) ? 'selected' : '';
                        select.append(`<option value="${s.id}" ${isSel}>${s.name} ${s.code ? '(' + s.code + ')' : ''}</option>`);
                    });
                    select.prop('disabled', false);
                } else {
                    select.append('<option value="">No states found for selected country</option>');
                }
            })
            .catch(() => {
                $('#stateLoadingSpinner').addClass('d-none');
                select.empty().append('<option value="">Failed to load states</option>');
            });
    }

    // Modal Country Change Listener -> Reloads Form States
    $('#cityCountryId').on('change', function () {
        const countryId = $(this).val();
        loadStates(countryId, '#cityStateId');
    });

    // Top Filter Country Change Listener -> Reloads Filter States
    $('#cityCountryFilter').on('change', function () {
        const countryId = $(this).val();
        const stateSelect = $('#cityStateFilter');
        stateSelect.empty().append('<option value="">All States</option>');

        if (countryId) {
            fetch(`${baseUrl}/api/masters/states-by-country/${countryId}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.data) {
                        res.data.forEach(s => {
                            stateSelect.append(`<option value="${s.id}">${s.name}</option>`);
                        });
                    }
                    MasterModule.reload();
                });
        } else {
            MasterModule.reload();
        }
    });

    $('#cityStateFilter, #cityStatusFilter').on('change', function () {
        MasterModule.reload();
    });

    MasterModule.init({
        entity: 'cities',
        entityTitle: 'City',
        tableSelector: '#citiesTable',
        modalSelector: '#cityModal',
        formSelector: '#cityForm',
        addNewBtnSelector: '#btnAddNewCity',
        statusFilterSelector: '#cityStatusFilter',
        onFormReset: function () {
            $('#cityStateId').prop('disabled', true).empty().append('<option value="">-- Select Country First --</option>');
        },
        onEditPopulate: function (data) {
            // First select country, then load states and select current state
            $('#cityCountryId').val(data.country_id);
            loadStates(data.country_id, '#cityStateId', data.state_id);
        },
        columns: [
            { data: 'id', name: 'ci.id' },
            { 
                data: 'name', 
                name: 'ci.name',
                render: function (data) {
                    return `<span class="fw-bold text-dark">${data}</span>`;
                }
            },
            { 
                data: 'state_name', 
                name: 's.name',
                render: function (data) {
                    return `<span class="badge bg-light text-dark border">${data}</span>`;
                }
            },
            { 
                data: 'country_name', 
                name: 'c.name',
                render: function (data) {
                    return `<span class="text-primary fw-medium"><i class="bi bi-geo-alt me-1 text-muted"></i>${data}</span>`;
                }
            },
            { 
                data: 'status', 
                name: 'ci.status',
                render: function (data) {
                    return data === 'ACTIVE' 
                        ? '<span class="badge-active">ACTIVE</span>' 
                        : '<span class="badge-inactive">INACTIVE</span>';
                }
            },
            { 
                data: 'created_at', 
                name: 'ci.created_at',
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
                    const statusTitle = row.status === 'ACTIVE' ? 'Deactivate City' : 'Activate City';

                    return `
                        <div class="action-btn-group">
                            <button type="button" class="btn btn-outline-primary btn-sm btn-edit-master" data-id="${row.id}" title="Edit City">
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
                status: $('#cityStatusFilter').val(),
                country_id: $('#cityCountryFilter').val(),
                state_id: $('#cityStateFilter').val()
            };
        }
    });
});
</script>
