@extends('admin_panel.layout.app')
@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <h3>Edit Transport</h3>
                <form action="{{ route('transport.update', $transport->id) }}" method="POST">
                    @csrf

                    <input type="hidden" name="id" value="{{ $transport->id }}">

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label>Name:</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $transport->name) }}">
                            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">نام</label>
                            <input type="text" class="form-control text-end" name="name_ur" dir="rtl"
                                value="{{ old('name_ur', $transport->name_ur ?? '') }}">
                        </div>

                        <div class="col-md-6">
                            <label>Company Name:</label>
                            <input type="text" class="form-control" name="company_name"
                                value="{{ old('company_name', $transport->company_name) }}">
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label>Address:</label>
                            <textarea class="form-control" name="address"
                                rows="3">{{ old('address', $transport->address) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">پتہ</label>
                            <textarea rows="3" class="form-control text-end" name="address_ur"
                                dir="rtl">{{ old('address_ur', $transport->address_ur ?? '') }}</textarea>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label>Mobile:</label>
                            <input type="text" class="form-control" name="mobile"
                                value="{{ old('mobile', $transport->mobile) }}">
                            @error('mobile') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="col-md-6">
                            <label>Email:</label>
                            <input type="email" class="form-control" name="email"
                                value="{{ old('email', $transport->email) }}">
                            @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <div class="text-center">
                        <button class="btn btn-primary" type="submit">Update Transport</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection