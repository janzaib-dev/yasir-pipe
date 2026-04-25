@extends('admin_panel.layout.app')

@section('content')
    @include('hr.partials.hr-styles')

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="page-title"><i class="fa fa-building"></i> Department Management</h1>
                        <p class="page-subtitle">Organize your company into departments</p>
                    </div>
                    @can('hr.departments.create')
                        <button type="button" class="btn btn-create" id="createBtn">
                            <i class="fa fa-plus"></i> Add Department
                        </button>
                    @endcan
                </div>

                <!-- Stats Row -->
                <div class="stats-row">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="fa fa-building"></i></div>
                        <div class="stat-value">{{ $departments->count() }}</div>
                        <div class="stat-label">Total Departments</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="fa fa-users"></i></div>
                        <div class="stat-value">{{ \App\Models\Hr\Employee::count() }}</div>
                        <div class="stat-label">Total Employees</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fa fa-chart-bar"></i></div>
                        <div class="stat-value">
                            {{ $departments->count() > 0 ? round(\App\Models\Hr\Employee::count() / $departments->count(), 1) : 0 }}
                        </div>
                        <div class="stat-label">Avg per Dept</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="fa fa-calendar"></i></div>
                        <div class="stat-value">{{ $departments->where('created_at', '>=', now()->subDays(30))->count() }}
                        </div>
                        <div class="stat-label">Added This Month</div>
                    </div>
                </div>

                <!-- Departments Card -->
                <div class="hr-card">
                    <div class="hr-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="search-box">
                                <i class="fa fa-search"></i>
                                <input type="search" id="deptSearch" placeholder="Search departments...">
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-outline-secondary btn-sm" id="refreshBtn"><i
                                        class="fa fa-sync"></i></button>
                            </div>
                        </div>
                        <span class="text-muted small" id="deptCount">{{ $departments->count() }} departments</span>
                    </div>

                    <div class="hr-grid" id="deptGrid">
                        @forelse($departments as $dept)
                            @php
                                $empCount = \App\Models\Hr\Employee::where('department_id', $dept->id)->count();
                            @endphp
                            <div class="hr-item-card" data-id="{{ $dept->id }}"
                                data-name="{{ strtolower($dept->name) }}"
                                data-desc="{{ strtolower($dept->description ?? '') }}">
                                <div class="hr-item-header">
                                    <div class="d-flex align-items-center">
                                        <div class="hr-avatar"
                                            style="background: linear-gradient(135deg, #0ea5e9, #0284c7);">
                                            {{ strtoupper(substr($dept->name, 0, 2)) }}
                                        </div>
                                        <div class="hr-item-info">
                                            <h4 class="hr-item-name">{{ $dept->name }}</h4>
                                            <div class="hr-item-subtitle">{{ $dept->description ?? 'No description' }}
                                            </div>
                                            <div class="hr-item-meta">
                                                ID: {{ $dept->id }} • Created
                                                {{ $dept->created_at?->diffForHumans() ?? 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="hr-actions">
                                        @can('hr.departments.edit')
                                            <button class="btn btn-edit edit-btn" title="Edit Department">
                                                <i class="fa fa-pen"></i>
                                            </button>
                                        @endcan
                                        @can('hr.departments.delete')
                                            <button class="btn btn-delete delete-btn"
                                                data-url="{{ route('hr.departments.destroy', $dept->id) }}" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                                <div class="hr-tags">
                                    <span class="hr-tag info"><i class="fa fa-users me-1"></i>{{ $empCount }}
                                        Employees</span>
                                </div>

                                <!-- Hidden fields for edit -->
                                <input type="hidden" class="name" value="{{ $dept->name }}">
                                <input type="hidden" class="description" value="{{ $dept->description }}">
                            </div>
                        @empty
                            <div class="empty-state" style="grid-column: 1/-1;">
                                <i class="fa fa-building"></i>
                                <p>No departments found. Add your first department!</p>
                            </div>
                        @endforelse
                    </div>
                    <div class="px-4 py-3 border-top">
                        {{ $departments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="departmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header gradient">
                    <h5 class="modal-title" id="modalLabel">
                        <i class="fa fa-building"></i>
                        <span>Add Department</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="departmentForm" action="{{ route('hr.departments.store') }}" method="POST"
                    data-ajax-validate="true">
                    @csrf
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="modal-body">
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-tag"></i> Department Name</label>
                            <input type="text" name="name" id="name" class="form-control"
                                placeholder="Enter department name" required>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-align-left"></i> Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="Enter description"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-save">
                            <i class="fa fa-check"></i>
                            <span>Save Department</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Create
            $('#createBtn').click(function() {
                $('#edit_id').val('');
                $('#departmentForm')[0].reset();
                $('#modalLabel').html('<i class="fa fa-building"></i><span>Add Department</span>');
                $('#departmentModal').modal('show');
            });

            // Edit
            $(document).on('click', '.edit-btn', function() {
                var card = $(this).closest('.hr-item-card');
                $('#edit_id').val(card.data('id'));
                $('#name').val(card.find('.name').val());
                $('#description').val(card.find('.description').val());
                $('#modalLabel').html('<i class="fa fa-pen"></i><span>Edit Department</span>');
                $('#departmentModal').modal('show');
            });

            // Delete
            $(document).on('click', '.delete-btn', function() {
                var url = $(this).data('url');
                Swal.fire({
                    title: 'Delete Department?',
                    text: "This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Yes, delete!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Deleted!', response.success, 'success')
                                        .then(() => location.reload());
                                }
                            }
                        });
                    }
                });
            });

            // Search
            $('#deptSearch').on('input', function() {
                var q = $(this).val().toLowerCase();
                $('.hr-item-card').each(function() {
                    var name = $(this).data('name') || '';
                    var desc = $(this).data('desc') || '';
                    $(this).toggle(name.indexOf(q) !== -1 || desc.indexOf(q) !== -1);
                });
                $('#deptCount').text($('.hr-item-card:visible').length + ' departments');
            });

            // Refresh
            $('#refreshBtn').click(() => location.reload());

            // Manual Form Submit Removed - using data-ajax-validate
        });
    </script>
@endsection
