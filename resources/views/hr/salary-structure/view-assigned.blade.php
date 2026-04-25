@extends('admin_panel.layout.app')

@section('content')
    @include('hr.partials.hr-styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .structure-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .assignment-row.inactive {
            background: #f8f9fa;
            opacity: 0.7;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-active {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-ended {
            background: #fee2e2;
            color: #dc2626;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="page-title"><i class="fa fa-users"></i> Assigned Employees</h1>
                        <p class="page-subtitle">View and manage employees assigned to this salary structure</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('hr.salary-structure.assign-page', $salaryStructure->id) }}"
                            class="btn btn-primary">
                            <i class="fa fa-user-plus"></i> Assign More Employees
                        </a>
                        <a href="{{ route('hr.salary-structure.index') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back to Structures
                        </a>
                    </div>
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
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <strong>Base/Daily:</strong> Rs.
                                        {{ number_format($salaryStructure->use_daily_wages ? $salaryStructure->daily_wages : $salaryStructure->base_salary, 2) }}
                                    </div>
                                    @if (!$salaryStructure->use_daily_wages)
                                        <div class="mb-2">
                                            <strong>Allowances:</strong> + Rs.
                                            {{ number_format($salaryStructure->total_allowances, 2) }}
                                        </div>
                                        <div class="mb-2">
                                            <strong>Deductions:</strong> - Rs.
                                            {{ number_format($salaryStructure->total_deductions, 2) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    @if (!$salaryStructure->use_daily_wages)
                                        <div class="mb-2">
                                            <strong>Net Salary:</strong> Rs.
                                            {{ number_format($salaryStructure->base_salary + $salaryStructure->total_allowances - $salaryStructure->total_deductions, 2) }}
                                        </div>
                                    @endif
                                    @if ($salaryStructure->commission_percentage)
                                        <div class="mb-2">
                                            <strong>Commission:</strong> {{ $salaryStructure->commission_percentage }}%
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="badge bg-white text-dark" style="font-size: 1.1rem; padding: 12px 24px;">
                                <i class="fa fa-users"></i> {{ $assignments->total() }} Total Assignments
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assignments Table -->
                <div class="hr-card">
                    <div class="hr-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">Assignment History</h5>
                                <small class="text-muted">All assignments (active and ended)</small>
                            </div>
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="filter" id="filter_all" value="all"
                                    checked autocomplete="off">
                                <label class="btn btn-outline-primary btn-sm" for="filter_all">All</label>

                                <input type="radio" class="btn-check" name="filter" id="filter_active" value="active"
                                    autocomplete="off">
                                <label class="btn btn-outline-success btn-sm" for="filter_active">Active Only</label>

                                <input type="radio" class="btn-check" name="filter" id="filter_ended" value="ended"
                                    autocomplete="off">
                                <label class="btn btn-outline-secondary btn-sm" for="filter_ended">Ended</label>
                            </div>
                        </div>
                    </div>

                    @if ($assignments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Designation</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Assigned By</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="assignmentsTable">
                                    @foreach ($assignments as $assignment)
                                        <tr class="assignment-row {{ $assignment->is_active ? '' : 'inactive' }}"
                                            data-status="{{ $assignment->is_active ? 'active' : 'ended' }}">
                                            <td>
                                                <strong>{{ $assignment->employee->full_name }}</strong>
                                                @if ($assignment->salary_structure_id != $salaryStructure->id)
                                                    <span class="badge bg-warning text-dark ms-1" style="font-size:0.7em"
                                                        title="Individually Updated">Edited</span>
                                                @else
                                                    <span class="badge bg-light text-dark ms-1 border"
                                                        style="font-size:0.7em">Inherited</span>
                                                @endif
                                                @if ($assignment->notes)
                                                    <br><small class="text-muted"><i class="fa fa-comment"></i>
                                                        {{ $assignment->notes }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $assignment->employee->department->name ?? 'N/A' }}</td>
                                            <td>{{ $assignment->employee->designation->name ?? 'N/A' }}</td>
                                            <td>
                                                <i class="fa fa-calendar-check text-success"></i>
                                                {{ $assignment->start_date->format('M d, Y') }}
                                            </td>
                                            <td>
                                                @if ($assignment->end_date)
                                                    <i class="fa fa-calendar-times text-danger"></i>
                                                    {{ $assignment->end_date->format('M d, Y') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($assignment->assignedBy)
                                                    {{ $assignment->assignedBy->name }}
                                                @else
                                                    <span class="text-muted">System</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($assignment->is_active && !$assignment->end_date)
                                                    <span class="status-badge status-active">
                                                        <i class="fa fa-check-circle"></i> Active
                                                    </span>
                                                @else
                                                    <span class="status-badge status-ended">
                                                        <i class="fa fa-times-circle"></i> Ended
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($assignment->is_active && !$assignment->end_date)
                                                    <button class="btn btn-sm btn-outline-danger end-assignment-btn"
                                                        data-assignment-id="{{ $assignment->id }}"
                                                        data-employee-id="{{ $assignment->employee_id }}"
                                                        data-employee-name="{{ $assignment->employee->full_name }}">
                                                        <i class="fa fa-stop-circle"></i> End
                                                    </button>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="px-4 py-3 border-top">
                            {{ $assignments->links() }}
                        </div>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fa fa-users fa-3x mb-3"></i>
                            <p>No employees assigned to this salary structure yet</p>
                            <a href="{{ route('hr.salary-structure.assign-page', $salaryStructure->id) }}"
                                class="btn btn-primary mt-3">
                                <i class="fa fa-user-plus"></i> Assign Employees
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Filter assignments
            $('input[name="filter"]').change(function() {
                const filter = $(this).val();
                if (filter === 'all') {
                    $('.assignment-row').show();
                } else {
                    $('.assignment-row').each(function() {
                        const status = $(this).data('status');
                        $(this).toggle(status === filter);
                    });
                }
            });

            // End assignment
            $('.end-assignment-btn').click(function() {
                const employeeId = $(this).data('employee-id');
                const employeeName = $(this).data('employee-name');

                Swal.fire({
                    title: 'End Assignment?',
                    html: `End salary structure assignment for <strong>${employeeName}</strong>?<br><small>End date will be set to today.</small>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, End Assignment',
                    confirmButtonColor: '#dc2626',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        endAssignment(employeeId);
                    }
                });
            });

            function endAssignment(employeeId) {
                $.ajax({
                    url: `/hr/salary-structure/{{ $salaryStructure->id }}/employee/${employeeId}`,
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}',
                        end_date: '{{ date('Y-m-d') }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Success!',
                            text: response.success,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        let errorMsg = 'Failed to end assignment';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        }
                        Swal.fire('Error', errorMsg, 'error');
                    }
                });
            }
        });
    </script>
@endsection
