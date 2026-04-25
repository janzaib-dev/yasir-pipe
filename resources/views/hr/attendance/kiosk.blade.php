<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Attendance Kiosk - Face Recognition</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            color: white;
            font-family: 'Segoe UI', sans-serif;
        }

        .kiosk-container {
            padding: 20px;
        }

        .time-display {
            font-size: 4rem;
            font-weight: 300;
            text-align: center;
            margin-bottom: 10px;
        }

        .date-display {
            font-size: 1.5rem;
            text-align: center;
            color: #aaa;
            margin-bottom: 30px;
        }

        .camera-container {
            background: #000;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            max-width: 640px;
            margin: 0 auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            min-height: 480px;
        }

        #video,
        #capturedImage {
            width: 100%;
            height: auto;
            display: block;
        }

        #capturedImage {
            display: none;
        }

        #canvas-overlay {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
        }

        .camera-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }

        .face-circle {
            width: 250px;
            height: 300px;
            border: 4px dashed rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .face-circle.detected {
            border-color: #00b894;
            box-shadow: 0 0 20px rgba(0, 184, 148, 0.5);
        }

        .face-circle.unknown {
            border-color: #d63031;
            box-shadow: 0 0 20px rgba(214, 48, 49, 0.5);
        }

        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }

        .btn-check-in {
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
            border: none;
            font-size: 1.5rem;
            padding: 20px 60px;
            border-radius: 50px;
            margin: 10px;
            opacity: 0.5;
            cursor: not-allowed;
            transition: all 0.3s;
        }

        .btn-check-in.active {
            opacity: 1;
            cursor: pointer;
            transform: scale(1.05);
        }

        .btn-check-out {
            background: linear-gradient(135deg, #e17055 0%, #d63031 100%);
            border: none;
            font-size: 1.5rem;
            padding: 20px 60px;
            border-radius: 50px;
            margin: 10px;
            opacity: 0.5;
            cursor: not-allowed;
            transition: all 0.3s;
        }

        .btn-check-out.active {
            opacity: 1;
            cursor: pointer;
            transform: scale(1.05);
        }

        .status-message {
            text-align: center;
            margin-top: 20px;
            font-size: 1.3rem;
            min-height: 30px;
        }

        .employee-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
            display: none;
        }

        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
        }

        #loading {
            display: none;
            text-align: center;
            margin-top: 20px;
        }

        #model-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.8);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            z-index: 100;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>

