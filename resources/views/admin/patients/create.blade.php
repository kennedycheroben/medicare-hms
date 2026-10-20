@extends('layouts.admin')

@section('title', 'Register New Patient')

@section('content')
<div class="container-fluid p-0" style="max-width: 960px;">
    <div class="mb-4">
        <a href="{{ route('admin.patients.index') }}" class="text-decoration-none text-muted small">&larr; Back to Patient Directory</a>
        <h1 class="h3 fw-bold text-dark mt-2 mb-1">Patient Master Index Registration</h1>
        <p class="text-muted small mb-0">Enroll a patient into the hospital registry and generate an authoritative Medical Record Number (MRN).</p>
    </div>

    <!-- Potential Duplicates Warning Banner -->
    @if(isset($potentialDuplicates) && $potentialDuplicates->isNotEmpty())
        <div class="card border-warning shadow-sm rounded-4 mb-4 bg-warning bg-opacity-10">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-2 text-warning-emphasis">
                    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                    <h2 class="h5 fw-bold mb-0">Potential Duplicate Patient Records Found</h2>
                </div>
                <p class="small text-dark mb-3">
                    One or more existing patients match the contact or demographic identifiers provided. Please verify if the patient is already enrolled to prevent fragmenting clinical health records.
                </p>

                <div class="table-responsive bg-white rounded-3 border mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Existing MRN</th>
                                <th>Full Legal Name</th>
                                <th>Date of Birth</th>
                                <th>Phone</th>
                                <th>National ID</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($potentialDuplicates as $match)
                                <tr>
                                    <td><code class="fw-bold">{{ $match->mrn }}</code></td>
                                    <td class="fw-semibold">{{ $match->full_name }}</td>
                                    <td>{{ $match->date_of_birth->format('Y-m-d') }} ({{ $match->age }} yrs)</td>
                                    <td><code>{{ $match->phone }}</code></td>
                                    <td>{{ $match->national_id ?? '—' }}</td>
                                    <td>
                                        <a href="{{ route('admin.patients.show', $match->id) }}" class="btn btn-xs btn-outline-primary py-0 px-2" target="_blank">
                                            View Profile &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-warning border-0 small mb-0 d-flex align-items-center justify-content-between">
                    <div>
                        <i class="bi bi-shield-exclamation me-1"></i> If you confirm this is indeed a distinct individual (e.g. sharing family phone), check the authorization box below to proceed with new registration.
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form action="{{ route('admin.patients.store') }}" method="POST">
                @csrf

                @if(isset($potentialDuplicates) && $potentialDuplicates->isNotEmpty())
                    <div class="bg-light p-3 rounded-3 mb-4 border border-warning">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="force_duplicate_override" value="1" id="force_duplicate_override" required>
                            <label class="form-check-label fw-semibold text-dark" for="force_duplicate_override">
                                I confirm this patient is distinct from the matches above and authorize new MRN generation.
                            </label>
                        </div>
                    </div>
                @endif

                <h3 class="h6 fw-bold text-uppercase text-muted border-bottom pb-2 mb-3">1. Legal Demographics</h3>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label fw-semibold small text-dark">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name', $formData['first_name'] ?? '') }}" required placeholder="e.g. Samuel">
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="last_name" class="form-label fw-semibold small text-dark">Last / Family Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name', $formData['last_name'] ?? '') }}" required placeholder="e.g. Kamau">
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="date_of_birth" class="form-label fw-semibold small text-dark">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $formData['date_of_birth'] ?? '') }}" max="{{ date('Y-m-d') }}" required>
                        @error('date_of_birth')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="gender" class="form-label fw-semibold small text-dark">Biological Sex / Gender <span class="text-danger">*</span></label>
                        <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required>
                            <option value="">Select...</option>
                            <option value="male" {{ old('gender', $formData['gender'] ?? '') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $formData['gender'] ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender', $formData['gender'] ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                            <option value="unspecified" {{ old('gender', $formData['gender'] ?? '') === 'unspecified' ? 'selected' : '' }}>Unspecified</option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="national_id" class="form-label fw-semibold small text-dark">National ID / Passport Number</label>
                        <input type="text" class="form-control @error('national_id') is-invalid @enderror" id="national_id" name="national_id" value="{{ old('national_id', $formData['national_id'] ?? '') }}" placeholder="e.g. 12345678">
                        @error('national_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <h3 class="h6 fw-bold text-uppercase text-muted border-bottom pb-2 mb-3">2. Contact & Address Details</h3>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-semibold small text-dark">Primary Phone Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $formData['phone'] ?? '') }}" required placeholder="e.g. +254 712 345 678">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label fw-semibold small text-dark">Email Address (Optional)</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $formData['email'] ?? '') }}" placeholder="patient@example.org">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-8">
                        <label for="address" class="form-label fw-semibold small text-dark">Residential Address</label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $formData['address'] ?? '') }}" placeholder="e.g. House 42, Parklands Ave">
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="city" class="form-label fw-semibold small text-dark">City / Town</label>
                        <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $formData['city'] ?? 'Nairobi') }}">
                        @error('city')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <h3 class="h6 fw-bold text-uppercase text-muted border-bottom pb-2 mb-3">3. Clinical Baselines & Emergency Contact</h3>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="blood_group" class="form-label fw-semibold small text-dark">Blood Group</label>
                        <select class="form-select @error('blood_group') is-invalid @enderror" id="blood_group" name="blood_group">
                            <option value="">Unknown</option>
                            <option value="A+" {{ old('blood_group', $formData['blood_group'] ?? '') === 'A+' ? 'selected' : '' }}>A+</option>
                            <option value="A-" {{ old('blood_group', $formData['blood_group'] ?? '') === 'A-' ? 'selected' : '' }}>A-</option>
                            <option value="B+" {{ old('blood_group', $formData['blood_group'] ?? '') === 'B+' ? 'selected' : '' }}>B+</option>
                            <option value="B-" {{ old('blood_group', $formData['blood_group'] ?? '') === 'B-' ? 'selected' : '' }}>B-</option>
                            <option value="AB+" {{ old('blood_group', $formData['blood_group'] ?? '') === 'AB+' ? 'selected' : '' }}>AB+</option>
                            <option value="AB-" {{ old('blood_group', $formData['blood_group'] ?? '') === 'AB-' ? 'selected' : '' }}>AB-</option>
                            <option value="O+" {{ old('blood_group', $formData['blood_group'] ?? '') === 'O+' ? 'selected' : '' }}>O+</option>
                            <option value="O-" {{ old('blood_group', $formData['blood_group'] ?? '') === 'O-' ? 'selected' : '' }}>O-</option>
                        </select>
                        @error('blood_group')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-8">
                        <label for="allergies" class="form-label fw-semibold small text-dark">Known Drug or Food Allergies</label>
                        <input type="text" class="form-control @error('allergies') is-invalid @enderror" id="allergies" name="allergies" value="{{ old('allergies', $formData['allergies'] ?? '') }}" placeholder="e.g. Penicillin, NSAIDs, Peanuts">
                        @error('allergies')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="emergency_contact_name" class="form-label fw-semibold small text-dark">Next of Kin / Contact Name</label>
                        <input type="text" class="form-control @error('emergency_contact_name') is-invalid @enderror" id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name', $formData['emergency_contact_name'] ?? '') }}" placeholder="e.g. Jane Kamau">
                    </div>

                    <div class="col-md-4">
                        <label for="emergency_contact_phone" class="form-label fw-semibold small text-dark">Next of Kin Phone</label>
                        <input type="text" class="form-control @error('emergency_contact_phone') is-invalid @enderror" id="emergency_contact_phone" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $formData['emergency_contact_phone'] ?? '') }}" placeholder="e.g. +254 700 000 000">
                    </div>

                    <div class="col-md-4">
                        <label for="emergency_contact_relationship" class="form-label fw-semibold small text-dark">Relationship</label>
                        <input type="text" class="form-control @error('emergency_contact_relationship') is-invalid @enderror" id="emergency_contact_relationship" name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship', $formData['emergency_contact_relationship'] ?? '') }}" placeholder="e.g. Spouse / Sibling">
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('admin.patients.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-person-check-fill me-1"></i> Register & Issue MRN
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
