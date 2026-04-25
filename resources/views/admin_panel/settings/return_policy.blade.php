@extends('admin_panel.layout.app')

@section('content')
    <style>
        :root {
            --primary-color: #4a69bd;
            --success-color: #10ac84;
            --danger-color: #ee5a6f;
            --warning-color: #f79f1f;
            --bg-light: #f5f6fa;
        }

        .settings-container {
            max-width: 900px;
            margin: 2rem auto;
        }

        .settings-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .settings-header {
            background: linear-gradient(135deg, var(--primary-color), #6a89cc);
            color: white;
            padding: 2rem;
        }

        .settings-header h4 {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .settings-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .settings-body {
            padding: 2rem;
        }

        .setting-group {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e1e8ed;
        }

        .setting-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .setting-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .setting-description {
            color: #7f8c8d;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .setting-input {
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .setting-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 105, 189, 0.1);
            outline: none;
        }

        .input-group-custom {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .input-addon {
            background: var(--bg-light);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            color: #2c3e50;
            white-space: nowrap;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 30px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }

        input:checked+.toggle-slider {
            background-color: var(--success-color);
        }

        input:checked+.toggle-slider:before {
            transform: translateX(30px);
        }

        .btn-save {
            background: linear-gradient(135deg, var(--success-color), #1dd1a1);
            color: white;
            border: none;
            padding: 0.875rem 2.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 172, 132, 0.3);
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 172, 132, 0.4);
        }

        .alert-custom {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d4edda;
            border-left: 4px solid var(--success-color);
            color: #155724;
        }

        .info-badge {
            display: inline-block;
            background: var(--bg-light);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-left: 0.5rem;
        }

        .example-text {
            background: #fff3cd;
            border-left: 3px solid var(--warning-color);
            padding: 0.75rem 1rem;
            border-radius: 4px;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #856404;
        }
    </style>

    <div class="settings-container">
        <div class="settings-card">
            <div class="settings-header">
                <h4>
                    <i class="fas fa-undo-alt"></i>
                    Return Policy Settings
                </h4>
                <p>Configure your store's return and refund policies</p>
            </div>

            <div class="settings-body">
                @if (session('success'))
                    <div class="alert-custom alert-success">
                        <i class="fas fa-check-circle fa-lg"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <form action="{{ route('settings.return-policy.update') }}" method="POST">
                    @csrf

                    <!-- Return Deadline -->
                    <div class="setting-group">
                        <label class="setting-label">
                            <i class="fas fa-calendar-alt text-primary"></i>
                            Return Deadline (Days)
                            <span class="info-badge">CRITICAL</span>
                        </label>
                        <p class="setting-description">
                            Number of days customers have to return items after purchase.
                            Set to <strong>0</strong> to completely disable returns.
                        </p>
                        <div class="input-group-custom">
                            <input type="number" name="return_deadline_days" class="setting-input"
                                value="{{ $settings->firstWhere('key', 'return_deadline_days')->value ?? 30 }}"
                                min="0" max="365" required>
                            <span class="input-addon">days</span>
                        </div>
                        <div class="example-text">
                            <strong>Examples:</strong>
                            0 = No returns allowed |
                            1 = Same day only |
                            7 = One week |
                            30 = One month |
                            90 = Three months
                        </div>
                    </div>

                    <!-- Require Approval -->
                    <div class="setting-group">
                        <label class="setting-label">
                            <i class="fas fa-user-check text-info"></i>
                            Require Manager Approval
                        </label>
                        <p class="setting-description">
                            If enabled, all returns must be approved by a manager before stock is restored and refunds are
                            processed.
                        </p>
                        <label class="toggle-switch">
                            <input type="checkbox" name="return_require_approval" value="1"
                                {{ $settings->firstWhere('key', 'return_require_approval')->value ?? 1 ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="ms-3" id="approval-status">
                            {{ $settings->firstWhere('key', 'return_require_approval')->value ?? 1 ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>

                    <!-- Auto-Approve Threshold -->
                    <div class="setting-group">
                        <label class="setting-label">
                            <i class="fas fa-bolt text-warning"></i>
                            Auto-Approve Threshold
                        </label>
                        <p class="setting-description">
                            Returns under this amount will be automatically approved without manager review.
                            Set to <strong>0</strong> to disable auto-approval.
                        </p>
                        <div class="input-group-custom">
                            <span class="input-addon">PKR</span>
                            <input type="number" name="return_auto_approve_threshold" class="setting-input"
                                value="{{ $settings->firstWhere('key', 'return_auto_approve_threshold')->value ?? 0 }}"
                                min="0" step="0.01">
                        </div>
                        <div class="example-text">
                            <strong>Tip:</strong> Set to 5000 to auto-approve returns under PKR 5,000.
                            Larger returns will still require manager approval.
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="text-center mt-4">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save me-2"></i>
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle switch text update
        document.querySelector('input[name="return_require_approval"]').addEventListener('change', function() {
            document.getElementById('approval-status').textContent = this.checked ? 'Enabled' : 'Disabled';
        });
    </script>
@endsection
