@extends('layouts.admin')

@section('title', 'Edit Physician Profile - ' . $doctor->name)

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.doctors.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Directory
            </a>
            <div>
                <h1 class="h3 fw-bold text-dark mb-0">Edit Physician Profile</h1>
                <span class="text-muted small">Update verified hospital credentials, department, and patient consultation hours</span>
            </div>
        </div>
        <div>
            @if($doctor->is_published)
                <span class="badge bg-success-subtle text-success border border-success-subtle p-2">
                    <i class="bi bi-globe me-1"></i> Publicly Published
                </span>
            @else
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle p-2">
                    <i class="bi bi-eye-slash me-1"></i> Unpublished Draft
                </span>
            @endif
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.doctors.update', $doctor->id) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-bold small">Doctor Full Name & Titles <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $doctor->name) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label for="department_id" class="form-label fw-bold small">Clinical Department <span class="text-danger">*</span></label>
                        <select name="department_id" id="department_id" class="form-select" required>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id', $doctor->department_id) == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="specialty" class="form-label fw-bold small">Medical Specialty / Sub-specialty <span class="text-danger">*</span></label>
                        <input type="text" name="specialty" id="specialty" class="form-control" value="{{ old('specialty', $doctor->specialty) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label for="qualifications" class="form-label fw-bold small">Degrees & Board Certifications <span class="text-danger">*</span></label>
                        <input type="text" name="qualifications" id="qualifications" class="form-control" value="{{ old('qualifications', $doctor->qualifications) }}" required>
                        <div class="form-text extra-small">Example: MBChB (UoN), MMed, Fellowship in Cardiology</div>
                    </div>

                    <div class="col-md-4">
                        <label for="experience_years" class="form-label fw-bold small">Years of Clinical Experience <span class="text-danger">*</span></label>
                        <input type="number" name="experience_years" id="experience_years" class="form-control" min="0" max="70" value="{{ old('experience_years', $doctor->experience_years) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label for="room_number" class="form-label fw-bold small">Consultation Room / Office</label>
                        <input type="text" name="room_number" id="room_number" class="form-control" value="{{ old('room_number', $doctor->room_number) }}" placeholder="e.g. Suite 304, Block B">
                    </div>

                    <div class="col-md-4">
                        <label for="is_available" class="form-label fw-bold small">Patient Availability Status <span class="text-danger">*</span></label>
                        <select name="is_available" id="is_available" class="form-select" required>
                            <option value="1" {{ old('is_available', $doctor->is_available) ? 'selected' : '' }}>Accepting Appointments (Active)</option>
                            <option value="0" {{ !old('is_available', $doctor->is_available) ? 'selected' : '' }}>On Leave / Unavailable</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="consultation_days" class="form-label fw-bold small">Consultation Days</label>
                        <input type="text" name="consultation_days" id="consultation_days" class="form-control" value="{{ old('consultation_days', $doctor->consultation_days) }}" placeholder="e.g. Monday, Wednesday, Friday">
                    </div>

                    <div class="col-md-6">
                        <label for="consultation_hours" class="form-label fw-bold small">Consultation Hours</label>
                        <input type="text" name="consultation_hours" id="consultation_hours" class="form-control" value="{{ old('consultation_hours', $doctor->consultation_hours) }}" placeholder="e.g. 09:00 AM - 01:00 PM">
                    </div>

                    <div class="col-md-12">
                        <label for="languages" class="form-label fw-bold small">Languages Spoken</label>
                        <input type="text" name="languages" id="languages" class="form-control" value="{{ old('languages', $doctor->languages) }}" placeholder="e.g. English, Swahili">
                    </div>

                    <div class="col-12">
                        <label for="bio" class="form-label fw-bold small">Professional Biography <span class="text-danger">*</span></label>
                        <textarea name="bio" id="bio" rows="5" class="form-control" required>{{ old('bio', $doctor->bio) }}</textarea>
                        <div class="form-text extra-small">Do not invent credentials. Only include hospital-verified clinical backgrounds.</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('admin.doctors.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
