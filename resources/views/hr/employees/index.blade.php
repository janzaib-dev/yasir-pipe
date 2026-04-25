@extends('admin_panel.layout.app')

@section('content')
    <!-- Script for Face API -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

    @include('hr.partials.hr-styles')

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="page-title"><i class="fa fa-users"></i> Employee Management</h1>
                        <p class="page-subtitle">Manage your organization's employee database</p>
                    </div>
                    @can('hr.employees.create')
                        <button type="button" class="btn btn-create" id="createBtn">
                            <i class="fa fa-user-plus"></i> Add Employee
                        </button>
                    @endcan
                </div>

                <!-- Stats Row -->
                @php
                    $activeCount = $employees->where('status', 'active')->count();
                    $nonActiveCount = $employees->where('status', 'non-active')->count();
                    $terminatedCount = $employees->where('status', 'terminated')->count();
                @endphp
                <div class="stats-row">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="fa fa-users"></i></div>
                        <div class="stat-value">{{ $employees->count() }}</div>
                        <div class="stat-label">Total Employees</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="fa fa-user-check"></i></div>
                        <div class="stat-value">{{ $activeCount }}</div>
                        <div class="stat-label">Active</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fa fa-user-clock"></i></div>
                        <div class="stat-value">{{ $nonActiveCount }}</div>
                        <div class="stat-label">Non-Active</div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-icon"><i class="fa fa-user-times"></i></div>
                        <div class="stat-value">{{ $terminatedCount }}</div>
                        <div class="stat-label">Terminated</div>
                    </div>
                </div>

                <!-- Employees Card -->
                <div class="hr-card">
                    <div class="hr-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="search-box">
                                <i class="fa fa-search"></i>
                                <input type="search" id="empSearch" placeholder="Search employees...">
                            </div>
                            <!-- Department Filter -->
                            <select id="deptFilter" class="form-select form-select-sm" style="width: 150px;">
                                <option value="all">All Departments</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ strtolower($dept->name) }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            <!-- Designation Filter -->
                            <select id="desigFilter" class="form-select form-select-sm" style="width: 150px;">
                                <option value="all">All Designations</option>
                                @foreach ($designations as $des)
                                    <option value="{{ strtolower($des->name) }}">{{ $des->name }}</option>
                                @endforeach
                            </select>
                            <!-- Status Filter -->
                            <select id="statusFilter" class="form-select form-select-sm" style="width: 130px;">
                                <option value="all">All Status</option>
                                <option value="active">Active</option>
                                <option value="non-active">Non-Active</option>
                                <option value="terminated">Terminated</option>
                            </select>

                            <div class="btn-group">
                                <button class="btn btn-outline-info btn-sm" data-filter="custom_time"
                                    title="Show Custom Timings"><i class="fa fa-clock"></i></button>
                                <button class="btn btn-outline-warning btn-sm" data-filter="default_shift"
                                    title="Show Default Shift"><i class="fa fa-user-clock"></i></button>
                                <button class="btn btn-outline-secondary btn-sm active" data-filter="all"
                                    title="Show All"><i class="fa fa-list"></i></button>
                                <button class="btn btn-outline-secondary btn-sm" id="refreshBtn"><i
                                        class="fa fa-sync"></i></button>
                            </div>
                        </div>
                        <span class="text-muted small" id="empCount">{{ $employees->count() }} employees</span>
                    </div>

                    <div class="hr-grid" id="empGrid">
                        @forelse($employees as $emp)
                            <div class="hr-item-card" data-id="{{ $emp->id }}"
                                data-name="{{ strtolower($emp->full_name) }}" data-email="{{ strtolower($emp->email) }}"
                                data-dept="{{ strtolower($emp->department->name ?? '') }}">
                                <div class="hr-item-header">
                                    <div class="d-flex align-items-center">
                                        <div class="hr-avatar">
                                            {{ strtoupper(substr($emp->first_name, 0, 1) . substr($emp->last_name, 0, 1)) }}
                                        </div>
                                        <div class="hr-item-info">
                                            <h4 class="hr-item-name">{{ $emp->full_name }}</h4>
                                            <div class="hr-item-subtitle">{{ $emp->email }}</div>
                                            <div class="hr-item-meta">
                                                ID: {{ $emp->id }} • Joined
                                                {{ \Carbon\Carbon::parse($emp->joining_date)->format('M d, Y') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="hr-actions">
                                        @can('hr.employees.edit')
                                            <button class="btn btn-success register-face-btn" data-id="{{ $emp->id }}"
                                                data-name="{{ $emp->full_name }}" title="Register Face ID">
                                                <i class="fa fa-camera"></i>
                                            </button>
                                            <button class="btn btn-edit edit-btn" title="Edit Employee">
                                                <i class="fa fa-pen"></i>
                                            </button>
                                        @endcan
                                        @can('hr.employees.delete')
                                            <button class="btn btn-delete delete-btn"
                                                data-url="{{ route('hr.employees.destroy', $emp->id) }}" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                                <div class="hr-tags">
                                    <span class="hr-tag default mb-1"><i
                                            class="fa fa-building me-1"></i>{{ $emp->department->name ?? 'N/A' }}</span>
                                    <span class="hr-tag default mb-1"><i
                                            class="fa fa-briefcase me-1"></i>{{ $emp->designation->name ?? 'N/A' }}</span>
                                    @if ($emp->custom_start_time)
                                        <span class="hr-tag warning mb-1"><i class="fa fa-clock me-1"></i>Custom
                                            Timing</span>
                                    @else
                                        <span class="hr-tag info mb-1"><i
                                                class="fa fa-clock me-1"></i>{{ $emp->shift->name ?? 'Default' }}</span>
                                    @endif
                                    <span
                                        class="hr-tag {{ $emp->status == 'active' ? 'success' : ($emp->status == 'non-active' ? 'warning' : 'danger') }} mb-1">
                                        {{ ucfirst($emp->status) }}
                                    </span>
                                    @if (!empty($emp->face_encoding) && is_array($emp->face_encoding) && count($emp->face_encoding) > 0)
                                        <span class="badge bg-primary p-2 mb-1"><i class="fa fa-smile me-1"></i>Face ID
                                            Set</span>
                                    @else
                                        <span class="badge bg-secondary p-2 mb-1"><i class="fa fa-meh me-1"></i>No Face
                                            ID</span>
                                    @endif
                                </div>

                                <!-- Hidden fields for edit -->
                                <input type="hidden" class="first_name" value="{{ $emp->first_name }}">
                                <input type="hidden" class="last_name" value="{{ $emp->last_name }}">
                                <input type="hidden" class="email" value="{{ $emp->email }}">
                                <input type="hidden" class="phone" value="{{ $emp->phone }}">
                                <input type="hidden" class="address" value="{{ $emp->address }}">
                                <input type="hidden" class="department_id" value="{{ $emp->department_id }}">
                                <input type="hidden" class="designation_id" value="{{ $emp->designation_id }}">
                                <input type="hidden" class="shift_id" value="{{ $emp->shift_id }}">
                                <input type="hidden" class="custom_start_time" value="{{ $emp->custom_start_time }}">
                                <input type="hidden" class="custom_end_time" value="{{ $emp->custom_end_time }}">
                                <input type="hidden" class="joining_date" value="{{ $emp->joining_date }}">
                                <input type="hidden" class="basic_salary" value="{{ $emp->basic_salary }}">
                                <input type="hidden" class="status" value="{{ $emp->status }}">
                                <input type="hidden" class="is_docs_submitted" value="{{ $emp->is_docs_submitted }}">
                                <input type="hidden" class="doc_degree" value="{{ $emp->getDocument('degree') }}">
                                <input type="hidden" class="doc_certificate"
                                    value="{{ $emp->getDocument('certificate') }}">
                                <input type="hidden" class="doc_hsc_marksheet"
                                    value="{{ $emp->getDocument('hsc_marksheet') }}">
                                <input type="hidden" class="doc_ssc_marksheet"
                                    value="{{ $emp->getDocument('ssc_marksheet') }}">
                                <input type="hidden" class="doc_cv" value="{{ $emp->getDocument('cv') }}">
                                <input type="hidden" class="casual_leave_dates"
                                    value="{{ $emp->leaves->pluck('start_date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))->implode(',') }}">
                            </div>
                        @empty
                            <div class="empty-state" style="grid-column: 1/-1;">
                                <i class="fa fa-users"></i>
                                <p>No employees found. Add your first employee!</p>
                            </div>
                        @endforelse
                    </div>
                    <div class="px-4 py-3 border-top">
                        {{ $employees->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header gradient">
                    <h5 class="modal-title" id="modalLabel">
                        <i class="fa fa-user-plus"></i>
                        <span>Add Employee</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="employeeForm" action="{{ route('hr.employees.store') }}" method="POST"
                    enctype="multipart/form-data" data-ajax-validate="true">
                    @csrf
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="modal-body">
                        <div class="row">
                            <!-- Personal Info -->
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-user"></i> First Name</label>
                                    <input type="text" name="first_name" id="first_name" class="form-control"
                                        placeholder="Enter first name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-user"></i> Last Name</label>
                                    <input type="text" name="last_name" id="last_name" class="form-control"
                                        placeholder="Enter last name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-envelope"></i> Email</label>
                                    <input type="email" name="email" id="email" class="form-control"
                                        placeholder="Enter email address" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-lock"></i> Password</label>
                                    <div class="input-group">
                                        <input type="password" name="password" id="password" class="form-control"
                                            placeholder="Leave blank to keep existing">
                                        <button class="btn btn-outline-secondary toggle-password" type="button"
                                            data-target="password">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-phone"></i> Phone</label>
                                    <input type="text" name="phone" id="phone" class="form-control"
                                        placeholder="Enter phone number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-building"></i> Department</label>
                                    <select name="department_id" id="department_id" class="form-select" required>
                                        <option value="">Select Department</option>
                                        @foreach ($departments as $dept)
                                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-clock"></i> Shift</label>
                                    <select name="shift_id" id="shift_id" class="form-select">
                                        @php
                                            $defaultShift = $shifts->where('is_default', true)->first();
                                            $otherShifts = $shifts->where('is_default', false);
                                        @endphp
                                        @if ($defaultShift)
                                            <option value="{{ $defaultShift->id }}">
                                                Default - {{ $defaultShift->name }}
                                                ({{ \Carbon\Carbon::parse($defaultShift->start_time)->format('h:i A') }} -
                                                {{ \Carbon\Carbon::parse($defaultShift->end_time)->format('h:i A') }})
                                            </option>
                                        @else
                                            <option value="">Default (9AM - 6PM)</option>
                                        @endif
                                        @foreach ($otherShifts as $shift)
                                            <option value="{{ $shift->id }}">{{ $shift->name }}
                                                ({{ \Carbon\Carbon::parse($shift->start_time)->format('h:i A') }} -
                                                {{ \Carbon\Carbon::parse($shift->end_time)->format('h:i A') }})
                                            </option>
                                        @endforeach
                                        <option value="custom">Custom Timing</option>
                                    </select>
                                </div>
                            </div>
                            <!-- Custom Time Container -->
                            <div id="custom_time_container" class="row col-12 ps-0 pe-0 ms-0 me-0"
                                style="display: none;">
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label"><i class="fa fa-clock"></i> Custom Start Time</label>
                                        <input type="time" name="custom_start_time" id="custom_start_time"
                                            class="form-control">
                                        <small class="text-muted">Overrides Shift Start Time</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label"><i class="fa fa-clock"></i> Custom End Time</label>
                                        <input type="time" name="custom_end_time" id="custom_end_time"
                                            class="form-control">
                                        <small class="text-muted">Overrides Shift End Time</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-briefcase"></i> Designation</label>
                                    <select name="designation_id" id="designation_id" class="form-select" required>
                                        <option value="">Select Designation</option>
                                        @foreach ($designations as $des)
                                            <option value="{{ $des->id }}">{{ $des->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-calendar"></i> Joining Date</label>
                                    <input type="date" name="joining_date" id="joining_date" class="form-control"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-money-bill"></i> Basic Salary</label>
                                    <input type="number" step="0.01" name="basic_salary" id="basic_salary"
                                        class="form-control" placeholder="Enter basic salary" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-toggle-on"></i> Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="active">Active</option>
                                        <option value="non-active">Non-Active</option>
                                        <option value="terminated">Terminated</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group-modern">
                                    <label class="form-label"><i class="fa fa-map-marker-alt"></i> Address</label>
                                    <textarea name="address" id="address" class="form-control" rows="2" placeholder="Enter address"></textarea>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="is_docs_submitted"
                                        id="is_docs_submitted" value="1">
                                    <label class="form-check-label" for="is_docs_submitted">Documents Submitted</label>
                                </div>
                            </div>

                            <!-- Casual Leave Days -->
                            <div class="col-md-12 mb-3">
                                <div class="form-group-modern">
                                    <label class="form-label" for="casual_leave_days">
                                        <i class="fa fa-calendar-check me-1"></i>
                                        Casual Leave Days
                                    </label>
                                    <div id="casual_leave_days_container"
                                        style="display: flex; flex-wrap: wrap; gap: 8px; padding: 8px 0;">
                                        <span class="casual-day-option badge bg-light text-dark border"
                                            data-value="Monday"
                                            style="cursor:pointer; padding:8px 16px; font-size:15px;">Monday</span>
                                        <span class="casual-day-option badge bg-light text-dark border"
                                            data-value="Tuesday"
                                            style="cursor:pointer; padding:8px 16px; font-size:15px;">Tuesday</span>
                                        <span class="casual-day-option badge bg-light text-dark border"
                                            data-value="Wednesday"
                                            style="cursor:pointer; padding:8px 16px; font-size:15px;">Wednesday</span>
                                        <span class="casual-day-option badge bg-light text-dark border"
                                            data-value="Thursday"
                                            style="cursor:pointer; padding:8px 16px; font-size:15px;">Thursday</span>
                                        <span class="casual-day-option badge bg-light text-dark border"
                                            data-value="Friday"
                                            style="cursor:pointer; padding:8px 16px; font-size:15px;">Friday</span>
                                        <span class="casual-day-option badge bg-light text-dark border"
                                            data-value="Saturday"
                                            style="cursor:pointer; padding:8px 16px; font-size:15px;">Saturday</span>
                                        <span class="casual-day-option badge bg-light text-dark border"
                                            data-value="Sunday"
                                            style="cursor:pointer; padding:8px 16px; font-size:15px;">Sunday</span>
                                    </div>
                                    <input type="hidden" name="casual_leave_days" id="casual_leave_days" />
                                    <small class="text-muted d-block mt-1">
                                        <i class="fa fa-info-circle me-1"></i>
                                        Click to select/deselect casual leave days. Selected days will be highlighted.
                                    </small>
                                </div>
                            </div>

                            <!-- Documents -->
                            <div id="documents_container" class="row" style="display: none;">
                                <div class="col-12 mb-3">
                                    <h6 class="text-primary"><i class="fa fa-file-alt me-2"></i>Upload Documents</h6>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label">Degree <span id="link_degree"></span></label>
                                        <input type="file" name="document_degree" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label">Certificate <span id="link_certificate"></span></label>
                                        <input type="file" name="document_certificate" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label">Intermediate Marksheet <span
                                                id="link_hsc_marksheet"></span></label>
                                        <input type="file" name="document_hsc_marksheet" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label">Matric Marksheet <span
                                                id="link_ssc_marksheet"></span></label>
                                        <input type="file" name="document_ssc_marksheet" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="form-label">CV <span id="link_cv"></span></label>
                                        <input type="file" name="document_cv" class="form-control">
                                    </div>
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
                            <span>Save Employee</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <style>
        .casual-day-option.selected {
            background: #007bff !important;
            color: #fff !important;
            border-color: #007bff !important;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.08);
        }

        .casual-day-option:hover {
            background: #e3f0ff !important;
        }
    </style>
    <!-- jQuery and Bootstrap are already loaded in the main layout -->

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Badge click handler for casual leave days
        $(document).ready(function() {
            var $container = $('#casual_leave_days_container');
            var $hiddenInput = $('#casual_leave_days');

            $container.on('click', '.casual-day-option', function() {
                $(this).toggleClass('selected');
                var selected = [];
                $container.find('.casual-day-option.selected').each(function() {
                    selected.push($(this).data('value'));
                });
                $hiddenInput.val(selected.join(','));
                console.log('Selected days:', selected);
            });
        });

        // Debug: Check if jQuery is loaded
        console.log('jQuery loaded:', typeof jQuery !== 'undefined');
        console.log('$ loaded:', typeof $ !== 'undefined');

        $(document).ready(function() {
            console.log('Document ready fired');
            console.log('createBtn exists:', $('#createBtn').length > 0);
            console.log('employeeModal exists:', $('#employeeModal').length > 0);

            // Toggle custom shift fields
            $('#shift_id').change(function() {
                if ($(this).val() === 'custom') {
                    $('#custom_time_container').slideDown();
                } else {
                    $('#custom_time_container').slideUp();
                    $('#custom_start_time').val('');
                    $('#custom_end_time').val('');
                }
            });

            // Create Employee - using event delegation to ensure it works
            $(document).on('click', '#createBtn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Create button clicked!');
                console.log('Modal element:', $('#employeeModal'));
                console.log('Bootstrap modal method:', typeof $('#employeeModal').modal);

                $('#edit_id').val('');
                $('#employeeForm')[0].reset();
                $('#is_docs_submitted').prop('checked', false);
                $('#documents_container').hide();
                $('#custom_time_container').hide();
                $('#shift_id').val($('#shift_id option:first').val()); // Select Default
                $('#link_degree, #link_certificate, #link_hsc_marksheet, #link_ssc_marksheet, #link_cv')
                    .html('');
                $('#modalLabel').html('<i class="fa fa-user-plus"></i><span>Add Employee</span>');

                // Clear casual leave day selections
                $('#casual_leave_days_container .casual-day-option').removeClass('selected');
                $('#casual_leave_days').val('');

                console.log('About to show modal...');
                $('#employeeModal').modal('show');
                console.log('Modal show called');
            });

            // Edit Employee
            $(document).on('click', '.edit-btn', function() {
                var card = $(this).closest('.hr-item-card');
                $('#edit_id').val(card.data('id'));
                $('#first_name').val(card.find('.first_name').val());
                $('#last_name').val(card.find('.last_name').val());
                $('#email').val(card.find('.email').val());
                $('#phone').val(card.find('.phone').val());
                $('#address').val(card.find('.address').val());
                $('#department_id').val(card.find('.department_id').val());
                $('#designation_id').val(card.find('.designation_id').val());

                // Handle Shift/Custom Logic
                var customStart = card.find('.custom_start_time').val();
                if (customStart && customStart !== '') {
                    $('#shift_id').val('custom');
                    $('#custom_start_time').val(card.find('.custom_start_time').val());
                    $('#custom_end_time').val(card.find('.custom_end_time').val());
                    $('#custom_time_container').show();
                } else {
                    $('#shift_id').val(card.find('.shift_id').val());
                    $('#custom_time_container').hide();
                }

                $('#joining_date').val(card.find('.joining_date').val());
                $('#basic_salary').val(card.find('.basic_salary').val());
                $('#status').val(card.find('.status').val());


                if (card.find('.is_docs_submitted').val() == '1') {
                    $('#is_docs_submitted').prop('checked', true);
                    $('#documents_container').show();
                } else {
                    $('#is_docs_submitted').prop('checked', false);
                    $('#documents_container').hide();
                }

                function setLink(id, filepath) {
                    if (filepath && filepath !== '') {
                        $('#' + id).html('<a href="{{ asset('') }}' + filepath +
                            '" target="_blank" class="text-primary small ms-2">(View)</a>');
                    } else {
                        $('#' + id).html('');
                    }
                }

                setLink('link_degree', card.find('.doc_degree').val());
                setLink('link_certificate', card.find('.doc_certificate').val());
                setLink('link_hsc_marksheet', card.find('.doc_hsc_marksheet').val());
                setLink('link_ssc_marksheet', card.find('.doc_ssc_marksheet').val());
                setLink('link_cv', card.find('.doc_cv').val());


                // Load casual leave days (badges)
                const leaveDays = card.find('.casual_leave_dates').val();
                $('#casual_leave_days_container .casual-day-option').removeClass('selected');
                if (leaveDays) {
                    const daysArray = leaveDays.split(',').filter(d => d.trim() !== '');
                    daysArray.forEach(function(day) {
                        $('#casual_leave_days_container .casual-day-option[data-value="' + day +
                            '"]').addClass('selected');
                    });
                    $('#casual_leave_days').val(leaveDays);
                    console.log('Loaded leave days:', daysArray);
                }

                $('#modalLabel').html('<i class="fa fa-pen"></i><span>Edit Employee</span>');
                $('#employeeModal').modal('show');
            });

            // Toggle documents
            $('#is_docs_submitted').change(function() {
                $(this).is(':checked') ? $('#documents_container').slideDown() : $('#documents_container')
                    .slideUp();
            });

            // Delete Employee
            $(document).on('click', '.delete-btn', function() {
                var url = $(this).data('url');
                Swal.fire({
                    title: 'Delete Employee?',
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

            // Unified Filtering Function
            function filterEmployees() {
                var searchText = $('#empSearch').val().toLowerCase();
                var selectedDept = $('#deptFilter').val();
                var selectedDesig = $('#desigFilter').val();
                var selectedStatus = $('#statusFilter').val();
                var activeToggle = $('.btn-group .active').data('filter');

                $('.hr-item-card').each(function() {
                    var card = $(this);

                    // Safe Data Retrieval
                    var name = (card.data('name') || '').toString().toLowerCase();
                    var email = (card.data('email') || '').toString().toLowerCase();
                    var dept = (card.data('dept') || '').toString().toLowerCase();
                    var desig = (card.find('.designation_id').val() || '').toString()
                        .toLowerCase(); // Using hidden input value if data attr missing? No, let's use what we have.
                    // Actually, I need to ensure data-attributes are present. I added hidden inputs earlier, but let's assume I can match by text content or similar if data attr missing. 
                    // WAIT, I haven't restored data-attributes on the card container yet! I need to do that too.
                    // Let's use the hidden inputs I verified are present in Step 986 (lines 117, 118, 121)
                    var deptId = card.find('.department_id').val();
                    var desigId = card.find('.designation_id')
                        .val(); // ID matching might be safer if filter values were IDs. But filter values are names (slugs).
                    // Let's stick to text matching for now as per my previous implementation, but I need to make sure the dropdown values match what's on the card.
                    // The dropdowns use `strtolower($name)`. The card text is visible.

                    // Let's rely on text content for now as it's more robust without data-attr if I missed one.
                    var deptText = card.find('.hr-tag:has(.fa-building)').text().trim().toLowerCase();
                    var desigText = card.find('.hr-tag:has(.fa-briefcase)').text().trim().toLowerCase();
                    var statusText = card.find('.hr-tag:last').text().trim().toLowerCase();

                    var isCustom = card.find('.custom_start_time').val() ? true : false;
                    var isDefault = !isCustom; // Simplified assumption for toggle

                    // 1. Text Search Check
                    var matchSearch = (name.indexOf(searchText) !== -1 || email.indexOf(searchText) !== -
                        1 || deptText.indexOf(searchText) !== -1);

                    // 2. Dropdown Filters Check
                    var matchDept = (selectedDept === 'all' || deptText === selectedDept || deptText
                        .includes(selectedDept)); // looser matching
                    var matchDesig = (selectedDesig === 'all' || desigText === selectedDesig || desigText
                        .includes(selectedDesig));
                    var matchStatus = (selectedStatus === 'all' || statusText === selectedStatus);

                    // 3. Toggle Buttons Check
                    var matchToggle = true;
                    if (activeToggle === 'custom_time') matchToggle = isCustom;
                    if (activeToggle === 'default_shift') matchToggle = isDefault;

                    // Final Decision
                    if (matchSearch && matchDept && matchDesig && matchStatus && matchToggle) {
                        card.show();
                    } else {
                        card.hide();
                    }
                });
                $('#empCount').text($('.hr-item-card:visible').length + ' employees');
            }

            // Event Listeners
            $('#empSearch').on('input', filterEmployees);
            $('#deptFilter, #desigFilter, #statusFilter').on('change', filterEmployees);

            $('[data-filter]').click(function() {
                $(this).addClass('active').siblings().removeClass('active');
                filterEmployees();
            });

            // Refresh
            $('#refreshBtn').click(() => location.reload());

            // Password Toggle Show/Hide
            $(document).on('click', '.toggle-password', function() {
                var targetId = $(this).data('target');
                var input = $('#' + targetId);
                var icon = $(this).find('i');

                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });

            // --- Face Registration Logic ---
            let faceStream = null;

            // Open Modal
            $(document).on('click', '.register-face-btn', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                $('#face_employee_id').val(id);
                $('#faceModalLabel').text('Register Face: ' + name);

                $('#faceModal').modal('show');
                startFaceCamera();
            });

            // Start Camera
            // Start Camera
            async function startFaceCamera() {
                try {
                    if (typeof faceapi === 'undefined') {
                        throw new Error("Face API not loaded");
                    }

                    // Check if models are loaded (check params on loaded nets)
                    if (!faceapi.nets.tinyFaceDetector.params) {
                        $('#face_status').html(
                            '<div class="d-inline-block px-3 py-1 rounded-pill bg-info bg-opacity-10 text-info border border-info border-opacity-25"><i class="fa fa-spinner fa-spin me-1"></i> Loading AI Models...</div>'
                        );
                        const MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
                        await Promise.all([
                            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                        ]);
                    }

                    if (faceStream) {
                        faceStream.getTracks().forEach(track => track.stop());
                    }

                    // Request Camera
                    faceStream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            width: 640,
                            height: 480,
                            facingMode: 'user'
                        }
                    });

                    const videoEl = document.getElementById('face-video');
                    if (videoEl) {
                        videoEl.srcObject = faceStream;
                        $('#face_status').html(
                            '<div class="d-inline-block px-3 py-1 rounded-pill bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25"><i class="fa fa-info-circle me-1"></i> Ready. Please look at the camera.</div>'
                        );
                        $('#btn-capture-face').prop('disabled', false);
                    }

                } catch (err) {
                    let msg = err.message;
                    if (msg.includes('Permission denied')) msg = "Camera permission denied.";

                    $('#face_status').html(
                        '<div class="d-inline-block px-3 py-1 rounded-pill bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="fa fa-exclamation-circle me-1"></i> Error: ' +
                        msg + '</div>');
                    console.error(err);
                }
            }

            // Capture & Save
            $('#btn-capture-face').off('click').on('click', async function() {
                const btn = $(this);
                const videoEl = document.getElementById('face-video');
                const canvasEl = document.getElementById('face-canvas');

                if (!videoEl || !canvasEl) return;

                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
                $('#face_status').html(
                    '<div class="d-inline-block px-3 py-1 rounded-pill bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25"><i class="fa fa-search me-1"></i> Detecting face...</div>'
                );

                try {
                    // Detect Face
                    const detections = await faceapi.detectSingleFace(videoEl, new faceapi
                            .TinyFaceDetectorOptions())
                        .withFaceLandmarks()
                        .withFaceDescriptor();

                    if (!detections) {
                        $('#face_status').html(
                            '<div class="d-inline-block px-3 py-1 rounded-pill bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="fa fa-times-circle me-1"></i> No face detected! Please try again.</div>'
                        );
                        btn.prop('disabled', false).html('<i class="fa fa-camera"></i> Capture & Save');
                        return;
                    }

                    // Capture Image
                    const context = canvasEl.getContext('2d');
                    canvasEl.width = videoEl.videoWidth;
                    canvasEl.height = videoEl.videoHeight;
                    context.drawImage(videoEl, 0, 0);
                    const image = canvasEl.toDataURL('image/jpeg');

                    // Send to Server
                    $.ajax({
                        url: '{{ route('hr.employees.face-register') }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            employee_id: $('#face_employee_id').val(),
                            descriptor: Array.from(detections
                                .descriptor), // Send array directly, Laravel casts to array
                            image: image
                        },
                        success: function(res) {
                            $('#face_status').html(
                                '<div class="d-inline-block px-3 py-1 rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="fa fa-check-circle me-1"></i> ' +
                                res.success + '</div>');
                            setTimeout(() => {
                                $('#faceModal').modal('hide');
                                location.reload();
                            }, 1000);
                        },
                        error: function(err) {
                            let msg = err.responseJSON && err.responseJSON.errors ? Object
                                .values(err.responseJSON.errors)[0][0] :
                                'Error saving face.';
                            $('#face_status').html(
                                '<div class="d-inline-block px-3 py-1 rounded-pill bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="fa fa-exclamation-circle me-1"></i> ' +
                                msg + '</div>');
                            btn.prop('disabled', false).html(
                                '<i class="fa fa-camera"></i> Capture & Save');
                        }
                    });
                } catch (err) {
                    console.error(err);
                    $('#face_status').html(
                        '<div class="d-inline-block px-3 py-1 rounded-pill bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="fa fa-exclamation-circle me-1"></i> Detection Error: ' +
                        err.message + '</div>');
                    btn.prop('disabled', false).html(
                        '<i class="fa fa-camera"></i> Capture & Save');
                }
            });

            // Stop Camera on Close
            $('#faceModal').on('hidden.bs.modal', function() {
                if (faceStream) {
                    faceStream.getTracks().forEach(track => track.stop());
                    faceStream = null; // Clear stream reference
                }
                $('#face_status').empty();
                $('#btn-capture-face').prop('disabled', true).html(
                    '<i class="fa fa-camera"></i> Capture & Save');
            });

            // Custom submit handler removed - using data-ajax-validate
        });
    </script>



    <!-- Face Registration Modal -->
    <div class="modal fade" id="faceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="faceModalLabel">Register Face</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <input type="hidden" id="face_employee_id">
                    <div
                        style="position: relative; width: 100%; border-radius: 8px; overflow: hidden; background: #000; margin-bottom: 15px;">
                        <video id="face-video" autoplay playsinline style="width: 100%; display: block;"></video>
                        <canvas id="face-canvas" style="display: none;"></canvas>
                        <div
                            style="position: absolute; top:50%; left:50%; transform: translate(-50%, -50%); width: 220px; height: 280px; border: 3px dashed rgba(255,255,255,0.7); border-radius: 50%; pointer-events: none;">
                        </div>
                    </div>
                    <div class="d-flex justify-content-center align-items-center mb-3" style="min-height: 30px;">
                        <div id="status-indicator" class="status-dot"></div>
                    </div>
                    <style>
                        .status-dot {
                            width: 16px;
                            height: 16px;
                            border-radius: 50%;
                            background: #e9ecef;
                            transition: all 0.3s ease;
                        }

                        .status-dot.yellow {
                            background: #ffc107;
                            box-shadow: 0 0 12px #ffc107;
                            animation: pulse-dot 1.5s infinite;
                        }

                        .status-dot.green {
                            background: #198754;
                            box-shadow: 0 0 12px #198754;
                            transform: scale(1.1);
                        }

                        .status-dot.red {
                            background: #dc3545;
                            box-shadow: 0 0 12px #dc3545;
                            animation: shake-dot 0.4s;
                        }

                        @keyframes pulse-dot {
                            0% {
                                opacity: 0.5;
                                transform: scale(0.8);
                            }

                            50% {
                                opacity: 1;
                                transform: scale(1.2);
                            }

                            100% {
                                opacity: 0.5;
                                transform: scale(0.8);
                            }
                        }

                        @keyframes shake-dot {

                            0%,
                            100% {
                                transform: translateX(0);
                            }

                            25% {
                                transform: translateX(-4px);
                            }

                            75% {
                                transform: translateX(4px);
                            }
                        }
                    </style>
                    <button type="button" class="btn btn-primary w-100" id="btn-capture-face" disabled>
                        <i class="fa fa-camera"></i> Capture & Save
                    </button>
                    <small class="text-muted mt-2 d-block">Wait for camera to load, then ensure only one face is
                        visible.</small>
                </div>
            </div>
        </div>
    </div>
    <!-- Isolated Face Logic -->
    <script>
        $(document).ready(function() {
            // Unbind any previous handlers
            $(document).off('click', '.register-face-btn');
            $('#btn-capture-face').off('click');
            $('#faceModal').off('hidden.bs.modal');

            // Global variables
            let faceStream = null;
            let isModelsLoaded = false;

            // UI Helper
            function setStatus(state) {
                const indicator = $('#status-indicator');
                indicator.removeClass('yellow green red');

                if (state === 'loading' || state === 'detecting') {
                    indicator.addClass('yellow');
                } else if (state === 'ready' || state === 'success') {
                    indicator.addClass('green');
                } else if (state === 'error') {
                    indicator.addClass('red');
                }
            }

            // Preload Models
            const MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
            (async function loadModels() {
                try {
                    setStatus('loading');
                    await Promise.all([
                        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                    ]);
                    isModelsLoaded = true;
                    console.log("FaceAPI Models Pre-loaded");
                    setStatus('ready'); // Or reset
                    setTimeout(() => setStatus('reset'), 1000); // Hide after load
                } catch (err) {
                    console.error("Failed to load FaceAPI models", err);
                    setStatus('error');
                }
            })();

            // Open Modal
            $(document).on('click', '.register-face-btn', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const name = $(this).data('name');

                $('#face_employee_id').val(id);
                $('#faceModalLabel').text('Register Face: ' + name);

                setStatus('loading');
                $('#faceModal').modal('show');

                startFaceCamera();
            });

            // Start Camera
            window.startFaceCamera = async function() {
                try {
                    setStatus('loading');
                    if (typeof faceapi === 'undefined') {
                        throw new Error("Face API library not loaded!");
                    }

                    // Check if models are loaded
                    if (!isModelsLoaded) {
                        await Promise.all([
                            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                        ]);
                        isModelsLoaded = true;
                    }

                    if (faceStream) {
                        faceStream.getTracks().forEach(track => track.stop());
                    }

                    faceStream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            width: 640,
                            height: 480,
                            facingMode: 'user'
                        }
                    });

                    const videoEl = document.getElementById('face-video');
                    if (videoEl) {
                        videoEl.srcObject = faceStream;
                        setStatus('ready');
                        $('#btn-capture-face').prop('disabled', false);
                    }

                } catch (err) {
                    let msg = err.message;
                    if (msg.includes('Permission denied')) msg = "Camera permission denied.";

                    $('#face_status').html(
                        '<div class="d-inline-block px-3 py-1 rounded-pill bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="fa fa-exclamation-circle me-1"></i> Error: ' +
                        msg + '</div>');
                    setStatus('error');
                    console.error(err);
                }
            };

            // Capture & Save
            $('#btn-capture-face').on('click', async function() {
                const btn = $(this);
                const videoEl = document.getElementById('face-video');
                const canvasEl = document.getElementById('face-canvas');

                if (!videoEl || !canvasEl) return;

                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
                setStatus('detecting');

                try {
                    const detections = await faceapi.detectSingleFace(videoEl, new faceapi
                            .TinyFaceDetectorOptions())
                        .withFaceLandmarks()
                        .withFaceDescriptor();

                    if (!detections) {
                        $('#face_status').html(
                            '<div class="d-inline-block px-3 py-1 rounded-pill bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="fa fa-times-circle me-1"></i> No face detected! Please try again.</div>'
                        );
                        setStatus('error');
                        // Optional: Shake effect or red flash?
                        setTimeout(() => setStatus('ready'), 1000);

                        btn.prop('disabled', false).html('<i class="fa fa-camera"></i> Capture & Save');
                        return;
                    }

                    const context = canvasEl.getContext('2d');
                    canvasEl.width = videoEl.videoWidth;
                    canvasEl.height = videoEl.videoHeight;
                    context.drawImage(videoEl, 0, 0);
                    const image = canvasEl.toDataURL('image/jpeg');

                    $.ajax({
                        url: '{{ route('hr.employees.face-register') }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            employee_id: $('#face_employee_id').val(),
                            descriptor: Array.from(detections
                                .descriptor),
                            image: image
                        },
                        success: function(res) {
                            $('#face_status').html(
                                '<div class="d-inline-block px-3 py-1 rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="fa fa-check-circle me-1"></i> ' +
                                res.success + '</div>');
                            setStatus('success');
                            btn.html('<i class="fa fa-check"></i> Saved');
                            setTimeout(() => {
                                $('#faceModal').modal('hide');
                                location.reload();
                            }, 1000);
                        },
                        error: function(err) {
                            let msg = err.responseJSON && err.responseJSON.errors ? Object
                                .values(err.responseJSON.errors)[0][0] :
                                'Error saving face.';
                            $('#face_status').html(
                                '<div class="d-inline-block px-3 py-1 rounded-pill bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="fa fa-exclamation-circle me-1"></i> ' +
                                msg + '</div>');
                            setStatus('error');
                            btn.prop('disabled', false).html(
                                '<i class="fa fa-camera"></i> Capture & Save');
                        }
                    });
                } catch (err) {
                    console.error(err);
                    $('#face_status').html(
                        '<div class="d-inline-block px-3 py-1 rounded-pill bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="fa fa-exclamation-circle me-1"></i> Detection Error: ' +
                        err.message + '</div>');
                    setStatus('error');
                    btn.prop('disabled', false).html(
                        '<i class="fa fa-camera"></i> Capture & Save');
                }
            });

            // Stop Camera on Close
            document.getElementById('faceModal').addEventListener('hidden.bs.modal', function() {
                if (faceStream) {
                    faceStream.getTracks().forEach(track => track.stop());
                    faceStream = null;
                }
                setStatus('reset');
                $('#face_status').empty();
                $('#btn-capture-face').prop('disabled', true).html(
                    '<i class="fa fa-camera"></i> Capture & Save');
            });
        });
    </script>
@endsection
