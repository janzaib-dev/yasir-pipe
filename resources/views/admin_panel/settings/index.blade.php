@extends('admin_panel.layout.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">ERP Settings</h3>
                    </div>
                    <div class="card-body">
                        <!-- Advanced Settings & Navigation -->
                        <div class="mb-4 pb-3 border-bottom">
                            <h6 class="text-muted mb-3 font-weight-bold text-uppercase"
                                style="font-size: 0.8rem; letter-spacing: 1px;">Advanced Actions</h6>
                            <div class="d-flex flex-wrap">
                                <a href="{{ route('settings.return-policy') }}"
                                    class="btn btn-outline-primary mr-2 mb-2 shadow-sm">
                                    <i class="fas fa-undo-alt mr-2"></i> Return Policy
                                </a>
                                <a href="{{ route('settings.return-approvers') }}"
                                    class="btn btn-outline-info mr-2 mb-2 shadow-sm">
                                    <i class="fas fa-user-shield mr-2"></i> Return Approvers
                                </a>
                                <a href="#" class="btn btn-outline-dark mr-2 mb-2 shadow-sm">
                                    <i class="fas fa-exchange-alt mr-2"></i> Switch Account
                                </a>
                            </div>
                        </div>

                        <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="company-tab" data-toggle="tab" href="#company"
                                    role="tab">
                                    <i class="fas fa-building"></i> Company
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="sales-tab" data-toggle="tab" href="#sales" role="tab">
                                    <i class="fas fa-shopping-cart"></i> Sales
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="inventory-tab" data-toggle="tab" href="#inventory" role="tab">
                                    <i class="fas fa-boxes"></i> Inventory
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="accounting-tab" data-toggle="tab" href="#accounting" role="tab">
                                    <i class="fas fa-calculator"></i> Accounting
                                </a>
                            </li>
                        </ul>

                        <form id="settingsForm" class="mt-4">
                            @csrf
                            <div class="tab-content" id="settingsTabContent">
                                <!-- Company Tab -->
                                <div class="tab-pane fade show active" id="company" role="tabpanel">
                                    @if (isset($settings['company']))
                                        @foreach ($settings['company'] as $setting)
                                            <div class="form-group">
                                                <label>{{ $setting['label'] }}</label>
                                                @if ($setting['type'] === 'text')
                                                    <textarea name="settings[{{ $setting['key'] }}]" class="form-control" rows="3">{{ $setting['value'] }}</textarea>
                                                @else
                                                    <input type="text" name="settings[{{ $setting['key'] }}]"
                                                        class="form-control" value="{{ $setting['value'] }}">
                                                @endif
                                                @if ($setting['description'])
                                                    <small
                                                        class="form-text text-muted">{{ $setting['description'] }}</small>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                <!-- Sales Tab -->
                                <div class="tab-pane fade" id="sales" role="tabpanel">
                                    @if (isset($settings['sales']))
                                        @foreach ($settings['sales'] as $setting)
                                            <div class="form-group">
                                                <label>{{ $setting['label'] }}</label>
                                                @if ($setting['type'] === 'text')
                                                    <textarea name="settings[{{ $setting['key'] }}]" class="form-control" rows="3">{{ $setting['value'] }}</textarea>
                                                @elseif($setting['type'] === 'integer')
                                                    <input type="number" name="settings[{{ $setting['key'] }}]"
                                                        class="form-control" value="{{ $setting['value'] }}">
                                                @else
                                                    <input type="text" name="settings[{{ $setting['key'] }}]"
                                                        class="form-control" value="{{ $setting['value'] }}">
                                                @endif
                                                @if ($setting['description'])
                                                    <small
                                                        class="form-text text-muted">{{ $setting['description'] }}</small>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                <!-- Inventory Tab -->
                                <div class="tab-pane fade" id="inventory" role="tabpanel">
                                    @if (isset($settings['inventory']))
                                        @foreach ($settings['inventory'] as $setting)
                                            <div class="form-group">
                                                <label>{{ $setting['label'] }}</label>
                                                <input type="number" name="settings[{{ $setting['key'] }}]"
                                                    class="form-control" value="{{ $setting['value'] }}">
                                                @if ($setting['description'])
                                                    <small
                                                        class="form-text text-muted">{{ $setting['description'] }}</small>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                <!-- Accounting Tab -->
                                <div class="tab-pane fade" id="accounting" role="tabpanel">
                                    @if (isset($settings['accounting']))
                                        @foreach ($settings['accounting'] as $setting)
                                            <div class="form-group">
                                                <label>{{ $setting['label'] }}</label>
                                                <input type="text" name="settings[{{ $setting['key'] }}]"
                                                    class="form-control" value="{{ $setting['value'] }}">
                                                @if ($setting['description'])
                                                    <small
                                                        class="form-text text-muted">{{ $setting['description'] }}</small>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#settingsForm').on('submit', function(e) {
                    e.preventDefault();

                    $.ajax({
                        url: '{{ route('settings.update') }}',
                        method: 'POST',
                        data: $(this).serialize(),
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                timer: 2000
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Failed to update settings',
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
