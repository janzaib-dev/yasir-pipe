@extends('admin_panel.layout.app')

@section('content')
    @include('hr.partials.hr-styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .filter-section {
            background: #f8f9fa;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .employee-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }

        .employee-row {
            transition: background 0.2s;
        }

        .employee-row:hover {
            background: #f8f9fa;
        }

        .employee-row.already-assigned {
            background: #fff3cd;
        }

        .employee-row.has-other-structure {
            background: #ffe5e5;
        }

        .structure-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .structure-detail {
            margin: 8px 0;
        }

        .badge-lg {
            font-size: 0.9rem;
            padding: 8px 16px;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="page-title"><i class="fa fa-user-plus"></i> Assign Employees to Salary Structure</h1>
                        <p class="page-subtitle">Select employees to assign this salary structure</p>
                    </div>
                    <a href="{{ route('hr.salary-structure.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Structures
                    </a>
                </div>

                <!-- Structure Info Card -->
                <div class="structure-info-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-3">
                                <i class="fa fa-file-invoice-dollar"></i>
                                {{ ucfirst($salaryStructure->salary_type) }}
                                @if ($salaryStructure->use_daily_wages)
                                    - Daily Wages
                                @else
                                    - Monthly Salary
                                @endif
                            </h4>
                            <div class="structure-detail">
                                <strong>Base/Daily:</strong> Rs.
                                {{ number_format($salaryStructure->use_daily_wages ? $salaryStructure->daily_wages : $salaryStructure->base_salary, 2) }}
                            </div>
                            @if (!$salaryStructure->use_daily_wages)
                                <div class="structure-detail">
                                    <strong>Allowances:</strong> + Rs.
                                    {{ number_format($salaryStructure->total_allowances, 2) }}
                                </div>
                                <div class="structure-detail">
                                    <strong>Deductions:</strong> - Rs.
                                    {{ number_format($salaryStructure->total_deductions, 2) }}
                                </div>
                                <div class="structure-detail">
                                    <strong>Net:</strong> Rs.
                                    {{ number_format($salaryStructure->base_salary + $salaryStructure->total_allowances - $salaryStructure->total_deductions, 2) }}
                                </div>
                            @endif
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="badge badge-lg bg-white text-dark">
                                <i class="fa fa-users"></i> Currently Assigned: {{ $salaryStructure->assigned_count }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <h5 class="mb-3"><i class="fa fa-filter"></i> Filter Employees</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Filter By</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="filter_type" id="filter_all" value="all"
                                    checked>
                                <label class="btn btn-outline-primary" for="filter_all">All Employees</label>

                                <input type="radio" class="btn-check" name="filter_type" id="filter_dept"
                                    value="department">
                                <label class="btn btn-outline-primary" for="filter_dept">Department</label>

                                <input type="radio" class="btn-check" name="filter_type" id="filter_desig"
                                    value="designation">
                                <label class="btn btn-outline-primary" for="filter_desig">Designation</label>
                            </div>
                        </div>
                        <div class="col-md-4" id="department_selector" style="display: none;">
                            <label class="form-label fw-bold">Department</label>
                            <select class="form-select" id="department_id">
                                <option value="">Select Department</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4" id="designation_selector" style="display: none;">
                            <label class="form-label fw-bold">Designation</label>
                            <select class="form-select" id="designation_id">
                                <option value="">Select Designation</option>
                                @foreach ($designations as $desig)
                                    <option value="{{ $desig->id }}">{{ $desig->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">&nbsp;</label>
                            <button class="btn btn-primary w-100" id="fetchEmployeesBtn">
                                <i class="fa fa-sync"></i> Load Employees
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Employees Table -->
                <div class="hr-card">
                    <div class="hr-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">Select Employees</h5>
                                <small class="text-muted" id="employeeCount">0 employees loaded</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-secondary" id="selectAllBtn">
                                    <i class="fa fa-check-square"></i> Select All
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" id="clearAllBtn">
                                    <i class="fa fa-square"></i> Clear All
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="employee-table" id="employeesContainer">
                        <div class="text-center text-muted py-5" id="emptyState">
                            <i class="fa fa-users fa-3x mb-3"></i>
                            <p>Click "Load Employees" to fetch employees based on your filter</p>
                        </div>
                    </div>
                </div>

                <!-- Assignment Form -->
                <form id="assignmentForm" style="display: none;">
                    @csrf
                    <input type="hidden" name="salary_structure_id" value="{{ $salaryStructure->id }}">
                    <div id="selectedEmployeesInputs"></div>

                    <div class="hr-card mt-4">
                        <div class="hr-header">
                            <h5 class="mb-0">Assignment Details</h5>
                        </div>
                        <div class="p-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Start Date</label>
                                    <input type="date" name="start_date" class="form-control"
                                        value="{{ date('Y-m-d') }}">
                                    <small class="text-muted">Date when this structure becomes active</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Notes (Optional)</label>
                                    <input type="text" name="notes" class="form-control"
                                        placeholder="e.g., Annual increment 2026">
                                </div>
                            </div>
                            <div class="mt-4 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-primary" style="font-size: 1rem; padding: 10px 20px;">
                                        <i class="fa fa-users"></i> <span id="selectedCount">0</span> Selected
                                    </span>
                                </div>
                                <button type="submit" class="btn btn-success btn-lg" id="assignBtn">
                                    <i class="fa fa-check-circle"></i> Assign Salary Structure
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            let employees = [];

            // Show/hide filter selectors
            $('input[name="filter_type"]').change(function() {
                const filterType = $(this).val();
                $('#department_selector').toggle(filterType === 'department');
                $('#designation_selector').toggle(filterType === 'designation');
            });

            // Fetch employees
            $('#fetchEmployeesBtn').click(function() {
                const filterType = $('input[name="filter_type"]:checked').val();
                const departmentId = $('#department_id').val();
                const designationId = $('#designation_id').val();

                if (filterType === 'department' && !departmentId) {
                    Swal.fire('Error', 'Please select a department', 'error');
                    return;
                }

                if (filterType === 'designation' && !designationId) {
                    Swal.fire('Error', 'Please select a designation', 'error');
                    return;
                }

                const btn = $(this);
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');

                $.ajax({
                    url: '{{ route('hr.salary-structure.fetch-employees') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        filter_type: filterType,
                        department_id: departmentId,
                        designation_id: designationId,
                        salary_structure_id: {{ $salaryStructure->id }}
                    },
                    success: function(response) {
                        employees = response.employees;
                        renderEmployees(employees);
                        $('#employeeCount').text(response.count + ' employees loaded');
                    },
                    error: function(xhr) {
                        let msg = 'Failed to fetch employees';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            msg = xhr.responseJSON.error;
                        }
                        Swal.fire('Error', msg, 'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="fa fa-sync"></i> Load Employees');
                    }
                });
            });

            function renderEmployees(employees) {
                if (employees.length === 0) {
                    $('#employeesContainer').html(`
                        <div class="text-center text-muted py-5">
                            <i class="fa fa-users fa-3x mb-3"></i>
                            <p>No active employees found for this filter</p>
                        </div>
                    `);
                    $('#assignmentForm').hide();
                    return;
                }

                let html =
                    '<table class="table table-hover mb-0"><thead><tr><th width="50"><input type="checkbox" id="selectAllCheckbox"></th><th>Employee</th><th>Department</th><th>Designation</th><th>Status</th></tr></thead><tbody>';

                employees.forEach(emp => {
                    let rowClass = '';
                    let statusBadge = '';
                    let disabled = '';

                    // Check employee status first
                    if (emp.status !== 'active') {
                        rowClass = 'text-muted';
                        statusBadge =
                            `<span class="badge bg-secondary">${emp.status ? emp.status.toUpperCase() : 'INACTIVE'}</span>`;
                        disabled = 'disabled';
                    } else if (emp.is_already_assigned) {
                        rowClass = 'already-assigned';
                        statusBadge = '<span class="badge bg-warning">Already Assigned</span>';
                        disabled = 'disabled';
                    } else if (emp.has_other_structure) {
                        rowClass = 'has-other-structure';
                        statusBadge = '<span class="badge bg-info">Has Other Structure</span>';
                    } else {
                        statusBadge = '<span class="badge bg-success">Available</span>';
                    }

                    html += `
                        <tr class="employee-row ${rowClass}">
                            <td><input type="checkbox" class="employee-checkbox" value="${emp.id}" ${disabled}></td>
                            <td>
                                <strong>${emp.first_name} ${emp.last_name || ''}</strong>
                                ${emp.status !== 'active' ? '<br><small class="text-danger"><i class="fa fa-exclamation-triangle"></i> Not Active</small>' : ''}
                            </td>
                            <td>${emp.department ? emp.department.name : 'N/A'}</td>
                            <td>${emp.designation ? emp.designation.name : 'N/A'}</td>
                            <td>${statusBadge}</td>
                        </tr>
                    `;
                });

                html += '</tbody></table>';
                $('#employeesContainer').html(html);
                $('#emptyState').hide();
                updateSelectedCount();
            }

            // Select All / Clear All
            $(document).on('change', '#selectAllCheckbox', function() {
                $('.employee-checkbox:not(:disabled)').prop('checked', this.checked);
                updateSelectedCount();
            });

            $('#selectAllBtn').click(function() {
                $('.employee-checkbox:not(:disabled)').prop('checked', true);
                $('#selectAllCheckbox').prop('checked', true);
                updateSelectedCount();
            });

            $('#clearAllBtn').click(function() {
                $('.employee-checkbox').prop('checked', false);
                $('#selectAllCheckbox').prop('checked', false);
                updateSelectedCount();
            });

            $(document).on('change', '.employee-checkbox', function() {
                updateSelectedCount();
            });

            function updateSelectedCount() {
                const count = $('.employee-checkbox:checked').length;
                $('#selectedCount').text(count);

                if (count > 0) {
                    $('#assignmentForm').slideDown();

                    // Update hidden inputs
                    let html = '';
                    $('.employee-checkbox:checked').each(function() {
                        html += `<input type="hidden" name="employee_ids[]" value="${$(this).val()}">`;
                    });
                    $('#selectedEmployeesInputs').html(html);
                } else {
                    $('#assignmentForm').slideUp();
                }
            }

            // Submit assignment
            $('#assignmentForm').submit(function(e) {
                e.preventDefault();

                const count = $('.employee-checkbox:checked').length;
                if (count === 0) {
                    Swal.fire('Error', 'Please select at least one employee', 'error');
                    return;
                }

                Swal.fire({
                    title: 'Confirm Assignment',
                    html: `Assign this salary structure to <strong>${count}</strong> employee(s)?<br><small>Previous active structures will be ended automatically.</small>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Assign',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performAssignment();
                    }
                });
            });

            function performAssignment() {
                const btn = $('#assignBtn');
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Assigning...');

                $.ajax({
                    url: '{{ route('hr.salary-structure.assign', $salaryStructure->id) }}',
                    method: 'POST',
                    data: $('#assignmentForm').serialize(),
                    success: function(response) {
                        let message = response.success;
                        if (response.skipped && response.skipped.length > 0) {
                            message +=
                                '<br><br><strong>Skipped Employees:</strong><ul class="text-start">';
                            response.skipped.forEach(skip => {
                                message += `<li>${skip.name}: ${skip.reason}</li>`;
                            });
                            message += '</ul>';
                        }

                        Swal.fire({
                            title: 'Success!',
                            html: message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = response.redirect;
                        });
                    },
                    error: function(xhr) {
                        let errorMsg = 'Assignment failed';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        }
                        Swal.fire('Error', errorMsg, 'error');
                        btn.prop('disabled', false).html(
                            '<i class="fa fa-check-circle"></i> Assign Salary Structure');
                    }
                });
            }
        });
    </script>
@endsection
