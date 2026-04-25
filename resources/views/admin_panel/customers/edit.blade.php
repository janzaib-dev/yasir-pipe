@extends('admin_panel.layout.app')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400..700&display=swap');

        :root {
            --primary-color: #4f46e5;
            --bg-light: #f8fafc;
            --input-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-muted: #64748b;
            --text-dark: #1e293b;
        }

        .main-content { overflow: hidden; }
        .main-content-inner { padding: 10px; }

        .modern-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            border: 1px solid var(--border-color);
            padding: 20px;
            height: calc(100vh - 140px);
            display: flex;
            flex-direction: column;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .page-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-title i {
            color: var(--primary-color);
            background: #e0e7ff;
            padding: 8px;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 12px 16px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .input-group-modern {
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .modern-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
        }

        .modern-control {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.9rem;
            color: var(--text-dark);
            transition: all 0.2s ease;
            width: 100%;
        }

        .modern-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            outline: none;
            background: white;
        }

        .modern-control::placeholder { color: #cbd5e1; }

        select.modern-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        .section-label {
            grid-column: span 12;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-top: 8px;
            margin-bottom: 0px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        .btn-modern-primary {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 24px;
            font-weight: 600;
            transition: all 0.2s;
            box-shadow: 0 2px 5px rgba(79, 70, 229, 0.3);
        }

        .btn-modern-primary:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.4);
        }

        .btn-modern-secondary {
            background: #f1f5f9;
            color: var(--text-dark);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .btn-modern-secondary:hover { background: #e2e8f0; }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid p-0">

                <form action="{{ route('customers.update', $customer->id) }}" method="POST" class="needs-validation modern-card" novalidate>
                    @csrf

                    <!-- Header -->
                    <div class="page-header">
                        <h1 class="page-title">
                            <i class="fa fa-user-pen"></i> Edit Customer
                        </h1>
                        <div class="d-flex gap-2">
                            <a href="{{ route('customers.index') }}" class="btn-modern-secondary">
                                <i class="fa fa-times me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn-modern-primary">
                                <i class="fa fa-check me-1"></i> Update Customer
                            </button>
                        </div>
                    </div>

                    <!-- Form Content Grid -->
                    <div class="form-grid">

                        <!-- Section 1 -->
                        <div class="section-label mt-0">Basic Information</div>

                        <div class="input-group-modern" style="grid-column: span 2;">
                            <label class="modern-label">Customer Code</label>
                            <input type="text" class="modern-control bg-light" name="customer_id"
                                value="{{ $customer->customer_id }}" readonly>
                        </div>
                        <div class="input-group-modern" style="grid-column: span 2;">
                            <label class="modern-label">Customer Type <span class="text-danger">*</span></label>
                            <select class="modern-control" name="customer_type" required>
                                <option value="Main Customer" {{ $customer->customer_type == 'Main Customer' ? 'selected' : '' }}>Main Customer</option>
                                <option value="Walking Customer" {{ $customer->customer_type == 'Walking Customer' ? 'selected' : '' }}>Walking Customer</option>
                            </select>
                        </div>
                        <div class="input-group-modern" style="grid-column: span 3;">
                            <label class="modern-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="modern-control" name="customer_name" required
                                value="{{ $customer->customer_name }}" placeholder="Customer Name">
                        </div>
                        <div class="input-group-modern" style="grid-column: span 2;">
                            <label class="modern-label">Mobile</label>
                            <input type="text" class="modern-control" name="mobile" placeholder="0300-1234567"
                                value="{{ $customer->mobile }}">
                        </div>
                        <div class="input-group-modern" style="grid-column: span 3;">
                            <label class="modern-label">Region (Zone)</label>
                            <select class="modern-control" name="zone">
                                <option value="">-- Select Zone --</option>
                                @foreach($zones as $z)
                                    <option value="{{ $z->id }}" {{ $customer->zone == $z->id ? 'selected' : '' }}>{{ $z->zone }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Financials / Address Line 2 -->
                        <div class="input-group-modern" style="grid-column: span 3;">
                            <label class="modern-label">Address</label>
                            <input type="text" class="modern-control" name="address"
                                placeholder="Shop No, Street Area, City"
                                value="{{ $customer->address }}">
                        </div>
                        <div class="input-group-modern" style="grid-column: span 3;">
                            <label class="modern-label text-danger">Opening Balance (Dr)</label>
                            <input type="number" step="0.01" class="modern-control" name="opening_balance"
                                value="{{ $customer->opening_balance ?? 0 }}" style="border-color: #fca5a5; background: #fff1f2;">
                        </div>
                        <div class="input-group-modern" style="grid-column: span 3;">
                            <label class="modern-label text-success">Credit Limit <small class="text-muted fw-normal">(0 = Ulmtd)</small></label>
                            <input type="number" step="0.01" class="modern-control" name="balance_range"
                                value="{{ $customer->balance_range ?? 0 }}" style="border-color: #86efac; background: #f0fdf4;">
                        </div>
                        <div class="input-group-modern" style="grid-column: span 3;">
                            <label class="modern-label text-primary">Payment Reminder Day</label>
                            <select class="modern-control" name="reminder_day" style="border-color: #93c5fd; background: #eff6ff;">
                                <option value="">No Reminder</option>
                                <option value="Monday" {{ $customer->reminder_day == 'Monday' ? 'selected' : '' }}>Monday</option>
                                <option value="Tuesday" {{ $customer->reminder_day == 'Tuesday' ? 'selected' : '' }}>Tuesday</option>
                                <option value="Wednesday" {{ $customer->reminder_day == 'Wednesday' ? 'selected' : '' }}>Wednesday</option>
                                <option value="Thursday" {{ $customer->reminder_day == 'Thursday' ? 'selected' : '' }}>Thursday</option>
                                <option value="Friday" {{ $customer->reminder_day == 'Friday' ? 'selected' : '' }}>Friday</option>
                                <option value="Saturday" {{ $customer->reminder_day == 'Saturday' ? 'selected' : '' }}>Saturday</option>
                                <option value="Sunday" {{ $customer->reminder_day == 'Sunday' ? 'selected' : '' }}>Sunday</option>
                            </select>
                        </div>

                        {{-- ============ COMMENTED OUT FIELDS ============ --}}
                        {{-- Urdu Name --}}
                        {{-- <div class="input-group-modern" style="grid-column: span 2;">
                            <label class="modern-label ms-auto"
                                style="font-family: 'Noto Nastaliq Urdu', serif;">کسٹمر کا نام</label>
                            <input type="text" class="modern-control text-end" dir="rtl" name="customer_name_ur"
                                value="{{ $customer->customer_name_ur }}"
                                style="font-family: 'Noto Nastaliq Urdu', serif;">
                        </div> --}}

                        {{-- CNIC / NTN --}}
                        {{-- <div class="input-group-modern" style="grid-column: span 3;">
                            <label class="modern-label">NTN / CNIC</label>
                            <input type="text" class="modern-control" name="cnic" value="{{ $customer->cnic }}">
                        </div> --}}

                        {{-- Filer Type --}}
                        {{-- <div class="input-group-modern" style="grid-column: span 3;">
                            <label class="modern-label">Filer Type</label>
                            <select class="modern-control" name="filer_type">
                                <option value="filer" {{ $customer->filer_type == 'filer' ? 'selected' : '' }}>Filer</option>
                                <option value="non filer" {{ $customer->filer_type == 'non filer' ? 'selected' : '' }}>Non Filer</option>
                                <option value="exempt" {{ $customer->filer_type == 'exempt' ? 'selected' : '' }}>Exempt</option>
                            </select>
                        </div> --}}

                        {{-- Zone --}}
                        {{-- Zone field moved to basic info section --}}

                        {{-- Contact Person --}}
                        {{-- <div class="input-group-modern" style="grid-column: span 3;">
                            <label class="modern-label">Contact Person</label>
                            <input type="text" class="modern-control" name="contact_person" value="{{ $customer->contact_person }}">
                        </div> --}}

                        {{-- Email --}}
                        {{-- <div class="input-group-modern" style="grid-column: span 4;">
                            <label class="modern-label">Email</label>
                            <input type="email" class="modern-control" name="email_address" value="{{ $customer->email_address }}">
                        </div> --}}

                        {{-- Secondary Contact --}}
                        {{-- <div class="section-label">Secondary Contact</div>
                        <div class="input-group-modern" style="grid-column: span 4;">
                            <input type="text" class="modern-control" name="contact_person_2" value="{{ $customer->contact_person_2 }}">
                        </div>
                        <div class="input-group-modern" style="grid-column: span 4;">
                            <input type="text" class="modern-control" name="mobile_2" value="{{ $customer->mobile_2 }}">
                        </div>
                        <div class="input-group-modern" style="grid-column: span 4;">
                            <input type="email" class="modern-control" name="email_address_2" value="{{ $customer->email_address_2 }}">
                        </div> --}}

                    </div>
                    <!-- End Grid -->

                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let nameField = document.querySelector('input[name="customer_name"]');
            if (nameField) nameField.focus();

            const form = document.querySelector('form.needs-validation');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (!form.checkValidity()) {
                        form.classList.add('was-validated');
                        let invalid = form.querySelector(':invalid');
                        if (invalid) invalid.focus();
                        return;
                    }
                    Swal.fire({
                        title: 'Confirm Update',
                        text: "Are you sure you want to update this customer?",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#4f46e5',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, Update!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            }
        });
    </script>
@endsection
