@extends('admin_panel.layout.app')
@section('content')

    <style>
        :root {
            --perm-primary: #4f46e5;
            --perm-success: #10b981;
            --perm-warning: #f59e0b;
            --perm-danger: #ef4444;
            --perm-info: #0ea5e9;
            --perm-bg: #f3f4f6;
            --perm-card: #ffffff;
            --perm-border: #e5e7eb;
            --perm-text: #111827;
            --perm-muted: #6b7280;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --radius-lg: 1rem;
        }

        .page-header {
            margin-bottom: 28px;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--perm-text);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title i {
            color: var(--perm-primary);
        }

        .page-subtitle {
            color: var(--perm-muted);
            font-size: 0.9rem;
            margin-top: 4px;
        }

        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--perm-card);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--perm-border);
            transition: all 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 12px;
        }

        .stat-card.primary .stat-icon {
            background: #eef2ff;
            color: var(--perm-primary);
        }

        .stat-card.success .stat-icon {
            background: #dcfce7;
            color: var(--perm-success);
        }

        .stat-card.warning .stat-icon {
            background: #fef3c7;
            color: var(--perm-warning);
        }

        .stat-card.info .stat-icon {
            background: #e0f2fe;
            color: var(--perm-info);
        }

        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--perm-text);
        }

        .stat-card .stat-label {
            font-size: 0.8rem;
            color: var(--perm-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Permissions Card Container */
        .perms-card {
            background: var(--perm-card);
            border-radius: 16px;
            border: 1px solid var(--perm-border);
            overflow: hidden;
        }

        .perms-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--perm-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            max-width: 350px;
            flex: 1;
        }

        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 2px solid var(--perm-border);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: #f8fafc;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--perm-primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            background: white;
        }

        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--perm-muted);
        }

        .btn-create {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);
            color: white;
        }

        /* Permission Groups */
        .perm-groups {
            padding: 24px;
        }

        .perm-group {
            background: var(--perm-card);
            border: 1px solid var(--perm-border);
            border-radius: 14px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .perm-group-header {
            background: #f8fafc;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--perm-border);
        }

        .perm-group-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .perm-group-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .perm-group-name {
            font-weight: 600;
            font-size: 1rem;
            color: var(--perm-text);
            text-transform: capitalize;
        }

        .perm-group-count {
            background: var(--perm-primary);
            color: white;
            font-size: 0.75rem;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
        }

        .perm-group-body {
            padding: 16px 20px;
        }

        .perm-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
        }

        .perm-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid var(--perm-border);
            transition: all 0.2s;
        }

        .perm-item:hover {
            border-color: var(--perm-primary);
            background: #eef2ff;
        }

        .perm-name {
            font-weight: 500;
            color: var(--perm-text);
            font-size: 0.9rem;
        }

        .perm-action-badge {
            font-size: 0.7rem;
            padding: 3px 10px;
            border-radius: 4px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .perm-action-badge.view {
            background: #dbeafe;
            color: #1e40af;
        }

        .perm-action-badge.create,
        .perm-action-badge.add {
            background: #dcfce7;
            color: #166534;
        }

        .perm-action-badge.edit,
        .perm-action-badge.read {
            background: #fef3c7;
            color: #92400e;
        }

        .perm-action-badge.delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .perm-action-badge.approve {
            background: #f3e8ff;
            color: #7c3aed;
        }
        
        .perm-action-badge.mark {
            background: #ffedd5;
            color: #c2410c;
        }
        
        .perm-action-badge.print {
            background: #f3f4f6;
            color: #4b5563;
        }
        
        .perm-action-badge.export {
            background: #e0e7ff;
            color: #4338ca;
        }

        .perm-action-badge.other {
            background: #f1f5f9;
            color: #64748b;
        }

        .perm-actions {
            display: flex;
            gap: 6px;
        }

        .perm-actions .btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            font-size: 0.8rem;
            transition: all 0.2s;
        }

        .perm-actions .btn-edit {
            background: #fef3c7;
            color: var(--perm-warning);
            border: none;
        }

        .perm-actions .btn-edit:hover {
            background: var(--perm-warning);
            color: white;
        }

        .perm-actions .btn-delete {
            background: #fee2e2;
            color: var(--perm-danger);
            border: none;
        }

        .perm-actions .btn-delete:hover {
            background: var(--perm-danger);
            color: white;
        }

        /* Modal Styling */
        .modal-content {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal-header.gradient {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 24px 28px;
            border: none;
        }

        .modal-header.gradient .modal-title {
            font-weight: 700;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header.gradient .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }

        .modal-body {
            padding: 28px;
            background: #ffffff;
        }

        .form-group-modern {
            margin-bottom: 20px;
        }

        .form-group-modern .form-label {
            font-weight: 600;
            color: var(--perm-text);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .form-group-modern .form-label i {
            color: var(--perm-primary);
            font-size: 0.9rem;
        }

        .form-group-modern .form-control,
        .form-group-modern .form-select {
            border: 2px solid var(--perm-border);
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: #f8fafc;
        }

        .form-group-modern .form-control:focus,
        .form-group-modern .form-select:focus {
            border-color: var(--perm-primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            background: #ffffff;
        }

        .modal-footer-modern {
            padding: 20px 28px;
            background: #f8fafc;
            border-top: 1px solid var(--perm-border);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-cancel {
            background: #f1f5f9;
            color: var(--perm-text);
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
        }

        .btn-save {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--perm-muted);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            color: #cbd5e1;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .perm-list {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .stats-row {
                grid-template-columns: 1fr;
            }

            .perms-header {
                flex-direction: column;
            }

            .search-box {
                max-width: 100%;
            }
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="page-title"><i class="fa fa-key"></i> Permission Management</h1>
                        <p class="page-subtitle">Manage system permissions organized by modules</p>
                    </div>
                    <button type="button" class="btn btn-create" id="addPermBtn">
                        <i class="fa fa-plus"></i> Add Permission
                    </button>
                </div>

                <!-- Stats Row -->
                @php
                    $groupedPerms = $permissions->groupBy(function ($p) {
                         // Group logic: everything before the last dot
                        $parts = explode('.', $p->name);
                        if (count($parts) > 1) {
                            array_pop($parts);
                            return implode('.', $parts);
                        }
                        return 'General';
                    })->sortKeys();
                @endphp
                <div class="stats-row">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="fa fa-key"></i></div>
                        <div class="stat-value">{{ $permissions->count() }}</div>
                        <div class="stat-label">Total Permissions</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="fa fa-layer-group"></i></div>
                        <div class="stat-value">{{ $groupedPerms->count() }}</div>
                        <div class="stat-label">Modules</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fa fa-user-shield"></i></div>
                        <div class="stat-value">{{ \Spatie\Permission\Models\Role::count() }}</div>
                        <div class="stat-label">Roles</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="fa fa-calendar"></i></div>
                        <div class="stat-value">{{ $permissions->where('created_at', '>=', now()->subDays(7))->count() }}
                        </div>
                        <div class="stat-label">Added This Week</div>
                    </div>
                </div>

                <!-- Permissions Card -->
                <div class="perms-card">
                    <div class="perms-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="search-box">
                                <i class="fa fa-search"></i>
                                <input type="search" id="permSearch" placeholder="Search permissions...">
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-outline-secondary btn-sm" id="exportPermsBtn"><i
                                        class="fa fa-download"></i></button>
                                <button class="btn btn-outline-secondary btn-sm" id="refreshBtn"><i
                                        class="fa fa-sync"></i></button>
                            </div>
                        </div>
                        <span class="text-muted small" id="permsCount">{{ $permissions->count() }} permissions</span>
                    </div>

                    <div class="perm-groups" id="permGroups">
                        @forelse($groupedPerms as $module => $perms)
                            @php
                                // Icon logic
                                $mainCategory = explode('.', $module)[0];
                                $moduleIcons = [
                                    'hr' => 'fa-users',
                                    'users' => 'fa-user',
                                    'roles' => 'fa-user-shield',
                                    'permissions' => 'fa-key',
                                    'settings' => 'fa-cog',
                                    'reports' => 'fa-chart-bar',
                                    'inventory' => 'fa-boxes',
                                    'sales' => 'fa-shopping-cart',
                                    'purchase' => 'fa-cart-plus',
                                    'accounts' => 'fa-calculator',
                                ];
                                $icon = $moduleIcons[$mainCategory] ?? 'fa-folder';
                                
                                // Format Title
                                $title = str_replace('.', ' ', $module);
                                $title = ucwords($title);
                                $title = str_replace('Hr ', 'HR ', $title);
                                
                                // Sort permissions in this group
                                $perms = $perms->sortBy(function($p) {
                                    $action = explode('.', $p->name);
                                    $action = end($action);
                                    $order = [
                                        'view' => 1, 'read' => 1,
                                        'create' => 2, 'add' => 2,
                                        'edit' => 3, 'update' => 3,
                                        'delete' => 4, 'remove' => 4,
                                        'approve' => 5,
                                        'mark' => 6,
                                        'print' => 7, 
                                        'export' => 8
                                    ];
                                    return $order[$action] ?? 99;
                                });
                            @endphp
                            <div class="perm-group" data-module="{{ strtolower($module) }}">
                                <div class="perm-group-header">
                                    <div class="perm-group-title">
                                        <div class="perm-group-icon"><i class="fa {{ $icon }}"></i></div>
                                        <span class="perm-group-name">{{ $title }}</span>
                                    </div>
                                    <span class="perm-group-count">{{ $perms->count() }} permissions</span>
                                </div>
                                <div class="perm-group-body">
                                    <div class="perm-list">
                                        @foreach ($perms as $perm)
                                            @php
                                                $action = collect(explode('.', $perm->name))->last();
                                                $actionClass = in_array($action, ['view', 'read']) ? 'view' :
                                                              (in_array($action, ['create', 'add']) ? 'create' :
                                                              (in_array($action, ['edit', 'update']) ? 'edit' :
                                                              (in_array($action, ['delete', 'remove']) ? 'delete' :
                                                              (in_array($action, ['approve']) ? 'approve' :
                                                              (in_array($action, ['mark']) ? 'mark' :
                                                              (in_array($action, ['print']) ? 'print' :
                                                              (in_array($action, ['export']) ? 'export' : 'other')))))));
                                            @endphp
                                            <div class="perm-item" data-id="{{ $perm->id }}"
                                                data-name="{{ strtolower($perm->name) }}">
                                                <div class="d-flex align-items-center gap-3">
                                                    <span class="perm-name">{{ $perm->name }}</span>
                                                    <span
                                                        class="perm-action-badge {{ $actionClass }}">{{ $action }}</span>
                                                </div>
                                                <div class="perm-actions">
                                                    <button class="btn btn-edit edit-perm-btn"
                                                        data-id="{{ $perm->id }}" data-name="{{ $perm->name }}"
                                                        title="Edit">
                                                        <i class="fa fa-pen"></i>
                                                    </button>
                                                    <button class="btn btn-delete delete-perm-btn"
                                                        data-id="{{ $perm->id }}" title="Delete">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <i class="fa fa-key"></i>
                                <p>No permissions found. Add your first permission!</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Permission Modal -->
    <div class="modal fade" id="permModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header gradient">
                    <h5 class="modal-title" id="permModalTitle">
                        <i class="fa fa-key"></i>
                        <span>Add New Permission</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="permForm" class="myform" action="{{ route('permissions.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="edit_id" id="permEditId">
                    <input type="hidden" name="name" id="permName">

                    <div class="modal-body">
                        <div class="form-group-modern">
                            <label class="form-label">
                                <i class="fa fa-layer-group"></i> Module
                            </label>
                            <select id="moduleSelect" class="form-select" required>
                                <option value="">-- Select Module --</option>
                            </select>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label">
                                <i class="fa fa-cog"></i> Action
                            </label>
                            <select id="actionSelect" class="form-select" required>
                                <option value="">-- Select Action --</option>
                                <option value="view">View</option>
                                <option value="create">Create</option>
                                <option value="edit">Edit</option>
                                <option value="delete">Delete</option>
                                <option value="approve">Approve</option>
                            </select>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label">
                                <i class="fa fa-eye"></i> Permission Preview
                            </label>
                            <input type="text" id="permPreview" class="form-control" readonly
                                placeholder="module.action">
                        </div>
                    </div>

                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-save">
                            <i class="fa fa-check"></i>
                            <span>Save Permission</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('assets/js/mycode.js') }}"></script>

    <script>
        function loadModules(selected) {
            $.getJSON('{{ route('modules.list') }}', function(res) {
                var $sel = $('#moduleSelect');
                $sel.empty().append('<option value="">-- Select Module --</option>');
                if (Array.isArray(res)) {
                    res.forEach(function(m) {
                        $sel.append($('<option>').val(m).text(m));
                    });
                }
                if (selected) $sel.val(selected);
                updatePreview();
            });
        }

        function updatePreview() {
            var module = $('#moduleSelect').val() || '';
            var action = $('#actionSelect').val() || '';
            var preview = module && action ? module + '.' + action : '';
            $('#permPreview').val(preview);
            $('#permName').val(preview);
        }

        $(document).ready(function() {
            // Add Permission Button
            $('#addPermBtn').click(function() {
                $('#permEditId').val('');
                $('#actionSelect').val('');
                $('#permModalTitle').html('<i class="fa fa-key"></i><span>Add New Permission</span>');
                loadModules();
                $('#permModal').modal('show');
            });

            // Module/Action change - update preview
            $(document).on('change', '#moduleSelect, #actionSelect', updatePreview);

            // Edit Permission
            $(document).on('click', '.edit-perm-btn', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var parts = name.split('.');
                var module = parts.shift();
                var action = parts.join('.') || 'view';

                $('#permEditId').val(id);
                $('#permModalTitle').html('<i class="fa fa-pen"></i><span>Edit Permission</span>');
                loadModules(module);
                setTimeout(function() {
                    $('#actionSelect').val(action);
                    updatePreview();
                }, 300);
                $('#permModal').modal('show');
            });

            // Delete Permission
            $(document).on('click', '.delete-perm-btn', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Permission?',
                    text: 'This action cannot be undone!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Yes, delete!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '{{ url('permission/delete') }}/' + id;
                    }
                });
            });

            // Search Permissions
            $('#permSearch').on('input', function() {
                var q = $(this).val().toLowerCase();
                var visibleCount = 0;

                $('.perm-group').each(function() {
                    var $group = $(this);
                    var module = $group.data('module') || '';
                    var groupVisible = false;

                    $group.find('.perm-item').each(function() {
                        var name = $(this).data('name') || '';
                        var match = name.indexOf(q) !== -1 || module.indexOf(q) !== -1;
                        $(this).toggle(match);
                        if (match) {
                            groupVisible = true;
                            visibleCount++;
                        }
                    });

                    $group.toggle(groupVisible);
                });

                $('#permsCount').text(visibleCount + ' permissions');
            });

            // Form Submit
            $('#permForm').submit(function(e) {
                e.preventDefault();
                var module = $('#moduleSelect').val();
                var action = $('#actionSelect').val();

                if (!module) {
                    Swal.fire('Error', 'Please select a module.', 'error');
                    return;
                }
                if (!action) {
                    Swal.fire('Error', 'Please select an action.', 'error');
                    return;
                }

                $('#permName').val(module + '.' + action);

                var formdata = new FormData(this);
                $(this).find(':submit').prop('disabled', true);
                myAjax($(this).attr('action'), formdata, 'POST');
            });

            // Refresh
            $('#refreshBtn').click(function() {
                location.reload();
            });
        });
    </script>

@endsection
