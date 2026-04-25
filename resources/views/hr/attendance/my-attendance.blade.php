@extends('admin_panel.layout.app')

@section('content')
    <style>
        .my-attendance {
            max-width: 600px;
            margin: 40px auto;
        }

        .attendance-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .attendance-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 32px;
            text-align: center;
        }

        .attendance-header h2 {
            margin: 0 0 8px 0;
            font-weight: 600;
        }

        .attendance-header .time {
            font-size: 3rem;
            font-weight: 700;
            margin: 16px 0;
        }

        .attendance-header .date {
            opacity: 0.9;
        }

        .attendance-body {
            padding: 32px;
        }

        .employee-info {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .employee-avatar {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .employee-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1a1a2e;
        }

        .employee-dept {
            color: #64748b;
        }

        .status-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
        }

        .status-row:not(:last-child) {
            border-bottom: 1px solid #e2e8f0;
        }

        .status-label {
            color: #64748b;
            font-size: 0.9rem;
        }

        .status-value {
            font-weight: 600;
            color: #1a1a2e;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-secondary {
            background: #f1f5f9;
            color: #64748b;
        }

        .btn-checkin {
            width: 100%;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-checkin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.4);
        }

        .btn-checkout {
            width: 100%;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }

        .btn-disabled {
            background: #cbd5e1;
            cursor: not-allowed;
        }

        .btn-disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .message {
            text-align: center;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .message-success {
            background: #dcfce7;
            color: #166534;
        }

        .message-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .no-employee {
            text-align: center;
            padding: 60px 20px;
        }

        .no-employee i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        /* Camera Modal */
        .camera-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .camera-modal.active {
            display: flex;
        }

        .camera-container {
            background: #1a1a2e;
            border-radius: 20px;
            padding: 24px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }

        .camera-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .camera-title.checkin {
            color: #22c55e;
        }

        .camera-title.checkout {
            color: #ef4444;
        }

        .video-container {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        #video {
            width: 100%;
            display: block;
            border-radius: 16px;
        }

        .face-guide {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 180px;
            height: 220px;
            border: 3px dashed rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            pointer-events: none;
        }

        .camera-actions {
            display: flex;
            gap: 12px;
        }

        .btn-capture {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-capture.checkin {
            background: #22c55e;
            color: white;
        }

        .btn-capture.checkout {
            background: #ef4444;
            color: white;
        }

        .btn-cancel {
            flex: 1;
            padding: 14px;
            border: 2px solid #64748b;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            background: transparent;
            color: white;
        }

        .camera-countdown {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 5rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            display: none;
        }

        #canvas {
            display: none;
        }
    </style>

    <!-- Script for Face API -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="my-attendance">
                <!-- Status & Feedback Container -->
                <div id="statusContainer" class="d-none mb-4 text-center">
                    <div class="status-pill d-inline-block px-4 py-2 rounded-pill bg-white shadow-sm border">
                        <i class="fa fa-spinner fa-spin text-primary me-2"></i>
                        <span id="globalStatusText" class="fw-bold text-dark">Initializing AI...</span>
                    </div>
                </div>

                <div class="attendance-card">
                    <div class="attendance-header">
                        <h2><i class="fa fa-fingerprint"></i> My Attendance</h2>
                        <div class="time" id="currentTime">--:--:--</div>
                        <div class="date" id="currentDate">Loading...</div>
                    </div>

                    @if ($employee)
                        <div class="attendance-body">
                            <div class="employee-info">
                                <div class="employee-avatar">
                                    @if ($employee->face_photo)
                                        <img src="{{ asset($employee->face_photo) }}" alt="Face"
                                            style="width:100%; height:100%; object-fit:cover; border-radius:12px;">
                                    @else
                                        {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                                    @endif
                                </div>
                                <div>
                                    <div class="employee-name">{{ $employee->full_name }}</div>
                                    <div class="employee-dept">{{ $employee->department->name ?? 'N/A' }} •
                                        {{ $employee->designation->name ?? '' }}</div>
                                </div>
                            </div>

                            <div id="messageBox"></div>

                            <div class="status-box">
                                <div class="status-row">
                                    <span class="status-label">Today's Status</span>
                                    <span
                                        class="status-badge {{ $attendance ? ($attendance->status == 'present' ? 'badge-success' : ($attendance->status == 'late' ? 'badge-warning' : 'badge-secondary')) : 'badge-secondary' }}">
                                        {{ $attendance ? ucfirst($attendance->status) : 'Not Marked' }}
                                    </span>
                                </div>
                                <div class="status-row">
                                    <span class="status-label">Check In</span>
                                    <span class="status-value" id="checkInTime">
                                        {{ $attendance && $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('h:i A') : '--:--' }}
                                    </span>
                                </div>
                                <div class="status-row">
                                    <span class="status-label">Check Out</span>
                                    <span class="status-value" id="checkOutTime">
                                        {{ $attendance && $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('h:i A') : '--:--' }}
                                    </span>
                                </div>
                                <div class="status-row">
                                    <span class="status-label">Total Hours</span>
                                    <span class="status-value" id="totalHours">
                                        {{ $attendance && $attendance->total_hours ? number_format($attendance->total_hours, 2) . ' hrs' : '--' }}
                                    </span>
                                </div>
                            </div>

                            @if (!$attendance || !$attendance->check_in_time)
                                <button type="button" class="btn-checkin" id="openCameraBtn" data-type="check_in">
                                    <i class="fa fa-sign-in-alt"></i> Check In with Camera
                                </button>
                            @elseif(!$attendance->check_out_time)
                                <button type="button" class="btn-checkout" id="openCameraBtn" data-type="check_out">
                                    <i class="fa fa-sign-out-alt"></i> Check Out with Camera
                                </button>
                            @else
                                <button type="button" class="btn-checkin btn-disabled" disabled>
                                    <i class="fa fa-check-circle"></i> Attendance Complete
                                </button>
                            @endif
                        </div>
                    @else
                        <div class="attendance-body">
                            <div class="no-employee">
                                <i class="fa fa-user-slash"></i>
                                <h4>No Employee Profile</h4>
                                <p class="text-muted">Your user account is not linked to an employee profile. Please contact
                                    HR.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Camera Modal with AI Features -->
    <div class="camera-modal" id="cameraModal">
        <div class="camera-container">
            <div class="camera-title" id="cameraTitle">
                <i class="fa fa-camera"></i> Check In
            </div>

            <div class="video-container"
                style="position: relative; border-radius: 16px; overflow: hidden; background: #000;">
                <video id="video" autoplay playsinline
                    style="width: 100%; height: auto; transform: scaleX(-1);"></video>
                <canvas id="face-overlay" style="position: absolute; top: 0; left: 0;"></canvas>

                <!-- Face Guide Overlay -->
                <div class="face-guide"></div>

                <!-- Status Pills -->
                <div id="aiStatus"
                    style="position: absolute; bottom: 20px; left: 0; right: 0; text-align: center; pointer-events: none;">
                    <span class="badge rounded-pill bg-dark bg-opacity-75 text-white shadow-sm px-3 py-2">
                        <i class="fa fa-search me-1"></i> Looking for face...
                    </span>
                </div>

                <div class="camera-countdown" id="countdown"></div>
                <canvas id="canvas" style="display:none;"></canvas>
            </div>

            <div class="camera-actions">
                <button class="btn-capture checkin" id="captureBtn" disabled>
                    <i class="fa fa-camera"></i> Wait for Face...
                </button>
                <button class="btn-cancel" id="cancelBtn">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        let currentType = 'check_in';
        let stream = null;
        let isModelsLoaded = false;
        let detectionInterval = null;

        const video = document.getElementById('video');
        const overlay = document.getElementById('face-overlay');
        const canvas = document.getElementById('canvas');
        const cameraModal = document.getElementById('cameraModal');

        // Preload Face API Models
        $(document).ready(async function() {
            const MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
            try {
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                    // faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL) // Not strictly needed for presence check
                ]);
                isModelsLoaded = true;
                console.log('Models Loaded');
                $('#globalStatusText').text('AI Ready');
            } catch (e) {
                console.error('Model Load Failed', e);
            }
        });

        // Update time
        function updateTime() {
            const now = new Date();
            $('#currentTime').text(now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit'
            }));
            $('#currentDate').text(now.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }));
        }
        setInterval(updateTime, 1000);
        updateTime();

        const requiresLocation = {{ $requiresLocation ? 'true' : 'false' }};

        // Open Camera
        $('#openCameraBtn').click(async function() {
            currentType = $(this).data('type');
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Checking permissions...');

            // Permissions
            if (requiresLocation) {
                const loc = await checkLocationPermission();
                if (!loc.granted) {
                    showPermissionError('location', loc.message);
                    resetMainButton(btn);
                    return;
                }
            }
            const cam = await checkCameraPermission();
            if (!cam.granted) {
                showPermissionError('camera', cam.message);
                resetMainButton(btn);
                return;
            }

            resetMainButton(btn);

            // Update UI
            if (currentType === 'check_in') {
                $('#cameraTitle').html('<i class="fa fa-sign-in-alt"></i> Check In').removeClass('checkout')
                    .addClass('checkin');
                $('#captureBtn').removeClass('checkout').addClass('checkin');
            } else {
                $('#cameraTitle').html('<i class="fa fa-sign-out-alt"></i> Check Out').removeClass('checkin')
                    .addClass('checkout');
                $('#captureBtn').removeClass('checkin').addClass('checkout');
            }

            // Start
            cameraModal.classList.add('active');
            startCamera();
        });

        function resetMainButton(btn) {
            btn.prop('disabled', false).html(currentType === 'check_in' ?
                '<i class="fa fa-sign-in-alt"></i> Check In with Camera' :
                '<i class="fa fa-sign-out-alt"></i> Check Out with Camera');
        }

        async function checkLocationPermission() {
            // ... existing logic ...
            return new Promise((resolve) => {
                if (!navigator.geolocation) {
                    resolve({
                        granted: false,
                        message: 'Not supported'
                    });
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    (p) => resolve({
                        granted: true
                    }),
                    (e) => resolve({
                        granted: false,
                        message: 'Location denied'
                    }), {
                        timeout: 5000
                    }
                );
            });
        }
        async function checkCameraPermission() {
            try {
                const s = await navigator.mediaDevices.getUserMedia({
                    video: true
                });
                s.getTracks().forEach(t => t.stop());
                return {
                    granted: true
                };
            } catch (e) {
                return {
                    granted: false,
                    message: e.message
                };
            }
        }

        // Show permission error
        function showPermissionError(type, msg) {
            $('#messageBox').html(`<div class="message message-error">${msg}</div>`);
            setTimeout(() => $('#messageBox').empty(), 5000);
        }

        async function startCamera() {
            // Wait for models if not loaded (should be loaded by now)
            if (!isModelsLoaded) {
                $('#aiStatus').html('<span class="badge rounded-pill bg-warning text-dark">Loading AI...</span>');
                // You might want to wait or just proceed without AI for now (and enable button)
            }

            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: 640,
                        height: 480,
                        facingMode: 'user'
                    }
                });
                video.srcObject = stream;

                // Start Detection Loop
                startDetection();

            } catch (err) {
                showPermissionError('camera', 'Camera failed.');
                closeCamera();
            }
        }

        function startDetection() {
            // Detect faces
            $('#captureBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Detecting Face...');

            detectionInterval = setInterval(async () => {
                if (!isModelsLoaded || !stream) return;

                const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions());

                if (detection) {
                    // Face Found
                    $('.face-guide').css('border-color', '#22c55e'); // Green
                    $('#aiStatus').html(
                        '<span class="badge rounded-pill bg-success text-white"><i class="fa fa-check"></i> Face Detected</span>'
                    );
                    $('#captureBtn').prop('disabled', false).html('<i class="fa fa-camera"></i> Capture & ' + (
                        currentType == 'check_in' ? 'Check In' : 'Check Out'));
                } else {
                    // No Face
                    $('.face-guide').css('border-color', 'rgba(255,255,255,0.5)');
                    $('#aiStatus').html(
                        '<span class="badge rounded-pill bg-danger bg-opacity-75 text-white">No Face Detected</span>'
                    );
                    $('#captureBtn').prop('disabled', true).html('<i class="fa fa-search"></i> Look at Camera');
                }

            }, 500); // Check every 500ms
        }

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            if (detectionInterval) clearInterval(detectionInterval);
            $('#aiStatus').empty();
        }

        function closeCamera() {
            stopCamera();
            cameraModal.classList.remove('active');
        }

        $('#cancelBtn').click(closeCamera);

        $('#captureBtn').click(function() {
            // Capture Flow
            clearInterval(detectionInterval); // Stop detecting
            const btn = $(this);
            btn.prop('disabled', true).html('Capturing...');

            // Flash
            $('.video-container').css('opacity', 0.5);
            setTimeout(() => $('.video-container').css('opacity', 1), 100);

            // Capture
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');

            // If video is mirrored (transform scaleX -1), we must mirror capture too?
            // Actually css transform doesn't affect video stream content.
            // But it affects user perception. If user looks left, video shows right (mirror).
            // If we draw raw video, it is NOT mirrored.
            // Usually for attendance photo, raw is fine.
            ctx.drawImage(video, 0, 0);
            const photo = canvas.toDataURL('image/jpeg', 0.8);

            // Get Loc
            getLocationAndSubmit(photo, btn);
        });

        function getLocationAndSubmit(photo, btn) {
            if (requiresLocation && navigator.geolocation) {
                btn.html('Getting Location...');
                navigator.geolocation.getCurrentPosition(
                    (p) => submitAttendance(photo, btn, p.coords.latitude, p.coords.longitude),
                    (e) => submitAttendance(photo, btn, null, null)
                );
            } else {
                submitAttendance(photo, btn, null, null);
            }
        }

        function submitAttendance(photo, btn, lat, lng) {
            btn.html('Uploading...');
            $.ajax({
                url: '{{ route('my-attendance.mark') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    type: currentType,
                    photo: photo,
                    latitude: lat,
                    longitude: lng
                },
                success: function(res) {
                    if (res.success) {
                        $('#aiStatus').html('<span class="badge bg-success">Success!</span>');
                        setTimeout(() => {
                            closeCamera();
                            location.reload();
                        }, 1000);
                    } else {
                        $('#aiStatus').html('<span class="badge bg-danger">' + res.error + '</span>');
                        startDetection(); // Restart detection to retry
                    }
                },
                error: function(err) {
                    $('#aiStatus').html('<span class="badge bg-danger">Error: ' + err.statusText + '</span>');
                    startDetection();
                }
            });
        }
    </script>

    <style>
        @keyframes pulse {

            0%,
            100% {
                transform: translate(-50%, -50%) scale(1);
            }

            50% {
                transform: translate(-50%, -50%) scale(1.1);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .video-container {
            transition: filter 0.1s ease;
        }
    </style>
@endsection
