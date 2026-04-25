@extends('admin_panel.layout.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Notifications</h3>
                        <button class="btn btn-sm btn-primary" id="markAllRead">
                            <i class="fas fa-check-double"></i> Mark All as Read
                        </button>
                    </div>
                    <div class="card-body">
                        @if ($notifications->count() > 0)
                            <div class="list-group">
                                @foreach ($notifications as $notification)
                                    <div class="list-group-item {{ $notification->is_read ? '' : 'bg-light' }}"
                                        data-id="{{ $notification->id }}">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1">
                                                @if ($notification->type === 'critical')
                                                    <span class="badge badge-danger">Critical</span>
                                                @elseif($notification->type === 'warning')
                                                    <span class="badge badge-warning">Warning</span>
                                                @else
                                                    <span class="badge badge-info">Info</span>
                                                @endif
                                                {{ $notification->title }}
                                            </h5>
                                            <small>{{ $notification->created_at->diffForHumans() }}</small>
                                        </div>
                                        <p class="mb-1">{{ $notification->message }}</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            @if ($notification->action_url)
                                                <a href="{{ $notification->action_url }}"
                                                    class="btn btn-sm btn-outline-primary">View Details</a>
                                            @else
                                                <span></span>
                                            @endif
                                            @if (!$notification->is_read)
                                                <button class="btn btn-sm btn-success mark-read"
                                                    data-id="{{ $notification->id }}">
                                                    <i class="fas fa-check"></i> Mark as Read
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-3">
                                {{ $notifications->links() }}
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No notifications</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            // Mark single notification as read
            $('.mark-read').on('click', function() {
                const id = $(this).data('id');
                const $item = $(this).closest('.list-group-item');

                $.ajax({
                    url: `/notifications/${id}/read`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function() {
                        $item.removeClass('bg-light');
                        $(this).remove();
                        updateNotificationBadge();
                    }
                });
            });

            // Mark all as read
            $('#markAllRead').on('click', function() {
                $.ajax({
                    url: '{{ route('notifications.readAll') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function() {
                        $('.list-group-item').removeClass('bg-light');
                        $('.mark-read').remove();
                        updateNotificationBadge();
                        Swal.fire({
                            icon: 'success',
                            title: 'All notifications marked as read',
                            timer: 2000
                        });
                    }
                });
            });

            function updateNotificationBadge() {
                $.get('{{ route('notifications.count') }}', function(data) {
                    $('.notification-badge').text(data.count);
                    if (data.count === 0) {
                        $('.notification-badge').hide();
                    }
                });
            }
        });
    </script>
@endsection
