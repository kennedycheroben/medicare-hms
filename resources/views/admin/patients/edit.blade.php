@extends('layouts.admin')

@section('title', 'Edit Patient - ' . $patient->mrn)

@section('content')
<div class="container-fluid p-0" style="max-width: 960px;">
    <div class="mb-4">
        <a href="{{ route('admin.patients.show', $patient->id) }}" class="text-decoration-none text-muted small">&larr; Back to Patient Profile</a>
        <div class="d-flex align-items-center gap-2 mt-2 mb-1">
            <h1 class="h3 fw-bold text-dark mb-0">Edit Patient Demographics</h1>
            <code class="fs-6 fw-bold text-primary px-2 py-1 bg-primary bg-opacity-10 rounded">{{ $patient->mrn }}</code>
        </div>
        <p class="text-muted small mb-0">Update contact and demographic details. Medical Record Number (MRN) is immutable.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form action="{{ route('admin.patients.update', $patient->id) }}" method="POST">
                @csrf
                @method('PUT')

                <h3 class="h6 fw-bold text-uppercase text-muted border-bottom pb-2 mb-3">1. Legal Demographics</h3>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label fw-semibold small text-dark">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name', $patient->first_name) }}" required>
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="last_name" class="form-label fw-semibold small text-dark">Last / Family Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name', $patient->last_name) }}" required>
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="date_of_birth" class="form-label fw-semibold small text-dark">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $patient->date_of_birth ? $patient->date_of_birth->format('Y-m-d') : '') }}" max="{{ date('Y-m-d') }}" required>
                        @error('date_of_birth')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="gender" class="form-label fw-semibold small text-dark">Biological Sex / Gender <span class="text-danger">*</span></label>
                        <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required>
                            <option value="male" {{ old('gender', $patient->gender) === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $patient->gender) === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender', $patient->gender) === 'other' ? 'selected' : '' }}>Other</option>
                            <option value="unspecified" {{ old('gender', $patient->gender) === 'unspecified' ? 'selected' : '' }}>Unspecified</option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="national_id" class="form-label fw-semibold small text-dark">National ID / Passport Number</label>
                        <input type="text" class="form-control @error('national_id') is-invalid @enderror" id="national_id" name="national_id" value="{{ old('national_id', $patient->national_id) }}">
                        @error('national_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <h3 class="h6 fw-bold text-uppercase text-muted border-bottom pb-2 mb-3">2. Contact & Address Details</h3>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-semibold small text-dark">Primary Phone Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $patient->phone) }}" required>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label fw-semibold small text-dark">Email Address (Optional)</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $patient->email) }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-8">
                        <label for="address" class="form-label fw-semibold small text-dark">Residential Address</label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $patient->address) }}">
                    </div>

                    <div class="col-md-4">
                        <label for="city" class="form-label fw-semibold small text-dark">City / Town</label>
                        <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $patient->city) }}">
                    </div>
                </div>

                <h3 class="h6 fw-bold text-uppercase text-muted border-bottom pb-2 mb-3">3. Clinical Baselines & Emergency Contact</h3>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="blood_group" class="form-label fw-semibold small text-dark">Blood Group</label>
                        <select class="form-select @error('blood_group') is-invalid @enderror" id="blood_group" name="blood_group">
                            <option value="">Unknown</option>
                            @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                                <option value="{{ $bg }}" {{ old('blood_group', $patient->blood_group) === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label for="allergies" class="form-label fw-semibold small text-dark">Known Drug or Food Allergies</label>
                        <input type="text" class="form-control @error('allergies') is-invalid @enderror" id="allergies" name="allergies" value="{{ old('allergies', $patient->allergies) }}">
                    </div>

                    <div class="col-md-4">
                        <label for="emergency_contact_name" class="form-label fw-semibold small text-dark">Next of Kin / Contact Name</label>
                        <input type="text" class="form-control @error('emergency_contact_name') is-invalid @enderror" id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name', $patient->emergency_contact_name) }}">
                    </div>

                    <div class="col-md-4">
                        <label for="emergency_contact_phone" class="form-label fw-semibold small text-dark">Next of Kin Phone</label>
                        <input type="text" class="form-control @error('emergency_contact_phone') is-invalid @enderror" id="emergency_contact_phone" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $patient->emergency_contact_phone) }}">
                    </div>

                    <div class="col-md-4">
                        <label for="emergency_contact_relationship" class="form-label fw-semibold small text-dark">Relationship</label>
                        <input type="text" class="form-control @error('emergency_contact_relationship') is-invalid @enderror" id="emergency_contact_relationship" name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship', $patient->emergency_contact_relationship) }}">
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $patient->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label fw-medium text-dark" for="is_active">
                                Active patient record (uncheck to archive)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('admin.patients.show', $patient->id) }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save me-1"></i> Update Patient Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
