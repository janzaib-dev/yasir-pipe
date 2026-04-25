@extends('admin_panel.layout.app')

@section('content')
    @include('hr.partials.hr-styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="page-title"><i class="fa fa-user-edit"></i> Update Salary Structure – Individual Employees
                        </h1>
                        <p class="page-subtitle">Select an employee to override their salary structure individually</p>
                    </div>
                    <a href="{{ route('hr.salary-structure.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Structures
                    </a>
                </div>

                <!-- Structure Context -->
                <div class="card bg-light mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-1 text-primary">{{ $salaryStructure->name }}</h5>
                                <span class="badge bg-secondary">{{ $salaryStructure->salary_type }}</span>
                                @if ($salaryStructure->use_daily_wages)
                                    <span class="badge bg-info">Daily Wages</span>
                                @endif
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block">Base Salary</small>
                                <span class="fw-bold fs-5">Rs. {{ number_format($salaryStructure->base_salary, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employees Table -->
                <div class="hr-card">
                    <div class="hr-header">
                        <h5 class="mb-0">Active Assignments</h5>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th>Current Salary</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($assignments as $employee)
                                    @php
                                        // Current salary info from Pivot or Structure?
                                        // The structure is the same for all (unless customized before?)
                                        // But here we list employees assigned to THIS structure.
                                        $breakdown = $salaryStructure->calculateMonthlySalary(0, 0);
                                        $total = $breakdown['net']; // Approximation
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $employee->full_name }}</strong>
                                        </td>
                                        <td>{{ $employee->department->name ?? 'N/A' }}</td>
                                        <td>{{ $employee->designation->name ?? 'N/A' }}</td>
                                        <td>
                                            Rs. {{ number_format($total, 2) }}
                                            <i class="fa fa-info-circle text-muted" title="Base Net Salary"></i>
                                        </td>
                                        <td>
                                            @if ($employee->status === 'active')
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">{{ ucfirst($employee->status) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($employee->status === 'active')
                                                <a href="{{ route('hr.salary-structure.edit-individual', $employee->id) }}"
                                                    class="btn btn-primary btn-sm">
                                                    <i class="fa fa-edit"></i> Update Custom
                                                </a>
                                            @else
                                                <button class="btn btn-secondary btn-sm" disabled
                                                    title="Cannot update inactive employee">
                                                    <i class="fa fa-ban"></i> Inactive
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fa fa-users mb-2 fs-3"></i><br>
                                            No active employees assigned to this structure.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-3 py-3">
                        {{ $assignments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
