@extends('admin_panel.layout.app')

@section('content')
    @include('hr.partials.hr-styles')

    <style>
        .holiday-card {
            background: var(--hr-card);
            border: 1px solid var(--hr-border);
            border-radius: 14px;
            overflow: hidden;
            transition: all 0.2s;
        }

        .holiday-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }

        .holiday-header {
            padding: 16px 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .holiday-header.public {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .holiday-header.company {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
        }

        .holiday-header.optional {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .holiday-body {
            padding: 24px;
            text-align: center;
        }

        .holiday-date-big {
            font-size: 3rem;
            font-weight: 800;
            color: var(--hr-text);
            line-height: 1;
        }

        .holiday-month {
            font-size: 1.1rem;
            color: var(--hr-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .holiday-day {
            font-size: 0.9rem;
            color: var(--hr-muted);
            margin-top: 4px;
        }

        .year-select-modern {
            border: 2px solid var(--hr-border);
            border-radius: 10px;
            padding: 10px 16px;
            font-weight: 600;
            background: white;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="page-title"><i class="fa fa-calendar-alt"></i> Holiday Management</h1>
                        <p class="page-subtitle">Manage public and company holidays</p>
                    </div>
                    <div class="d-flex gap-3">
                        <select id="yearSelect" class="year-select-modern">
                            @for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}
                                </option>
                            @endfor
                        </select>
                        @can('hr.holidays.create')
                            <button type="button" class="btn btn-create" id="createBtn">
                                <i class="fa fa-plus"></i> Add Holiday
                            </button>
                        @endcan
                    </div>
                </div>

                <!-- Stats Row -->
                @php
                    $allHolidays = \App\Models\Hr\Holiday::whereYear('date', $year)->get();
                    $publicCount = $allHolidays->where('type', 'public')->count();
                    $companyCount = $allHolidays->where('type', 'company')->count();
                    $optionalCount = $allHolidays->where('type', 'optional')->count();
                @endphp
                <div class="stats-row">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="fa fa-calendar-alt"></i></div>
                        <div class="stat-value">{{ $holidays->total() }}</div>
                        <div class="stat-label">Total Holidays</div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-icon"><i class="fa fa-flag"></i></div>
                        <div class="stat-value">{{ $publicCount }}</div>
                        <div class="stat-label">Public</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon"><i class="fa fa-building"></i></div>
                        <div class="stat-value">{{ $companyCount }}</div>
                        <div class="stat-label">Company</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fa fa-question-circle"></i></div>
                        <div class="stat-value">{{ $optionalCount }}</div>
                        <div class="stat-label">Optional</div>
                    </div>
                </div>

                <!-- Holidays Card -->
                <div class="hr-card">
                    <div class="hr-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="search-box">
                                <i class="fa fa-search"></i>
                                <input type="search" id="holidaySearch" placeholder="Search holidays...">
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-outline-secondary btn-sm" id="refreshBtn"><i
                                        class="fa fa-sync"></i></button>
                            </div>
                        </div>
                        <span class="text-muted small" id="holidayCount">{{ $holidays->total() }} holidays in
                            {{ $year }}</span>
                    </div>

                    <div class="hr-grid" id="holidayGrid">
                        @forelse($holidays as $holiday)
                            <div class="holiday-card" data-id="{{ $holiday->id }}"
                                data-name="{{ strtolower($holiday->name) }}">
                                <div class="holiday-header {{ $holiday->type }}">
                                    <strong>{{ $holiday->name }}</strong>
                                    <div class="hr-actions">
                                        @can('hr.holidays.edit')
                                            <button class="btn btn-sm text-white edit-btn" data-id="{{ $holiday->id }}"
                                                data-name="{{ $holiday->name }}"
                                                data-date="{{ $holiday->date->format('Y-m-d') }}"
                                                data-type="{{ $holiday->type }}"
                                                data-description="{{ $holiday->description }}">
                                                <i class="fa fa-pen"></i>
                                            </button>
                                        @endcan
                                        @can('hr.holidays.delete')
                                            <button class="btn btn-sm text-white delete-btn" data-id="{{ $holiday->id }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                                <div class="holiday-body">
                                    <div class="holiday-date-big">{{ $holiday->date->format('d') }}</div>
                                    <div class="holiday-month">{{ $holiday->date->format('F') }}</div>
                                    <div class="holiday-day">{{ $holiday->date->format('l') }}</div>
                                    @if ($holiday->description)
                                        <p class="text-muted small mt-3 mb-0">{{ $holiday->description }}</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="empty-state" style="grid-column: 1/-1;">
                                <i class="fa fa-calendar-times"></i>
                                <p>No holidays defined for {{ $year }}. Add your first!</p>
                            </div>
                        @endforelse
                    </div>
                    <div class="px-4 py-3 border-top">
                        {{ $holidays->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="holidayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header gradient"
                    style="background: linear-gradient(135deg, #ef4444, #dc2626) !important;">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fa fa-calendar-alt"></i>
                        <span>Add Holiday</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="holidayForm" action="{{ route('hr.holidays.store') }}" method="POST" data-ajax-validate="true">
                    @csrf
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="modal-body">
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-tag"></i> Holiday Name</label>
                            <input type="text" name="name" id="name" class="form-control"
                                placeholder="e.g., Eid ul Fitr" required>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-calendar"></i> Date</label>
                            <input type="date" name="date" id="date" class="form-control" required>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-tag"></i> Type</label>
                            <select name="type" id="type" class="form-select" required>
                                <option value="public">Public Holiday</option>
                                <option value="company">Company Holiday</option>
                                <option value="optional">Optional Holiday</option>
                            </select>
                        </div>
                        <div class="form-group-modern">
                            <label class="form-label"><i class="fa fa-align-left"></i> Description</label>
                            <textarea name="description" id="description" class="form-control" rows="2"
                                placeholder="Optional description"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer-modern">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                            <i class="fa fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-save"
                            style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                            <i class="fa fa-check"></i>
                            <span>Save Holiday</span>
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
            $('#yearSelect').change(function() {
                window.location.href = '{{ route('hr.holidays.index') }}?year=' + $(this).val();
            });

            $('#createBtn').click(function() {
                $('#holidayForm')[0].reset();
                $('#edit_id').val('');
                $('#modalTitle').html('<i class="fa fa-calendar-alt"></i><span>Add Holiday</span>');
                $('#holidayModal').modal('show');
            });

            $(document).on('click', '.edit-btn', function() {
                $('#edit_id').val($(this).data('id'));
                $('#name').val($(this).data('name'));
                $('#date').val($(this).data('date'));
                $('#type').val($(this).data('type'));
                $('#description').val($(this).data('description'));
                $('#modalTitle').html('<i class="fa fa-pen"></i><span>Edit Holiday</span>');
                $('#holidayModal').modal('show');
            });

            $(document).on('click', '.delete-btn', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Holiday?',
                    text: 'This cannot be undone!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Yes, delete!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/hr/holidays/' + id,
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

            $('#holidaySearch').on('input', function() {
                var q = $(this).val().toLowerCase();
                $('.holiday-card').each(function() {
                    var name = $(this).data('name') || '';
                    $(this).toggle(name.indexOf(q) !== -1);
                });
                $('#holidayCount').text($('.holiday-card:visible').length + ' holidays');
            });

            $('#refreshBtn').click(() => location.reload());

            // Custom submit handler removed - using data-ajax-validate
        });
    </script>
@endsection