<body>
    <a href="{{ route('hr.attendance.index') }}" class="back-link">
        <i class="fa fa-arrow-left"></i> Back to Attendance
    </a>

    <div class="container kiosk-container">
        <div class="time-display" id="currentTime">--:--:--</div>
        <div class="date-display" id="currentDate">Loading...</div>

        <div class="camera-container">
            <video id="video" autoplay playsinline muted></video>
            <canvas id="canvas-overlay"></canvas>
            <img id="capturedImage" alt="Captured">
            <canvas id="capture-canvas" style="display: none;"></canvas>

            <div class="camera-overlay">
                <div class="face-circle" id="faceGuide"></div>
            </div>

            <div id="model-loading">
                <div class="spinner-border text-light mb-2" role="status"></div>
                <div>Loading Face Models...</div>
            </div>
        </div>

        <div class="status-message" id="statusMessage">Initializing Camera...</div>
        <div class="text-center text-muted mb-3" id="identifiedName"
            style="height: 25px; font-weight: bold; font-size: 1.2rem;"></div>

        <div class="action-buttons">
            <button class="btn btn-check-in text-white" id="checkInBtn" disabled>
                <i class="fa fa-sign-in-alt"></i> Check In
            </button>
            <button class="btn btn-check-out text-white" id="checkOutBtn" disabled>
                <i class="fa fa-sign-out-alt"></i> Check Out
            </button>
        </div>

        <div id="loading">
            <div class="spinner-border text-light" role="status">
                <span class="visually-hidden">Processing...</span>
            </div>
            <p class="mt-2">Processing attendance...</p>
        </div>

        <div class="employee-info" id="employeeInfo">
            <div class="row align-items-center">
                <div class="col-auto">
                    <img id="employeePhoto" src="" class="rounded-circle" width="80" height="80"
                        style="object-fit: cover;">
                </div>
                <div class="col">
                    <h4 id="employeeName" class="mb-1"></h4>
                    <p id="employeeDept" class="mb-0 text-muted"></p>
                </div>
                <div class="col-auto">
                    <span id="attendanceStatus" class="badge bg-success fs-5"></span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        // Configuration
        const MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models'; // Using main demo repo for models
        let brandedDescriptors = [];
        let faceMatcher = null;
        let verifiedEmployeeId = null;
        let isProcessing = false;

        // Update time display
        function updateTime() {
            const now = new Date();
            const time = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit'
            });
            const date = now.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            $('#currentTime').text(time);
            $('#currentDate').text(date);
        }
        setInterval(updateTime, 1000);
        updateTime();

        // Initialize System
        async function init() {
            try {
                // 1. Load Models
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
                    faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL)
                ]);

                $('#model-loading').html(
                    '<div class="spinner-border text-light mb-2"></div><div>Loading Employees...</div>');

                // 2. Load Employee Data
                await loadLabeledImages();

                $('#model-loading').hide();
                $('#statusMessage').text('Ready. Please look at the camera.');

                // 3. Start Camera
                startCamera();
            } catch (err) {
                console.error(err);
                $('#model-loading').html(
                    '<div class="text-danger">Error loading system resources. <br>Check console.</div>');
            }
        }

        // Fetch Employee Encodings associated with IDs
        async function loadLabeledImages() {
            try {
                const response = await $.get('{{ route('hr.employees.encodings') }}');
                const labeledDescriptors = [];

                response.forEach(emp => {
                    if (emp.descriptor) {
                        // Descriptor is stored as array in DB (casted), verify it
                        const descriptorFloat32 = new Float32Array(Object.values(emp.descriptor));
                        labeledDescriptors.push(new faceapi.LabeledFaceDescriptors(emp.id.toString(), [
                            descriptorFloat32
                        ]));
                    }
                });

                if (labeledDescriptors.length > 0) {
                    faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.6); // 0.6 is distance threshold
                }
            } catch (error) {
                console.error('Error fetching encodings:', error);
                $('#statusMessage').text('Error loading employee database.');
            }
        }

        async function startCamera() {
            const video = document.getElementById('video');
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {}
                });
                video.srcObject = stream;
            } catch (err) {
                $('#statusMessage').html('<span class="text-danger">Camera Access Denied</span>');
            }

            video.addEventListener('play', () => {
                const canvas = document.getElementById('canvas-overlay');
                const displaySize = {
                    width: video.width || 640,
                    height: video.height || 480
                };
                faceapi.matchDimensions(canvas, displaySize);

                setInterval(async () => {
                    if (isProcessing) return; // Pause detection while processing attendance

                    // Detect Faces
                    const detections = await faceapi.detectAllFaces(video, new faceapi
                            .TinyFaceDetectorOptions())
                        .withFaceLandmarks()
                        .withFaceDescriptors();

                    const resizedDetections = faceapi.resizeResults(detections, displaySize);

                    // Clear Previous Drawings
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    if (resizedDetections.length > 0) {
                        const detection = resizedDetections[0]; // Take primary face
                        const box = detection.detection.box;

                        // Visual Guide Logic
                        const faceGuide = $('#faceGuide');

                        // Recognition Logic
                        if (faceMatcher) {
                            const bestMatch = faceMatcher.findBestMatch(detection.descriptor);

                            if (bestMatch.label !== 'unknown') {
                                verifiedEmployeeId = bestMatch.label;
                                const distance = bestMatch.distance;

                                faceGuide.removeClass('unknown').addClass('detected');
                                $('#identifiedName').text('Identified: Employee #' +
                                    verifiedEmployeeId + ' (' + Math.round((1 - distance) *
                                    100) + '%)');

                                // Enable Buttons
                                enableButtons(true);
                            } else {
                                verifiedEmployeeId = null;
                                faceGuide.removeClass('detected').addClass('unknown');
                                $('#identifiedName').text('Unknown Person');
                                enableButtons(false);
                            }
                        } else {
                            // No employees registered yet logic
                            $('#identifiedName').text('System Empty: No Faces Registered');
                        }
                    } else {
                        $('#faceGuide').removeClass('detected unknown');
                        $('#identifiedName').text('');
                        enableButtons(false);
                    }
                }, 100); // Check every 100ms
            });
        }

        function enableButtons(enable) {
            const btns = $('#checkInBtn, #checkOutBtn');
            if (enable) {
                btns.prop('disabled', false).addClass('active');
            } else {
                btns.prop('disabled', true).removeClass('active');
            }
        }

        // Capture photo for audit trail
        function capturePhoto() {
            const video = document.getElementById('video');
            const canvas = document.getElementById('capture-canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            return canvas.toDataURL('image/jpeg', 0.8);
        }

        // Mark attendance
        function markAttendance(type) {
            if (!verifiedEmployeeId) return;

            isProcessing = true;
            const photo = capturePhoto();

            // UI Updates
            $('#loading').show();
            $('.action-buttons button').prop('disabled', true).removeClass('active');
            $('#statusMessage').text('');

            // Stop Video temporarily? No, keep it running but ignore detections

            $.ajax({
                url: '{{ route('hr.attendance.mark') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    type: type,
                    photo: photo,
                    employee_id: verifiedEmployeeId // Send the identified ID
                },
                success: function(response) {
                    $('#loading').hide();

                    if (response.success) {
                        // Beep Sound could go here

                        if (response.employee) {
                            $('#employeeName').text(response.employee.name);
                            $('#employeeDept').text(response.employee.department);
                            if (response.employee.photo) {
                                $('#employeePhoto').attr('src', response.employee.photo);
                            } else {
                                $('#employeePhoto').attr('src', 'https://ui-avatars.com/api/?name=' + response
                                    .employee.name + '&background=random');
                            }
                            $('#attendanceStatus')
                                .text(type === 'check_in' ? 'Checked In' : 'Checked Out')
                                .removeClass('bg-success bg-danger')
                                .addClass(type === 'check_in' ? 'bg-success' : 'bg-danger');

                            $('#employeeInfo').slideDown();
                        }

                        let msg = '<span class="text-success fw-bold"><i class="fa fa-check-circle"></i> ' +
                            response.message + '</span>';
                        if (response.is_late) {
                            msg += '<br><span class="text-warning"><i class="fa fa-clock"></i> Late by ' +
                                response.late_minutes + ' minutes</span>';
                        }
                        $('#statusMessage').html(msg);
                    } else {
                        $('#statusMessage').html(
                            '<span class="text-danger fw-bold"><i class="fa fa-times-circle"></i> ' + (
                                response.error || 'Error') + '</span>');
                    }

                    // Reset after delay
                    setTimeout(resetKiosk, 6000);
                },
                error: function(xhr) {
                    $('#loading').hide();
                    isProcessing = false;
                    let err = 'Error processing request';
                    if (xhr.responseJSON && xhr.responseJSON.error) err = xhr.responseJSON.error;
                    $('#statusMessage').html(
                        '<span class="text-danger"><i class="fa fa-exclamation-triangle"></i> ' + err +
                        '</span>');
                    setTimeout(resetKiosk, 4000);
                }
            });
        }

        function resetKiosk() {
            isProcessing = false;
            $('#employeeInfo').slideUp();
            $('#statusMessage').text('Ready. Please look at the camera.');
            verifiedEmployeeId = null;
            $('#identifiedName').text('');
            // Buttons will auto-enable next time face is detected
        }

        // Button handlers
        $('#checkInBtn').click(function() {
            markAttendance('check_in');
        });

        $('#checkOutBtn').click(function() {
            markAttendance('check_out');
        });

        // Start
        init();
    </script>
</body>

</html>
