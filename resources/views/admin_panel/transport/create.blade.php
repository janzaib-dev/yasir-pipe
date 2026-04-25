@extends('admin_panel.layout.app')
@section('content')
<div class="main-content py-4">
    <div class="main-content-inner">
        <div class="container">
            <div class="card shadow rounded">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background: #02007c; color:white ">
                    <h4 class="mb-0" style="color: white !important">Add New Transport</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('transport.store') }}" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Name (English)</label>
                                <input type="text" class="form-control" name="name" value="{{ old('name') }}" placeholder="Enter name">
                                @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">نام (اردو)</label>
                                <input type="text" class="form-control text-end" name="name_ur" dir="rtl"
                                       value="{{ old('name_ur', $transport->name_ur ?? '') }}" placeholder="نام درج کریں">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Company Name</label>
                                <input type="text" class="form-control" name="company_name" value="{{ old('company_name') }}" placeholder="Enter company name">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Mobile</label>
                                <input type="text" class="form-control" name="mobile" value="{{ old('mobile') }}" placeholder="03XX-XXXXXXX">
                                @error('mobile') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="example@email.com">
                                @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="3" placeholder="Enter address">{{ old('address') }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">پتہ (اردو)</label>
                                <textarea class="form-control text-end" name="address_ur" rows="3" dir="rtl" placeholder="پتہ درج کریں">{{ old('address_ur', $transport->address_ur ?? '') }}</textarea>
                            </div>
                        </div>

                        <div class="text-center">
                            <button class="btn btn-success px-5" type="submit">Save Transport</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
