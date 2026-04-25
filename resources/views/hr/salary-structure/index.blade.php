@extends('admin_panel.layout.app')

@section('content')
    @include('hr.partials.hr-styles')

    <style>
        .structure-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.2s ease;
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .structure-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
            border-color: #d1d5db;
        }

        .structure-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f3f4f6;
        }

        .structure-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #111827;
            margin: 0 0 4px 0;
            line-height: 1.4;
        }

        .structure-type-badge {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            background-color: #f3f4f6;
            color: #6b7280;
            vertical-align: middle;
            margin-left: 6px;
        }

        .salary-amount-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: #4f46e5;
            /* Indigo 600 */
            margin: 0;
            line-height: 1;
        }

        .salary-amount-label {
            color: #9ca3af;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: right;
            margin-top: 4px;
        }

        .structure-details {
            flex: 1;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
        }

        .detail-label {
            color: #6b7280;
        }

        .detail-value {
            font-weight: 600;
            color: #374151;
        }

        .assigned-info {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
            font-size: 0.85rem;
            color: #6b7280;
        }

        .assigned-count {
            font-weight: 600;
            color: #4f46e5;
            background: #eef2ff;
            padding: 2px 8px;
            border-radius: 12px;
        }

        .card-actions {
            margin-top: auto;
            border-top: 1px solid #f3f4f6;
            padding-top: 16px;
        }

        .btn-assign-main {
            width: 100%;
            background-color: #4f46e5;
            color: white;
            font-weight: 600;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            text-decoration: none;
            transition: 0.2s;
            border: 1px solid rgba(0, 0, 0, 0.1);
            display: block;
            margin-bottom: 12px;
        }

        .btn-assign-main:hover {
            background-color: #4338ca;
            color: white;
        }

        .secondary-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            flex: 1;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 8px;
            border-radius: 6px;
            background-color: white;
            border: 1px solid #e5e7eb;
            color: #6b7280;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .action-btn:hover {
            background-color: #f9fafb;
            color: #111827;
            border-color: #d1d5db;
        }

        .action-btn.delete:hover {
            background-color: #fef2f2;
            color: #ef4444;
            border-color: #fecaca;
        }

        .create-structure-card {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100%;
            cursor: pointer;
            transition: all 0.2s;
            background-color: #f9fafb;
            color: #6b7280;
        }

        .create-structure-card:hover {
            border-color: #4f46e5;
            color: #4f46e5;
            background-color: #eef2ff;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="page-title"><i class="fa fa-money-bill-wave"></i> Salary Structures</h1>
                        <p class="page-subtitle">Create and manage salary structures, then assign them to employees</p>
                    </div>
                    @if ($canCreate || $canEdit)
                        <button class="btn btn-create" onclick="createNewStructure()">
                            <i class="fa fa-plus me-1"></i> Create New Structure
                        </button>
                    @endif
                </div>

                <!-- Stats Row -->
                @php
                    $totalStructures = \App\Models\Hr\SalaryStructure::count();
                    $totalAssignments = \App\Models\Hr\EmployeeSalaryStructure::where('is_active', true)
                        ->distinct('employee_id')
                        ->count();
                    $unassignedEmployees = \App\Models\Hr\Employee::where('status', 'active')
                        ->whereDoesntHave('activeSalaryStructure')
                        ->count();
                @endphp
                <div class="stats-row">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="fa fa-file-invoice-dollar"></i></div>
                        <div class="stat-value">{{ $totalStructures }}</div>
                        <div class="stat-label">Total Structures</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="fa fa-users"></i></div>
                        <div class="stat-value">{{ $totalAssignments }}</div>
                        <div class="stat-label">Assigned Employees</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fa fa-user-times"></i></div>
                        <div class="stat-value">{{ $unassignedEmployees }}</div>
                        <div class="stat-label">Unassigned Employees</div>
                    </div>
                </div>

                <!-- Salary Structures Grid -->
                <div class="hr-card">
                    <div class="hr-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="search-box">
                                <i class="fa fa-search"></i>
                                <input type="search" id="structureSearch" placeholder="Search structures...">
                            </div>
                        </div>
                        <span class="text-muted small" id="structureCount">{{ $structures->total() }} structures</span>
                    </div>

                    <div class="hr-grid" id="structuresGrid">
                        @forelse($structures as $structure)
                            @php
                                $childCount = $structure->children->sum('assigned_count');
                                $totalAssigned = $structure->assigned_count + $childCount;
                            @endphp
                            <div class="structure-card"
                                data-name="{{ strtolower($structure->name ?? $structure->salary_type) }}">
                                <div class="structure-card-header">
                                    <div style="flex: 1;">
                                        <h3 class="structure-title">
                                            {{ $structure->name }}
                                            @if ($structure->salary_type == 'both')
                                                <span class="structure-type-badge">Monthly + Comm.</span>
                                            @elseif($structure->salary_type == 'commission')
                                                <span class="structure-type-badge">Commission</span>
                                            @elseif($structure->use_daily_wages)
                                                @if ($structure->base_salary > 0)
                                                    <span class="structure-type-badge">Monthly + Daily</span>
                                                @else
                                                    <span class="structure-type-badge">Daily Wages</span>
                                                @endif
                                            @else
                                                <span class="structure-type-badge">Monthly Salary</span>
                                            @endif
                                        </h3>
                                        <div class="assigned-info">
                                            <i class="fa fa-users"></i>
                                            <span class="assigned-count">{{ $totalAssigned }}</span> Assigned
                                        </div>
                                    </div>
                                    <div class="salary-amount">
                                        <p class="salary-amount-value">
                                            Rs.
                                            {{ number_format($structure->use_daily_wages ? $structure->daily_wages : $structure->base_salary, 0) }}
                                        </p>
                                        <p class="salary-amount-label">
                                            {{ $structure->use_daily_wages ? 'Per Day' : 'Base Salary' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="structure-details">
                                    @if (!$structure->use_daily_wages)
                                        <div class="detail-row">
                                            <span class="detail-label">Allowances</span>
                                            <span class="detail-value text-success">+ Rs.
                                                {{ number_format($structure->total_allowances, 0) }}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Deductions</span>
                                            <span class="detail-value text-danger">- Rs.
                                                {{ number_format($structure->total_deductions, 0) }}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Net Salary</span>
                                            <span class="detail-value">Rs.
                                                {{ number_format($structure->base_salary + $structure->total_allowances - $structure->total_deductions, 0) }}</span>
                                        </div>
                                    @else
                                        <div class="detail-row">
                                            <span class="detail-label">Deduction Rules</span>
                                            <span class="detail-value">
                                                {{ count($structure->attendance_deduction_policy['late_rules'] ?? []) }}
                                                Late /
                                                {{ count($structure->attendance_deduction_policy['early_rules'] ?? []) }}
                                                Early
                                            </span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Carry Forward</span>
                                            <span class="detail-value">
                                                {{ $structure->carry_forward_deductions ? 'Yes' : 'No' }}
                                            </span>
                                        </div>
                                    @endif

                                    @if ($structure->commission_percentage)
                                        <div class="detail-row">
                                            <span class="detail-label">Commission</span>
                                            <span class="detail-value">{{ $structure->commission_percentage }}%</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="card-actions">
                                    @if ($canEdit)
                                        <a href="{{ route('hr.salary-structure.assign-page', $structure->id) }}"
                                            class="btn-assign-main" title="Assign to Employees">
                                            <i class="fa fa-user-plus me-1"></i> Assign Employees
                                        </a>
                                    @endif

                                    <div class="secondary-actions">
                                        @if ($canView || $canEdit)
                                            <a href="{{ route('hr.salary-structure.view-assigned', $structure->id) }}"
                                                class="action-btn" title="View Assigned Employees">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        @endif

                                        @if ($canEdit)
                                            <a href="{{ route('hr.salary-structure.edit-template', $structure->id) }}"
                                                class="action-btn" title="Edit Template">
                                                <i class="fa fa-pencil-alt"></i>
                                            </a>
                                            <a href="{{ route('hr.salary-structure.individual-update-page', $structure->id) }}"
                                                class="action-btn" title="Update Individual Employees">
                                                <i class="fa fa-user-cog"></i>
                                            </a>
                                        @endif

                                        @if ($canDelete)
                                            <a href="javascript:void(0);" onclick="deleteStructure({{ $structure->id }})"
                                                class="action-btn delete" title="Delete Template">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="empty-state">
                                    <i class="fa fa-money-bill-wave"></i>
                                    <p>No salary structures created yet.</p>
                                    @if ($canCreate || $canEdit)
                                        <button class="btn btn-create mt-3" onclick="createNewStructure()">
                                            <i class="fa fa-plus me-1"></i> Create First Structure
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforelse
                    </div>

                    @if ($structures->total() > 0)
                        <div class="px-4 py-3 border-top">
                            {{ $structures->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function createNewStructure() {
            window.location.href = "{{ route('hr.salary-structure.create') }}";
        }

        function deleteStructure(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this! This will permanently delete the structure template.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('hr.salary-structure.destroy-template', ':id') }}".replace(':id',
                            id),
                        type: 'POST', // Method override for DELETE is handled by _method
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Deleted!',
                                    response.success,
                                    'success'
                                ).then(() => {
                                    location.reload(); // Reload to update list/stats
                                });
                            }
                        },
                        error: function(xhr) {
                            let msg = 'Something went wrong!';
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                msg = xhr.responseJSON.error;
                            }
                            Swal.fire(
                                'Error!',
                                msg,
                                'error'
                            );
                        }
                    });
                }
            });
        }

        $(document).ready(function() {
            $('#structureSearch').on('input', function() {
                var q = $(this).val().toLowerCase();
                $('.structure-card').each(function() {
                    var name = $(this).data('name') || '';
                    $(this).toggle(name.indexOf(q) !== -1);
                });
                updateCount();
            });

            function updateCount() {
                $('#structureCount').text($('.structure-card:visible').length + ' structures');
            }
        });
    </script>
@endsection
