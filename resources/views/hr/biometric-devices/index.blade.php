@extends('admin_panel.layout.app')

@section('content')
    @include('hr.partials.hr-styles')

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="page-title"><i class="fa fa-fingerprint"></i> Biometric Devices</h1>
                        <p class="page-subtitle">Manage fingerprint attendance devices</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light border" data-bs-toggle="modal"
                            data-bs-target="#deviceGuideModal">
                            <i class="fa fa-question-circle text-primary me-1"></i> Setup Guide
                        </button>
                        @can('hr.biometric.devices.create')
                            <button type="button" class="btn btn-create" id="addDeviceBtn">
                                <i class="fa fa-plus"></i> Add Device
                            </button>
                        @endcan
                    </div>
                </div>

                <!-- Device Guidance Modal -->
                <div class="modal fade" id="deviceGuideModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header gradient text-white">
                                <h5 class="modal-title font-weight-bold"><i class="fa fa-info-circle me-2"></i> Biometric
                                    Device Guide</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold text-primary mb-3"><i class="fa fa-link me-2"></i> Connection
                                            Steps</h6>
                                        <ol class="small ps-3">
                                            <li class="mb-2"><strong>Network:</strong> Ensure the device and server are on
                                                the same network or the device IP is public/forwarded.</li>
                                            <li class="mb-2"><strong>Configuration:</strong> Add the device with its IP
                                                and Port (Default is 4370 for ZKTeco/ Hikvision).</li>
                                            <li class="mb-2"><strong>Test:</strong> Always use the "Test" button to
                                                confirm communication before syncing.</li>
                                        </ol>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="fw-bold text-primary mb-3"><i class="fa fa-sync me-2"></i>
                                            Synchronization</h6>
                                        <ol class="small ps-3">
                                            <li class="mb-2"><strong>Sync Employees:</strong> Use the "Sync" button to
                                                push active employees to the device. This assigns them a "Device User ID".
                                            </li>
                                            <li class="mb-2"><strong>Enrolment:</strong> After syncing, enrol the
                                                employee's fingerprint on the physical device using the "Device User ID"
                                                shown in their profile.</li>
                                            <li class="mb-2"><strong>Logs:</strong> Once set up, logs are pulled
                                                automatically or manually via the Attendance module.</li>
                                        </ol>
                                    </div>
                                    <div class="col-12 mt-4 pt-3 border-top">
                                        <h6 class="fw-bold text-danger mb-2"><i class="fa fa-tools me-2"></i>
                                            Troubleshooting</h6>
                                        <div class="p-3 bg-light rounded-3 small">
                                            <ul class="mb-0">
                                                <li><strong>Failed Test:</strong> Check if Port 80 (Hikvision) or 4370
                                                    (ZKTeco) is open. Verify the Device ID/Username/Password.</li>
                                                <li><strong>No Logs:</strong> Ensure employees were synced *before*
                                                    punching. The "Device User ID" must exactly match the ID on the physical
                                                    terminal.</li>
                                                <li><strong>Punch Gap:</strong> If check-outs aren't appearing, verify you
                                                    haven't punched within the "Punch Gap" timeframe configured below.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Global Attendance Settings Card -->
                <div class="card mb-4"
                    style="border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #667eea;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1"><i class="fa fa-cog text-primary me-2"></i>Global Attendance Settings
                                </h5>
                                <small class="text-muted">Settings that apply to all employees</small>
                            </div>
                        </div>
                        <form id="globalSettingsForm" class="row align-items-end">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label"><i class="fa fa-stopwatch me-1"></i> Punch Gap (Minutes)</label>
                                <input type="number" name="attendance_punch_gap_minutes" id="punch_gap_minutes"
                                    class="form-control" value="{{ \App\Models\Hr\HrSetting::getPunchGapMinutes() }}"
                                    min="1" max="120" required>
                                <small class="text-muted">Min. gap between check-in and check-out punches</small>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary" id="saveSettingsBtn">
                                    <i class="fa fa-save me-1"></i> Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Devices Grid -->
                <div class="row">
                    @forelse ($devices as $device)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100" style="border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="mb-1">{{ $device->name }}</h5>
                                            <small class="text-muted">{{ $device->model ?? 'N/A' }}</small>
                                        </div>
                                        <span class="badge {{ $device->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $device->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fa fa-network-wired me-2 text-primary"></i>
                                            <span>{{ $device->ip_address }}:{{ $device->port }}</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fa fa-users me-2 text-success"></i>
                                            <span>{{ $device->employees->count() }} employees enrolled</span>
                                        </div>
                                        @if ($device->last_sync_at)
                                            <div class="d-flex align-items-center">
                                                <i class="fa fa-sync me-2 text-info"></i>
                                                <small>Last sync: {{ $device->last_sync_at->diffForHumans() }}</small>
                                            </div>
                                        @endif
                                    </div>

                                    @if ($device->notes)
                                        <p class="text-muted small mb-3">{{ $device->notes }}</p>
                                    @endif

                                    <!-- Action Buttons -->
                                    <div class="btn-group w-100 mb-2" role="group">
                                        @can('hr.biometric.devices.edit')
                                            <button class="btn btn-sm btn-primary test-connection-btn"
                                                data-id="{{ $device->id }}">
                                                <i class="fa fa-plug"></i> Test
                                            </button>
                                            <button class="btn btn-sm btn-info sync-employees-btn"
                                                data-id="{{ $device->id }}">
                                                <i class="fa fa-users"></i> Sync
                                            </button>
                                        @endcan
                                    </div>

                                    <div class="btn-group w-100" role="group">
                                        @can('hr.biometric.devices.edit')
                                            <button class="btn btn-sm btn-outline-secondary edit-device-btn"
                                                data-id="{{ $device->id }}" data-name="{{ $device->name }}"
                                                data-ip="{{ $device->ip_address }}" data-port="{{ $device->port }}"
                                                data-username="{{ $device->username }}" data-model="{{ $device->model }}"
                                                data-notes="{{ $device->notes }}" data-active="{{ $device->is_active }}">
                                                <i class="fa fa-edit"></i> Edit
                                            </button>
                                        @endcan
                                        @can('hr.biometric.devices.delete')
                                            <button class="btn btn-sm btn-outline-danger delete-device-btn"
                                                data-id="{{ $device->id }}">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <i class="fa fa-fingerprint" style="font-size: 48px; color: #ccc;"></i>
                                    <p class="mt-3 text-muted">No biometric devices configured yet.</p>
                                    @can('hr.biometric.devices.create')
                                        <button class="btn btn-primary mt-2" id="addDeviceBtnEmpty">
                                            <i class="fa fa-plus"></i> Add Your First Device
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Device Modal -->
    <div class="modal fade" id="deviceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header gradient">
                    <h5 class="modal-title" id="deviceModalLabel">
                        <i class="fa fa-fingerprint"></i>
                        <span id="modalTitle">Add Device</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="deviceForm" data-ajax-validate="true">
                    @csrf
                    <input type="hidden" id="device_id" name="device_id">
                    <input type="hidden" id="_method" name="_method" value="POST">

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label"><i class="fa fa-tag"></i> Device Name</label>
                                <input type="text" name="name" id="device_name" class="form-control"
                                    placeholder="e.g., Main Office - BC-K40" required>
                            </div>

                            <div class="col-md-8 mb-3">
                                <label class="form-label"><i class="fa fa-network-wired"></i> IP Address</label>
                                <input type="text" name="ip_address" id="device_ip" class="form-control"
                                    placeholder="e.g., 192.0.0.64" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label"><i class="fa fa-plug"></i> Port</label>
                                <input type="number" name="port" id="device_port" class="form-control"
                                    value="4370" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fa fa-user"></i> Username</label>
                                <input type="text" name="username" id="device_username" class="form-control"
                                    placeholder="Optional">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fa fa-lock"></i> Password</label>
                                <input type="password" name="password" id="device_password" class="form-control"
                                    placeholder="Optional">
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label"><i class="fa fa-info-circle"></i> Model</label>
                                <input type="text" name="model" id="device_model" class="form-control"
                                    placeholder="e.g., BC-K40">
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label"><i class="fa fa-sticky-note"></i> Notes</label>
                                <textarea name="notes" id="device_notes" class="form-control" rows="2" placeholder="Optional notes"></textarea>
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" id="device_active" class="form-check-input"
                                        value="1" checked>
                                    <label class="form-check-label" for="device_active">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check"></i> Save Device
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Save Global Settings
            $('#globalSettingsForm').submit(function(e) {
                e.preventDefault();
                const btn = $('#saveSettingsBtn');
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    url: '{{ route('hr.settings.update') }}',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        Swal.fire('Saved!', response.success || 'Settings updated', 'success');
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.error ||
                            'Failed to save settings', 'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="fa fa-save me-1"></i> Save Settings');
                    }
                });
            });

            // Open Add Modal
            $('#addDeviceBtn, #addDeviceBtnEmpty').click(function() {
                $('#device_id').val('');
                $('#_method').val('POST');
                $('#deviceForm')[0].reset();
                $('#device_active').prop('checked', true);
                $('#modalTitle').text('Add Device');
                $('#deviceModal').modal('show');
            });

            // Open Edit Modal
            $('.edit-device-btn').click(function() {
                const id = $(this).data('id');
                $('#device_id').val(id);
                $('#_method').val('PUT');
                $('#device_name').val($(this).data('name'));
                $('#device_ip').val($(this).data('ip'));
                $('#device_port').val($(this).data('port'));
                $('#device_username').val($(this).data('username'));
                $('#device_model').val($(this).data('model'));
                $('#device_notes').val($(this).data('notes'));
                $('#device_active').prop('checked', $(this).data('active') == 1);
                $('#modalTitle').text('Edit Device');
                $('#deviceModal').modal('show');
            });

            // Submit Form
            $('#deviceForm').submit(function(e) {
                e.preventDefault();

                const deviceId = $('#device_id').val();
                const method = $('#_method').val();
                const url = deviceId ?
                    '{{ route('hr.biometric-devices.store') }}/'.replace('biometric-devices',
                        `biometric-devices/${deviceId}`) :
                    '{{ route('hr.biometric-devices.store') }}';

                const formData = new FormData(this);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-HTTP-Method-Override': method
                    },
                    success: function(response) {
                        $('#deviceModal').modal('hide');
                        Swal.fire('Success!', response.message, 'success').then(() => location
                            .reload());
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors || {};
                        let errorMsg = xhr.responseJSON?.message || 'Failed to save device';

                        if (Object.keys(errors).length > 0) {
                            errorMsg += ':\n' + Object.values(errors).flat().join('\n');
                        }

                        Swal.fire('Error!', errorMsg, 'error');
                    }
                });
            });

            // Test Connection
            $('.test-connection-btn').click(function() {
                const deviceId = $(this).data('id');
                $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Testing...');

                $.post(`{{ route('hr.biometric-devices.index') }}/${deviceId}/test`, {
                        _token: '{{ csrf_token() }}'
                    })
                    .done(response => {
                        Swal.fire(response.success ? 'Connected!' : 'Failed', response.message,
                            response.success ? 'success' : 'error');
                    })
                    .fail(() => {
                        Swal.fire('Error!', 'Failed to test connection', 'error');
                    })
                    .always(() => {
                        $(this).prop('disabled', false).html('<i class="fa fa-plug"></i> Test');
                    });
            });

            // Sync Employees
            $('.sync-employees-btn').click(function() {
                const deviceId = $(this).data('id');
                const btn = $(this);

                Swal.fire({
                    title: 'Sync Employees?',
                    text: 'This will push all active employees to the device.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, sync!',
                }).then(result => {
                    if (result.isConfirmed) {
                        btn.prop('disabled', true).html(
                            '<i class="fa fa-spinner fa-spin"></i> Syncing...');

                        $.post(`{{ route('hr.biometric-devices.index') }}/${deviceId}/sync-employees`, {
                                _token: '{{ csrf_token() }}'
                            })
                            .done(response => {
                                Swal.fire('Success!', response.message, 'success').then(() =>
                                    location.reload());
                            })
                            .fail(xhr => {
                                Swal.fire('Error!', xhr.responseJSON?.message ||
                                    'Failed to sync employees', 'error');
                            })
                            .always(() => {
                                btn.prop('disabled', false).html(
                                    '<i class="fa fa-users"></i> Sync');
                            });
                    }
                });
            });

            // Pull Attendance
            $('.pull-attendance-btn').click(function() {
                const deviceId = $(this).data('id');
                const btn = $(this);

                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Pulling...');

                $.post(`{{ route('hr.biometric-devices.index') }}/${deviceId}/pull-attendance`, {
                        _token: '{{ csrf_token() }}'
                    })
                    .done(response => {
                        Swal.fire('Success!', response.message, 'success').then(() => location
                            .reload());
                    })
                    .fail(xhr => {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to pull attendance',
                            'error');
                    })
                    .always(() => {
                        btn.prop('disabled', false).html('<i class="fa fa-download"></i> Pull');
                    });
            });

            // Delete Device
            $('.delete-device-btn').click(function() {
                const deviceId = $(this).data('id');

                Swal.fire({
                    title: 'Delete Device?',
                    text: 'This action cannot be undone!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Yes, delete!',
                }).then(result => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `{{ route('hr.biometric-devices.index') }}/${deviceId}`,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire('Deleted!', response.message, 'success').then(
                                    () => location.reload());
                            },
                            error: function() {
                                Swal.fire('Error!', 'Failed to delete device', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
