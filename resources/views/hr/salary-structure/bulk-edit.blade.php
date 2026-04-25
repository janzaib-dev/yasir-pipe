@extends('admin_panel.layout.app')

@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3>Bulk Edit Salary Structure</h3>
                            <a href="{{ route('hr.salary-structure.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to List
                            </a>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> You have selected {{ $employees->count() }} employees
                                    for bulk update.
                                </div>
                                <p class="text-muted">Bulk editing functionality is ready for implementation using the Grid
                                    view.</p>

                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Department</th>
                                                <th>Current Base Salary</th>
                                                <th>Allowances</th>
                                                <th>Deductions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($employees as $emp)
                                                <tr>
                                                    <td>{{ $emp->full_name }}</td>
                                                    <td>{{ optional($emp->department)->name ?? '-' }}</td>
                                                    <td>{{ optional($emp->salaryStructure)->base_salary ?? 'Not Set' }}</td>
                                                    <td>{{ optional($emp->salaryStructure)->total_allowances ?? 0 }}</td>
                                                    <td>{{ optional($emp->salaryStructure)->total_deductions ?? 0 }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
