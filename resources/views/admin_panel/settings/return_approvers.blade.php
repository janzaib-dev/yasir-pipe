@extends('admin_panel.layout.app')

@section('content')
    <style>
        :root {
            --primary: #4a69bd;
            --success: #10ac84;
            --danger: #ee5a6f;
            --warning: #f79f1f;
            --bg-light: #f5f6fa;
        }

        .approvers-container {
            max-width: 1000px;
            margin: 2rem auto;
        }

        .approvers-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header-custom {
            background: linear-gradient(135deg, var(--primary), #6a89cc);
            color: white;
            padding: 2rem;
        }

        .card-header-custom h4 {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header-custom p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .card-body-custom {
            padding: 2rem;
        }

        .user-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .user-table thead {
            background: var(--bg-light);
        }

        .user-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
        }

        .user-table td {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .user-table tbody tr:hover {
            background: #f8f9fa;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .user-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .user-email {
            font-size: 0.875rem;
            color: #7f8c8d;
        }

        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: var(--bg-light);
            color: var(--primary);
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
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
            transition: 0.3s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }

        input:checked+.toggle-slider {
            background-color: var(--success);
        }

        input:checked+.toggle-slider:before {
            transform: translateX(26px);
        }

        .permission-label {
            font-size: 0.875rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .permission-description {
            font-size: 0.75rem;
            color: #7f8c8d;
            margin-top: 0.25rem;
        }

        .alert-custom {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .alert-info {
            background: #d1ecf1;
            border-left: 4px solid var(--primary);
            color: #0c5460;
        }

        .alert-warning {
            background: #fff3cd;
            border-left: 4px solid var(--warning);
            color: #856404;
        }

        .permission-cell {
            text-align: center;
        }
    </style>

    <div class="approvers-container">
        <div class="approvers-card">
            <div class="card-header-custom">
                <h4>
                    <i class="fas fa-user-shield"></i>
                    Return Approval Permissions
                </h4>
                <p>Grant users permission to approve sale returns and past-deadline returns</p>
            </div>

            <div class="card-body-custom">
                <div class="alert-custom alert-info">
                    <i class="fas fa-info-circle fa-lg"></i>
                    <div>
                        <strong>Permission Levels:</strong><br>
                        <strong>Can Approve Returns:</strong> User can approve regular sale returns<br>
                        <strong>Can Approve Past Deadline:</strong> User can approve returns that are past the deadline
                        (Super Admin privilege)
                    </div>
                </div>

                <div class="alert-custom alert-warning">
                    <i class="fas fa-exclamation-triangle fa-lg"></i>
                    <div>
                        <strong>Important:</strong> Only grant "Past Deadline" permission to trusted managers. This allows
                        them to bypass the return deadline policy.
                    </div>
                </div>

                <table class="user-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th class="text-center">Can Approve Returns</th>
                            <th class="text-center">Can Approve Past Deadline</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <span class="user-name">{{ $user->name }}</span>
                                        <span class="user-email">{{ $user->email }}</span>
                                    </div>
                                </td>
                                <td>
                                    @foreach ($user->roles as $role)
                                        <span class="role-badge">{{ $role->name }}</span>
                                    @endforeach
                                </td>
                                <td class="permission-cell">
                                    <label class="toggle-switch">
                                        <input type="checkbox" class="permission-toggle" data-user-id="{{ $user->id }}"
                                            data-permission="can_approve_returns"
                                            {{ $user->can_approve_returns ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </td>
                                <td class="permission-cell">
                                    <label class="toggle-switch">
                                        <input type="checkbox" class="permission-toggle" data-user-id="{{ $user->id }}"
                                            data-permission="can_approve_past_deadline_returns"
                                            {{ $user->can_approve_past_deadline_returns ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center" style="padding: 2rem;">
                                    <i class="fas fa-users fa-3x mb-3" style="color: #ccc;"></i>
                                    <p style="color: #7f8c8d;">No users found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggles = document.querySelectorAll('.permission-toggle');

            toggles.forEach(toggle => {
                toggle.addEventListener('change', function() {
                    const userId = this.dataset.userId;
                    const permission = this.dataset.permission;
                    const isChecked = this.checked;

                    // Prepare data
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('user_id', userId);

                    if (permission === 'can_approve_returns' && isChecked) {
                        formData.append('can_approve_returns', '1');
                    }
                    if (permission === 'can_approve_past_deadline_returns' && isChecked) {
                        formData.append('can_approve_past_deadline_returns', '1');
                    }

                    // If unchecking, we need to send the current state of both checkboxes
                    const row = this.closest('tr');
                    const approveReturns = row.querySelector(
                        '[data-permission="can_approve_returns"]').checked;
                    const approvePastDeadline = row.querySelector(
                        '[data-permission="can_approve_past_deadline_returns"]').checked;

                    formData.set('can_approve_returns', approveReturns ? '1' : '0');
                    formData.set('can_approve_past_deadline_returns', approvePastDeadline ? '1' :
                        '0');

                    // Send AJAX request
                    fetch('{{ route('settings.return-approvers.update') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Show success message
                                showNotification(data.message, 'success');
                            } else {
                                // Revert toggle
                                this.checked = !isChecked;
                                showNotification('Failed to update permissions', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            // Revert toggle
                            this.checked = !isChecked;
                            showNotification('An error occurred', 'error');
                        });
                });
            });

            function showNotification(message, type) {
                // Create notification element
                const notification = document.createElement('div');
                notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            background: ${type === 'success' ? '#10ac84' : '#ee5a6f'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideIn 0.3s ease;
        `;
                notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            ${message}
        `;

                document.body.appendChild(notification);

                // Remove after 3 seconds
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }
        });
    </script>

    <style>
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    </style>
@endsection
