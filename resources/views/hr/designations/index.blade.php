@extends('admin_panel.layout.app')

@section('content')
    @include('hr.partials.hr-styles')

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="page-title"><i class="fa fa-briefcase"></i> Designation Management</h1>
                        <p class="page-subtitle">Define job titles and positions in your organization</p>
                    </div>
                    @can('hr.designations.create')
                        <button type="button" class="btn btn-create" id="createBtn">
                            <i class="fa fa-plus"></i> Add Designation
                        </button>
                    @endcan
                </div>

                <!-- Stats Row -->
                <div class="stats-row">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="fa fa-briefcase"></i></div>
                        <div class="stat-value">{{ $designations->total() }}</div>
                        <div class="stat-label">Total Designations</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="fa fa-users"></i></div>
                        <div class="stat-value">{{ \App\Models\Hr\Employee::count() }}</div>
                        <div class="stat-label">Total Employees</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fa fa-building"></i></div>
                        <div class="stat-value">{{ \App\Models\Hr\Department::count() }}</div>
                        <div class="stat-label">Departments</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="fa fa-calendar"></i></div>
                        <div class="stat-value">
                            {{ \App\Models\Hr\Designation::where('created_at', '>=', now()->subDays(30))->count() }}
                        </div>
                        <div class="stat-label">Added This Month</div>
                    </div>
                </div>

                <!-- Designations Card -->
                <div class="hr-card">
                    <div class="hr-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="search-box">
                                <i class="fa fa-search"></i>
                                <input type="search" id="desigSearch" placeholder="Search designations...">
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-outline-secondary btn-sm" id="refreshBtn"><i
                                        class="fa fa-sync"></i></button>
                            </div>
                        </div>
                        <span class="text-muted small" id="desigCount">{{ $designations->total() }} designations</span>
                    </div>

                    <div class="hr-grid" id="desigGrid">
                        @forelse($designations as $desig)
                            @php
                                $empCount = \App\Models\Hr\Employee::where('designation_id', $desig->id)->count();
                            @endphp
                            <div class="hr-item-card" data-id="{{ $desig->id }}"
                                data-name="{{ strtolower($desig->name) }}"
                                data-desc="{{ strtolower($desig->description ?? '') }}">
                                <div class="hr-item-header">
                                    <div class="d-flex align-items-center">
                                        <div class="hr-avatar"
                                            style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                            {{ strtoupper(substr($desig->name, 0, 2)) }}
                                        </div>
                                        <div class="hr-item-info">
                                            <h4 class="hr-item-name">{{ $desig->name }}</h4>
                                            <div class="hr-item-subtitle">{{ $desig->description ?? 'No description' }}
                                            </div>
                                            <div class="hr-item-meta">
                                                ID: {{ $desig->id }} • Created
                                                {{ $desig->created_at?->diffForHumans() ?? 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="hr-actions">
                                        @can('hr.designations.edit')
                                            <button class="btn btn-edit edit-btn" title="Edit Designation">
                                                <i class="fa fa-pen"></i>
                                            </button>
                                        @endcan
                                        @can('hr.designations.delete')
                                            <button class="btn btn-delete delete-btn"
                                                data-url="{{ route('hr.designations.destroy', $desig->id) }}" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                                <div class="hr-tags">
                                    <span class="hr-tag info"><i class="fa fa-users me-1"></i>{{ $empCount }}
                                        Employees</span>
                                    @if ($desig->requires_location)
                                        <span class="hr-tag warning"><i class="fa fa-map-marker-alt me-1"></i>Location
                                            Required</span>
                                    @else
                                        <span class="hr-tag default"><i class="fa fa-building me-1"></i>On-Site</span>
                                    @endif
                                </div>

                                <input type="hidden" class="name" value="{{ $desig->name }}">
                                <input type="hidden" class="description" value="{{ $desig->description }}">
                                <input type="hidden" class="requires_location"
                                    value="{{ $desig->requires_location ? '1' : '0' }}">
                            </div>
                        @empty
                            <div class="empty-state" style="grid-column: 1/-1;">
                                <i class="fa fa-briefcase"></i>
                                <p>No designations found. Add your first designation!</p>
                            </div>
                        @endforelse
                    </div>
                    <div class="px-4 py-3 border-top">
                        {{ $designations->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="designationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header gradient">
                    <h5 class="modal-title" id="modalLabel">
                        <i class="fa fa-briefcase"></i>
                        <span>Add Designation</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="designationForm" action="{{ route('hr.designations.store') }}" method="POST"
                    data-ajax-validate="true">
                    @csrf
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="modal-body">
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-tag"></i> Designation Name</label>
                            <input type="text" name="name" id="name" class="form-control"
                                placeholder="Enter designation name" required>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-align-left"></i> Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="Enter description"></textarea>
                        </div>
                        <div class="form-group-modern">
                            <div class="form-check"
                                style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b;">
                                <input class="form-check-input" type="checkbox" name="requires_location"
                                    id="requires_location" value="1">
                                <label class="form-check-label" for="requires_location" style="color: #92400e;">
                                    <i class="fa fa-map-marker-alt me-1"></i> <strong>Location Required for
                                        Attendance</strong>
                                    <br><small class="text-muted">Enable for field workers who need to provide GPS location
                                        when marking attendance. Leave unchecked for on-site office staff.</small>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-save">
                            <i class="fa fa-check"></i>
                            <span>Save Designation</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $('#createBtn').click(function() {
                $('#edit_id').val('');
                $('#designationForm')[0].reset();
                $('#requires_location').prop('checked', false);
                $('#modalLabel').html('<i class="fa fa-briefcase"></i><span>Add Designation</span>');
                $('#designationModal').modal('show');
            });

            $(document).on('click', '.edit-btn', function() {
                var card = $(this).closest('.hr-item-card');
                $('#edit_id').val(card.data('id'));
                $('#name').val(card.find('.name').val());
                $('#description').val(card.find('.description').val());
                $('#requires_location').prop('checked', card.find('.requires_location').val() === '1');
                $('#modalLabel').html('<i class="fa fa-pen"></i><span>Edit Designation</span>');
                $('#designationModal').modal('show');
            });

            $(document).on('click', '.delete-btn', function() {
                var url = $(this).data('url');
                Swal.fire({
                    title: 'Delete Designation?',
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

            $('#desigSearch').on('input', function() {
                var q = $(this).val().toLowerCase();
                $('.hr-item-card').each(function() {
                    var name = $(this).data('name') || '';
                    var desc = $(this).data('desc') || '';
                    $(this).toggle(name.indexOf(q) !== -1 || desc.indexOf(q) !== -1);
                });
                $('#desigCount').text($('.hr-item-card:visible').length + ' designations');
            });

            $('#refreshBtn').click(() => location.reload());

            // Custom submit handler removed - using data-ajax-validate
        });
    </script>
@endsection
