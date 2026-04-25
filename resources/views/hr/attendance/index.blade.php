@extends('admin_panel.layout.app')

@section('content')
    @include('hr.partials.hr-styles')

    <style>
        .attendance-card {
            background: var(--hr-card);
            border: 1px solid var(--hr-border);
            border-radius: 14px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            transition: all 0.2s;
        }

        .attendance-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        .attendance-card.present {
            border-left: 4px solid #22c55e;
        }

        .attendance-card.absent {
            border-left: 4px solid #ef4444;
        }

        .attendance-card.late {
            border-left: 4px solid #f59e0b;
        }

        .attendance-card.leave {
            border-left: 4px solid #3b82f6;
        }

        .time-input-group {
            background: #f8fafc;
            border: 1px solid var(--hr-border);
            border-radius: 10px;
            padding: 12px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .time-label {
            font-size: 0.75rem;
            color: var(--hr-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
            display: block;
        }

        .time-field {
            border: 1px solid var(--hr-border);
            border-radius: 6px;
            padding: 6px;
            width: 100%;
            font-size: 0.9rem;
        }

        .location-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.75rem;
            color: var(--hr-muted);
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 4px;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .status-select {
            width: 100%;
            padding: 8px;
            border-radius: 8px;
            border: 1px solid var(--hr-border);
            background: white;
            font-weight: 500;
        }

        .shift-info {
            font-size: 0.8rem;
            color: var(--hr-muted);
            background: #f8fafc;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 4px;
        }

        .save-bar {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--hr-bg);
            padding: 12px 24px;
            border-radius: 50px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            z-index: 100;
            display: none;
            border: 1px solid var(--hr-border);
            align-items: center;
            gap: 16px;
        }

        .save-bar.visible {
            display: flex;
            animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                transform: translate(-50%, 100%);
                opacity: 0;
            }

            to {
                transform: translate(-50%, 0);
                opacity: 1;
            }
        }

        .holiday-banner {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
        }

        /* Punch Gap Timer Styles */
        .punch-timer {
            background: linear-gradient(135deg, #667eea15, #764ba215);
            border: 1px solid #667eea30;
            border-radius: 8px;
            padding: 8px 12px;
            text-align: center;
            margin-top: 8px;
        }

        .punch-timer.waiting {
            background: linear-gradient(135deg, #f59e0b15, #d9770615);
            border-color: #f59e0b50;
        }

        .punch-timer.ready {
            background: linear-gradient(135deg, #22c55e15, #16a34a15);
            border-color: #22c55e50;
        }

        .timer-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--hr-muted);
            margin-bottom: 2px;
        }

        .timer-countdown {
            font-size: 1.1rem;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            color: #667eea;
        }

        .timer-countdown.waiting {
            color: #f59e0b;
        }

        .timer-countdown.ready {
            color: #22c55e;
        }

        /* Status Dropdown Styling */
        .status-select {
            font-weight: 600 !important;
            padding: 4px 28px 4px 12px !important;
            border-radius: 20px !important;
            border: 1px solid transparent !important;
            cursor: pointer;
            transition: all 0.2s;
        }

        .status-select:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
        }

        .status-select.status-present {
            background-color: #dcfce7;
            color: #166534;
            border-color: #bbf7d0 !important;
        }

        .status-select.status-absent {
            background-color: #fee2e2;
            color: #991b1b;
            border-color: #fecaca !important;
        }

        .status-select.status-late {
            background-color: #fef3c7;
            color: #92400e;
            border-color: #fde68a !important;
        }

        .status-select.status-leave {
            background-color: #e0f2fe;
            color: #075985;
            border-color: #bae6fd !important;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="page-title"><i class="fa fa-clock"></i> Daily Attendance</h1>
                        <p class="page-subtitle">{{ \Carbon\Carbon::parse($selectedDate)->format('l, F d, Y') }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light border" data-bs-toggle="modal"
                            data-bs-target="#attendanceGuideModal">
                            <i class="fa fa-question-circle text-primary me-1"></i> System Guide
                        </button>
                        @can('hr.attendance.create')
                            <button type="button" class="btn btn-warning" id="markAbsentBtn">
                                <i class="fa fa-user-times me-1"></i> Mark Absent
                            </button>
                        @endcan
                        @can('hr.biometric.devices.edit')
                            <button type="button" class="btn btn-info" id="pullAttendanceBtn">
                                <i class="fa fa-sync me-1"></i> Pull Attendance
                            </button>
                        @endcan
                        @can('hr.attendance.create')
                            <a href="{{ route('hr.attendance.kiosk') }}" class="btn btn-outline-primary">
                                <i class="fa fa-desktop me-2"></i> Kiosk Mode
                            </a>
                        @endcan
                    </div>
                </div>

                <!-- Guidance Modal -->
                <div class="modal fade" id="attendanceGuideModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header gradient text-white">
                                <h5 class="modal-title font-weight-bold"><i class="fa fa-info-circle me-2"></i> HR
                                    Attendance Guide</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold text-primary mb-3"><i class="fa fa-microchip me-2"></i>
                                            Attendance Sources</h6>
                                        <ul class="list-unstyled">
                                            <li class="mb-2"><span class="badge bg-info me-2">Biometric Device</span>
                                                Pulled directly from fingerprint terminals.</li>
                                            <li class="mb-2"><span class="badge bg-warning text-dark me-2">Manual
                                                    (HR)</span> Entries created or modified by HR staff.</li>
                                            <li class="mb-2"><span class="badge bg-primary me-2">Kiosk / Web</span> Marked
                                                via Face recognition or User Panel.</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="fw-bold text-primary mb-3"><i class="fa fa-calculator me-2"></i>
                                            Calculation Logic</h6>
                                        <ul class="list-unstyled small">
                                            <li class="mb-2"><strong>Late Arrival:</strong> Automatically flagged if
                                                check-in is after the shift start time + grace minutes.</li>
                                            <li class="mb-2"><strong>Early Leave:</strong> Flagged if check-out is before
                                                the scheduled shift end time.</li>
                                            <li class="mb-2"><strong>Total Hours:</strong> Calculated precisely based on
                                                actual check-in and check-out times.</li>
                                        </ul>
                                    </div>
                                    <div class="col-12 mt-4 pt-3 border-top">
                                        <h6 class="fw-bold text-success mb-2"><i class="fa fa-lightbulb me-2"></i> Pro-Tips
                                            for HR</h6>
                                        <div class="p-3 bg-light rounded-3 small">
                                            <ul class="mb-0">
                                                <li><strong>Manual Override:</strong> Changing a status or time will
                                                    highlight the card in blue. Only these "dirty" cards are updated when
                                                    you click "Save Now".</li>
                                                <li><strong>Bulk Absent:</strong> Use the "Mark Absent" button to identify
                                                    all active employees who haven't checked in by today's cutoff.</li>
                                                <li><strong>Punch Gap:</strong> If an employee punches out too soon after
                                                    punching in, the system ignores it to prevent duplicates (Configure this
                                                    in Biometric Settings).</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Holiday Alert -->
                @if ($isHoliday)
                    <div class="holiday-banner">
                        <i class="fa fa-calendar-star fa-lg"></i>
                        <div>
                            <div style="font-size: 0.9rem; opacity: 0.9;">Today is a Holiday</div>
                            <div style="font-size: 1.1rem; font-weight: 700;">{{ $holiday->name }}</div>
                        </div>
                    </div>
                @endif

                <!-- Stats Row -->
                <div class="stats-row">
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="fa fa-check"></i></div>
                        <div class="stat-value">{{ $summary['present'] }}</div>
                        <div class="stat-label">Present</div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-icon"><i class="fa fa-times"></i></div>
                        <div class="stat-value">{{ $summary['absent'] }}</div>
                        <div class="stat-label">Absent</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fa fa-exclamation-triangle"></i></div>
                        <div class="stat-value">{{ $summary['late'] }}</div>
                        <div class="stat-label">Late</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="fa fa-umbrella-beach"></i></div>
                        <div class="stat-value">{{ $summary['leave'] }}</div>
                        <div class="stat-label">On Leave</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-white">
                    <form id="filterForm" method="GET" action="{{ route('hr.attendance.index') }}"
                        class="d-flex flex-wrap gap-3 align-items-end">
                        <div style="flex: 1; min-width: 200px;">
                            <label class="form-label text-muted small fw-bold">DATE</label>
                            <input type="date" name="date" class="form-control" value="{{ $selectedDate }}">
                        </div>
                        <div style="flex: 1; min-width: 200px;">
                            <label class="form-label text-muted small fw-bold">DEPARTMENT</label>
                            <select name="department_id" class="form-select">
                                <option value="">All Departments</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        {{ $selectedDepartment == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div style="flex: 1; min-width: 200px;">
                            <label class="form-label text-muted small fw-bold">DESIGNATION</label>
                            <select name="designation_id" class="form-select">
                                <option value="">All Designations</option>
                                @foreach ($designations as $desig)
                                    <option value="{{ $desig->id }}"
                                        {{ $selectedDesignation == $desig->id ? 'selected' : '' }}>
                                        {{ $desig->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div style="flex: 1; min-width: 150px;">
                            <label class="form-label text-muted small fw-bold">STATUS</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="present" {{ $selectedStatus == 'present' ? 'selected' : '' }}>Present
                                </option>
                                <option value="absent" {{ $selectedStatus == 'absent' ? 'selected' : '' }}>Absent</option>
                                <option value="late" {{ $selectedStatus == 'late' ? 'selected' : '' }}>Late</option>
                                <option value="leave" {{ $selectedStatus == 'leave' ? 'selected' : '' }}>Leave</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary"><i class="fa fa-filter me-1"></i>
                                Apply</button>
                            <a href="{{ route('hr.attendance.index') }}" class="btn btn-light border"><i
                                    class="fa fa-sync"></i></a>
                        </div>
                    </form>
                </div>

                <!-- Attendance Grid -->
                <form id="attendanceForm" action="{{ route('hr.attendance.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="date" value="{{ $selectedDate }}">

                    <div class="hr-grid">
                        @forelse ($employees as $emp)
                            @php
                                $attendance = $emp->attendances->first();
                                $approvedLeave = $emp->leaves->first(); // Get approved leave for this date
                                $status = $attendance->status ?? 'absent';

                                // If employee has approved leave and no attendance record, set status to leave
                                if ($approvedLeave && !$attendance) {
                                    $status = 'leave';
                                }

                                // Override status if late but still showing present
                                if ($attendance && $attendance->status == 'present' && $attendance->is_late) {
                                    $status = 'late';
                                }

                                if (!$attendance && $isHoliday) {
                                    $status = 'holiday';
                                }

                                // Determine Shift Display
                                $isCustomShift = !empty($emp->custom_start_time) && !empty($emp->custom_end_time);

                                if ($isCustomShift) {
                                    $shiftName = 'Custom Timing';
                                    $shiftTime =
                                        \Carbon\Carbon::parse($emp->custom_start_time)->format('h:i A') .
                                        ' - ' .
                                        \Carbon\Carbon::parse($emp->custom_end_time)->format('h:i A');
                                } elseif ($emp->shift) {
                                    $shiftName = $emp->shift->name;
                                    $shiftTime =
                                        \Carbon\Carbon::parse($emp->shift->start_time)->format('h:i A') .
                                        ' - ' .
                                        \Carbon\Carbon::parse($emp->shift->end_time)->format('h:i A');
                                } else {
                                    $shiftName = 'Default';
                                    $shiftTime = '09:00 AM - 05:00 PM';
                                }
                            @endphp

                            <div class="attendance-card {{ $status }}">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="hr-avatar" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                        {{ strtoupper(substr($emp->first_name, 0, 1) . substr($emp->last_name, 0, 1)) }}
                                    </div>
                                    <div class="hr-item-info ms-3">
                                        <h4 class="hr-item-name">{{ $emp->full_name }}</h4>

                                        {{-- Display Leave Info if exists --}}
                                        @if ($approvedLeave)
                                            <div class="mb-2">
                                                <span class="badge bg-info" style="font-size: 0.75rem;">
                                                    <i class="fa fa-umbrella-beach me-1"></i>
                                                    {{ ucfirst($approvedLeave->leave_type) }} Leave
                                                    ({{ \Carbon\Carbon::parse($approvedLeave->start_date)->format('D') }})
                                                </span>
                                            </div>
                                        @endif

                                        <div class="shift-info">
                                            @if ($isCustomShift)
                                                <span class="badge bg-warning text-dark me-1"
                                                    style="font-size: 0.7rem;">CUSTOM</span>
                                            @endif
                                            <!-- Status Dropdown -->
                                            <input type="hidden" name="attendance[{{ $emp->id }}][is_dirty]"
                                                value="0" class="dirty-marker">

                                            @php
                                                $statusClass = match ($status) {
                                                    'present' => 'status-present',
                                                    'absent' => 'status-absent',
                                                    'late' => 'status-late',
                                                    'leave' => 'status-leave',
                                                    default => 'status-absent',
                                                };
                                            @endphp

                                            <select name="attendance[{{ $emp->id }}][status]"
                                                class="form-select form-select-sm d-inline-block w-auto mt-1 status-select {{ $statusClass }}"
                                                onchange="showSaveBar(this)" style="font-size: 0.8rem;">
                                                <option value="present"
                                                    {{ ($attendance && $attendance->status == 'present' && !$attendance->is_late) || (!$attendance && $status == 'present') ? 'selected' : '' }}>
                                                    Present</option>
                                                <option value="absent"
                                                    {{ ($attendance && $attendance->status == 'absent') || (!$attendance && $status == 'absent') ? 'selected' : '' }}>
                                                    Absent</option>
                                                <option value="late"
                                                    {{ ($attendance && ($attendance->status == 'late' || ($attendance->status == 'present' && $attendance->is_late))) || (!$attendance && $status == 'late') ? 'selected' : '' }}>
                                                    Late</option>
                                                <option value="leave"
                                                    {{ ($attendance && $attendance->status == 'leave') || (!$attendance && $status == 'leave') ? 'selected' : '' }}>
                                                    Leave</option>
                                            </select>
                                            <i class="fa fa-clock me-1"></i> {{ $shiftTime }}
                                        </div>
                                        <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                                            {{ $shiftName }}
                                        </div>
                                    </div>
                                </div>

                                <div class="time-input-group">
                                    <div>
                                        <label class="time-label">Check In</label>
                                        <input type="time" name="attendance[{{ $emp->id }}][clock_in]"
                                            class="time-field" onchange="showSaveBar(this)"
                                            {{ $status == 'absent' || $status == 'leave' ? 'disabled style=opacity:0.5;background-color:#f1f5f9' : '' }}
                                            value="{{ $attendance && $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') : '' }}">

                                        @if ($attendance && $attendance->check_in_time)
                                            @if ($attendance->is_late)
                                                @php
                                                    $lHrs = floor($attendance->late_minutes / 60);
                                                    $lMins = $attendance->late_minutes % 60;
                                                    $lateText = ($lHrs > 0 ? $lHrs . 'h ' : '') . $lMins . 'm';
                                                @endphp
                                                <small class="text-warning d-block mt-1"><i
                                                        class="fa fa-exclamation-circle"></i> Late
                                                    {{ $lateText }}</small>
                                            @endif
                                            @if ($attendance->is_early_in)
                                                @php
                                                    $eHrs = floor($attendance->early_in_minutes / 60);
                                                    $eMins = $attendance->early_in_minutes % 60;
                                                    $earlyInText = ($eHrs > 0 ? $eHrs . 'h ' : '') . $eMins . 'm';
                                                @endphp
                                                <small class="text-success d-block mt-1"><i class="fa fa-clock"></i> Early
                                                    In {{ $earlyInText }}</small>
                                            @endif
                                            @if ($attendance->check_in_location)
                                                <div class="location-badge mt-1"
                                                    title="Source: {{ $attendance->check_in_location }}">
                                                    <i class="fa fa-map-marker-alt"></i>
                                                    {{ $attendance->check_in_location }}
                                                </div>
                                            @endif

                                            {{-- Punch Gap Timer --}}
                                            @if (!$attendance->check_out_time)
                                                @php
                                                    $punchGap = \App\Models\Hr\HrSetting::getPunchGapMinutes();
                                                    $checkInTime = \Carbon\Carbon::parse($attendance->check_in_time);
                                                    $validCheckOutTime = $checkInTime->copy()->addMinutes($punchGap);
                                                    $now = \Carbon\Carbon::now();
                                                    $remainingSeconds = $now->lt($validCheckOutTime)
                                                        ? $now->diffInSeconds($validCheckOutTime)
                                                        : 0;
                                                    $isReady = $remainingSeconds <= 0;
                                                @endphp
                                                <div class="punch-timer {{ $isReady ? 'ready' : 'waiting' }} mt-2">
                                                    <div class="timer-label">
                                                        <i class="fa fa-stopwatch me-1"></i>
                                                        {{ $isReady ? 'Check-out Ready' : 'Check-out In' }}
                                                    </div>
                                                    <div class="timer-countdown {{ $isReady ? 'ready' : 'waiting' }}"
                                                        data-remaining="{{ $remainingSeconds }}">
                                                        {{ $isReady ? 'Ready' : gmdate('H:i:s', $remainingSeconds) }}
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    </div>

                                    <div>
                                        <label class="time-label">Check Out</label>
                                        <input type="time" name="attendance[{{ $emp->id }}][clock_out]"
                                            class="time-field" onchange="showSaveBar(this)"
                                            {{ $status == 'absent' || $status == 'leave' ? 'disabled style=opacity:0.5;background-color:#f1f5f9' : '' }}
                                            value="{{ $attendance && $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i') : '' }}">

                                        @if ($attendance && $attendance->check_out_time)
                                            @if ($attendance->is_early_leave)
                                                @php
                                                    $hrs = floor($attendance->early_leave_minutes / 60);
                                                    $mins = $attendance->early_leave_minutes % 60;
                                                    $earlyText = ($hrs > 0 ? $hrs . 'h ' : '') . $mins . 'm';
                                                @endphp
                                                <small class="text-info d-block mt-1"><i
                                                        class="fa fa-person-walking-arrow-right"></i>
                                                    Early Leave {{ $earlyText }}</small>
                                            @endif
                                            @if ($attendance->check_out_location)
                                                <div class="location-badge mt-1"
                                                    title="Source: {{ $attendance->check_out_location }}">
                                                    <i class="fa fa-map-marker-alt"></i>
                                                    {{ $attendance->check_out_location }}
                                                </div>
                                            @endif
                                            @if ($attendance->total_hours)
                                                <small class="text-muted d-block mt-1">
                                                    <i class="fa fa-hourglass-half"></i>
                                                    {{ number_format($attendance->total_hours, 2) }}
                                                    Hrs
                                                </small>
                                            @endif
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-auto">
                                    <label class="time-label">Status</label>
                                    @if ($attendance)
                                        <div class="d-flex justify-content-between align-items-center">
                                            @php
                                                $displayStatus = $attendance->status;
                                                $tagClass = 'warning';
                                                if ($displayStatus == 'present') {
                                                    if ($attendance->is_late) {
                                                        $displayStatus = 'late';
                                                        $tagClass = 'warning';
                                                    } else {
                                                        $tagClass = 'success';
                                                    }
                                                } elseif ($displayStatus == 'absent') {
                                                    $tagClass = 'danger';
                                                } elseif ($displayStatus == 'leave') {
                                                    $tagClass = 'info';
                                                }
                                            @endphp
                                            <span class="hr-tag {{ $tagClass }}">
                                                {{ ucfirst($displayStatus) }}
                                            </span>
                                        </div>
                                    @else
                                        <select name="attendance[{{ $emp->id }}][status]" class="status-select"
                                            onchange="showSaveBar(this)">
                                            <option value="">-- Mark Status --</option>
                                            <option value="present">Present</option>
                                            <option value="absent">Absent</option>
                                            <option value="late">Late</option>
                                            <option value="leave">Leave</option>
                                        </select>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="col-12 py-5 text-center">
                                <i class="fa fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No employees found matching the filters.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $employees->links() }}
                    </div>

                    <!-- Floating Save Bar -->
                    @can('hr.attendance.create')
                        <div class="save-bar" id="saveBar">
                            <span class="fw-bold text-dark">Unsaved changes detected</span>
                            <button type="submit" class="btn btn-save shadow-sm">
                                <i class="fa fa-check me-2"></i> Save Now
                            </button>
                        </div>
                    @endcan
                </form>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function showSaveBar(element) {

            let bar = document.getElementById('saveBar');
            if (bar) bar.classList.add('visible');

            // Mark card as dirty and update UI
            if (element) {
                let card = element.closest('.attendance-card');
                if (card) {
                    // Mark as dirty
                    let dirtyMarker = card.querySelector('.dirty-marker');
                    if (dirtyMarker) dirtyMarker.value = '1';

                    // Highlight the card being edited
                    card.style.borderColor = 'var(--hr-primary)';
                    card.style.boxShadow = '0 0 0 2px var(--hr-primary)20';

                    // If the change came from a status dropdown
                    if (element.name && element.name.includes('[status]')) {
                        let status = element.value;
                        let timeFields = card.querySelectorAll('.time-field');
                        let timeGroup = card.querySelector('.time-input-group');

                        // Update card appearance based on status
                        card.classList.remove('present', 'absent', 'late', 'leave');
                        if (status) card.classList.add(status);

                        // Update Select Color
                        element.classList.remove('status-present', 'status-absent', 'status-late', 'status-leave');
                        element.classList.add('status-' + status);

                        if (status === 'absent' || status === 'leave') {
                            timeFields.forEach(f => {
                                f.value = '';
                                f.disabled = true;
                                f.style.opacity = '0.5';
                                f.style.backgroundColor = '#f1f5f9';
                            });
                            if (timeGroup) timeGroup.style.opacity = '0.6';
                        } else {
                            timeFields.forEach(f => {
                                f.disabled = false;
                                f.style.opacity = '1';
                                f.style.backgroundColor = '';
                            });
                            if (timeGroup) timeGroup.style.opacity = '1';
                        }
                    }
                }
            }
        }

        // Live Countdown Timer
        function updateCountdowns() {
            document.querySelectorAll('.timer-countdown[data-remaining]').forEach(function(el) {
                let remaining = parseInt(el.dataset.remaining);
                if (remaining > 0) {
                    remaining--;
                    el.dataset.remaining = remaining;
                    let hours = Math.floor(remaining / 3600);
                    let minutes = Math.floor((remaining % 3600) / 60);
                    let seconds = remaining % 60;
                    el.textContent = String(hours).padStart(2, '0') + ':' +
                        String(minutes).padStart(2, '0') + ':' +
                        String(seconds).padStart(2, '0');
                    if (remaining <= 0) {
                        el.innerHTML = '<i class="fa fa-check-circle"></i> Ready';
                        el.classList.remove('waiting');
                        el.classList.add('ready');
                        let timerBox = el.closest('.punch-timer');
                        if (timerBox) {
                            timerBox.classList.remove('waiting');
                            timerBox.classList.add('ready');
                            timerBox.querySelector('.timer-label').innerHTML =
                                '<i class="fa fa-stopwatch me-1"></i> Check-out Ready';
                        }
                    }
                }
            });
        }
        setInterval(updateCountdowns, 1000);

        $(document).ready(function() {
            // Initialize state for absent/leave rows on load
            $('.attendance-card.absent, .attendance-card.leave').each(function() {
                let card = $(this);
                card.find('.time-field').prop('disabled', true).css({
                    'opacity': '0.5',
                    'backgroundColor': '#f1f5f9'
                });
                card.find('.time-input-group').css('opacity', '0.6');
            });

            // Form Submit Handler
            $('#attendanceForm').on('submit', function(e) {
                e.preventDefault();
                console.log('Form Submit Triggered');

                let form = $(this);
                let btn = form.find('.btn-save');
                let originalBtnContent = btn.html();

                // Debug Check
                // alert('Submitting Form... Check Console for Data');
                console.log('Serialized Data:', form.serialize());

                if (btn.length > 0) {
                    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');
                }

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        console.log('Success Response:', response);
                        Swal.fire({
                            title: 'Saved!',
                            text: response.success || 'Attendance updated.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            if (response.reload !== false) location.reload();
                        });
                    },
                    error: function(xhr) {
                        console.error('Error Response:', xhr);
                        let errorMessage = 'Failed to save.';
                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.errors) {
                                errorMessage = Object.values(xhr.responseJSON.errors).flat()
                                    .join('<br>');
                            } else if (xhr.responseJSON.error) {
                                errorMessage = xhr.responseJSON.error;
                            } else if (xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        if (btn.length > 0) {
                            btn.prop('disabled', false).html(originalBtnContent);
                        }
                    }
                });
            });

            // Pull Attendance Button
            $('#pullAttendanceBtn').click(function() {
                let btn = $(this);
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Pulling...');
                $.post("{{ route('hr.attendance.pull') }}", {
                        _token: "{{ csrf_token() }}"
                    })
                    .done(function(res) {
                        Swal.fire('Success', res.message, 'success').then(() => location.reload());
                    })
                    .fail(function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.error || 'Failed to pull.', 'error');
                        btn.prop('disabled', false).html(
                            '<i class="fa fa-sync me-1"></i> Pull Attendance');
                    });
            });

            // Mark Absent Button
            $('#markAbsentBtn').click(function() {
                Swal.fire({
                    title: 'Mark Absent?',
                    text: "Mark all employees without attendance as 'Absent'.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Mark Absent'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let btn = $('#markAbsentBtn');
                        btn.prop('disabled', true).html(
                            '<i class="fa fa-spinner fa-spin me-1"></i> Processing...');
                        $.post("{{ route('hr.attendance.mark-absent') }}", {
                                _token: "{{ csrf_token() }}"
                            })
                            .done(function(res) {
                                Swal.fire('Success', res.message, 'success').then(() => location
                                    .reload());
                            })
                            .fail(function(xhr) {
                                Swal.fire('Error', xhr.responseJSON?.error || 'Failed.',
                                    'error');
                                btn.prop('disabled', false).html(
                                    '<i class="fa fa-user-times me-1"></i> Mark Absent');
                            });
                    }
                });
            });
        });
    </script>
@endsection
