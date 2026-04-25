@extends('admin_panel.layout.app')

@section('content')
    @include('hr.partials.hr-styles')

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="page-title"><i class="fa fa-clock"></i> Shift Management</h1>
                        <p class="page-subtitle">Configure work shifts and schedules</p>
                    </div>
                    @can('hr.shifts.create')
                        <div class="btn-group">
                            @can('hr.shifts.edit')
                                <button type="button" class="btn btn-dark" id="syncBtn">
                                    <i class="fa fa-sync"></i> Sync to Device
                                </button>
                            @endcan
                            <button type="button" class="btn btn-create" id="createBtn">
                                <i class="fa fa-plus"></i> Add Shift
                            </button>
                        </div>
                    @endcan
                </div>

                <!-- Stats Row -->
                <!-- Stats Row -->
                @php
                    $defaultShift = \App\Models\Hr\Shift::where('is_default', true)->first();
                    // Count employees explicitly assigned to default shift
                    $defaultShiftEmpCount = $defaultShift
                        ? \App\Models\Hr\Employee::where('shift_id', $defaultShift->id)->count()
                        : 0;
                    // Count employees with custom times
                    $customTimeEmpCount = \App\Models\Hr\Employee::whereNotNull('custom_start_time')->count();
                    // Count employees with ANY shift assigned
                    $totalEmpWithShift = \App\Models\Hr\Employee::whereNotNull('shift_id')->count();
                @endphp
                <div class="stats-row">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="fa fa-users"></i></div>
                        <div class="stat-value">{{ $defaultShiftEmpCount }}</div>
                        <div class="stat-label">On Default Shift</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fa fa-user-clock"></i></div>
                        <div class="stat-value">{{ $customTimeEmpCount }}</div>
                        <div class="stat-label">Custom Timing</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
                        <div class="stat-value">{{ $totalEmpWithShift }}</div>
                        <div class="stat-label">Shift Assigned</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="fa fa-clock"></i></div>
                        <div class="stat-value">{{ $shifts->total() }}</div>
                        <div class="stat-label">Total Shifts</div>
                    </div>
                </div>

                <!-- Shifts Card -->
                <div class="hr-card">
                    <div class="hr-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="search-box">
                                <i class="fa fa-search"></i>
                                <input type="search" id="shiftSearch" placeholder="Search shifts...">
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-outline-secondary btn-sm" id="refreshBtn"><i
                                        class="fa fa-sync"></i></button>
                            </div>
                        </div>
                        <span class="text-muted small" id="shiftCount">{{ $shifts->total() }} shifts</span>
                    </div>

                    <div class="hr-grid" id="shiftGrid">
                        @forelse($shifts as $shift)
                            <div class="hr-item-card" data-id="{{ $shift->id }}"
                                data-name="{{ strtolower($shift->name) }}">
                                <div class="hr-item-header">
                                    <div class="d-flex align-items-center">
                                        <div class="hr-avatar"
                                            style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                                            <i class="fa fa-clock"></i>
                                        </div>
                                        <div class="hr-item-info">
                                            <h4 class="hr-item-name">{{ $shift->name }}</h4>
                                            <div class="hr-item-subtitle">
                                                {{ \Carbon\Carbon::parse($shift->start_time)->format('h:i A') }} -
                                                {{ \Carbon\Carbon::parse($shift->end_time)->format('h:i A') }}
                                            </div>
                                            <div class="hr-item-meta">
                                                Grace: {{ $shift->grace_minutes }} min • {{ $shift->employees->count() }}
                                                employees
                                            </div>
                                        </div>
                                    </div>
                                    <div class="hr-actions">
                                        @can('hr.shifts.edit')
                                            <button class="btn btn-edit edit-btn" title="Edit Shift">
                                                <i class="fa fa-pen"></i>
                                            </button>
                                        @endcan
                                        @can('hr.shifts.delete')
                                            <button class="btn btn-delete delete-btn" data-id="{{ $shift->id }}"
                                                title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                                <div class="hr-tags">
                                    @if ($shift->is_default)
                                        <span class="hr-tag success"><i class="fa fa-star me-1"></i>Default</span>
                                    @endif
                                    <span class="hr-tag info"><i
                                            class="fa fa-users me-1"></i>{{ $shift->employees->count() }} Employees</span>
                                    @if ($shift->break_start && $shift->break_end)
                                        <span class="hr-tag default"><i class="fa fa-coffee me-1"></i>Break:
                                            {{ \Carbon\Carbon::parse($shift->break_start)->format('h:i A') }} -
                                            {{ \Carbon\Carbon::parse($shift->break_end)->format('h:i A') }}</span>
                                    @endif
                                </div>

                                <input type="hidden" class="edit-data" data-id="{{ $shift->id }}"
                                    data-name="{{ $shift->name }}"
                                    data-start_time="{{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }}"
                                    data-end_time="{{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}"
                                    data-break_start="{{ $shift->break_start ? \Carbon\Carbon::parse($shift->break_start)->format('H:i') : '' }}"
                                    data-break_end="{{ $shift->break_end ? \Carbon\Carbon::parse($shift->break_end)->format('H:i') : '' }}"
                                    data-grace_minutes="{{ $shift->grace_minutes }}"
                                    data-is_default="{{ $shift->is_default ? '1' : '0' }}">
                            </div>
                        @empty
                            <div class="empty-state" style="grid-column: 1/-1;">
                                <i class="fa fa-clock"></i>
                                <p>No shifts found. Add your first shift!</p>
                            </div>
                        @endforelse
                    </div>
                    <div class="px-4 py-3 border-top">
                        {{ $shifts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="shiftModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header gradient">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fa fa-clock"></i>
                        <span>Add Shift</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="shiftForm" action="{{ route('hr.shifts.store') }}" method="POST" data-ajax-validate="true">
                    @csrf
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-tag"></i> Shift Name</label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        placeholder="e.g., Morning Shift" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-play"></i> Start Time</label>
                                    <input type="time" name="start_time" id="start_time" class="form-control"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-stop"></i> End Time</label>
                                    <input type="time" name="end_time" id="end_time" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-coffee"></i> Break Start</label>
                                    <input type="time" name="break_start" id="break_start" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-coffee"></i> Break End</label>
                                    <input type="time" name="break_end" id="break_end" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-hourglass-half"></i> Grace Period
                                        (minutes)</label>
                                    <input type="number" name="grace_minutes" id="grace_minutes" class="form-control"
                                        min="0" max="60" value="15" required>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="is_default" id="is_default" class="form-check-input"
                                        value="1">
                                    <label class="form-check-label" for="is_default">Set as Default Shift</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-save">
                            <i class="fa fa-check"></i>
                            <span>Save Shift</span>
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
            $('#syncBtn').click(function() {
                Swal.fire({
                    title: 'Sync Shifts?',
                    text: 'This will push all shift schedules to connected biometric devices.',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Sync',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return $.ajax({
                            url: '{{ route('hr.shifts.sync') }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                        }).catch(error => {
                            Swal.showValidationMessage(
                                `Request failed: ${error.responseJSON.error || error.statusText}`
                            )
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire('Synced!', result.value.success, 'success');
                    }
                });
            });

            $('#createBtn').click(function() {
                $('#shiftForm')[0].reset();
                $('#edit_id').val('');
                $('#modalTitle').html('<i class="fa fa-clock"></i><span>Add Shift</span>');
                $('#shiftModal').modal('show');
            });

            $(document).on('click', '.edit-btn', function() {
                var data = $(this).closest('.hr-item-card').find('.edit-data');
                $('#edit_id').val(data.data('id'));
                $('#name').val(data.data('name'));
                $('#start_time').val(data.data('start_time'));
                $('#end_time').val(data.data('end_time'));
                $('#break_start').val(data.data('break_start'));
                $('#break_end').val(data.data('break_end'));
                $('#grace_minutes').val(data.data('grace_minutes'));
                $('#is_default').prop('checked', data.data('is_default') == '1');
                $('#modalTitle').html('<i class="fa fa-pen"></i><span>Edit Shift</span>');
                $('#shiftModal').modal('show');
            });

            $(document).on('click', '.delete-btn', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Shift?',
                    text: 'This cannot be undone!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Yes, delete!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/hr/shifts/' + id,
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

            $('#shiftSearch').on('input', function() {
                var q = $(this).val().toLowerCase();
                $('.hr-item-card').each(function() {
                    var name = $(this).data('name') || '';
                    $(this).toggle(name.indexOf(q) !== -1);
                });
                $('#shiftCount').text($('.hr-item-card:visible').length + ' shifts');
            });

            $('#refreshBtn').click(() => location.reload());

            // Custom submit handler removed - using data-ajax-validate
        });
    </script>
@endsection
