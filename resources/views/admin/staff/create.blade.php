@extends('layouts.admin')

@section('title', 'Add Staff Member')

@section('content')
<div class="container-fluid p-0" style="max-width: 900px;">
    <div class="mb-4">
        <a href="{{ route('admin.staff.index') }}" class="text-decoration-none text-muted small">&larr; Back to Staff Directory</a>
        <h1 class="h3 fw-bold text-dark mt-2 mb-1">Provision New Staff Account</h1>
        <p class="text-muted small mb-0">Create an authenticated staff account and assign least-privilege role permissions.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form action="{{ route('admin.staff.store') }}" method="POST">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-semibold small text-dark">Full Legal Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Dr. Jane Doe">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label fw-semibold small text-dark">Staff Institutional Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required placeholder="name@medicare.local">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="role" class="form-label fw-semibold small text-dark">RBAC Role Assignment <span class="text-danger">*</span></label>
                        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                            <option value="">Select Role...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->value }}" {{ old('role') === $role->value ? 'selected' : '' }}>
                                    {{ $role->label() }} ({{ $role->value }})
                                </option>
                            @endforeach
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="department_id" class="form-label fw-semibold small text-dark">Clinical Department</label>
                        <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id">
                            <option value="">General / Cross-Departmental</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="doctor_id" class="form-label fw-semibold small text-dark">Link to Doctor Profile (if Medical Doctor)</label>
                        <select class="form-select @error('doctor_id') is-invalid @enderror" id="doctor_id" name="doctor_id">
                            <option value="">None / Not Applicable</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}" {{ old('doctor_id') == $doc->id ? 'selected' : '' }}>
                                    {{ $doc->name }} ({{ $doc->specialty }})
                                </option>
                            @endforeach
                        </select>
                        @error('doctor_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label fw-semibold small text-dark">Initial Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required minlength="8" placeholder="Minimum 8 characters">
                        <div class="form-text small">Hashed using Bcrypt (cost 12) per security baseline.</div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                            <label class="form-check-label fw-medium text-dark" for="is_active">
                                Account is active and authorized to log in
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-person-check-fill me-1"></i> Create Staff Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
