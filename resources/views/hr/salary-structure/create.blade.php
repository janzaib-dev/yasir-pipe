@extends('admin_panel.layout.app')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3>{{ isset($salaryStructure->id) ? 'Edit Salary Structure' : 'Create New Salary Structure' }}
                            </h3>
                            <a href="{{ route('hr.salary-structure.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to List
                            </a>
                        </div>

                        @if ($readOnly ?? false)
                            <div class="alert alert-warning mb-3">
                                <i class="fa fa-eye"></i> <strong>View Only Mode:</strong> You have view permission only.
                                All fields are disabled.
                            </div>
                        @endif

                        @if (($hasAssignments ?? false) && isset($salaryStructure->id))
                            <div class="alert alert-warning mb-3">
                                <i class="fa fa-exclamation-triangle"></i> <strong>Warning:</strong> This structure is
                                currently assigned to active employees.
                                <ul class="mb-0 mt-1">
                                    <li>Changes <strong>WILL</strong> affect currently assigned employees immediately.</li>
                                    <li>Please review changes carefully.</li>
                                    <li>Payroll type cannot be changed.</li>
                                </ul>
                            </div>
                        @endif

                        <div class="border mt-1 shadow rounded p-4" style="background-color: white;">
                            <form id="salaryForm"
                                action="{{ isset($salaryStructure->id) ? route('hr.salary-structure.update-template', $salaryStructure->id) : route('hr.salary-structure.store') }}"
                                method="POST" data-ajax-validate="true">
                                @csrf
                                @if (isset($salaryStructure->id))
                                    @method('PUT')
                                @endif

                                <div class="row">
                                    <!-- Info Alert -->
                                    <div class="col-md-12 mb-3">
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i>
                                            <strong>{{ isset($salaryStructure->id) ? 'Editing Standalone Salary Structure' : 'Creating Standalone Salary Structure' }}</strong><br>
                                            {{ isset($salaryStructure->id) ? 'Update the compensation details below.' : 'After creating this structure, you can assign it to one or multiple employees from the structures list.' }}
                                        </div>
                                    </div>

                                    <!-- Structure Name -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Structure Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required
                                            placeholder="e.g. Senior Developer Package, Daily Worker A"
                                            value="{{ $salaryStructure->name ?? '' }}"
                                            {{ $readOnly ?? false ? 'disabled' : '' }}>
                                    </div>

                                    <!-- Salary Type UI -->
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Structure Type <span
                                                class="text-danger">*</span></label>
                                        @php
                                            $uiType = 'monthly'; // default
                                            if (isset($salaryStructure->id)) {
                                                if ($salaryStructure->salary_type === 'commission') {
                                                    $uiType = 'commission';
                                                } elseif (
                                                    $salaryStructure->salary_type === 'both' ||
                                                    $salaryStructure->salary_type === 'monthly_commission'
                                                ) {
                                                    $uiType = 'monthly_commission';
                                                } elseif ($salaryStructure->use_daily_wages) {
                                                    if ($salaryStructure->base_salary > 0) {
                                                        $uiType = 'monthly_daily';
                                                    } else {
                                                        $uiType = 'daily';
                                                    }
                                                }
                                            }
                                        @endphp
                                        <select id="ui_structure_type" class="form-select" required
                                            {{ ($readOnly ?? false) || ($hasAssignments ?? false) ? 'disabled' : '' }}>
                                            <option value="monthly" {{ $uiType == 'monthly' ? 'selected' : '' }}>Monthly
                                                Salary Only</option>
                                            <option value="daily" {{ $uiType == 'daily' ? 'selected' : '' }}>Daily Wages
                                                Only</option>
                                            <option value="monthly_daily"
                                                {{ $uiType == 'monthly_daily' ? 'selected' : '' }}>Monthly Salary + Daily
                                                Wages</option>
                                            <option value="commission" {{ $uiType == 'commission' ? 'selected' : '' }}>
                                                Commission Only</option>
                                            <option value="monthly_commission"
                                                {{ $uiType == 'monthly_commission' ? 'selected' : '' }}>Monthly Salary +
                                                Commission</option>
                                        </select>

                                        <!-- Hidden Inputs for Backend Mapping -->
                                        <input type="hidden" name="salary_type" id="salary_type"
                                            value="{{ $salaryStructure->salary_type ?? 'salary' }}">
                                        <input type="hidden" name="use_daily_wages" id="use_daily_wages_hidden"
                                            value="{{ $salaryStructure->use_daily_wages ?? '0' }}">
                                    </div>


                                    <!-- Base Salary -->
                                    <div class="col-md-4 mb-3" id="base_salary_container">
                                        <label class="form-label fw-bold">Base Salary</label>
                                        <input type="number" step="0.01" name="base_salary" id="base_salary"
                                            class="form-control" value="{{ $salaryStructure->base_salary ?? 0 }}"
                                            {{ $readOnly ?? false ? 'disabled' : '' }}>
                                    </div>

                                    <!-- Daily Wages (Conditional) -->
                                    <div class="col-md-4 mb-3" id="daily_wages_container"
                                        style="display:
                                                       none;">
                                        <label class="form-label fw-bold">Daily Wage Rate</label>
                                        <!-- Hidden Toggle for backward compatibility/JS logic -->
                                        <div class="form-check form-switch d-none">
                                            <input class="form-check-input" type="checkbox" id="use_daily_wages"
                                                value="1">
                                        </div>

                                        <input type="number" step="0.01" name="daily_wages" id="daily_wages"
                                            class="form-control" value="{{ $salaryStructure->daily_wages ?? '' }}"
                                            placeholder="Daily Rate"
                                            {{ ($readOnly ?? false) || !($salaryStructure->use_daily_wages ?? false) ? 'disabled' : '' }}>
                                    </div>

                                    <!-- Leave Salary Per Day -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Leave Salary Per Day</label>
                                        <input type="number" step="0.01" name="leave_salary_per_day"
                                            class="form-control" value="{{ $salaryStructure->leave_salary_per_day ?? '' }}"
                                            placeholder="For leave deductions" {{ $readOnly ?? false ? 'disabled' : '' }}>
                                    </div>

                                    <!-- Commission Settings -->
                                    <div class="col-md-12" id="commission_section" style="display: none;">
                                        <hr>
                                        <div class="card border-primary mb-3">
                                            <div class="card-header bg-primary text-white">
                                                <i class="fa fa-chart-line"></i> Commission Settings
                                            </div>
                                            <div class="card-body">
                                                <!-- Monthly Sales Target -->
                                                <div class="row mb-4">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold">
                                                            <i class="fa fa-bullseye text-danger"></i> Total Monthly Sales
                                                            Target
                                                        </label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">Rs.</span>
                                                            <input type="number" step="0.01" name="sales_target"
                                                                id="sales_target" class="form-control form-control-lg"
                                                                value="{{ $salaryStructure->sales_target ?? '' }}"
                                                                placeholder="e.g., 50000">
                                                        </div>
                                                        <small class="text-muted">Monthly sales target for the
                                                            employee</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold">
                                                            <i class="fa fa-toggle-on text-success"></i> Commission Type
                                                        </label>
                                                        <div class="btn-group w-100" role="group">
                                                            <input type="radio" class="btn-check"
                                                                name="commission_mode" id="mode_flat" value="flat"
                                                                {{ !$salaryStructure->commission_tiers || count($salaryStructure->commission_tiers ?? []) == 0 ? 'checked' : '' }}>
                                                            <label class="btn btn-outline-info" for="mode_flat">
                                                                <i class="fa fa-percent"></i> Flat Commission
                                                            </label>

                                                            <input type="radio" class="btn-check"
                                                                name="commission_mode" id="mode_tiered" value="tiered"
                                                                {{ $salaryStructure->commission_tiers && count($salaryStructure->commission_tiers ?? []) > 0 ? 'checked' : '' }}>
                                                            <label class="btn btn-outline-warning" for="mode_tiered">
                                                                <i class="fa fa-layer-group"></i> Tiered Commission
                                                            </label>
                                                        </div>
                                                        <small class="text-muted">Choose one: Flat % or Tiered
                                                            rates</small>
                                                    </div>
                                                </div>

                                                <!-- Flat Commission Section -->
                                                <div id="flat_commission_section" class="card border-info mb-3"
                                                    style="display: none;">
                                                    <div class="card-header bg-info text-white">
                                                        <i class="fa fa-percent"></i> Flat Commission Rate
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Commission
                                                                    Percentage</label>
                                                                <div class="input-group input-group-lg">
                                                                    <input type="number" step="0.01" min="0"
                                                                        max="100" name="commission_percentage"
                                                                        id="commission_percentage" class="form-control"
                                                                        value="{{ $salaryStructure->commission_percentage ?? '' }}"
                                                                        placeholder="e.g., 5">
                                                                    <span class="input-group-text">%</span>
                                                                </div>
                                                                <small class="text-muted">This % applies to all
                                                                    sales</small>
                                                            </div>
                                                            <div class="col-md-6 d-flex align-items-center">
                                                                <div class="alert alert-info mb-0 w-100">
                                                                    <i class="fa fa-info-circle"></i>
                                                                    <strong>Example:</strong> 5% on Rs. 50,000 sales = Rs.
                                                                    2,500 commission
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Tiered Commission Section -->
                                                <div id="tiered_commission_section" class="card border-warning"
                                                    style="display: none;">
                                                    <div
                                                        class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                                                        <span><i class="fa fa-layer-group"></i> <strong>Commission
                                                                Tiers</strong></span>
                                                        <button type="button" class="btn btn-dark btn-sm"
                                                            id="addCommissionTier">
                                                            <i class="fa fa-plus"></i> Add Tier
                                                        </button>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="alert alert-info py-2 mb-3">
                                                            <i class="fa fa-info-circle"></i>
                                                            <strong>How it works:</strong> Define commission % for each
                                                            sales range.
                                                            <br>
                                                            <small>Example: 2% for 0-10000, 5% for 10001-30000, 8% for
                                                                30001-50000</small>
                                                        </div>

                                                        <!-- Tier Headers -->
                                                        <div class="row mb-2 fw-bold text-muted" id="tier_headers"
                                                            style="display: none;">
                                                            <div class="col-md-1 text-center">#</div>
                                                            <div class="col-md-3">Commission %</div>
                                                            <div class="col-md-4">Sales Range</div>
                                                            <div class="col-md-3">Tier Covers</div>
                                                            <div class="col-md-1"></div>
                                                        </div>

                                                        <div id="commission_tiers_container">
                                                            @if ($salaryStructure->commission_tiers)
                                                                @php $prevAmount = 0; @endphp
                                                                @foreach ($salaryStructure->commission_tiers as $index => $tier)
                                                                    <div class="row mb-2 commission-tier-row align-items-center"
                                                                        data-index="{{ $index }}">
                                                                        <div class="col-md-1 text-center">
                                                                            <span
                                                                                class="badge bg-secondary tier-number">{{ $index + 1 }}</span>
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <div class="input-group">
                                                                                <input type="number" step="0.01"
                                                                                    min="0" max="100"
                                                                                    name="commission_tiers[{{ $index }}][percentage]"
                                                                                    class="form-control tier-percentage"
                                                                                    placeholder="e.g., 5"
                                                                                    value="{{ $tier['percentage'] ?? '' }}">
                                                                                <span class="input-group-text">%</span>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="input-group">
                                                                                <span class="input-group-text">Up to
                                                                                    Rs.</span>
                                                                                <input type="number" step="0.01"
                                                                                    min="1"
                                                                                    name="commission_tiers[{{ $index }}][upto_amount]"
                                                                                    class="form-control tier-upto"
                                                                                    placeholder="e.g., 10000"
                                                                                    value="{{ $tier['upto_amount'] ?? '' }}">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <span
                                                                                class="tier-range-display badge bg-light text-dark">
                                                                                Rs. {{ number_format($prevAmount) }} -
                                                                                {{ number_format($tier['upto_amount'] ?? 0) }}
                                                                            </span>
                                                                        </div>
                                                                        <div class="col-md-1">
                                                                            <button type="button"
                                                                                class="btn btn-outline-danger btn-sm remove-row">
                                                                                <i class="fa fa-times"></i>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                    @php $prevAmount = $tier['upto_amount'] ?? 0; @endphp
                                                                @endforeach
                                                            @endif
                                                        </div>

                                                        <div id="no_tiers_message" class="text-center text-muted py-3"
                                                            style="{{ $salaryStructure->commission_tiers && count($salaryStructure->commission_tiers) > 0 ? 'display:none;' : '' }}">
                                                            <i class="fa fa-info-circle"></i> No commission tiers defined.
                                                            <br>Click "Add Tier" or use flat commission % above.
                                                        </div>

                                                        <div id="tier_validation_error"
                                                            class="alert alert-danger py-2 mt-2" style="display: none;">
                                                            <i class="fa fa-exclamation-triangle"></i> <span
                                                                id="tier_error_text"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Allowances Section -->
                                    <div class="col-md-12 mt-3">
                                        <div class="card border-success">
                                            <div class="card-header bg-success text-white">
                                                <i class="fa fa-gift"></i> <strong>Allowances</strong>
                                            </div>
                                            <div class="card-body">
                                                <div class="alert alert-light py-2 mb-3">
                                                    <i class="fa fa-info-circle text-success"></i>
                                                    Add monthly allowances like Housing, Transport, Medical, Food, etc.
                                                </div>

                                                <!-- Allowances Header -->
                                                <div class="row mb-2 fw-bold text-muted" id="allowance_headers"
                                                    style="{{ $salaryStructure->allowances && count($salaryStructure->allowances) > 0 ? '' : 'display:none;' }}">
                                                    <div class="col-md-1 text-center">#</div>
                                                    <div class="col-md-1 text-center">Active</div>
                                                    <div class="col-md-4">Allowance Name</div>
                                                    <div class="col-md-4">Amount (Rs.)</div>
                                                    <div class="col-md-2"></div>
                                                </div>

                                                <div id="allowances_container">
                                                    @if ($salaryStructure->allowances)
                                                        @foreach ($salaryStructure->allowances as $index => $allowance)
                                                            <div class="row mb-2 allowance-row align-items-center">
                                                                <div class="col-md-1 text-center">
                                                                    <span
                                                                        class="badge bg-success allowance-number">{{ $index + 1 }}</span>
                                                                </div>
                                                                <div class="col-md-1 text-center">
                                                                    <div
                                                                        class="form-check form-switch d-flex justify-content-center">
                                                                        <input class="form-check-input allowance-active"
                                                                            type="checkbox"
                                                                            name="allowances[{{ $index }}][is_active]"
                                                                            value="1"
                                                                            {{ !isset($allowance['is_active']) || $allowance['is_active'] ? 'checked' : '' }}
                                                                            {{ $readOnly ?? false ? 'disabled' : '' }}>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <input type="text"
                                                                        name="allowances[{{ $index }}][name]"
                                                                        class="form-control"
                                                                        placeholder="e.g., Housing Allowance"
                                                                        value="{{ $allowance['name'] ?? '' }}">
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text">Rs.</span>
                                                                        <input type="number" step="0.01"
                                                                            name="allowances[{{ $index }}][amount]"
                                                                            class="form-control allowance-amount"
                                                                            placeholder="Amount"
                                                                            value="{{ $allowance['amount'] ?? '' }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <button type="button"
                                                                        class="btn btn-outline-danger btn-sm remove-row">
                                                                        <i class="fa fa-times"></i> Remove
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>

                                                <div id="no_allowances_message" class="text-center text-muted py-3"
                                                    style="{{ $salaryStructure->allowances && count($salaryStructure->allowances) > 0 ? 'display:none;' : '' }}">
                                                    <i class="fa fa-info-circle"></i> No allowances added yet.
                                                </div>

                                                <!-- Add Allowance Button at Bottom -->
                                                <div class="text-center mt-3 pt-3 border-top">
                                                    <button type="button" class="btn btn-success" id="addAllowance">
                                                        <i class="fa fa-plus-circle"></i> Add Allowance
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Deductions Section -->
                                    <div class="col-md-12 mt-3">
                                        <div class="card border-danger">
                                            <div class="card-header bg-danger text-white">
                                                <i class="fa fa-minus-circle"></i> <strong>Deductions</strong>
                                            </div>
                                            <div class="card-body">
                                                <div class="alert alert-light py-2 mb-3">
                                                    <i class="fa fa-info-circle text-danger"></i>
                                                    Add fixed monthly deductions (e.g., Tax, Insurance).
                                                </div>

                                                <!-- Deductions Header -->
                                                <div class="row mb-2 fw-bold text-muted" id="deduction_headers"
                                                    style="{{ $salaryStructure->deductions && count($salaryStructure->deductions) > 0 ? '' : 'display:none;' }}">
                                                    <div class="col-md-1 text-center">#</div>
                                                    <div class="col-md-1 text-center">Active</div>
                                                    <div class="col-md-4">Deduction Name</div>
                                                    <div class="col-md-4">Amount (Rs.)</div>
                                                    <div class="col-md-2"></div>
                                                </div>

                                                <div id="deductions_container">
                                                    @if ($salaryStructure->deductions)
                                                        @foreach ($salaryStructure->deductions as $index => $deduction)
                                                            <div class="row mb-2 deduction-row align-items-center">
                                                                <div class="col-md-1 text-center">
                                                                    <span
                                                                        class="badge bg-danger deduction-number">{{ $index + 1 }}</span>
                                                                </div>
                                                                <div class="col-md-1 text-center">
                                                                    <div
                                                                        class="form-check form-switch d-flex justify-content-center">
                                                                        <input class="form-check-input deduction-active"
                                                                            type="checkbox"
                                                                            name="deductions[{{ $index }}][is_active]"
                                                                            value="1"
                                                                            {{ !isset($deduction['is_active']) || $deduction['is_active'] ? 'checked' : '' }}
                                                                            {{ $readOnly ?? false ? 'disabled' : '' }}>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <input type="text"
                                                                        name="deductions[{{ $index }}][name]"
                                                                        class="form-control"
                                                                        placeholder="e.g., Income Tax"
                                                                        value="{{ $deduction['name'] ?? '' }}">
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text">Rs.</span>
                                                                        <input type="number" step="0.01"
                                                                            name="deductions[{{ $index }}][amount]"
                                                                            class="form-control deduction-amount"
                                                                            placeholder="Amount"
                                                                            value="{{ $deduction['amount'] ?? '' }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <button type="button"
                                                                        class="btn btn-outline-danger btn-sm remove-row">
                                                                        <i class="fa fa-times"></i> Remove
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>

                                                <div id="no_deductions_message" class="text-center text-muted py-3"
                                                    style="{{ $salaryStructure->deductions && count($salaryStructure->deductions) > 0 ? 'display:none;' : '' }}">
                                                    <i class="fa fa-info-circle"></i> No deductions added yet.
                                                </div>

                                                <!-- Add Deduction Button -->
                                                <div class="text-center mt-3 pt-3 border-top">
                                                    <button type="button" class="btn btn-danger" id="addDeduction">
                                                        <i class="fa fa-plus-circle"></i> Add Deduction
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <!-- Attendance Deduction Rules -->
                                    <div class="col-md-12 mt-3" id="attendance_rules_section" style="display:none;">
                                        <div class="card border-secondary">
                                            <div class="card-header bg-secondary text-white">
                                                <i class="fa fa-clock"></i> <strong>Attendance Deduction Rules (Daily
                                                    Wage)</strong>
                                            </div>
                                            <div class="card-body">
                                                <div class="alert alert-info py-2 mb-3">
                                                    <i class="fa fa-info-circle"></i>
                                                    <strong>How it works:</strong> Define deduction rules for late check-in
                                                    and early check-out.
                                                    <br><small>
                                                        • Ranges are continuous: Each rule starts from where the previous
                                                        one ended.<br>
                                                        • Only enter the <strong>Max Minutes</strong> for each rule. The Min
                                                        is calculated automatically.<br>
                                                        • Leave Max empty for the last rule to make it apply to <strong>any
                                                            delay ≥ Min</strong> (open-ended).
                                                    </small>
                                                </div>

                                                <div class="form-check form-switch mb-4">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="carry_forward_deductions" name="carry_forward_deductions"
                                                        value="1"
                                                        {{ $salaryStructure->carry_forward_deductions ? 'checked' : '' }}
                                                        {{ $readOnly ?? false ? 'disabled' : '' }}>
                                                    <label class="form-check-label fw-bold"
                                                        for="carry_forward_deductions">
                                                        Carry forward remaining deduction to next day if daily wage is
                                                        insufficient
                                                    </label>
                                                </div>

                                                <div class="row">
                                                    <!-- Late Rules -->
                                                    <div class="col-md-6 border-end">
                                                        <h6 class="fw-bold text-danger"><i class="fa fa-clock"></i> Late
                                                            Check-in Rules</h6>

                                                        <!-- Headers -->
                                                        <div class="row mb-2 fw-bold text-muted small"
                                                            id="late_rules_headers" style="display:none;">
                                                            <div class="col-3">Range (min)</div>
                                                            <div class="col-2">Max</div>
                                                            <div class="col-2">Type</div>
                                                            <div class="col-3">Amount</div>
                                                            <div class="col-2"></div>
                                                        </div>

                                                        <div id="late_rules_container">
                                                            @if (isset($salaryStructure->attendance_deduction_policy['late_rules']))
                                                                @foreach ($salaryStructure->attendance_deduction_policy['late_rules'] as $index => $rule)
                                                                    <div
                                                                        class="row mb-2 rule-row late-rule-row align-items-center">
                                                                        <div class="col-3">
                                                                            <span
                                                                                class="badge bg-light text-dark range-display">
                                                                                {{ $rule['min_minutes'] }} -
                                                                                {{ $rule['max_minutes'] ?? '∞' }}
                                                                            </span>
                                                                            <input type="hidden"
                                                                                name="late_rules[{{ $index }}][min_minutes]"
                                                                                class="rule-min"
                                                                                value="{{ $rule['min_minutes'] }}">
                                                                        </div>
                                                                        <div class="col-2">
                                                                            <input type="number"
                                                                                name="late_rules[{{ $index }}][max_minutes]"
                                                                                class="form-control form-control-sm rule-max"
                                                                                placeholder="{{ $rule['max_minutes'] ? '' : '∞' }}"
                                                                                value="{{ $rule['max_minutes'] }}">
                                                                        </div>
                                                                        <div class="col-2">
                                                                            <select
                                                                                name="late_rules[{{ $index }}][type]"
                                                                                class="form-select form-select-sm">
                                                                                <option value="fixed"
                                                                                    {{ ($rule['type'] ?? 'fixed') == 'fixed' ? 'selected' : '' }}>
                                                                                    Rs.</option>
                                                                                <option value="percentage"
                                                                                    {{ ($rule['type'] ?? 'fixed') == 'percentage' ? 'selected' : '' }}>
                                                                                    %</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-3">
                                                                            <input type="number" step="0.01"
                                                                                name="late_rules[{{ $index }}][amount]"
                                                                                class="form-control form-control-sm rule-amount"
                                                                                placeholder="Amt"
                                                                                value="{{ $rule['amount'] }}">
                                                                        </div>
                                                                        <div class="col-2">
                                                                            <button type="button"
                                                                                class="btn btn-outline-danger btn-sm remove-rule"><i
                                                                                    class="fa fa-times"></i></button>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            @endif
                                                        </div>

                                                        <div id="late_rules_validation"
                                                            class="alert alert-danger py-1 mt-2 small"
                                                            style="display:none;"></div>

                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-secondary mt-2"
                                                            id="addLateRule"><i class="fa fa-plus"></i> Add Rule</button>
                                                    </div>

                                                    <!-- Early Rules -->
                                                    <div class="col-md-6">
                                                        <h6 class="fw-bold text-danger"><i class="fa fa-sign-out-alt"></i>
                                                            Early Check-out Rules</h6>

                                                        <!-- Headers -->
                                                        <div class="row mb-2 fw-bold text-muted small"
                                                            id="early_rules_headers" style="display:none;">
                                                            <div class="col-3">Range (min)</div>
                                                            <div class="col-2">Max</div>
                                                            <div class="col-2">Type</div>
                                                            <div class="col-3">Amount</div>
                                                            <div class="col-2"></div>
                                                        </div>

                                                        <div id="early_rules_container">
                                                            @if (isset($salaryStructure->attendance_deduction_policy['early_rules']))
                                                                @foreach ($salaryStructure->attendance_deduction_policy['early_rules'] as $index => $rule)
                                                                    <div
                                                                        class="row mb-2 rule-row early-rule-row align-items-center">
                                                                        <div class="col-3">
                                                                            <span
                                                                                class="badge bg-light text-dark range-display">
                                                                                {{ $rule['min_minutes'] }} -
                                                                                {{ $rule['max_minutes'] ?? '∞' }}
                                                                            </span>
                                                                            <input type="hidden"
                                                                                name="early_rules[{{ $index }}][min_minutes]"
                                                                                class="rule-min"
                                                                                value="{{ $rule['min_minutes'] }}">
                                                                        </div>
                                                                        <div class="col-2">
                                                                            <input type="number"
                                                                                name="early_rules[{{ $index }}][max_minutes]"
                                                                                class="form-control form-control-sm rule-max"
                                                                                placeholder="{{ $rule['max_minutes'] ? '' : '∞' }}"
                                                                                value="{{ $rule['max_minutes'] }}">
                                                                        </div>
                                                                        <div class="col-2">
                                                                            <select
                                                                                name="early_rules[{{ $index }}][type]"
                                                                                class="form-select form-select-sm">
                                                                                <option value="fixed"
                                                                                    {{ ($rule['type'] ?? 'fixed') == 'fixed' ? 'selected' : '' }}>
                                                                                    Rs.</option>
                                                                                <option value="percentage"
                                                                                    {{ ($rule['type'] ?? 'fixed') == 'percentage' ? 'selected' : '' }}>
                                                                                    %</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-3">
                                                                            <input type="number" step="0.01"
                                                                                name="early_rules[{{ $index }}][amount]"
                                                                                class="form-control form-control-sm rule-amount"
                                                                                placeholder="Amt"
                                                                                value="{{ $rule['amount'] }}">
                                                                        </div>
                                                                        <div class="col-2">
                                                                            <button type="button"
                                                                                class="btn btn-outline-danger btn-sm remove-rule"><i
                                                                                    class="fa fa-times"></i></button>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            @endif
                                                        </div>

                                                        <div id="early_rules_validation"
                                                            class="alert alert-danger py-1 mt-2 small"
                                                            style="display:none;"></div>

                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-secondary mt-2"
                                                            id="addEarlyRule"><i class="fa fa-plus"></i> Add Rule</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Summary Section -->
                                    <div class="col-md-12 mt-3">
                                        <div class="card bg-light border-primary shadow-sm">
                                            <div class="card-body">
                                                <div class="row text-center">
                                                    <div class="col-md-3 border-end">
                                                        <small class="text-muted d-block uppercase">Base / Daily</small>
                                                        <span class="h5 text-primary" id="summary_base">0</span>
                                                    </div>
                                                    <div class="col-md-3 border-end">
                                                        <small class="text-muted d-block uppercase">Allowances</small>
                                                        <span class="h5 text-success" id="summary_allowances">0</span>
                                                    </div>
                                                    <div class="col-md-3 border-end">
                                                        <small class="text-muted d-block uppercase">Deductions</small>
                                                        <span class="h5 text-danger" id="summary_deductions">0</span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <small class="text-muted d-block uppercase fw-bold">Est. Net
                                                            Salary</small>
                                                        <span class="h4 fw-bold text-dark" id="summary_net">0</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit -->
                                    @if (!($readOnly ?? false))
                                        <div class="col-md-12 mt-4">
                                            <hr>
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fa fa-save"></i> Save Salary Structure
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            var allowanceIndex = {{ count($salaryStructure->allowances ?? []) }};
            var deductionIndex = {{ count($salaryStructure->deductions ?? []) }};
            var lateRuleIndex = {{ count($salaryStructure->attendance_deduction_policy['late_rules'] ?? []) }};
            var earlyRuleIndex = {{ count($salaryStructure->attendance_deduction_policy['early_rules'] ?? []) }};
            var commissionTierIndex = {{ count($salaryStructure->commission_tiers ?? []) }};
            var isReadOnly = {{ $readOnly ?? false ? 'true' : 'false' }};

            // If read-only mode, disable all inputs and hide action buttons
            if (isReadOnly) {
                $('#salaryForm input, #salaryForm select, #salaryForm textarea').prop('disabled', true);
                $('#salaryForm .remove-row').hide();
                $('#addAllowance, #addCommissionTier').hide();
                $('input[name="commission_mode"]').prop('disabled', true);
            }

            var isReadOnly = {{ $readOnly ?? false ? 'true' : 'false' }};

            // Start - New Structure Type Logic
            function updateStructureUI() {
                var type = $('#ui_structure_type').val();

                // Defaults
                $('#base_salary_container').hide();
                $('#daily_wages_container').hide();
                $('#commission_section').slideUp();
                $('#attendance_rules_section').slideUp();

                // Reset backend values
                var salaryType = 'salary';
                var useDaily = '0';
                var isDailyChecked = false;

                if (type === 'monthly') {
                    salaryType = 'salary';
                    $('#base_salary_container').show();
                    useDaily = '0';
                } else if (type === 'daily') {
                    salaryType = 'salary';
                    $('#daily_wages_container').show();
                    $('#attendance_rules_section').slideDown();
                    useDaily = '1';
                    isDailyChecked = true;
                } else if (type === 'monthly_daily') {
                    salaryType = 'salary';
                    $('#base_salary_container').show();
                    $('#daily_wages_container').show();
                    $('#attendance_rules_section').slideDown();
                    useDaily = '1';
                    isDailyChecked = true;
                } else if (type === 'commission') {
                    salaryType = 'commission';
                    $('#commission_section').slideDown();
                } else if (type === 'monthly_commission') {
                    salaryType = 'both';
                    $('#base_salary_container').show();
                    $('#commission_section').slideDown();
                }

                // Update Hidden Inputs
                $('#salary_type').val(salaryType);
                $('#use_daily_wages_hidden').val(useDaily);
                // Only update checkbox if not read-only to avoid UI flickering/state issues, though hidden input handles submission
                if (!isReadOnly) {
                    $('#use_daily_wages').prop('checked', isDailyChecked);
                }

                // Enable/Disable inputs to prevent validation issues
                // respect ReadOnly mode (if ReadOnly, everything stays disabled)
                if (!isReadOnly) {
                    $('#base_salary').prop('disabled', !$('#base_salary_container').is(':visible'));
                    $('#daily_wages').prop('disabled', !$('#daily_wages_container').is(':visible'));
                }

                recalculate();
            }

            // Bind Event
            $('#ui_structure_type').change(updateStructureUI);

            // Toggle between Flat and Tiered commission mode
            function toggleCommissionMode() {
                var mode = $('input[name="commission_mode"]:checked').val();
                if (mode === 'flat') {
                    $('#flat_commission_section').slideDown();
                    $('#tiered_commission_section').slideUp();
                    // Clear tiers when switching to flat
                    $('#commission_percentage').prop('disabled', false);
                } else {
                    $('#flat_commission_section').slideUp();
                    $('#tiered_commission_section').slideDown();
                    // Clear flat commission when switching to tiered
                    $('#commission_percentage').val('').prop('disabled', true);
                }
            }

            $('input[name="commission_mode"]').change(toggleCommissionMode);

            // Initial call
            updateStructureUI();
            toggleCommissionMode();


            // Update tier display
            function updateTierDisplay() {
                var tiers = $('.commission-tier-row');
                var tierCount = tiers.length;

                // Show/hide headers and no-tiers message
                if (tierCount > 0) {
                    $('#tier_headers').show();
                    $('#no_tiers_message').hide();
                } else {
                    $('#tier_headers').hide();
                    $('#no_tiers_message').show();
                }

                // Update tier numbers and range display
                var prevAmount = 0;
                tiers.each(function(index) {
                    $(this).find('.tier-number').text(index + 1);
                    var uptoAmount = parseFloat($(this).find('.tier-upto').val()) || 0;
                    $(this).find('.tier-range-display').text('Rs. ' + prevAmount.toLocaleString() + ' - ' +
                        uptoAmount.toLocaleString());
                    prevAmount = uptoAmount;
                });
            }

            // Validate tiers
            function validateTiers() {
                var salesTarget = parseFloat($('#sales_target').val()) || 0;
                var tiers = $('.commission-tier-row');
                var isValid = true;
                var errorMsg = '';
                var prevAmount = 0;

                tiers.each(function(index) {
                    var uptoAmount = parseFloat($(this).find('.tier-upto').val()) || 0;

                    // Check if tier exceeds sales target
                    if (salesTarget > 0 && uptoAmount > salesTarget) {
                        isValid = false;
                        errorMsg = 'Tier ' + (index + 1) + ' amount (Rs. ' + uptoAmount.toLocaleString() +
                            ') exceeds sales target (Rs. ' + salesTarget.toLocaleString() + ')';
                        $(this).find('.tier-upto').addClass('is-invalid');
                    } else {
                        $(this).find('.tier-upto').removeClass('is-invalid');
                    }

                    // Check if tiers are in ascending order
                    if (uptoAmount <= prevAmount && uptoAmount > 0) {
                        isValid = false;
                        errorMsg = 'Tier ' + (index + 1) + ' must be greater than previous tier (Rs. ' +
                            prevAmount.toLocaleString() + ')';
                        $(this).find('.tier-upto').addClass('is-invalid');
                    }

                    prevAmount = uptoAmount;
                });

                if (!isValid) {
                    $('#tier_validation_error').show();
                    $('#tier_error_text').text(errorMsg);
                } else {
                    $('#tier_validation_error').hide();
                }

                return isValid;
            }

            // Event listeners for validation
            $(document).on('input', '.tier-upto, #sales_target', function() {
                updateTierDisplay();
                validateTiers();
            });

            // Initial display update
            updateTierDisplay();

            // Add Commission Tier Row
            $('#addCommissionTier').click(function() {
                var tierNum = $('.commission-tier-row').length + 1;
                var html = `
                    <div class="row mb-2 commission-tier-row align-items-center" data-index="${commissionTierIndex}">
                        <div class="col-md-1 text-center">
                            <span class="badge bg-secondary tier-number">${tierNum}</span>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" name="commission_tiers[${commissionTierIndex}][percentage]" class="form-control tier-percentage" placeholder="e.g., 5">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">Up to Rs.</span>
                                <input type="number" step="0.01" min="1" name="commission_tiers[${commissionTierIndex}][upto_amount]" class="form-control tier-upto" placeholder="e.g., 10000">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <span class="tier-range-display badge bg-light text-dark">Rs. 0 - ?</span>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="fa fa-times"></i></button>
                        </div>
                    </div>
                `;
                $('#commission_tiers_container').append(html);
                commissionTierIndex++;
                updateTierDisplay();
            });

            // Remove row handler update
            // Remove row handler update
            $(document).on('click', '.remove-row', function() {
                var row = $(this).closest('.row');
                var isAllowance = row.hasClass('allowance-row');
                var isDeduction = row.hasClass('deduction-row');
                row.remove();
                if (isAllowance) updateAllowanceDisplay();
                if (isDeduction) updateDeductionDisplay();
                updateTierDisplay();
                validateTiers();
            });

            // Update allowance display
            function updateAllowanceDisplay() {
                var allowances = $('.allowance-row');
                var count = allowances.length;

                if (count > 0) {
                    $('#allowance_headers').show();
                    $('#no_allowances_message').hide();
                } else {
                    $('#allowance_headers').hide();
                    $('#no_allowances_message').show();
                }

                // Update numbers
                allowances.each(function(index) {
                    $(this).find('.allowance-number').text(index + 1);
                });
            }

            // Initial call
            updateAllowanceDisplay();

            // Add Allowance Row
            $('#addAllowance').click(function() {
                var num = $('.allowance-row').length + 1;
                var html = `
                    <div class="row mb-2 allowance-row align-items-center">
                        <div class="col-md-1 text-center">
                            <span class="badge bg-success allowance-number">${num}</span>
                        </div>
                        <div class="col-md-1 text-center">
                            <div class="form-check form-switch d-flex justify-content-center">
                                <input class="form-check-input allowance-active" type="checkbox" name="allowances[${allowanceIndex}][is_active]" value="1" checked>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="allowances[${allowanceIndex}][name]" class="form-control" placeholder="e.g., Housing Allowance">
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" step="0.01" name="allowances[${allowanceIndex}][amount]" class="form-control allowance-amount" placeholder="Amount">
                            </div>
                        </div>
                        <div class="col-md-2">
                             <button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="fa fa-times"></i> Remove</button>
                        </div>
                    </div>
                `;
                $('#allowances_container').append(html);
                allowanceIndex++;
                updateAllowanceDisplay();
            });

            // --- Deductions Functions ---
            function updateDeductionDisplay() {
                var deductions = $('.deduction-row');
                if (deductions.length > 0) {
                    $('#deduction_headers').show();
                    $('#no_deductions_message').hide();
                } else {
                    $('#deduction_headers').hide();
                    $('#no_deductions_message').show();
                }
                deductions.each(function(index) {
                    $(this).find('.deduction-number').text(index + 1);
                });
                recalculate();
            }

            // Initial Deduction Display
            updateDeductionDisplay();

            $('#addDeduction').click(function() {
                var num = $('.deduction-row').length + 1;
                var html = `
                    <div class="row mb-2 deduction-row align-items-center">
                        <div class="col-md-1 text-center">
                            <span class="badge bg-danger deduction-number">${num}</span>
                        </div>
                        <div class="col-md-1 text-center">
                            <div class="form-check form-switch d-flex justify-content-center">
                                <input class="form-check-input deduction-active" type="checkbox" name="deductions[${deductionIndex}][is_active]" value="1" checked>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="deductions[${deductionIndex}][name]" class="form-control" placeholder="e.g., Tax">
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" step="0.01" name="deductions[${deductionIndex}][amount]" class="form-control deduction-amount" placeholder="Amount">
                            </div>
                        </div>
                        <div class="col-md-2">
                             <button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="fa fa-times"></i></button>
                        </div>
                    </div>
                `;
                $('#deductions_container').append(html);
                deductionIndex++;
                deductionIndex++;
                updateDeductionDisplay();
            });

            // --- Attendance Rules JS (Enhanced) ---

            // Function to recalculate all rule ranges for a container
            function recalculateRuleRanges(containerSelector, validationSelector, headersSelector) {
                var rules = $(containerSelector + ' .rule-row');
                var currentMin = 1;
                var hasOpenEnded = false;
                var isValid = true;
                var errorMsg = '';

                // Show/hide headers based on rule count
                if (rules.length > 0) {
                    $(headersSelector).show();
                } else {
                    $(headersSelector).hide();
                }

                // Validate and update ranges (No sorting to keep focus stable)
                currentMin = 1;
                hasOpenEnded = false;

                rules.each(function(index) {
                    var row = $(this);

                    // Get values
                    var maxVal = row.find('.rule-max').val();
                    var maxMinutes = (maxVal === '' || maxVal === null) ? null : parseInt(maxVal);
                    var isOpenEnded = (maxMinutes === null);

                    // Validate: Only one open-ended rule
                    if (isOpenEnded && hasOpenEnded) {
                        isValid = false;
                        errorMsg = 'Only one open-ended (∞) rule is allowed.';
                        row.find('.rule-max').addClass('is-invalid');
                    } else {
                        row.find('.rule-max').removeClass('is-invalid');
                    }

                    // Validate: max must be >= currentMin
                    if (!isOpenEnded && maxMinutes < currentMin) {
                        isValid = false;
                        errorMsg = 'Rule ' + (index + 1) + ': Max (' + maxMinutes +
                            ') cannot be less than calculated Min (' + currentMin + ').';
                        row.find('.rule-max').addClass('is-invalid');
                    }

                    // Update min input and display
                    row.find('.rule-min').val(currentMin);
                    var displayMax = isOpenEnded ? '∞' : maxMinutes;
                    row.find('.range-display').text(currentMin + ' - ' + displayMax);

                    // Update placeholder for open-ended
                    if (isOpenEnded) {
                        row.find('.rule-max').attr('placeholder', '∞');
                    } else {
                        row.find('.rule-max').attr('placeholder', '');
                    }

                    // Update next min
                    if (!isOpenEnded && maxMinutes !== null) {
                        currentMin = maxMinutes + 1;
                    }

                    if (isOpenEnded) {
                        hasOpenEnded = true;
                    }
                });

                // Show/hide validation error
                if (!isValid) {
                    $(validationSelector).html('<i class="fa fa-exclamation-triangle"></i> ' + errorMsg).show();
                } else {
                    $(validationSelector).hide();
                }

                return isValid;
            }

            // Function to get next min for a new rule
            function getNextMinForContainer(containerSelector) {
                var rules = $(containerSelector + ' .rule-row');
                var maxMin = 1;
                var hasOpenEnded = false;

                rules.each(function() {
                    var maxVal = $(this).find('.rule-max').val();
                    if (maxVal === '' || maxVal === null) {
                        hasOpenEnded = true;
                    } else {
                        var max = parseInt(maxVal);
                        if (max >= maxMin) {
                            maxMin = max + 1;
                        }
                    }
                });

                return {
                    nextMin: maxMin,
                    hasOpenEnded: hasOpenEnded
                };
            }

            // Add Late Rule
            $('#addLateRule').click(function() {
                var info = getNextMinForContainer('#late_rules_container');

                if (info.hasOpenEnded) {
                    Swal.fire('Cannot Add Rule',
                        'An open-ended rule already exists. No new rules can be added after it.',
                        'warning');
                    return;
                }

                var html = `
                    <div class="row mb-2 rule-row late-rule-row align-items-center">
                        <div class="col-3">
                            <span class="badge bg-light text-dark range-display">${info.nextMin} - ?</span>
                            <input type="hidden" name="late_rules[${lateRuleIndex}][min_minutes]" class="rule-min" value="${info.nextMin}">
                        </div>
                        <div class="col-2">
                            <input type="number" name="late_rules[${lateRuleIndex}][max_minutes]" class="form-control form-control-sm rule-max" placeholder="">
                        </div>
                        <div class="col-2">
                            <select name="late_rules[${lateRuleIndex}][type]" class="form-select form-select-sm">
                                <option value="fixed">Rs.</option>
                                <option value="percentage">%</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <input type="number" step="0.01" name="late_rules[${lateRuleIndex}][amount]" class="form-control form-control-sm rule-amount" placeholder="Amt">
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-rule"><i class="fa fa-times"></i></button>
                        </div>
                    </div>
                `;
                $('#late_rules_container').append(html);
                lateRuleIndex++;
                recalculateRuleRanges('#late_rules_container', '#late_rules_validation',
                    '#late_rules_headers');
            });

            // Add Early Rule
            $('#addEarlyRule').click(function() {
                var info = getNextMinForContainer('#early_rules_container');

                if (info.hasOpenEnded) {
                    Swal.fire('Cannot Add Rule',
                        'An open-ended rule already exists. No new rules can be added after it.',
                        'warning');
                    return;
                }

                var html = `
                    <div class="row mb-2 rule-row early-rule-row align-items-center">
                        <div class="col-3">
                            <span class="badge bg-light text-dark range-display">${info.nextMin} - ?</span>
                            <input type="hidden" name="early_rules[${earlyRuleIndex}][min_minutes]" class="rule-min" value="${info.nextMin}">
                        </div>
                        <div class="col-2">
                            <input type="number" name="early_rules[${earlyRuleIndex}][max_minutes]" class="form-control form-control-sm rule-max" placeholder="">
                        </div>
                        <div class="col-2">
                            <select name="early_rules[${earlyRuleIndex}][type]" class="form-select form-select-sm">
                                <option value="fixed">Rs.</option>
                                <option value="percentage">%</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <input type="number" step="0.01" name="early_rules[${earlyRuleIndex}][amount]" class="form-control form-control-sm rule-amount" placeholder="Amt">
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-rule"><i class="fa fa-times"></i></button>
                        </div>
                    </div>
                `;
                $('#early_rules_container').append(html);
                earlyRuleIndex++;
                recalculateRuleRanges('#early_rules_container', '#early_rules_validation',
                    '#early_rules_headers');
            });

            // Remove rule handler - recalculate ranges after removal
            $(document).on('click', '.remove-rule', function() {
                var row = $(this).closest('.rule-row');
                var isLate = row.hasClass('late-rule-row');
                var isEarly = row.hasClass('early-rule-row');

                row.remove();

                if (isLate) {
                    recalculateRuleRanges('#late_rules_container', '#late_rules_validation',
                        '#late_rules_headers');
                }
                if (isEarly) {
                    recalculateRuleRanges('#early_rules_container', '#early_rules_validation',
                        '#early_rules_headers');
                }
            });

            // Recalculate ranges when max_minutes changes
            $(document).on('input change', '.rule-max', function() {
                var row = $(this).closest('.rule-row');
                var isLate = row.hasClass('late-rule-row');
                var isEarly = row.hasClass('early-rule-row');

                if (isLate) {
                    recalculateRuleRanges('#late_rules_container', '#late_rules_validation',
                        '#late_rules_headers');
                }
                if (isEarly) {
                    recalculateRuleRanges('#early_rules_container', '#early_rules_validation',
                        '#early_rules_headers');
                }
            });

            // Initial calculation on page load
            recalculateRuleRanges('#late_rules_container', '#late_rules_validation', '#late_rules_headers');
            recalculateRuleRanges('#early_rules_container', '#early_rules_validation', '#early_rules_headers');

            // Update remove-row handler to also update allowance display
            $(document).off('click', '.remove-row').on('click', '.remove-row', function() {
                $(this).closest('.row').remove();
                updateTierDisplay();
                validateTiers();
                updateAllowanceDisplay();
            });

            // --- Calculation Logic ---
            function parseVal(selector) {
                var val = parseFloat($(selector).val());
                return isNaN(val) ? 0 : val;
            }

            function recalculate() {
                var baseSalary = parseVal('#base_salary');
                var useDaily = $('#use_daily_wages').is(':checked');
                var dailyWages = parseVal('#daily_wages');

                var baseTotal = baseSalary;
                if (useDaily) {
                    baseTotal += (dailyWages * 30); // Est. 30 days
                }

                // Allowances
                var totalAllowances = 0;
                $('.allowance-row').each(function() {
                    var isActive = $(this).find('.allowance-active').is(':checked');
                    if (isActive) {
                        var amt = parseFloat($(this).find('.allowance-amount').val()) || 0;
                        totalAllowances += amt;
                    }
                });

                // Deductions
                var totalDeductions = 0;
                $('.deduction-row').each(function() {
                    var isActive = $(this).find('.deduction-active').is(':checked');
                    if (isActive) {
                        var amt = parseFloat($(this).find('.deduction-amount').val()) || 0;
                        totalDeductions += amt;
                    }
                });

                var net = baseTotal + totalAllowances - totalDeductions;

                $('#summary_base').text(baseTotal.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
                $('#summary_allowances').text(totalAllowances.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
                $('#summary_deductions').text(totalDeductions.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
                $('#summary_net').text('Rs. ' + net.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
            }

            $(document).on('input change',
                '#base_salary, #daily_wages, #use_daily_wages, .allowance-amount, .allowance-active, .deduction-amount, .deduction-active',
                function() {
                    recalculate();
                });

            // Toggle Daily Wages
            $('#use_daily_wages').change(function() {
                if ($(this).is(':checked')) {
                    $('#daily_wages').prop('disabled', false);
                    $('#attendance_rules_section').slideDown();
                } else {
                    $('#daily_wages').prop('disabled', true).val('');
                    $('#attendance_rules_section').slideUp();
                }
                recalculate();
            });
            // Initial Check
            if ($('#use_daily_wages').is(':checked')) {
                $('#attendance_rules_section').show();
            } else {
                $('#attendance_rules_section').hide();
            }

            // Initial Recalc
            recalculate();

            // Form Submit with validation
            $('#salaryForm').submit(function(e) {
                // Validate tiers before submitting
                if (!validateTiers()) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    Swal.fire('Validation Error', 'Please fix commission tier errors before saving.',
                        'error');
                    return;
                }

                // Validate attendance rules if daily wages is enabled
                if ($('#use_daily_wages').is(':checked')) {
                    var lateValid = recalculateRuleRanges('#late_rules_container', '#late_rules_validation',
                        '#late_rules_headers');
                    var earlyValid = recalculateRuleRanges('#early_rules_container',
                        '#early_rules_validation', '#early_rules_headers');

                    if (!lateValid || !earlyValid) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        Swal.fire('Validation Error',
                            'Please fix attendance deduction rule errors before saving.', 'error');
                        return;
                    }
                }
                // Custom AJAX removed - global validation handles it
            });
        });
    </script>
@endsection
